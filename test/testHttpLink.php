<?php
// $Id$
// Test BUFVC link screen
// Phil Hansen, 27 April 11
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class LinkTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('link');
        $this->search_url = $MODULE->url('search');
        }

    function test_default_page()
        {
        $this->setMaximumRedirects(0);
        global $STRINGS, $MODULE;
        $this->get($this->url);
        // confirm redirect
        $this->assertResponse(303);
        $this->assertHeader('location', $MODULE->url('index'));
        
        // confirm error message displayed
        $this->setMaximumRedirects(1);
        $this->get($this->url);
        $this->assertTag('div', $STRINGS['error_external_link'], 'class', 'error-message');
        }
    
    function test_link()
        {
        $this->get($this->url . '/test/single?l=test_url');
        // title is set
        $this->assertTag('div', 'single');
        // test url is used
        $this->assertPattern('|a href="test_url"|');
        }
    
    function test_link_with_search_results()
        {
        $this->get($this->search_url);
        $this->click('Search');
        $this->get($this->url . '/test/many001');
        // title is set
        $this->assertTag('div', 'manymany 001');
        // next/prev links are present
        $this->assertTag('div', 'paging');
        $this->assertPattern('|/test/many000|');
        $this->assertPattern('|/test/many002|');
        }
    }
