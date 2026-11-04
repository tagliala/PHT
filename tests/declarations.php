<?php
/**
 * Every file in the library must declare its class without a diagnostic.
 * Catches deprecations that PHP emits at declaration time, before any call:
 * Iterator return types, implicitly nullable parameters, reserved names.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

$files = array();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PHT_TEST_ROOT . '/PHT'));
foreach ($iterator as $file) {
    if ($file->isFile() && substr($file->getPathname(), -4) === '.php') {
        $files[] = $file->getPathname();
    }
}
sort($files);

$declared = 0;
foreach ($files as $path) {
    $relative = str_replace(PHT_TEST_ROOT . '/', '', $path);
    if ($relative === 'PHT/autoload.php') {
        continue;
    }
    $source = file_get_contents($path);
    preg_match('/^namespace\s+([^;]+);/m', $source, $namespace);
    preg_match('/^(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+(\w+)/m', $source, $class);
    $name = isset($namespace[1]) && isset($class[1]) ? $namespace[1] . '\\' . $class[1] : $relative;

    phtTest('load ' . $relative, function () use ($name, $relative) {
        $loaded = class_exists($name) || interface_exists($name)
            || (function_exists('trait_exists') && trait_exists($name));
        if (!$loaded) {
            throw new PhtTestFailure($relative . ' does not declare ' . $name);
        }
    });
    $declared++;
}

phtTest('file count', function () use ($declared) {
    phtTestTrue($declared > 400, 'only ' . $declared . ' files were found');
});
