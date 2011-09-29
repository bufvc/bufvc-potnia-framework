<?php
// $Id$
// Tests for Query class
// James Fryer, 24 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('DataSource');
Mock::generate('Module');
Mock::generate('Query');
Mock::generate('QueryCriteriaCache');
Mock::generate('QueryDataSourceEncoder');
Mock::generate('QueryFactoryUtil');

class QueryFactoryTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->module = new MockModule();
        $this->factory = new QueryFactory($this->module);
        }
    
    function test_guess_config_default()
        {
        $this->module->query_config = Array(
            'filters' => 'filters',
            );
        $this->factory->util = new MockQueryFactoryUtil();
        $this->factory->util->expectOnce('get_default_table');
        $this->factory->util->setReturnValue('get_default_table', 'table_name');
        $this->factory->util->expectOnce('get_criteria_defs', Array('table_name'));
        $this->factory->util->setReturnValue('get_criteria_defs', 'criteria_defs');
        $this->factory->util->expectOnce('get_query_lists', Array('table_name'));
        $this->factory->util->setReturnValue('get_query_lists', 'query_lists');
        $r = $this->factory->guess_config();
        $expected = Array(
            'table_name' => 'table_name',
            'criteria_defs' => 'criteria_defs',
            'query_lists' => 'query_lists',
            'filters' => 'filters',
            );
        $this->assertEqual($expected, $r);
        }
        
    function test_guess_config_with_helper_classes() 
        {
        $this->module->query_config = Array(
            'table_name' => 'table_name',
            'filters' => 'filters',
            'cache_class' => 'cache_class',
            'encoder_class' => 'encoder_class',
            );
        $this->factory->util = new MockQueryFactoryUtil();
        $this->factory->util->expectNever('get_default_table');
        $this->factory->util->expectOnce('get_criteria_defs', Array('table_name'));
        $this->factory->util->expectOnce('get_query_lists', Array('table_name'));
        $r = $this->factory->guess_config();
        $expected = Array(
            'table_name' => 'table_name',
            'filters' => 'filters',
            'cache_class' => 'cache_class',
            'encoder_class' => 'encoder_class',
            );
        $this->assertEqual($expected, $r);
        }
        
    function test_guess_config_with_override()
        {
        $this->module->query_config = Array(
            'filters' => 'b',
            );
        $config_override = Array(
            'table_name' => 'table_name',
            'criteria_defs' => 'criteria_defs',
            'query_lists' => 'query_lists',
            'filters' => 'filters',
            );
        $this->factory->util = new MockQueryFactoryUtil();
        $this->factory->util->expectNever('get_default_table');
        $this->factory->util->expectNever('get_criteria_defs');
        $this->factory->util->expectNever('get_query_lists');
        $r = $this->factory->guess_config($config_override);
        $expected = Array(
            'table_name' => 'table_name',
            'criteria_defs' => 'criteria_defs',
            'query_lists' => 'query_lists',
            'filters' => 'filters',
            );
        $this->assertEqual($expected, $r);
        }        
        
    function test_guess_config_filters()
        {
        global $CONF;
        $this->module->query_config = Array(
            'table_name' => 'prevent_call_to_util',
            'criteria_defs' => 'prevent_call_to_util',
            'query_lists' => 'prevent_call_to_util',
            'filters' => Array('module', 'filters'),
            );
            
        // Filters set from query config
        $r = $this->factory->guess_config();
        $expected = Array('module', 'filters');
        $this->assertEqual($expected, $r['filters']);
        
        // Filters from query merged with filters in $CONF
        $CONF['query_filters'] = Array('global', 'filters');
        $r = $this->factory->guess_config();
        $expected = Array('global', 'filters', 'module', 'filters');
        $this->assertEqual($expected, $r['filters']);

        // Filters from $CONF used only
        unset($this->module->query_config['filters']);
        $r = $this->factory->guess_config();
        $expected = Array('global', 'filters');
        $this->assertEqual($expected, $r['filters']);

        // Clean up
        unset($CONF['query_filters']);
        }

    function test_new_query()
        {
        $this->factory->configure(Array('table_name'=>'foo'));
        $query = $this->factory->new_query();
        $this->assertEqual('query', strtolower(get_class($query)));
        $this->assertEqual('foo', $query->table_name);
        $this->assertIdentical($this->module, $query->module);
        }

    function test_new_query_adds_criteria_lists()
        {
        $this->factory->configure(Array('criteria_defs'=>Array(
            Array('name' => 'x', 'type'=>QC_TYPE_LIST, 'list'=>Array('foo')),
            )));
        $query = $this->factory->new_query();
        $this->assertEqual(Array('foo'), $query->get_list('x'));
        }
        
    function test_configure_defaults()
        {
        // Default is new-style config
        $config = $this->factory->get_config();
        $this->assertEqual('QueryCriteriaCache', $config['cache_class']);
        $this->assertEqual('QueryDataSourceEncoder', $config['encoder_class']);
        }
        
    function test_default_page_size()
        {
        // a page_size criterion will be added if one is not specified
        $this->factory->configure(Array('criteria_defs'=>Array(
            Array('name' => 'x', 'type'=>QC_TYPE_LIST, 'list'=>Array('foo')),
            )));
        $query = $this->factory->new_query();
        
        $this->assertNotNull( $query['page_size'] ); 
        $this->assertEqual( 10, $query['page_size']->get_value() );
        }
        
    function test_no_default_page_size()
        {
        $this->factory->configure( Array(
            'is_page_size_mandatory'=>FALSE,
            'criteria_defs'=>Array(
                Array('name' => 'x', 'type'=>QC_TYPE_LIST, 'list'=>Array('foo')),
            )));
        $query = $this->factory->new_query();
        $this->assertNull( $query['page_size'] ); 
        }
  
    function test_table_name_set()
        {
        $this->factory->configure(Array('table_name'=>'foo'));
        $query = $this->factory->new_query();
        $this->assertEqual('foo', $query->table_name);
        }

    function test_cache_instantiated()
        {
        $this->factory->configure(Array('cache_class'=>'MockQueryCriteriaCache'));
        $query = $this->factory->new_query();
        $this->assertEqual('mockquerycriteriacache', strtolower(get_class($query->_cache)));
        }

    function test_encoder_instantiated()
        {
        $this->factory->configure(Array('encoder_class'=>'MockQueryDataSourceEncoder'));
        $query = $this->factory->new_query();
        $this->assertEqual('mockquerydatasourceencoder', strtolower(get_class($query->_encoder)));
        }

    function test_filters_set()
        {
        // Class names will be instantiated if they exist
        $this->factory->configure(Array('filters'=>Array(new stdClass(), 'stdClass', 'NotFound')));
        $query = $this->factory->new_query();
        $this->assertEqual(Array(new stdClass(), new stdClass()), $query->filters);
        }

    function test_lists_set()
        {
        $this->factory->configure(Array('query_lists'=>Array('foo'=>'bar')));
        $query = $this->factory->new_query();
        $this->assertEqual('bar', $query->get_list('foo'));
        }

    function test_new_style_criteria_set()
        {
        // Accepts array or criterion
        $this->factory->configure(Array('criteria_defs'=>Array(
            Array('name' => 'x'),
            QueryCriterionFactory::create(Array('name' => 'y'))
            )));        
        $query = $this->factory->new_query(Array('x'=>'foo', 'y'=>'bar'));
        $this->assertEqual('foo', $query['x']->get_value());
        $this->assertEqual('bar', $query['y']->get_value());
        }
        
    //### Experimental, not yet sure if session handling belongs here 
    function test_session_handling()
        {
        $module = new DummyModule();
        $util = new MockQueryFactoryUtil();
        $util->setReturnValue('get_default_table', 'tbl');

        // Clear old session data
        unset($_SESSION['QUERY']);
        unset($_SESSION['QUERY/tbl/dummy']);
        
        // Creates default query if none was there
        $query = QueryFactory::get_session_query($module, FALSE, NULL, $util);
        $this->assertEqual('query', strtolower(get_class($query)));
        $this->assertEqual($module->name, $query->module->name);

        // Also creates with ignore_module_session
        unset($_SESSION['QUERY']);
        unset($_SESSION['QUERY/tbl/dummy']);
        $module = new DummyModule();
        $query = QueryFactory::get_session_query($module, TRUE, NULL, $util);
        $this->assertEqual('query', strtolower(get_class($query)));
        $this->assertEqual($module->name, $query->module->name);

        // Set "queries" in session for global and module
        $_SESSION['QUERY'] = 'foo';
        $_SESSION['QUERY/tbl/dummy'] = 'bar';
        
        // Use flag, get the default query
        $query = QueryFactory::get_session_query($module, TRUE, NULL, $util);
        $this->assertEqual('foo', $query);
        
        // Module specified, get query for that module
        $query = QueryFactory::get_session_query($module, FALSE, NULL, $util);
        $this->assertEqual('bar', $query);
        
        // Save the query to the session
        $query = new MockQuery();
        $query->table_name = 'test';
        QueryFactory::set_session_query($module, $query);
        $this->assertEqual($query, $_SESSION['QUERY/test/dummy']);
        $this->assertEqual($query, $_SESSION['QUERY']);

        // Get a query with an alternate table
        $_SESSION['QUERY/alt/dummy'] = 'quux';
        $query = QueryFactory::get_session_query($module, FALSE, 'alt', $util);
        $this->assertEqual('quux', $query);
        
        // Save a query with an alternate table
        $query = new MockQuery();
        $query->table_name = 'test2';
        QueryFactory::set_session_query($module, $query, $util);
        $this->assertEqual($query, $_SESSION['QUERY/test2/dummy']);
        $this->assertEqual($query, $_SESSION['QUERY']);
        
        // Create a query with an alternate table
        unset($_SESSION['QUERY']);
        unset($_SESSION['QUERY/test2/dummy']);
        $query = QueryFactory::get_session_query($module, TRUE, 'test2', $util);
        $this->assertEqual('query', strtolower(get_class($query)));
        $this->assertEqual('test2', $query->table_name);
        }
    }

class QueryFactoryUtilTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->module = new MockModule();
        $this->util = new QueryFactoryUtil($this->module);
        $this->ds = new MockDataSource();
        $this->module->setReturnValue('get_datasource', $this->ds);
        }

    function test_get_default_table()
        {
        $this->module->expectOnce('list_query_tables');
        $this->module->setReturnValue('list_query_tables', Array('foo'=>'', 'bar'=>''));
        $r = $this->util->get_default_table();
        $this->assertEqual('foo', $r);
        }

    function test_get_criteria_defs()
        {
        $this->ds->expectOnce('retrieve', Array('/table_name'));
        $this->ds->setReturnValue('retrieve', Array('query_criteria'=>'criteria defs'));
        $r = $this->util->get_criteria_defs('table_name');
        $this->assertEqual('criteria defs', $r);
        }

    function test_get_query_lists()
        {
        // Checks in global DS query lists before table
        $this->ds->expectCallCount('retrieve', 2);
        $this->ds->setReturnValue('retrieve', Array('A'=>'a', 'B'=>'b', 'C'=>'overwritten'), Array('/query_lists'));
        $this->ds->setReturnValue('retrieve', Array('query_lists'=>Array('C'=>'c', 'D'=>'d')), Array('/table_name'));
        $r = $this->util->get_query_lists('table_name');
        $this->assertEqual(Array('A'=>'a', 'B'=>'b', 'C'=>'c', 'D'=>'d'), $r);
        }
    }
