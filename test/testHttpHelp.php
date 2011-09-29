<?php
// $Id$
// Test BUFVC help screen
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

/* This is an integration test, testing the web functionality at a high level
     - Calling the base URL gets the default help screen
     - Adding a help page name gets a specific help page
*/
class HelpTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('help');
        }

    function test_default_page()
        {
        $this->get($this->url);
        $this->assertHelpPage('Help');
        }

    function test_template_page()
        {
        $this->get($this->url . '/test');
        $this->assertHelpPage('Help on test');
        $this->assertTag('p', 'Using template help_test');
        }
    
    function test_cms_page()
        {
        $this->get($this->url . '/testcms');
        // Uses specialised template
        $this->assertHelpPage('CMS Test');
        $this->assertTag('p', 'Using CMS page');
        }

    function test_missing_help_page()
        {
        $this->get($this->url . '/notfound');
        $this->assertHelpPage('Help', 404);
        $this->assertTag('div', 'Help information not found', 'class', 'error-message');
        }

    function assertHelpPage($title, $status=200)
        {
        global $MODULE;
        $this->assertResponse($status);
        $this->assertTitle(new PatternExpectation("|{$MODULE->title}.*$title|"));
        $this->assertTag('h1', $title);
        // Trap PHP errors
        $this->assertNoPattern('/^<b>(Notice|Error|Warning|Parse error)/im');
        }
    }
