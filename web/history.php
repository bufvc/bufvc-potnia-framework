<?php
// $Id$
// View Search History
// James Fryer, 5 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $STRINGS['history_title'];
$TEMPLATE = 'history';

$QUERYLIST = $USER->search_history;
$RECORDLIST = @$_SESSION['HISTORY_RECORDS'];

// check for a form POST
if (is_array($_POST) && count($_POST) > 0)
    {
    // clear search history
    if (isset($_POST['clear_history']))
        {
        $USER->clear_search_history();
        $redirect_url = $MODULE->url('history');
        }
    // clear viewed records
    if (isset($_POST['clear_viewed']))
        {
        $_SESSION['HISTORY_RECORDS'] = new RecordList($CONF['record_history_size']);
        $redirect_url = $MODULE->url('history').'/viewed';
        }
    // redirect to the history page and return status 303
    if (isset($redirect_url))
        {
        header("HTTP/1.1 303 See Other");
        header("Location: " . $redirect_url);
        exit();
        }
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type());
require_once $CONF['path_templates'] . $TEMPLATE . '.php';
