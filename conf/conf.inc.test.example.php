<?php

include_once('conf/conf.inc.php');

// Test/dev entry point configuration. Keep this file generic and copy it to
// conf/conf.inc.test.php for local/private values.
$DEV_MOD=true;
$PATH_CAS_CONFIG='conf/cas-test.inc.php';
$LOG_FILENAME = "logs/" . date("Y-m-d") . "-test.log";

?>
