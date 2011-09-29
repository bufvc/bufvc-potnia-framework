<?php
// $Id$
// Include file Invocrown demo. Used by web files to fetch config.php
// James Fryer, 27 June 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Depending on your installation, you may need to change the line below
// to contain an absolute path
$config_php = dirname(realpath(__FILE__)) . '/../etc/config.php';
if (!file_exists($config_php))
	{
    //### FIXME: should be full path
    header('Location: http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/install.php', TRUE, 302);
    exit();
    //### die("Config file not found, please install: $config_php\n");
    }
require_once($config_php);
require_once($CONF['path_src'] . 'common.inc.php');
