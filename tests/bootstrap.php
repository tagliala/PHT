<?php
/**
 * Test bootstrap: turns every diagnostic into a failure and keeps the tests
 * away from the network.
 *
 * PHP 5.3 compatible on purpose: the library supports it and CI runs it.
 */

if (!defined('PHT_TEST_ROOT')) {
    define('PHT_TEST_ROOT', dirname(__DIR__));
    define('PHT_TEST_STATE', 'pht_test_state');
}

error_reporting(-1);
date_default_timezone_set('UTC');
ini_set('display_errors', '1');
ini_set('log_errors', '0');

require_once PHT_TEST_ROOT . '/PHT/autoload.php';

$GLOBALS[PHT_TEST_STATE] = array(
    'case' => null,
    'cases' => 0,
    'failures' => array(),
    'pending' => array(),
);

class PhtTestFailure extends Exception
{
}

/**
 * Point every outgoing request at a closed local port: curl fails immediately,
 * nothing leaves the machine, and no DNS lookup happens because a proxy is in use.
 */
\PHT\Config\Config::$proxyIp = '127.0.0.1';
\PHT\Config\Config::$proxyPort = 1;
\PHT\Config\Config::$logType = 'none';
putenv('no_proxy=');
putenv('NO_PROXY=');

$phtTestNames = array(
    1 => 'E_ERROR', 2 => 'E_WARNING', 4 => 'E_PARSE', 8 => 'E_NOTICE', 16 => 'E_CORE_ERROR',
    32 => 'E_CORE_WARNING', 64 => 'E_COMPILE_ERROR', 128 => 'E_COMPILE_WARNING',
    256 => 'E_USER_ERROR', 512 => 'E_USER_WARNING', 1024 => 'E_USER_NOTICE',
    2048 => 'E_STRICT', 4096 => 'E_RECOVERABLE_ERROR', 8192 => 'E_DEPRECATED',
    16384 => 'E_USER_DEPRECATED',
);

function phtTestRecord($kind, $message)
{
    $state = &$GLOBALS[PHT_TEST_STATE];
    $state['failures'][] = array(
        'case' => $state['case'] === null ? '(outside a case)' : $state['case'],
        'kind' => $kind,
        'message' => $message,
    );
}

set_error_handler(function ($number, $message, $file, $line) use ($phtTestNames) {
    $kind = isset($phtTestNames[$number]) ? $phtTestNames[$number] : 'E_' . $number;
    phtTestRecord($kind, $message . ' at ' . str_replace(PHT_TEST_ROOT . '/', '', $file) . ':' . $line);
    return true;
}, -1);

function phtTest($name, $callable)
{
    $state = &$GLOBALS[PHT_TEST_STATE];
    $state['case'] = $name;
    $state['cases']++;
    try {
        call_user_func($callable);
    } catch (Exception $e) {
        phtTestRecord('exception', get_class($e) . ': ' . $e->getMessage());
    } catch (Throwable $e) {
        phtTestRecord('exception', get_class($e) . ': ' . $e->getMessage());
    }
    $state['case'] = null;
}

/**
 * A case that is expected to fail because of a known defect. It never fails the
 * build: it reports the diagnostics it produced, and reports loudly when it
 * starts passing, so the case can be promoted to phtTest().
 */
function phtTestPending($name, $callable, $reason)
{
    $state = &$GLOBALS[PHT_TEST_STATE];
    $before = count($state['failures']);
    $state['case'] = $name;
    $state['cases']++;
    try {
        call_user_func($callable);
    } catch (Exception $e) {
        phtTestRecord('exception', get_class($e) . ': ' . $e->getMessage());
    } catch (Throwable $e) {
        phtTestRecord('exception', get_class($e) . ': ' . $e->getMessage());
    }
    $found = count($state['failures']) - $before;
    $state['failures'] = array_slice($state['failures'], 0, $before);
    $state['case'] = null;
    $state['pending'][] = array('name' => $name, 'reason' => $reason, 'found' => $found);
}

function phtTestSame($expected, $actual)
{
    if ($expected !== $actual) {
        throw new PhtTestFailure(
            'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

function phtTestTrue($value, $what = 'value')
{
    if ($value !== true) {
        throw new PhtTestFailure($what . ' is not true: ' . var_export($value, true));
    }
}

/**
 * Returns the exception when $callable throws the expected class.
 */
function phtTestThrows($class, $callable)
{
    try {
        call_user_func($callable);
    } catch (Exception $e) {
        if ($e instanceof $class) {
            return $e;
        }
        throw new PhtTestFailure('expected ' . $class . ', got ' . get_class($e) . ': ' . $e->getMessage());
    }
    throw new PhtTestFailure('expected ' . $class . ', nothing was thrown');
}

function phtTestFixture($name)
{
    return file_get_contents(PHT_TEST_ROOT . '/tests/fixtures/' . $name);
}

/**
 * Fetches a fixture through PHT with curl, so the transfer path runs for real.
 */
function phtTestFetchFixture($name, $check = false)
{
    return \PHT\Network\Request::fetchUrl('file://' . realpath(PHT_TEST_ROOT . '/tests/fixtures/' . $name), $check);
}

register_shutdown_function(function () {
    $state = $GLOBALS[PHT_TEST_STATE];
    $grouped = array();
    foreach ($state['failures'] as $failure) {
        $line = '  ' . $failure['case'] . ': [' . $failure['kind'] . '] ' . $failure['message'];
        if (!isset($grouped[$line])) {
            $grouped[$line] = 0;
        }
        $grouped[$line]++;
    }
    foreach ($state['pending'] as $pending) {
        if ($pending['found'] === 0) {
            echo 'PENDING ' . $pending['name'] . ": now passes, promote it to phtTest() - " . $pending['reason'] . "\n";
            continue;
        }
        echo 'PENDING ' . $pending['name'] . ': ' . $pending['found'] . ' diagnostic(s) - ' . $pending['reason'] . "\n";
    }
    if (!$grouped) {
        echo 'ok   ' . basename($_SERVER['SCRIPT_NAME']) . '  ' . $state['cases'] . " cases\n";
        return;
    }
    echo 'FAIL ' . basename($_SERVER['SCRIPT_NAME']) . "\n";
    foreach ($grouped as $line => $count) {
        echo $count > 1 ? $line . ' (x' . $count . ')' : $line;
        echo "\n";
    }
    exit(1);
});
