<?php
// $Id$
// Tests for User class
// Phil Hansen, 25 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');

class UserTestCase
    extends UnitTestCase
    {
    function test_create_success()
        {
        $user = User::create('newuser');
        $this->assertEqual($user->login, 'newuser');
        $this->assertTrue( $user->is_registered() );
        }

    function test_create_fail()
        {
        $user = User::create(''); // empty not allowed
        $this->assertTrue(is_null($user));
        $user = User::create(NULL); // null
        $this->assertTrue(is_null($user));
        $user = User::create('new user'); // spaces not allowed
        $this->assertTrue(is_null($user));
        }

    function test_retrieve()
        {
        $user = User::retrieve('notfound'); // user does not exist
        $this->assertTrue(is_null($user));

        $user = User::retrieve('user'); // existing user
        $this->assertEqual($user->login, 'user');
        }

    function test_special_characters()
        {
        $user = User::create('https://idp.bufvc.ac.uk/shibboleth!https:/login.bufvc.ac.uk/saml/metadata!1g4smg75fekk9cjh22dq6cq0s2');
        $this->assertFalse(is_null($user));
        $this->assertEqual($user->login, 'https://idp.bufvc.ac.uk/shibboleth!https:/login.bufvc.ac.uk/saml/metadata!1g4smg75fekk9cjh22dq6cq0s2');

        $user2 = User::retrieve('https://idp.bufvc.ac.uk/shibboleth!https:/login.bufvc.ac.uk/saml/metadata!1g4smg75fekk9cjh22dq6cq0s2');
        $this->assertFalse(is_null($user2));
        }

    function test_login_ends_in_equals()
        {
        $user = User::create('foo=');
        $this->assertFalse(is_null($user));
        $user2 = User::retrieve('foo=');
        $this->assertFalse(is_null($user2));
        }

    function test_instance_guest()
        {
        global $CONF;
        $user = User::instance();
        $this->assertEqual($user->login, $CONF['default_user']);

        $user2 = User::instance();
        $this->assertEqual($user, $user2);
        }

    function test_instance_new()
        {
        $user = User::instance('newuser');
        $this->assertEqual($user->login, 'newuser');

        $user2 = User::instance('newuser');
        $this->assertEqual($user, $user2);
        }

    function test_update()
        {
        $user = User::instance('user');
        $user->email = 'test@invocrown.com';
        $user->name = 'New User';
        $user->update();

        $user2 = User::instance('user');
        $this->assertEqual($user2->email, 'test@invocrown.com');
        $this->assertEqual($user2->name, 'New User');

        $user = User::instance('guest'); // guest user cannot update
        $user->email = 'test@invocrown.com';
        $user->update();

        $user2 = User::instance('guest');
        $this->assertNotEqual($user2->email, 'test@invocrown.com');
        }

    function test_list_rights()
        {
        $user = User::instance('guest');
        $this->assertTrue(count($user->list_rights()) == 0);

        $user = User::instance('editor');
        $rights = $user->list_rights();
        $this->assertTrue(count($rights) >= 2);
        $this->assertTrue(isset($rights['save_data']));
        $this->assertFalse(is_null($user->_rights)); // test rights cache
        }

    function test_list_all_rights()
        {
        $user = User::instance('');
        $rights = $user->list_all_rights();
        $this->assertTrue(count($rights) >= 2);
        $this->assertTrue(isset($rights['save_data']));
        }

    function test_has_right()
        {
        $user = User::instance('guest');
        $this->assertFalse($user->has_right('save_data'));

        $user = User::instance('user');
        $this->assertTrue($user->has_right('save_data'));
        $this->assertFalse($user->has_right('edit_record'));

        $this->assertFalse($user->has_right('not_found')); // non-existing right
        }

    function test_set_right()
        {
        $user = User::instance('user');
        $this->assertFalse($user->has_right('edit_record'));
        $user->set_right('edit_record', TRUE);
        $this->assertTrue($user->has_right('edit_record'));
        $user->set_right('edit_record', FALSE);
        $this->assertFalse($user->has_right('edit_record'));

        $user->set_right('not_found', TRUE);
        $this->assertFalse($user->has_right('not_found'));
        }

    function test_default_rights()
        {
        $user = User::create('defaultrights');
        $this->assertTrue($user->has_right('save_data'));
        }

    function test_set_transient_rights()
        {
        $user = User::instance('user');
        $user->set_right('edit_record', TRUE, FALSE);
        $this->assertTrue($user->has_right('edit_record'));
        $rights = $user->list_rights();
        $this->assertIdentical($rights['edit_record'], 'Edit records');

        // After reload, right has gone
        $user = User::instance('user');
        $this->assertFalse($user->has_right('edit_record'));
        }

    function test_clear_transient_rights()
        {
        $user = User::instance('user');
        $user->set_right('edit_record', TRUE, FALSE);
        $user->set_right('edit_record', FALSE, FALSE);
        $this->assertFalse($user->has_right('edit_record'));

        $user = User::instance('user');
        $user->set_right('edit_record', TRUE, FALSE);
        $user->set_right('edit_record', FALSE);
        $this->assertFalse($user->has_right('edit_record'));
        }

    function test_transient_rights_can_be_any_string()
        {
        $user = User::instance('user');
        $user->set_right('foobar', TRUE, FALSE);
        $this->assertTrue($user->has_right('foobar'));
        $rights = $user->list_rights();
        $this->assertIdentical($rights['foobar'], 'foobar');
        }

    function test_permanent_overrides_transient()
        {
        $user = User::instance('user');
        $user->set_right('edit_record', TRUE, FALSE);
        $user->set_right('edit_record', TRUE);
        $this->assertTrue($user->has_right('edit_record'));
        $rights = $user->list_rights();
        $this->assertIdentical($rights['edit_record'], 'Edit records');

        // After reload, right persists
        $user = User::instance('user');
        $this->assertTrue($user->has_right('edit_record'));

        // Clean up
        $user->set_right('edit_record', FALSE);
        }

    function test_set_transient_on_guest()
        {
        $user = User::instance('guest');
        $user->set_right('edit_record', TRUE, FALSE);
        $this->assertTrue($user->has_right('edit_record'));
        }

    function test_root_flag()
        {
        $user = User::instance('user');
        $this->assertFalse($user->has_right('edit_record'));
        $user->hasRoot = true; // set flag manually
        $this->assertTrue($user->has_right('edit_record'));
        }

    function test_save_load_data()
        {
        $user = User::instance('user');
        $this->assertTrue(is_null($user->load_data('newdata')));

        $user->save_data('newdata', 'this is some new data'); // insert
        $this->assertTrue($user->load_data('newdata') == 'this is some new data');
        $user->save_data('newdata', 'chang\'ed data'); // update
        $this->assertTrue($user->load_data('newdata') == 'chang\'ed data');

        $user = User::instance('guest'); // this user is not allowed to save data
        $user->save_data('testdata', 'test data');
        $this->assertTrue(is_null($user->load_data('testdata')));
        }

    function test_guest_when_no_db()
        {
        global $CONF, $MODULE;
        // Flush modules
        Module::flush();

        // use a fake db
        $old_server = $CONF['db_server'];
        $old_database = $CONF['db_database'];
        $old_module = @$MODULE;
        $old_mod_conf = @$CONF['modules']['user'];
        $MODULE = Module::load('user'); //###
        $CONF['db_server'] = 'no_server';
        $CONF['db_database'] = 'unit_test2';
        $CONF['modules']['user'] = NULL;

        // try to retrieve some users
        $user = User::instance('user');
        $this->assertEqual($user->login, 'guest');
        $this->assertFalse( $user->is_registered() );
        $user = User::instance('guest');
        $this->assertEqual($user->login, 'guest');
        $this->assertFalse( $user->is_registered() );
        $user = User::instance('newtestuser');
        $this->assertEqual($user->login, 'guest');
        $this->assertFalse( $user->is_registered() );

        // restore the correct db for any tests that follow
        $CONF['db_server'] = $old_server;
        $CONF['db_database'] = $old_database;
        $CONF['modules']['user'] = $old_mod_conf;
        $MODULE = $old_module;
        }
    
    function test_get_timeout()
        {
        $user = User::instance('user');
        global $CONF;
        // check default
        $this->assertEqual($CONF['user_timeout'], $user->get_timeout());
        // set a prefs timeout
        $user->prefs['timeout'] = 600;
        $this->assertEqual(600, $user->get_timeout());
        }
    
    function test_clear_search_history()
        {
        global $MODULE;
        $user = User::instance('user');
        $query = QueryFactory::create($MODULE);
        // add a query
        $user->add_to_search_history($query);
        $this->assertEqual(count($user->search_history), 1);
        $user->clear_search_history();
        // queries have been cleared
        $this->assertEqual(count($user->search_history), 0);
        }
    }
