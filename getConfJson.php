<?php

// Fichier des associations et des propri&eacute;t&eacute;s
include_once('conf/conf.inc.php');
//include_once('conf/conf.inc.test.php');
$LOG_FILENAME = "logs/getConfJson-" . date("Y-m-d") . ".log";

include_once('commonFunction.php');

function confForApp($conf, $etab, $appli) {
  $tab = array();
  if (array_key_exists($appli,$conf)) {
    $tab["DEFAULT"] = $conf[$appli]['DEFAULT_LINK'];
    foreach ($conf[$appli]['LINK'] as $key => $value) {
      //log_action("Key :".print_r($key, true));
      //log_action("Value :".print_r($value, true));
      $tab[$key] = $value;
    }
    ksort($tab);
  }

  return $tab;
}
$LOG_LVL="DEBUG";
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 1728000");
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, X-Prototype-Version,Content-Type, Cache-Control");

#JSON returns
$allow_access = check_autorized_access();

if(!$allow_access) {
  log_action("ERROR", "Forbidden Access:");
  log_action("ERROR", print_r($_SERVER , true));
  http_response_code(403);
  echo "Forbidden Access";
  exit();
}

if(function_exists('json_encode')) {
  header('Content-Type: application/json; charset=utf-8;');
  if ( $_GET['APPLI'] != "") {
    $tab = confForApp($mapping,null,$_GET['APPLI']);
    log_action("DEBUG", print_r($tab , true));
    echo json_encode($tab);
  } else {
    log_action("ERROR", "Aucun paramètre n'a été fourni" );
    http_response_code(400);
    echo "Wrong Request";
  }
} else {
  log_action("ERROR", "exit has function json_encode doesn't exist" );
  http_response_code(400);
  echo "Wrong Request";
}

?>
