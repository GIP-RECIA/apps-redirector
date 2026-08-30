<?php
/**
 * Minimal phpCAS substitute for index.php integration tests. Values are passed
 * through environment variables so each process has an isolated CAS session.
 */

define('SAML_VERSION_1_1', 'SAML_VERSION_1_1');
define('PHPCAS_LANG_FRENCH', 'fr');

class phpCAS
{
    public static function client($protocol, $host, $port, $uri, $serviceUrls, $changeSessionId)
    {
    }

    public static function setDebug($path)
    {
    }

    public static function setVerbose($enabled)
    {
    }

    public static function setLang($language)
    {
    }

    public static function setCasServerCACert($path)
    {
    }

    public static function addRebroadcastNode($node)
    {
    }

    public static function handleLogoutRequests($enabled, $hosts)
    {
    }

    public static function forceAuthentication()
    {
    }

    public static function logout()
    {
    }

    public static function getAttributes()
    {
        $attributes = json_decode(getenv('CI_CAS_ATTRIBUTES') ?: '{}', true);
        return is_array($attributes) ? $attributes : array();
    }

    public static function getUser()
    {
        return getenv('CI_CAS_USER') ?: 'ci-user';
    }

    public static function getVersion()
    {
        return 'ci-fake';
    }
}
