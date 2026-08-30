<?php
/**
 * HTTP integration test for the access-denied page served by index.php.
 * It verifies the 403 status and the portal chrome tags, but not external
 * JavaScript loading.
 */

declare(strict_types=1);
error_reporting(E_ALL);

$tests = 0;
$fails = 0;

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

function wait_for_server(int $port, int $timeoutMs = 5000): void
{
    $deadline = microtime(true) + ($timeoutMs / 1000);
    while (microtime(true) < $deadline) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
        if ($socket) {
            fclose($socket);
            return;
        }
        usleep(100000);
    }
    throw new RuntimeException('Le serveur PHP intégré ne répond pas sur le port ' . $port . '.');
}

function run_http_request(int $port, string $path): array
{
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
        ),
    ));
    $body = file_get_contents('http://127.0.0.1:' . $port . $path, false, $context);
    $headers = isset($http_response_header) ? $http_response_header : array();
    $statusLine = isset($headers[0]) ? $headers[0] : '';
    if (!preg_match('/^HTTP\/\d\.\d\s+(\d{3})\b/', $statusLine, $matches)) {
        throw new RuntimeException('Réponse HTTP invalide: ' . $statusLine);
    }
    return array((int) $matches[1], $body === false ? '' : $body);
}

$port = 18765;
$env = array_merge($_ENV, array(
    'REDIRECTOR_CONFIG' => 'ci/conf/conf.inc.php',
    'CI_CAS_ATTRIBUTES' => '{}',
));
$process = proc_open(
    'php -S 127.0.0.1:' . $port . ' -t .',
    array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
    $pipes,
    dirname(__DIR__),
    $env
);

if (!is_resource($process)) {
    throw new RuntimeException('Impossible de démarrer le serveur PHP intégré.');
}

try {
    wait_for_server($port);
    list($status, $body) = run_http_request($port, '/index.php');

    assertEquals(403, $status, 'Code HTTP 403');
    assertEquals(true, strpos($body, '<extended-uportal-header') !== false, 'Header uPortal présent');
    assertEquals(true, strpos($body, '<extended-uportal-footer') !== false, 'Footer uPortal présent');
    assertEquals(true, strpos($body, 'Accès refusé') !== false, 'Titre de page présent');
    assertEquals(true, strpos($body, 'Vous n\'avez pas acc&egrave;s &agrave; ce service !') !== false, 'Message utilisateur présent');
} finally {
    foreach ($pipes as $pipe) {
        fclose($pipe);
    }
    proc_terminate($process);
    proc_close($process);
}

if ($fails === 0) {
    print "Tests HTTP index: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
