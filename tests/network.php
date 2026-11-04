<?php
/**
 * Network paths. Nothing leaves the machine: the bootstrap sends curl to a
 * closed local port, so every remote call fails the same way everywhere.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

$connection = new \PHT\Connection(array(
    'CONSUMER_KEY' => 'test-consumer-key',
    'CONSUMER_SECRET' => 'test-consumer-secret',
));

phtTest('fetchUrl returns the body of a file url', function () {
    phtTestSame(phtTestFixture('ok.xml'), phtTestFetchFixture('ok.xml', false));
});

phtTest('fetchUrl checks the body when asked', function () {
    phtTestSame(phtTestFixture('ok.xml'), phtTestFetchFixture('ok.xml', true));
});

phtTest('fetchUrl throws on an unreachable host', function () {
    phtTestThrows('PHT\Exception\NetworkException', function () {
        \PHT\Network\Request::fetchUrl('http://127.0.0.1:1/nothing.xml', true);
    });
});

phtTest('fetchUrl returns false on an unreachable host without check', function () {
    phtTestSame(false, \PHT\Network\Request::fetchUrl('http://127.0.0.1:1/nothing.xml', false));
});

phtTest('pingChppServer reports the server unreachable', function () {
    phtTestSame(false, \PHT\Network\Request::pingChppServer(1000));
});

phtTest('isChppDown reports the server down', function () use ($connection) {
    phtTestSame(true, $connection->isChppDown(1, 1));
});

phtTest('deleteChppAccess survives an unreachable host', function () use ($connection) {
    $connection->deleteChppAccess('token', 'secret');
    phtTestTrue(true);
});

phtTest('getPermanentAuthorization returns false', function () use ($connection) {
    phtTestSame(false, $connection->getPermanentAuthorization('http://localhost/callback'));
});

phtTest('getTemporaryAuthorization returns false', function () use ($connection) {
    phtTestSame(false, $connection->getTemporaryAuthorization('http://localhost/callback'));
});

phtTest('getChppAccess returns false', function () use ($connection) {
    phtTestSame(false, $connection->getChppAccess('first-secret', 'token', 'verifier'));
});
