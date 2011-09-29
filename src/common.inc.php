<?php
// $Id$
// Common library file for Invocrown demo
// James Fryer, 27 June 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// --- Initialisation ---

// Report uninitialised vars
error_reporting (E_ALL ^ E_DEPRECATED);

// Is the site enabled?
if (!$CONF['enabled'])
    die('Sorry, this service is unavailable');

// Look for module in environment -- overrides config.php setting
if ($CONF['debug'] && @$_GET['debug_module'] != '')
    $CONF['module'] = $_GET['debug_module'];
else if (@$_ENV['MODULE'] != '')
    $CONF['module'] = $_ENV['MODULE'];

// Initialise the unit test framework
$CONF['unit_test_active'] = 0;
if ($CONF['debug'] && (defined('UNIT_TEST') || file_exists($CONF['path'] . '/test/UNIT_TEST_ACTIVE')))
	{
    require_once $CONF['path_src'] . 'basic/unit_test.inc.php';
    $CONF['unit_test_active'] = 1;
    }
    
// --- Includes ---

// PEAR
require_once('DB/Table.php');
require_once('MDB2.php');
require_once('MDB2/Extended.php');
require_once('MDB2/Driver/mysql.php');
require_once('Mail.php');

// Utilities
require_once $CONF['path_src'] . 'basic/util.inc.php';
require_once $CONF['path_src'] . 'basic/sphinx.inc.php';
require_once $CONF['path_src'] . 'basic/htmlutil.inc.php';
require_once $CONF['path_src'] . 'basic/dateutil.inc.php';
require_once $CONF['path_src'] . 'basic/identify_bot.inc.php';
require_once $CONF['path_src'] . 'basic/DummyMail.class.php';
require_once $CONF['path_src'] . 'basic/BaseContainer.class.php';
require_once $CONF['path_src'] . 'basic/CsvConverter.class.php';
require_once $CONF['path_src'] . 'basic/TextExtractor.class.php';
require_once $CONF['path_src'] . 'basic/QueryLogParser.class.php';
require_once $CONF['path_src'] . 'basic/SpellingCorrector.class.php';
require_once $CONF['path_src'] . 'query/Block.class.php'; //### TODO: move to util ???
if (@$CONF['url_ironduke'] != '')
    require_once $CONF['path_src'] . 'basic/ironduke.lib.php';

// String literals
require_once $CONF['path_src'] . 'strings.inc.php';
require_once $CONF['path_templates'] . 'inc-strings.php';

// Database access
require_once $CONF['path_src'] . 'basic/database.inc.php';

// Query
require_once($CONF['path_src'] . 'query/QueryCriterion.class.php');
require_once($CONF['path_src'] . 'query/QueryCriteria.class.php');

// Logical storage layer
require_once $CONF['path_src'] . 'datasource/DataSource.class.php';
require_once $CONF['path_src'] . 'datasource/DataSourceUtil.class.php';
require_once $CONF['path_src'] . 'datasource/DummyDataSource.class.php';
require_once $CONF['path_src'] . 'query/Playlist.class.php';

// Formatters
require_once $CONF['path_src'] . 'formatters/DublinCoreFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/TextFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/BibTeXFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/AtomFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/JsonFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/ICalendarFormatter.class.php';
require_once $CONF['path_src'] . 'formatters/CitationFormatter.class.php';

// Application-level objects
require_once $CONF['path_src'] . 'query/QueryFilter.class.php';
require_once $CONF['path_src'] . 'query/Query.class.php';
require_once $CONF['path_src'] . 'query/QueryList.class.php';
require_once $CONF['path_src'] . 'query/RecordList.class.php';
require_once $CONF['path_src'] . 'query/MarkedRecord.class.php';
require_once $CONF['path_src'] . 'query/Module.class.php';
require_once $CONF['path_src'] . 'query/Listings.class.php';
require_once $CONF['path_modules'] . 'user/src/User.class.php';

// --- Application ---

// Handle magic quotes
// Escapes are handled from code, strip slashes if magic quotes is enabled
if (get_magic_quotes_gpc()) // magic quotes is enabled
    {
    $_GET = stripslashes_deep($_GET);
    $_POST = stripslashes_deep($_POST);
    $_REQUEST = stripslashes_deep($_REQUEST);
    $_COOKIE = stripslashes_deep($_COOKIE);
    }

// Debug mode -- used for online tests
if (@$CONF['debug'])
    {
    // test user has been set on query string
    if (@$_GET['debug_user'] != '')
        $_SERVER['REMOTE_USER'] = $_GET['debug_user'];
    // Test user can see data before cutoff date
    //### FIXME: find a better way to do this: will break in 2024!
    if (@$_GET['debug_no_cutoff'])
        $CONF['guest_cutoff_date'] = 3600 * 24 * 365 * 15;
    }

