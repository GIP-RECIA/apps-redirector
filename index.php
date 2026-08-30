<?php
// Fichier des associations et des propriétés
include_once('conf/conf.inc.php');
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
$msg_access_problem = '<div style="text-align:center;margin-left: auto;margin-right: auto;">Vous n\'avez pas acc&egrave;s &agrave; ce service !</div>';
if (isset($_GET['appli']) and $_GET['appli']!="" ){
  log_action("INFO","Le nom de l'application demandée est : ".$_GET['appli']);
  $appli = $_GET['appli'];
  if (is_array($mapping) && array_key_exists($appli, $mapping)){
    log_action("TRACE", "L'application demandée fait bien partie de la liste des applications configurées.");
    if (array_key_exists('DOMAIN',$mapping[$appli]) && array_key_exists('DOMAIN_MAP',$mapping[$appli])) {
      log_action("DEBUG", "Cas de configuration sur le mapping de domaine");
      log_action("DEBUG", "Les clés de configuration définies pour l'application sont : ".implode(', ', array_keys($mapping[$appli])));
      log_action("TRACE", "Le tableau des liens associés aux domaines courants définis pour l'application est : ".print_r($mapping[$appli], true));
      try {
        $current_domain = $mapping[$appli]['DOMAIN'];
        $redirect_rslt = find_link_override($mapping[$appli]);
        if (is_null($redirect_rslt)) {
          $redirect_rslt = find_regex_link($mapping[$appli]);
        }
        if (is_null($redirect_rslt)) {
          $redirect_rslt = array_key_exists($current_domain, $mapping[$appli]['DOMAIN_MAP']) ? $mapping[$appli]['DOMAIN_MAP'][$current_domain] : null;
        }
        $default_redirect = array_key_exists('DEFAULT_LINK', $mapping[$appli]) ? $mapping[$appli]['DEFAULT_LINK'] : null;
        log_action("DEBUG", "Recherche de l'URL de redirection pour le domaine courant '". $current_domain ."'");
        if (is_null($redirect_rslt)) {
          log_action("INFO", "Aucune URL n'est configurée pour le domaine courant '" . $current_domain . "' et l'application " . $appli . ".");
        }
        if (is_null($redirect_rslt) && ! is_null($default_redirect)) {
          log_action("DEBUG", "Mapping de domaine sur domaine non configuré, appliquer redirection sur l'URL par défaut défini");
          $redirect_rslt = $default_redirect;
        } else if (is_null($redirect_rslt)) {
          log_action("INFO", "Aucun DEFAULT_LINK n'est défini pour l'application " . $appli . ".");
        }
        // si url de redirect OK
        if (! is_null($redirect_rslt)) do_redirect($mapping[$appli], $redirect_rslt);
        // sinon message d'erreur
        log_action("DEBUG", "Aucune url de redirection n'a été trouvée.");
        echo $msg_access_problem;
      } catch (Exception $e) {
        echo $msg_access_problem;
      }
    } else if (array_key_exists('USER_ATTRIBUTE',$mapping[$appli]) && array_key_exists('LINK',$mapping[$appli])) {
      log_action("DEBUG", "Cas de configuration sur le mapping attribut utilisateur");
      $user_attr = $mapping[$appli]['USER_ATTRIBUTE'];
      $user_attr_fallback = array_key_exists('USER_ATTRIBUTE_FALLBACK', $mapping[$appli]) ? $mapping[$appli]['USER_ATTRIBUTE_FALLBACK'] : null;
      log_action("DEBUG", "Le nom de l'attribut CAS utilisé pour le mapping avec le lien est : ".$user_attr);
      log_action("DEBUG", "Les clés de configuration définies pour l'application sont : ".implode(', ', array_keys($mapping[$appli])));
      log_action("TRACE", "Le tableau des liens associés aux propriétés définies pour l'application est : ".print_r($mapping[$appli], true));
      log_action("DEBUG", "Le nom de l'attribut CAS de fallback utilisé pour le mapping avec le lien est : ".$user_attr_fallback);
      if (is_array($CAS_attrs)){
        try {
          $redirect_rslt = find_cas_attr($user_attr, $appli);
          // fallback sur l'attribut de fallback si défini
          if (is_null($redirect_rslt) && ! is_null($user_attr_fallback)) {
            log_action("DEBUG", "L'attribut utilisateur de fallback sera utilisé pour le mapping car l'attribut de base n'est pas fourni.");
            $redirect_rslt = find_cas_attr($user_attr_fallback, $appli);
          }
          if (is_null($redirect_rslt)) {
            $redirect_rslt = find_default_link($mapping[$appli]);
          }
          // si url de redirect OK
          if (! is_null($redirect_rslt)) do_redirect($mapping[$appli], $redirect_rslt);
          // sinon message d'erreur
          log_action("INFO", "Aucune URL de redirection n'a été trouvée pour l'application " . $appli . " avec l'attribut " . $user_attr . ".");
          echo $msg_access_problem;
        } catch (Exception $e) {
          echo $msg_access_problem;
        }
      } else {
        log_action("ERROR", "Le serveur CAS n'a pas retourné d'attribut utilisateur souhaité pour l'application " . $appli . ".");
        log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
        echo $msg_access_problem;
      }
    } else {
      log_action("DEBUG", "Erreur de configuration sur un attribut de mapping");
      log_action("ERROR", "Les propriétés USER_ATTRIBUTE + LINK ou DOMAIN + DOMAIN_MAP dans la property \$mapping['".$appli."'] doivent être renseignées !");
      echo $msg_access_problem;
      exit();
    }
  } else {
    log_action("ERROR","L'application demandée n'est pas définie dans la configuration, vérifiez la configuration (dans le fichier conf.inc.php).");
    echo $msg_access_problem;
  }
} else {
  log_action("ERROR","Il manque le paramètre définissant l'application en paramètre de l'url d'accès !");
  echo $msg_access_problem;
}
?>
