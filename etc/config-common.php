<?php
// $Id$
// Master configuration file for Invocrown demo
// James Fryer, 27 June 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// *** NOTE: DO NOT MAKE CONFIGURATION CHANGES IN THIS FILE! ***
// This file is for default configuration settings only.
// To change your local configuration, edit config.php

// Current engine version
$CONF['version'] = '1.1';

// If this is 0, public interfaces will be unavailable
$CONF['enabled'] = 1;

// Email address for system administrator
$CONF['admin_email'] = 'admin@invocrown.com';

// Email address for general enquiries
$CONF['contact_email'] = $CONF['admin_email'];

// Default user
$CONF['default_user'] = 'guest';

//User Agent for identifying this site
$CONF['user_agent'] = $CONF['url'].' '.$CONF['admin_email'];

// Default rights for new users
$CONF['default_user_rights'] = Array('save_data');

// Database defaults
// Database name must be set in config.php
if (!isset($CONF['db_user']))
    {
    $CONF['db_user'] = 'root';
    $CONF['db_pass'] = '';
    }
if (!isset($CONF['db_server']))
    $CONF['db_server'] = 'localhost';
if (!isset($CONF['db_database']))
    $CONF['db_database'] = '';

// Are we running from a CLI?
$CONF['console'] = @$_SERVER['DOCUMENT_ROOT'] == '';

// Set the session manager to use. Options "peardb" or "file"
//### FIXME: currently ignored
$CONF['session_manager'] = 'file';

// Path to installation
// This is a fully qualified unix path, must begin and end with slash
// The code below attempts to get the path. If this fails, set it in config.php
if (!isset($CONF['path']))
    $CONF['path'] = dirname(dirname(realpath(__FILE__))) . '/';

// Other paths
$CONF['path_src'] = $CONF['path'] . 'src/';
$CONF['path_lib'] = $CONF['path'] . 'lib/';
$CONF['path_etc'] = $CONF['path'] . 'etc/';
$CONF['path_var'] = $CONF['path'] . 'var/';
$CONF['path_sql'] = $CONF['path'] . 'sql/';
$CONF['path_web'] = $CONF['path'] . 'web/';
$CONF['path_modules'] = $CONF['path'] . 'modules/';
$CONF['path_templates'] = $CONF['path'] . 'templates/';
$CONF['path_tmp'] = $CONF['path_var'] . 'tmp/';
$CONF['path_test'] = $CONF['path'] . 'test/';

// Look for module in environment -- this overrides config.php setting
if (getenv('MODULE') != '')
    $CONF['module'] = getenv('MODULE');
    
//### Experimental
// The module "mode" controls how modules are presented to users
//    simple: The user is expected to use a single module at a time
//    nested: The first module "contains" the others. Useful for federated search
//    nested2: Test mode, as nested but one query is stored for each session 
$CONF['module_mode'] = 'simple';

// Set in common.inc.php if multiple modules detected
//### TODO: is this still needed???
$CONF['multi_module'] = FALSE;

// Modules can be aliased, e.g. '/tvandradio/trilt'=>'trilt'
$CONF['module_aliases'] = Array();

// View record navigation - 0=none, 1=top, 2=bottom, 3=both
$CONF['record_navigation_position'] = 2;

// Page sizes for search results
$CONF['search_results_page_options'] = Array(10, 50, 100);

// Always show the search form if true. Otherwise use JS to hide it on results pages.
$CONF['always_show_search_form'] = FALSE;

// Search history limit, or 0 to disable
$CONF['search_history_size'] = 50;

// Record history size, or 0 to disable
$CONF['record_history_size'] = 50;

// Saved searches limit, or 0 to disable
$CONF['saved_searches_size'] = 20;

// Marked records limit, or 0 to disable
$CONF['marked_records_size'] = 100;

// Search summary length
$CONF['summary_length'] = 200;

// Search prompt to show in search box by default
$CONF['search_prompt'] = 'all records';

// Number of records to randomly select from (i.e. first 8 results)
$CONF['results_highlight_random_sample_size'] = 8;

// If set, use JS to control forms from buttons elsewhere on the screen
// If clear, always use a submit button within its form
$CONF['submit_forms_with_script'] = 1;

// Available formats for exporting records
$CONF['export_formats'] = Array('email', 'xml', 'text', 'bibtex', 'json', 'citation', 'printer');

// The maximum number of records that can be exported, 0 means no limit
$CONF['max_export'] = $CONF['marked_records_size'];

// Number of intermediate page links to show in search results paging
$CONF['intermediate_pages_size'] = 5;

// Query results cache size
$CONF['query_cache_size'] = 1000;

// List of default query filters
$CONF['query_filters'] = Array('SpellingCorrectorFilter');

// Auto-Alerts results limit
$CONF['auto_alerts_results_size'] = 20;

// Auto-Alerts email line lengths
$CONF['auto_alerts_email_line_length'] = 65;

// Max chars that can be sent in an email note
$CONF['email_note_length'] = 100;

// Max emails sent per session
$CONF['max_emails_per_session'] = 3;

// Unprivileged users can only see back 2 weeks
$CONF['guest_cutoff_date'] = 1209600; // 2 weeks

// Default user session timeout (in seconds) - use 0 to disable timeout
$CONF['user_timeout'] = 30*60;

// Options for user timeout
//### FIXME: move strings 
$CONF['user_timeout_options'] = Array(
    '600'=>'10 minutes',
    '1800'=>'30 minutes',
    '3600'=>'1 hour',
    '7200'=>'2 hours',
    '14400'=>'4 hours',
    '0'=>'Never'
    );
    
// This string will be rendered before the body tag of all standard pages
$CONF['analytics_js'] = '';

// Default charset
$CONF['default_charset'] = 'UTF-8';

// Default locale
$CONF['locale'] = 'en_GB.utf8';

// Site URL
// Do not put a slash at the end
// Note that this must be set in config.php before this file is called
if (!isset($CONF['url']))
    die('URL not set - please configure correctly');

// What to use when creating URLs for PHP files -- usually either '.php' or ''
$CONF['php_file_ext'] = '.php';

// Example of sub-URL
$CONF['url_images'] = $CONF['url'] . '/images';

// Login URL
//### TODO: implement standard login page
$CONF['url_login'] = $CONF['url']. '/';

// External media URL
$CONF['url_media'] = $CONF['url'] . '/media';

// Prefix for View Record title
//### FIXME: move to strings file
$CONF['view_record_prefix'] = 'View Record: ';

// External service URLs
$CONF['url_resources'] = 'http://test.invocrown.com/resources';
$CONF['url_services'] = 'http://test.invocrown.com/services';
$CONF['url_playlist_service'] = $CONF['url_services'] . '/playlist';

// Do we want to use Sphinx search?
$CONF['sphinx'] = 0;
$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312;
$CONF['sphinx_max_matches'] = 1000; // See Sphinx SetLimits documentation

// Logging
$CONF['log_file'] = $CONF['path_var'] . 'log/error_log';
$CONF['log_level'] = 2;
$CONF['query_log'] = $CONF['log_file'];
$CONF['record_log'] = $CONF['log_file'];

// Debugging/testing
$CONF['debug'] = 0;
$CONF['unit_test_active'] = 0;
