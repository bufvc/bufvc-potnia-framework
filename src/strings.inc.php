<?php
// $Id$
// String literals
// Phil Hansen, 04 June 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


$STRINGS = Array(
    // error messages
    'error_401' => 'Access to this collection is a privilege of BUFVC membership. If you are already a BUFVC member, please <a  href="%s">log in</a>. Otherwise you may <a href="http://bufvc.ac.uk/membership">join now</a>.',
    'error_401_edit' => 'Unauthorized: You do not have permission to edit records',
    'error_401_prefs' => 'Unauthorized: You do not have permission to edit user preferences',
    'error_401_record' => 'Unauthorized: You do not have permission to view this record',
    'error_404_record' => 'Record not found',
    'error_404_help' => 'Help information not found',
    'error_404_search' => 'Page not found',
    'error_404' => 'Page not found',
    'error_500_db' => 'Database connection not initialised',
    'error_required_fields' => 'Please fill in required fields',
    'error_records_limit' => 'Too many records',
    'error_saved_searches_limit' => 'Too many auto alerts',
    'error_query_criteria' => 'Missing or invalid criteria',
    'error_query_empty' => 'No results found',
    'error_query_not_found' => 'The requested item was not found',
    'error_user_timeout' => 'Your session has timed out',
    'error_create_record' => 'Record could not be created. Please check the error logs for more information.',
    'error_update_record' => 'Record could not be updated. Please check the error logs for more information.',
    'error_delete_record' => 'Record could not be deleted. Please check the error logs for more information.',
    'error_external_link' => 'Error with external link',

    // trilt errors
    'error_date_format' => 'Incorrect date format',
    'error_time_format' => 'Incorrect time format',

    // titles
    'edit_title' => 'Edit',
    'edit_title_new' => 'New Item',
    'help_title' => 'Help',
    'history_title' => 'History',
    'marked_title' => 'Marked Records',
    'prefs_title' => 'User Preferences',
    'saved_title' => 'Auto Alerts',
    'search_title' => 'Search',
    'search_title_basic' => 'Basic Search',
    'search_title_advanced' => 'Advanced Search',
    'results_title' => 'Results',
    'link_title' => 'BUFVC External Link to ',

    // status messages
    'item_delete' => 'Item deleted',
    'item_create' => 'Item created',
    'item_save' => 'Item saved',
    'marked_update' => 'Marked records updated',
    'record_mark' => 'Record marked',
    'record_unmark' => 'Record unmarked',
    'prefs_save' => 'Preferences saved',
    'user_data_delete' => 'Saved data removed',
    'search_save' => 'Auto Alert saved',
    'search_delete' => 'Auto Alert deleted',
    'no_saved_searches' => 'No auto alerts',
    'search_set_active' => 'Auto Alert activated',
    'search_remove_active' => 'Auto Alert deactivated',
    'search_active_list_update' => 'Active list updated',
    'search_day_set' => 'Auto Alerts will be sent every %s',
    'search_day_disabled' => 'Auto Alerts disabled',
    'query_all' => 'All records',
    'listings_incomplete_data' => 'The listings data for this day may be incomplete.',

    // auto-alerts
    'auto_alert_email_results_subject' => 'Auto-Alert search results',
    'auto_alert_email_intro' => 'These are the results of your Auto Alert.',
    'auto_alert_email_too_many_results' => "The number of results returned has been limited. To see the full results, please log in to the site and perform the search.",
    'auto_alert_email_footer' => 'This is an automatically generated message sent from the Auto-Alert system.',

    // Buttons
    'save_search_button' => 'Save as Auto Alert',

    // Other prompts
    'save_search_help' => 'About auto alerts',

    // Other useful strings
    'export_formats' => Array(
        'email'=>'Email',
        'xml'=>'XML (Dublin Core)',
        'text'=>'Text',
        'bibtex'=>'BibTeX',
        'atom'=>'Atom',
        'json'=>'JavaScript (JSON)',
        'citation'=>'Citation',
        ),

    // months
    'months' => Array(
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December'
        ),
    );

/*
// To separate auto-alerts and saved searches uncomment this block
$STRINGS['saved_title'] = 'Saved Searches';
$STRINGS['search_save'] = 'Search saved';
$STRINGS['search_delete'] = 'Search deleted';
$STRINGS['no_saved_searches'] = 'No saved searches';
$STRINGS['save_search_button'] = 'Save Search';
$STRINGS['save_search_help'] = 'About saved searches';
*/

?>
