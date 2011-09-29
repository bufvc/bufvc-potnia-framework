<?php
// $Id$
// Index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $MODULE->title;
$TEMPLATE = 'info_default';
$RECORD = NULL;

// Get the output format
$output_format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'html';

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

// Figure out what action we are taking.
// The default is to show the info_default template
// If there is a path after the URL,
// check for an info page template, otherwise display a record
else if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    {
    // look for info page
    if (substr_count($_SERVER['PATH_INFO'], '/') == 1)
        {
        $page = substr($_SERVER['PATH_INFO'], 1); // remove slash

        // Look for a CMS page first
        $CMS = $MODULE->get_cms_page($page);
        if (!is_null($CMS))
            $TEMPLATE = 'cms_page';

        // Next look for the module template
        if ($MODULE->find_template('info_' . $page))
            $TEMPLATE = 'info_' . $page;
        }

    // no info page was specified, try record
    if ($TEMPLATE == 'info_default')
        {
        // get the mode (empty or 'player')
        $mode = @$_REQUEST['mode'];
        // Look for format in '.ext'
        $tmp = explode('.', $_SERVER['PATH_INFO'], 2);
        // Qualify URL with module name
        $url = '/' . $MODULE->name . $tmp[0];
        if (isset($tmp[1]))
            $output_format = $tmp[1];
        //### old code $RECORD = $QUERY->get_record($url, @$_REQUEST);
        //### temp hack
        $nav_query = QueryFactory::get_session_query($MODULE, TRUE);
        $RECORD = $nav_query->get_record($url, @$_REQUEST);
        if (!is_null($RECORD))
            $QUERY = $nav_query;
        //### temp hack ends

        if (is_null($RECORD))
            {
            // No such record: return 404 status, display search page
            header("HTTP/1.0 404 Not found");
            $MESSAGE = $STRINGS['error_404_record'];
            }
        // If this is a hidden record, the user must have the edit_record right
        else if (@$RECORD['hidden'] && !$MODULE->can_edit($USER))
            {
            header("HTTP/1.0 404 Not found");
            $MESSAGE = $STRINGS['error_404_record'];
            }
        // If this is a restricted record, the user must be logged in
        else if (@$RECORD['restricted'] && !$USER->has_right('save_data'))
            {
            header("HTTP/1.0 401 Unauthorized");
            $MESSAGE = $STRINGS['error_401_record'];
            $output_format = 'html';
            }
        // check if this is the media player
        else if ($mode == 'player')
            $TEMPLATE = 'player';
        // Load the record template
        else {
            $TEMPLATE = 'record';
            $TITLE = $CONF['view_record_prefix'] . @$RECORD['title'];

            // check permissions
            if ($MODULE->can_edit($USER)) // user can edit records
                $URL_EDIT = $MODULE->url_edit($RECORD, @$_REQUEST);
            $SIDEBAR = @$RECORD['sidebar'];
            }
        }
    if( !is_null($RECORD) )
        {
        $_SESSION['HISTORY_RECORDS']->add( $RECORD );
        xlog( 1, $RECORD['url'], 'VIEW-RECORD', $CONF['record_log']); 
        }
    }

// If the format is email, redirect to the 'send email' page
if ($output_format == 'email')
    {
    $req = @$_REQUEST;
    $req['url'] = $RECORD['url'];
    unset($req['format']);
    unset($req['PHPSESSID']);
    $req = http_build_query($req);
    header("Location: " . $MODULE->url('email', '/index?'.$req), TRUE, 303);
    exit();
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type($output_format));
// look for a formatter
$formatter = $MODULE->new_formatter($output_format);
if (!is_null($formatter))
    {
    header('Content-Disposition: attachment; filename='.date('Ymd').'-record'.$formatter->file_ext);
    print $MODULE->format_records(Array($RECORD), $output_format, 1);
    exit;
    }
// look for template
else
    require_once $MODULE->find_template($TEMPLATE, $output_format);
