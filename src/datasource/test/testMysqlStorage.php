<?php
// $Id$
// Test the MySQL storage handler
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once('MDB2/Driver/mysql.php');

Mock::generate('DS_Mysql_Database');
Mock::generate('MDB2_Driver_mysql', 'MockDB');
Mock::generate('SqlGenerator');

// Test the MySQL storage handler
class MysqlStorageTestBase
    extends UnitTestCase
    {
    // Test configuration
    var $config = Array(
        'test' => Array(
            'title'=>'Test',
            'mutable'=>TRUE,
            'storage'=> 'mysql',//### FIXME: find some way to inject our storage into DS
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('require'=>TRUE),
                'description' => Array(),
                'related' => Array(
                    'type' => 'ignored in this test',
                    ),
                ),
            'search'=>Array(
                'fields' => Array('t.title','t.description AS summary'),
                // The indexes define what criteria are acceptable in a query
                'index' => Array(
                    'default' => Array('type'=>'fulltext', 'fields'=>'t.title,t.description'),
                    'title' => Array('type'=>'fulltext', 'fields'=>'t.title'),
                    ),
                ),
            ),
        'related' => Array(
            'title'=>'Related',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Related_Table',
            'fields'=>Array(
                'x' => Array('require'=>TRUE),
                ),
            ),
        );

    // Test data
    var $url = '/test/path';

    var $input = Array(
        'title' => "A 'test' item",
        'description' => "Test 'description'",
        );

    function setup($config=NULL)
        {
        global $MODULE;
        if (is_null($config))
            $config = $this->config;
        if (!isset($config['test']['pear_db']))
            $config['test']['pear_db'] = $MODULE->get_pear_db();
        $this->ds = new DataSource($config);
        $this->mock_database = new MockDS_Mysql_Database();
        $this->storage = new DataSource_MysqlStorage($this->ds, $this->mock_database);
        $this->parser = new SimpleQueryParser();
        }

    // Tests for field 'different_name' aliased to 'related'
    function setup_with_alias()
        {
        $config = $this->config;
        $config['test']['fields']['different_name'] = $config['test']['fields']['related'];
        unset($config['test']['fields']['related']);
        $config['test']['fields']['different_name']['related_to'] = 'related';
        $this->setup($config);
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
    }
    
