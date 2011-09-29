<?php
// $Id$
// Tests for RecordList
// Alexander Veenendaal, 16 Jun 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

// Mocks
Mock::generate('DataSource');
Mock::generate('Module');
Mock::generate('QueryCache');
Mock::generate('QueryEncoder');

class RecordListTestCase
    extends UnitTestCase
    {
    var $query_config = Array(
        'table'=>'test',
        );
    
    function setup()
        {
        global $MODULE;
        $this->query = $this->new_query_from_defs( $this->query_config );
        $this->list = new RecordList();
        }    
    
    function test_add_and_count()
        {
        $this->assertEqual($this->list->count(), 0);
        $this->assertEqual(count($this->list), 0);
        
        $record = $this->query->get_record('/dummy/test/single');
        $this->list->add($record);
        
        $this->assertEqual(count($this->list), 1);

        // Add same record twice is ignored
        $record = $this->query->get_record('/dummy/test/single');
        $this->list->add($record);
        $this->assertEqual(count($this->list->count()), 1);
        }
    
    function test_add_array_style()
        {
        $record = $this->query->get_record('/dummy/test/single');
        $this->assertEqual( count($this->list), 0 );
        $this->list[] = $record;
        $this->assertEqual(count($this->list), 1);

        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        $this->assertEqual(count($this->list->count()), 1);
        }
    
    function test_add_valid_records_only()
        {
        $this->assertEqual( count($this->list), 0 );
        $this->list[] = '/dummy/test/item001';
        $this->assertEqual( count($this->list), 0 );
        
        $this->list[] = Array( 'url' => '/dummy/test/blah' );
        $this->assertEqual( count($this->list), 0 );
        
        $this->list[] = NULL;
        $this->assertEqual( count($this->list), 0 );
        }

    function test_record_is_added_to_front()
        {
        
        for ($i = 0; $i < 3; $i++)
            $this->list[] =  $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );
        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        
        $this->assertEqual( $this->list[0]['url'], $record['url'] );
        }
    
    function test_record_readded()
        {
        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        
        $this->assertEqual( $this->list->array[0]['url'], $record['url'] );
        
        for ($i = 0; $i < 3; $i++)
            $this->list[] =  $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );
            
        $this->assertNotEqual( $this->list[0]['url'], $record['url'] );
        
        $this->list[] = $record;        
        $this->assertEqual( $this->list[0]['url'], $record['url'] );
        }
        
    function test_record_get()
        {
        global $MODULE;
        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        $this->assertEqual(count($this->list), 1);
        
        for ($i = 0; $i < 3; $i++)
            $this->list[] =  $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );

        $this->assertEqual( $this->list['/dummy/test/single']['url'], $record['url'] );
        $this->assertEqual( $this->list['/dummy/test/single']['modname'], 'dummy' );
        $this->assertEqual( $this->list['/dummy/test/single']['title'], $record['title'] );
        $this->assertEqual( $this->list['/dummy/test/single']['description'], $record['description'] );
        $this->assertEqual( $this->list['/dummy/test/single']['_table'], $record['_table'] );
        }
        
    function test_record_isset()
        {
        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        $this->assertEqual(count($this->list), 1);
        $this->assertTrue( isset($this->list['/dummy/test/single']) );
        }

    function test_remove_record()
        {
        $record = $this->query->get_record('/dummy/test/single');
        $this->list[] = $record;
        $this->assertEqual(count($this->list), 1);
        
        // unset works by the url
        unset($this->list['/dummy/test/single']);
        $this->assertEqual(count($this->list), 0);
        
        // but still works with indexes
        $this->list[] = $this->query->get_record('/dummy/test/many000');
        $this->assertEqual($this->list->count(), 1);
        unset($this->list[0]);
        $this->assertEqual(count($this->list), 0);
        }        
        
    function test_iterate_over()
        {
        $records = Array();
        for ($i = 0; $i < 3; $i++)
            {
            $record = $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );
            $records[] = $record;
            $this->list[] = $record;
            }
        
        // the records in the list will be reversed
        $index = 0;
        foreach( $this->list as $record )
            {
            $this->assertEqual( $record['url'], $records[count($records)-1-$index]['url'] );
            $index = $index + 1;
            }
        
        foreach( $this->list as $index=>$record )
            {
            $this->assertEqual( $record['url'], $records[count($records)-1-$index]['url'] );
            }
        }        
        
    function test_limit()
        {
        $this->list = new RecordList(3);
        
        for ($i = 0; $i < 10; $i++)
            $this->list[] =  $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );
            
        $this->assertEqual($this->list->count(), 3);
        
        // reset the query list, default limit is umlimited
        $this->list = new RecordList();
        
        for ($i = 0; $i < 15; $i++)
            $this->list[] =  $this->query->get_record( sprintf('/dummy/test/many%03d', $i) );
            
        $this->assertEqual($this->list->count(), 15);
        }
    
    function new_query_from_defs( $criteria_defs=NULL )
        {
        global $MODULE;
        $config = $this->query_config;
        if ($criteria_defs)
            $config['criteria_defs'] = $criteria_defs;
        return QueryFactory::create($MODULE, $config);
        }
    }
