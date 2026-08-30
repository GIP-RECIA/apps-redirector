<?php
/**
 * Integration tests for index.php orchestration using CI-only configuration and
 * a fake phpCAS implementation. Each scenario runs in a child process because
 * the controller deliberately exits after producing its response.
 */

declare(strict_types=1);
error_reporting(E_ALL);

$tests = 0;
$fails = 0;
$accessProblem = '<div style="text-align:center;margin-left: auto;margin-right: auto;">Vous n\'avez pas acc&egrave;s &agrave; ce service !</div>';

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

function run_index($application, array $attributes, string $domain): array
{
    $parts = array(
        'CI_CAS_ATTRIBUTES=' . escapeshellarg(json_encode($attributes)),
        'CI_SERVER_NAME=' . escapeshellarg($domain),
        'REDIRECTOR_CONFIG=ci/conf/conf.inc.php',
    );
    if ($application !== null) {
        $parts[] = 'CI_APPLICATION=' . escapeshellarg($application);
    }
    $parts[] = escapeshellarg(PHP_BINARY);
    $parts[] = 'ci/run-index.php';
    $output = array();
    $status = 0;
    exec(implode(' ', $parts) . ' 2>&1', $output, $status);
    return array($status, implode("\n", $output));
}

function assert_redirect(string $label, $application, array $attributes, string $domain, string $url): void
{
    list($status, $output) = run_index($application, $attributes, $domain);
    print "Attributs CAS testés: " . json_encode($attributes, JSON_UNESCAPED_SLASHES) . "\n";
    print "Domaine testé: $domain\n";
    assertEquals(0, $status, "$label: code de sortie");
    assertEquals('header("Location: "' . $url . '", true, 302);', $output, "$label: URL résolue");
}

assert_redirect(
    'Attribut: LINK exact prioritaire',
    'TEST_ROUTING',
    array('TestIdentifier' => '1234567A'),
    'redirector.test',
    'https://redirector.test/target-c'
);

assert_redirect(
    'Attribut: REGEX_LINK',
    'TEST_ROUTING',
    array('TestIdentifier' => '9870000A'),
    'redirector.test',
    'https://redirector.test/target-b'
);

assert_redirect(
    'Attribut: fallback après attribut principal absent',
    'TEST_ROUTING',
    array('TestFallbackIdentifier' => '19999999999999'),
    'redirector.test',
    'https://redirector.test/target-a'
);

assert_redirect(
    'Attribut: DEFAULT_LINK sans fallback',
    'TEST_DEFAULT',
    array('TestDefaultIdentifier' => '5550000A'),
    'redirector.test',
    'https://redirector.test/target-default'
);

assert_redirect(
    'Domaine: override LINK exact',
    'TEST_DOMAIN',
    array('TestDomainIdentifier' => '7771234A'),
    'redirector.test',
    'https://redirector.test/target-domain-exact'
);

assert_redirect(
    'Domaine: override REGEX_LINK',
    'TEST_DOMAIN',
    array('TestDomainIdentifier' => '7770000A'),
    'redirector.test',
    'https://redirector.test/target-domain-regex'
);

assert_redirect(
    'Domaine: DOMAIN_MAP',
    'TEST_DOMAIN',
    array(),
    'redirector.test',
    'https://service.test/domain-map'
);

assert_redirect(
    'Domaine: DEFAULT_LINK',
    'TEST_DOMAIN',
    array(),
    'unknown.test',
    'https://service.test/domain-default'
);

list($status, $output) = run_index('TEST_FILTER_DENIED', array('TestDeniedIdentifier' => '8880000A', 'TestAccess' => 'denied'), 'redirector.test');
assertEquals(0, $status, 'Filtre refusé: code de sortie');
assertEquals($accessProblem, $output, 'Filtre refusé: message d’accès');

list($status, $output) = run_index('TEST_REPLACE_FAILURE', array('TestReplaceIdentifier' => '9990000A'), 'redirector.test');
assertEquals(0, $status, 'REPLACE invalide: code de sortie');
assertEquals($accessProblem, $output, 'REPLACE invalide: message d’accès');

list($status, $output) = run_index('UNKNOWN', array(), 'redirector.test');
assertEquals(0, $status, 'Application inconnue: code de sortie');
assertEquals($accessProblem, $output, 'Application inconnue: message d’accès');

list($status, $output) = run_index(null, array(), 'redirector.test');
assertEquals(0, $status, 'Application absente: code de sortie');
assertEquals($accessProblem, $output, 'Application absente: message d’accès');

if ($fails === 0) {
    print "Tests d’intégration index: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
