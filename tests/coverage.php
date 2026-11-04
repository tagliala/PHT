<?php
/**
 * Line coverage of the library, measured with phpdbg, which ships with PHP and
 * needs no extension and no dependency.
 *
 *   php tests/coverage.php              # report and check the floors
 *   php tests/coverage.php --min=12     # require a different overall floor
 *   php tests/coverage.php --report     # report only, no floors
 *
 * The leak cases are not part of this run: phpdbg records every opcode, so its
 * own memory growth makes them fail.
 */

if (!function_exists('phpdbg_start_oplog')) {
    $command = 'phpdbg -qrr ' . escapeshellarg(__FILE__);
    foreach (array_slice($argv, 1) as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }
    passthru($command, $status);
    exit($status);
}

$root = dirname(__DIR__);
$reportOnly = in_array('--report', $argv, true);
$minOverall = 3.0;
$minPerDirectory = array('Network/' => 60.0, 'Utils/' => 30.0);
foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--min=([0-9.]+)$/', $argument, $match)) {
        $minOverall = (float)$match[1];
    }
}

phpdbg_start_oplog();
require $root . '/tests/bootstrap.php';
$files = array('declarations', 'parsing', 'payloads', 'network', 'cache', 'pending');
foreach ($files as $file) {
    require $root . '/tests/' . $file . '.php';
}
$executed = phpdbg_end_oplog();
$executable = phpdbg_get_executable();

$totalLines = 0;
$totalRun = 0;
$touched = 0;
$fileCount = 0;
$perDirectory = array();
foreach ($executable as $path => $lines) {
    if (strpos($path, $root . '/PHT/') !== 0 || substr($path, -4) !== '.php') {
        continue;
    }
    $executableLines = array_keys($lines);
    $covered = isset($executed[$path]) ? array_intersect($executableLines, array_keys($executed[$path])) : array();
    $relative = substr($path, strlen($root) + 1);
    $parts = explode('/', $relative);
    $directory = count($parts) > 2 ? $parts[1] . '/' : '(top level)';
    if (!isset($perDirectory[$directory])) {
        $perDirectory[$directory] = array(0, 0);
    }
    $perDirectory[$directory][0] += count($covered);
    $perDirectory[$directory][1] += count($executableLines);
    $totalRun += count($covered);
    $totalLines += count($executableLines);
    $fileCount++;
    if ($covered) {
        $touched++;
    }
}
ksort($perDirectory);

$overall = $totalLines ? 100 * $totalRun / $totalLines : 0;
printf("coverage: %d of %d executable lines (%.1f%%), %d of %d files touched\n", $totalRun, $totalLines, $overall, $touched, $fileCount);
foreach ($perDirectory as $directory => $counts) {
    printf("  %-12s %5d/%5d  %6.1f%%\n", $directory, $counts[0], $counts[1], $counts[1] ? 100 * $counts[0] / $counts[1] : 0);
}

if ($reportOnly) {
    exit(0);
}

$failed = array();
if ($overall < $minOverall) {
    $failed[] = sprintf('overall %.1f%% is below the %.1f%% floor', $overall, $minOverall);
}
foreach ($minPerDirectory as $directory => $floor) {
    if (!isset($perDirectory[$directory])) {
        continue;
    }
    $counts = $perDirectory[$directory];
    $percent = $counts[1] ? 100 * $counts[0] / $counts[1] : 100;
    if ($percent < $floor) {
        $failed[] = sprintf('%s %.1f%% is below the %.1f%% floor', $directory, $percent, $floor);
    }
}

if ($failed) {
    echo "COVERAGE FAILED\n";
    foreach ($failed as $failure) {
        printf("  %s\n", $failure);
    }
    exit(1);
}
printf("coverage floors met: overall >= %.1f%%, %s\n", $minOverall, implode(', ', array_map(function ($directory, $floor) {
    return $directory . ' >= ' . $floor . '%';
}, array_keys($minPerDirectory), $minPerDirectory)));
exit(0);
