<?php
/**
 * The memory cache driver stores compressed, serialised payloads and groups them
 * by the xml file they came from.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

phtTest('memory cache round trip', function () {
    $cache = new \PHT\Cache\Memory();
    phtTestTrue($cache->set('file=user&userId=5', 'payload'));
    phtTestSame('payload', $cache->get('file=user&userId=5'));
});

phtTest('memory cache ignores keys without a file', function () {
    $cache = new \PHT\Cache\Memory();
    phtTestSame(false, $cache->set('userId=5', 'payload'));
});

phtTest('memory cache misses an unknown key', function () {
    $cache = new \PHT\Cache\Memory();
    phtTestSame(false, $cache->get('file=unknown'));
});

phtTest('memory cache deletes a key', function () {
    $cache = new \PHT\Cache\Memory();
    $cache->set('file=user&userId=5', 'payload');
    $cache->delete('file=user&userId=5');
    phtTestSame(false, $cache->get('file=user&userId=5'));
});

phtTest('memory cache clears a file group', function () {
    $cache = new \PHT\Cache\Memory();
    $cache->set('file=user&userId=5', 'payload');
    $cache->set('file=user&userId=6', 'payload');
    $cache->clear('user');
    phtTestSame(false, $cache->get('file=user&userId=5'));
    phtTestSame(false, $cache->get('file=user&userId=6'));
});

phtTest('memory cache expires a key', function () {
    $cache = new \PHT\Cache\Memory();
    $cache->set('file=user&userId=5', 'payload', 1);
    phtTestSame('payload', $cache->get('file=user&userId=5'));
    sleep(2);
    phtTestSame(false, $cache->get('file=user&userId=5'));
});

phtTest('memory cache purges everything', function () {
    $cache = new \PHT\Cache\Memory();
    $cache->set('file=user&userId=5', 'payload');
    $cache->purge();
    phtTestSame(false, $cache->get('file=user&userId=5'));
});
