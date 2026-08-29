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

// Diagnostic output is available only from explicitly authorized IP addresses.
$DEV_MOD = isset($DEV_MOD) && $DEV_MOD && check_authorized_access();
if ($DEV_MOD) {
  error_reporting(E_ALL);
  ini_set('display_errors', 'On');
}

function can_access($conf_property){
  global $msg_access_problem;
  // Vérifie s'il y a un filtre d'accés
  if (! array_key_exists('FILTER',$conf_property)) {
    return true;
  }
  try {
    if (evaluate_filter_rule($conf_property['FILTER'])) {
      log_action("DEBUG", "Le test du filtre est positif");
      return true;
    }
    log_action("INFO", "Le filtre interdit l'accès à l'utilisateur !");
    return false;
  } catch (Throwable $e) {
    log_action("ERROR", $e->getMessage());
    echo $msg_access_problem;
    exit();
  }
}

function evaluate_filter_rule($rule) {
  if (!is_array($rule)) {
    throw new Exception("Un filtre a été défini mais celui-ci n'est pas correctement configuré.");
  }
  if (array_key_exists('USER_ATTRIBUTE', $rule) and array_key_exists('REGEX', $rule)) {
    return match_filter_condition($rule);
  }
  if (!array_key_exists('OPERATOR', $rule) or !array_key_exists('RULES', $rule) or !is_array($rule['RULES'])) {
    throw new Exception("Un filtre composé doit définir les propriétés OPERATOR et RULES.");
  }
  if (!is_string($rule['OPERATOR'])) {
    throw new Exception("L'opérateur du filtre composé doit être une chaîne.");
  }
  $operator = strtoupper($rule['OPERATOR']);
  if ($operator !== 'AND' and $operator !== 'OR') {
    throw new Exception("L'opérateur de filtre " . $rule['OPERATOR'] . " n'est pas supporté.");
  }
  if (empty($rule['RULES'])) {
    throw new Exception("Un filtre composé doit contenir au moins une règle.");
  }
  $i = 0;
  while ($i < sizeof($rule['RULES'])) {
    $match = evaluate_filter_rule($rule['RULES'][$i]);
    if ($operator === 'AND' and !$match) {
      return false;
    }
    if ($operator === 'OR' and $match) {
      return true;
    }
    $i++;
  }
  return $operator === 'AND';
}

function match_filter_condition($condition) {
  global $CAS_attrs;
  $filter_attr = $condition['USER_ATTRIBUTE'];
  $regex = $condition['REGEX'];
  if (!is_string($filter_attr) || !is_string($regex)) {
    throw new Exception("Les propriétés USER_ATTRIBUTE et REGEX du filtre doivent être des chaînes.");
  }
  if (!is_array($CAS_attrs)) {
    throw new Exception("Le serveur CAS n'a pas retourné d'attributs exploitables pour le filtre.");
  }
  log_action("TRACE", "Le tableau de la condition de filtre est : ".print_r($condition, true));
  log_action("DEBUG", "Le nom de l'attribut CAS utilisé pour la condition est : ".$filter_attr);
  if (!array_key_exists($filter_attr, $CAS_attrs)) {
    log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
    throw new Exception("Le serveur CAS n'a pas retourné l'attribut " . $filter_attr . " souhaité pour le filtre. Les attributs fournis par le serveur CAS sont : " . implode(', ', array_keys($CAS_attrs)));
  }
  log_action("TRACE", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : ".print_r($CAS_attrs[$filter_attr], true));
  $cas_values = is_array($CAS_attrs[$filter_attr]) ? $CAS_attrs[$filter_attr] : array($CAS_attrs[$filter_attr]);
  $i = 0;
  while ($i < sizeof($cas_values)) {
    log_action("TRACE", "Teste l'appartenance de ".$cas_values[$i]);
    $match = @preg_match($regex, $cas_values[$i]);
    if ($match === false) {
      throw new Exception("Expression régulière FILTER invalide : " . $regex);
    }
    if ($match === 1) {
      log_action("DEBUG", "Le test est positif");
      return true;
    }
    log_action("DEBUG", "Le test est négatif");
    $i++;
  }
  return false;
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
      log_action("ERROR", "Remplacement d'une chaîne par rapport à un attribut CAS contenant plusieurs valeurs pour l'attribut " . $replacement_attr . " !");
      log_action("TRACE", "Liste des valeurs CAS retournées : ".print_r($CAS_attrs[$replacement_attr], true));
      echo $msg_access_problem;
      exit();
    }
    log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $replacement_attr . " souhaité pour le remplacement. Les attributs fournis par le serveur CAS sont : " . implode(', ', array_keys($CAS_attrs)));
    log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
    echo $msg_access_problem;
    exit();
  }
  log_action("ERROR", "Une chaîne de remplacement a été définie mais celle-ci n'est pas correctement configurée avec l'attributs USER_ATTRIBUTE");
  echo $msg_access_problem;
  exit();
}

function do_redirect($conf_property,$url) {
  global $appli, $DEV_MOD, $msg_access_problem;
  if (is_null($url) || trim($url) === '' || strtolower(trim($url)) === 'null') {
    log_action("INFO", "Aucune redirection n'est définie pour l'application " . $appli . "  !");
    echo $msg_access_problem;
    exit();
  }
  $url = do_replacement($conf_property, $url);
  log_action("INFO", "Le lien vers lequel rediriger l'utilisateur est : ".$url);
  if (!can_access($conf_property)){
    log_action("ERROR", "L'utilisater " . phpCAS::getUser() . " n'a pas les droits pour accéder à l'application " . $appli . "  !");
    echo $msg_access_problem;
    exit();
  }
  if ($DEV_MOD) {
    header('Content-Type: text/plain; charset=utf-8;');
    echo 'header("Location: "' . $url . '", true, 302);';
    exit();
  }
  header('Content-Type: text/html; charset=utf-8;');
  header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
  header("Location: ".$url, true, 302);
  exit();
}

