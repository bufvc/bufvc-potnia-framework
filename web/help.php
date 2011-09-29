<?php
// $Id$
// Help window
// James Fryer, 28 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

$TITLE = $STRINGS['help_title'];
$RETURN_URL = $MODULE->url('search');
$TEMPLATE = 'help_default';

// Figure out what action we are taking.
// The default is to show the default help screen.

// If there is a path after the URL, check for a help page template
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    {
    // look for help page
    if (substr_count($_SERVER['PATH_INFO'], '/') == 1)
        {
        $page = substr($_SERVER['PATH_INFO'], 1); // remove slash

        // Look for a CMS page first
        $CMS = $MODULE->get_cms_page($page);
        if (!is_null($CMS))
            $TEMPLATE = 'help_cms_page';
        // check for the template
        //### FIXME: calls find_template twice, any way to avoid this???
        else if ($MODULE->find_template('help_' . $page) != '')
            $TEMPLATE = 'help_' . $page;
        else {
            // No such help page: return 404 status
            header("HTTP/1.0 404 Not found");
            $MESSAGE = $STRINGS['error_404_help'];
            }
        }
    }

require_once $MODULE->find_template($TEMPLATE);
