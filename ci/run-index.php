<?php
/**
 * Executes the real index.php with a synthetic request and fake phpCAS client.
 */

$application = getenv('CI_APPLICATION');
$_SERVER['SERVER_NAME'] = getenv('CI_SERVER_NAME') ?: 'redirector.test';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['PHP_SELF'] = '/index.php';
$_GET = array();
if ($application !== false && $application !== '') {
    $_GET['appli'] = $application;
}
$_REQUEST = $_GET;

include dirname(__DIR__) . '/index.php';
