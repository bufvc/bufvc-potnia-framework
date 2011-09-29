<?php
// $Id$
// Test edit screen
// Phil Hansen, 5 Sep 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

/* This is an integration test, testing the web functionality at a high level
     - Calling the base URL gets a list of tables - mutable tables are actively linked
     - Calling a table URL gets a blank "new item" form
     - Calling an item URL gets a filled-in form

    Assumes the standard mock data source is available

*/

class EditTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('edit');
        }

    // test default edit screen
    function test_edit_table_list()
        {
        $this->get($this->url);
        $this->assertPage('Edit');
        $this->assertTag('ul', '', 'class', 'results');
        $this->assertLink('Test');
        $this->assertLink('Test 2');
        }

    function test_new_item_form()
        {
        global $MODULE;
        $this->get($this->url);
        $this->click('Test');
        $this->assertPage('New Item');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'POST');
        $this->assertField('slug', '');
        $this->assertField('title', '');
        $this->assertField('description', '');
        $this->assertFieldById('submit', 'Create');
        }

    // We simulate creation failure by trying to create a record on a non-mutable
    // table (test3) stored in memory.
    // This is enough to test the error feedback given to the user
    function test_create_fail()
        {
        $this->get($this->url . '/test3');
        $this->setField('slug', 'failtest');
        $this->setField('title', 'test title');
        $this->setField('description', 'test description');
        $this->click('Create');
        $this->assertResponse(400);
        $this->assertTag('div', '', 'class', 'error-message');
        }
    
    function test_create_fail_required_fields()
        {
        $this->get($this->url);
        $this->click('Test');
        $this->click('Create');
        $this->assertPage('New Item', 400);
        $this->assertTag('div', '', 'class', 'error-message');
        }

    function test_create_success()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->url . '/test');
        $this->setField('slug', 'testslug');
        $this->setField('title', 'test title');
        $this->setField('description', 'test description');
        $this->click('Create');

        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url . '/test/testslug');
        }

    function test_edit_notfound()
        {
        $this->get($this->url . '/test/notfound'); // item link
        $this->assertPage('Edit', 404);
        $this->assertTag('div', 'Record not found', 'class', 'error-message');

        $this->get($this->url . '/test4'); // table link
        $this->assertPage('Edit', 404);
        $this->assertTag('div', 'Record not found', 'class', 'error-message');
        }

    function test_edit_invalid()
        {
        $this->get($this->url . '/test/single');
        $this->assertPage('Edit');
        $this->assertField('title', 'single');
        $this->assertField('description', 'Test item');
        $this->setField('title', '');
        $this->assertFieldById('submit', 'Save');
        $this->click('Save');
        $this->assertPage('Edit', 400);
        $this->assertTag('div', '', 'class', 'error-message');
        $this->assertField('title', '');
        $this->assertField('description', 'Test item');
        }

    function test_edit()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->url . '/test/single');
        $this->assertPage('Edit');
        $this->assertField('title', 'single');
        $this->assertField('description', 'Test item');

        // modify the data
        $this->setField('title', 'single update');
        $this->setField('description', 'Test item update');
        $this->click('Save');

        // check response
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url . '/test/single');

        // confirm changes
        $this->get($this->url . '/test/single');
        $this->assertPage('Edit');
        $this->assertField('title', 'single update');
        $this->assertField('description', 'Test item update');
        $this->assertTag('div', '', 'class', 'info-message');

        // return the data to the original state
        $this->setField('title', 'single');
        $this->setField('description', 'Test item');
        $this->click('Save');
        }

    function test_delete_success()
        {
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->url . '/test');
        $this->setField('slug', 'testdelete');
        $this->setField('title', 'test title');
        $this->setField('description', 'test description');
        $this->click('Create');

        $this->get($this->url . '/test/testdelete'); // get the new item
        $this->click('Delete');

        // check response
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url);

        // make sure the item was removed
        $this->get($this->url . '/test/testdelete');
        $this->assertPage('Edit', 404);
        $this->assertTag('div', 'Record not found', 'class', 'error-message');
        }

    function test_edit_templates()
        {
        // uses specialised new item template
        $this->get($this->url . '/test2');
        $this->assertPage('New Item');
        $this->assertTag('p', 'Using template edit_item_test2');

        // uses specialised edit template
        $this->get($this->url . '/test2/has_template');
        $this->assertPage('Edit');
        $this->assertTag('p', 'Using template edit_item_test2');
        }

    function test_edit_special_characters()
        {
        // create
        $this->get($this->url . '/test');
        $this->setField('slug', 'testspecial');
        $this->setField('title', 'apostrophe\'s "quotes"');
        $this->setField('description', 'special ><br /> &;25');
        $this->click('Create');

        // confirm changes
        $this->assertPage('Edit');
        $this->assertField('title', "apostrophe\xE2\x80\x99s \"quotes\"");
        $this->assertField('description', 'special ><br /> &;25');

        // update
        $this->setField('title', 'apostrophe\'\'s ""quotes""');
        $this->setField('description', 'special ><br /> &&123;;');
        $this->click('Save');

        // confirm changes
        $this->assertPage('Edit');
        $this->assertField('title', "apostrophe\xE2\x80\x99's \"\"quotes\"\"");
        $this->assertField('description', 'special ><br /> &&123;;');
        }

    function test_info_messages()
        {
        // For this test we want to follow the redirects so we can check the session messages
        $this->get($this->url . '/test');
        $this->setField('slug', 'testmessage');
        $this->setField('title', 'test title');
        $this->setField('description', 'test description');
        $this->click('Create'); // create
        $this->assertTag('div', 'created', 'class', 'info-message'); // created message

        $this->click('Save'); // update
        $this->assertTag('div', 'saved', 'class', 'info-message'); // saved message

        $this->click('Delete'); // delete
        $this->assertTag('div', 'deleted', 'class', 'info-message'); // deleted message
        }
    
    function test_module_processing()
        {
        // The dummy module has code to change the title
        // 'Title before saving' into 'Title after saving'
        $this->get($this->url . '/test');
        $this->setField('slug', 'testmodule');
        $this->setField('title', 'Title before saving');
        $this->click('Create');

        // confirm changes
        $this->assertPage('Edit');
        $this->assertField('title', 'Title after saving');

        // update
        $this->click('Save');
        // confirm no change
        $this->assertPage('Edit');
        $this->assertField('title', 'Title after saving');
        }
    
    function test_redirect_flag()
        {
        // The dummy module has code to set the flag when 'title' => 'redirect'
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->url . '/test');
        $this->setField('slug', 'testmodule');
        $this->setField('title', 'redirect');
        $this->click('Create');
        
        // check response
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url);
        }
    
    function test_append_url_flag()
        {
        // The dummy module has code to set the flag when 'title' => 'append_url'
        // it should append '?foo=bar' to the end
        $this->setMaximumRedirects(0); // don't follow redirects on this test
        $this->get($this->url . '/test');
        $this->setField('slug', 'testappend');
        $this->setField('title', 'append_url');
        $this->click('Create');

        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url . '/test/testappend?foo=bar');
        }
    
    function test_module_error()
        {
        // The dummy module has code to set the error when 'title' => 'error'
        $this->get($this->url . '/test');
        $this->setField('slug', 'testmodule');
        $this->setField('title', 'error');
        $this->click('Create');
        $this->assertPage('New Item', 400);
        $this->assertTag('div', '', 'class', 'error-message');
        }
    
    function test_module_final_processing()
        {
        // The dummy module has code to set the append_url flag
        // in the finish_edit_process function
        $this->setMaximumRedirects(0); // don't follow redirects on this test       
        $this->get($this->url . '/test');
        $this->setField('slug', 'testfinish_edit');
        $this->setField('title', 'finish_edit');
        $this->click('Create');

        $this->assertResponse(303);
        $this->assertHeader('Location', $this->url . '/test/testfinish_edit?foo=bar');
        }
    
    function test_module_final_processing_error()
        {
        // The dummy module has code to set the error when 'title'=>'finish_error'
        $this->get($this->url . '/test');
        $this->setField('slug', 'testfinish_error');
        $this->setField('title', 'finish_error');
        $this->click('Create');
        $this->assertPage('New Item', 400);
        $this->assertTag('div', 'error while finishing', 'class', 'error-message');
        }

    function test_edit_permission()
        {
        $this->get($this->url . '?debug_user=guest'); // guest user does not have edit_record permission
        $this->assertPage('Edit', 401);
        $this->assertTag('div', 'Unauthorized', 'class', 'error-message');
        $this->assertNoLink('Test');
        }

    function test_no_module_permission()
        {
        // specify a module right on query string
        $this->get($this->url . '?module_right=some_right');
        $this->assertResponse(401);
        $this->assertTag('div', 'Unauthorized', 'class', 'error-message');
        }
    }
