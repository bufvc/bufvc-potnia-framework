<?php
// $Id$
// Tests for DataSource class
// James Fryer, 11 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('DataSource');
Mock::generate('DataSource_MemoryStorage', 'MockStorage');
Mock::generate('_DataSourceStorageFactory', 'MockStorageFactory');
Mock::generate('QueryParserFactory');
Mock::generate('SimpleQueryParser');

class DataSourceStorageFactoryTestCase
    extends UnitTestCase
    {
    function test_new_storage()
        {
        $ds = new MockDataSource();
        $factory = new _DataSourceStorageFactory($ds);
        $this->assertNull($factory->new_storage('foobar'));
        $storage = $factory->new_storage('memory');
        $this->assertEqual(strtolower(get_class($storage)), 'datasource_memorystorage');
        $storage = $factory->new_storage('mysql');
        $this->assertEqual(strtolower(get_class($storage)), 'datasource_mysqlstorage');
        }
    }

class SearchErrorMockStorage
    extends MockStorage
    {
    function search(&$ds, $table, $tree, $offset, $max_count)
        {
        return $ds->_set_error(400, 'err');
        }
    }
    
// Test the DS interface passes correct calls to storage layer
class DataSourceTestBase
    extends UnitTestCase
    {
    var $config = Array(
        'test' => Array(
            'title'=>'Test',
            'description'=>'Test table',
            'mutable'=>TRUE,
            'storage'=> 'mock',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                ),
            ),
        'second_test' => Array(
            'title'=>'TestToo',
            'description'=>'Test table',
            'mutable'=>TRUE,
            'storage'=>'mock',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                ),
            ),
        );
        
    function setup($config=NULL, $extra_setup_function=NULL, $arg=NULL)
        {
        if ($config == NULL)
            $config = $this->config;
        // Return mock storage for all requests except memory
        $this->storage = new MockStorage();
        $this->storage_factory = new MockStorageFactory();
        $this->storage_factory->setReturnReference('new_storage', new DataSource_MemoryStorage(), Array('memory'));
        $this->storage_factory->setReturnReference('new_storage', $this->storage, Array('mock'));
        if (!is_null($extra_setup_function))
            $extra_setup_function($this, $arg);
        $this->ds = new DataSource($config, $this->storage_factory);
        }

    function assertError($status)
        {
        $this->assertEqual($this->ds->error_code, $status, 'error_code: %s');
        $this->assertTrue($this->ds->error_message != '', 'missing error_message');
        }

    function assertNoError()
        {
        $this->assertTrue($this->ds->error_code == 0, 'error_code: ' . $this->ds->error_code);
        $this->assertTrue($this->ds->error_message == '', 'error_message: ' . $this->ds->error_message);
        }

    function assertResults($r, $offset, $count, $total, $accuracy='exact')
        {
        $this->assertEqual($r['count'], $count, 'count: %s');
        $this->assertEqual(count($r['data']), $r['count'], 'inconsistent count: %s');
        $this->assertEqual($r['offset'],  $offset, 'offset: %s');
        $this->assertEqual($r['total'], $total, 'total: %s');
        $this->assertEqual($r['accuracy'], $accuracy, 'accuracy: %s');
        }
    }
    
