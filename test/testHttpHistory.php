<?php
// $Id$
// Test BUFVC search history screen
// James Fryer, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');
// include_once('../lib/phpQuery/phpQuery/phpQuery.php');

class HistoryTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('history');
        $this->search_url = $MODULE->url('search');
        $this->record_url = $MODULE->url('index');
        $this->marked_url = $MODULE->url('marked');
        }

    function XXX_test_get_empty_history()
        {
        global $MODULE, $STRINGS;
        $this->get($this->url);
        $this->assertPage('History');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('p', 'No history');
        }

    function test_get_search_history()
        {
        global $STRINGS;
        // Do a search
        $this->get($this->search_url . '?q=notfound');

        // It will now appear in the history
        $this->get($this->url);
        $this->assertPage('History');
        //### 20081030 JF: commented out as the design changes have broken this test
        //### $this->assertTag('ul', NULL, 'class', 'querylist');
        //### $this->assertTag('li', 'notfound');
        // Link to search results
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=notfound'));

        // Perform another search, get two entries
        $this->get($this->search_url . '?q=single');
        $this->get($this->url);
        //### $this->assertTag('ul', NULL, 'class', 'querylist');
        //### $this->assertTag('li', 'notfound');
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=notfound'));
        //### $this->assertTag('li', 'single');
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        }
        
    function test_get_record_history()
        {
        // View the first record
        $this->get($this->record_url . '/test/single' );
        
        // It will now appear in the history
        $this->get($this->url);
        $this->assertPage('History');
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/single') );

        // view another record
        $this->get($this->record_url . '/test/many000' );
        
        // it will appear at the top of the history, with the first record below it
        $this->get($this->url);
        $this->assertPage('History');
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/many000'), TRUE );        
        $this->assertTagRelativePosition('a', NULL, 'href', quotemeta($this->record_url . '/test/single'), POSITION_AFTER );
        
        // View the first record again
        $this->get($this->record_url . '/test/single' );
        
        // the first record will be first in the list
        $this->get($this->url);
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/many000'), TRUE );
        $this->assertTagRelativePosition('a', NULL, 'href', quotemeta($this->record_url . '/test/single'), POSITION_BEFORE );
        }
        
    function test_mark_records_form()
        {
        $this->get($this->record_url . '/test/many000' );

        $this->get($this->url);
        $this->assertPage('History');
        
        $this->assertTag('form', NULL, 'method', 'POST');
        $this->assertTag('form', NULL, 'action', $this->marked_url);
        
        $this->assertField('mark_results', 'Mark Selected Records'); // submit button
        $this->assertField('/dummy/test/many000', false); // the name of the checkbox is the url of the field
        }
        
    function test_mark_records_submit()
        {
        $this->get($this->record_url . '/test/many000' );
        $this->get($this->record_url . '/test/many001' );
        
        $this->get($this->url);
        $this->assertPage('History');
        
        $this->setField('/dummy/test/many000', true);
        $this->click('Mark Selected Records');
        $this->assertPage('History');
    
        // confirm the record is now marked
        $this->assertField('/dummy/test/many000', true);
        $this->assertField('/dummy/test/many001', false); // a different record is not checked
        }
        
    function test_view_marked_records()
        {
        $this->get($this->record_url . '/test/many000' );
        $this->click('Mark Record');

        $this->get($this->marked_url);
        $this->assertPage('Marked Records');

        // Results are present
        $this->assertTag('ul', NULL, 'class', 'results');
        $this->assertTag('li', 'many000');
        $this->assertLink('manymany 000');
        $this->assertField('/dummy/test/many000', true);
        }
    
    function test_unmarking_records()
        {
        $this->get($this->record_url . '/test/many000' );
        $this->assertField('mark_record', 'Mark Record'); // submit button
        $this->click('Mark Record');
        $this->get($this->record_url . '/test/many001' );
        $this->click('Mark Record');
        
        $this->get($this->url);
        $this->assertField('mark_results', 'Mark Selected Records'); // submit button
        $this->setField('/dummy/test/many000', false); // clear the checkbox
        $this->click('Mark Selected Records');
        $this->get($this->marked_url);
        $this->assertNoTag('li', 'many000'); // this record is no longer on the page
        $this->assertField('/dummy/test/many001', true); // this record is still on the page
        }

    function test_mark_all_results()
        {
        // run a search initially (to be sure that all search results are not being marked)
        $this->get($this->search_url . '?q=');
        
        $this->get($this->record_url . '/test/many000' );
        $this->get($this->record_url . '/test/many003' );
        
        // confirm both records
        $this->get($this->url);
        $this->assertField('/dummy/test/many000', false);
        $this->assertField('/dummy/test/many003', false);
        
        // confirm order of both
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/many000'), TRUE );        
        $this->assertTagRelativePosition('a', NULL, 'href', quotemeta($this->record_url . '/test/many003'), POSITION_BEFORE );
        
        $this->get($this->url);
        $this->assertField('mark_all_viewed', 'Mark All Records');
        $this->click('Mark All Records');
        
        // confirm both records are marked
        $this->assertField('/dummy/test/many000', true);
        $this->assertField('/dummy/test/many003', true);
        
        // make sure other search results are not marked (previous bug)
        $this->get($this->marked_url);
        $this->assertNoTag('li', 'many001');
        $this->assertNoTag('li', 'many002');
        }
    
    function test_saved_history()
        {
        global $USER;
        // perform a search. check its recorded in history.
        $this->get($this->search_url . '?q=single');
        $this->get($this->url);
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        
        // restart the session
        $this->restart();
        
        // et voila, search still there
        $this->get($this->url);
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        
        $this->restart();
        
        // view the page as guest
        $this->get($this->search_url . '?q=single&debug_user=guest');
        $this->get($this->url . '?debug_user=guest');
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        
        // guests do not have their searches saved
        $this->restart();
        
        $this->get($this->url . '?debug_user=guest');
        $this->assertNoTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        }
    
    function test_clear_history()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        // perform a search and confirm in history.
        $this->get($this->search_url . '?q=single');
        $this->get($this->url);
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        
        // clear the history
        $data = Array('clear_history' => 1);
        $this->post($this->url, $data);
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url);
        
        // search history is gone
        $this->get($this->url);
        $this->assertNoTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        }
    
    function test_clear_viewed_records()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        // view some records
        $this->get($this->record_url . '/test/single' );
        $this->get($this->record_url . '/test/many000' );
        // the viewed record history is populated
        $this->get($this->url);
        $this->assertPage('History');
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/single'));
        $this->assertTag('a', NULL, 'href', quotemeta($this->record_url . '/test/many000'));
        
        // clear the viewed records
        $data = Array('clear_viewed' => 1);
        $this->post($this->url, $data);
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url.'/viewed');
        
        // viewed records are gone
        $this->get($this->url);
        $this->assertNoTag('a', NULL, 'href', quotemeta($this->record_url . '/test/single'));
        $this->assertNoTag('a', NULL, 'href', quotemeta($this->record_url . '/test/many000'));
        }
    }
