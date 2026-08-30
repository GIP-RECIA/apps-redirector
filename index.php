<?php
// Fichier des associations et des propriétés. CI can provide a synthetic config.
$configPath = getenv('REDIRECTOR_CONFIG') ?: 'conf/conf.inc.php';
include_once($configPath);
// import phpCAS lib
include_once($PATH_CAS_LIB);
include_once($PATH_CAS_CONFIG);

// session management
if ($session_mode == 'FILE' && $session_cluster_path_shared !== '') {
  ini_set('session.save_path',realpath($session_cluster_path_shared));
}
ini_set('session.name', 'APPS_REDIRECTOR');
ini_set('session.use_cookies',  true);
ini_set('session.use_only_cookies', true);
ini_set('session.cookie_lifetime', 15 * 60); // en secondes
ini_set('session.cookie_path',  dirname($_SERVER['PHP_SELF']));
ini_set('session.cookie_domain', "");
ini_set('session.cookie_secure', true);
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_httponly', true); // PHP 5.2.0. minimum
ini_set('session.use_trans_sid', false);

// client CAS init
phpCAS::client($protocol, $host, $port, $uri, $cas_service_base_urls, true);

include_once('commonFunction.php');
include_once('resolution.php');

// Diagnostic output is available only from explicitly authorized IP addresses.
$DEV_MOD = isset($DEV_MOD) && $DEV_MOD && check_authorized_access();
if ($DEV_MOD) {
  error_reporting(E_ALL);
  ini_set('display_errors', 'On');
}


if (log_lvl_to_int("DEBUG") >= log_lvl_to_int($LOG_LVL)){
  // Activation de la log phpCAS
  phpCAS::setDebug($PHPCAS_LOG_FILENAME);
  phpCAS::setVerbose(true);
}

// initialize phpCAS
//phpCAS::client($protocol,$host,$port,$uri);

// set the language to french
phpCAS::setLang(PHPCAS_LANG_FRENCH);

// no SSL validation for the CAS server for dev mod only
//phpCAS::setNoCasServerValidation();
phpCAS::setCasServerCACert($cas_server_ca_cert_path);

if ($session_mode == 'BROADCAST' && is_array($rebroadcast_nodes)) {
  foreach( $rebroadcast_nodes as $value ){
    phpCAS::addRebroadcastNode($value);
  }
}

// to handle the global logout
//phpCAS::handleLogoutRequests(false);
phpCAS::handleLogoutRequests(true, $cas_real_hosts);

// force CAS authentication
phpCAS::forceAuthentication();

// logout if desired
if (isset($_REQUEST['logout'])) {
  phpCAS::logout();
}

$CAS_attrs = phpCAS::getAttributes();
$CAS_user = phpCAS::getUser();

log_action("DEBUG","SessionID (si aucun global logout non fonctionnel) : ".session_id()." et request keys : ".implode(', ', array_keys($_REQUEST)));
log_action("TRACE","Request values : ".print_r($_REQUEST, true));
log_action("DEBUG","Successfull Authentication!");
log_action("DEBUG","Connexion au serveur cas avec les paramètres suivants : ".$protocol.",".$host.":".$port."/".$uri);
log_action("INFO","L'utilisateur est correctement authentifié et son uid est : " . phpCAS::getUser());
log_action("DEBUG","La version du client phpCAS est : " . phpCAS::getVersion());
log_action("DEBUG","Les attributs CAS fournis sont : ".implode(', ', array_keys($CAS_attrs)));
log_action("TRACE","Le tableau des attributs CAS fournis est : ".print_r($CAS_attrs, true));
if (isset($_GET['appli']) and $_GET['appli']!="" ){
  log_action("INFO","Le nom de l'application demandée est : ". $_GET['appli']);
  $appli = $_GET['appli'];
  try {
    $current_domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    $redirect_rslt = resolve_redirect_target($appli, $current_domain);
    if (! is_null($redirect_rslt)) {
      do_redirect($mapping[$appli], $redirect_rslt);
      exit();
    }
    log_action("DEBUG", "Aucune url de redirection n'a été trouvée.");
    render_access_denied_page();
  } catch (Throwable $e) {
    log_action("ERROR", $e->getMessage());
    render_access_denied_page();
    exit();
  }
} else {
  log_action("ERROR","Il manque le paramètre définissant l'application en paramètre de l'url d'accès !");
  render_access_denied_page();
}
?>
