<?php
/**
 * Configuration synthétique installée par la CI dans conf/conf.inc.php et
 * utilisée par les tests unitaires de résolution.
 */

if (($ciServerName = getenv('CI_SERVER_NAME')) !== false && $ciServerName !== '') {
    $_SERVER['SERVER_NAME'] = $ciServerName;
} elseif (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'redirector.test';
}

$LOG_LVL = 'ERROR';
$DEV_MOD = true;
if (($ciDevMod = getenv('REDIRECTOR_DEV_MOD')) !== false) {
    $ciDevMod = strtolower(trim($ciDevMod));
    $DEV_MOD = in_array($ciDevMod, array('1', 'true', 'yes', 'on'), true);
}
$PATH_CAS_LIB = 'ci/fake-phpcas.php';
$PATH_CAS_CONFIG = 'ci/conf/cas.inc.php';
$LOG_FILENAME = sys_get_temp_dir() . '/esco-apps-redirector-ci.log';
$PHPCAS_LOG_FILENAME = sys_get_temp_dir() . '/esco-apps-redirector-phpcas-ci.log';
$AUTORIZED_IPS = array();
$AUTORIZED_SUBNET = array('127.0.0.');

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
// The exact link intentionally overlaps the first regex rule.
$mapping['TEST_ROUTING']['LINK']['1234567A'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-c';
$mapping['TEST_ROUTING']['LINK']['1234567Z'] = 'null';
$mapping['TEST_ROUTING']['LINK']['19999999999999'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-a';

$mapping['TEST_DEFAULT']['USER_ATTRIBUTE'] = 'TestDefaultIdentifier';
$mapping['TEST_DEFAULT']['LINK'] = array();
$mapping['TEST_DEFAULT']['REGEX_LINK'] = array();
$mapping['TEST_DEFAULT']['REGEX_LINK']['/^321[0-9]{4}[A-Z]$/i'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-default-regex';
$mapping['TEST_DEFAULT']['DEFAULT_LINK'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-default';

$mapping['TEST_NO_DEFAULT']['USER_ATTRIBUTE'] = 'TestNoDefaultIdentifier';
$mapping['TEST_NO_DEFAULT']['LINK'] = array();

$mapping['TEST_ARRAY']['USER_ATTRIBUTE'] = 'TestArrayIdentifier';
$mapping['TEST_ARRAY']['LINK'] = array();
$mapping['TEST_ARRAY']['LINK']['1110000A'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-array-first';
$mapping['TEST_ARRAY']['LINK']['2220000B'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-array-second';

$mapping['TEST_DOMAIN']['USER_ATTRIBUTE'] = 'TestDomainIdentifier';
$mapping['TEST_DOMAIN']['LINK'] = array();
$mapping['TEST_DOMAIN']['LINK']['7771234A'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-domain-exact';
$mapping['TEST_DOMAIN']['REGEX_LINK'] = array();
$mapping['TEST_DOMAIN']['REGEX_LINK']['/^777[0-9]{4}[A-Z]$/i'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-domain-regex';
$mapping['TEST_DOMAIN']['DOMAIN_MAP'] = array(
    'redirector.test' => 'https://service.test/domain-map',
);
$mapping['TEST_DOMAIN']['DEFAULT_LINK'] = 'https://service.test/domain-default';

$mapping['TEST_FILTER_DENIED']['USER_ATTRIBUTE'] = 'TestDeniedIdentifier';
$mapping['TEST_FILTER_DENIED']['LINK'] = array();
$mapping['TEST_FILTER_DENIED']['LINK']['8880000A'] = 'https://' . $_SERVER['SERVER_NAME'] . '/target-filter-denied';
$mapping['TEST_FILTER_DENIED']['FILTER'] = array(
    'USER_ATTRIBUTE' => 'TestAccess',
    'REGEX'          => '/^allowed$/',
);

$mapping['TEST_REPLACE']['REPLACE']['USER_ATTRIBUTE'] = 'TestReplacement';

$mapping['TEST_REPLACE_FAILURE']['USER_ATTRIBUTE'] = 'TestReplaceIdentifier';
$mapping['TEST_REPLACE_FAILURE']['LINK'] = array(
    '9990000A' => 'https://service.test/%TestReplacement%',
);
$mapping['TEST_REPLACE_FAILURE']['REPLACE']['USER_ATTRIBUTE'] = 'TestReplacement';

$testAndFilter = array(
    'OPERATOR' => 'AND',
    'RULES' => array(
        array(
            'USER_ATTRIBUTE' => 'TestRole',
            'REGEX'          => '/^staff$/',
        ),
        array(
            'OPERATOR' => 'OR',
            'RULES' => array(
                array(
                    'USER_ATTRIBUTE' => 'TestRegion',
                    'REGEX'          => '/^north$/',
                ),
                array(
                    'USER_ATTRIBUTE' => 'TestTier',
                    'REGEX'          => '/^gold$/',
                ),
            ),
        ),
    ),
);
