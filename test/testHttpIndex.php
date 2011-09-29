<?php
// $Id$
// Test BUFVC index screen
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

/* This is an integration test, testing the web functionality at a high level
     - Calling the base URL gets a search form
     - Adding a result ID gets a single result
     - Adding an info page name gets an info page

    Assumes the standard mock data source is available

*/

class IndexTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('index');
        }

    function test_default_page()
        {
        global $MODULE;
        $this->get($this->url);
        $this->assertPage($MODULE->title);
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'GET');
        $this->assertField('q', '');
        $this->assertTag('p', 'Using template info_default');
        
        // ensure qc help
        $this->assertTag('span', 'enter your search term here', 'id', 'q_help');
        $this->assertTag('span', 'select the date range', 'id', 'date_help');
        $this->assertTag('span', 'choose your genre', 'id', 'category_help');
        }

    function test_info_page()
        {
        $this->get($this->url . '/test');
        // Uses specialised template
        $this->assertPage('Test');
        $this->assertTag('p', 'Using template info_test');
        }

    function test_cms_page()
        {
        $this->get($this->url . '/testcms');
        // Uses specialised template
        $this->assertPage('CMS Test');
        $this->assertTag('p', 'Using CMS page');
        }

    function test_default_search()
        {
        global $MODULE;
        $this->get($this->url);
        $this->assertPage($MODULE->title);
        
        $this->setField('q', 'single');
        $this->click('Search');
        $this->assertPage('Results');
        }

    function test_view_record()
        {
        $this->get($this->url . '/test/single');
        $this->assertPage('View Record');

        // Record is present
        $this->assertTag('dl', NULL, 'class', 'row');
        $this->assertTag('dt', 'Title');
        $this->assertTag('dd', 'single');

        // Edit link
        $this->assertLink('Edit this record');
        }

    function test_view_record_no_edit_permission()
        {
        $this->get($this->url . '/test/single?debug_user=guest'); // guest user does not have edit_record permission
        $this->assertPage('View Record');
        $this->assertNoLink('Edit this record');
        }

    function test_view_record_specialised()
        {
        $this->get($this->url . '/test2/has_template');
        $this->assertPage('View Record');
        // Uses specialised template
        $this->assertTag('p', 'Using template record_test2');
        }

    function test_view_hidden_record()
        {
        global $MODULE;
        $this->get($this->url . '/test/hidden');
        $this->assertPage('View Record');
        // try to access the record as guest
        $this->get($this->url . '/test/hidden?debug_user=guest');
        $this->assertPage($MODULE->title, 404);
        $this->assertTag('div', 'Record not found', 'class', 'error-message');
        }

    function test_view_restricted_record()
        {
        global $MODULE;
        // Logged-in user can see the record
        $this->get($this->url . '/test/restricted');
        $this->assertPage('View Record');

        // try to access the record as guest
        $this->get($this->url . '/test/restricted?debug_user=guest');
        $this->assertPage($MODULE->title, 401);
        $this->assertTag('div', 'Unauthorized', 'class', 'error-message');
        // Applies to other formats too, they get the HTML error page
        $this->get($this->url . '/test/restricted.xml?debug_user=guest');
        $this->assertPage($MODULE->title, 401);
        $this->assertTag('div', 'Unauthorized', 'class', 'error-message');
        $this->assertHeaderPattern('content-type', '/text\/html/');
        }

    function test_missing_record()
        {
        global $MODULE;
        $this->get($this->url . '/test/notfound');
        $this->assertPage($MODULE->title, 404);
        $this->assertTag('div', 'Record not found', 'class', 'error-message');
        }

    function test_play_record()
        {
        $this->get($this->url . '/test/single?mode=player');
        $title = 'Playing';
        global $MODULE;
        $this->assertResponse(200);
        $this->assertTitle(new PatternExpectation("|{$MODULE->title}.*$title|"));
        $this->assertTag('h1', $MODULE->title);
        $this->assertTag('h2', $title);
        // Trap PHP errors
        $this->assertNoPattern('/^<b>(Notice|Error|Warning|Parse error)/im');
        }

    function test_view_record_playlist()
        {
        global $CONF;
        global $MODULE;
        // Format can be sent as GET var
        $this->get($this->url . '/test/single?format=xspf');
        $this->assertHeader('content-type', 'application/xspf+xml');
        $this->assertTag('title', 'single');
        $this->assertTag('location', 'file1.mp3');
        // Format can also be extension
        $this->get($this->url . '/test/single.xspf');
        $this->assertHeader('content-type', 'application/xspf+xml');
        }

    function test_view_record_playlist_no_permission()
        {
        $this->get($this->url . '/test/single?format=xspf&debug_user=guest');
        $this->assertResponse(403);
        }

    function test_view_record_xml()
        {
        global $MODULE;
        $this->get($this->url . '/test/single.xml');
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-record.xml');
        $this->assertTag('dc:title', 'single');
        $this->assertTag('dc:description', 'Test item');
        $this->assertTag('dc:identifier', '/test/single');
        // Format can be sent as GET var
        $this->get($this->url . '/test/single?format=xml');
        $this->assertHeader('content-type', 'application/xml');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-record.xml');
        }

    function test_view_record_text()
        {
        $this->get($this->url . '/test/single?format=text');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-record.txt');
        $this->assertPattern('|Title:         single|');
        $this->assertPattern('|Description:   Test item|');
        $this->assertPattern('|/test/single|');
        }

    function test_view_record_bibtex()
        {
        $this->get($this->url . '/test/single?format=bibtex');
        $this->assertHeaderPattern('content-type', '/text\/plain/');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-record.bib');
        $this->assertPattern('|title = "single"|');
        $this->assertPattern('|abstract = "Test item"|');
        $this->assertPattern('|/test/single|');
        }
    
    function test_email_format_redirect()
        {
        $this->setMaximumRedirects(0);
        global $MODULE;
        $this->get($this->url . '/test/single.email');
        $this->assertResponse(303);
        $this->assertHeader('location', $MODULE->url('email', '/index?url='.urlencode('/dummy/test/single')));
        
        $this->get($this->url . '/test/single?format=email');
        $this->assertResponse(303);
        $this->assertHeader('location', $MODULE->url('email', '/index?url='.urlencode('/dummy/test/single')));
        }
    
    function test_view_record_json()
        {
        global $MODULE;
        $this->get($this->url . '/test/single.json');
        $this->assertHeader('content-type', 'application/json');
        $this->assertHeader('content-disposition', 'attachment; filename=' . date('Ymd') . '-record.json');
        $this->assertPattern('|"title":{"label":"Title","value":"single"}|');
        }

    function test_no_module_permission()
        {
        // specify a module right on query string
        $this->get($this->url . '?module_right=some_right');
        $this->assertResponse(401);
        $this->assertTag('div', 'Access to this collection is a privilege', 'class', 'error-message');
        }
    
    function test_no_module_permission_when_exporting()
        {
        // specify a module right on query string
        $this->get($this->url . '/test/single?format=text&module_right=some_right');
        $this->assertResponse(401);
        $this->assertTag('div', 'Access to this collection is a privilege', 'class', 'error-message');
        }
    
    function test_user_timeout()
        {
        $this->setMaximumRedirects(0);
        $debug_time = time() - 21600;
        $this->get($this->url . '/test/single?debug_time='.$debug_time);
        global $CONF;
        // confirm redirect to login screen
        $this->assertResponse(303);
        $this->assertHeader('Location', $CONF['url_login']);
        
        // confirm error message displayed
        $this->setMaximumRedirects(2);
        $this->get($this->url . '/test/single?debug_time='.$debug_time);
        $this->assertTag('div', 'Your session has timed out', 'class', 'error-message');
        }
    }
