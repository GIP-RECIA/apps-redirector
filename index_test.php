<?php
// Fichier des associations et des propriétés
include_once('conf/conf.inc.test.php');
//include_once('conf/conf.inc.php');

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
ini_set('session.cookie_httponly', true); // PHP 5.2.0. minimum
ini_set('session.use_trans_sid', false);

include_once('commonFunction.php');

// client CAS init
phpCAS::client($protocol,$host,$port,$uri, true);

function can_access($conf_property){
  global $CAS_attrs,$msg_access_problem;
  // Vérifie s'il y a un filtre d'accés
  if (! array_key_exists('FILTER',$conf_property)) {
    return true;
  } else if (array_key_exists('FILTER',$conf_property) and array_key_exists('USER_ATTRIBUTE', $conf_property['FILTER']) and array_key_exists('REGEX', $conf_property['FILTER'])) {
    $filter_attr=$conf_property['FILTER']['USER_ATTRIBUTE'];
    $regex=$conf_property['FILTER']['REGEX'];
    log_action("TRACE", "Un filtre sur l'accès est défini et les proriétées USER_ATTRIBUTE et REGEX sont définies.");
    log_action("DEBUG", "Le nom de l'attribut CAS utilisé pour le filtre avec le lien est : ".$filter_attr);
    log_action("DEBUG", "Le tableau des propriétés du filtre est : ".print_r($conf_property['FILTER'], true));
    log_action("DEBUG", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : ".print_r($CAS_attrs[$filter_attr], true));
    if (array_key_exists($filter_attr, $CAS_attrs)){
      log_action("TRACE", "L'attribut utilisateur nécessaire au filtre est fourni par le serveur CAS.");
      if (!is_array($CAS_attrs[$filter_attr])){
        if (preg_match($regex, $CAS_attrs[$filter_attr])){
          log_action("DEBUG", "Le test du filtre est positif");
          return true;
        }
        log_action("INFO", "Le filtre interdit l'accès à l'utilisateur !");
        return false;
      }
      $found=false;
      $i=0;
      log_action("TRACE", "Nous sommes dans le cas d'un tableau de valeurs retournées pas le CAS");
      log_action("DEBUG", "Liste des valeurs CAS à tester : ".print_r($CAS_attrs[$filter_attr], true));
      while (!$found and $i < sizeof($CAS_attrs[$filter_attr])){
        $current_CAS_attr=$CAS_attrs[$filter_attr][$i];
        log_action("DEBUG", "Teste l'appartenance de ".$current_CAS_attr);
        if (preg_match($regex, $current_CAS_attr)) {
          $found = true;
          log_action("DEBUG", "Le teste est positif");
        } else {
          log_action("DEBUG", "Le test est négatif");
        }
        $i++;
      }
      if (!found){
        log_action("INFO", "Le filtre interdit l'accès à l'utilisateur !");
      }
      return $found;
    }
    log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $filter_attr . " souhaité pour le filtre. La liste des attributs fournis par le serveur CAS sont : " . print_r($CAS_attrs, true));
    echo $msg_access_problem;
    exit();
  }
  log_action("ERROR", "Un filtre a été défini mais celui-ci n'est pas correctement configuré avec les attributs USER_ATTRIBUTE et REGEX.");
  echo $msg_access_problem;
  exit();
}

function do_replacement($conf_property,$chaine){
  global $CAS_attrs,$msg_access_problem;
  // vérifie s'il y a des remplacements à réaliser
  if (! array_key_exists('REPLACE',$conf_property)) {
    return $chaine;
  } else if (array_key_exists('REPLACE',$conf_property) and array_key_exists('USER_ATTRIBUTE', $conf_property['REPLACE'])) {
    $replacement_attr = $conf_property['REPLACE']['USER_ATTRIBUTE'];
    if (array_key_exists($replacement_attr, $CAS_attrs)){
      log_action("TRACE", "L'attribut utilisateur nécessaire au remplacement de chaîne est fourni par le serveur CAS.");
      if (!is_array($CAS_attrs[$replacement_attr])){
        $replacement_value=strtolower($CAS_attrs[$replacement_attr]);
        if (array_key_exists('VALUE_TO_LOWERCASE', $conf_property['REPLACE']) and !$conf_property['REPLACE']['VALUE_TO_LOWERCASE']) {
          $replacement_value=strtoupper($CAS_attrs[$replacement_attr]);
        }
        $modif_chaine = str_ireplace('%'.$replacement_attr.'%',$replacement_value,$chaine);
        log_action("DEBUG", "Le remplacement de caractère sur la chaîne ".$chaine." à retourné :" . $modif_chaine);
        return $modif_chaine;
      }
      $i=0;
      log_action("ERROR", "Remplacement d'une chaîne par rapport à un attribut CAS contenant plusieurs valeurs ! Liste des valeurs CAS retournées : ".print_r($CAS_attrs[$replacement_attr], true));
      echo $msg_access_problem;
      exit();
    }
    log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $replacement_attr . " souhaité pour le filtre. La liste des attributs fournis par le serveur CAS sont : " . print_r($CAS_attrs, true));
    echo $msg_access_problem;
    exit();
  }
  log_action("ERROR", "Une chaîne de remplacement a été définie mais celle-ci n'est pas correctement configurée avec l'attributs USER_ATTRIBUTE");
  echo $msg_access_problem;
  exit();
}

