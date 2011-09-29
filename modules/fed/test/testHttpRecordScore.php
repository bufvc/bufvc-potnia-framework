<?php
// $Id$
// Test score records
// James Fryer, 20 Aug 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class RecordScoreHttpTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('module', '/fed/score.php');
        $this->stats = new RecordStats($MODULE->get_pear_db());
        }

    function test_default_page()
        {
        global $CONF;
        $this->setMaximumRedirects(0);
        $this->get($this->url);
        $this->assertResponse(303);
        $this->assertHeader('location', $CONF['url']);
        }

    function test_submit_score()
        {
        $this->setMaximumRedirects(0);
        $data = Array(
            'record'=>'/foo',
            'score'=>'1',
            'redirect_url'=>'http://example.com',
            );
        $this->post($this->url, $data);
        $this->assertResponse(303);
        $this->assertHeader('location', 'http://example.com');
        $stats = $this->stats->retrieve('/stats/foo');
        $this->assertEqual(1.0, $stats['score']);
        $this->assertEqual(1, $stats['score_count']);
        
        // Guests can't vote
        $this->post($this->url . '?debug_user=guest', $data);
        $this->assertResponse(303);
        $this->assertHeader('location', 'http://example.com');
        $stats = $this->stats->retrieve('/stats/foo');
        $this->assertEqual(1.0, $stats['score']);
        $this->assertEqual(1, $stats['score_count']);
        }
    }

