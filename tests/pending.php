<?php
/**
 * Known defects, kept visible without failing the build.
 *
 * A case marked pending reports the diagnostics it produced. When it starts
 * passing, the runner says so, and the case moves to the file it belongs to.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

$connection = new \PHT\Connection(array(
    'CONSUMER_KEY' => 'test-consumer-key',
    'CONSUMER_SECRET' => 'test-consumer-secret',
));

// With curl pointed at a closed port, Auth::checkToken() parses an empty body:
// DOMDocument::loadXML('') raises a ValueError on PHP 8, and on PHP 5 and 7 it
// warns and hands back a PHT\Xml\Token built from nothing. No issue filed yet.
phtTestPending(
    'checkChppAccess on a failed request',
    function () use ($connection) {
        $token = $connection->checkChppAccess('token', 'secret');
        $token instanceof \PHT\Xml\Token || phtTestTrue(false, 'returned ' . var_export($token, true));
    },
    'parses an empty body instead of reporting the failed request'
);
