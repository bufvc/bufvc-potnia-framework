<?php
// $Id$
// Test BUFVC feeds screen
// James Fryer, 09 Jun 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class FeedsTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('feeds');
        }

    function test_default_page()
        {
        $this->get($this->url);
        $this->assertResponse(200);
        $this->assertHeader('content-type', 'application/atom+xml');
        $this->assertTag('id', $this->url);
        }
    
    function test_feed_with_path()
        {
        $this->get($this->url.'/test');
        $this->assertResponse(200);
        $this->assertHeader('content-type', 'application/atom+xml');
        $this->assertTag('summary', 'path=test');
        }
    }
