<?php
// $Id$
// Config file for Invocrown demo unit tests
// James Fryer, 27 June 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// This file is for changes to the config for unit tests -- most notably
//  database name and data path

// Database variables
$CONF['db_user'] = 'test';
$CONF['db_pass'] = '';
$CONF['db_database'] = 'unit_test';

// Marked records limit
$CONF['marked_records_size'] = 3;

// Saved searches limit
$CONF['saved_searches_size'] = 4;

// Export records download limit
$CONF['max_export'] = 3;

// Emails per session
$CONF['max_emails_per_session'] = 2;

// Email note
$CONF['email_note_length'] = 12;

// Don't use JavaScript
$CONF['submit_forms_with_script'] = 0;

// Use simple module mode
$CONF['module_mode'] = 'simple';

// Default user for testing
$CONF['default_user'] = 'editor';

// Prefix for View Record title
//### FIXME: move to strings file
$CONF['view_record_prefix'] = 'View Record: ';

// Don't want query filters
unset($CONF['query_filters']);

// Sphinx max result count param
$CONF['sphinx_max_matches'] = 56;

// Standard default user rights
$CONF['default_user_rights'] = Array('save_data');

// Login URL
$CONF['url_login'] = $CONF['url']. '/';

// Ironduke integration
$CONF['url_ironduke'] = '';

// Logging
$CONF['log_file'] = $CONF['path_var'] . 'log/unit_test.log';
$CONF['log_level'] = 100;

// Query results cache size
$CONF['query_cache_size'] = 1000;