class DataSourceMetaTestCase
    extends DataSourceTestBase
    {
    var $config_includes_query_criteria = Array(
        'test' => Array(
            'title'=>'Test',
            'description'=>'Test table',
            'mutable'=>TRUE,
            'storage'=> 'mock',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                ),
            'query_criteria' => Array(
                Array(
                    'name' => 'q',
                    'label' => 'Search',
                    'render_default' => 'All records',
                    'list' => 'list_search',
                    ), // query
                Array(
                    'name' => 'text',
                    'label' => 'Search',
                    ), // query
                Array(
                    'name' => 'sort',
                    'type' => QC_TYPE_SORT,
                    'list' => 'sort',
                    'is_renderable' => FALSE,
                    ), // sort by
                ),
            'query_lists' => Array(
                'list_search' => Array(
                    ''=>'All fields', 
                    'title'=>'Title Only'
                    ),
                ),
            ),
        'query_lists' => Array(
             'title' => 'Lists',
             'storage' => 'mock',
             'description' =>'DS level list definitions',
             'sort' => Array(
                 ''=>'Date (oldest first)', 
                 'date'=>'Date (newest first)', 
                 'title'=>'Title'
                 ),
             ),
        'second_test' => Array(
            'title'=>'TestToo',
            'description'=>'Test table',
            'mutable'=>TRUE,
            'storage'=>'mock',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                ),
            ),
        );

    function test_default_error_code()
        {
        // Default settings
        $this->assertTrue($this->ds->error_code == 0);
        $this->assertTrue($this->ds->error_message == '');
        }

    function test_is_query_criteria_defined()
        {
        $record = $this->ds->retrieve('/meta');
        $this->assertFalse($record['query_criteria_defined']);        
        $this->ds = new DataSource($this->config_includes_query_criteria, $this->storage_factory);        
        $record = $this->ds->retrieve('/meta');
        $this->assertTrue($record['query_criteria_defined']);
        }        

    function test_meta_search()
        {
        $r = $this->ds->search('meta', '', 0, 1000);
        $this->assertNoError();
        // Will return the number of configured tables plus /meta itself
        $expected = count($this->config) + 1;
        $this->assertResults($r, 0, $expected, $expected);
        $this->assertTrue($r['data'][0]['url'] == '/meta');
        $this->assertTrue($r['data'][0]['title'] == 'Meta-data');
        $this->assertTrue($r['data'][1]['url'] == '/test');
        $this->assertTrue($r['data'][1]['title'] == 'Test');
        }

    function test_retrieve_meta()
        {
        // Meta table
        $record = $this->ds->retrieve('/meta');
        $this->assertNoError();
        $this->assertTrue($record['title'] == 'Meta-data');
        $this->assertTrue($record['url'] == '/meta');
        $this->assertTrue($record['_table'] == 'meta');
        $this->assertTrue($record['key'] == 'meta');
        $this->assertFalse($record['mutable']);
        // Works without leading slash
        $record = $this->ds->retrieve('meta');
        $this->assertNoError();

        // Test table
        $record = $this->ds->retrieve('/test');
        $this->assertNoError();
        $this->assertTrue($record['title'] == 'Test');
        $this->assertTrue($record['url'] == '/test');
        $this->assertTrue($record['_table'] == 'meta');
        $this->assertTrue($record['key'] == 'test');
        $this->assertTrue($record['mutable']);
        $record = $this->ds->retrieve('test');
        $this->assertNoError();
        }
        
    function test_meta_list_table_names()
        {
        $initial_table_count = count($this->config) + 1;
        
        // retrieve the meta table
        $record = $this->ds->retrieve('/meta');
        
        $this->assertNoError();
        $this->assertNotNull( $record['names'] );
        
        // there should be the number of tables + the meta table
        $this->assertTrue( count($record['names']) == $initial_table_count,
            "expected " . ($initial_table_count) . " names; is actually " . count($record['names']) );
        
        // check the table names match
        $this->assertTrue( $record['names'][0] == 'meta' );
        $this->assertTrue( $record['names'][1] == 'test' );
        $this->assertTrue( $record['names'][2] == 'second_test' );
        }        
    }
    
