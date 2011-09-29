<?php
// $Id$
// Test TRILT listings screen
// Phil Hansen, 09 Jul 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class HttpListingsTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        // $module = Module::load('dummy');
        $this->url = $MODULE->url('listings');
        }

    function test_search_form()
        {
        global $MODULE;
        $this->get($this->url.'?date=2009-06-12');
        $this->assertPage('Multi Channel Listings');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'GET');
        $this->assertFieldById('submit', 'View');
        }
    
    function test_search_no_results()
        {
        $this->get($this->url.'?date=2009-06-01');
        $this->assertPage('Multi Channel Listings', 400);
        $this->assertTag('div', 'No results found', 'class', 'error-message');
    
        // Results are not present
        $this->assertNoTag('table', '', 'class', 'listings');
        $this->assertNoTag('ul', '', 'class', 'paging');
        }
    
    function test_search_results()
        {
        $this->get($this->url.'?date=2009-06-12&time=0');
        $this->assertTag('table', '', 'class', 'listings');
        $this->assertTag('ul', '', 'class', 'paging');
        $this->assertTag('th', 'BBC1 London');
        $this->assertLink('Test Programme');
    
        // highlighted program
        $this->assertTag('dd', '', 'class', 'highlighted');
        }
    
    function test_search_results_grid_style()
        {
        $this->get($this->url.'?date=2009-06-12&time=8&style=grid');
        $this->assertTag('table', '', 'class', 'listings');
        $this->assertTag('ul', '', 'class', 'paging');
        $this->assertTag('th', 'BBC1 London');
        $this->assertLink('Test Programme');
    
        // highlighted program
        $this->assertTag('dd', '', 'class', 'highlighted');
        }
    
    function test_search_results_before_incomplete_date()
        {
        // this test is written with the incomplete date being '2001-09-07'
        // a message is displayed for days before the incomplete date
        global $STRINGS;
        $this->get($this->url.'?date=2000-06-12');
        $this->assertPage('Multi Channel Listings');
        $this->assertTag('div', $STRINGS['listings_incomplete_data'], 'class', 'error-message');
        }
    
    // test programs that reside on the time 'border'
    // e.g. a search for 8am should show a program that broadcasts 7:30-8:30am
    function test_border_results()
        {
        $this->get($this->url.'?date=2009-06-12&time=10&style=grid');
        $this->assertLink('< Test Programme2');
    
        // 12am border, grid style
        $this->get($this->url.'?date=2009-06-12&time=21&style=grid');
        $this->assertLink('Test Programme2 >');
    
        // 12am border, list style
        $this->get($this->url.'?date=2009-06-12&time=0');
        $this->assertLink('Test Programme2 >');
        }
    
    function test_paging()
        {
        $this->get($this->url.'?date=2009-06-12');
        $this->assertTag('a', 'previous day');
        $this->assertTag('a', 'next day');
        }
    
    function test_default_criteria()
        {
        $this->get($this->url);
        $this->assertField('date[0]', date('Y') );
        $this->assertField('date[1]', date('m') );
        $this->assertField('date[2]', date('d') );
        $this->assertField('time', date('G'));
        $this->assertField('style', 'list');
        $this->assertField('view_grid', 'Switch to Grid View');
        // TODO: add channel
        }
    
    function test_criteria()
        {
        $this->get($this->url.'?date=2009-06-12&style=grid&time=10');
        $this->assertField('date[0]', '2009' );
        $this->assertField('date[1]', '06' );
        $this->assertField('date[2]', '12' );
        $this->assertField('time', 10);
        $this->assertField('style', 'grid');
        $this->assertField('view_list', 'Switch to List View');
        }
    
    function test_time_criteria()
        {
        $this->get($this->url.'?date=2009-06-12&style=grid&time=14');
        $this->assertPage('Multi Channel Listings');
        // this broadcasts airs at 8am
        $this->assertNoLink('Test Programme');
    
        // whole day, list style
        $this->get($this->url.'?date=2009-06-12&time=0');
        $this->assertPage('Multi Channel Listings');
        $this->assertLink('Test Programme');
        }
    
    function test_channel_defaults()
        {
        $this->get($this->url.'?date=2009-06-12');
        // verify some defaults
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'Five');
        }
    
    function test_set_channels()
        {
        // NOTE AV : a lot of fun was had getting the channel operation to work with the
        // new query impl because the channel QS parameter now has a dual purpose.
        // It firstly acts as a standard QC parameter - with defaults, and able to persist
        // choices across requests and if required sessions.
        // The second use is demonstrated below, as an argument to the action command. To
        // get around this, channel is removed from the request parameters early on in listings.php, however
        // the best solution would be to rename the action argument form to something else. 
        // post with a list of channels to set
        $data = Array('action'=>'set_channels', 'channel'=>Array(54=>1,68=>1));
        $this->post($this->url, $data);
        $this->get($this->url.'?date=2009-06-12');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'BBC2 London');
        $this->assertNoTag('th', 'Five');
        }
    
    function test_set_channels_with_commas()
        {
        // post with a list of comma separated channels
        $data = Array('action'=>'set_channels', 'channel'=>'54,68');
        $this->post($this->url, $data);
        
        // confirm the channels were set
        $this->get($this->url.'?date=2009-06-12');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'BBC2 London');
        $this->assertNoTag('th', 'Five');
        }
    
    function test_set_channels_guest()
        {
        $this->setMaximumRedirects(0);
        // post with a list of channels to set
        $data = Array('action'=>'set_channels', 'channel'=>Array(54=>1,68=>1));
        $this->post($this->url . '?debug_user=guest&debug_no_cutoff=1', $data);

        // Redirect
        $this->assertResponse(303);
        $this->get($this->url . '?date=2009-06-12&debug_user=guest&debug_no_cutoff=1');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'BBC2 London');
        $this->assertNoTag('th', 'Five');
        
        // Commas
        $data = Array('action'=>'set_channels', 'channel'=>'54,1');
        $this->post($this->url . '?debug_user=guest&debug_no_cutoff=1', $data);
        $this->get($this->url . '?date=2009-06-12&debug_user=guest&debug_no_cutoff=1');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'Test');
        $this->assertNoTag('th', 'BBC2 London');
        }
    
    function test_add_channel()
        {
        // post with add flag
        $data = Array('action'=>'add_channel', 'channel'=>1); //=Test
        $this->post($this->url, $data);
        
        // confirm the channel was added
        $this->get($this->url.'?date=2009-06-12');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'Test');
        }
    
    function test_add_channel_guest()
        {
        $this->setMaximumRedirects(0);
        // Check default channels. Note need for debug_no_cutoff param
        //### FIXME: remove debug_no_cutoff
        $this->get($this->url.'?date=2009-06-12&debug_user=guest&debug_no_cutoff=1');
        $this->assertTag('th', 'BBC1 London');
        $this->assertNoTag('th', 'Test');
        // post with add flag
        $data = Array('action'=>'add_channel', 'channel'=>1); //=Test
        $this->post($this->url . '?debug_user=guest&debug_no_cutoff=1', $data);
        // Redirect
        $this->assertResponse(303);
        // confirm the channel was added
        $this->get($this->url.'?date=2009-06-12&debug_user=guest&debug_no_cutoff=1');
        $this->assertTag('th', 'BBC1 London');
        $this->assertTag('th', 'Test');
        }

    function test_remove_channel()
        {
        // post with remove flag
        $data = Array('action'=>'remove_channel', 'channel'=>138);
        $this->post($this->url, $data);
        
        // confirm the channel was removed
        $this->get($this->url . '?date=2009-06-12');
        $this->assertTag('th', 'BBC1 London');
        $this->assertNoTag('th', 'Five');
        }

    function test_remove_channel_guest()
        {
        $this->setMaximumRedirects(0);
        // post with remove flag
        $data = Array('action'=>'remove_channel', 'channel'=>138);
        $this->post($this->url . '?debug_user=guest&debug_no_cutoff=1', $data);
        // Redirect
        $this->assertResponse(303);
        // confirm the channel was removed
        $this->get($this->url.'?date=2009-06-12&debug_user=guest&debug_no_cutoff=1');
        $this->assertTag('th', 'BBC1 London');
        $this->assertNoTag('th', 'Five');
        }

    function test_action_redirect()
        {
        $this->setMaximumRedirects(0);
        // Adding 'redirect_url' gives us a redirect after the POST
        $data = Array('action'=>'add_channel', 'channel'=>1,
            'redirect_url'=>'http://example.com');
        $this->post($this->url, $data);
        $this->assertResponse(303);
        $this->assertHeader('Location', 'http://example.com');

        // Ignored if AJAX request
        $data['ajax'] = 1;
        $this->post($this->url, $data);
        $this->assertResponse(200);

        // Unrecognised commands are ignored
        //### TODO???
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
        $this->get($this->url.'?date=2009-06-12&time=0&module_right=some_right');
        $this->assertResponse(401);
        $this->assertTag('div', 'Access to this collection is a privilege', 'class', 'error-message');
        }
    }
