<?php
// $Id$
// Trilt listings page
// Phil Hansen, 8 Jul 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Get Listings class
$classname = $MODULE->listings_class;
$listings = new $classname();
// If we have an incoming command with a channel parameter, then grab it here
// as it can conflict with the normal channel QC
// NOTE AV : the use of the name channel is not ideal - I'd rename it if I was sure
// that it wouldn't break existing interfaces
if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
    $channel_action = $_POST['channel'];
    unset( $_POST['channel'] );
    unset( $_REQUEST['channel'] );
    }

// Get the current Listings Query or create a new one
$QUERY = $MODULE->get_session_data('LISTINGS_QUERY');
if (is_null($QUERY))
    $QUERY = QueryFactory::create($MODULE, $MODULE->listings_query_config );

// a way to retain what channels may have been set in the session
if( !isset($_REQUEST['channel']) )
    $_REQUEST['channel'] = $QUERY['channel']->get_value();

$QUERY->set_criteria_values($_REQUEST);
$listings->process_criteria($QUERY);

// Set up global vars
$TITLE = $listings->title;
$TEMPLATE = 'listings';
$is_logged_in = $USER->has_right('save_data');

if( $is_logged_in && @count($USER->prefs['listings_channels']) == 0 )
    $USER->prefs['listings_channels'] = $QUERY['channel']->get_default();

// Get the output format
$output_format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'html';

// If the format is email, redirect to the 'send email' page
if ($output_format == 'email')
    {
    header("Location: " . $MODULE->url('email', '/listings?' . $QUERY->url_query($_REQUEST)), TRUE, 303);
    exit();
    }

// check for a form POST, saving channel data
if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
    // determine post type
    $action = @$_POST['action'];
    $from_ajax = @$_POST['ajax'];
    $redirect_url = @$_POST['redirect_url'];

    // add a channel
    if ($action == 'add_channel')
        {
        // $channel = $_POST['channel'];
        if ($channel_action != '')
            {
            $channels = $is_logged_in ? @$USER->prefs['listings_channels'] : $QUERY['channel']->get_value();
            if( !isset($channels[$channel_action]) )
                {
                // always a single channel ?
                $channels[$channel_action] = TRUE;
                $MESSAGE = 'Channel added';
                xlog(2, 'Added channel: ' . $channel_action, 'LISTINGS');
                }
            }
        }

    // remove a selected channel
    else if ($action == 'remove_channel')
        {
        if ($channel_action != '')
            {
            $channels = $is_logged_in ? @$USER->prefs['listings_channels'] : $QUERY['channel']->get_value();
            if( count($channels) == 0 )
                $channels = $QUERY['channel']->get_default();
            if( isset($channels[$channel_action]) )
                {
                unset($channels[$channel_action]);
                $MESSAGE = 'Channel removed';
                xlog(2, 'Removed channel: ' . $channel_action, 'LISTINGS');
                }
            }
        }

    else if ($action == 'set_channels')
        {
        $channels = $channel_action;
        if (!is_array($channels))
            {
            $channel_list = explode(',', $channels);
            $channels = Array();
            foreach($channel_list as $channel)
                $channels[$channel] = TRUE;
            }
        }
    // Save the new channel list
    if (@$channels != '')
        {
        if ($is_logged_in)
            {
            $QUERY['channel']->set_value();
            $QUERY['channel']->set_value( $channels );
            $USER->prefs['listings_channels'] = $QUERY['channel']->get_value();
            $USER->save_prefs();
            }
        else {
            $QUERY['channel']->set_value();
            $QUERY['channel']->set_value( $channels );
            $MODULE->set_session_data('LISTINGS_QUERY', $QUERY);
            if ($redirect_url == '')
                $redirect_url = $QUERY->url();
            }
        }

    // if we came from an ajax call, simply show the message and stop
    if ($from_ajax)
        {
        echo (@$MESSAGE) ? $MESSAGE : '';
        exit();
        }

    // Otherwise redirect if required
    else if ($redirect_url != '')
        {
        // redirect to the specified url and return status 303
        header("Location: " . $redirect_url, TRUE, 303);
        exit();
        }
    }

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
else if (is_null( $QUERY->search() ))
    {
    header("HTTP/1.0 400 Bad request");
    $MESSAGE = ($QUERY->error_code) ? $QUERY->error_message : $STRINGS['error_query_empty'];
    }
else {
    // check date
    if( $QUERY['date']->status == QC_STATUS_ERROR && isset($QUERY['date']->error_qs_key['date_start']) )
        $MESSAGE = $STRINGS['listings_incomplete_data'];
    //### FIXME: Add future message as well
    }

// Remember the current query

$MODULE->set_session_data('LISTINGS_QUERY', $QUERY);

//### FIXME: passing var to template, should be ALL CAPS
$results = $listings->collate_results($QUERY);

// Display page to user
header('Content-Type: ' . $MODULE->content_type($output_format));
require_once $MODULE->find_template($TEMPLATE, $output_format);