class DataSourceCreateTestCase
    extends DataSourceTestBase
    {
    function test_create()
        {
        $input = Array(
            'slug' => "A Slug",
            'title' => "A test item",
            'description' => "Test description",
            );
        // Record passed to storage is modified:
        // Slug removed, _table added, URL added
        //### FIXME: storage should not be passed URL in args and data!
        //### FIXME: not sure about _table either...
        $expected_record = $input;
        unset($expected_record['slug']);
        $expected_record['_table'] = 'test';
        $expected_record['url'] = '/test/a_slug';
        // Create calls 'create' then 'retrieve' with the returned URL
        $this->storage->setReturnValue('create', 'created_url', Array('*', '/test/a_slug', $expected_record));
        $this->storage->setReturnValue('retrieve', 'returned_data', Array('*', 'created_url'));
        $this->storage->expectOnce('create');
        $this->storage->expectOnce('retrieve');
        $this->assertEqual($this->ds->create('/test', $input), 'returned_data');
        $this->assertNoError();
        }

    // It's OK to write 'test' not '/test'
    function test_create_alt_meta()
        {
        $input = Array(
            'slug' => "slug",
            'title' => "Test",
            );
        // Create returns the result of the storage call
        $this->storage->expectOnce('create', Array('*', '/test/slug', '*'));
        $this->storage->setReturnValue('create', 'created_url');
        $this->ds->create('test', $input);
        $this->assertNoError();
        }

    // Need required field to create a record
    function test_create_missing_fields()
        {
        $record = $this->ds->create('/test', Array());
        $this->assertError(400);
        $this->assertNull($record);
        }

    // Can't create in RO tables
    function test_create_read_only()
        {
        $record = $this->ds->create('/meta', Array('title'=>'test'));
        $this->assertError(405);
        $this->assertNull($record);
        }

    function test_create_bad_meta()
        {
        $record = $this->ds->create('/badmeta', Array('title'=>'test'));
        $this->assertError(404);
        }

    function test_create_slug_fixup()
        {
        // Create a new item, make sure slug is handled correctly
        $wart = rand();
        $input = Array(
            'slug' => "$\"!S'luG 1[2]_3.:/\\()*&=-" . $wart,
            'title' => "test",
            );
        // Spaces are converted to underscore
        // Other non-alnum chars are removed, except hyphen
        // Slug forced to lower-case
        $expected_url = '/test/slug_12_3./-' . $wart;
        $this->storage->expectOnce('create', Array('*', $expected_url, '*'));
        $this->ds->create('/test', $input);
        $this->assertNoError();
        }

    // It's OK to leave the slug out
    function test_create_missing_slug()
        {
        $input = Array(
            'title' => 'No slug',
            );
        // A unique URL will be created even without a slug
        $expected_url = new PatternExpectation('/^\/test\/[^ ]+$/');
        $this->storage->expectOnce('create', Array('*', $expected_url, '*'));
        $this->ds->create('test', $input);
        $this->assertNoError();
        }

    function test_create_with_default_value()
        {
        // add a default value for the description field
        $temp_config = $this->config;
        $temp_config['test']['fields']['description']['default'] = 'default data';
        $ds = new DataSource($temp_config, $this->storage_factory);
        $input = Array(
            'slug' => "slug",
            'title' => "Test",
            );
        $expected_record = $input;
        unset($expected_record['slug']);
        // expect the default value to be set for this field
        $expected_record['description'] = 'default data';
        $expected_record['_table'] = 'test';
        $expected_record['url'] = '/test/slug';
        // Create returns the result of the storage call
        $this->storage->expectOnce('create', Array('*', '/test/slug', $expected_record));
        $this->storage->setReturnValue('create', 'created_url');
        $ds->create('/test', $input);
        $this->assertNoError();
        }
    
    function test_create_normalises_data()
        {
        // record data is normalised before storage
        $input = Array(
            'slug' => "slug",
            'title' => "A 'test' item",
            'description' => "Test 'description'",
            );
        $expected_record = $input;
        unset($expected_record['slug']);
        // normalise quotes
        $expected_record['title'] = "A \xE2\x80\x98test\xE2\x80\x99 item";
        $expected_record['description'] = "Test \xE2\x80\x98description\xE2\x80\x99";
        $expected_record['_table'] = 'test';
        $expected_record['url'] = '/test/slug';
        $this->storage->expectOnce('create', Array('*', '/test/slug', $expected_record));
        $this->storage->setReturnValue('create', 'created_url');
        $this->ds->create('/test', $input);
        $this->assertNoError();
        }
    
    function test_create_normalisation_disabled()
        {
        $this->ds->enable_storage_normalisation = 0;
        $input = Array(
            'slug' => "slug",
            'title' => "A 'test' item",
            'description' => "Test 'description'",
            );
        $expected_record = $input;
        unset($expected_record['slug']);
        $expected_record['_table'] = 'test';
        $expected_record['url'] = '/test/slug';
        $this->storage->expectOnce('create', Array('*', '/test/slug', $expected_record));
        $this->storage->setReturnValue('create', 'created_url');
        $this->ds->create('/test', $input);
        $this->assertNoError();
        }
    
    function test_create_with_storage_error()
        {
        $input = Array(
            'slug' => "slug",
            'title' => "Test",
            );
        // Create returns NULL because the storage call failed
        $this->storage->expectOnce('create', Array('*', '/test/slug', '*'));
        $this->storage->setReturnValue('create', NULL);
        // the storage retrieve function is never called
        $this->storage->expectNever('retrieve');
        $record = $this->ds->create('test', $input);
        $this->assertNull($record);
        }
    }
    
