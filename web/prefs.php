<?php
// $Id$
// User Preferences
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $STRINGS['prefs_title'];
$TEMPLATE = 'prefs';

// check permissions
if (!$USER->has_right('save_data'))
    {
    // set status and error message
    header("HTTP/1.0 401 Unauthorized");
    $MESSAGE = $STRINGS['error_401_prefs'];
    $MESSAGE_CLASS = 'error-message';
    }

// check for a form POST
else if (is_array($_POST) && count($_POST) > 0)
    {
    // get the form data
    $USER->email = $_POST['email'];
    $USER->name = $_POST['name'];
    $USER->prefs['page_size'] = $_POST['page_size'];
    $USER->prefs['search_mode'] = $_POST['search_mode'];
    if (isset($_POST['timeout']))
        $USER->prefs['timeout'] = $_POST['timeout'];
    else if (isset($USER->prefs['timeout']))
        unset($USER->prefs['timeout']);
    
    // pass to module for any extra handling
    $MODULE->process_prefs($_POST, $USER);
    
    // update user data
    $USER->update();

    // save preferences
    $USER->save_prefs();

    $MESSAGE = $STRINGS['prefs_save'];
    $MESSAGE_CLASS = 'info-message';
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type());
require_once $CONF['path_templates'] . $TEMPLATE . '.php';
