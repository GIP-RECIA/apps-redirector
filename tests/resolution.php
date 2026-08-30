<?php
/**
 * Tests autonomes des fonctions de résolution avec une configuration interne
 * entièrement synthétique.
 *
 * Usage: php tests/resolution.php
 */

declare(strict_types=1);
error_reporting(E_ALL);

$_SERVER['SERVER_NAME'] = 'redirector.test';

function log_action($lvl = "ERROR", $msg = ""): void
{
}

function log_lvl_to_int($lvl)
{
    switch ($lvl) {
        case "TRACE": return 0;
        case "DEBUG": return 1;
        case "INFO": return 2;
        case "WARN": return 3;
        case "ERROR": return 4;
        default: return 5;
    }
}

include dirname(__DIR__) . '/resolution.php';
include dirname(__DIR__) . '/ci/conf/conf.inc.php';

$baseUrl = 'https://' . $_SERVER['SERVER_NAME'];
$tests = 0;
$fails = 0;

function check(bool $cond, string $label): void
{
    global $tests, $fails;
    $tests++;
    if (!$cond) {
        $fails++;
        fwrite(STDERR, "FAIL: $label\n");
    }
}

function assertEquals($expected, $actual, string $label): void
{
    global $tests, $fails;
    $tests++;
    if ($expected !== $actual) {
        $fails++;
        fwrite(STDERR, "FAIL: $label\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n");
    }
}

function assertThrows(string $label, callable $cb): void
{
    global $tests, $fails;
    $tests++;
    try {
        $cb();
        $fails++;
        fwrite(STDERR, "FAIL: $label (aucune exception levée)\n");
    } catch (Throwable $e) {
    }
}

function set_attrs(array $attrs): void
{
    global $CAS_attrs;
    $CAS_attrs = $attrs;
}

if (!isset($mapping['TEST_ROUTING'])) {
    fwrite(STDERR, "L'application de test n'est pas configurée\n");
    exit(1);
}

set_attrs(array('TestIdentifier' => '1230000A'));
assertEquals($baseUrl . '/target-a', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Première règle regex');

set_attrs(array('TestIdentifier' => '9870000A'));
assertEquals($baseUrl . '/target-b', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Seconde règle regex');

set_attrs(array('TestIdentifier' => '4567890A'));
assertEquals($baseUrl . '/target-c', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Override exact');

set_attrs(array('TestIdentifier' => '1234567Z'));
assertEquals('null', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Identifiant exclu');

set_attrs(array('TestIdentifier' => '5550000A'));
assertThrows('Identifiant non couvert', function () {
    find_cas_attr('TestIdentifier', 'TEST_ROUTING');
});

set_attrs(array('TestFallbackIdentifier' => '19999999999999'));
assertEquals($baseUrl . '/target-a', find_cas_attr('TestFallbackIdentifier', 'TEST_ROUTING'), 'Fallback synthétique');

$routingFilter = $mapping['TEST_ROUTING']['FILTER'];

set_attrs(array('TestIdentifier' => '1230000A'));
check(evaluate_filter_rule($routingFilter) === true, 'Filtre: identifiant autorisé');

set_attrs(array('TestIdentifier' => '1234567Z'));
check(evaluate_filter_rule($routingFilter) === false, 'Filtre: identifiant exclu');

set_attrs(array('TestFallbackIdentifier' => '19999999999999'));
check(evaluate_filter_rule($routingFilter) === true, 'Filtre: fallback synthétique');

set_attrs(array('TestIdentifier' => '5550000A'));
check(evaluate_filter_rule($routingFilter) === true, 'Filtre: identifiant non exclu');

set_attrs(array('OTHER_ATTRIBUTE' => 'value'));
check(evaluate_filter_rule($routingFilter) === false, 'Filtre: attribut absent');

assertEquals('https://service.test/default', find_domain_link($mapping['TEST_DOMAIN'], $_SERVER['SERVER_NAME']), 'DOMAIN_MAP: domaine courant');

if ($fails === 0) {
    print "Tests de résolution: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