class DataSourceRetrieveTestCase
    extends DataSourceTestBase
    {
    function test_retrieve()
        {
        $this->storage->setReturnValue('retrieve', 'RETRIEVE');
        $this->assertEqual($this->ds->retrieve('/test/foo'), 'RETRIEVE');
        $this->assertNoError();
        }

    function test_retrieve_not_found()
        {
        $this->ds->retrieve('/test/notfound');
        $this->assertError(404);
        }

    function test_retrieve_bad_meta()
        {
        $this->storage->setReturnValue('retrieve', 'RETRIEVE');
        $this->ds->retrieve('/badmeta');
        $this->assertError(404);
        }

    function test_retrieve_copyright()
        {
        // copyright strings are returned with all records
        $this->ds->copyright = 'foo';
        $this->storage->setReturnValue('retrieve', Array('title'=>'RETRIEVE'));
        $this->assertEqual($this->ds->retrieve('/test/foo'), Array('title'=>'RETRIEVE','copyright'=>'foo'));
        $this->assertNoError();
        }

    function test_update()
        {
        $this->storage->expectOnce('update', Array('*', '/test/foo', Array('NEW_DATA')));
        $this->storage->expectOnce('retrieve', Array('*', '/test/foo'));
        $this->storage->setReturnValue('update', 1);
        $this->storage->setReturnValue('retrieve', 'retrieved_data');
        $this->assertEqual($this->ds->update('/test/foo', Array('NEW_DATA')), 'retrieved_data');
        $this->assertNoError();
        }

    function test_update_not_found()
        {
        $this->ds->update('/test/notfound', Array('title'=>'foo'));
        $this->assertError(404);
        }

    function test_update_bad_meta()
        {
        $this->storage->setReturnValue('update', 'UPDATE');
        $this->ds->update('/badmeta', Array('title'=>'foo'));
        $this->assertError(404);
        }

    function test_update_read_only()
        {
        $record = $this->ds->update('/meta', Array('title'=>'foo'));
        $this->assertError(405);
        }
    
    function test_update_normalises_data()
        {
        // record data is normalised before storage
        $input = Array(
            'title' => "A 'test' item",
            'description' => "Test 'description'",
            );
        $expected_record = $input;
        // normalise quotes
        $expected_record['title'] = "A \xE2\x80\x98test\xE2\x80\x99 item";
        $expected_record['description'] = "Test \xE2\x80\x98description\xE2\x80\x99";
        $this->storage->expectOnce('update', Array('*', '/test/foo', $expected_record));
        $this->storage->setReturnValue('update', 1);
        $this->ds->update('/test/foo', $input);
        $this->assertNoError();
        }
    
    function test_update_normalisation_disabled()
        {
        $this->ds->enable_storage_normalisation = 0;
        $input = Array(
            'title' => "A 'test' item",
            'description' => "Test 'description'",
            );
        $expected_record = $input;
        $this->storage->expectOnce('update', Array('*', '/test/foo', $expected_record));
        $this->storage->setReturnValue('update', 1);
        $this->ds->update('/test/foo', $input);
        $this->assertNoError();
        }
    
    function test_update_with_storage_error()
        {
        // This test is weak because it is not able to test the case where update
        // returns NULL and an error code has been set.
        // Update returns NULL because the storage call failed
        $this->storage->expectOnce('update', Array('*', '/test/foo', Array('NEW_DATA')));
        $this->storage->setReturnValue('update', NULL);
        // the storage retrieve function is never called
        $this->storage->expectNever('retrieve');
        $record = $this->ds->update('/test/foo', Array('NEW_DATA'));
        $this->assertNull($record);
        }
    }
    
