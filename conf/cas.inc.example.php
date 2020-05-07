<?php
$host='cas.univ.fr';
$port=443;
$uri='cas';
$protocol=SAML_VERSION_1_1;
$cas_server_ca_cert_path='/path/to/cert/my_ca_cert.pem';
// proxies server names and servers fro rebroadcast
$cas_real_hosts=array(
  0 => 'frontal1.univ.fr',
  1 => 'frontal1.univ.fr',
  2 => 'node1.univ.fr',
  3 => 'node2.univ.fr',
);
// set the way to share or not the session between several nodes, values are FILE or BROADCAST
//$session_mode='FILE'; // 'FILE' for persisted + NFS sharing / not good for numerous users access or generated session should be removed really regularly.
$session_mode='BROADCAST'; //to use phpCAS rebroadcast mode (forward to all listed node the logout request)
// set these 2 properties depending on previous conf
$session_cluster_path_shared='/path/to/php_sessions/apps-redirector';
$rebroadcast_nodes = array('http://node1.univ.fr','http://node2.univ.fr');
?>
