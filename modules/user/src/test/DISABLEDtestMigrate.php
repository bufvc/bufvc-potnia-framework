<?php // $Id$
// Migrate classic Athens TRILT accounts to OpenAthensSP
// James Fryer, 23 July 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');
require_once('../UserMigrator.class.php');

// Needs sql file: modules/user/test/migration_user_unit_test.sql
// this conflicts with other tests so I have disabled this file for now

class MigratorTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->migr = new UserMigrator();
        }

    function test_migrate_error()
        {
        $status = $this->migr->migrate();
        $this->assertTrue($status == 999);
        $this->assertTrue($this->migr->status == 999);
        $this->assertTrue($this->migr->status_message != '');
        }

    function test_get_users()
        {
        // No users found
        $users = $this->migr->get_users('notfound@example.com');
        $this->assertTrue(is_null($users));
        // One user found
        $users = $this->migr->get_users('unique_entry@example.com');
        $this->assertTrue(is_array($users));
        $this->assertTrue(count($users) == 1);
        $this->assertTrue($users[0]['id'] == 100);
        // Multiple users
        $users = $this->migr->get_users('too_many_accounts@example.com');
        $this->assertTrue(count($users) == 3);
        // Users are sorted by ID
        $this->assertTrue($users[0]['id'] == 101);
        $this->assertTrue($users[1]['id'] == 102);
        }

    function test_get_user_rights()
        {
        // 101, no rights
        $rights = $this->migr->get_user_rights(101);
        $this->assertIdentical($rights, Array());

        // 122, editor
        $rights = $this->migr->get_user_rights(122);
        $this->assertEqual($rights, Array('edit_record'));
        }

    function test_get_user_data()
        {
        // 101, no data
        $data = $this->migr->get_user_data(101);
        $this->assertIdentical($data, Array());

        // 110, some data
        $data = $this->migr->get_user_data(110);
        $this->assertEqual($data, Array('test'=>'foo'));
        }

    function test_email_not_found()
        {
        $status = $this->migr->setup('not_found@example.com');
        $this->assertTrue($status == 901);
        $this->assertTrue($this->migr->email == 'not_found@example.com');
        $this->assertTrue($this->migr->status == 901);
        $this->assertTrue($this->migr->status_message != '');
        }

    function test_new_acct_not_found()
        {
        $status = $this->migr->setup('unique_entry@example.com');
        $this->assertTrue($status == 902);
        // Data for old user has been copied
        $this->assertTrue($this->migr->old_user['id'] == 100);
        $this->assertTrue($this->migr->old_user['login'] == 'test2');
        }

    function test_multiple_accts()
        {
        $status = $this->migr->setup('too_many_accounts@example.com');
        $this->assertTrue($status == 903);
        }

    function test_normal_user()
        {
        $status = $this->migr->setup('normal@example.com');
        $this->assertTrue($status == 1);
        // Old user data is there
        $this->assertTrue($this->migr->old_user['id'] == 110);
        $this->assertIdentical($this->migr->get_user_data(110), Array('test'=>'foo'));
        // New user data has been set up
        $this->assertTrue($this->migr->new_user['id'] == 111);
        // Data is migrated
        $status = $this->migr->migrate();
        $this->assertEqual($status, 1);
        $users = $this->migr->get_users('normal@example.com');
        $u = $users[1];
        $this->assertTrue($u['id'] == 111);
        $this->assertTrue($u['institution_id'] == 123);
        $this->assertIdentical($this->migr->get_user_data(111), Array('test'=>'foo'));

        // Old account has been flagged as migrated
        $u = $users[0];
        $this->assertTrue($u['id'] == 110);
        $this->assertTrue($u['name'] == 'MIGRATED');
        $this->assertIdentical($this->migr->get_user_data(110), Array());
        }

    function test_admin_migration()
        {
        $status = $this->migr->setup('admin@example.com');
        $this->assertTrue($status == 2);

        // Data is migrated
        $status = $this->migr->migrate();
        $this->assertTrue($status == 2);
        $users = $this->migr->get_users('admin@example.com');
        $u = $users[1];
        $this->assertTrue($u['id'] == 121);
        $this->assertTrue($u['institution_id'] == 456);
        $this->assertTrue($u['root']);
        $this->assertIdentical($this->migr->get_user_data(111), Array('test'=>'foo'));

        // Editors and offair admins also get status 2
        $status = $this->migr->setup('editor@example.com');
        $this->assertTrue($status == 2);
        $status = $this->migr->setup('offairadmin@example.com');
        $this->assertTrue($status == 2);
        }

    function test_offair_rep_migration()
        {
        $status = $this->migr->setup('offairrep@example.com');
        $this->assertTrue($status == 3);

        // Migrate
        $status = $this->migr->migrate();
        $this->assertTrue($status == 3);
        $users = $this->migr->get_users('offairrep@example.com');
        $u = $users[1];
        $this->assertTrue($u['id'] == 131);
        $this->assertTrue($u['institution_id'] == 789);
        $this->assertTrue($u['telephone_number'] == '012345');
        $this->assertTrue($u['offair_notifications'] == 255);
        // Rights are migrated
        $this->assertIdentical($this->migr->get_user_rights(131), Array('offair_rep'));

        // Migration has also changed off-air order IDs
        $sql = 'SELECT * FROM Orders';
        $orders = db_get_all($sql, DB_FETCHMODE_ASSOC, $this->migr->db);
        $this->assertTrue($orders[0]['off_air_rep_id'] == 131);
        $this->assertTrue($orders[1]['off_air_rep_id'] == 131);
        $this->assertTrue($orders[2]['off_air_rep_id'] == 999);
        }

    function test_already_migrated()
        {
        $status = $this->migr->setup('migrated@example.com');
        $this->assertTrue($status == 904);
        }

    function test_existing_data_overwritten()
        {
        $status = $this->migr->setup('test8a@example.com');
        $status = $this->migr->migrate();
        // Old data is removed, only new data exists
        $this->assertIdentical($this->migr->get_user_data(151), Array('test'=>'quux'));
        }

    function test_existing_rights_overwritten()
        {
        $status = $this->migr->setup('test8b@example.com');
        $status = $this->migr->migrate();
        // Rights have been migrated
        $this->assertIdentical($this->migr->get_user_rights(153), Array('edit_record', 'offair_admin'));
        }
    }
