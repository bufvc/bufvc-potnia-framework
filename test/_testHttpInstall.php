<?php
// $Id$
// Test BUFVC installer
// James Fryer, June 10 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// $ICONF=installation config
// Assumes sql/unit_test_install.sql is run
$ICONF['mysql'] = Array('user'=>'test', 'host'=>'localhost', 'database'=>'unit_test', 'password'=>'', );
$ICONF['demo_module'] = 'hermes';

// Get base URL from command line
if ($argc < 2)
   exit("Please provide the base URL for web/ directory.\n");
$ICONF['url'] = $argv[1];

define('UNIT_TEST', 1);
if (!file_exists('lib/simpletest/unit_tester.php'))
   exit("Can't find lib/simpletest: run this script from the base project dir.\n");
require_once 'lib/simpletest/unit_tester.php';
require_once 'lib/simpletest/web_tester.php';
require_once 'src/test/helpers.inc.php';
// require_once 'lib/simpletest/reporter.php';
// require_once 'lib/simpletest/mock_objects.php';

if (config_exists())
   exit("This installation has an existing config.php -- tests not run.\n");
if (!is_writable('etc/'))
   exit("Can't write to etc/ -- tests not run.\n");
   
// Does config.php exist?
function config_exists()
    {
    return file_exists('etc/config.php');
    }

class InstallTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $ICONF;
        $this->url = $ICONF['url'];
        }

    function assertPage($title, $status=200, $module=NULL)
        {
        $this->assertResponse($status);
        $this->assertTitle($title);
        $this->assertTag('h1', $title);
        // Trap PHP errors
        $this->assertNoPattern('/^<b>(Notice|Error|Warning|Parse error)/im');
        // Trap installer errors
        $this->assertNoPattern('/^E[0-9][0-9]/im');
        }

    function test_redirect()
        {
        $this->setMaximumRedirects(0);
        $this->get($this->url . '/index.php');
        $this->assertResponse(302);
        $this->assertHeader('Location', $this->url . '/install.php');
        }

    function test_get_install_form()
        {
        $this->get($this->url . '/install.php');
        $this->assertPage('Install the BUFVC Potnia Framework');
        $this->assertField('sitename', '');
        $this->assertField('db_server', 'localhost');
        $this->assertField('db_database', '');
        $this->assertField('db_user', '');
        $this->assertField('db_pass', '');
        }

    function test_wont_overwrite_config()
        {
        touch('etc/config.php');
        $this->get($this->url . '/install.php');
        $this->assertPattern('/^E01/im');
        unlink('etc/config.php');
        }

    function test_wont_overwrite_db()
        {
        global $ICONF;
        $this->create_test_db();
        $this->get($this->url . '/install.php');
        $this->setField('sitename', 'Unit Test generated site');
        $this->setField('db_server', $ICONF['mysql']['host']);
        $this->setField('db_database', $ICONF['mysql']['database']);
        $this->setField('db_user', $ICONF['mysql']['user']);
        $this->setField('db_pass', $ICONF['mysql']['password']);
        $this->clickSubmit('Install');
        $this->assertPattern('/^E03/im');
        $this->drop_test_db();
        }

    function test_submit_install_form()
        {
        $this->drop_test_db();
        global $ICONF;
        $this->get($this->url . '/install.php');
        $this->setField('sitename', 'Unit Test generated site');
        $this->setField('db_server', $ICONF['mysql']['host']);
        $this->setField('db_database', $ICONF['mysql']['database']);
        $this->setField('db_user', $ICONF['mysql']['user']);
        $this->setField('db_pass', $ICONF['mysql']['password']);

        $this->clickSubmit('Install');
        $this->assertPage('Thanks');
        
        // Config file is correct
        $this->assertTrue(config_exists(), 'Config file created');
        $CONF = Array();
        include 'etc/config.php';
        $this->assertEqual($ICONF['url'], $CONF['url']);
        $this->assertEqual($ICONF['demo_module'], $CONF['module']);
        $this->assertEqual($ICONF['mysql']['user'], $CONF['db_user']);
        $this->assertEqual($ICONF['mysql']['password'], $CONF['db_pass']);
        $this->assertEqual($ICONF['mysql']['database'], $CONF['db_database']);
        $this->assertEqual($ICONF['mysql']['host'], $CONF['db_server']);
        //### TODO: should not need this
        $this->assertEqual($CONF['modules'][$ICONF['demo_module']]['db_database'], $CONF['db_database']);
        //### TODO: Add sidebar conf
        $this->assertEqual('', $CONF['db_wart']);
        $this->assertTrue($CONF['debug']);
        
        // Database has been created and populated
        $this->assertTrue($this->test_db_exists());
        
        // Clean up
        unlink('etc/config.php');
        $this->drop_test_db();
        }

    function drop_test_db()
        {
        global $ICONF;
        mysql_connect($ICONF['mysql']['host'], $ICONF['mysql']['user'], $ICONF['mysql']['password']);
        mysql_query('DROP DATABASE ' . $ICONF['mysql']['database']);
        mysql_close();
        }

    function create_test_db()
        {
        global $ICONF;
        mysql_connect($ICONF['mysql']['host'], $ICONF['mysql']['user'], $ICONF['mysql']['password']);
        mysql_query('CREATE DATABASE ' . $ICONF['mysql']['database']);
        mysql_close();
        }

    function test_db_exists()
        {
        global $ICONF;
        mysql_connect($ICONF['mysql']['host'], $ICONF['mysql']['user'], $ICONF['mysql']['password']);
        $result = mysql_select_db($ICONF['mysql']['database']);
        mysql_close();
        return $result;
        }
    }

$test = new InstallTestCase();
$test->run(new TextReporter());
