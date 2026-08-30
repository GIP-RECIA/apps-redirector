<?php

$configPath = getenv('REDIRECTOR_CONFIG') ?: 'conf/conf.inc.php';
include_once($configPath);

function log_action($lvl="ERROR",$msg) {
  global $LOG_FILENAME, $LOG_LVL, $CAS_user;
  if (log_lvl_to_int($lvl) >= log_lvl_to_int($LOG_LVL)){
    $fd = fopen($LOG_FILENAME, "a");
    $now = DateTime::createFromFormat('U.u', microtime(true));
    $str = "[" . $now->format("d/m/Y H:i:s.u") . "] - [" . $CAS_user . "] " .$lvl . " : " . $msg;
    fwrite($fd, $str . PHP_EOL);
    fclose($fd);
  }
}
function log_lvl_to_int($lvl){
  switch ($lvl){
    case "TRACE" : $val=0;break;
    case "DEBUG" : $val=1;break;
    case "INFO" : $val=2;break;
    case "WARN" : $val=3;break;
    case "ERROR" : $val=4;break;
    default:$val=5;
  }
  return $val;
}

function check_authorized_access() {
  global $AUTORIZED_IPS, $AUTORIZED_SUBNET;
  $entry = array();
  if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) $entry = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);

  $allow_access = false;
  foreach($entry as $v){
    if(in_array($v,$AUTORIZED_IPS)) {
      $allow_access = true;
      break;
    }
  }
  if (!$allow_access && !array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
    foreach($AUTORIZED_SUBNET as $v) {
      if(substr($_SERVER['REMOTE_ADDR'], 0, strlen($v)) === $v){
        $allow_access = true;
        break;
      }
    }
  }
  return $allow_access;
}

function render_access_denied_page(string $message = "Vous n'avez pas acc&egrave;s &agrave; ce service !"): void
{
  http_response_code(403);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html>';
  echo '<html lang="fr">';
  echo '<head>';
  echo '<meta charset="utf-8">';
  echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<title>Accès refusé</title>';
  echo '<script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-header.js"></script>';
  echo '<script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-footer.js"></script>';
  echo '<style>body{margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f6f7fb;color:#1f2937}main{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}section{max-width:28rem;background:#fff;border:1px solid #dbe3ee;border-radius:12px;padding:28px 24px;box-shadow:0 6px 24px rgba(15,23,42,.08);text-align:center}h1{margin:0 0 12px;font-size:1.35rem}p{margin:0;line-height:1.5}</style>';
  echo '</head>';
  echo '<body>';
  echo '<header><extended-uportal-header template-api-path="/commun/portal_template_api.tpl.json" fname="ESCO Apps Redirector"></extended-uportal-header></header>';
  echo '<main><section><h1>Accès refusé</h1><p>' . $message . '</p></section></main>';
  echo '<footer><extended-uportal-footer template-api-path="/commun/portal_template_api.tpl.json"></extended-uportal-footer></footer>';
  echo '</body>';
  echo '</html>';
}

?>
