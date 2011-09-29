<?php
// $Id$
// Test BUFVC saved searches screen
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class SavedSearchesTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE, $STRINGS;
        $this->url = $MODULE->url('saved');
        $this->search_url = $MODULE->url('search');
        $this->title = $STRINGS['saved_title'];
        $this->save_button = $STRINGS['save_search_button'];
        }
        
    function teardown()
        {
        global $CONF;
        $user = User::instance($CONF['default_user']);
        $user->save_data('saved_searches', new QueryList());
        }

    function test_default_saved_page()
        {
        global $MODULE, $STRINGS;
        $this->get($this->url);
        $this->assertPage($this->title);
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('p', $STRINGS['no_saved_searches']);
        }

    function test_save_search_form()
        {
        $this->get($this->search_url . '?q=many');
        $this->assertTag('form', NULL, 'method', 'POST');
        $this->assertTag('form', NULL, 'action', $this->url);
        $this->assertField('save', $this->save_button); // submit button
        }

    function test_save_search()
        {
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        $this->assertPage($this->title);
        $this->assertTag('div', NULL, 'class', 'querylist');
        $this->assertTag('dd', 'many');
        // Link to search results
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=many'));

        // Perform another search, get two entries
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);
        $this->get($this->url);
        $this->assertTag('div', NULL, 'class', 'querylist');
        $this->assertTag('dd', 'many');
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=many'));
        $this->assertTag('dd', 'single');
        $this->assertTag('a', NULL, 'href', quotemeta($this->search_url . '?q=single'));
        }

    function test_delete_search()
        {
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=notfound');
        $this->click($this->save_button);

        $this->get($this->url);
        $this->assertField('delete', 'Delete'); // submit button

        // delete newest record
        // with multiple submit buttons all using the same name, simpletest 'clicks' the first button
        // the delete buttons use javascript to set a hidden field, this is done manually with simpletest
        $this->clickSubmit('Delete', array('key'=>'0'));
        $this->get($this->url);
        $this->assertNoTag('dd', 'notfound'); // this search is no longer on the page
        $this->assertTag('dd', 'many'); // this search is still on the page
        $this->assertTag('dd', 'single'); // this search is still on the page

        // delete a few more
        $this->clickSubmit('Delete', array('key'=>'0'));
        $this->clickSubmit('Delete', array('key'=>'0'));
        $this->assertPage($this->title);
        $this->assertNoTag('dd', 'single');
        $this->assertNoTag('dd', 'many');
        }

    function test_save_search_permission()
        {
        global $STRINGS;
        $this->get($this->search_url . '?q=many&debug_user=guest');
        $this->assertNoTag('form', NULL, 'action', $this->url); // no save search form

        $this->get($this->url . '?debug_user=guest');
        $this->assertPage($this->title);
        $this->assertTag('p', $STRINGS['no_saved_searches']);
        }

    function test_saved_searches_limit()
        {
        // 4 is the testing limit
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=notfound');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=filler');
        $this->click($this->save_button);
        $this->assertPage($this->title);
        $this->assertNoTag('div', '', 'class', 'error-message');

        $this->get($this->search_url . '?q=test2');
        $this->click($this->save_button);
        $this->assertPage($this->title);
        $this->assertTag('div', '', 'class', 'error-message');
        }

    function test_search_day_form()
        {
        global $CONF;
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);

        // select option is not present without an email address
        $this->get($this->url);
        $this->assertNoTag('select', NULL, 'name', 'day');
        $this->assertLink('set up your email address');

        // add an email address for the default test user
        $user = User::instance($CONF['default_user']);
        $user->email = 'test@invocrown.com';
        $user->update();
        $this->get($this->url);
        $this->assertTag('select', NULL, 'name', 'day');
        $this->assertField('update', 'Update');

        // select a day
        $this->setField('day', '2');
        $this->click('Update');
        // confirm change
        $this->assertField('day', '2');

        //
        // Test the set_day interface
        // (this is included in this test for simplicity since an email is required)
        $data = Array('set_day'=>1, 'day'=>'3');
        // post the data to the saved page
        $this->post($this->url, $data);

        // confirm the day was set
        $this->get($this->url);
        $this->assertPage($this->title);
        $this->assertField('day', '3');
        
        // Reset day
        $data = Array('set_day'=>1, 'day'=>'0');
        $this->post($this->url, $data);

        // revert email change
        $user->email = '';
        $user->update();
        }

    function test_active_list_update()
        {
        // WARNING - SimpleTest apparently cannot handle form fields named '0'
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=notfound');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=filler'); // buffer value for simpletest
        $this->click($this->save_button);

        $this->get($this->url);
        $this->assertField('update', 'Update');
        $this->assertField('1', true);
        // deactivate a search
        $this->setField('1', false);
        $this->click('Update');
        //$this->get($this->url);
        $this->assertField('1', false);

        // reactivate the search and deactivate another search
        $this->setField('1', true);
        $this->setField('2', false);
        $this->click('Update');
        $this->assertField('1', true);
        $this->assertField('2', false);
        }

    function test_active_interface()
        {
        // add some saved searches
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=single');
        $this->click($this->save_button);
        $this->get($this->search_url . '?q=notfound');
        $this->click($this->save_button);

        // confirm the saved search is active
        $this->get($this->url);
        $this->assertField('1', true);

        // post with the 'remove' flag
        $data = Array('remove_active'=>1, 'key'=>'1');
        $this->post($this->url, $data);

        // confirm the saved search was deactivated
        $this->get($this->url);
        $this->assertField('1', false);

        // post with the 'set' flag
        $data = Array('set_active'=>1, 'key'=>'1');
        $this->post($this->url, $data);

        // confirm the saved search is active again
        $this->get($this->url);
        $this->assertField('1', true);
        }
    }