class DataSourceDeleteTestCase
    extends DataSourceTestBase
    {
    function test_delete()
        {
        $this->storage->expectOnce('delete', Array('*', '/test/foo'));
        // Storage must return true on success
        $this->storage->setReturnValue('delete', 1);
        $this->assertTrue($this->ds->delete('/test/foo'));
        $this->assertNoError();
        }

    function test_delete_not_found()
        {
        $this->ds->delete('/test/notfound');
        $this->assertError(404);
        }

    function test_delete_bad_meta()
        {
        $this->storage->setReturnValue('delete', 1);
        $this->ds->delete('/badmeta');
        $this->assertError(404);
        }

    function test_delete_read_only()
        {
        $this->ds->delete('/meta');
        $this->assertError(405);
        }
    }
    
class DataSourceSearchTestCase
    extends DataSourceTestBase
    {
    // Set up the parser/factory to return 'parsed-query' from 'query'
    function setup_mock_search($query_string='query')
        {
        // The parser factory is used to inject a mock parser
        $this->ds->_parser_factory = new MockQueryParserFactory();
        $this->ds->_parser_factory->expectOnce('new_parser', Array($query_string, '*'));
        $parser = new MockSimpleQueryParser();
        $this->ds->_parser_factory->setReturnValue('new_parser', $parser);
        // The mock parser returns the parsed query which is passed to the storage
        $parser->expectOnce('parse', Array($query_string));
        $parser->setReturnValue('parse', 'parsed-query');
        }
        
    function test_search()
        {
        $expected = Array('total'=>1); // Other fields are ignored
        $this->setup_mock_search();
        $this->storage->expectOnce('search', Array('*', '/test', 'parsed-query', 123, 456));
        $this->storage->setReturnValue('search', $expected);
        $r = $this->ds->search('/test', 'query', 123, 456);
        $this->assertNoError();
        $this->assertEqual($r, $expected);
        }
    
    function test_search_bad_args()
        {
        // Invalid count/offset
        $r = $this->ds->search('/test', '', 0, -1);
        $this->assertError(400);
        $this->assertNull($r);
        $r = $this->ds->search('/test', '', -1, 10);
        $this->assertError(400);
        $this->assertNull($r);
        }

    function test_search_bad_meta()
        {
        // No such table
        $r = $this->ds->search('/badmeta', 'bar', 0, 1000);
        $this->assertError(404);
        $this->assertNull($r);
        }
        
    function test_search_with_where_clause()
        {
        $config = $this->config;
        $config['test']['search']['where'] = 'a=W';
        $this->setup($config);
        $this->ds->_parser_factory = new MockQueryParserFactory();
        $this->ds->_parser_factory->expectAt(0, 'new_parser', Array('query', '*'));
        $parser = new MockSimpleQueryParser();
        $this->ds->_parser_factory->setReturnValue('new_parser', $parser);
        $parser->expectCallCount('parse', 3);
        // first parse is original query string
        $parser->expectAt(0, 'parse', Array('query'));
        // second parse is the where clasue
        $parser->expectAt(1, 'parse', Array('a=W'));
        // third parse is the whole thing
        $parser->expectAt(2, 'parse', Array('query{a=W}'));
        //### should ParsedQuery be mocked?  I'm taking advantage of the find and to_string functions
        $tree1 = new ParsedQuery();
        $tree2 = new ParsedQuery(new QP_TreeClause('a','=','W'));
        $parser->setReturnValueAt(0, 'parse', $tree1);
        $parser->setReturnValueAt(1, 'parse', $tree2);
        $this->ds->search('/test', 'query', 123, 456);
        }
    
    function test_search_with_where_clause_no_replace()
        {
        $config = $this->config;
        $config['test']['search']['where'] = 'a=W';
        $this->setup($config);
        $this->ds->_parser_factory = new MockQueryParserFactory();
        $this->ds->_parser_factory->expectAt(0, 'new_parser', Array('query', '*'));
        $parser = new MockSimpleQueryParser();
        $this->ds->_parser_factory->setReturnValue('new_parser', $parser);
        // parse is only called twice as the where clause is not added
        $parser->expectCallCount('parse', 2);
        $parser->expectAt(0, 'parse', Array('query'));
        $parser->expectAt(1, 'parse', Array('a=W'));
        $tree1 = new ParsedQuery(new QP_TreeClause('a', '=', 'Y'));
        $tree2 = new ParsedQuery(new QP_TreeClause('a', '=', 'W'));
        $parser->setReturnValueAt(0, 'parse', $tree1);
        $parser->setReturnValueAt(1, 'parse', $tree2);
        $this->ds->search('/test', 'query', 123, 456);
        }
    
    function test_search_normalises_path()
        {
        $this->storage->expectOnce('search', Array('*', '/test', '*', 0, 1000));
        $this->ds->search('test', '', 0, 1000);
        }

    function test_search_no_results()
        {
        $expected = Array('total'=>0);
        $this->setup_mock_search();
        $this->storage->expectOnce('search', Array('*', '/test', 'parsed-query', 0, 1000));
        $this->storage->setReturnValue('search', $expected);
        $r = $this->ds->search('/test', 'query', 0, 1000);
        $this->assertError(400);
        $this->assertEqual($r, $expected);
        }

    function test_search_null_from_storage()
        {
        $expected = Array('total'=>0);
        $r = $this->ds->search('/test', 'query', 0, 1000);
        $this->assertError(400);
        $this->assertEqual($r, $expected);
        }

    function test_search_normalises_query()
        {
        $this->setup_mock_search("\"test data\xE2\x80\x99s\"");
        $this->ds->search('test', "\xE2\x80\x9Ctest data's\xE2\x80\x9D", 0, 1000);
        }
    
    function test_search_normalisation_disabled()
        {
        $this->ds->enable_query_normalisation = 0;
        $this->setup_mock_search("test data's");
        $this->ds->search('test', "test data's", 0, 1000);
        }
    
    function setup_alt_storage($mock_class='MockStorage')
        {
        // Additional storage type used for search storage test
        $extra_setup = function($self, $mock_class) {
            $self->storage2 = new $mock_class();
            $self->storage_factory->setReturnReference('new_storage', $self->storage2, Array('mock2'));
            };
        $config = $this->config;
        $config['test']['storage_search'] = 'mock2';
        $this->setup($config, $extra_setup, $mock_class);
        }
        
    function test_search_alt_storage()
        {
        $this->setup_alt_storage();
        $this->storage2->expectOnce('search');
        $this->storage2->setReturnValue('search', Array());
        $this->storage->expectNever('search');
        $r = $this->ds->search('/test', 'query', 123, 456);
        }

    // If the search storage returns NULL, fall back to the standard storage
    function test_search_alt_storage_falls_back()
        {
        $this->setup_alt_storage();
        $this->storage2->expectOnce('search');
        $this->storage2->setReturnValue('search', NULL);
        $this->storage->expectOnce('search');
        $r = $this->ds->search('/test', 'query', 123, 456);
        }

    // ... unless the search storage has an error condition
    function test_search_alt_storage_does_not_fall_back_on_error()
        {
        $this->setup_alt_storage('SearchErrorMockStorage');
        $this->storage->expectNever('search');
        $r = $this->ds->search('/test', 'query', 123, 456);
        $this->assertError(400);
        $this->assertNull($r);
        }

    function test_search_with_adaptor()
        {
        // Does not use a mock query parser
        $config = $this->config;
        // the query {replace=this} will be changed to {title=single}
        $config['test']['adaptor'] = Array(
            'test' => Array(
                'index' => 'replace',
                'value' => 'this',
                'query_string'=> '{title=single}'
                ),
            );
        $this->setup($config);
        $parser = new SimpleQueryParser();
        $expect_tree = $parser->parse('{title=single}');
        $this->storage->expectOnce('search', Array('*', '/test', $expect_tree, 123, 456));
        $r = $this->ds->search('/test', '{replace=this}', 123, 456);
        }

    function test_normalise_search_query()
        {
        $tests = Array(
            // double quote tests
            "\xE2\x80\x9Ctest" => '"test',
            "test\xE2\x80\x9D" => 'test"',
            "\xE2\x80\x9Etest 2\xE2\x80\x9F" => '"test 2"',
            chr(147) . "test" => '"test',
            chr(148) . " test" => '" test',
            chr(147) . "test 2" . chr(148) => '"test 2"',
            "\xE2\x80\x9C-\xE2\x80\x9D-\xE2\x80\x9E-\xE2\x80\x9F-".chr(147)."-".chr(148) => '"-"-"-"-"-"',
            "\xE2\x80\xB3 test \xE3\x80\x83" => '" test "',
            // single quote tests
            "\xE2\x80\x98test" => "\xE2\x80\x99test",
            "\xE2\x80\x9A test" => "\xE2\x80\x99 test",
            "\xE2\x80\x9Btest" => "\xE2\x80\x99test",
            chr(145) . "test" . chr(146) => "\xE2\x80\x99test\xE2\x80\x99",
            "'test'" => "\xE2\x80\x99test\xE2\x80\x99",
            "\xE2\x80\x98-\xE2\x80\x9A-\xE2\x80\x9B-'-".chr(145)."-".chr(146) => 
                "\xE2\x80\x99-\xE2\x80\x99-\xE2\x80\x99-\xE2\x80\x99-\xE2\x80\x99-\xE2\x80\x99",
            "\x60test\xC2\xB4" => "\xE2\x80\x99test\xE2\x80\x99",
            "\xCC\x80-\xCC\x81-\xE2\x80\xB2" => "\xE2\x80\x99-\xE2\x80\x99-\xE2\x80\x99",
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual($this->ds->normalise_search_query($test), $expected);
        }
    
    function test_normalise_storage_data()
        {
        $input = Array(
            // apostrophe tests
            'field1' => "test'",
            'field2' => "test's",
            'field3' => "test\xE2\x80\x98s test\xE2\x80\x9As",
            'field4' => "a\xE2\x80\x9B b\x60 c\xC2\xB4 d\xCC\x80 e\xCC\x81 f\xE2\x80\xB2 g".chr(145)." h".chr(146),
            'field5' => "test '",
            // open single quote tests
            'field6' => "'test'",
            'field7' => " 'test'",
            'field8' => " \xE2\x80\x99a \xE2\x80\x9Ab \xE2\x80\x9Bc \x60d \xC2\xB4e",
            'field9' => " \xCC\x80a \xCC\x81b \xE2\x80\xB2c ".chr(145)."d ".chr(146)."e",
            // arrays within arrays
            'array1' => Array(Array(
                'subfield1' => " 'test'", 'subfield2' => " 'test'", 'subfield3' => Array(
                    'subsubfield1' => " 'test'")), Array(
                'subfield1' => " 'test'")),
            );
        $expected = Array(
            'field1' => "test\xE2\x80\x99",
            'field2' => "test\xE2\x80\x99s",
            'field3' => "test\xE2\x80\x99s test\xE2\x80\x99s",
            'field4' => "a\xE2\x80\x99 b\xE2\x80\x99 c\xE2\x80\x99 d\xE2\x80\x99 e\xE2\x80\x99 f\xE2\x80\x99 g\xE2\x80\x99 h\xE2\x80\x99",
            'field5' => "test '",
            'field6' => "'test\xE2\x80\x99",
            'field7' => " \xE2\x80\x98test\xE2\x80\x99",
            'field8' => " \xE2\x80\x98a \xE2\x80\x98b \xE2\x80\x98c \xE2\x80\x98d \xE2\x80\x98e",
            'field9' => " \xE2\x80\x98a \xE2\x80\x98b \xE2\x80\x98c \xE2\x80\x98d \xE2\x80\x98e",
            'array1' => Array(Array(
                'subfield1' => " \xE2\x80\x98test\xE2\x80\x99", 'subfield2' => " \xE2\x80\x98test\xE2\x80\x99", 'subfield3' => Array(
                    'subsubfield1' => " \xE2\x80\x98test\xE2\x80\x99")), Array(
                'subfield1' => " \xE2\x80\x98test\xE2\x80\x99")),
            );
        $this->assertEqual($this->ds->normalise_storage_data($input), $expected);
        }
    }
