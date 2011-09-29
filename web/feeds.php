<?php
// $Id$
// Feed generator
// Phil Hansen, 8 June 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

$path = NULL;
// check for path after the url
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    $path = substr($_SERVER['PATH_INFO'], 1); // remove slash

header('Content-Type: ' . $MODULE->content_type('atom'));
print $MODULE->get_feed($path);
