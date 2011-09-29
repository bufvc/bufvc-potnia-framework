<?php
// $Id$
// Test BUFVC web service interface
// James Fryer, 7 Jan 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class WebserviceTestCase
    extends ImprovedWebTestCase
    {
    var $json_format;
    
    function setup()
        {
        global $MODULE;
        $this->json_format = TRUE;
        $this->url = $MODULE->url('ws');
        }
    
    function search($table, $query_string=NULL, $offset=NULL, $max_count=NULL)
        {
        $url = $this->url . "/search$table";
        if (!is_null($query_string))
            $url .= "?query=$query_string";
        if (!is_null($offset))
            $url .= "&offset=$offset";
        if (!is_null($max_count))
            $url .= "&max_count=$max_count";
        if (!$this->json_format)
            $url .= "&format=php";            
        $this->get($url);
        return $this->response();
        }
        
    function response()
        {
        $content = $this->getContent();
        if ($this->json_format)
            return json_decode($content, TRUE);
        else
            return unserialize($content);
        }
        
    function test_search_errors()
        {
        // Invalid count/offset
        $r = $this->search('/test', '', 0, -1);
        $this->assertError(400);
        $r = $this->search('/test', '', -1, 10);
        $this->assertError(400);

        // No such table
        $r = $this->search('/foo', 'bar', 0, 1000);
        $this->assertError(404);
        }

    function test_search_no_results()
        {
        $this->search('/test', 'notfound');
        $this->assertResponse(400);
        $this->assertHeader('content-type', 'application/json');
        $this->assertResults(0, 0, 0);
        }

    function test_search_single()
        {
        $r = $this->search('/test', 'single', 0, 10);
        $this->assertNoError();
        $this->assertResults(0, 1, 1);
        $this->assertEqual($r['data'][0]['url'], '/test/single');
        $this->assertEqual($r['data'][0]['title'], 'single');
        $this->assertEqual($r['data'][0]['summary'], 'Test item');
        }

    function test_search_single_php_format()
        {
        $this->json_format = FALSE;
        $this->test_search_single();
        $this->assertHeader('content-type', 'text/plain');
        }

    function test_search_many()
        {
        $r = $this->search('/test', 'manymany', 0, 10);
        $this->assertNoError();
        $this->assertResults(0, 10, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many000');

        // Get a second page (diff size)
        $r = $this->search('test', 'manymany', 10, 12);
        $this->assertNoError();
        $this->assertResults(10, 12, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many010');

        // Get more records than are available
        $r = $this->search('/test', 'manymany', 20, 10);
        $this->assertNoError();
        $this->assertResults(20, 5, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many020');
        }

    function test_search_count()
        {
        // Search for 0 returns count only
        $r = $this->search('/test', 'manymany', 0, 0);
        $this->assertNoError();
        $this->assertResults(0, 0, 25);
        }

    function test_search_all()
        {
        $r = $this->search('/test');
        $this->assertNoError();
        $this->assertResults(0, 28, 28);
        }

    function assertNoError()
        {
        $this->assertResponse(200);
        //### $r = $this->response();
        //### $this->assertTrue($r['error_code'] == 0, 'error_code: ' . $r['error_code']);
        //### $this->assertTrue($r['error_message'] == '', 'error_message: ' . $r['error_message']);
        }

    function assertError($status)
        {
        $this->assertResponse($status);
        $r = $this->response();
        $this->assertTrue(is_null($r), 'Expected null error return');
        $this->assertEqual('', $this->getContent());
        //### $r = $this->response();
        //### $this->assertEqual($r['error_code'], $status, 'error_code: %s');
        //### $this->assertTrue($r['error_message'] != '', 'missing error_message');
        }

    function assertResults($offset, $count, $total, $accuracy='exact')
        {
        $r = $this->response();
        $this->assertEqual($r['count'], $count, 'count: %s');
        $this->assertEqual(count($r['data']), $r['count'], 'inconsistent count: %s');
        $this->assertEqual($r['offset'],  $offset, 'offset: %s');
        $this->assertEqual($r['total'], $total, 'total: %s');
        $this->assertEqual($r['accuracy'], $accuracy, 'accuracy: %s');
        }

    }
