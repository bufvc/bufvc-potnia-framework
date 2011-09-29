<?php
// $Id$
// Test export formatters
// James Fryer, 1 Dec 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

//### TODO: move other formatter tests here (e.g. marked records)

class SearchFormattersTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('search');
        }

    function test_search_results_xml()
        {
        global $MODULE;
        $this->get($this->url . '?q=many&format=xml');
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-search_results.xml');
        // confirm two records present
        $this->assertTag('dc:title', 'many 000');
        $this->assertTag('dc:description', 'Test item 000');
        $this->assertTag('dc:identifier', '/test/many000');
        $this->assertTag('dc:title', 'many 001');
        $this->assertTag('dc:description', 'Test item 001');
        $this->assertTag('dc:identifier', '/test/many001');
        }

    function test_xml_results_limit()
        {
        // 3 is the testing limit
        // include the page size limit of 2 for testing
        global $MODULE;
        $this->get($this->url . '?q=many&page_size=3&format=xml');
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-search_results.xml');
        $this->assertTag('dc:title', 'many 000');
        $this->assertTag('dc:title', 'many 001');
        $this->assertTag('dc:title', 'many 002');
        $this->assertNoTag('dc:title', 'many 003');
        }

    function test_search_results_text()
        {
        $this->get($this->url . '?q=many&format=text');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-search_results.txt');
        // confirm two records present
        $this->assertPattern('|Title:         manymany 000|');
        $this->assertPattern('|Description:   Test item 000|');
        $this->assertPattern('|Title:         manymany 001|');
        $this->assertPattern('|Description:   Test item 001|');
        }

    function test_search_results_bibtex()
        {
        $this->get($this->url . '?q=many&format=bibtex');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-search_results.bib');
        // confirm two records present
        $this->assertPattern('|title = "manymany 000"|');
        $this->assertPattern('|abstract = "Test item 000"|');
        $this->assertPattern('|title = "manymany 001"|');
        $this->assertPattern('|abstract = "Test item 001"|');
        }

    function test_search_results_json()
        {
        $this->get($this->url . '?q=many&format=json');
        $this->assertHeader('content-type', 'application/json');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-search_results.json');
        // confirm two records present
        $this->assertPattern('|manymany 000|');
        $this->assertPattern('|Test item 000|');
        $this->assertPattern('|manymany 001|');
        $this->assertPattern('|Test item 001|');
        // confirm query info array present
        $this->assertPattern('|"info":|');
        }
    }