class MysqlStorageSearchTestCase
    extends MysqlStorageTestBase
    {
    // Search is passed through to database
    function test_search()
        {
        $this->storage->db = new MockDB();
        $this->storage->generator = new MockSqlGenerator();
        $this->storage->generator->expectOnce('convert', Array('parsed-query'));
        $this->storage->generator->setReturnValue('convert', 'converted-sql');
        $expected = 'converted-sql LIMIT 0, 1000';
        $this->storage->db->expectOnce('queryAll', Array($expected, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValue('queryAll', Array());
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertNoError();
        }

    function test_search_convert_error()
        {
        $this->storage->db = new MockDB();
        $this->storage->generator = new MockSqlGenerator();
        $this->storage->generator->expectOnce('convert', Array('parsed-query'));
        $this->storage->generator->setReturnValue('convert', NULL);
        $this->storage->generator->error_message = 'err';
        $this->storage->db->expectNever('queryAll');
        $r = $this->storage->search($this->ds, '/test', 'parsed-query', 0, 1000);
        $this->assertError(400, 'err');
        }
        
    function test_search_limit_no_union()
        {
        $this->storage->db = new MockDB();
        // expected that SQL_CALC_FOUND_ROWS and FOUND_ROWS are used
        $expected = "SELECT SQL_CALC_FOUND_ROWS t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t LIMIT 0, 1000";
        $this->storage->db->expectOnce('queryAll', Array($expected, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValue('queryAll', Array());
        $this->storage->db->expectOnce('queryOne', Array("SELECT FOUND_ROWS();"));
        $r = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 1000);
        }

    function test_search_limit_with_union()
        {
        $this->storage->db = new MockDB();
        $expected = "(SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE)) LIMIT 0, 1000";
        $this->storage->db->expectOnce('queryAll', Array($expected, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValue('queryAll', Array());
        // expected that a regular count is used (not FOUND_ROWS)
        $expected = "SELECT count(*) FROM ((SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE))) qp_count_t";
        $this->storage->db->expectOnce('queryOne', Array($expected));
        $tree = $this->parser->parse('{default=single} OR {title="manymany 010"}');
        $r = $this->storage->search($this->ds, '/test', $tree, 0, 1000);
        }

    // similar to MysqlDataSourceTestCase::test_search_count
    // this test is to make sure that only the count query is run for an empty limit
    function test_search_empty_limit()
        {
        $this->storage->db = new MockDB();
        $this->storage->db->expectNever('queryAll');
        $this->storage->db->expectOnce('queryOne');
        $r = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 0);
        }
    
    function test_search_with_facets()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            1 => Array('type'=>'group1', 'name'=>'type1', 'select'=>"t.title=''"),
            2 => Array('type'=>'group1', 'name'=>'type2', 'select'=>"t.description<>''"),
            4 => Array('type'=>'group1', 'name'=>'type3', 'select'=>'all'),
            8 => Array('type'=>'group2', 'name'=>'type4', 'select'=>'all'),
            );
        $this->setup($config);
        $this->storage->db = new MockDB();
        // SQL_CALC_FOUND_ROWS is not used
        $expected1 = "SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 | 8 AS facet_details FROM T t LIMIT 0, 1000";
        $this->storage->db->expectCallCount('queryAll', 2);
        $this->storage->db->expectAt(0, 'queryAll', Array($expected1, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(0, 'queryAll', Array());
        // does not use a second query for the count
        $this->storage->db->expectNever('queryOne');
        // the expected facet query
        $expected2 = "SELECT SUM(IF(t.title='',1,0)) AS 'type1',SUM(IF(t.description<>'',1,0)) AS 'type2',COUNT(DISTINCT t.id) AS total FROM T t";
        $this->storage->db->expectAt(1, 'queryAll', Array($expected2, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(1, 'queryAll', Array('0'=>Array('total'=>'3','type1'=>'0','type2'=>'3')));
        $r = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 1000);
        // confirm the expected total and facet array
        $this->assertEqual($r['total'], 3);
        $this->assertEqual($r['facets'], Array('group1'=>Array('type1'=>0,'type2'=>3,'type3'=>3), 
                'group2'=>Array('type4'=>3), 'accuracy'=>'exact'));
        }
    
    function test_search_with_facets_and_union()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            1 => Array('type'=>'facet_genre', 'name'=>'type1', 'select'=>"t.title=''"),
            2 => Array('type'=>'facet_genre', 'name'=>'type2', 'select'=>"t.description<>''"),
            4 => Array('type'=>'facet_genre', 'name'=>'type3', 'select'=>'all'),
            );
        $this->setup($config);
        $this->storage->db = new MockDB();
        $expected1 = "(SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 AS facet_details FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 AS facet_details FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE)) LIMIT 0, 1000";
        $this->storage->db->expectCallCount('queryAll', 2);
        $this->storage->db->expectAt(0, 'queryAll', Array($expected1, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(0, 'queryAll', Array());
        // expected that a regular count is used (not the value from the facet query)
        $expected = "SELECT count(*) FROM ((SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE))) qp_count_t";
        $this->storage->db->expectOnce('queryOne', Array($expected));
        $this->storage->db->setReturnValue('queryOne', '3');
        // the expected facet query
        $expected2 = "(SELECT SUM(IF(t.title='',1,0)) AS 'type1',SUM(IF(t.description<>'',1,0)) AS 'type2',COUNT(DISTINCT t.id) AS total FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT SUM(IF(t.title='',1,0)) AS 'type1',SUM(IF(t.description<>'',1,0)) AS 'type2',COUNT(DISTINCT t.id) AS total FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE))";
        $this->storage->db->expectAt(1, 'queryAll', Array($expected2, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(1, 'queryAll', Array('0'=>Array('total'=>'3','type1'=>'0','type2'=>'3'),'1'=>Array('total'=>'1','type1'=>'1','type2'=>'0')));
        $tree = $this->parser->parse('{default=single} OR {title="manymany 010"}');
        $r = $this->storage->search($this->ds, '/test', $tree, 0, 1000);
        // confirm the expected total and facet array
        $this->assertEqual($r['total'], 3);
        // this also confirms that type3 has been set to 3 rather than 4 (total was fixed to match result total)
        $this->assertEqual($r['facets'], Array('facet_genre'=>Array('type1'=>1,'type2'=>3,'type3'=>3),'accuracy'=>'approx'));
        }
    
    function test_search_facet_details()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            1 => Array('type'=>'group1', 'name'=>'type1', 'select'=>"t.title=''"),
            2 => Array('type'=>'group1', 'name'=>'type2', 'select'=>"t.description<>''"),
            4 => Array('type'=>'group1', 'name'=>'type3', 'select'=>'all'),
            );
        $this->setup($config);
        $this->storage->db = new MockDB();
        // facet_details bitwise comparison has been added to query
        $expected = "SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 AS facet_details FROM T t LIMIT 0, 1000";
        $this->storage->db->expectCallCount('queryAll', 2);
        $this->storage->db->expectAt(0, 'queryAll', Array($expected, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(0, 'queryAll', Array());
        $r = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 1000);
        }
    
    function test_search_facet_details_union()
        {
        $config = $this->config;
        $config['test']['facets'] = Array(
            1 => Array('type'=>'group1', 'name'=>'type1', 'select'=>"t.title=''"),
            2 => Array('type'=>'group1', 'name'=>'type2', 'select'=>"t.description<>''"),
            4 => Array('type'=>'group1', 'name'=>'type3', 'select'=>'all'),
            );
        $this->setup($config);
        $this->storage->db = new MockDB();
        // facet_details bitwise comparison has been added to each part of union query
        $expected = "(SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 AS facet_details FROM T t WHERE MATCH(t.title,t.description) AGAINST('single' IN BOOLEAN MODE)) UNION (SELECT t.title,t.description AS summary,CONCAT('/test/', t.id) AS url,'test' AS _table,IF(t.title='',1,0) | IF(t.description<>'',2,0) | 4 AS facet_details FROM T t WHERE MATCH(t.title) AGAINST('\"manymany 010\"' IN BOOLEAN MODE)) LIMIT 0, 1000";
        $this->storage->db->expectCallCount('queryAll', 2);
        $this->storage->db->expectAt(0, 'queryAll', Array($expected, NULL, MDB2_FETCHMODE_ASSOC));
        $this->storage->db->setReturnValueAt(0, 'queryAll', Array());
        $tree = $this->parser->parse('{default=single} OR {title="manymany 010"}');
        $r = $this->storage->search($this->ds, '/test', $tree, 0, 1000);
        }
    }
    
class MysqlStorageCreateTestCase
    extends MysqlStorageTestBase
    {
    // Create calls table->insert
    function test_create()
        {
        // Calls is_replaceable for each field
        $this->mock_database->expectAtLeastOnce('is_replaceable');
        $this->mock_database->setReturnValue('is_replaceable', FALSE);

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('insert', Array($meta, $this->input));
        $this->mock_database->setReturnValue('insert', 123);

        // Will call select with ID
        $this->mock_database->expectOnce('select', Array($meta, 123));
        $this->mock_database->setReturnValue('select', Array('url'=>'new_url'));

        // Will not call insert_related or insert_links
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('is_related', FALSE);
        $this->mock_database->expectNever('insert_related');
        $this->mock_database->expectNever('insert_links');

        $r = $this->storage->create($this->ds, $this->url, $this->input);
        $this->assertEqual($r, 'new_url');
        $this->assertNoError();
        }

    // An error is received from the database layer, sets error in DS
    function test_create_error()
        {
        $this->mock_database->error_code = 999;
        $this->mock_database->error_message = 'msg';
        $this->mock_database->setReturnValue('insert', NULL);
        $r = $this->storage->create($this->ds, $this->url, $this->input);
        $this->mock_database->expectNever('select');
        $this->assertNull($r);
        $this->assertError(999);
        }

    // If there are related fields, they will be created and linked
    function test_create_related()
        {
        $input = $this->input;
        $input['related'] = 'related_value';

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('insert', Array($meta, $input));
        $this->mock_database->setReturnValue('insert', 123);

        // Calls insert_related and insert_links
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));

        $this->mock_database->expectOnce('insert_related', Array($meta, $other_meta, 123, 'related_value', 0));
        $this->mock_database->setReturnValue('insert_related', 'related_id_list');

        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 123, Array('related_id_list'), 'related_value'));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->create($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    // Replaceable fields are overwritten
    //### FIXME: change 'replaceable' to something better
    function test_create_replaceable()
        {
        $input = $this->input;
        $input['related'] = 'replaceable_value';

        // Calls is_replaceable
        $this->mock_database->expectCallCount('is_replaceable', 3);
        $this->mock_database->setReturnValue('is_replaceable', TRUE, Array('*', 'related'));

        // Calls replace_field for replaceable field(s)
        $meta = $this->ds->_get_meta($this->url);
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectOnce('replace_field', Array($meta, $other_meta, 'related', 'replaceable_value'));
        $this->mock_database->setReturnValue('replace_field', 'replacement');

        // Calls insert with modified field
        // replaced fields are an array composed of id, value
        $modified_input = $input;
        $modified_input['related'] = Array('id'=>'replacement', 'value'=>'replaceable_value');
        $this->mock_database->expectOnce('insert', Array($meta, $modified_input));
        $this->mock_database->setReturnValue('insert', 123);

        $this->storage->create($this->ds, $this->url, $input);
        $this->assertNoError();
        }
    
    // Replaceable field can have a 'default_id' value specified
    function test_create_replaceable_with_default_id()
        {
        // set default id value in config
        $config = $this->config;
        $config['test']['fields']['related']['default_id']='1';
        $this->setup($config);
        // clear the field
        $input = $this->input;
        $input['related'] = '';
        
        $this->mock_database->setReturnValue('is_replaceable', TRUE, Array('*', 'related'));
        // replace_field function is not called since the value is empty
        $this->mock_database->expectNever('replace_field');
        
        // Calls insert with modified field
        $modified_input = $input;
        $modified_input['related'] = '1';
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('insert', Array($meta, $modified_input));
        $this->mock_database->setReturnValue('insert', 123);

        $this->storage->create($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    // A table name can be specified in the configuration
    function test_create_related_with_alias()
        {
        $this->setup_with_alias();

        $input = $this->input;
        $input['different_name'] = 'related_value';

        $meta = $this->ds->_get_meta($this->url);

        $this->mock_database->setReturnValue('insert', 123);

        // The 'other' meta key is set to the alias
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta['real_key'] = $other_meta['key'];
        $other_meta['key'] = 'different_name';

        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'different_name'));

        $this->mock_database->expectOnce('insert_related', Array($meta, $other_meta, 123, 'related_value', 0));
        $this->mock_database->setReturnValue('insert_related', 'related_id_list');

        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 123, Array('related_id_list'), 'related_value'));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->create($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    function test_create_related_with_multiple_tables()
        {
        $config = $this->config;
        $config['related2'] = Array(
            'title'=>'Another Related',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Related_Table',
            'fields'=>Array(
                'y' => Array('require'=>TRUE),
                ),
            );
        // relates to two tables
        $config['test']['fields']['related']['related_to'] = Array('related','related2');
        $this->setup($config);
        
        $input = $this->input;
        $input['related'] = Array('related'=>'related_value','related2'=>'another value');
        
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('insert', Array($meta, $input));
        $this->mock_database->setReturnValue('insert', 123);

        // meta for two tables is returned
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta2 = $this->ds->_get_meta('/related2');
        $other_meta2['real_key'] = $other_meta2['key'];
        $other_meta2['key'] = 'related';
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));
        // calls insert_related two times (one for each other_meta)
        $this->mock_database->expectCallCount('insert_related', 2);
        $this->mock_database->expectAt(0,'insert_related', Array($meta, $other_meta, 123, $input['related'], 0));
        $this->mock_database->expectAt(1,'insert_related', Array($meta, $other_meta2, 123, $input['related'], 1));
        $this->mock_database->setReturnValueAt(0, 'insert_related', Array('related_id_list'));
        $this->mock_database->setReturnValueAt(1, 'insert_related', Array('other_id'));
        // insert_links is called with a double indexed array (each set of ids)
        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 123, Array(Array('related_id_list'),Array('other_id')),$input['related']));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->create($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    function test_create_related_insert_error()
        {
        $input = $this->input;
        $input['related'] = 'related_value';

        // Error on insert_related
        $this->mock_database->error_code = 999;
        $this->mock_database->error_message = 'msg';
        $this->mock_database->setReturnValue('insert', 123);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('insert_related', NULL);

        $r = $this->storage->create($this->ds, $this->url, $input);
        $this->assertNull($r);
        $this->assertError(999);
        }

    function test_create_related_link_error()
        {
        $input = $this->input;
        $input['related'] = 'related_value';

        // Error on insert_related
        $this->mock_database->error_code = 999;
        $this->mock_database->error_message = 'msg';
        $this->mock_database->setReturnValue('insert', 123);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('insert_related', 'related_ids');
        $this->mock_database->setReturnValue('insert_links', NULL);

        $r = $this->storage->create($this->ds, $this->url, $input);
        $this->assertNull($r);
        $this->assertError(999);
        }
    }
    
class MysqlStorageRetrieveTestCase
    extends MysqlStorageTestBase
    {
    // Create calls table->insert
    function test_retrieve()
        {
        $input = $this->input;
        $input['id'] = 456; // Returned by select()

        // Database returns input from meta-data
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('select', Array($meta));
        $this->mock_database->setReturnValue('select', $input);

        // Will not call insert_related or insert_links
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('is_related', FALSE);
        $this->mock_database->expectNever('select_related');

        $r = $this->storage->retrieve($this->ds, $this->url);
        $this->assertEqual($r, $input);
        $this->assertNoError();
        }

    function test_retrieve_not_found()
        {
        // Does not need to return error as DS will set 404 if result is NULL
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->setReturnValue('select', NULL);
        $r = $this->storage->retrieve($this->ds, $this->url);
        $this->assertNull($r);
        $this->assertNoError();
        }

    // If there are related fields, they will be added to the result
    function test_retrieve_related()
        {
        $expected_input = $this->input;
        $expected_input['id'] = 456; // Returned by select()

        $expected_return = $expected_input;
        $expected_return['related'] = 'related_items';

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('select', Array($meta));
        $this->mock_database->setReturnValue('select', $expected_input);

        // Calls select_related and select_links
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));

        $this->mock_database->expectOnce('select_related', Array($meta, $other_meta, 456));
        $this->mock_database->setReturnValue('select_related', 'related_items');

        $r = $this->storage->retrieve($this->ds, $this->url, $this->input);
        $this->assertEqual($r, $expected_return);
        $this->assertNoError();
        }

    function test_retrieve_related_with_alias()
        {
        $this->setup_with_alias();

        $expected_input = $this->input;
        $expected_input['id'] = 456; // Returned by select()

        $expected_return = $expected_input;
        $expected_return['different_name'] = 'related_items';

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->setReturnValue('select', $expected_input);

        // Calls select_related and select_links
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta['real_key'] = $other_meta['key'];
        $other_meta['key'] = 'different_name';
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'different_name'));

        $this->mock_database->expectOnce('select_related', Array($meta, $other_meta, 456));
        $this->mock_database->setReturnValue('select_related', 'related_items');

        $r = $this->storage->retrieve($this->ds, $this->url, $this->input);
        $this->assertEqual($r, $expected_return);
        $this->assertNoError();
        }
    }
    
