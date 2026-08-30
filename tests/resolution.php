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
        return;
    }
    print "PASS: $label\n";
}

function assertEquals($expected, $actual, string $label): void
{
    global $tests, $fails;
    $tests++;
    if ($expected !== $actual) {
        $fails++;
        fwrite(STDERR, "FAIL: $label\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n");
        return;
    }
    print "PASS: $label\n  resultat: " . var_export($actual, true) . "\n";
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
        print "PASS: $label => exception attendue\n";
    }
}

function set_attrs(array $attrs): void
{
    global $CAS_attrs;
    $CAS_attrs = $attrs;
    print "Attributs CAS testés: " . json_encode($attrs, JSON_UNESCAPED_SLASHES) . "\n";
}

// LINK exact, REGEX_LINK, blocage explicite et fallback sur attribut absent.
set_attrs(array('TestIdentifier' => '1230000A'));
assertEquals($baseUrl . '/target-a', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Première règle regex');

set_attrs(array('TestIdentifier' => '9870000A'));
assertEquals($baseUrl . '/target-b', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Seconde règle regex');

set_attrs(array('TestIdentifier' => '1234567A'));
assertEquals($baseUrl . '/target-c', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'LINK exact prioritaire sur REGEX_LINK');

set_attrs(array('TestIdentifier' => '1234567Z'));
assertEquals('null', find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Identifiant exclu');

set_attrs(array());
assertEquals(null, find_cas_attr('TestIdentifier', 'TEST_ROUTING'), 'Attribut principal absent');

set_attrs(array('TestFallbackIdentifier' => '19999999999999'));
assertEquals($baseUrl . '/target-a', find_cas_attr('TestFallbackIdentifier', 'TEST_ROUTING'), 'Fallback après attribut principal absent');

// DEFAULT_LINK est utilisé pour un attribut présent non mappé, sans fallback.
set_attrs(array('TestDefaultIdentifier' => '3210000A'));
assertEquals($baseUrl . '/target-default-regex', find_cas_attr('TestDefaultIdentifier', 'TEST_DEFAULT'), 'REGEX_LINK avant DEFAULT_LINK');

set_attrs(array('TestDefaultIdentifier' => '5550000A'));
assertEquals($baseUrl . '/target-default', find_cas_attr('TestDefaultIdentifier', 'TEST_DEFAULT'), 'DEFAULT_LINK après attribut non mappé');

set_attrs(array('TestNoDefaultIdentifier' => '5550000A'));
assertThrows('Attribut non mappé sans DEFAULT_LINK', function () {
    find_cas_attr('TestNoDefaultIdentifier', 'TEST_NO_DEFAULT');
});

// Les LINK exacts issus d'un tableau CAS restent prioritaires selon l'ordre de configuration.
set_attrs(array('TestArrayIdentifier' => array('2220000B', '1110000A')));
assertEquals($baseUrl . '/target-array-first', find_cas_attr('TestArrayIdentifier', 'TEST_ARRAY'), 'Tableau CAS: priorité de la première clé LINK');

// Résolution de domaine et ses composants testables indépendamment du contrôleur HTTP.
set_attrs(array('TestDomainIdentifier' => '7771234A'));
assertEquals($baseUrl . '/target-domain-exact', find_link_override($mapping['TEST_DOMAIN']), 'Domaine: LINK exact');

set_attrs(array('TestDomainIdentifier' => '7770000A'));
assertEquals($baseUrl . '/target-domain-regex', find_regex_link($mapping['TEST_DOMAIN']), 'Domaine: REGEX_LINK');

print "Domaine testé: redirector.test\n";
assertEquals('https://service.test/domain-map', find_domain_link($mapping['TEST_DOMAIN'], 'redirector.test'), 'DOMAIN_MAP: domaine connu');

print "Domaine testé: unknown.test\n";
assertEquals(null, find_domain_link($mapping['TEST_DOMAIN'], 'unknown.test'), 'DOMAIN_MAP: domaine inconnu');
assertEquals('https://service.test/domain-default', find_default_link($mapping['TEST_DOMAIN']), 'Domaine inconnu: DEFAULT_LINK');

// FILTER simple, composé, imbriqué et erreurs de configuration.
$routingFilter = $mapping['TEST_ROUTING']['FILTER'];

set_attrs(array('TestIdentifier' => '1230000A'));
check(evaluate_filter_rule($routingFilter) === true, 'Filtre OR: identifiant autorisé');

set_attrs(array('TestIdentifier' => '1234567Z'));
check(evaluate_filter_rule($routingFilter) === false, 'Filtre OR: identifiant exclu');

set_attrs(array('TestFallbackIdentifier' => '19999999999999'));
check(evaluate_filter_rule($routingFilter) === true, 'Filtre OR: fallback synthétique');

set_attrs(array('TestRole' => 'staff', 'TestRegion' => 'north'));
check(evaluate_filter_rule($testAndFilter) === true, 'Filtre AND imbriqué: première branche OR');

set_attrs(array('TestRole' => 'staff', 'TestTier' => 'gold'));
check(evaluate_filter_rule($testAndFilter) === true, 'Filtre AND imbriqué: seconde branche OR');

set_attrs(array('TestRole' => 'student', 'TestRegion' => 'north'));
check(evaluate_filter_rule($testAndFilter) === false, 'Filtre AND imbriqué: condition refusée');

assertThrows('Filtre: opérateur invalide', function () {
    evaluate_filter_rule(array('OPERATOR' => 'XOR', 'RULES' => array(array('USER_ATTRIBUTE' => 'TestRole', 'REGEX' => '/^staff$/'))));
});

assertThrows('Filtre: règles vides', function () {
    evaluate_filter_rule(array('OPERATOR' => 'AND', 'RULES' => array()));
});

set_attrs(array('TestRole' => 'staff'));
assertThrows('Filtre: regex invalide', function () {
    evaluate_filter_rule(array('USER_ATTRIBUTE' => 'TestRole', 'REGEX' => '/[/'));
});

set_attrs(array('TestIdentifier' => '1230000A'));
assertThrows('REGEX_LINK: regex invalide', function () {
    find_regex_link(array('REGEX_LINK' => array('/[/' => 'https://service.test/invalid')), 'TestIdentifier');
});

// REPLACE applique la casse configurée et préserve une URL sans règle de remplacement.
set_attrs(array('TestReplacement' => 'MiXeD'));
assertEquals('https://service.test/mixed', do_replacement($mapping['TEST_REPLACE'], 'https://service.test/%TestReplacement%'), 'REPLACE: minuscules par défaut');

$mapping['TEST_REPLACE']['REPLACE']['VALUE_TO_LOWERCASE'] = false;
assertEquals('https://service.test/MIXED', do_replacement($mapping['TEST_REPLACE'], 'https://service.test/%TestReplacement%'), 'REPLACE: majuscules configurées');
assertEquals('https://service.test/unchanged', do_replacement(array(), 'https://service.test/unchanged'), 'REPLACE: règle absente');

if ($fails === 0) {
    print "Tests de résolution: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
