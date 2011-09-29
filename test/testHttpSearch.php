<?php
// $Id$
// Test BUFVC web interface
// James Fryer, 7 Apr 05
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

/* This is an integration test, testing the web functionality at a high level
     - Calling the base URL gets a search form
     - Adding a query gets a list of results
     - Adding a result ID gets a single result

    Assumes the standard dummy data source is available

*/

class SearchTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('search');
        $this->record_url = $MODULE->url('index');
        }
        
    
    
        
    function test_search_form()
        {
        global $MODULE;
        $this->get($this->url);
        $this->assertPage('Basic Search');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'GET');
        $this->assertField('q', '');
        $this->assertField('page_size', '10');
        $this->assertField('sort', '');
        $this->assertFieldById('submit', 'Search');

        // Results are not present
        $this->assertNoTag('ol', '', 'class', 'results');
        }

    function test_search_no_results()
        {
        $this->get($this->url);
        $this->setField('q', 'notfound');
        $this->click('Search');
        $this->assertPage('Basic Search', 400);
        $this->assertTag('div', 'No results found', 'class', 'error-message');

        // Results are not present
        $this->assertNoTag('ol', '', 'class', 'results');
        }

    function test_search_special_characters()
        {
        $this->get($this->url);
        $this->setField('q', "not'found");
        $this->click('Search');
        $this->assertPage('Search', 400);
        }

    function test_criteria_are_preserved()
        {
        // Search with no results leaves form filled in
        $this->get($this->url);
        $this->setField('q', 'notfound');
        $this->setField('sort', 'title');
        $this->setField('page_size', '50');
        $this->click('Search');
        $this->assertField('q', 'notfound');
        $this->assertField('sort', 'title');
        $this->assertField('page_size', '50');

        // Search with results, return to form still filled in
        $this->setField('q', 'single');
        $this->click('Search');
        $this->get($this->url);
        $this->assertField('q', 'single');

        // Search with no results does not display results of prev query!
        $this->setField('q', 'notfound');
        $this->click('Search');
        $this->assertNoTag('ol', '', 'class', 'results');

        // Adding 'mode=new' clears criteria and results
        $this->setField('q', 'single'); // Some results on prev query
        $this->get($this->url, Array('mode'=>'new'));
        $this->assertField('q', '');
        $this->assertNoTag('ol', '', 'class', 'results');
        }

    // Quoted criteria were being lost
    function test_quoted_criteria_are_preserved()
        {
        // Search with no results leaves form filled in
        $this->get($this->url);
        $this->setField('q', '"notfound"');
        $this->click('Search');
        $this->assertField('q', '"notfound"');

        // Works with advanced too
        $this->get($this->url . '?mode=advanced');
        $this->setField('q[0][v]', '"notfound"');
        $this->click('Search');
        $this->assertField('q[0][v]', '"notfound"');
        }

    function test_search_results()
        {
        $this->get($this->url);
        $this->setField('q', 'many');
        $this->click('Search');
        $this->assertPage('Results');

        // No search form on results page (???)
        //### Have left for time being. Maybe we should do it google style...

        // Results are present
        $this->assertTag('div', NULL, 'class', 'paging');
        $this->assertTag('ol', NULL, 'class', 'results');
        $this->assertTag('li', 'many000');
        $this->assertLink('manymany 000');
        }

    function test_paging()
        {
        $this->get($this->url);
        $this->click('Search');
        $this->assertPattern('/Page 1/');
        $this->get($this->url . '?q=&page=2');
        $this->assertPattern('/Page 2/');
        }

    function test_view_record()
        {
        $this->get($this->url);
        $this->setField('q', 'single');
        $this->click('Search');
        $this->clickLink('single');
        $this->assertPage('Record');
        }

    function test_advanced_flag()
        {
        // Including 'mode=advanced' on URL returns advanced search
        $this->get($this->url . '?mode=advanced');

        // We have advanced search
        $this->assertPage('Advanced Search');
        $this->assertNoTag('ol', '', 'class', 'results');

        // Once Advanced is set, it stays set
        $this->get($this->url);
        $this->assertPage('Advanced Search');

        // Turn off Advanced,with 'mode=basic'
        $this->get($this->url . '?mode=basic');
        $this->assertPage('Basic Search');
        // Test in assertPage not good enough
        $this->assertNoPattern('|^<h2>.*Advanced Search.*</h2>|im');
        $this->get($this->url);
        $this->assertPage('Basic Search');
        $this->assertNoPattern('|^<h2>.*Advanced Search.*</h2>|im');
        }

    function test_advanced_criteria_sets_flag()
        {
        // Including an advanced criteria such as 'text' sets the advanced mode
        $this->get($this->url . '?text=single');

        // We have advanced search
        // $this->showSource()
        $this->assertPage('Results');
        $this->get($this->url);
        $this->assertPage('Advanced Search');
        }

    function test_advanced_search_form()
        {
        $this->get($this->url . '?mode=advanced');
        $this->assertTag('form', NULL, 'method', 'GET');
        
        $this->assertField('q[0][v]', '');
        $this->assertField('q[0][index]', '');
        $this->assertField('q[1][v]', '');
        $this->assertField('q[1][index]', '');
        $this->assertField('q[1][oper]', 'and');
        $this->assertField('q[2][v]', '');
        $this->assertField('q[2][index]', '');
        $this->assertField('text', '');
        $this->assertField('q[2][oper]', 'and');
        $this->assertFieldById('submit', 'Search');
        }

    function test_advanced_criteria_preserved()
        {
        //### NOTE AV : will fail without preceeding fields - may need to fix this weakness
        // $this->get($this->url . '?q[1][v]=foo&text=123&q[1][index]=title&q[1][oper]=not', 400);
        $this->get($this->url . '?q[0][v]=&q[0][index]=title&q[0][oper]=and&q[1][v]=foo&text=123&q[1][index]=title&q[1][oper]=not', 400);
        $this->get($this->url);
        $this->assertField('q[1][v]', 'foo');
        $this->assertField('q[1][index]', 'title');
        $this->assertField('q[1][oper]', 'not');
        $this->assertField('text', '123');
        }

    function test_basic_advanced_toggle_preserves_input()
        {
        // basic search
        $this->get($this->url . '?q=foo');
        $this->assertField('q', 'foo');
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');

        // switch to advanced search
        $this->get($this->url . '?mode=advanced');
        $this->assertField('q[0][v]', 'foo');
        // mode=advanced/basic also shows search form
        $this->assertPattern('|SHOW_SEARCH_FORM|im');

        $this->get($this->url . '?adv_q1=bar');
        $this->assertField('q[0][v]', 'bar');
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');

        // switch to basic search and check input
        $this->get($this->url . '?mode=basic');
        $this->assertField('q', 'bar');
        $this->assertPattern('|SHOW_SEARCH_FORM|im');
        }

    function test_basic_search_works_in_advanced_mode()
        {
        // Go into advanced mode
        $this->get($this->url . '?mode=advanced');

        // Do a basic-mode search
        $this->get($this->url . '?q=foo');

        // Basic query field is copied to advanced field
        $this->assertField('q[0][v]', 'foo');
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');
        }
        
    function test_basic_search_works_in_advanced_mode_redux()
        {
        // Do a basic-mode search
        $this->get($this->url . '?q=foo');
        
        // Go into advanced mode
        $this->get($this->url . '?mode=advanced');

        // Basic query field is copied to advanced field - but only in the first item
        $this->assertField('q[0][v]', 'foo');
        $this->assertField('q[1][v]', '');
        $this->assertField('q[2][v]', '');
        }


    function test_basic_to_advanced_search_switch()
        {
        // start an advanced search
        $this->get($this->url . '?q[0][v]=smash&q[0][index]=');
        // switch to a basic search
        $this->get($this->url . '?mode=basic&editquery=1');
        // perform a basic query
        $this->setField('q', "mash");
        $this->click('Search');
        // ensure we are still in basic search mode
        $this->assertPage('Basic Search', 400);
        }
        
    function test_basic_to_advanced()
        {
        // start with a basic search
        $this->get($this->url . '?q=smash');
        // switch to advanced search
        $this->get($this->url . '?mode=advanced');
        // do another search
        $this->get($this->url . '?adv_q1=mash');
        // start a new search - this should move to basic mode
        $this->get($this->url . '?mode=new');
        // assert we are in basic mode
        $this->assertPage('Basic Search');
        // Test in assertPage not good enough
        $this->assertNoPattern('|^<h2>.*Advanced Search.*</h2>|im');
        // the search box should be blank
        $this->assertField('q', '');
        // switch to advanced search
        $this->get($this->url . '?mode=advanced');
        // query field should be blank
        $this->assertField('q[0][v]', '');
        $this->assertField('q[1][v]', '');
        $this->assertField('q[2][v]', '');
        }

    
    // Passing editquery=1 on the URL forces the search form to be displayed, even if there
    // are results and the always_show_search_form config option is FALSE (default)
    function test_force_search_form_display()
        {
        $this->get($this->url);
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');
        $this->get($this->url . '?q=foo');
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');
        $this->get($this->url . '?q=foo&editquery=1');
        $this->assertPattern('|SHOW_SEARCH_FORM|im');
        $this->get($this->url . '?q=foo&editquery=0');
        $this->assertNoPattern('|SHOW_SEARCH_FORM|im');
        }

    function test_email_format_redirect()
        {
        $this->setMaximumRedirects(0);
        global $MODULE;
        $this->get($this->url . '?q=many&format=email');
        $this->assertResponse(303);
        $this->assertHeader('location', $MODULE->url('email', '/search?q=many'));
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
        $this->get($this->url . '?q=many&format=text&module_right=some_right');
        $this->assertResponse(401);
        $this->assertTag('div', 'Access to this collection is a privilege', 'class', 'error-message');
        }

    function test_search_alt_table_new()
        {
        $this->get($this->url . '/test2?mode=new');
        $this->assertPage('Basic Search: Test 2');
        // We are looking at the correct page
        $this->assertTag('p', 'table:test2');
        }
        
    function test_search_alt_table()
        {
        $this->get($this->url . '/test2');
        $this->assertPage('Basic Search: Test 2');
        // We are looking at the correct page
        $this->assertTag('p', 'table:test2');
        }

    function test_search_alt_table_preserved()
        {
        // First get the default table in the session
        $this->get($this->url . '/test');
        $this->setField('q', 'notfound');
        $this->click('Search');
        $this->assertField('q', 'notfound');
        
        // The session doesn't leak to the alternate table
        $this->get($this->url . '/test2');
        $this->assertField('q');
        // We are looking at the correct page
        $this->assertTag('p', 'table:test2');
        
        // We can set the queries independently
        $this->setField('q', '2notfound2');
        $this->click('Search');
        $this->assertField('q', '2notfound2');

        // Both queries are stored in the session
        $this->get($this->url . '/test');
        $this->assertField('q', 'notfound');
        $this->get($this->url . '/test2');
        $this->assertField('q', '2notfound2');

        // Default table doesn't need to be specified
        $this->get($this->url);
        $this->assertField('q', 'notfound');
        }

    function test_alt_table_notfound()
        {
        $this->get($this->url . '/notfound');
        $this->assertPage('Basic Search', 404);
        $this->assertTag('div', 'Page not found', 'class', 'error-message');
        }
    }
