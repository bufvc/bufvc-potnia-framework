<?php
// $Id$
// Tests for QueryCriteriaCache class
// Alexander Veenendaal, 03 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'query/QueryCriteriaCache.class.php');

// Mocks
Mock::generate('Module');
Mock::generate('QueryCache');
Mock::generate('QueryEncoder');
Mock::generate('DataSource');
Mock::generate('DataSource_MemoryStorage', 'MockStorage');
Mock::generate('_DataSourceStorageFactory', 'MockStorageFactory');

class QueryCriteriaCacheTestCase
    extends UnitTestCase
    {    
    function setup()
        {
        $this->cache = new QueryCriteriaCache(10);
        $this->cache->results = Array(
            'data' => Array(
                Array('url' => '/test/123'),
                Array('url' => '/test/456'),
                Array('url' => '/test/789'),
                Array('url' => '/test/abc'),
                Array('url' => '/test/def'),
                ),
            'offset' => 0,
            'count' => 5,
            'total' => 5,
            );
        }

    function test_compare_record()
        {
        $this->assertTrue($this->cache->compare_record(0, Array('url'=>'/test/123')));
        $this->assertTrue($this->cache->compare_record(1, Array('url'=>'/test/456')));
        $this->assertFalse($this->cache->compare_record(0, Array('url'=>'/test/456')));
        }

    function test_set_record_link()
        {
        $this->assertEqual($this->cache->set_record_link(0), '/test/123');
        $this->assertEqual($this->cache->set_record_link(3), '/test/abc');
        }

    function test_hit()
        {
        $this->cache->criteria = null;
        $criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '123') ),
            ));
        $other_criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '456') ),
            ));
        // no cache
        $this->assertFalse($this->cache->hit($criteria, 0));
        // cached criteria doesn't match
        $this->cache->criteria = $criteria;
        $this->assertFalse($this->cache->hit($other_criteria, 0));
        // offset outside bounds of current cache
        $this->cache->results['offset'] = 5;
        $this->assertFalse($this->cache->hit($criteria, 0));
        $this->assertFalse($this->cache->hit($criteria, 4));
        $this->assertFalse($this->cache->hit($criteria, 15));
        // offset within bounds
        $this->assertTrue($this->cache->hit($criteria, 5));
        $this->assertTrue($this->cache->hit($criteria, 10));
        $this->assertTrue($this->cache->hit($criteria, 14));
        }

    function test_hit_compares_sort()
        {
        $this->cache->criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '123') ),
            QueryCriterionFactory::create( Array('name' => 'sort', 'value' => 'foo') ),
            ));
        $criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '123') ),
            QueryCriterionFactory::create( Array('name' => 'sort', 'value' => 'bar') ),
            ));
        $this->assertFalse($this->cache->hit($criteria, 0));
        }

    function test_set()
        {
        $results = Array(
            'data' => Array(
                    Array('url' => '/test/456'),
                    ),
                );
        $criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '456') ),
            ));
        // $criteria = Array('test'=>'456');
        // no results
        $this->cache->set(null, $criteria);
        $this->assertTrue(is_null($this->cache->results));
        $this->assertTrue(is_null($this->cache->criteria));
        // results
        $this->cache->set($results, $criteria);
        $this->assertEqual($this->cache->results, $results);
        $this->assertEqual($this->cache->criteria, $criteria);
        }

    function test_get()
        {
        // get a slice of 2 records from the cache
        $expected = Array(
            'data' => Array(
                Array('url' => '/test/789'),
                Array('url' => '/test/abc'),
                ),
            'offset' => 2,
            'count' => 2,
            'total' => 5,
            );
        $this->assertEqual($this->cache->get(2, 2), $expected);

        // empty cache
        $this->cache->results = null;
        $this->assertTrue(is_null($this->cache->get(2, 2)));
        }

    function test_search()
        {
        $table = '/test';
        $query_string = '{test=123}';
        // $criteria = Array('test'=>'123');
        $criteria = new QueryCriteria( Array(
            QueryCriterionFactory::create( Array('name' => 'test', 'value' => '123') ),
            ));
        $expected_results = Array(
            'data' => Array(
                Array('url' => '/test/123'),
                ),
            'offset' => 0,
            'count' => 1,
            'total' => 1,
            );
        $ds = new MockDataSource();
        // search will populate cache
        $this->cache->size = 20;
        $ds->expect('search', Array($table, $query_string, 0, 20));
        $ds->setReturnValue('search', $expected_results);
        $results = $this->cache->search($ds, $table, $query_string, 0, 10, $criteria);
        $this->assertEqual($results, $expected_results);
        $this->assertEqual($this->cache->results, $expected_results);
        $this->assertEqual($this->cache->criteria, $criteria);
        // disable cache
        $this->cache->size = 0;
        $ds->expect('search', Array($table, $query_string, 0, 10));
        $ds->setReturnValue('search', $expected_results);
        $results = $this->cache->search($ds, $table, $query_string, 0, 10, $criteria);
        $this->assertEqual($results, $expected_results);
        }
    }
