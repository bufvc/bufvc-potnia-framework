<?php
// $Id$
// Tests for Aggregate Storage
// James Fryer, 7 July 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('AggregateStorageSearchBuffer', 'MockBuffer');
Mock::generate('DataSource');

class AggregateStorageSearchBufferTestCase
    extends UnitTestCase
    {
    function test_search()
        {
        $buffer = new AggregateStorageSearchBuffer();
        $ds = new MockDataSource();
        $comp = Array(
            'ds' => $ds, 
            'table'=>'table',
            );
        $ds->expectOnce('search', Array('table', 'query', 1, 2));
        $buffer->search('comp_name', $comp, 'query', 1, 2);
        }

    function test_get_results()
        {
        $buffer = new AggregateStorageSearchBuffer();
        $ds = new MockDataSource();
        $ds->setReturnValueAt(0, 'search', 'foo');
        $ds->setReturnValueAt(1, 'search', 'bar');
        $comp1 = Array('ds' => $ds, 'table'=>'table1');
        $comp2 = Array('ds' => $ds, 'table'=>'table2');
        $buffer->search('cn1', $comp1, 'query1', 1, 2);
        $buffer->search('cn2', $comp2, 'query2', 3, 4);
        $expected = Array(
            Array('comp_name'=>'cn1', 'comp'=>$comp1, 'results'=>'foo'),
            Array('comp_name'=>'cn2', 'comp'=>$comp2, 'results'=>'bar'),
            );
        $this->assertEqual($expected, $buffer->get_results());
        }
    }

class AggregateStorageTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $module = Module::load('dummy');
        $this->mock_ds1 = new MockDataSource();
        $this->mock_ds2 = new MockDataSource();
        $this->config = Array(
            'test' => Array(
                'title'=>'Test',
                'description'=>'Test table',
                'storage'=> 'aggregate',
                'components' => Array(
                    'X' => Array(
                        'ds' => $this->mock_ds1,
                        'table' => '/test1',
                        'icon' => 'icon_override',
                        // Configured with module
                        'module' => $module,
                        ),
                    'Y' => Array(
                        'ds' => $this->mock_ds2,
                        'table' => '/test2',
                        'title' => 'ds2',
                        'description' => 'descr 2',
                        'icon' => 'icon2',
                        // Configured with module name + data
                        'module' => $module->name,
                        'query_add' => '{extra_query_from_config}',
                        'weight'=>2.0,
                        // use adaptor
                        'adaptor' => Array(
                            Array(
                                'index' => 'adaptor',
                                'value' => 'replace',
                                'query_string' => '{adaptor_test=1}',
                                ),
                            Array(
                                'index' => 'adaptor',
                                'value' => 'all',
                                'query_string' => '',
                                ),
                            Array(
                                'index' => 'adaptor',
                                'value' => 'ignore',
                                'query_string' => NULL,
                                ),
                            ),
                        ),
                    ),
                ),
            );
        $this->ds = new DataSource($this->config);
        $this->storage = new DataSource_AggregateStorage();
        }

    function test_buffer_is_called_by_storage()
        {
        $parser = new SimpleQueryParser();
        // This is the only mock test for the buffer, all other tests assume the default buffer is used
        $this->storage->buffer = new MockBuffer();
        $this->storage->buffer->expectAt(0, 'search', Array('X', $this->config['test']['components']['X'], '*', 0, 100));
        $this->storage->buffer->expectAt(1, 'search', Array('Y', $this->config['test']['components']['Y'], '*', 0, 100));
        $this->storage->buffer->expectCallCount('search', 2);
        $this->storage->buffer->expectOnce('get_results');
        $this->storage->buffer->setReturnValue('get_results', Array());
        $this->storage->search($this->ds, '/test', $parser->parse('query'), 0, 100);
        }

    function test_search_calls_all_components()
        {
        // Each DS is called in turn
        $this->mock_ds1->expectOnce('search', Array('/test1', '{default=query}', 0, 100));
        $this->mock_ds2->expectOnce('search', Array('/test2', '{default=query}({extra_query_from_config})', 0, 100));
        $this->ds->search('/test', 'query', 0, 100);
        }

    function test_search_with_component()
        {
        // Single DS is specified
        $this->mock_ds1->expectOnce('search', Array('/test1', '{a=b}', 0, 100));
        $this->mock_ds2->expectNever('search');
        $this->ds->search('/test', '{a=b}{components=X}', 0, 100);
        }

    function test_search_with_numeric_component()
        {
        // Single DS is specified
        $this->mock_ds1->expectOnce('search', Array('/test1', '', 0, 100));
        $this->mock_ds2->expectNever('search');
        $this->ds->search('/test', '{components=0}', 0, 100);
        }

    function test_search_with_component_list()
        {
        // Each named DS is called in turn
        $this->mock_ds1->expectOnce('search', Array('/test1', '', 0, 100));
        $this->mock_ds2->expectOnce('search', Array('/test2', '({extra_query_from_config})', 0, 100));
        $this->ds->search('/test', 'components=X,Y', 0, 100);
        }

    function test_search_with_numeric_component_list()
        {
        // Each named DS is called in turn
        $this->mock_ds1->expectOnce('search', Array('/test1', '', 0, 100));
        $this->mock_ds2->expectOnce('search', Array('/test2', '({extra_query_from_config})', 0, 100));
        $this->ds->search('/test', 'components=0,1', 0, 100);
        }
    
    function test_search_with_adaptor_replace()
        {
        $this->mock_ds1->expectOnce('search', Array('/test1', '{adaptor=replace}', 0, 100));
        $this->mock_ds2->expectOnce('search', Array('/test2', '{adaptor_test=1}({extra_query_from_config})', 0, 100));
        $this->ds->search('/test', '{adaptor=replace}', 0, 100);
        }
    
    function test_search_adaptor_all()
        {
        $this->mock_ds1->expectOnce('search', Array('/test1', '{adaptor=all}', 0, 100));
        $this->mock_ds2->expectOnce('search', Array('/test2', '({extra_query_from_config})', 0, 100));
        $this->ds->search('/test', '{adaptor=all}', 0, 100);
        }
    
    function test_search_adaptor_ignore()
        {
        $this->mock_ds1->expectOnce('search', Array('/test1', '{adaptor=ignore}', 0, 100));
        $this->mock_ds2->expectNever('search');
        $this->ds->search('/test', '{adaptor=ignore}', 0, 100);
        }

    // Search offset is normalised to 0 if 0<=offset<=max_count
    function test_offset_first_page()
        {
        $this->mock_ds1->expectOnce('search', Array('/test1', '*', 0, 2));
        $this->mock_ds2->expectOnce('search', Array('/test2', '*', 0, 2));
        $this->ds->search('/test', 'query', 1, 2);
        }

    // Search offset is normalised to max_count if max_count<=offset<=2*max_count
    function test_offset_later_page()
        {
        $this->mock_ds1->expectOnce('search', Array('/test1', '*', 6, 3));
        $this->mock_ds2->expectOnce('search', Array('/test2', '*', 6, 3));
        $this->ds->search('/test', 'query', 8, 3);
        }
    
    function test_config_expansion()
        {
/* ###
        // This is not currently possible with the way aggregate storage
        // is implemented. The storage handler has no opportunity to
        // change the DS configuration. Adding this capability would
        // remove the need for various module loads.
        $module = Module::load('dummy');
        // Module is expanded in configuration
        $expected_components = Array(
            '1' => Array(
                'ds' => $this->mock_ds1,
                'table' => '/test1',
                'title' => 'Dummy Module', // Gets title from module
                'description' => $module->description,
                'icon' => 'icon_override', // Items defined in config override module
                'module' => $module,
                ),
            '2' => Array(
                'ds' => $this->mock_ds2,
                'table' => '/test2',
                'title' => 'ds2',
                'description' => 'descr 2',
                'icon' => 'icon2',
                'module' => $module, // Module is looked up
                ),
            );
        $meta = $this->ds->retrieve('/test');
        $this->assertEqual($meta['components'], $expected_components);
###*/
        }

    // If count<=requested, return 'exact'
    function test_search_exact()
        {
        $result1 = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    ),
                ),
            'offset' => 0,
            'count' => 1,
            'total'=> 1,
            'accuracy' => 'exact',
            );
        $this->mock_ds1->setReturnValue('search', $result1);
        $result2 = Array(
            'total'=> 0,
            );
        $this->mock_ds2->setReturnValue('search', $result2);

        $module = Module::load('dummy');
        $expected_components = Array();
        $expected_components[0] = array_merge($this->config['test']['components']['X'], 
                Array('name'=>'X', 'module'=>$module, 'total'=>1, 'accuracy' => 'exact', ));
        $expected = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    'module' => $module,
                    '_weight' => 1.0,
                    ),
                ),
            'offset' => 0,
            'count' => 1,
            'total'=> 1,
            'accuracy' => 'exact',
            'components' => $expected_components,
            );
        $r = $this->ds->search('/test', 'query', 0, 2);
        $this->assertEqual($r, $expected);
        }

    // Search merges and trims results, adds summaries
    // If count>requested, return 'exceeds'
    function test_search_exceeds()
        {
        $module = Module::load('dummy');

        $result1 = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    'foo' => 'Other fields copied',
                    ),
                Array(
                    'url' => '/test1/c',
                    '_sort' => 'C',
                    ),
                ),
            'offset' => 10,
            'count' => 2,
            'total'=> 3,
            'accuracy' => 'exact',
            );
        $this->mock_ds1->setReturnValue('search', $result1);
        $result2 = Array(
            'data' => Array(
                Array(
                    'url' => '/test2/b',
                    '_sort' => 'B',
                    ),
                Array(
                    'url' => '/test2/d',
                    '_sort' => 'D',
                    ),
                ),
            'offset' => 10,
            'count' => 2,
            'total'=> 100,
            'accuracy' => 'exact',
            );
        $this->mock_ds2->setReturnValue('search', $result2);

        $expected_components = Array();
        $expected_components[0] = array_merge($this->config['test']['components']['X'], 
                Array('name'=>'X', 'module'=>$module, 'total'=>3, 'accuracy' => 'exact', ));
        $expected_components[1] = array_merge($this->config['test']['components']['Y'], 
                Array('name'=>'Y', 'module'=>$module, 'total'=>100, 'accuracy' => 'exact', ));
        $expected = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    'foo' => 'Other fields copied',
                    'module' => $module,
                    '_weight' => 1.0,
                    ),
                Array(
                    'url' => '/test2/b',
                    '_sort' => 'B',
                    'module' => $module,
                    '_weight' => 2.0,
                    ),
                ),
            'offset' => 0,
            'count' => 2,
            'total'=> 2,
            'accuracy' => 'exceeds',
            'components' => $expected_components,
            );
        $r = $this->ds->search('/test', 'query', 0, 2);
        $this->assertEqual($r, $expected);
        }
        
    function test_search_uses_total_found_if_avail()
        {
        $result1 = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    ),
                ),
            'offset' => 0,
            'count' => 1,
            'total'=> 1,
            'total_found'=> 100,
            'accuracy' => 'exact',
            );
        $this->mock_ds1->setReturnValue('search', $result1);
        $result2 = Array(
            'total'=> 0,
            );
        $this->mock_ds2->setReturnValue('search', $result2);

        $module = Module::load('dummy');
        $expected_components = Array();
        $expected_components[0] = array_merge($this->config['test']['components']['X'], 
                Array('name'=>'X', 'module'=>$module, 
                    'total'=>100,  // This is what we want to see
                    'accuracy' => 'exact', ));
        $expected = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    'module' => $module,
                    '_weight' => 1.0,
                    ),
                ),
            'offset' => 0,
            'count' => 1,
            'total'=> 1,
            'accuracy' => 'exact',
            'components' => $expected_components,
            );
        $r = $this->ds->search('/test', 'query', 0, 2);
        $this->assertEqual($r, $expected);
        }
    
    function test_search_with_restricted()
        {
        $this->config['test']['components']['X']['restricted'] = TRUE;
        $this->ds = new DataSource($this->config);
        $result1 = Array(
            'data' => Array(
                Array(
                    'url' => '/test1/a',
                    '_sort' => 'A',
                    ),
                ),
            'offset' => 0,
            'count' => 1,
            'total'=> 1,
            'accuracy' => 'exact',
            );
        $this->mock_ds1->setReturnValue('search', $result1);
        $r = $this->ds->search('/test', 'query', 0, 2);
        // the total is 0 but the total of the component has been set from 'total_found'
        $this->assertEqual($r['data'], Array());
        $this->assertEqual($r['count'], 0);
        $this->assertEqual($r['total'], 0);
        $this->assertEqual($r['components'][0]['total'], 1);
        }

    function test_search_aggregates_facets()
        {
        $result1 = Array(
            'data' => Array(),
            'offset' => 0,
            'count' => 5,
            'total'=> 5,
            'accuracy' => 'exact',
            'facets'=>Array(
                'group1' => Array(
                    'type1' => 3,
                    'type2' => 1,
                    ),
                'group2' => Array(
                    'type3' => 2,
                    ),
                'accuracy' => 'exact',
                ),
            );
        $this->mock_ds1->setReturnValue('search', $result1);
        $result2 = Array(
            'data' => Array(),
            'offset' => 0,
            'count' => 3,
            'total'=> 3,
            'accuracy' => 'exact',
            'facets'=>Array(
                'group1' => Array(
                    'type1' => 2,
                    ),
                'group2' => Array(
                    'type3' => 1,
                    ),
                'accuracy' => 'approx',
                ),
            );
        $this->mock_ds2->setReturnValue('search', $result2);
        $r = $this->ds->search('/test', 'query', 0, 2);
        $expected = Array(
            'group1' => Array(
                'type1' => 5,
                'type2' => 1,
                ),
            'group2' => Array(
                'type3' => 3,
                ),
            'accuracy' => 'approx',
            );
        $this->assertEqual($r['facets'], $expected);
        }

    function test_default_sort()
        {
        $this->setup_sort_test('default', Array('type'=>'asc'), 
                Array(
                    Array(
                        'url' => '/test1/a',
                        '_sort' => 0.5,
                        ),
                    ),
                Array(
                    Array(
                        'url' => '/test2/b',
                        '_sort' => 1.0,
                        ),
                    )
                );
        $module = Module::load('dummy');
        $expected_data = Array(
            Array(
                'url' => '/test1/a',
                '_sort' => 0.5,
                'module' => $module,
                '_weight' => 1.0,
                ),
            Array(
                'url' => '/test2/b',
                '_sort' => 1.0,
                'module' => $module,
                '_weight' => 2.0,
                ),
            );
        $r = $this->ds->search('/test', '', 0, 1000);
        $this->assertEqual($r['data'], $expected_data);
        }

    function test_descending_sort()
        {
        $this->setup_sort_test('foo', Array('type'=>'desc'), 
                Array(
                    Array(
                        'url' => '/test1/a',
                        '_sort' => 0.5,
                        ),
                    ),
                Array(
                    Array(
                        'url' => '/test2/b',
                        '_sort' => 1.0,
                        ),
                    )
                );
        $module = Module::load('dummy');
        $expected_data = Array(
            Array(
                'url' => '/test2/b',
                '_sort' => 1.0,
                'module' => $module,
                '_weight' => 2.0,
                ),
            Array(
                'url' => '/test1/a',
                '_sort' => 0.5,
                'module' => $module,
                '_weight' => 1.0,
                ),
            );
        $r = $this->ds->search('/test', 'sort=foo', 0, 1000);
        $this->assertEqual($r['data'], $expected_data);
        }

    function test_relevance_sort()
        {
        // Relevance sort does two things:
        // 1. Apply module's 'weight' config
        // 2. Take one record from each module and put it at the start of the list
        $this->setup_sort_test('foo', Array('type'=>'rel'), 
                Array(
                    Array(
                        'url' => '/test1/a',
                        '_sort' => 2.0,         // Will be first
                        ),
                    Array(
                        'url' => '/test1/c',
                        '_sort' => 1.0,         // Will be last
                        ),
                    ),
                Array(
                    Array(
                        'url' => '/test2/b',
                        '_sort' => 0.1, // Will be prioritised regardless of value
                        ),
                    Array(
                        'url' => '/test2/d',
                        '_sort' => 1.5, // Will be 3.0 when weight is considered so will sort above /test1/c
                        ),
                    )
                );
        $module = Module::load('dummy');
        $expected_data = Array(
            Array(
                'url' => '/test1/a',
                '_sort' => 2.0,
                'module' => $module,
                '_weight' => 1.0,
                ),
            Array(
                'url' => '/test2/b',
                '_sort' => 0.1,
                'module' => $module,
                '_weight' => 2.0,
                ),
            Array(
                'url' => '/test2/d',
                '_sort' => 1.5,
                'module' => $module,
                '_weight' => 2.0,
                ),
            Array(
                'url' => '/test1/c',
                '_sort' => 1.0,
                'module' => $module,
                '_weight' => 1.0,
                ),
            );
        $r = $this->ds->search('/test', 'sort=foo', 0, 1000);
        $this->assertEqual($r['data'], $expected_data);
        }

    function test_relevance_sort_with_restricted()
        {
        $this->config['test']['components']['X']['restricted'] = TRUE;
        $this->ds = new DataSource($this->config);
        $this->setup_sort_test('foo', Array('type'=>'rel'), 
                Array(
                    Array(
                        'url' => '/test1/a', // Will be removed
                        '_sort' => 2.0,     
                        ),
                    ),
                Array(
                    Array(
                        'url' => '/test2/b',
                        '_sort' => 0.1, 
                        ),
                    )
                );
        $module = Module::load('dummy');
        $expected_data = Array(
            Array(
                'url' => '/test2/b',
                '_sort' => 0.1,
                'module' => $module,
                '_weight' => 2.0,
                ),
            );
        $r = $this->ds->search('/test', 'sort=foo', 0, 1000);
        $this->assertEqual($r['data'], $expected_data);
        }

    function setup_sort_test($sort_name, $sort, $data1, $data2)
        {
        $result_base = Array(
            'offset' => 0,
            'accuracy' => 'exact',
            );
        $result1 = $result_base;
        $result1['count'] = $result1['total'] = count($data1);
        $result1['data'] = $data1;
        $this->mock_ds1->setReturnValue('search', $result1);
        $this->mock_ds1->expectOnce('retrieve', Array('/test1'));
        $this->mock_ds1->setReturnValue('retrieve', Array('search'=>Array('index'=>Array('sort.' . $sort_name=>$sort))));
        $result2 = $result_base;
        $result2['count'] = $result2['total'] = count($data2);
        $result2['data'] = $data2;
        $this->mock_ds2->setReturnValue('search', $result2);
        }

    function test_crud_not_supported()
        {
        $this->assertNull($this->storage->create($this->ds, '/test', Array('title'=>'bar')));
        $this->assertNull($this->storage->retrieve($this->ds, '/test/bar'));
        $this->assertNull($this->storage->update($this->ds, '/test/bar', Array('title'=>'bar')));
        $this->assertNull($this->storage->delete($this->ds, '/test/bar'));
        }
    }
