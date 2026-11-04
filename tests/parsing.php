<?php
/**
 * Parsing paths: the XML validation, the lazy iterator, and the small helpers
 * that most of the library funnels through.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

$valid = phtTestFixture('ok.xml');

phtTest('checkXmlData accepts a valid document', function () use ($valid) {
    \PHT\Network\Request::checkXmlData($valid, 200);
    phtTestTrue(true);
});

phtTest('checkXmlData rejects non-xml', function () {
    phtTestThrows('PHT\Exception\NetworkException', function () {
        \PHT\Network\Request::checkXmlData('not xml at all', 200);
    });
});

phtTest('checkXmlData rejects an empty payload', function () {
    phtTestThrows('PHT\Exception\NetworkException', function () {
        \PHT\Network\Request::checkXmlData('', 0);
    });
});

phtTest('checkXmlData reports a CHPP error file', function () {
    $body = '<?xml version="1.0"?><HattrickData><FileName>chpperror.xml</FileName></HattrickData>';
    phtTestThrows('PHT\Exception\ChppException', function () use ($body) {
        \PHT\Network\Request::checkXmlData($body, 200);
    });
});

phtTest('checkXmlData reports a payload without FileName', function () {
    $body = '<?xml version="1.0"?><HattrickData><Team /></HattrickData>';
    phtTestThrows('PHT\Exception\ChppException', function () use ($body) {
        \PHT\Network\Request::checkXmlData($body, 200);
    });
});

phtTest('XmlIterator walks a node list lazily', function () {
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadXML('<HattrickData><Team><TeamID>1</TeamID></Team><Team><TeamID>2</TeamID></Team></HattrickData>');
    $keys = array();
    $types = array();
    foreach (new \PHT\Utils\XmlIterator($dom->getElementsByTagName('Team'), 'PHT\Xml\World\Country') as $key => $country) {
        $keys[] = $key;
        $types[] = get_class($country);
    }
    phtTestSame(array(0, 1), $keys);
    phtTestSame(array('PHT\Xml\World\Country', 'PHT\Xml\World\Country'), $types);
});

phtTest('urlencodeRfc3986 handles arrays, integers and nulls', function () {
    phtTestSame(
        array('a%20b', 'c~d', '0', ''),
        \PHT\Network\Request::urlencodeRfc3986(array('a b', 'c~d', 0, null))
    );
});

phtTest('Date converts a CHPP date', function () {
    phtTestSame('2026-11-04', \PHT\Utils\Date::convert('2026-11-04 10:00:00', 'Y-m-d'));
});

phtTest('Date returns the input untouched without a format', function () {
    phtTestSame('2026-11-04 10:00:00', \PHT\Utils\Date::convert('2026-11-04 10:00:00', null));
});