class MysqlStorageUpdateTestCase
    extends MysqlStorageTestBase
    {
    function test_update()
        {
        $new_data = Array('title'=>'foo');
        // Database returns input from meta-data
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('update', Array($meta, $new_data));
        $this->mock_database->setReturnValue('update', TRUE);

        // Calls is_replaceable for each field
        $this->mock_database->expectAtLeastOnce('is_replaceable');
        $this->mock_database->setReturnValue('is_replaceable', FALSE);

        // Will not call insert_related or insert_links
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('is_related', FALSE);
        $this->mock_database->expectNever('delete_related');
        $this->mock_database->expectNever('insert_related');
        $this->mock_database->expectNever('delete_links');
        $this->mock_database->expectNever('insert_links');

        $r = $this->storage->update($this->ds, $this->url, $new_data);
        $this->assertTrue($r);
        $this->assertNoError();
        }

    function test_update_error()
        {
        $this->mock_database->error_code = 999;
        $this->mock_database->error_message = 'msg';
        $this->mock_database->setReturnValue('update', FALSE);
        $r = $this->storage->update($this->ds, $this->url, Array());
        $this->assertFalse($r);
        $this->assertError(999);
        }

    function test_update_related()
        {
        $input = $this->input;
        $input['related'] = 'related_value';

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('update', Array($meta, $input));
        $this->mock_database->setReturnValue('update', 789);

        // Calls update_related then delete_links, insert_links
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));

        $this->mock_database->expectOnce('delete_related', Array($meta, $other_meta, 789, 'related_value'));
        $this->mock_database->setReturnValue('delete_related', 1);

        $this->mock_database->expectOnce('insert_related', Array($meta, $other_meta, 789, 'related_value', 0));
        $this->mock_database->setReturnValue('insert_related', 'related_id_list');

        $this->mock_database->expectOnce('delete_links', Array($meta, $other_meta, 789));
        $this->mock_database->setReturnValue('delete_links', TRUE);

        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 789, Array('related_id_list'), 'related_value'));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->update($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    function test_update_related_with_alias()
        {
        $this->setup_with_alias();

        $input = $this->input;
        $input['different_name'] = 'related_value';

        // Calls insert
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->setReturnValue('update', 789);

        // Calls update_related then delete_links, insert_links
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta['real_key'] = $other_meta['key'];
        $other_meta['key'] = 'different_name';
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'different_name'));

        $this->mock_database->expectOnce('delete_related', Array($meta, $other_meta, 789, 'related_value'));
        $this->mock_database->setReturnValue('delete_related', 1);

        $this->mock_database->expectOnce('insert_related', Array($meta, $other_meta, 789, 'related_value', 0));
        $this->mock_database->setReturnValue('insert_related', 'related_id_list');

        $this->mock_database->expectOnce('delete_links', Array($meta, $other_meta, 789));
        $this->mock_database->setReturnValue('delete_links', TRUE);

        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 789, Array('related_id_list'), 'related_value'));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->update($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    function test_update_related_with_multiple_tables()
        {
        // simulate 3 additional keys for this test
        $config = $this->config;
        $config['related2'] = Array(
            'title'=>'Another Related',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Related_Table',
            'fields'=>Array(
                'y' => Array('require'=>TRUE),
                ),
            );
        $config['related3'] = Array(
            'title'=>'Another Related',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Related_Table',
            'fields'=>Array(
                'y' => Array('require'=>TRUE),
                ),
            );
        // relates to three tables
        $config['test']['fields']['related']['related_to'] = Array('related','related2','related3');
        $this->setup($config);
        
        $input = $this->input;
        $input['related'] = Array('related'=>'related_value','related2'=>'another value','related3'=>'third value');
        
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('update', Array($meta, $input));
        $this->mock_database->setReturnValue('update', 789);

        // meta for three tables is returned
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta2 = $this->ds->_get_meta('/related2');
        $other_meta2['real_key'] = $other_meta2['key'];
        $other_meta2['key'] = 'related';
        $other_meta3 = $this->ds->_get_meta('/related3');
        $other_meta3['real_key'] = $other_meta3['key'];
        $other_meta3['key'] = 'related';
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));
        $this->mock_database->expectOnce('delete_related', Array($meta, $other_meta, 789, $input['related']));
        $this->mock_database->setReturnValue('delete_related', 1);
        // calls insert_related three times (one for each other_meta)
        $this->mock_database->expectCallCount('insert_related', 3);
        $this->mock_database->expectAt(0,'insert_related', Array($meta, $other_meta, 789, $input['related'], 0));
        $this->mock_database->expectAt(1,'insert_related', Array($meta, $other_meta2, 789, $input['related'], 1));
        $this->mock_database->expectAt(2,'insert_related', Array($meta, $other_meta3, 789, $input['related'], 2));
        $this->mock_database->setReturnValueAt(0, 'insert_related', Array('related_id_list'));
        $this->mock_database->setReturnValueAt(1, 'insert_related', Array('other_id'));
        $this->mock_database->setReturnValueAt(2, 'insert_related', Array('third_id'));
        $this->mock_database->expectOnce('delete_links', Array($meta, $other_meta, 789));
        $this->mock_database->setReturnValue('delete_links', TRUE);
        // insert_links is called with a double indexed array (each set of ids)
        $this->mock_database->expectOnce('insert_links', Array($meta, $other_meta, 789, Array(Array('related_id_list'),Array('other_id'),Array('third_id')), $input['related']));
        $this->mock_database->setReturnValue('insert_links', TRUE);

        $this->storage->update($this->ds, $this->url, $input);
        $this->assertNoError();
        }

    // Replaceable fields are overwritten
    //### FIXME: change 'replaceable' to something better
    function test_update_replaceable()
        {
        $input = $this->input;
        $input['related'] = 'replaceable_value';

        // Calls is_replaceable
        $this->mock_database->expectCallCount('is_replaceable', 3);
        $this->mock_database->setReturnValue('is_replaceable', TRUE, Array('*', 'related'));

        // Calls replace_field for replaceable field(s)
        $meta = $this->ds->_get_meta($this->url);
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectOnce('replace_field', Array($meta, $other_meta, 'related', 'replaceable_value'));
        $this->mock_database->setReturnValue('replace_field', 'replacement');

        // Calls update with modified field
        // replaced fields are an array with id, value
        $modified_input = $input;
        $modified_input['related'] = Array('id'=>'replacement', 'value'=>'replaceable_value');
        $this->mock_database->expectOnce('update', Array($meta, $modified_input));
        $this->mock_database->setReturnValue('update', 123);

        $this->storage->update($this->ds, $this->url, $input);
        $this->assertNoError();
        }
    
    // Replaceable field can have a 'default_id' value specified
    function test_update_replaceable_with_default_id()
        {
        // set default id value in config
        $config = $this->config;
        $config['test']['fields']['related']['default_id']='1';
        $this->setup($config);
        // clear the field
        $input = $this->input;
        $input['related'] = '';
        
        $this->mock_database->setReturnValue('is_replaceable', TRUE, Array('*', 'related'));
        // replace_field function is not called since the value is empty
        $this->mock_database->expectNever('replace_field');
        
        // Calls update with modified field
        $modified_input = $input;
        $modified_input['related'] = '1';
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('update', Array($meta, $modified_input));
        $this->mock_database->setReturnValue('update', 123);

        $this->storage->update($this->ds, $this->url, $input);
        $this->assertNoError();
        }
    }
    
