<?php
// $Id$
// Test BUFVC prefs screen
// Phil Hansen, 10 Oct 2008
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

/* Tests the web functionality of the user preferences page at a high level
     - Calling the base URL gets an edit form
     - Without proper permissions (i.e. 'save_data' right) an error message
       is displayed and 401 returned
*/

class UserPreferencesTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('prefs');
        $this->search_url = $MODULE->url('search');
        }

    function test_default_prefs_page()
        {
        global $MODULE;
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'POST');
        $this->assertField('email', '');
        $this->assertField('name', 'Editor');
        // additional module prefs template is included
        $this->assertField('test_field', '');
        }

    function test_save_prefs()
        {
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->setField('email', 'test@invocrown.com');
        $this->setField('page_size', '50');
        $this->setField('timeout', '600');
        $this->click('Save');

        // confirm changes
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->assertField('email', 'test@invocrown.com');
        $this->assertField('page_size', '50');
        $this->assertField('search_mode', 'default');
        $this->assertField('timeout', '600');

        // revert changes
        $this->setField('email', '');
        $this->setField('page_size', '10');
        $this->setField('timeout', '1800');
        $this->click('Save');
        }

    function test_prefs_permission()
        {
        // guest user does not have save_data permission
        $this->get($this->url . '?debug_user=guest');
        $this->assertPage('User Preferences', 401);
        $this->assertTag('div', 'Unauthorized', 'class', 'error-message');
        $this->assertNoTag('form'); // no save search form
        }

    function test_search_prefs()
        {
        // default search form
        $this->get($this->search_url);
        $this->assertPage('Search');
        $this->assertField('page_size', '10');

        // set the search preferences
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->setField('page_size', '50');
        $this->setField('search_mode', 'advanced');
        $this->click('Save');

        // start a new browser session
        $this->restart();

        // check search form
        $this->get($this->search_url);
        $this->assertPage('Advanced Search');
        $this->assertField('page_size', '50');

        // revert changes
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->setField('page_size', '10');
        $this->setField('search_mode', 'default');
        $this->click('Save');
        }

    function test_save_module_prefs()
        {
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->setField('test_field', 'test value');
        $this->click('Save');

        // confirm changes
        $this->get($this->url);
        $this->assertPage('User Preferences');
        $this->assertField('test_field', 'test value');
        }
    }
