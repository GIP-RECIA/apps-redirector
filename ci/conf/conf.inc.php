<?php
/**
 * Configuration synthétique installée par la CI dans conf/conf.inc.php et
 * utilisée par les tests unitaires de résolution.
 *
 * Il ne décrit qu'une application de test avec des identifiants et des routes
 * synthétiques et ne contient aucune donnée privée. Le but est de
 * rendre les tests exécutables sans le dépôt privé de configuration.
 */

$_SERVER['SERVER_NAME'] = 'redirector.test';

// Identifiant exclu du filtre (valeur synthétique).
$allowedIdentifierRegex = '/^(?!(?:1234567Z)$)[0-9]{7}[A-Z]$/i';

$mapping['TEST_ROUTING']['USER_ATTRIBUTE'] = 'TestIdentifier';
$mapping['TEST_ROUTING']['USER_ATTRIBUTE_FALLBACK'] = 'TestFallbackIdentifier';
$mapping['TEST_ROUTING']['LINK'] = array();
$mapping['TEST_ROUTING']['REGEX_LINK'] = array();
$mapping['TEST_ROUTING']['REGEX_LINK']['/^123[0-9]{4}[A-Z]$/i'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-a';
$mapping['TEST_ROUTING']['REGEX_LINK']['/^987[0-9]{4}[A-Z]$/i'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-b';
$mapping['TEST_ROUTING']['FILTER'] = array(
    'OPERATOR' => 'OR',
    'RULES' => array(
        array(
            'USER_ATTRIBUTE' => 'TestIdentifier',
            'REGEX'          => $allowedIdentifierRegex,
        ),
        array(
            'USER_ATTRIBUTE' => 'TestFallbackIdentifier',
            'REGEX'          => '/^19999999999999$/',
        ),
    ),
);

// Override exact -> target-c.
$mapping['TEST_ROUTING']['LINK']['4567890A'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-c';
// Identifiant exclu -> accès bloqué.
$mapping['TEST_ROUTING']['LINK']['1234567Z'] = 'null';
// Fallback synthétique -> target-a.
$mapping['TEST_ROUTING']['LINK']['19999999999999'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-a';

$mapping['TEST_DOMAIN']['DOMAIN_MAP'] = array(
    'redirector.test' => 'https://service.test/default',
);