// --- Modules ---

// Load the requested module, falling back to the dummy module
if (@$CONF['module'] == '')
    {
    $tmp_url = parse_url($CONF['url']);
    $tmp = Module::expand_alias($_SERVER['SCRIPT_NAME'], @$tmp_url['path']);
    $CONF['module'] = Module::name_from_url($tmp, @$tmp_url['path']);
    if ($CONF['unit_test_active'] == 0)
        {
        if (!in_array($CONF['module'], $CONF['allowed_modules']))
            $CONF['module'] = $CONF['allowed_modules'][0];
        //### FIXME: find a better way to select module in webservice
        if (!@$CONF['webservice_active'])
            $CONF['multi_module'] = TRUE;
        }
    // Special handling for unit tests
    //### FIXME: this is still stupid
    else {
        $CONF['multi_module'] = in_array($CONF['module'], $CONF['allowed_modules']);
        $CONF['allowed_modules'][] = 'dummy';
        }
    }
else
    $CONF['allowed_modules'] = Array($CONF['module'], 'user'); //### FIXME: user hack -- sb able to load class user without loading user module
$MODULE = Module::load($CONF['module']);
if (is_null($MODULE))
	$MODULE = new DummyModule();

// Need to load all modules in multi-module mode
//### FIXME: can we avoid this with __autoload???
if (@$CONF['multi_module'])
    {
    foreach ($CONF['allowed_modules'] AS $mod_name)
        Module::load($mod_name);
    }

// Get the unit test helpers
// (these may depend on included classes, so they are not included above)
if ($CONF['unit_test_active'])
	require_once($CONF['path_src'] . 'test/helpers.inc.php');

// --- Session handling ---

if (!$CONF['console'])
    session_start();

// Debug mode - clear session command
if (@$CONF['debug'] && @$_GET['clear_session'])
    $_SESSION = array();

// Set up database sessions
//### if ($CONF['session_manager'] == 'peardb' && empty($CONF['new_admin']))
//###     require_once $CONF['path_src'] . 'basic/SessionHandler.class.php';

// Get the user
$USER = User::instance(is_cli() ? 'guest' : @$_SERVER['REMOTE_USER']);

// Debug mode -- used for online tests -- check timeout override value
if (@$CONF['debug'] && @$_GET['debug_time'])
    $_SESSION[$USER->login.'_TIME'] = $_GET['debug_time'];

// check idle time
if ($USER->is_registered() && $USER->get_timeout() > 0)
    {
    if (isset($_SESSION[$USER->login.'_TIME']) && time() - $_SESSION[$USER->login.'_TIME'] > $USER->get_timeout())
        {
        unset($_SESSION[$USER->login.'_TIME']);
        set_session_message($STRINGS['error_user_timeout'], 'error-message');
        header("HTTP/1.1 303 See Other");
        header("Location: " . $CONF['url_login']);
        exit();
        }
    else
        $_SESSION[$USER->login.'_TIME'] = time();
    }

// Users who can't save data can't use email export
//### FIXME: this is the wrong right to use surely???
if (!$USER->has_right('save_data'))
    {
    $tmp = array_search('email', $CONF['export_formats']);
    if ($tmp !== FALSE)
        unset($CONF['export_formats'][$tmp]);
    }

// --- Global variables ---

// Main site title
$SITE_TITLE = $MODULE->title . @$CONF['title_suffix'];

// Get the current Query or create a new one
$QUERY = QueryFactory::get_session_query($MODULE);

// Marked records
$MARKED_RECORDS = isset($_SESSION['MARKED_RECORDS']) ? $_SESSION['MARKED_RECORDS'] : new MarkedRecord();

// Record History
// TODO AV - move into user
if (!isset($_SESSION['HISTORY_RECORDS']))
    $_SESSION['HISTORY_RECORDS'] = new RecordList($CONF['record_history_size']);

// Get session message if there is one
$MESSAGE = NULL;
$MESSAGE_CLASS = 'error-message'; // default class is for errors
if (isset($_SESSION['MESSAGE']))
    get_session_message($MESSAGE, $MESSAGE_CLASS); // retrieve the message

// Current record
$RECORD = NULL;

// Mailer
//### FIXME: should be set up elsewhere
if ($CONF['unit_test_active'])
    $MAILER = new DummyMail();
else
    $MAILER = Mail::factory('mail');
