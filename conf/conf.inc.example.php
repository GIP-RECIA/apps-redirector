<?php

$LOG_LVL="INFO"; // TRACE,DEBUG,INFO,WARN,ERROR sont les valeurs possibles, attention à la CASSE !
//$LOG_LVL="DEBUG"; // TRACE,DEBUG,INFO,WARN,ERROR sont les valeurs possibles, attention à la CASSE !
// passer dev_mod à true affiche certains log dans le HTML et évite d'effectuer les redirections.
$DEV_MOD=false;
$PATH_CAS_LIB="/var/www/phpCAS/phpCAS-1.3.8/CAS.php";
$PATH_CAS_CONFIG="conf/cas.inc.php";
//$PATH_CAS_CONFIG="conf/cas-test.inc.php";
$LOG_FILENAME = "logs/" . date("Y-m-d") . ".log";
//$LOG_FILENAME = "logs/" . date("Y-m-d") . "-test.log";
$PHPCAS_LOG_FILENAME="logs/phpCAS.log";

$AUTORIZED_IPS=array('127.0.0.1');
$AUTORIZED_SUBNET=array('192.168.0.');

if ($DEV_MOD){
  echo "dev_mod";
  error_reporting(E_ALL);
  ini_set('display_errors','On');
}
print_r($DEV_MOD, true);

/** tableau de mapping des noms, type, etc sous la forme $etab['UAI']['LABEL|TYPE|SITE']='value' **/
static $mapping = array();
/** For json informaitons */
static $etab = array();

/* Configuration d'une application
//définition de l'attribut utilisateur fourni dans le ticket CAS permettant de faire la distinction dans l'association du lien vers lequel rediriger l'utilisateur
$mapping['APPS_NAME']['USER_ATTRIBUTE']='ENTPersonProfils';
//définition du mapping valeur attribut et lien.
$mapping['APPS_NAME']['LINK']['PROFIL_1']='http://AN_URL';
//définition facultative d'un lien par défaut si aucun lien spécifique n'est trouvé.
$mapping['APPS_NAME']['DEFAULT_LINK']='http://A_DEFAULT_URL';
//une chaîne vide ou la valeur 'null' indique qu'aucune redirection ne doit être effectuée.
$mapping['APPS_NAME']['LINK']['PROFIL_WITHOUT_REDIRECT']='null';
//définition facultative d'un remplacement de variable dans toutes les urls de redirection.
$mapping['APPS_NAME']['LINK']['PROFIL_WITH_PLACEHOLDER']='https://%ESCOUAICourant%.example.org';
$mapping['APPS_NAME']['REPLACE']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['APPS_NAME']['REPLACE']['VALUE_TO_LOWERCASE']=true;
//définition facultative d'un filtre sur un autre attribut fourni dans le ticket CAS afin d'empêcher certains utilisateurs d'accéder à l'application.
$mapping['APPS_NAME']['FILTER']['USER_ATTRIBUTE']='ENTPersonJointure';
// regex à appliquer sur la ou les valeurs de l'attribut fourni par le CAS afin de déterminer si l'utilisateur à le droit d'accès.
$mapping['APPS_NAME']['FILTER']['REGEX']='A REGEX';
//définition alternative d'un mapping par domaine. DOMAIN est la valeur courante à tester, DOMAIN_MAP contient les associations domaine/url.
$mapping['APPS_NAME_BY_DOMAIN']['DOMAIN']=$_SERVER['SERVER_NAME'];
$mapping['APPS_NAME_BY_DOMAIN']['DEFAULT_LINK']='http://A_DEFAULT_URL';
$mapping['APPS_NAME_BY_DOMAIN']['DOMAIN_MAP']=array();
$mapping['APPS_NAME_BY_DOMAIN']['DOMAIN_MAP']['domain.example.org']='http://AN_URL';
//définition facultative de surcharges établissement pour un mapping par domaine.
$mapping['APPS_NAME_BY_DOMAIN']['USER_ATTRIBUTE']='ESCOUAICourant';
$mapping['APPS_NAME_BY_DOMAIN']['USER_ATTRIBUTE_FALLBACK']='ESCOSIRENCourant';
$mapping['APPS_NAME_BY_DOMAIN']['LINK']['UAI_WITH_SPECIFIC_URL']='http://A_SPECIFIC_URL';
*/

/*
 * APP EXEMPLE
 **/
//$mapping['EXEMPLE']['USER_ATTRIBUTE']='ENTPersonProfils';
//$mapping['EXEMPLE']['LINK']=array();
//$mapping['EXEMPLE']['LINK']['National_ENS']='url2';
//$mapping['EXEMPLE']['LINK']['National_ELV']='url1';
//$mapping['EXEMPLE']['LINK']['National_DIR']='url3';
//$mapping['EXEMPLE']['LINK']['National_ETA']='url2';
//$mapping['EXEMPLE']['FILTER']['USER_ATTRIBUTE']='ENTPersonJointure';
//$mapping['EXEMPLE']['FILTER']['REGEX']='/^AC-ORLEANS-TOURS/';

//// CF conf par etablissements.

/************************************************************************************/
/*                                                                                  */
/*      CONF PAR ETABLISSEMENT ET CONF NON DEFAULT                                  */
/*                                                                                  */
/************************************************************************************/

/*
 * Fictif Lycee
 **/
$mapping['SITEETAB']['LINK']['0450822X']='http://www.monsite.fr';
$mapping['APPLI']['LINK']['0450822X']=$mapping['APPLI']['COMMON_LINK_PART'].'id_xxxxx';
$mapping['DEMO1']['LINK']['0450822X']='https://demo1.appli.fr/';
$etab['0450822X']['LABEL']="Lyc&eacute;e Fictif";
$etab['0450822X']['SITE']=$mapping['SITEETAB']['LINK']['0450822X'];
$etab['0450822X']['TYPE']='LYCEE';


// should be at end
ksort($etab);
?>
