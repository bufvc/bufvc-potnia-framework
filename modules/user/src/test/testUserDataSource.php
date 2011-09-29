<?php
// $Id$
// Tests for UserDataSource
// Phil Hansen, 04 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');
require_once($CONF['path_src'] . 'datasource/test/BaseDataSourceTestCase.class.php');

// Some static test data
$USER_TEST_ITEM = Array(
        'login'=>'dstest_user',
        'email'=>'dstest@datasourcetest.com',
        'name'=>"DSTest User",
        'root'=>'0',
        'rights' => Array('edit_record', 'play_audio'),
        'telephone_number' => '123-4567',
        'institution' => 'Test Member Institution',
        'institution_id' => 1,
        'user_data' => Array(Array('name'=>'some_data', 'value'=>'this is saved data')),
        );

class UserDataSourceTestCase
    extends BaseDataSourceTestCase
    {
    function new_datasource()
        {
        global $MODULE;
        return $MODULE->new_datasource();
        }

    var $test_data;
    var $related_tables = Array('rights');

    function setup()
        {
        parent::setup();
        global $USER_TEST_ITEM;
        $this->test_data = $USER_TEST_ITEM;
        }

    function test_search()
        {
        global $CONF;
        // All searches which should return one result
        $tests = Array(
                "{default=DSTest}",
                "{email=dstest}",
                "{name=DSTest}",
                '{default="datasourcetest"}',
                "{sort=login}{default=DSTest}",
                );

        // Create with data
        $record = $this->ds->create('/user', $this->test_data);
        foreach ($tests as $test)
            {
            //print "### $test\n";
            // println( $test );
            // $CONF['debug_trace'] = 1;
            $r = $this->ds->search('/user', $test, 0, 10);
            $this->assertNoError();
            $this->assertResults($r, 0, 1, 1);
            }
        $this->ds->delete($record['url']);

        // Create with no data then update
        $record = $this->ds->create('/user', Array('login'=>'foo'));
        $this->ds->update($record['url'], $this->test_data);
        foreach ($tests as $test)
            {
            //### print "### $test\n";
            $r = $this->ds->search('/user', $test, 0, 10);
            $this->assertNoError();
            $this->assertResults($r, 0, 1, 1);
            }
        // print_r($this->ds->retrieve($record['url']));//###

        // remove one right
        $this->ds->update($record['url'], Array('login'=>'test_user', 'rights'=>Array('edit_record')));
        $record = $this->ds->retrieve($record['url']);
        // only edit_record right is present, play_audio has been removed
        $this->assertEqual($record['rights'], Array('edit_record'));
        $this->ds->delete($record['url']);
        }

    function test_bufvc_fields()
        {
        $record = $this->ds->create('/user', $this->test_data);
        $this->ds->delete($record['url']);
        $this->assertEqual($record['login'], 'dstest_user');
        $this->assertEqual($record['telephone_number'], '123-4567');
        $this->assertEqual($record['institution_id'], 1);
        $this->assertEqual($record['offair_notifications'], 1);
        }
    
    function test_user_data_search()
        {
        $record = $this->ds->create('/user', $this->test_data);
        $test = "{user=$record[id]}";
        $r = $this->ds->search('/user_data', $test, 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 1, 1);
        $this->ds->delete($record['url']);
        }
    
    function test_flags()
        {
        $this->assertEqual($this->ds->enable_query_normalisation, 0);
        $this->assertEqual($this->ds->enable_storage_normalisation, 0);
        }
    }
