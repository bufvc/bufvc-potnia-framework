<?php
// $Id$
// Webservice interface (search only at present)
// James Fryer, 7 Jan 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$CONF['webservice_active'] = TRUE;

include './include.php';

// Default output format is JSON
$format_defs = Array(
    'json'=>Array(
        'content_type'=>'application/json',
        'format_fn'=>'json_encode',
        ),
    'php'=>Array(
        'content_type'=>'text/plain',
        'format_fn'=>'serialize',
        ),
    );

// Default is error
$response_code = 400;
$response = NULL;

if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '')
    {
    $table = $_SERVER['PATH_INFO'];
    $table = str_replace('/search', '', $table);
    $query_string = @$_GET['query'];
    $offset = @$_GET['offset'];
    if ($offset == '')
        $offset = 0;
    $max_count = @$_GET['max_count'];
    if ($max_count == '')
        $max_count = 1000;
    $format = @$_GET['format'];
    $ds = $MODULE->get_datasource();
    $response = $ds->search($table, $query_string, $offset, $max_count);    

    // Output
    if ($ds->error_code)
        $response_code = $ds->error_code;
    else
        $response_code = 200;
    }

// Get format details
if (!array_key_exists(@$format, $format_defs))
    $format = 'json';
extract($format_defs[$format]);

// Output headers and content   
header("HTTP/1.0 $response_code");
header("content-type: $content_type");
if (!is_null($response))
    print $format_fn($response);
