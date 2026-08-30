<?php
/**
 * HTTP integration tests for index.php served through the PHP built-in server.
 * They verify a real 403 page and a real 302 redirect without relying on
 * external portal JavaScript loading.
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

function free_port(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($socket === false) {
        throw new RuntimeException('Impossible de réserver un port libre: ' . $errstr);
    }
    $name = stream_socket_get_name($socket, false);
    fclose($socket);
    if (!preg_match('/:(\d+)$/', $name, $matches)) {
        throw new RuntimeException('Port libre invalide: ' . $name);
    }
    return (int) $matches[1];
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

function start_server(array $env): array
{
    $port = free_port();
    $serverEnv = array_merge($_ENV, $env);
    $serverEnv['REDIRECTOR_CONFIG'] = 'ci/conf/conf.inc.php';
    $serverEnv['CI_SERVER_NAME'] = 'redirector.test';
    $serverEnv['CI_CAS_USER'] = 'ci-user';

    $process = proc_open(
        'php -S 127.0.0.1:' . $port . ' -t .',
        array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')),
        $pipes,
        dirname(__DIR__),
        $serverEnv
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Impossible de démarrer le serveur PHP intégré.');
    }

    wait_for_server($port);
    return array($process, $pipes, $port);
}

function stop_server($process, array $pipes): void
{
    foreach ($pipes as $pipe) {
        fclose($pipe);
    }
    proc_terminate($process);
    proc_close($process);
}

function http_request(int $port, string $path): array
{
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => "Host: redirector.test\r\nConnection: close\r\n",
        ),
    ));
    $body = file_get_contents('http://127.0.0.1:' . $port . $path, false, $context);
    $headers = isset($http_response_header) ? $http_response_header : array();
    $statusLine = isset($headers[0]) ? $headers[0] : '';
    if (!preg_match('/^HTTP\/\d\.\d\s+(\d{3})\b/', $statusLine, $matches)) {
        throw new RuntimeException('Réponse HTTP invalide: ' . $statusLine);
    }
    $headerMap = array();
    foreach ($headers as $headerLine) {
        if (strpos($headerLine, ':') === false) {
            continue;
        }
        list($name, $value) = explode(':', $headerLine, 2);
        $headerMap[strtolower(trim($name))] = trim($value);
    }
    return array((int) $matches[1], $headerMap, $body === false ? '' : $body);
}

function assert_http_scenario(string $label, array $env, string $path, int $expectedStatus, ?string $expectedLocation = null, ?string $expectedBodyContains = null): void
{
    global $tests;
    $server = start_server($env);
    try {
        list($process, $pipes, $port) = $server;
        list($status, $headers, $body) = http_request($port, $path);

        assertEquals($expectedStatus, $status, $label . ': code HTTP');
        if (!is_null($expectedLocation)) {
            assertEquals($expectedLocation, isset($headers['location']) ? $headers['location'] : null, $label . ': en-tête Location');
        }
        if (!is_null($expectedBodyContains)) {
            assertEquals(true, strpos($body, $expectedBodyContains) !== false, $label . ': contenu présent');
        }
    } finally {
        stop_server($server[0], $server[1]);
    }
}

assert_http_scenario(
    'Page d’accès refusé',
    array('REDIRECTOR_DEV_MOD' => '0', 'CI_CAS_ATTRIBUTES' => '{}'),
    '/index.php',
    403,
    null,
    'Vous n\'avez pas acc&egrave;s &agrave; ce service !'
);

assert_http_scenario(
    'Redirection réelle',
    array('REDIRECTOR_DEV_MOD' => '0', 'CI_CAS_ATTRIBUTES' => json_encode(array('TestIdentifier' => '1234567A'))),
    '/index.php?appli=TEST_ROUTING',
    302,
    'https://redirector.test/target-c',
    null
);

assert_http_scenario(
    'Fallback réel',
    array('REDIRECTOR_DEV_MOD' => '0', 'CI_CAS_ATTRIBUTES' => json_encode(array('TestFallbackIdentifier' => '19999999999999'))),
    '/index.php?appli=TEST_ROUTING',
    302,
    'https://redirector.test/target-a',
    null
);

assert_http_scenario(
    'Aucun attribut principal',
    array('REDIRECTOR_DEV_MOD' => '0', 'CI_CAS_ATTRIBUTES' => '{}'),
    '/index.php?appli=TEST_ROUTING',
    403,
    null,
    'Vous n\'avez pas acc&egrave;s &agrave; ce service !'
);

if ($fails === 0) {
    print "Tests HTTP index: OK ($tests assertions)\n";
    exit(0);
}

fwrite(STDERR, "$fails assertion(s) en échec sur $tests\n");
exit(1);
