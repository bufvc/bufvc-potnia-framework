<?php
// $Id$
// Show external links in a framed page
// Phil Hansen, 19 April 11
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

$RECORD = NULL;
$R_URL = NULL;

// check for link url on the request
$URL = @$_REQUEST['l'];
// get record url from request
if (isset($_REQUEST['r']))
    $R_URL = @$_REQUEST['r'];
// check for record url on path
else if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    $R_URL = $_SERVER['PATH_INFO'];

if (!empty($R_URL))
    {
    // qualify url with module name if not already present
    if (substr_count($R_URL, '/') < 3)
        $R_URL = '/' . $MODULE->name . $R_URL;
    // retrieve record and query
    $nav_query = QueryFactory::get_session_query($MODULE, TRUE);
    $RECORD = $nav_query->get_record($R_URL, @$_REQUEST);
    if (!is_null($RECORD))
        $QUERY = $nav_query;
    }

// if link url was not given, try to retrieve from record
if (empty($URL) && !is_null($RECORD))
    $URL = @$RECORD['location'];

// check for error
if (empty($URL) && is_null($RECORD))
    {
    set_session_message($STRINGS['error_external_link'], 'error-message');
    header("HTTP/1.1 303 See Other");
    header("Location: " . $MODULE->url('index'));
    exit();
    }

$TEMPLATE = 'link';
$TITLE = $STRINGS['link_title'] . $URL;

header('Content-Type: ' . $MODULE->content_type());
// special case, return header frame
if (@$_REQUEST['t'] == 'header')
    require_once $CONF['path_templates'] . 'inc-link_header.php';
else
    require_once $CONF['path_templates'] . $TEMPLATE . '.php';