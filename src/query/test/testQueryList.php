<?php
// $Id$
// Tests for Query class
// James Fryer, 8 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

// Mocks
Mock::generate('DataSource');
Mock::generate('Module');
Mock::generate('QueryCache');
Mock::generate('QueryEncoder');
Mock::generate('DataSource_MemoryStorage', 'MockStorage');
Mock::generate('_DataSourceStorageFactory', 'MockStorageFactory');

class QueryListTestCase
    extends UnitTestCase
    {
    var $query_config = Array(
        'table_name'=>'test',
        'criteria_defs' => Array(
            Array(
                'name' => 'q',
                'label' => 'Search',
                'render_default' => 'All records',
                'list' => 'list_search',
                'is_primary' => TRUE,
                ), // query
            Array(
                'name' => 'text',
                'label' => 'Search',
                ), // query
            Array(
                'name' => 'sort',
                'type' => QC_TYPE_SORT,
                'is_renderable' => FALSE,
                ), // sort by
            ),
        'query_lists' => Array(
            'sort' => Array(
                ''=>'Date (oldest first)', 
                'date'=>'Date (newest first)', 
                'title'=>'Title'
                ),
            'list_search' => Array(
                ''=>'All fields', 
                'title'=>'Title',
                'person'=>'Person',
                ),
            ),
        );
        
    function setup()
        {
        global $MODULE;
        $this->url_search = $MODULE->url('search');
        $this->list = new QueryList();
        $this->query = QueryFactory::create($MODULE);
        }
        
    function create_query($criteria_values=NULL)
        {
        global $MODULE;
        return QueryFactory::create($MODULE,NULL,$criteria_values);
        }

    function test_add_item()
        {
        $this->assertTrue(count($this->list) == 0);
        $this->list->add($this->create_query());
        $this->assertTrue(count($this->list) == 1);
        }

    function test_can_only_add_queries()
        {
        $this->list->add(1);
        $this->assertTrue(count($this->list) == 0);
        $this->list->add('foo');
        $this->assertTrue(count($this->list) == 0);
        $this->list->add(Array(1, 'foo'));
        $this->assertTrue(count($this->list) == 0);
        }

    function test_item_is_added_to_front()
        {
        $q1 = $this->create_query(Array('q'=>'foo'));
        $this->list->add($q1);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=foo');
        $q2 = $this->create_query(Array('q'=>'bar'));
        $this->list->add($q2);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=bar');
        $this->assertEqual($this->list[1]['url'], $this->url_search.'?q=foo');
        }

    function test_no_duplicates()
        {
        $q1 = $this->create_query(Array('q'=>'foo'));
        $this->list->add($q1);
        $q2 = $this->create_query(Array('q'=>'bar'));
        $this->list->add($q2);
        $this->list->add($q1);
        // change page on query 1
        $q1->page = 2;
        $this->list->add($q1);
        $this->assertTrue(count($this->list) == 2);
        $this->assertEqual($this->list[0]['criteria']['q'], 'foo');
        $this->list->add($q2);
        $this->list->add($q2);
        $this->assertTrue(count($this->list) == 2);
        $this->assertEqual($this->list[0]['criteria']['q'], 'bar');
        $q3 = $this->create_query(Array('q'=>'baz'));
        $this->list->add($q3);
        $this->assertTrue(count($this->list) == 3);
        $this->assertEqual($this->list[0]['criteria']['q'], 'baz');
        $this->list->add($q1);
        $this->assertTrue(count($this->list) == 3);
        $this->assertEqual($this->list[0]['criteria']['q'], 'foo');
        }
    
    function test_remove()
        {
        $q1 = $this->create_query(Array('q'=>'abc'));
        $q2 = $this->create_query(Array('q'=>'def'));
        $q3 = $this->create_query(Array('q'=>'ghi'));
        $this->list->add($q1);
        $this->list->add($q2);
        $this->list->add($q3);
        
        // remove the middle query
        $this->list->remove(1);
        $this->assertEqual(count($this->list), 2);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=ghi');
        $this->assertEqual($this->list[1]['url'], $this->url_search.'?q=abc');
        // remove the newest query
        $this->list->remove(0);
        $this->assertEqual(count($this->list), 1);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=abc');
        // remove the last remaining query
        $this->list->remove(0);
        $this->assertEqual(count($this->list), 0);
        // add a query back
        $this->list->add($q1);
        $this->assertEqual(count($this->list), 1);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=abc');
        }

    function test_contains()
        {
        $q1 = $this->create_query(Array('q'=>'abc'));
        $q2 = $this->create_query(Array('q'=>'def'));
        $q3 = $this->create_query(Array('q'=>'ghi'));
        $this->list->add($q1);
        $this->list->add($q2);
        $this->list->add($q3);

        $q4 = $this->create_query(Array('q'=>'def'));
        $this->assertTrue($this->list->contains($q4));

        $q5 = $this->create_query(Array('q'=>'jkl'));
        $this->assertFalse($this->list->contains($q5));
        }

    function test_limit()
        {
        $list1 = new QueryList(3); // history limited to 3

        // setup 11 queries
        $q1 = $this->create_query(Array('q'=>'foo'));
        $q2 = $this->create_query(Array('q'=>'bar'));
        $q3 = $this->create_query(Array('q'=>'baz'));
        $q4 = $this->create_query(Array('q'=>'abc'));
        $q5 = $this->create_query(Array('q'=>'def'));
        $q6 = $this->create_query(Array('q'=>'ghi'));
        $q7 = $this->create_query(Array('q'=>'jkl'));
        $q8 = $this->create_query(Array('q'=>'mno'));
        $q9 = $this->create_query(Array('q'=>'pqr'));
        $q10 = $this->create_query(Array('q'=>'stu'));
        $q11 = $this->create_query(Array('q'=>'vwx'));

        // add 6 queries, confirm limit
        $list1->add($q1);
        $this->assertTrue(count($list1) == 1);
        $list1->add($q2);
        $this->assertTrue(count($list1) == 2);
        $list1->add($q3);
        $this->assertTrue(count($list1) == 3);
        $list1->add($q4);
        $this->assertTrue(count($list1) == 3);
        $list1->add($q5);
        $this->assertTrue(count($list1) == 3);
        $this->assertEqual($list1[2]['url'], $this->url_search.'?q=baz'); // oldest query is now baz
        $list1->add($q6);
        $this->assertTrue(count($list1) == 3);
        $this->assertEqual($list1[2]['url'], $this->url_search.'?q=abc'); // oldest query is now abc

        $list1 = new QueryList(); // reset the query list, default limit is umlimited
        $list1->add($q1);
        $list1->add($q2);
        $list1->add($q3);
        $list1->add($q4);
        $list1->add($q5);
        $list1->add($q6);
        $list1->add($q7);
        $list1->add($q8);
        $list1->add($q9);
        $list1->add($q10);
        $list1->add($q11);
        $this->assertTrue(count($list1) == 11);
        }

    function test_outline()
        {
        // Default outline is empty
        $this->assertEqual(Array(), $this->list->outline());
        
        // Two different queries generates two heads
        $this->list->add($this->create_query(Array('q'=>'abc')));
        $this->list->add($this->create_query(Array('q'=>'def')));
        $expected = Array(
            Array('head'=> $this->list[0], 'tail'=>Array()),
            Array('head'=> $this->list[1], 'tail'=>Array()),
            );
        $this->assertEqual($expected, $this->list->outline());

        // Similar queries are grouped together
        $this->list->add($this->create_query(Array('q'=>'def', 'text'=>'foo')));
        $expected = Array(
            Array('head'=> $this->list[0], 'tail'=>Array($this->list[1])), // 'def'
            Array('head'=> $this->list[2], 'tail'=>Array()), // 'abc'
            );
        $this->assertEqual($expected, $this->list->outline());

        // Most recently added query is always first
        $this->list->add($this->create_query(Array('q'=>'def', 'text'=>'bar')));
        $this->list->add($this->create_query(Array('q'=>'abc', 'text'=>'bar')));
        $expected = Array(
            Array('head'=> $this->list[0], 'tail'=>Array($this->list[4])), // 'abc'
            Array('head'=> $this->list[1], 'tail'=>Array($this->list[2], $this->list[3])), // 'def'
            );
        $this->assertEqual($expected, $this->list->outline());
        }

    function test_outline_with_array_criteria()
        {
        $this->list->add($this->create_query(Array('q'=>Array('abc'))));
        $this->list->add($this->create_query(Array('q'=>Array('def'))));
        $this->list->add($this->create_query(Array('q'=>Array('abc'), 'text'=>'foo')));
        $expected = Array(
            Array('head'=> $this->list[0], 'tail'=>Array($this->list[2])),
            Array('head'=> $this->list[1], 'tail'=>Array()),
            );
        $this->assertEqual($expected, $this->list->outline());
        }

    function test_outline_with_two_modules()
        {
        $this->list->add($this->create_query(Array('q'=>Array('abc'))));
        $query = $this->create_query(Array('q'=>Array('abc')));
        $query->module = clone($query->module);
        $query->module->name = 'changedname';
        $this->list->add($query);
        $expected = Array(
            Array('head'=> $this->list[0], 'tail'=>Array()),
            Array('head'=> $this->list[1], 'tail'=>Array()),
            );
        $this->assertEqual($expected, $this->list->outline());
        }
        
    // Further tests:

    function test_add_item_new_query_impl()
        {
        $this->assertTrue(count($this->list) == 0);
        $this->list->add( $this->new_query() );
        $this->assertTrue(count($this->list) == 1);
        }
    
    function test_item_is_added_to_front_new_query_impl()
        {
        global $CONF;
        $q1 = $this->new_query_shortform( Array( 'q' => 'foo' ) );
        $this->list->add($q1);
        
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=foo');
        $q2 = $this->new_query_shortform( Array( 'q' => 'bar' ) );
        $this->list->add($q2);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=bar');
        $this->assertEqual($this->list[1]['url'], $this->url_search.'?q=foo');
        }

    function test_no_duplicates_new_query_impl()
        {
        $q1 = $this->new_query_shortform( Array( 'q' => 'foo' ) );
        $this->list->add($q1);
        $q2 = $this->new_query_shortform( Array( 'q' => 'bar' ) );
        $this->list->add($q2);
        $this->list->add($q1);
        // change page on query 1
        $q1->page = 2;
        $this->list->add($q1);
        $this->assertTrue(count($this->list) == 2);
        $this->assertEqual($this->list[0]['hash'], $q1->hash() );
        $this->list->add($q2);
        $this->list->add($q2);
        $this->assertTrue(count($this->list) == 2);
        $this->assertEqual($this->list[0]['hash'], $q2->hash() );
        $q3 = $this->new_query_shortform( Array( 'q' => 'baz' ) );
        $this->list->add($q3);
        $this->assertTrue(count($this->list) == 3);
        $this->assertEqual($this->list[0]['hash'], $q3->hash() );
        $this->list->add($q1);
        $this->assertTrue(count($this->list) == 3);
        $this->assertEqual($this->list[0]['hash'], $q1->hash() );
        }
        
    function test_remove_new_query_impl()
        {
        $q1 = $this->new_query_shortform( Array( 'q' => 'abc' ) );
        $q2 = $this->new_query_shortform( Array( 'q' => 'def' ) );
        $q3 = $this->new_query_shortform( Array( 'q' => 'ghi' ) );
        $this->list->add($q1);
        $this->list->add($q2);
        $this->list->add($q3);
    
        // remove the middle query
        $this->list->remove(1);
        $this->assertEqual(count($this->list), 2);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=ghi');
        $this->assertEqual($this->list[1]['url'], $this->url_search.'?q=abc');
        // remove the newest query
        $this->list->remove(0);
        $this->assertEqual(count($this->list), 1);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=abc');
        // remove the last remaining query
        $this->list->remove(0);
        $this->assertEqual(count($this->list), 0);
        // add a query back
        $this->list->add($q1);
        $this->assertEqual(count($this->list), 1);
        $this->assertEqual($this->list[0]['url'], $this->url_search.'?q=abc');
        }
        
      
    function test_contains_new_query_impl()
        {
        $q1 = $this->new_query_shortform( Array( 'q' => 'abc' ) );
        $q2 = $this->new_query_shortform( Array( 'q' => 'def' ) );
        $q3 = $this->new_query_shortform( Array( 'q' => 'ghi' ) );
        $this->list->add($q1);
        $this->list->add($q2);
        $this->list->add($q3);

        $q4 = $this->new_query_shortform( Array( 'q' => 'def' ) );
        $this->assertTrue($this->list->contains($q4));

        $q5 = $this->new_query_shortform( Array( 'q' => 'jkl' ) );
        $this->assertFalse($this->list->contains($q5));
        }

    function test_limit_new_query_impl()
        {
        $list1 = new QueryList(3); // history limited to 3

        // setup 11 queries
        $q1 = $this->new_query_shortform( Array( 'q' => 'foo' ) );
        $q2 = $this->new_query_shortform( Array( 'q' => 'bar' ) );
        $q3 = $this->new_query_shortform( Array( 'q' => 'baz' ) );
        $q4 = $this->new_query_shortform( Array( 'q' => 'abc' ) );
        $q5 = $this->new_query_shortform( Array( 'q' => 'def' ) );
        $q6 = $this->new_query_shortform( Array( 'q' => 'ghi' ) );
        $q7 = $this->new_query_shortform( Array( 'q' => 'jkl' ) );
        $q8 = $this->new_query_shortform( Array( 'q' => 'mno' ) );
        $q9 = $this->new_query_shortform( Array( 'q' => 'pqr' ) );
        $q10 = $this->new_query_shortform( Array( 'q' => 'stu' ) );
        $q11 = $this->new_query_shortform( Array( 'q' => 'vwx' ) );
        
        // add 6 queries, confirm limit
        $list1->add($q1);
        $this->assertTrue(count($list1) == 1);
        $list1->add($q2);
        $this->assertTrue(count($list1) == 2);
        $list1->add($q3);
        $this->assertTrue(count($list1) == 3);
        $list1->add($q4);
        $this->assertTrue(count($list1) == 3);
        $list1->add($q5);
        $this->assertTrue(count($list1) == 3);
        $this->assertEqual($list1[2]['url'], $this->url_search.'?q=baz'); // oldest query is now baz
        $list1->add($q6);
        $this->assertTrue(count($list1) == 3);
        $this->assertEqual($list1[2]['url'], $this->url_search.'?q=abc'); // oldest query is now abc

        $list1 = new QueryList(); // reset the query list, default limit is umlimited
        $list1->add($q1);
        $list1->add($q2);
        $list1->add($q3);
        $list1->add($q4);
        $list1->add($q5);
        $list1->add($q6);
        $list1->add($q7);
        $list1->add($q8);
        $list1->add($q9);
        $list1->add($q10);
        $list1->add($q11);
        $this->assertTrue(count($list1) == 11);
        }
        
    function test_ignore_sort_new_query_impl()
        {
        $q1 = $this->new_query_shortform( Array( 'q' => 'abc' ) );
        $q2 = $this->new_query_shortform( Array( 'q' => 'abc', 'sort' => 'desc' ) );
        $q3 = $this->new_query_shortform( Array( 'q' => 'abc', 'sort' => 'asc' ) );
        
        $this->list->add($q1);
        $this->assertTrue(count($this->list) == 1);
        $this->list->add($q2);
        $this->assertTrue(count($this->list) == 1);
        $this->list->add($q3);
        $this->assertTrue(count($this->list) == 1);
        }
    
    
    function new_query_shortform( $name_values )
        {
        $criteria_defs = Array();
        foreach( $name_values as $name=>$value )
            $criteria_defs[] = Array( 'name'=>$name, 'value'=>$value );    
        return $this->new_query( $criteria_defs );
        }
    
    function new_query( $criteria_defs = NULL )
        {
        global $MODULE;
        $config = $this->query_config;
        if ($criteria_defs)
            $config['criteria_defs'] = $criteria_defs;
        unset($MODULE->query_config);//### TEMP -- to remove the query_name field
        return QueryFactory::create($MODULE, $config);
        }
    }