function find_default_link($conf_property) {
  if (array_key_exists('DEFAULT_LINK', $conf_property)) {
    log_action("TRACE", "Nous sommes dans le cas de l'utilisation du lien par défaut.");
    return $conf_property['DEFAULT_LINK'];
  }
}

function find_link_override($conf_property) {
  global $CAS_attrs;
  if (!array_key_exists('LINK', $conf_property) || !is_array($conf_property['LINK'])) {
    return;
  }
  $override_attrs = array();
  if (array_key_exists('USER_ATTRIBUTE', $conf_property)) {
    $override_attrs[] = $conf_property['USER_ATTRIBUTE'];
  }
  if (array_key_exists('USER_ATTRIBUTE_FALLBACK', $conf_property)) {
    $override_attrs[] = $conf_property['USER_ATTRIBUTE_FALLBACK'];
  }
  $j = 0;
  while ($j < sizeof($override_attrs)) {
    $override_attr = $override_attrs[$j];
    if (array_key_exists($override_attr, $CAS_attrs)) {
      if (!is_array($CAS_attrs[$override_attr]) && array_key_exists($CAS_attrs[$override_attr], $conf_property['LINK'])) {
        return $conf_property['LINK'][$CAS_attrs[$override_attr]];
      } else if (is_array($CAS_attrs[$override_attr])) {
        $possible_values = array_keys($conf_property['LINK']);
        $i = 0;
        while ($i < sizeof($possible_values)) {
          if (in_array($possible_values[$i], $CAS_attrs[$override_attr])) {
            return $conf_property['LINK'][$possible_values[$i]];
          }
          $i++;
        }
      }
    }
    $j++;
  }
}

function find_regex_link($conf_property, $user_attr = null) {
  global $CAS_attrs;
  if (!array_key_exists('REGEX_LINK', $conf_property) || !is_array($conf_property['REGEX_LINK'])) {
    return;
  }
  $regex_attrs = array();
  if (!is_null($user_attr)) {
    $regex_attrs[] = $user_attr;
  } else {
    if (array_key_exists('USER_ATTRIBUTE', $conf_property)) {
      $regex_attrs[] = $conf_property['USER_ATTRIBUTE'];
    }
    if (array_key_exists('USER_ATTRIBUTE_FALLBACK', $conf_property)) {
      $regex_attrs[] = $conf_property['USER_ATTRIBUTE_FALLBACK'];
    }
  }
  $j = 0;
  while ($j < sizeof($regex_attrs)) {
    $regex_attr = $regex_attrs[$j];
    if (array_key_exists($regex_attr, $CAS_attrs)) {
      $cas_values = is_array($CAS_attrs[$regex_attr]) ? $CAS_attrs[$regex_attr] : array($CAS_attrs[$regex_attr]);
      foreach ($conf_property['REGEX_LINK'] as $regex => $link) {
        $i = 0;
        while ($i < sizeof($cas_values)) {
          $match = @preg_match($regex, $cas_values[$i]);
          if ($match === false) {
            log_action("ERROR", "Expression régulière REGEX_LINK invalide : " . $regex);
            throw new Exception("Configuration error !");
          }
          if ($match === 1) {
            log_action("DEBUG", "REGEX_LINK " . $regex . " correspond à l'attribut " . $regex_attr . ".");
            return $link;
          }
          $i++;
        }
      }
    }
    $j++;
  }
}

/**
* Retourn l'url de redirection si OK, null si attribut utilisateur non existant et throw exception si pas de droits d'accès
*/
function find_cas_attr($user_attr, $appli) {
  global $CAS_attrs, $mapping;
  if (array_key_exists($user_attr, $CAS_attrs)) {
    log_action("TRACE", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : ".print_r($CAS_attrs[$user_attr], true));
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
      log_action("DEBUG", "Liste des propriétés définies à tester : ".implode(', ', $possible_val_user_attr));
      while (!$found and $i < sizeof($possible_val_user_attr)){
        log_action("TRACE", "Teste l'appartenance de ".$possible_val_user_attr[$i]);
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
        log_action("DEBUG", "Aucun LINK exact n'a été trouvé pour l'application " . $appli . " et l'attribut CAS " . $user_attr . ".");
        log_action("TRACE", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : ".print_r($CAS_attrs[$user_attr], true));
      } else {
        return $mapping[$appli]['LINK'][$cas_attr];
      }
    }
    if (!is_null($regex_link = find_regex_link($mapping[$appli], $user_attr))) {
      return $regex_link;
    } else if (!is_null($default_link = find_default_link($mapping[$appli]))) {
      // Cas où rien n'a été trouvé en fonction de l'attribut utilisateur, dans ce cas on prend la valeur par défaut si celle-ci est définie.
      return $default_link;
      // cas du !array_key_exists($CAS_attrs[$user_attr],$mapping[$appli]['LINK']) and !is_array($CAS_attrs[$user_attr])
    } else {
      log_action("ERROR", "Aucune propriétée n'a été définie pour l'application " . $appli . " et l'attribut CAS choisi " . $user_attr . ", vérifiez la configuration (par exemple l'association profil/url dans le fichier conf.inc.php).");
      log_action("TRACE", "La valeur CAS non configurée pour l'attribut " . $user_attr . " est : " . $CAS_attrs[$user_attr]);
      throw new Exception("Configuration error !");
    }
  } else {
    log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $user_attr . " souhaité pour l'application " . $appli . ". Les attributs fournis par le serveur CAS sont : " . implode(', ', array_keys($CAS_attrs)));
    log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
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
