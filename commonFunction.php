<?php

include_once('conf/conf.inc.php');

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

function check_autorized_access() {
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

?>