function do_redirect($conf_property,$url) {
  global $appli, $DEV_MOD, $msg_access_problem;
  log_action("INFO", "Le lien vers lequel rediriger l'utilisateur est : ".$url);
  if (!can_access($conf_property)){
    log_action("ERROR", "L'utilisater " . phpCAS::getUser() . " n'a pas les droits pour accéder à l'application " . $appli . "  !");
    echo $msg_access_problem;
    exit();
  }
  // on vérifie que l'on n'est pas en mod de développement pour s'éviter la redirection
  if (!$DEV_MOD) {
    header('Content-Type: text/html; charset=utf-8;');
    header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".$url);
    exit();
  } else {
    echo 'header("Location: "'.$url.')';
    exit();
  }
}

/**
* Retourn l'url de redirection si OK, null si attribut utilisateur non existant et throw exception si pas de droits d'accès
*/
function find_cas_attr($user_attr, $appli) {
  global $CAS_attrs, $mapping;

  if (array_key_exists($user_attr, $CAS_attrs)) {
    log_action("DEBUG", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : ".print_r($CAS_attrs[$user_attr], true));
    log_action("TRACE", "L'attribut utilisateur nécessaire à la selection du lien est bien fourni par le serveur CAS.");
    if (! is_array($CAS_attrs[$user_attr]) and array_key_exists($CAS_attrs[$user_attr],$mapping[$appli]['LINK'])){
      $cas_attr=$CAS_attrs[$user_attr];
      log_action("TRACE", "Nous ne sommes pas dans le cas d'un tableau de valeurs retournées par le serveur CAS !");
      return $mapping[$appli]['LINK'][$cas_attr];
    } else if (is_array($CAS_attrs[$user_attr])){
      /* S'il y a plusieurs valeurs on prend la première qui vient, c'est pour cela qu'il faut configurer en premier dans le fichier conf.inc.php les propriétées prioritaire */
      $possible_val_user_attr=array_keys($mapping[$appli]['LINK']);
      $found=false;
      $i=0;
      log_action("TRACE", "Nous sommes dans le cas d'un tableau de valeurs retournée pas le CAS");
      log_action("DEBUG", "Liste des propriétés définies à tester : ".print_r($possible_val_user_attr, true));
      while (!$found and $i < sizeof($possible_val_user_attr)){
        log_action("DEBUG", "Teste l'appartenance de ".$possible_val_user_attr[$i]);
        if (in_array($possible_val_user_attr[$i], $CAS_attrs[$user_attr])) {
          $found = true;
          $cas_attr=$possible_val_user_attr[$i];
          log_action("DEBUG", "Le teste est positif");
        } else {
          log_action("DEBUG", "Le teste est négatif");
        }
        $i++;
      }
      if (! $found){
        log_action("ERROR", "Les valeurs des propriétées définies pour l'application " . $appli . " n'ont pas été trouvées parmis celles fournies par le serveur CAS pour l'utilisateur " . phpCAS::getUser() ." !");
        return;
      } else {
        return $mapping[$appli]['LINK'][$cas_attr];
      }
    } else if (array_key_exists('DEFAULT_LINK',$mapping[$appli])) {
      // Cas où rien n'a été trouvé en fonction de l'attribut utilisateur, dans ce cas on prend la valeur par défaut si celle-ci est définie.
      log_action("TRACE", "Nous sommes dans le cas de l'utilisation du lien par défaut.");
      $LINK = do_replacement($mapping[$appli], $mapping[$appli]['DEFAULT_LINK']);
      return $LINK;
      // cas du !array_key_exists($CAS_attrs[$user_attr],$mapping[$appli]['LINK']) and !is_array($CAS_attrs[$user_attr])
    } else {
      log_action("ERROR", "Aucune propriétée n'a été définie pour l'application " . $appli . ", l'attribut CAS choisi " . $user_attr . " et la valeur " . $CAS_attrs[$user_attr] . ", vérifiez la configuration (par exemple l'association profil/url dans le fichier conf.inc.php).");
      throw new Exception("Configuration error !");
    }
  } else {
    log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $user_attr . " souhaité pour l'application " . $appli . ". La liste des attributs fournis par le serveur CAS sont : " . print_r($CAS_attrs, true));
    return;
  }
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

if ($DEV_MOD){
  log_action("WARN", "Le mode développement est activé !");
  error_reporting(E_ALL);
  ini_set('display_errors','On');

  ?>
  <html>
  <head>
  <title>ESCO-Apps-Redirector</title>
  </head>
  <body>
  <?php
}
log_action("DEBUG","SessionID (si aucun global logout non fonctionnel) : ".session_id()." et request value : ".print_r($_REQUEST, true));
log_action("DEBUG","Successfull Authentication!");
log_action("DEBUG","Connexion au serveur cas avec les paramètres suivants : ".$protocol.",".$host.":".$port."/".$uri);
log_action("INFO","L'utilisateur est correctement authentifié et son uid est : " . phpCAS::getUser());
log_action("DEBUG","La version du client phpCAS est : " . phpCAS::getVersion());
log_action("DEBUG","Le tableau des attributs CAS fournis est : ".print_r($CAS_attrs, true));
if ($DEV_MOD){
  ?>
  <h1>Successfull Authentication!</h1>
  <p><a href="?logout=">Logout</a></p>
  <p>connexion au serveur cas avec les paramètres suivants :<b><?php echo $protocol.",".$host.":".$port."/".$uri; ?></b></p>
  <p>the user's login is <b><?php echo phpCAS::getUser(); ?></b>.</p>
  <p>phpCAS version is <b><?php echo phpCAS::getVersion()."<br>"; print_r(phpCAS::getAttributes());?></b>.</p>
  <p>attribute  ESCOUAICourant is <?php echo (array_key_exists('ESCOUAICourant',$CAS_attrs)) ? $CAS_attrs['ESCOUAICourant']: "<b>Not provided</b>"; ?></b>.</p>
  <p>conf proxy <?php print_r($cas_real_hosts); ?></b>.</p>
  <?php
}
$msg_access_problem = '<div style="text-align:center;margin-left: auto;margin-right: auto;">Vous n\'avez pas acc&egrave;s &agrave; ce service !</div>';
if (isset($_GET['appli']) and $_GET['appli']!="" ){
  log_action("INFO","Le nom de l'application demandée est : ".$_GET['appli']);
  $appli = $_GET['appli'];
  if (is_array($mapping) && array_key_exists($appli, $mapping)){
    log_action("TRACE", "L'application demandée fait bien partie de la liste des applications configurées.");
    if (!array_key_exists('USER_ATTRIBUTE',$mapping[$appli]) || !array_key_exists('LINK',$mapping[$appli])) {
      log_action("ERROR", "Les propriétés USER_ATTRIBUTE et LINK dans la property \$mapping['".$appli."'] doivent être renseignées !");
      echo $msg_access_problem;
      exit();
    }
    $user_attr = $mapping[$appli]['USER_ATTRIBUTE'];
    $user_attr_fallback = array_key_exists('USER_ATTRIBUTE_FALLBACK', $mapping[$appli]) ? $mapping[$appli]['USER_ATTRIBUTE_FALLBACK'] : null;
    log_action("DEBUG", "Le nom de l'attribut CAS utilisé pour le mapping avec le lien est : ".$user_attr);
    log_action("DEBUG", "Le tableau des liens associés aux propriétés définies pour l'application est : ".print_r($mapping[$appli], true));
    log_action("DEBUG", "Le nom de l'attribut CAS de fallback utilisé pour le mapping avec le lien est : ".$user_attr_fallback);
    if (is_array($CAS_attrs)){
      try {
        $redirect_rslt = find_cas_attr($user_attr, $appli);
        // fallback sur l'attribut de fallback si défini
        if (is_null($redirect_rslt) && ! is_null($user_attr_fallback)) {
          log_action("DEBUG", "L'attribut utilisateur de fallback sera utilisé pour le mapping car l'attribut de base n'est pas fourni.");
          $redirect_rslt = find_cas_attr($user_attr_fallback, $appli);
        }
        // si url de redirect OK
        if (! is_null($redirect_rslt)) do_redirect($mapping[$appli], $redirect_rslt);
        // sinon message d'erreur
        log_action("DEBUG", "Aucune url de redirection n'a été trouvée.");
        echo $msg_access_problem;
      } catch (Exception $e) {
        echo $msg_access_problem;
      }
    } else {
      log_action("ERROR", "Le serveur CAS n'a pas retourné d'attribut utilisateur souhaité pour l'application " . $appli . ". La liste des attributs fournis par le serveur CAS sont : " . print_r($CAS_attrs, true));
      echo $msg_access_problem;
    }
  } else {
    log_action("ERROR","L'application demandée n'est pas définie dans la configuration, vérifiez la configuration (dans le fichier conf.inc.php).");
    echo $msg_access_problem;
  }
} else {
  log_action("ERROR","Il manque le paramètre définissant l'application en paramètre de l'url d'accès !");
  echo $msg_access_problem;
}
if ($DEV_MOD){
  ?>
  </body>
  </html>
  <?php
}
?>
