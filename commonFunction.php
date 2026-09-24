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
  echo '<script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-page-layout.js"></script>';
  echo '<script type="text/javascript" src="/resource-server/webjars/gip-recia__ui-webcomponents/dist/r-footer.js"></script>';
  echo '<style>html{-webkit-text-size-adjust:100%;box-sizing:border-box;-moz-tab-size:4;tab-size:4;word-break:normal}*,:after,:before{background-repeat:no-repeat;box-sizing:inherit}:after,:before{text-decoration:inherit;vertical-align:inherit}*{margin:0;padding:0}hr{color:inherit;height:0;overflow:visible}details,main{display:block}summary{display:list-item}small{font-size:80%}[hidden]{display:none}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}a{background-color:transparent}a:active,a:hover{outline-width:0}code,kbd,pre,samp{font-family:monospace,monospace}pre{font-size:1em}b,strong{font-weight:bolder}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-0.25em}sup{top:-0.5em}table{border-color:inherit;text-indent:0}iframe{border-style:none}[aria-busy=true]{cursor:progress}[aria-controls]{cursor:pointer}input{border-radius:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-decoration{-webkit-appearance:none}textarea{overflow:auto;resize:vertical}button,input,optgroup,select,textarea{font:inherit}optgroup{font-weight:700}button{overflow:visible}button,select{text-transform:none}[role=button],[type=button],[type=reset],[type=submit],button{cursor:pointer}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button:-moz-focusring{outline:1px dotted ButtonText}[type=reset],[type=submit],button,html [type=button]{-webkit-appearance:button}button,input,select,textarea{background-color:transparent;border-style:none}a:focus,button:focus,input:focus,select:focus,textarea:focus{outline-width:0}select{-moz-appearance:none;-webkit-appearance:none}select::-ms-expand{display:none}select::-ms-value{color:currentColor}legend{border:0;color:inherit;display:table;max-width:100%;white-space:normal}::-webkit-file-upload-button{-webkit-appearance:button;color:inherit;font:inherit}[aria-disabled=true],[disabled]{cursor:default}img{border-style:none}progress{vertical-align:baseline}h6,.h6,h5,.h5,h4,.h4,h3,.h3,h2,.h2,h1,.h1{font-family:"Sora","sans-serif";color:var(--recia-body);margin-bottom:var(--recia-font-size-base)}h1,.h1{font-size:var(--recia-font-size-h1);font-weight:bold}h2,.h2{font-size:var(--recia-font-size-h2);font-weight:bold}h3,.h3{font-size:var(--recia-font-size-h3);font-weight:600}h4,.h4{font-size:var(--recia-font-size-h4);font-weight:600}h5,.h5{font-size:var(--recia-font-size-h5)}h6,.h6{font-size:var(--recia-font-size-h6)}.container-fluid,.container{width:100%;padding-right:16px;padding-left:16px;margin-right:auto;margin-left:auto}@media screen and (width >= 576px){.container{max-width:540px}}@media screen and (width >= 768px){.container{max-width:720px}}@media screen and (width >= 992px){.container{max-width:960px}}@media screen and (width >= 1200px){.container{max-width:1140px}}@media screen and (width >= 1400px){.container{max-width:1320px}}.skip-links{position:absolute;z-index:1031;min-height:var(--recia-header-height);padding:16px;background-color:var(--recia-body-bg);display:flex;align-items:center;transform:translateY(-100%);transition:transform .15s cubic-bezier(0.4, 0, 0.2, 1)}.skip-links>ul{padding-left:0;list-style:none;display:flex;flex-wrap:wrap;gap:16px;font-size:var(--recia-font-size-sm);font-weight:500}.skip-links>ul>li>a{color:inherit;text-decoration:none;color:var(--recia-system-blue)}.skip-links>ul>li>a:hover,.skip-links>ul>li>a:focus-visible{text-decoration:underline}.skip-links>ul>li>a:focus-visible{outline:none;text-decoration-thickness:.2em}@media(hover: none){.skip-links>ul>li>a{text-decoration:underline}}.skip-links>ul::after{content:"";position:absolute;top:0;right:-16px;bottom:0;padding-right:32px;background:linear-gradient(90deg, var(--recia-body-bg) 60%, rgba(255, 255, 255, 0) 100%)}.skip-links:focus-within{transform:translateY(0)}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Bold.ttf") format("truetype");font-weight:bold;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-BoldItalic.ttf") format("truetype");font-weight:bold;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Italic.ttf") format("truetype");font-weight:normal;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Medium.ttf") format("truetype");font-weight:500;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-MediumItalic.ttf") format("truetype");font-weight:500;font-style:italic;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-Regular.ttf") format("truetype");font-weight:normal;font-style:normal;font-display:swap}@font-face{font-family:"DM Sans";src:url("/commun/fonts/DM_Sans/static/DMSans-SemiBold.ttf") format("truetype");font-weight:600;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-Bold.ttf") format("truetype");font-weight:bold;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-Regular.ttf") format("truetype");font-weight:normal;font-style:normal;font-display:swap}@font-face{font-family:"Sora";src:url("/commun/fonts/Sora/static/Sora-SemiBold.ttf") format("truetype");font-weight:600;font-style:normal;font-display:swap}html,body{height:100%}html>body{background-color:var(--recia-body-bg);font-family:"DM Sans","sans-serif";font-style:normal;font-weight:normal;font-size:var(--recia-body-font-size);letter-spacing:0;color:var(--recia-body);display:grid;grid-template-rows:auto auto 1fr auto;grid-template-areas:"header" "alert" "main" "footer"}html>body>header{grid-area:header;height:var(--recia-header-height, 68px)}html>body>.alert{grid-area:alert}html>body>main{grid-area:main;padding-top:32px;padding-bottom:40px;display:grid;grid-auto-rows:min-content;row-gap:40px}html>body>main>*{min-width:0}html>body>footer{grid-area:footer}@media(width >= 768px){html>body>main{padding-bottom:60px;row-gap:60px}}</style>';
  echo '</head>';
  echo '<body>';
  echo '<nav role="navigation" aria-label="Accès rapide" class="skip-links"><ul><li><a href="#main">Contenu</a></li></ul></nav>';
  echo '<header><r-header template-api-path="/commun/portal_template_api.tpl.json" fname="ESCO Apps Redirector" navigation-drawer-visible></r-header></header>';
  echo '<main id="main" tabindex="-1"><div class="container"><r-page-layout back-link="{&quot;name&quot;: &quot;Retour à l\'accueil&quot;,&quot;href&quot;: &quot;/portail&quot;,&quot;target&quot;: &quot;_self&quot;,&quot;rel&quot;: &quot;noopener noreferrer&quot;}" page-title="Accès refusé"><p>' . $message . '</p></r-page-layout></div></main>';
  echo '<footer><r-footer template-api-path="/commun/portal_template_api.tpl.json"></r-footer></footer>';
  echo '</body>';
  echo '</html>';
}

?>
