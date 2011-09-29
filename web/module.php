<?php
// $Id$
// Dispatch module calls to their correct files. Good for tests -- better to use Apache alias in production.
// James Fryer, 21 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/** Using this script you can dispatch web calls to files in a module's web directory.
        E.g. these calls:
            http://example.com/modname/module.php/test.php
            http://example.com/module.php/modname/test.php
        will dispatch to the file:
            module_path/web/test.php
        This is mostly for test and ease of installation. A production server would use
        Apache alias to achieve the same thing.
        
        If the file isn't found, a 404 error is returned.
*/
// NOTE AV : added by me to patch my lack of nginx sk1llz
if( isset($_SERVER['ORIG_PATH_INFO']) )
    $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
// Look for module in path info (unit tests put it here)
$path_info = @$_SERVER['PATH_INFO'];
$tmp = explode('/', $path_info);
// PATH_INFO has a starting slash, so the array has an empty element at start
array_shift($tmp);
// Handle the two possibilities: implicit module, php file starts path
if (@substr_compare($tmp[0], '.php', -4, 4) == 0)
    $path_info = @$_SERVER['PATH_INFO'];
// Module specified in PATH_INFO, php file is next in path
else {
    $_ENV['MODULE'] = $tmp[0];
    array_shift($tmp);
    $path_info = '/' . join('/', $tmp);
    }

// Special case when previous fails
if( $path_info == '' )
    {
    $tmp = explode('/', $_SERVER['REQUEST_URI'] );
    while( $tmp[0] != 'module.php' )
        array_shift($tmp);
    array_shift($tmp);
    $path_info = '/' . join('/', $tmp);
    }

include './include.php';

// Work out what file we really want
$filename = $MODULE->path . 'web' . $path_info;
$found = is_file($filename);
xlog(4, 'Dispatch file: ' . @$_SERVER['PATH_INFO'] . ' -> ' . $filename . ' (' . ($found ? 'found' : 'not found') . ')', 'MODULE');
// Not found - 404
if (!$found)
    {
    $TITLE = 'Module dispatch';
    header("HTTP/1.0 404 Not found");
    $MESSAGE = $STRINGS['error_404'];
    require_once $CONF['path_templates'] . 'error.php';
    exit();
    }
    
// Dispatch to the file    
require_once $filename;
