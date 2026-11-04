<?php
/**
 * Runs every test file in its own process, so a fatal error in one file cannot
 * hide the results of the others.
 *
 * usage: php tests/run.php [name ...]
 */

$root = dirname(__DIR__);
$files = array('declarations', 'parsing', 'payloads', 'network', 'leaks', 'cache', 'pending');

$requested = array_slice($argv, 1);
if ($requested) {
    $files = array_values(array_intersect($files, $requested));
}

$php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
$command = $php
    . ' -d error_reporting=-1 -d display_errors=1 -d log_errors=0'
    . ' -d auto_prepend_file=' . escapeshellarg($root . '/tests/bootstrap.php');

$failed = 0;
foreach ($files as $name) {
    $output = array();
    $code = 0;
    exec($command . ' ' . escapeshellarg($root . '/tests/' . $name . '.php') . ' 2>&1', $output, $code);
    echo implode("\n", $output), "\n";
    if ($code !== 0) {
        $failed++;
    }
}

echo "\n";
echo $failed === 0 ? "all test files passed\n" : $failed . " of " . count($files) . " test files failed\n";
exit($failed === 0 ? 0 : 1);