class MysqlStorageDeleteTestCase
    extends MysqlStorageTestBase
    {
    function test_delete()
        {
        // Database returns input from meta-data
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('select', Array($meta));
        $this->mock_database->setReturnValue('select', Array('id'=>234));
        $this->mock_database->expectOnce('delete', Array($meta, 234));
        $this->mock_database->setReturnValue('delete', TRUE);

        // Will not call insert_related or insert_links
        $this->mock_database->expectAtLeastOnce('is_related');
        $this->mock_database->setReturnValue('is_related', FALSE);
        $this->mock_database->expectNever('delete_related');
        $this->mock_database->expectNever('delete_links');

        $r = $this->storage->delete($this->ds, $this->url);
        $this->assertTrue($r);
        $this->assertNoError();
        }

    function test_delete_not_found()
        {
        $this->mock_database->setReturnValue('select', NULL);
        $r = $this->storage->delete($this->ds, $this->url);
        $this->assertFalse($r);
        $this->assertError(404);
        }

    function test_delete_error()
        {
        $this->mock_database->error_code = 999;
        $this->mock_database->error_message = 'msg';
        $this->mock_database->setReturnValue('select', Array('id'=>234));
        $this->mock_database->setReturnValue('delete', FALSE);
        $r = $this->storage->delete($this->ds, $this->url);
        $this->assertFalse($r);
        $this->assertError(999);
        }

    function test_delete_related()
        {
        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->expectOnce('select', Array($meta));
        $this->mock_database->setReturnValue('select', Array('id'=>234));
        $this->mock_database->expectOnce('delete', Array($meta, 234));
        $this->mock_database->setReturnValue('delete', 1);

        // Calls delete_related and delete_links
        $other_meta = $this->ds->_get_meta('/related');
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'related'));

        $this->mock_database->expectOnce('delete_related', Array($meta, $other_meta, 234));
        $this->mock_database->setReturnValue('delete_related', TRUE);

        $this->mock_database->expectOnce('delete_links', Array($meta, $other_meta, 234));
        $this->mock_database->setReturnValue('delete_links', TRUE);

        $r = $this->storage->delete($this->ds, $this->url);
        $this->assertTrue($r);
        $this->assertNoError();
        }

    /*
        Stronger contract: the DS will always pass valid data to the storage layer
            so prob need to add tests to DS to check e.g. if no valid fields underlying
            layer doesn't get called
    */

    function test_delete_related_with_alias()
        {
        $this->setup_with_alias();

        $meta = $this->ds->_get_meta($this->url);
        $this->mock_database->setReturnValue('select', Array('id'=>234));
        $this->mock_database->setReturnValue('delete', 1);

        // Calls delete_related and delete_links
        $other_meta = $this->ds->_get_meta('/related');
        $other_meta['real_key'] = $other_meta['key'];
        $other_meta['key'] = 'different_name';
        $this->mock_database->expectCallCount('is_related', 3);
        $this->mock_database->setReturnValue('is_related', TRUE, Array('*', 'different_name'));

        $this->mock_database->expectOnce('delete_related', Array($meta, $other_meta, 234));
        $this->mock_database->setReturnValue('delete_related', TRUE);

        $this->mock_database->expectOnce('delete_links', Array($meta, $other_meta, 234));
        $this->mock_database->setReturnValue('delete_links', TRUE);

        $r = $this->storage->delete($this->ds, $this->url);
        $this->assertNoError();
        }
    }
