<?php
// Fichier des associations et des propri&eacute;t&eacute;s
include_once('conf/conf.inc.php');
//include_once('conf/conf.inc.test.php');

class link {
  public $LABEL;

  public $TITLE;

  public $URL;

  function __construct($label, $title, $url){
    $this->LABEL=$label;
    $this->TITLE=$title;
    $this->URL=$url;
  }

}

class groupLink {
  public $GROUP;

  public $LINKLIST;

  function __construct($group, $linkarray){
    $this->GROUP=$group;
    $this->LINKLIST=$linkarray;
  }

}

include_once('commonFunction.php');

function tabForSelectSiteEtab($etab,$filter) {
  $tab = array();
  switch ($filter) {
    case 'CFA' : $tab[0] = new groupLink("DEFAULT", array(new link("Sites de la R&eacute;gion, des CFA...","Sites de la R&eacute;gion, des CFA...","#")));
    $tab[1] = new groupLink("R&eacute;gion", array(
      new link("Guide de l'apprentissage", "Guide de l'apprentissage", "http://www.regioncentre.fr/files/live/sites/regioncentre/files/contributed/docs/education-formation/apprentissage/guide-apprentissage-2012.pdf"),
      new link("R&eacute;gion Centre", "Site de la R&eacute;gion Centre", "http://www.regioncentre.fr"),
      new link("Jeunesocentre","Site Jeunesocentre","http://www.jeunesocentre.fr"),
      new link("L'&eacute;toile: Orientation, Formation ...","Site de l'&eacute;toile: Orientation, Formation et Emploi en R&eacute;gion Centre", "http://www.etoile.regioncentre.fr/GIP/site/etoile")
      )
    );
    $tab[2] = new groupLink("CFA du Cher", array());
    $tab[3] = new groupLink("CFA de l'Eure-et-Loir", array());
    $tab[4] = new groupLink("CFA de l'Indre", array());
    $tab[5] = new groupLink("CFA de l'Indre-et-Loire", array());
    $tab[6] = new groupLink("CFA du Loir-et-Cher", array());
    $tab[7] = new groupLink("CFA du Loiret", array());
  break;
  case 'LYCEE' : $tab[0] = new groupLink("DEFAULT", array(new link("Sites de la R&eacute;gion, des Lyc&eacute;es...","Sites de la R&eacute;gion, des Lyc&eacute;es...","#")));
  $tab[1] = new groupLink("R&eacute;gion", array(
    new link("R&eacute;gion Centre", "Site de la R&eacute;gion Centre", "http://www.regioncentre.fr"),
    new link("JeunesOCentre","Site JeunesOCentre","http://www.jeunesocentre.fr"),
    new link("L'&eacute;toile: Orientation, Formation ...","Site de l'&eacute;toile: Orientation, Formation et Emploi en R&eacute;gion Centre", "http://www.etoile.regioncentre.fr/GIP/site/etoile")
    )
  );
  $tab[2] = new groupLink("Lyc&eacute;es du Cher", array());
  $tab[3] = new groupLink("Lyc&eacute;es de l'Eure-et-Loir", array());
  $tab[4] = new groupLink("Lyc&eacute;es de l'Indre", array());
  $tab[5] = new groupLink("Lyc&eacute;es de l'Indre-et-Loire", array());
  $tab[6] = new groupLink("Lyc&eacute;es du Loir-et-Cher", array());
  $tab[7] = new groupLink("Lyc&eacute;es du Loiret", array());
break;
case 'CLG37' : $tab[0] = new groupLink("DEFAULT", array(new link("Sites du CG de l'Indre-et-Loire, des Coll&eagrave;ges...","Sites du CG de l'Indre-et-Loire, des Coll&eagrave;ges...","#")));
$tab[1] = new groupLink("CG de l'Indre-et-loire", array(
  new link("CG 37", "Site du CG de l'Indre-et-loire", "http://www.cg37.fr/"),
  )
);
//$tab[2] = new groupLink("Coll&eagrave;ges du Cher", array());
//$tab[3] = new groupLink("Coll&eagrave;ges de l'Eure-et-Loir", array());
//$tab[4] = new groupLink("Coll&eagrave;ges de l'Indre", array());
$tab[5] = new groupLink("Coll&eagrave;ges de l'Indre-et-Loire", array());
//$tab[6] = new groupLink("Coll&eagrave;ges du Loir-et-Cher", array());
//$tab[7] = new groupLink("Coll&eagrave;ges du Loiret", array());
break;
}

foreach ($etab as $key => $value) {
  //log_action("Key :".print_r($key, true));
  //log_action("Value :".print_r($value, true));
  if ($value['TYPE'] == $filter){
    $dep = substr($key, 1, 2);
    //log_action(print_r($dep, true));
    switch ($dep) {
      case '18' : $tab[2]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      case '28' : $tab[3]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      case '36' : $tab[4]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      case '37' : $tab[5]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      case '41' : $tab[6]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      case '45' : $tab[7]->LINKLIST[] = new link($value['LABEL'], "Site du ".$value['LABEL'], $value['SITE']);break;
      DEFAULT : log_action("ERREUR Ne doit pas arriver : ".print_r($dep, true));
    }
  }
}

return $tab;
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 1728000");
//header("Access-Control-Allow-Headers: Origin, X-Requested-With, X-Prototype-Version,Content-Type, Cache-Control");

$TYPE="";
# First check whether the Origin header exists
if( isset($_SERVER['HTTP_ORIGIN']) ) {
  # Define a list of permitted origins
  $allowed = array('https://test-lycee.portail.ent', 'https://test-cfa.portail.ent', 'https://test.cas.ent', 'https://test.college.ent', 'https://lycees.netocentre.fr', 'https://cfa.netocentre.fr', 'https://www.touraine-eschool.fr', 'https://ent.netocentre.fr');
  $allowed_CFA = array('https://test-cfa.portail.ent', 'https://cfa.netocentre.fr');
  $allowed_LYCEE = array('https://lycees.netocentre.fr', 'https://test-lycee.portail.ent');
  $allowed_CLG37 = array('https://test.college.ent', 'https://www.touraine-eschool.fr');
  # Check whether our origin is permitted.
  if(in_array($_SERVER['HTTP_ORIGIN'], $allowed) ){
    $filtered_url = filter_input(INPUT_SERVER, 'HTTP_ORIGIN', FILTER_SANITIZE_URL);
    $send_header  = 'Access-Control-Allow-Origin: '.$filtered_url;
    header($send_header);
    // Send your content here.
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_CFA) ){
      $TYPE="CFA";
    } else if  (in_array($_SERVER['HTTP_ORIGIN'], $allowed_LYCEE) ){
      $TYPE="LYCEE";
    } else if  (in_array($_SERVER['HTTP_ORIGIN'], $allowed_CLG37) ){
      $TYPE="CLG37";
    }
  }
} else {
  exit;
}
#JSON returns
if(function_exists('json_encode')) {
  header('Content-Type: application/json; charset=utf-8;');
  if (isset($_POST['SITESETAB'])) {
    if ( $_POST['SITESETAB'] != "") {
      echo json_encode(array("URL" => $mapping['SITEETAB']['LINK'][$_POST['SITESETAB']], 'TITLE' => "Aller au site web de mon établissement."));
    } else {
      exit;
    }
  } else {
    $tab = tabForSelectSiteEtab($etab,$TYPE);
    log_action("DEBUG", print_r($tab , true));
    echo json_encode($tab);
  }
} else {
  log_action("ERROR", "exit has function json_encode doesn't exist" );
  exit;
}
?>
