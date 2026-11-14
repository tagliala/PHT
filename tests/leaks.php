<?php
/**
 * Repeated calls must not accumulate handles or memory: the library runs inside
 * long-lived workers, where a leak per request becomes an outage.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

define('PHT_TEST_LEAK_CALLS', 200);
define('PHT_TEST_LEAK_TOLERANCE', 65536);

/**
 * Runs $callable 1000 times after a warm-up and reports growth.
 */
function phtTestLeak($name, $callable)
{
    for ($i = 0; $i < 50; $i++) {
        call_user_func($callable);
    }
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }

    $resources = function_exists('get_resources') ? count(get_resources()) : null;
    $memory = memory_get_usage();

    for ($i = 0; $i < PHT_TEST_LEAK_CALLS; $i++) {
        call_user_func($callable);
    }
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }

    $memoryGrowth = memory_get_usage() - $memory;

    // Handles are only visible as resources on PHP 7.x.
    if ($resources !== null && PHP_VERSION_ID < 80000) {
        $after = count(get_resources());
        if ($after !== $resources) {
            throw new PhtTestFailure($after . ' resources left after ' . PHT_TEST_LEAK_CALLS . ' calls (was ' . $resources . ')');
        }
    }
    if ($memoryGrowth > PHT_TEST_LEAK_TOLERANCE) {
        throw new PhtTestFailure('memory grew by ' . $memoryGrowth . ' bytes over ' . PHT_TEST_LEAK_CALLS . ' calls');
    }
}

$valid = phtTestFixture('ok.xml');

phtTest('leak: checkXmlData with valid xml', function () use ($valid) {
    phtTestLeak('valid', function () use ($valid) {
        \PHT\Network\Request::checkXmlData($valid, 200);
    });
});

phtTest('leak: checkXmlData with invalid xml', function () {
    phtTestLeak('invalid', function () {
        try {
            \PHT\Network\Request::checkXmlData('not xml at all', 200);
        } catch (\PHT\Exception\NetworkException $e) {
        }
    });
});

phtTest('leak: fetchUrl', function () {
    phtTestLeak('fetch', function () {
        phtTestFetchFixture('ok.xml', false);
    });
});

phtTest('leak: pingChppServer', function () {
    phtTestLeak('ping', function () {
        \PHT\Network\Request::pingChppServer(50);
    });
});
