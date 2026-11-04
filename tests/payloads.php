<?php
/**
 * Response objects built from payloads. This is where most of the library
 * lives: about eight thousand lines of getters that no network test reaches.
 */

require_once dirname(__FILE__) . '/bootstrap.php';

phtTest('Xml\User reads the manager', function () {
    $user = new \PHT\Xml\User(phtTestFixture('user.xml'));
    phtTestSame('5', $user->getId());
    phtTestSame('example_manager', $user->getName());
    phtTestSame('1', $user->getLanguageId());
    phtTestSame('English', $user->getLanguageName());
    phtTestSame('5', $user->getCountryId());
    phtTestSame('Italia', $user->getCountryName());
    phtTestSame('Euro', $user->getCurrencyName());
    phtTestSame(1.0, (float)$user->getCurrencyRate());
    phtTestSame('2026-11-04', $user->getFetchedDate('Y-m-d'));
    phtTestSame('user.xml', $user->getXmlFileName());
    phtTestSame('5', $user->getConnectedUserId());
});

phtTest('Xml\User counts the teams', function () {
    $user = new \PHT\Xml\User(phtTestFixture('user.xml'));
    phtTestSame(2, $user->getTeamNumber());
    $names = array();
    $ids = array();
    foreach ($user->getTeams() as $team) {
        $names[] = $team->getName();
        $ids[] = (int)$team->getId();
    }
    phtTestSame(array('Example United', 'Example Reserves'), $names);
    phtTestSame(array(1001, 1002), $ids);
});

phtTest('Xml\Player\Senior reads the player', function () {
    $player = new \PHT\Xml\Player\Senior(phtTestFixture('player.xml'));
    phtTestSame('4242', $player->getId());
    phtTestSame('Example', $player->getFirstName());
    phtTestSame('Player', $player->getLastName());
    phtTestSame('Example', $player->getNickName());
    phtTestSame('24', $player->getAge());
    phtTestSame('1', $player->getGenderId());
    phtTestSame('7', $player->getShirtNumber());
    phtTestSame('12', $player->getDays());
    phtTestSame('1500', $player->getTsi());
    phtTestSame('7', $player->getForm());
    phtTestSame('0', $player->getCards());
    phtTestSame('0', $player->getInjury());
    phtTestSame('5', $player->getExperience());
    phtTestSame('4', $player->getLeadership());
    phtTestSame('12', $player->getLoyalty());
    phtTestSame('6', $player->getStamina());
    phtTestSame('1', $player->getKeeper());
    phtTestSame('5', $player->getDefender());
    phtTestSame('6', $player->getPlaymaker());
    phtTestSame('4', $player->getWinger());
    phtTestSame('6', $player->getPassing());
    phtTestSame('4', $player->getScorer());
    phtTestSame('3', $player->getSetPieces());
});

phtTest('Xml\Team\Senior reads the club', function () {
    $team = new \PHT\Xml\Team\Senior(phtTestFixture('team.xml'));
    phtTestSame('1001', $team->getId());
    phtTestSame('Example United', $team->getName());
    phtTestSame('5', $team->getUserId());
    phtTestSame('5', $team->getCountryId());
    phtTestSame('Italia', $team->getCountryName());
    phtTestSame('2002', $team->getArenaId());
    phtTestSame('Example Park', $team->getArenaName());
    phtTestSame('5', $team->getLeagueId());
    phtTestSame('example_manager', $team->getLoginName());
    phtTestSame(null, $team->getFriendlyOppositeTeamId());
    phtTestSame('3', $team->getNumberOfVictories());
    phtTestSame('5', $team->getNumberOfUndefeat());
});

phtTest('Xml\Team\Senior drops teams that do not match the id', function () {
    $team = new \PHT\Xml\Team\Senior(phtTestFixture('team.xml'), 1001);
    phtTestSame('1001', $team->getId());
    phtTestSame('Example United', $team->getName());
});

phtTest('Xml\World\Country reads the league', function () {
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadXML('<Country><LeagueID>5</LeagueID><LeagueName>Italia</LeagueName><Season>79</Season><SeasonOffset>0</SeasonOffset><ShortName>IT</ShortName><Continent>Europe</Continent><ZoneName>Italia</ZoneName><EnglishName>Italy</EnglishName><LanguageId>1</LanguageId><LanguageName>English</LanguageName></Country>');
    $country = new \PHT\Xml\World\Country($dom);
    phtTestSame('5', $country->getLeagueId());
    phtTestSame('Italia', $country->getLeagueName());
    phtTestSame('79', $country->getSeasonNumber());
    phtTestSame('0', $country->getSeasonOffset());
    phtTestSame('IT', $country->getShortName());
    phtTestSame('Europe', $country->getContinent());
    phtTestSame('Italy', $country->getEnglishName());
});
