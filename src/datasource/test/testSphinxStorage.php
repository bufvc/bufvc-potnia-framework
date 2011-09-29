<?php
// $Id$
// Test the MySQL storage handler
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once('MDB2/Driver/mysql.php');
require_once $CONF['path_lib'] . 'sphinxapi.php';

Mock::generate('MDB2_Driver_mysql', 'MockDB');
Mock::generate('SqlGenerator');
Mock::generate('SphinxGenerator');
Mock::generate('SphinxClient', 'MockSphinx');

// Test the MySQL storage handler
class SphinxStorageTestCase
    extends UnitTestCase
    {
    // Test configuration
    var $config = Array(
        'test' => Array(
            'title'=>'Test',
            'mutable'=>TRUE,
            'storage'=> 'sphinx',//### FIXME: find some way to inject our storage into DS
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                ),
            'search'=>Array(
                'sphinx' => 1,
                'fields' => Array('t.title','t.description AS summary'),
                // The indexes define what criteria are acceptable in a query
                'index' => Array(
                    'default' => Array('type'=>'fulltext', 'fields'=>'t.title,t.description'),
                    'title' => Array('type'=>'fulltext', 'fields'=>'t.title'),
                    ),
                ),
            ),
        );

    function setup($config=NULL)
        {
        global $MODULE;
        if (is_null($config))
            $config = $this->config;
        $this->ds = new DataSource($config);
        $this->sphinx = new MockSphinx();
        $this->sphinx->_socket = FALSE; // Suppress error message
        $this->storage = new DataSource_SphinxStorage($this->ds, $this->sphinx);
        $this->storage->sphinx_generator = new MockSphinxGenerator();
        $this->storage->sphinx_generator->setReturnValue('sphinx', $this->sphinx);
        $this->storage->sphinx_generator->setReturnValue('add_query', 123);
        }

    function setup_sphinx($search_results=NULL)
        {
        if (!$search_results)
            $search_results = Array(123=>Array('matches'=>Array(Array('id'=>1),Array('id'=>2)), 'total'=>2, 'total_found'=>111));
        $this->sphinx->expectOnce('RunQueries');
        $this->sphinx->setReturnValue('RunQueries', $search_results);
        }
        
    function setup_sql_generator()
        {
        $this->storage->generator = new MockSqlGenerator();
        $this->storage->generator->expectOnce('to_sql', Array(NULL, 'none')); // No sort
        $this->storage->generator->setReturnValue('to_sql', 'quux');
        }

    function setup_mdb2($sphinx_key='t.id', $id_list='1,2')
        {
        $this->storage->db = new MockDB();
        $this->storage->db->expectOnce('queryAll', Array("quux WHERE $sphinx_key IN($id_list)", NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValue('queryAll', Array(Array('id'=>2, 'title'=>'title2'), Array('id'=>1, 'title'=>'title1'), ));
        }
        
    function setup_db_not_used()
        {
        // SQL not generated/run
        $this->storage->generator = new MockSqlGenerator();
        $this->storage->generator->expectNever('to_sql');
        $this->storage->db = new MockDB();
        $this->storage->db->expectNever('queryAll');
        }
        
    function assertNoError()
        {
        $this->assertTrue($this->ds->error_code == 0, 'error_code: ' . $this->ds->error_code);
        $this->assertTrue($this->ds->error_message == '', 'error_message: ' . $this->ds->error_message);
        }

    function assertError($status)
        {
        $this->assertEqual($this->ds->error_code, $status, 'error_code: %s');
        $this->assertTrue($this->ds->error_message != '', 'missing error_message');
        }

    function test_search()
        {
        // Query gets converted to sphinx
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));

        // Sphinx query is run
        $this->setup_sphinx();
        
        // SQL is generated and run
        $this->setup_sql_generator();
        $this->setup_mdb2();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        
        // Results fields are filled in
        $this->assertEqual(2, count($r['data']));
        $this->assertEqual(2, $r['total']);
        $this->assertEqual(111, $r['total_found']);
        $this->assertEqual('exact', $r['accuracy']);
        }

    function test_search_no_results()
        {
        // Query gets converted to sphinx
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));

        // Sphinx query is run, no results
        $this->setup_sphinx(Array(123=>Array()));
        
        // SQL is not used
        $this->setup_db_not_used();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        
        // Results fields are filled in
        $this->assertEqual(0, count($r['data']));
        $this->assertEqual(0, $r['total']);
        $this->assertEqual('exact', $r['accuracy']);
        }

    // Mysql returns results in a random order, we need them sorted per the sphinx
    function test_search_results_sorted_by_sphinx()
        {
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $expected = Array(
            Array('id' => 1, 'title' => 'title1'),
            Array('id' => 2, 'title' => 'title2'),
            );
        $this->assertEqual($expected, $r['data']);
        }

    function test_search_results_sort_field()
        {
        $search_results = Array(
            123=>Array(
                'matches'=>Array(
                    Array('id'=>1, 'attrs'=>Array('foo'=>1)),
                    Array('id'=>2, 'attrs'=>Array('foo'=>2))
                ), 'total'=>2));
        $this->setup_sphinx($search_results);
        $this->setup_sql_generator();
        $this->storage->sphinx_generator->setReturnValue('sort_field', 'foo');
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $expected = Array(
            Array('id' => 1, 'title' => 'title1', '_sort'=>1),
            Array('id' => 2, 'title' => 'title2', '_sort'=>2),
            );
        $this->assertEqual($expected, $r['data']);
        }

    function test_search_results_sort_field_rel()
        {
        $search_results = Array(
            123=>Array(
                'matches'=>Array(
                    Array('id'=>1, 'weight'=>100),
                    Array('id'=>2, 'weight'=>50)
                ), 'total'=>2));
        $this->setup_sphinx($search_results);
        $this->setup_sql_generator();
        $this->storage->sphinx_generator->setReturnValue('sort_field', 'rel');
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $expected = Array(
            Array('id' => 1, 'title' => 'title1', '_sort'=>1.0),
            Array('id' => 2, 'title' => 'title2', '_sort'=>0.5),
            );
        $this->assertEqual($expected, $r['data']);
        }

    function test_search_results_sort_field_date()
        {
        $search_results = Array(
            123=>Array(
                'matches'=>Array(
                    Array('id'=>1, 'attrs'=>Array('date'=>1)),
                    Array('id'=>2, 'attrs'=>Array('date'=>2))
                ), 'total'=>2));
        $this->setup_sphinx($search_results);
        $this->setup_sql_generator();
        $this->storage->sphinx_generator->setReturnValue('sort_field', 'date');
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $expected = Array(
            Array('id' => 1, 'title' => 'title1', '_sort'=>strftime('%Y-%m-%d %H:%M:%S', 1)),
            Array('id' => 2, 'title' => 'title2', '_sort'=>strftime('%Y-%m-%d %H:%M:%S', 2)),
            );
        $this->assertEqual($expected, $r['data']);
        }
    
    function test_search_results_sort_field_str_ord()
        {
        $search_results = Array(
            123=>Array(
                'matches'=>Array(
                    Array('id'=>1, 'attrs'=>Array('title_ord'=>1)),
                    Array('id'=>2, 'attrs'=>Array('title_ord'=>2))
                ), 'total'=>2));
        $this->setup_sphinx($search_results);
        $this->setup_sql_generator();
        $this->storage->sphinx_generator->setReturnValue('sort_field', 'title_ord');
        // include the contents of $this->setup_mdb2() directly so we can modify the return value
        $this->storage->db = new MockDB();
        $this->storage->db->expectOnce('queryAll', Array("quux WHERE t.id IN(1,2)", NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValue('queryAll', Array(Array('id'=>2, 'title'=>'title2', 'title_ord'=>'title2 from sort'), Array('id'=>1, 'title'=>'title1'), ));
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $expected = Array(
            Array('id' => 1, 'title' => 'title1', '_sort'=>'title1'),
            Array('id' => 2, 'title' => 'title2', 'title_ord'=>'title2 from sort', '_sort'=>'title2 from sort'),
            );
        $this->assertEqual($expected, $r['data']);
        }

    function test_search_with_sphinx_key()
        {
        $config = $this->config;
        $config['test']['search']['sphinx_key'] = 'foo';
        $this->setup($config);
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2('foo');
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }

    function test_search_with_sphinx_index_in_config()
        {
        $config = $this->config;
        $config['test']['search']['sphinx_index'] = 'foo';
        $this->setup($config);
        $this->storage->sphinx_generator->expectOnce('add_query', Array('foo', 'parsed-query', 0, 1000, NULL));
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }

    function test_search_with_sphinx_index_in_query()
        {
        // The result of find_sphinx_config_value will be passed to add_query
        $this->storage->sphinx_generator->expectAt(0, 'find_sphinx_config_value', Array('parsed-query', 'sphinx_index'));
        $this->storage->sphinx_generator->setReturnValue('find_sphinx_config_value', 'bar');
        $this->storage->sphinx_generator->expectOnce('add_query', Array('bar', 'parsed-query', 0, 1000, NULL));
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }
    
    function test_search_with_sphinx_group_by_in_config()
        {
        $config = $this->config;
        $config['test']['search']['sphinx_group_by'] = Array('attribute'=>'foo');
        $this->setup($config);
        $this->storage->sphinx_generator->setReturnValue('find_sphinx_config_value', '');
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, Array('attribute'=>'foo')));
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }
    
    function test_search_with_sphinx_group_by_in_query()
        {
        $config = $this->config;
        $config['test']['search']['sphinx_group_by'] = Array('attribute'=>'foo');
        $this->setup($config);
        $this->storage->sphinx_generator->expectAt(0, 'find_sphinx_config_value', Array('parsed-query', 'sphinx_index'));
        $this->storage->sphinx_generator->expectAt(1, 'find_sphinx_config_value', Array('parsed-query', 'sphinx_group_by'));
        $this->storage->sphinx_generator->setReturnValueAt(0, 'find_sphinx_config_value', '');
        $this->storage->sphinx_generator->setReturnValueAt(1, 'find_sphinx_config_value', Array('attribute'=>'bar'));
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, Array('attribute'=>'bar')));
        $this->setup_sphinx();
        $this->setup_sql_generator();
        $this->setup_mdb2();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }

    function test_search_fails_if_sphinx_flag_not_set()
        {
        $config = $this->config;
        unset($config['test']['search']['sphinx']);
        $this->setup($config);
        
        $this->storage->sphinx_generator->expectNever('add_query');
        
        $this->storage->sphinx = $this->sphinx;
        $this->sphinx->expectNever('RunQueries');
        $this->setup_db_not_used();
        
        $this->assertNull($this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000));
        $this->assertNoError();
        }

    function test_search_fails_if_sphinx_unavailable_no_error()
        {
        $this->storage->sphinx_generator = new MockSphinxGenerator();
        $this->storage->sphinx_generator->setReturnValue('sphinx', NULL);
        $this->storage->sphinx_generator->setReturnValue('add_query', 0);
        $this->setup_db_not_used();

        $this->assertNull($this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000));
        $this->assertNoError();
        }

    function test_search_fails_if_generator_returns_null_no_error()
        {
        $this->storage->sphinx_generator = new MockSphinxGenerator();
        $this->storage->sphinx_generator->setReturnValue('sphinx', $this->sphinx);
        $this->storage->sphinx_generator->setReturnValue('add_query', NULL);
        $this->setup_db_not_used();

        $this->assertNull($this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000));
        $this->assertNoError();
        }

    function test_search_generator_error()
        {
        $this->storage->sphinx_generator = new MockSphinxGenerator();
        $this->storage->sphinx_generator->setReturnValue('sphinx', $this->sphinx);
        $this->storage->sphinx_generator->setReturnValue('add_query', NULL);
        $this->storage->sphinx_generator->error_message = 'err';
        $this->setup_db_not_used();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertError(400, 'err');
        }

    function test_search_sphinx_query_error()
        {
        // Query gets converted to sphinx
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));
        $this->storage->sphinx_generator->setReturnValue('add_query', 0);

        // Sphinx query is run
        $this->setup_sphinx(Array(123=>Array('error' => 'err', 'warning'=>0, 'status'=>1),));
        $this->setup_db_not_used();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertError(400, 'err');
        }
        
    function test_search_sphinx_general_error()
        {
        // Query gets converted to sphinx
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));

        // Sphinx query is run
        $this->setup_sphinx();
        $this->sphinx->setReturnValue('GetLastError', 'err');
        $this->setup_db_not_used();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertError(400, 'err');
        }

    // If a connection error is encountered, the error code is 500
    function test_search_sphinx_connect_error()
        {
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));
        $this->setup_sphinx();
        $this->sphinx->setReturnValue('GetLastError', 'err');
        $this->sphinx->setReturnValue('IsConnectError', TRUE);
        $this->storage->db = new MockDB();
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertError(500, 'err');
        }

    function test_search_with_facets()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            256 => Array('type'=>'group1', 'name'=>'facet1', 'select'=>"t.title=''"),
            512 => Array('type'=>'group1', 'name'=>'facet2', 'select'=>"t.description<>''"),
            1024 => Array('type'=>'group1', 'name'=>'facet3', 'select'=>"all"),
            2048 => Array('type'=>'group2', 'name'=>'facet4', 'select'=>"all"),
            'count'=>'ignored',
            );
        $this->setup($config);

        // Query gets converted to sphinx
        $this->storage->sphinx_generator->expectOnce('add_query', Array('test', 'parsed-query', 0, 1000, NULL));
        
        // Sphinx group by is called
        $this->storage->sphinx_generator->expectOnce('add_group_by', Array('test', 'facets'));
        $this->storage->sphinx_generator->setReturnValue('add_group_by', 456);

        // Sphinx query is run, returns two sets of results indexed by return value from query functions
        $sphinx_results = Array(
            123=>Array('matches'=>Array(Array('id'=>1), Array('id'=>2), Array('id'=>3)), 'total'=>3),
            456=>Array('matches'=>Array(
                // facet1
                0 => Array(
                    'attrs' => Array(
                        '@groupby' => 256,
                        '@count' => 0,
                        )
                    ),
                // facet2
                1 => Array(
                    'attrs' => Array(
                        '@groupby' => 512,
                        '@count' => 3,
                        )
                    ),
                ), 'total'=>2),
            );
        $this->setup_sphinx($sphinx_results);
        
        // SQL is generated and run
        $this->setup_sql_generator();
        $this->setup_mdb2('t.id', '1,2,3');

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();

        // confirm the expected total and facet array
        $this->assertEqual($r['total'], 3);
        $this->assertEqual($r['facets'], Array('group1'=>Array('facet1'=>0,'facet2'=>3,'facet3'=>3), 
                'group2'=>Array('facet4'=>3), 'accuracy'=>'exact'));
        }
    
    function test_search_facet_details_copied_from_sphinx()
        {
        $sphinx_results = Array(
            123=>Array('matches'=>Array(Array('id'=>1, 'attrs'=>Array('facet_details'=>2))), 'total'=>1),
            );
        $this->setup_sphinx($sphinx_results);
        
        // SQL is generated and run
        $this->setup_sql_generator();
        $this->storage->db = new MockDB();
        $this->storage->db->expectOnce('queryAll');
        $this->storage->db->setReturnValue('queryAll', Array(Array('id'=>1, 'title'=>'title1'), ));

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();

        // Returned result includes facet details
        $this->assertEqual(Array('id'=>1, 'title'=>'title1', 'facet_details'=>2), $r['data'][0]);
        }
    
    // It is probably better to compute constant facets than to do them this way. 
    // This test should be deleted if the functionality is not needed.
    function MAYBE_test_search_facet_details_added_in_code()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            1 => Array('group'=>'group1', 'type'=>'type1', 'select'=>"all"),
            );
        $this->setup($config);
        $this->setup_sphinx();
        
        // SQL is generated and run
        $this->setup_sql_generator();
        $this->setup_mdb2();

        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();

        // Items have had facet added
        $this->assertEqual(1, $r['data'][0]['facet_details']);
        }
    }
    
