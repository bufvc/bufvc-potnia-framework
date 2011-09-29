<?php
// $Id$
// Search a datasource
// James Fryer, 7 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $STRINGS['search_title_basic'];
$TEMPLATE = 'search';

// Config variables for new query
$query_config = Array();

// In addition to the query criteria, this script accepts a control
// variable 'mode' with the following values:
//  - new: Create a new query
//  - advanced: Turn on advanced search mode
//  - basic: Turn on basic search mode

// The session can be removed by passing query=new as query variable
$mode = @$_REQUEST['mode'];

// Check for table name on the URL
$table_name = NULL;
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    {
    $tmp = explode('/', $_SERVER['PATH_INFO']);
    $table_name = $tmp[1];
    }

// Check if it's a valid table
if ($table_name != '' && !in_array($table_name, array_keys($MODULE->list_query_tables())))
    {
    header("HTTP/1.0 404 Not found");
    $MESSAGE = $STRINGS['error_404_search'];
    }

// Create new query if required
if ($mode == 'new')
    {
    $QUERY = QueryFactory::create($MODULE, Array('table_name'=>$table_name));
    // set basic/advanced search based on user prefs for new searches
    if (isset($USER->prefs['search_mode']))
        $mode = ($USER->prefs['search_mode'] == 'advanced') ? 'advanced' : 'basic';
    else
        // explicitly set the mode to basic
        $mode = 'basic';
    $NEW_QUERY = TRUE;
    // set the new empty query in the session - this prevents old query data coming back
    QueryFactory::set_session_query($MODULE,$QUERY);
    }
// Check we have the correct query table and get the correct query if necessary
else if ($table_name != $QUERY->table_name)
    $QUERY = QueryFactory::get_session_query($MODULE, FALSE, $table_name);
// The default behaviour is to hide the search form unless there are results.
// This GET option overrides that behaviour.
$SHOW_SEARCH_FORM = @$_REQUEST['editquery'];

// Get the output format
$output_format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'html';

// Should we show advanced search page?
if ($mode == 'advanced')
    {
    $ADVANCED_SEARCH = TRUE;
    // Force display of search form
    $SHOW_SEARCH_FORM = TRUE;
    }
else if ($mode == 'basic')
    {
    $ADVANCED_SEARCH = FALSE;
    // Force display of search form
    $SHOW_SEARCH_FORM = TRUE;
    }
else if ($QUERY->has_advanced($_REQUEST))
    {
    $ADVANCED_SEARCH = TRUE;
    }
else if (isset($_SESSION['ADVANCED_SEARCH']))
    {
    $ADVANCED_SEARCH = $_SESSION['ADVANCED_SEARCH'];
    }
else if (@$USER->prefs['search_mode'] == 'advanced')
    {
    $ADVANCED_SEARCH = TRUE;
    }
else
    $ADVANCED_SEARCH = FALSE;

$_SESSION['ADVANCED_SEARCH'] = $ADVANCED_SEARCH;
if ($ADVANCED_SEARCH)
    {
    $TITLE = $STRINGS['search_title_advanced'];
    // Allow basic search parameters to be used in advanced search mode
    //### FIXME: need to look at the way "magic" parameters are handled.
    //###   e.g. allow notation on the URL such as ?q[0]=foo
    if (@$_REQUEST['q'] != ''  && $_REQUEST['q'] != $CONF['search_prompt'])
        $_REQUEST['adv_q1'] = $_REQUEST['q'];
    }
// If there are multiple search tables, include the search table in the title
$query_tables = $MODULE->list_query_tables();
if (count($query_tables) > 1)
    $TITLE .= ': ' . $query_tables[$QUERY->table_name];

// Figure out what action we are taking.
// The default is to show the search form.
// check permissions
if (!$MODULE->has_right($USER))
    {
    // set status and error message
    header("HTTP/1.0 401 Unauthorized");
    $MESSAGE = $STRINGS['error_401'];
    $login_url = $CONF['url_login'] . '?url=' . urlencode(get_current_url());
    $MESSAGE = sprintf($MESSAGE, $login_url);
    $MESSAGE_CLASS = 'error-message';
    $TEMPLATE = 'info_unauthorized';
    $output_format = 'html';
    }

// If the format is email, redirect to the 'send email' page
else if ($output_format == 'email')
    {
    unset($_REQUEST['format']);
    header("Location: " . $MODULE->url('email', '/search?' . $QUERY->url_query($_REQUEST)), TRUE, 303);
    exit();
    }

// If there are query parameters, do the search
else if ($QUERY->has_allowed_criteria($_REQUEST))
    {
    $results = $QUERY->search($_REQUEST);
    if (is_null($results))
        {
        header("HTTP/1.0 400 Bad request");
        // check for error
        $MESSAGE = ($QUERY->error_code) ? $QUERY->error_message : $STRINGS['error_query_empty'];
        }
    else {
        $TITLE = $STRINGS['results_title'];
        }
    // Remember the current query in the session
    QueryFactory::set_session_query($MODULE, $QUERY);
    // Add to search history
    $USER->add_to_search_history($QUERY);
    }

// Set up the right-hand blocks
$SIDEBAR = @$QUERY->filter_info['sidebar'];

// Display page to user
header('Content-Type: ' . $MODULE->content_type($output_format));
// look for a formatter
$formatter = $MODULE->new_formatter($output_format);
if (!is_null($formatter))
    {
    // clone the query object and run the search with a larger page size
    $large_query = clone($QUERY);
    $criteria = $_REQUEST;
    $criteria['page_size'] = $CONF['max_export'];
    $criteria['page'] = 1;
    $large_query->search($criteria);
    header('Content-Disposition: attachment; filename='.date('Ymd').'-search_results'.$formatter->file_ext);
    $result = $MODULE->format_records($large_query->results, $output_format, $CONF['max_export']);
    // For json we add in the query info array
    if ($output_format == 'json')
        $result = $formatter->add_query_info_fields($result, $large_query->info);
    print $result;
    exit;
    }
// look for a template
else
    require_once $MODULE->find_template($TEMPLATE, $output_format);
