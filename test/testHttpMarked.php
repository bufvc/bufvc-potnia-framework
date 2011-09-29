<?php
// $Id$
// Test BUFVC marked records screen
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class MarkedRecordsTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        $module = Module::load('dummy');
        $this->url = $module->url('marked');
        $this->search_url = $module->url('search');
        $this->record_url = $module->url('index');
        }

    function test_default_marked_page()
        {
        global $MODULE;
        $this->get($this->url);
        $this->assertPage('Marked Records');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('p', 'No marked records');
        }

    function test_mark_records_form()
        {
        $this->get($this->search_url . '?q=many');
        $this->assertTag('form', NULL, 'method', 'POST');
        $this->assertTag('form', NULL, 'action', $this->url);
        $this->assertField('mark_results', 'Mark Selected Records'); // submit button
        $this->assertField('/dummy/test/many000', false); // the name of the checkbox is the url of the field
        }

    function test_mark_records_submit()
        {
        $this->get($this->search_url . '?q=many');
        $this->setField('/test/many000', true);
        $this->click('Mark Selected Records');
        $this->assertPage('Results');

        // confirm the record is now marked
        $this->get($this->search_url . '?q=many');
        $this->assertField('/dummy/test/many000', true);
        $this->assertField('/dummy/test/many001', false); // a different record is not checked
        }

    function test_view_marked_records()
        {
        $this->get($this->search_url . '?q=many');
        $this->setField('/dummy/test/many000', true);
        $this->click('Mark Selected Records');

        $this->get($this->url);
        $this->assertPage('Marked Records');

        // Results are present
        //### FIXME: test summary is present
        //### if records are marked from view recd page there may not be a summary
        $this->assertTag('ul', NULL, 'class', 'results');
        $this->assertTag('li', 'many000');
        $this->assertLink('manymany 000');
        $this->assertField('/dummy/test/many000', true);
        }

    function test_unmarking_records()
        {
        $this->get($this->search_url . '?q=many');
        $this->setField('/dummy/test/many000', true);
        $this->setField('/dummy/test/many001', true);
        $this->click('Mark Selected Records');

        $this->get($this->url);
        $this->assertField('update', 'Mark Selected Records'); // submit button
        $this->setField('/dummy/test/many000', false); // clear the checkbox
        $this->click('Mark Selected Records');

        $this->get($this->url);
        $this->assertNoTag('li', 'many000'); // this record is no longer on the page
        $this->assertField('/dummy/test/many001', true); // this record is still on the page
        }

    function test_unmarking_records_from_results()
        {
        $this->get($this->search_url . '?q=many');
        $this->setField('/dummy/test/many000', true); // mark 3 records
        $this->setField('/dummy/test/many001', true);
        $this->setField('/dummy/test/many002', true);
        $this->click('Mark Selected Records');

        $this->get($this->search_url . '?q=many');
        $this->setField('/dummy/test/many001', false); // unmark 1 record
        $this->click('Mark Selected Records');

        $this->get($this->search_url . '?q=many');
        $this->assertField('/dummy/test/many000', true);
        $this->assertField('/dummy/test/many001', false);
        $this->assertField('/dummy/test/many002', true);
        }

    function test_mark_all_results()
        {
        $this->get($this->search_url . '?q=single');
        $this->assertField('mark_all_results', 'Mark All Records');
        $this->click('Mark All Records');
        // confirm the record is now marked
        $this->get($this->search_url . '?q=single');
        $this->assertField('/dummy/test/single', true);
        // another search
        $this->get($this->search_url . '?q=many 003');
        $this->click('Mark All Records');
        // confirm both records
        $this->get($this->url);
        $this->assertField('/dummy/test/single', true);
        $this->assertField('/dummy/test/many003', true);
        // try a search with too many results, submit button not present
        $this->get($this->search_url . '?q=many');
        $this->assertNoPattern('|input type="submit" name="mark_all_results"|');
        }

    function test_toggle_mark_on_record_screen()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->record_url . '/test/single');
        $this->assertField('mark_record', 'Mark Record'); // submit button
        $this->click('Mark Record');

        // confirm the redirect
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->record_url . '/test/single');

        $this->get($this->record_url . '/test/single');
        $this->assertField('unmark_record', 'Unmark Record'); // unmark submit button
        $this->click('Unmark Record'); // unmark the record

        // confirm record is unmarked
        $this->get($this->record_url . '/test/single');
        $this->assertField('mark_record', 'Mark Record'); // submit button
        }

    function test_marked_result_is_marked_when_viewed()
        {
        $this->get($this->search_url . '?q=many');
        $this->setField('/dummy/test/many001', true); // mark this record
        $this->click('Mark Selected Records');

        $this->get($this->record_url . '/test/many001'); // get the record
        $this->assertField('unmark_record', 'Unmark Record'); // unmark submit button
        }

    function test_marked_records_limit()
        {
        // 3 is the testing limit
        $this->mark_some_records(4);

        $this->assertPage('Results');
        $this->assertNoTag('div', '', 'class', 'error-message');

        $this->get($this->record_url . '/test/many003');
        $this->click('Mark Record');
        $this->assertTag('div', '', 'class', 'error-message');
        }

    function test_mark_record_interface()
        {
        $data = Array('mark_record' => 1, 'url' => '/dummy/test/single');
        $this->post($this->url, $data); // post the data to the marked page

        // confirm the record was marked
        $this->get($this->url);
        $this->assertPage('Marked Records');
        $this->assertLink('single');
        $this->assertField('/dummy/test/single', true);

        $data = Array('unmark_record' => 1, 'url' => '/dummy/test/single');
        $this->post($this->url, $data);

        // confirm the record was unmarked
        $this->get($this->url);
        $this->assertPage('Marked Records');
        $this->assertNoLink('single');
        }
    
    function test_mark_record_interface_ajax()
        {
        $data = Array('mark_record' => 1, 'url' => '/dummy/test/single', 'ajax' => 1);
        $result = $this->post($this->url, $data);
        // a JSON encoded string is returned
        $this->assertTrue(preg_match('/"message":"Record marked"/', $result));
        $this->assertTrue(preg_match('/"title":"single"/', $result));
        
        $data = Array('unmark_record' => 1, 'url' => '/dummy/test/single', 'ajax' => 1);
        $result = $this->post($this->url, $data);
        $this->assertTrue(preg_match('/"message":"Record unmarked"/', $result));
        $this->assertTrue(preg_match('/"title":"single"/', $result));
        
        // if no record exists an empty status message is returned
        $data = Array('unmark_record' => 1, 'url' => '/dummy/test/single', 'ajax' => 1);
        $result = $this->post($this->url, $data);
        $this->assertTrue(preg_match('/"message":""/', $result));
        // no title field present
        $this->assertFalse(preg_match('/"title"/', $result));
        }

    function test_unmark_all_records()
        {
        // mark some records
        $this->post($this->url, Array('mark_record'=>1, 'url'=>'/dummy/test/single'));
        $this->post($this->url, Array('mark_record'=>1, 'url'=>'/dummy/test/many001'));
        $this->post($this->url, Array('mark_record'=>1, 'url'=>'/dummy/test/many002'));
        $this->get($this->url);
        $this->assertPage('Marked Records');
        $this->assertLink('single');
        $this->assertLink('manymany 001');

        $this->click('Unmark All Records');
        $this->assertTag('p', 'No marked records');
        }

    function test_view_marked_records_xml()
        {
        $this->mark_some_records();

        $this->get($this->url . '?format=xml');
        global $MODULE;
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-marked_records.xml');

        // confirm two records present
        $this->assertTag('dc:title', 'many 000');
        $this->assertTag('dc:description', 'Test item 000');
        $this->assertTag('dc:identifier', '/test/many000');
        $this->assertTag('dc:title', 'many 001');
        $this->assertTag('dc:description', 'Test item 001');
        $this->assertTag('dc:identifier', '/test/many001');
        }

    function test_view_marked_records_xml_limit()
        {
        // 3 is the testing limit
        $this->mark_some_records(4);

        $this->get($this->url . '?format=xml');
        global $MODULE;
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-marked_records.xml');
        $this->assertTag('dc:title', 'many 000');
        $this->assertTag('dc:title', 'many 001');
        $this->assertTag('dc:title', 'many 002');
        $this->assertNoTag('dc:title', 'many 003');
        }

    function test_view_marked_records_text()
        {
        $this->mark_some_records();

        $this->get($this->url . '?format=text');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-marked_records.txt');

        // confirm two records present
        $this->assertPattern('|Title:         manymany 000|');
        $this->assertPattern('|Description:   Test item 000|');
        $this->assertPattern('|Title:         manymany 001|');
        $this->assertPattern('|Description:   Test item 001|');
        }

    function test_view_marked_records_bibtex()
        {
        $this->mark_some_records();

        $this->get($this->url . '?format=bibtex');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-marked_records.bib');

        // confirm two records present
        $this->assertPattern('|title = "manymany 000"|');
        $this->assertPattern('|abstract = "Test item 000"|');
        $this->assertPattern('|title = "manymany 001"|');
        $this->assertPattern('|abstract = "Test item 001"|');
        }

    function test_email_format_redirect()
        {
        $this->setMaximumRedirects(0);
        $this->mark_some_records();

        global $MODULE;
        $this->get($this->url . '?format=email');
        $this->assertResponse(303);
        $this->assertHeader('location', $MODULE->url('email', '/marked'));
        }
    
    function test_view_marked_records_json()
        {
        $this->mark_some_records();

        $this->get($this->url . '?format=json');
        $this->assertHeader('content-type', 'application/json');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-marked_records.json');

        // confirm two records present
        $this->assertPattern('|"title":{"label":"Title","value":"manymany 000"}|');
        $this->assertPattern('|"description":{"label":"Description","value":"Test item 000"}|');
        $this->assertPattern('|"title":{"label":"Title","value":"manymany 001"}|');
        }

    function mark_some_records($count=4)
        {
        $this->get($this->search_url . '?q=many');
        for ($i = 0; $i < $count; $i++)
            $this->setField(sprintf('/dummy/test/many%03d', $i), true);
        $this->click('Mark Selected Records');
        }
    }
