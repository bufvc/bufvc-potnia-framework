<?php
print "<?php\n";
?>
// Configuration for BUFVC
// Generated by install script on <?=$date?>

$CONF['module'] = '<?=$conf['module']?>';

// URL
// No terminating slash
$CONF['url'] = "<?=$conf['url']?>";

// Include the main configuration
require_once dirname(realpath(__FILE__)) . '/config-common.php';

// *** Add your configuration below here ***

// Database variables
$CONF['db_user'] = '<?=$conf['db_user']?>';
$CONF['db_pass'] = '<?=$conf['db_pass']?>';
$CONF['db_database'] = '<?=$conf['db_database']?>';
$CONF['db_server'] = '<?=$conf['db_server']?>';
// Appended to database name
$CONF['db_wart'] = '<?=$conf['db_wart']?>';
$CONF['modules']['<?=$conf['module']?>']['db_database'] = '<?=$conf['db_database']?>';

// Navigation mode
//### TODO $CONF['module_mode'] = 'nested';

// Debugging
$CONF['debug'] = 1;
