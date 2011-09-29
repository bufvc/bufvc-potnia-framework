<?php
// $Id$
// Tests for MySQL DataSource class
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once('MDB2/Driver/mysql.php');

Mock::generate('MDB2_Driver_mysql', 'MockDB');
Mock::generate('DB_Table');

class DSMysqlDatabaseTestBase
    extends UnitTestCase
    {
    function setup()
        {
        $this->db = new MockDB();
        $this->db_table = new MockDB_Table();
        $this->database = new DS_Mysql_Database($this->db, $this->db_table);
        $this->error = new PEAR_Error('test error', 900, NULL, NULL, 'user info');
        }

    // Note, this is not the same as the similar function in the DS classes!
    function assertError($status=500)
        {
        $this->assertEqual($this->database->error_code, $status, 'error_code: %s');
        $this->assertTrue($this->database->error_message != '', 'missing error_message');
        }
    }
    
class DSMysqlDatabaseFactoryTestCase
    extends DSMysqlDatabaseTestBase
    {
    // Converts $meta to db_table cols
    function test_new_db_table()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'ig1' => Array('no_type'=>'ignored'),
                'ig2' => Array('type'=>'other_types_ignored'),
                'ig3' => Array(),
                'ok1'=> Array('type'=>'varchar', 'other_data'=>'is_copied'),
                'ok2'=> Array('type'=>'smallint'),
                'ok3'=> Array('type'=>'integer'),
                'ok4'=> Array('type'=>'bigint'),
                'ok5'=> Array('type'=>'decimal'),
                'ok6'=> Array('type'=>'double'),
                'ok7'=> Array('type'=>'boolean'),
                'ok8'=> Array('type'=>'date'),
                'ok9'=> Array('type'=>'time'),
                'ok10'=> Array('type'=>'timestamp'),
                'ok11'=> Array('type'=>'clob'),
                'ok12'=> Array('type'=>'char'),
                'conv1'=> Array('type'=>'text'),
                'conv2'=> Array('type'=>'implode'),
                'ig4'=> Array('type'=>'many_to_one'), // M:1 ignored without mysql_field
                'alias1'=> Array('type'=>'many_to_one', 'mysql_field'=>'aliased1'),
                'alias2'=> Array('type'=>'varchar', 'mysql_field'=>'aliased2'),
                ),
            );
        $db_table = $this->database->_new_db_table($meta);
        $this->assertEqual($db_table->table, 'T');
        $this->assertEqual($this->db_table->fetchmode, MDB2_FETCHMODE_ASSOC);

        // Check configuration, all 'ok' fields are copied across
        // 'conv' fields are converted
        $conversions = Array(
            'conv1'=> Array('type'=>'clob'),
            'conv2'=> Array('type'=>'clob'),
            //'conv3'=> Array('type'=>'integer'),
            );

        $expected_col = Array();
        // ID field is added
        $expected_col['id'] = Array('type'=>'integer');
        foreach ($meta['fields'] as $key=>$value)
            {
            if (substr($key, 0, 2) == 'ok')
                $expected_col[$key] = $value;
            else if (substr($key, 0, 4) == 'conv')
                $expected_col[$key] = array_merge($value, $conversions[$key]);
            else if (substr($key, 0, 5) == 'alias')
                {
                $new_key = $value['mysql_field'];
                if ($value['type'] == 'many_to_one')
                    $value['type'] = 'integer';
                $expected_col[$new_key] = $value;
                }
            }
        $this->assertEqual($expected_col, $db_table->col);
        }

    // If a URL field is present, it is added to the db_table config
    function test_new_db_table_with_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                // The field name itself is ignored, change this perhaps???
                'url'=> Array('select'=>'f'),
                'x'=> Array('type'=>'integer'),
                ),
            );
        $expected_col = Array(
            'id' => Array('type'=>'integer'),
            'f' => Array('type'=>'varchar', 'size'=>255),
            'x'=> Array('type'=>'integer'),
            );
        $db_table = $this->database->_new_db_table($meta);
        $this->assertEqual($expected_col, $db_table->col);
        }

    // Related field types are one_to_many and many_to_many
    function test_is_related()
        {
        $meta = Array(
            'fields'=>Array(
                'a' => Array('type'=>'any other type'),
                'b' => Array('type'=>'one_to_many'),
                'c' => Array('type'=>'many_to_many'),
                ),
            );
        $this->assertFalse($this->database->is_related($meta, 'a'));
        $this->assertTrue($this->database->is_related($meta, 'b'));
        $this->assertTrue($this->database->is_related($meta, 'c'));
        }
    }
    
class DSMysqlDatabaseInsertTestCase
    extends DSMysqlDatabaseTestBase
    {
    function test_insert()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'ignored_when_id_is_url',
            );
        $this->db_table->expectNever('select');
        $this->db_table->setReturnValue('nextId', 123);
        $record = Array('title'=>'Title', 'ignored'=>'not stored');
        $expected_record = $record;
        unset($expected_record['ignored']);
        $expected_record['id'] = 123;
        $this->db_table->expectOnce('insert', Array($expected_record));
        $r = $this->database->insert($meta, $record);
        // Returns new ID
        $this->assertEqual(123, $r);
        }

    function test_insert_with_id()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'ignored_when_id_is_url',
            );
        $this->db_table->expectNever('select');
        $this->db_table->expectNever('nextId');
        $record = Array('title'=>'Title', 'id'=>'123');
        $expected_record = $record;
        $this->db_table->expectOnce('insert', Array($expected_record));
        $r = $this->database->insert($meta, $record);
        // Returns new ID
        $this->assertEqual(123, $r);
        }

    // Sample error handling function -- other error handling is not tested
    function test_insert_error()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'new_url',
            );
        // Error in insert
        $this->db_table->setReturnValue('insert', $this->error);
        $r = $this->database->insert($meta, 'dummy');
        $this->assertNull($r);
        $this->assertError();

        // Error in nextId
        $this->db_table->setReturnValue('nextId', $this->error);
        $r = $this->database->insert($meta, 'dummy');
        $this->assertError();
        $this->assertNull($r);
        }

    // Insert (and update) will look for a field name if one is missing
    // The first defined field will be used
    function test_insert_default_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                ),
            'key' => 'url_path',
            'path_info'=>'ignored_when_id_is_url',
            );
        foreach (Array('Title', Array('Title')) as $record)
            {
            $this->setup();
            $this->db_table->setReturnValue('nextId', 123);
            $expected_record = Array('title'=>'Title', 'id'=>123);
            $this->db_table->expectOnce('insert', Array($expected_record));
            $this->database->insert($meta, $record);
            }
        }

    function test_insert_with_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                ),
            'key' => 'url_path',
            'path_info'=>'new_url',
            );
        $expected_query = Array(
            'select'=>"T.id",
            'where'=>"T.f='new_url'",
            'get'=> 'one',
            );
        $this->db_table->expectOnce('select', Array($expected_query));
        $this->db_table->setReturnValue('nextId', 123);
        // Url field in record is ignored -- meta[path_info] is used instead as this is set up by storage
        $record = Array('f'=>'abc');
        $expected_record = $record;
        $expected_record['f'] = 'new_url';
        $expected_record['id'] = 123;
        $this->db_table->expectOnce('insert', Array($expected_record));
        $this->database->insert($meta, $record);
        }

    function test_insert_with_dupe_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                ),
            'key' => 'url_path',
            'path_info'=>'new_url',
            );
        $expected_query = Array(
            'select'=>"T.id",
            'where'=>"T.f='new_url'",
            'get'=> 'one',
            );
        $this->db_table->expectOnce('select', Array($expected_query));
        $this->db_table->setReturnValue('select', 'exists');
        $this->db_table->setReturnValue('nextId', 123);
        // Url field in record is ignored -- meta[path_info] is used instead as this is set up by storage
        $record = Array('f'=>'abc');
        $expected_record = $record;
        $expected_record['f'] = 'new_url123'; // temp fix -- would prefer to randomise URL (and of course 123 may exist...)
        $expected_record['id'] = 123;
        $this->db_table->expectOnce('insert', Array($expected_record));
        $this->database->insert($meta, $record);
        }

    // Implode field type aggregates other fields
    function test_insert_with_implode()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'i' => Array(
                    'type'=>'implode',
                    // Fields can be anything present in the record
                    'keys'=>Array('x', 'y', 'z', 'empty1', 'empty2', 'not-present'),
                    'implode' => '|',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'ignored_when_id_is_url',
            );
        $this->db_table->setReturnValue('nextId', 123);
        $record = Array(
            'x'=>'abc', // Scalar
            'y'=>Array('def', 'ghi'), // 1D array
            'z'=>Array(Array('a'=>'jkl', 'b'=>'mno'), Array('a'=>'pqr')), // 2D array
            'empty1'=>'',
            'empty2'=>Array(),
            );
        $expected_record = Array('i'=>'abc|def|ghi|jkl|mno|pqr', 'id' => 123);
        $this->db_table->expectOnce('insert', Array($expected_record));
        $this->database->insert($meta, $record);
        }

    // 'mysql_field' overrides the field name
    //### FIXME: maybe change name from mysql_field???
    function test_insert_with_mysql_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar', 'mysql_field'=>'aliased'),
                ),
            'key' => 'url_path',
            );
        $this->db_table->setReturnValue('nextId', 123);
        $record = Array('title'=>'Title');
        $expected_record = Array('aliased'=>'Title', 'id'=>123);
        $this->db_table->expectOnce('insert', Array($expected_record));
        $r = $this->database->insert($meta, $record);
        }
    
    // default values can be specified in the DS config
    function test_insert_with_default_value()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'extra' => Array('type'=>'varchar', 'default'=>'some value'),
                ),
            'key' => 'url_path',
            );
        $this->db_table->setReturnValue('nextId', 123);
        $record = Array('title'=>'Title');
        $expected_record = Array('title'=>'Title', 'extra'=>'some value', 'id'=>123);
        $this->db_table->expectOnce('insert', Array($expected_record));
        $r = $this->database->insert($meta, $record);
        }

    function test_insert_related_one_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'foreign_key'=>'this_id',
                    'lookup'=>'ignored in one_to_many',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                // FK must be defined in 'other' table
                'this_id'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        // Does not call select even though lookup field is present
        $this->db_table->expectNever('select');

        $records = Array(Array('a'=>1), Array('a'=>2));
        $this->db_table->expectCallCount('insert', 2);

        $this->db_table->setReturnValueAt(0, 'nextId', 11);
        $expected_record = Array('a'=>1, 'this_id'=>345, 'id'=>11);
        $this->db_table->expectAt(0, 'insert', Array($expected_record));

        $this->db_table->setReturnValueAt(1, 'nextId', 22);
        $expected_record = Array('a'=>2, 'this_id'=>345, 'id'=>22);
        $this->db_table->expectAt(1, 'insert', Array($expected_record));

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array(11, 22));
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }

    function test_insert_related_one_to_many_immutable()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'foreign_key'=>'this_id',
                    'lookup'=>'ignored in one_to_many',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>0,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                // FK must be defined in 'other' table
                'this_id'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        // Does not call select even though lookup field is present
        $this->db_table->expectNever('select');

        $records = Array(Array('a'=>1), Array('a'=>2));
        // Does not insert records because table is read-only
        $this->db_table->expectCallCount('insert', 0);
        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array());
        }

    function test_insert_related_many_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    'split' => 'ignored when record list is array'
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );

        $records = Array(Array('a'=>1), Array('a'=>2));
        $this->db_table->expectCallCount('insert', 2);

        $this->db_table->setReturnValueAt(0, 'nextId', 11);
        $expected_record = Array('a'=>1, 'id'=>11);
        $this->db_table->expectAt(0, 'insert', Array($expected_record));

        $this->db_table->setReturnValueAt(1, 'nextId', 22);
        $expected_record = Array('a'=>2, 'id'=>22);
        $this->db_table->expectAt(1, 'insert', Array($expected_record));

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array(11, 22));
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }

    function test_insert_related_many_to_many_with_lookup()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    'lookup'=>'a',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );

        // Handles default fields
        foreach (Array(Array('a'=>"x'yz"), Array("x'yz"), "x'yz") as $test)
            {
            $this->setup();
            $records = Array($test, Array('a'=>2));

            // Calls select twice, the first time it finds an item, the second time it doesn't
            $this->db_table->expectCallCount('select', 2);
            $expected_query = Array(
                'select'=>"T2.id",
                'where'=>"T2.a='x\'yz'",
                'get'=> 'one',
                );
            $this->db_table->expectAt(0, 'select', Array($expected_query));
            $this->db_table->setReturnValueAt(0, 'select', 11);
            $expected_query['where'] = "T2.a='2'";
            $this->db_table->expectAt(1, 'select', Array($expected_query));
            $this->db_table->setReturnValueAt(1, 'select', NULL);

            // Insert is only called once
            $this->db_table->setReturnValue('nextId', 22);
            $expected_record = Array('a'=>2, 'id'=>22);
            $this->db_table->expectOnce('insert', Array($expected_record));

            $r = $this->database->insert_related($meta, $other_meta, 345, $records);
            $this->assertEqual($r, Array(11, 22));
            // Sets up other meta
            $this->assertEqual($this->db_table->table, 'T2');
            }
        }

    function test_insert_related_many_to_many_with_lookup_with_more_keys()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // can have any number of keys
                    'keys'=>Array('this_id', 'other_id', 'third_id'),
                    // one lookup for each additional key
                    'lookup'=>Array('a','b'),
                    // can specify a field map for the additional tables
                    'related_field_map'=>Array(Array(),Array('c'=>'b')),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $other_meta2 = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T3',
            'fields'=>Array(
                'b'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );

        // related field 1 lookup
        $records = Array(Array('a'=>"z",'b'=>'bar'), Array('a'=>2));
        $this->db_table->expectCallCount('select', 2);
        $expected_query = Array(
            'select'=>"T2.id",
            'where'=>"T2.a='z'",
            'get'=> 'one',
            );
        $this->db_table->expectAt(0, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(0, 'select', 11);
        $expected_query['where'] = "T2.a='2'";
        $this->db_table->expectAt(1, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(1, 'select', NULL);
        $this->db_table->setReturnValue('nextId', 22);
        $expected_record = Array('a'=>2, 'id'=>22);
        $this->db_table->expectOnce('insert', Array($expected_record));
        // use the first other_meta and index 0
        $r = $this->database->insert_related($meta, $other_meta, 345, $records, 0);
        $this->assertEqual($r, Array(11, 22));
        $this->assertEqual($this->db_table->table, 'T2');
        
        // related field 2 lookup
        //foreach (Array(Array('a'=>"x",'b'=>"z"), Array("z"), "z", Array('a'=>'foo','c'=>'z')) as $test)
        $this->setup();
        $records = Array(Array('a'=>"x",'b'=>"z"), Array('b'=>2));
        $this->db_table->expectCallCount('select', 2);
        $expected_query = Array(
            'select'=>"T3.id",
            'where'=>"T3.b='z'",
            'get'=> 'one',
            );
        $this->db_table->expectAt(0, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(0, 'select', 11);
        $expected_query['where'] = "T3.b='2'";
        $this->db_table->expectAt(1, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(1, 'select', NULL);
        $this->db_table->setReturnValue('nextId', 22);
        $expected_record = Array('b'=>2, 'id'=>22);
        $this->db_table->expectOnce('insert', Array($expected_record));
        // use the second other_meta and the next index (this is field 2)
        $r = $this->database->insert_related($meta, $other_meta2, 345, $records, 1);
        $this->assertEqual($r, Array(11, 22));
        $this->assertEqual($this->db_table->table, 'T3');
        
        // related field 2 with field map (value 'c' gets mapped to 'b')
        $this->setup();
        $records = Array( Array('a'=>'foo','c'=>'z'), Array('b'=>2));
        $this->db_table->expectCallCount('select', 2);
        $expected_query = Array(
            'select'=>"T3.id",
            'where'=>"T3.b='z'",
            'get'=> 'one',
            );
        $this->db_table->expectAt(0, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(0, 'select', 11);
        $expected_query['where'] = "T3.b='2'";
        $this->db_table->expectAt(1, 'select', Array($expected_query));
        $this->db_table->setReturnValueAt(1, 'select', NULL);
        $this->db_table->setReturnValue('nextId', 22);
        $expected_record = Array('b'=>2, 'id'=>22);
        $this->db_table->expectOnce('insert', Array($expected_record));
        $r = $this->database->insert_related($meta, $other_meta2, 345, $records, 1);
        $this->assertEqual($r, Array(11, 22));
        $this->assertEqual($this->db_table->table, 'T3');
        }

    function test_insert_related_many_to_many_immutable()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    'lookup'=>'a',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>0, // related table is read-only
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );

        $records = Array(1, 2);

        // Calls select twice, the first time it finds an item, the second time it doesn't
        $this->db_table->expectCallCount('select', 2);
        $this->db_table->setReturnValueAt(0, 'select', 11);
        $this->db_table->setReturnValueAt(1, 'select', NULL);

        // Insert is not called
        $this->db_table->expectCallCount('insert', 0);

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array(11));
        }

    function test_insert_related_with_split()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'keys'=>Array('this_id', 'other_id'),
                    'lookup'=>'a', // Needed with split
                    'split' => '; *', // Regex
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $records = '1;   2';

        // Splits the input record and does a lookup
        $this->db_table->expectCallCount('nextId', 2);
        $this->db_table->expectCallCount('insert', 2);
        $this->db_table->setReturnValueAt(0, 'nextId', 11);
        $this->db_table->setReturnValueAt(1, 'nextId', 22);
        $expected_record = Array('a'=>'1', 'id'=>11);
        $this->db_table->expectAt(0, 'insert', Array($expected_record));
        $expected_record = Array('a'=>'2', 'id'=>22);
        $this->db_table->expectAt(1, 'insert', Array($expected_record));

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array(11, 22));
        }

    // Split does not cause empty data to be inserted
    function test_insert_related_empty_split()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'keys'=>Array('this_id', 'other_id'),
                    'lookup'=>'a', // Needed with split
                    'split' => '; *', // Regex
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $records = '';

        // Splits the input record and does a lookup
        $this->db_table->expectCallCount('nextId', 0);
        $this->db_table->expectCallCount('insert', 0);

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array());
        }
    
    // Split ignores empty units
    // e.g. '1; 2;' should only be 2 not 3 records
    function test_insert_related_split_with_empties()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'keys'=>Array('this_id', 'other_id'),
                    'lookup'=>'a', // Needed with split
                    'split' => '; *', // Regex
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>TRUE,
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $records = ' ;1;   2;';

        // Splits the input record and does a lookup
        $this->db_table->expectCallCount('nextId', 2);
        $this->db_table->expectCallCount('insert', 2);
        $this->db_table->setReturnValueAt(0, 'nextId', 11);
        $this->db_table->setReturnValueAt(1, 'nextId', 22);
        $expected_record = Array('a'=>'1', 'id'=>11);
        $this->db_table->expectAt(0, 'insert', Array($expected_record));
        $expected_record = Array('a'=>'2', 'id'=>22);
        $this->db_table->expectAt(1, 'insert', Array($expected_record));

        $r = $this->database->insert_related($meta, $other_meta, 345, $records);
        $this->assertEqual($r, Array(11, 22));
        }

    function test_insert_links()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $expected_sql = "INSERT INTO L (this_id,other_id) VALUES (123,4),(123,5),(123,6)";
        $this->db->expectOnce('query', Array($expected_sql));
        $r = $this->database->insert_links($meta, $other_meta, 123, Array(Array(4,5,6)));
        $this->assertTrue($r);
        }

    function test_insert_links_with_text_keys()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $expected_sql = "INSERT INTO L (this_id,other_id) VALUES (123,'a'),(123,'b'),(123,'c')";
        $this->db->expectOnce('query', Array($expected_sql));
        $r = $this->database->insert_links($meta, $other_meta, 123, Array(Array('a','b','c')));
        $this->assertTrue($r);
        }

    function test_insert_links_ignores_duplicates()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $expected_sql = "INSERT INTO L (this_id,other_id) VALUES (123,4),(123,5),(123,6),(123,'a'),(123,'b')";
        $this->db->expectOnce('query', Array($expected_sql));
        $r = $this->database->insert_links($meta, $other_meta, 123, Array(Array(4,5,6,4,'a',5,'b','a')));
        $this->assertTrue($r);
        }

    function test_insert_links_empty()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        foreach (Array(Array(), Array(0=>Array()), '', NULL) as $test)
            {
            $this->setup();
            $this->db->expectNever('query');
            $r = $this->database->insert_links($meta, $other_meta, 123, $test);
            $this->assertTrue($r);
            }
        }

    function test_insert_links_ignores_one_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $this->db->expectNever('query');
        $r = $this->database->insert_links($meta, $other_meta, 123, Array(4,5,6));
        $this->assertTrue($r);
        }

    function test_insert_links_with_more_keys()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // can have any number of keys
                    'keys'=>Array('this_id', 'other_id', 'third_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $expected_sql = "INSERT INTO L (this_id,other_id,third_id) VALUES (1,1,1),(1,1,2),(1,2,2)";
        $this->db->expectOnce('query', Array($expected_sql));
        $r = $this->database->insert_links($meta, $other_meta, 1, Array(Array(1,1,2,1),Array(1,2,2,1)));
        $this->assertTrue($r);
        }
    
    function test_insert_links_with_additional_columns()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // can have any number of keys
                    'keys'=>Array('this_id', 'other_id'),
                    // this column will be added and saved with the link table
                    'link_columns'=>Array('test'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $record = Array(
            Array('test'=>'a'),
            Array('test'=>2),
            Array('notfound'=>3), // NULL is used for missing values
            );
        $expected_sql = "INSERT INTO L (this_id,other_id,test) VALUES (123,4,'a'),(123,5,2),(123,6,NULL)";
        $this->db->expectOnce('query', Array($expected_sql));
        $this->db->expectOnce('quote', Array('a'));
        $this->db->setReturnValue('quote', "'a'");
        $r = $this->database->insert_links($meta, $other_meta, 123, Array(Array(4,5,6)), $record);
        $this->assertTrue($r);
        }
    }
    
class DSMysqlDatabaseSelectTestCase
    extends DSMysqlDatabaseTestBase
    {
    function test_select()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'description' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.title,T.description,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            //### TODO: extra joins
            'where'=>"T.id='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select($meta);
        $this->assertEqual($r, 'returned_data');
        }

    function test_select_with_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                'title' => Array('type'=>'varchar'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.f,T.title,CONCAT('/url_path/', T.f) AS url,'url_path' AS _table",
            'where'=>"T.f='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select($meta);
        $this->assertEqual($r, 'returned_data');
        }

    function test_select_with_id()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.f,CONCAT('/url_path/', T.f) AS url,'url_path' AS _table",
            'where'=>"T.id='123'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select($meta, 123);
        $this->assertEqual($r, 'returned_data');
        }

    // A scalar field with 'hide' defined will not be used in SELECT queries
    function test_select_with_hide()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'hidden' => Array('type'=>'varchar', 'hide'=>TRUE),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.title,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            'where'=>"T.id='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $r = $this->database->select($meta);
        }

    // Define a constant value
    function test_select_with_const()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'c' => Array('type'=>'const', 'value'=>"foo'bar"),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.title,'foo\'bar' AS c,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            'where'=>"T.id='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $r = $this->database->select($meta);
        }

    // Add arbritrary SQL
    function test_select_with_sql()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'q' => Array('type'=>'sql', 'value'=>"STRLEN('foo')"),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.title,STRLEN('foo') AS q,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            'where'=>"T.id='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $r = $this->database->select($meta);
        }

    function test_select_with_mysql_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar', 'mysql_field'=>'aliased'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.aliased,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            'where'=>"T.id='url_name'",
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $r = $this->database->select($meta);
        }

    function test_select_many_to_one()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'a' => Array('type'=>'varchar'),
                'x' => Array(
                    'type'=>'many_to_one',
                    'select'=>'T2.x',
                    'join'=>'JOIN T2 ON T.x=T2.id',
                    ),
                'y' => Array(
                    'type'=>'many_to_one',
                    'select'=>'T3.y',
                    'join'=>'JOIN T3 ON T.aliased=T3.id',
                    'mysql_field' => 'aliased',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $expected_query = Array(
            'select'=>"T.id,T.a,T.aliased,T2.x,T3.y,CONCAT('/url_path/', T.id) AS url,'url_path' AS _table",
            'where'=>"T.id='url_name'",
            'join'=>'JOIN T2 ON T.x=T2.id JOIN T3 ON T.aliased=T3.id',
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $this->database->select($meta);
        }

    function test_select_bang_random()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'), // This is ignored by !random
                'title' => Array('type'=>'varchar'),
                'description' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'!random',
            );
        $expected_query = Array(
            'select'=>"T.id,T.f,T.title,T.description,CONCAT('/url_path/', T.f) AS url,'url_path' AS _table",
            'where'=>'T.id >= random_id',
            'order'=>'T.id ASC',
            'join'=>'JOIN (SELECT (RAND() * (SELECT MAX(id) FROM T)) AS random_id) AS TRAND',
            'get'=> 'row',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 0, 1));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select($meta);
        $this->assertEqual($r, 'returned_data');
        }

    function test_select_related_one_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'where'=>"T2.this_id=123",
            'get'=> 'all',
            //### TODO: order
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select_related($meta, $other_meta, 123);
        $this->assertEqual($r, 'returned_data');
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }

    function test_select_related_one_to_many_with_where()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    'where'=>'T2.b=0',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer'),'b'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'where'=>"T2.this_id=123 AND T2.b=0",
            'get'=> 'all',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select_related($meta, $other_meta, 123);
        $this->assertEqual($r, 'returned_data');
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }

    function test_select_related_many_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'select' => 'T2.a',
                    'join'=>'JOIN L ON L.other_id=T2.id',
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'join'=>'JOIN L ON L.other_id=T2.id',
            'where'=>"L.this_id=123",
            'get'=> 'all',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select_related($meta, $other_meta, 123);
        $this->assertEqual($r, 'returned_data');
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }

    function test_select_related_many_to_many_without_join()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'select' => 'T2.a',
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'join'=>'JOIN L ON L.other_id=T2.id',
            'where'=>"L.this_id=123",
            'get'=> 'all',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $this->db_table->setReturnValue('select', 'returned_data');
        $r = $this->database->select_related($meta, $other_meta, 123);
        $this->assertEqual($r, 'returned_data');
        }

    function test_select_related_with_col()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    // Works the same with many_to_many
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    // This will tell DB_Table to get a single column
                    'get'=>'col',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'where'=>"T2.this_id=123",
            'get'=> 'col',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $r = $this->database->select_related($meta, $other_meta, 123);
        }

    function test_select_related_with_sort_order()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    // Works the same with many_to_many
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    // Order, group passed to DB_Table if present
                    'order'=>'foo',
                    'group'=>'G',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'where'=>"T2.this_id=123",
            'group'=>'G',
            'order'=> 'foo',
            'get'=>'all',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $r = $this->database->select_related($meta, $other_meta, 123);
        }

    // When selecting an aliased field, the URL must be the real table name not the alias
    function test_select_related_with_alias()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            'real_key' => 'unaliased_key',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/unaliased_key/', T2.id) AS url,'unaliased_key' AS _table",
            'where'=>"T2.this_id=123",
            'get'=> 'all',
            //### TODO: order
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, NULL, NULL));
        $this->database->select_related($meta, $other_meta, 123);
        }

    function test_select_related_with_limit()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'select' => 'T2.a',
                    'foreign_key'=>'this_id',
                    'limit' => Array(1,2),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_query = Array(
            'select'=>"T2.a,CONCAT('/other/', T2.id) AS url,'other' AS _table",
            'where'=>"T2.this_id=123",
            'get'=> 'all',
            );
        $this->db_table->expectOnce('select', Array($expected_query, NULL, NULL, 1, 2));
        $this->db_table->setReturnValue('select', 'returned_data');
        $this->database->select_related($meta, $other_meta, 123);
        }
    }
    
class DSMysqlDatabaseUpdateTestCase
    extends DSMysqlDatabaseTestBase
    {
    function test_update()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                'ignored' => Array(),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $record = Array('title'=>'Title', 'ignored'=>'not stored', 'id'=>'not stored');
        $expected_record = $record;

        // ID is never updated
        unset($expected_record['id']);
        unset($expected_record['ignored']);
        $expected_where_clause = "T.id='url_name'";
        $this->db_table->expectOnce('update', Array($expected_record, $expected_where_clause));

        // Returns record ID
        $expected_query = Array(
            'select'=>"T.id",
            'where'=>$expected_where_clause,
            'get'=> 'one',
            );
        $this->db_table->expectOnce('select', Array($expected_query));
        $this->db_table->setReturnValue('select', 'returned_id');

        $r = $this->database->update($meta, $record);
        $this->assertEqual($r, 'returned_id');
        }

    function test_update_default_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        foreach (Array('Title', Array('Title')) as $record)
            {
            $this->setup();
            $expected_record = Array('title'=>'Title');
            $this->db_table->expectOnce('update', Array($expected_record, '*'));
            $this->database->update($meta, $record);
            }
        }

    function test_update_with_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                'title' => Array('type'=>'varchar'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $record = Array('title'=>'Title', 'f'=>'not stored', 'id'=>'not stored');
        $expected_record = $record;

        // ID, URL never updated
        unset($expected_record['id']);
        unset($expected_record['f']);
        $expected_where_clause = "T.f='url_name'";
        $this->db_table->expectOnce('update', Array($expected_record, $expected_where_clause));

        // Returns record ID
        $expected_query = Array(
            'select'=>"T.id",
            'where'=>$expected_where_clause,
            'get'=> 'one',
            );
        $this->db_table->expectOnce('select', Array($expected_query));
        $this->db_table->setReturnValue('select', 'returned_id');

        $r = $this->database->update($meta, $record);
        $this->assertEqual($r, 'returned_id');
        }

    function test_update_with_implode()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'i' => Array(
                    'type'=>'implode',
                    // Fields can be anything present in the record
                    'keys'=>Array('x', 'y', 'z', 'empty1', 'empty2', 'not-present'),
                    'implode' => '|',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $record = Array(
            'x'=>'abc', // Scalar
            'y'=>Array('def', 'ghi'), // 1D array
            'z'=>Array(Array('a'=>'jkl', 'b'=>'mno'), Array('a'=>'pqr')), // 2D array
            'empty1'=>'',
            'empty2'=>Array(),
            );
        $expected_record = Array('i'=>'abc|def|ghi|jkl|mno|pqr');
        $expected_where_clause = "T.id='url_name'";
        $this->db_table->expectOnce('update', Array($expected_record, $expected_where_clause));
        $this->database->update($meta, $record);
        }

    function test_update_with_mysql_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'title' => Array('type'=>'varchar', 'mysql_field'=>'aliased'),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $this->db_table->setReturnValue('nextId', 123);
        $record = Array('title'=>'Title');
        $expected_record = Array('aliased'=>'Title');
        $expected_where_clause = "T.id='url_name'";
        $this->db_table->expectOnce('update', Array($expected_record, $expected_where_clause));
        $this->database->update($meta, $record);
        }
    }
    
class DSMysqlDatabaseDeleteTestCase
    extends DSMysqlDatabaseTestBase
    {
    function test_delete()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'key' => 'url_path',
            'path_info'=>'url_name',
            'fields'=>Array(), // Not required for delete
            );
        $expected_where_clause = "T.id='url_name'";
        $this->db_table->expectOnce('delete', Array($expected_where_clause));
        $r = $this->database->delete($meta);
        $this->assertTrue($r);
        }

    function test_delete_with_url()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'key' => 'url_path',
            'path_info'=>'url_name',
            'fields'=>Array(
                'url' => Array('select'=>'f'),
                ),
            );
        $expected_where_clause = "T.f='url_name'";
        $this->db_table->expectOnce('delete', Array($expected_where_clause));
        $r = $this->database->delete($meta);
        $this->assertTrue($r);
        }

    // Caller can send true ID as a hint
    function test_delete_with_id()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'key' => 'url_path',
            'path_info'=>'url_name',
            'fields'=>Array(        // Ignored if ID supplied
                'url' => Array('select'=>'ignored'),
                ),
            );
        $expected_where_clause = "T.id='123'";
        $this->db_table->expectOnce('delete', Array($expected_where_clause));
        $r = $this->database->delete($meta, 123);
        $this->assertTrue($r);
        }

    function test_delete_related_one_to_many()
        {
        // Default is to delete related fields
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'foreign_key'=>'this_id',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>1,
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_where_clause = "T2.this_id=123";
        $this->db_table->expectOnce('delete', Array($expected_where_clause));
        $r = $this->database->delete_related($meta, $other_meta, 123);
        $this->assertTrue($r);
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        }
    
    function test_delete_related_one_to_many_with_keep_related_flag()
        {
        // Default is to do nothing
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'foreign_key'=>'this_id',
                    'keep_related'=>true,
                    'lookup'=>'other',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>1,
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $expected_where_clause = "T2.this_id=123 AND other='456'";
        $this->db_table->expectCallCount('delete', 3);
        $this->db_table->expectAt(0, 'delete', Array($expected_where_clause));
        $r = $this->database->delete_related($meta, $other_meta, 123, 456);
        $this->assertTrue($r);
        // Sets up other meta
        $this->assertEqual($this->db_table->table, 'T2');
        
        // test with multiple data records as an array
        $data = Array(
            0 => Array(
                'other'=>'456',
                'junk'=>'not relevant',
                ),
            1 => Array(
                'junk'=>'ignored',
                'other'=>'789',
                ),
            );
        $expected_where_clause = "T2.this_id=123 AND other='456'";
        $expected_where_clause2 = "T2.this_id=123 AND other='789'";
        $this->db_table->expectAt(1, 'delete', Array($expected_where_clause));
        $this->db_table->expectAt(2, 'delete', Array($expected_where_clause2));
        $r = $this->database->delete_related($meta, $other_meta, 123, $data);
        $this->assertTrue($r);
        }

    function test_delete_related_one_to_many_immutable()
        {
        // Default is to delete related fields
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    'foreign_key'=>'this_id',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mutable'=>0, // table is read-only
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $this->db_table->expectNever('delete');
        $r = $this->database->delete_related($meta, $other_meta, 123);
        $this->assertTrue($r);
        }

    function test_delete_related_many_to_many()
        {
        // Default is to do nothing
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array('a'=>Array('type'=>'integer')),
            'key' => 'other',
            );
        $this->db_table->expectNever('delete');
        $r = $this->database->delete_related($meta, $other_meta, 123);
        $this->assertTrue($r);
        }

    function test_delete_links()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_many',
                    'link'=>'L',
                    // First key is "this" table, second key is "other" table
                    'keys'=>Array('this_id', 'other_id'),
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $expected_sql = "DELETE FROM L WHERE this_id=789";
        $this->db->expectOnce('query', Array($expected_sql));
        $r = $this->database->delete_links($meta, $other_meta, 789);
        $this->assertTrue($r);
        //### test error handling
        }

    function test_delete_links_ignores_one_to_many()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'one_to_many',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(),
            'key' => 'other',   // Used to look up field defs for relation
            );
        $this->db->expectNever('query');
        $r = $this->database->delete_links($meta, $other_meta, 789);
        $this->assertTrue($r);
        }
    }
    
class DSMysqlDatabaseReplaceFieldTestCase
    extends DSMysqlDatabaseTestBase
    {
    // Replaceable field needs to be many_to_one with lookup entry
    function test_is_replaceable()
        {
        $meta = Array(
            'fields'=>Array(
                'a' => Array('type'=>'any other type'),
                'b' => Array('type'=>'any other type', 'lookup'=>'with lookup'),
                'c' => Array('type'=>'many_to_one'), // No lookup
                'd' => Array('type'=>'many_to_one', 'lookup'=>''), // Empty lookup
                'e' => Array('type'=>'many_to_one', 'lookup'=>'foo'),
                ),
            );
        $this->assertFalse($this->database->is_replaceable($meta, 'a'));
        $this->assertFalse($this->database->is_replaceable($meta, 'b'));
        $this->assertFalse($this->database->is_replaceable($meta, 'c'));
        $this->assertFalse($this->database->is_replaceable($meta, 'd'));
        $this->assertTrue($this->database->is_replaceable($meta, 'e'));
        }

    function test_replace_field()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_one',
                    'lookup'=>'a',
                    'select' => 'ignored',
                    'join' => 'ignored',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $test = "x'yz";

        // Calls select, which "finds" a record
        $expected_query = Array(
            'select'=>"T2.id",
            'where'=>"T2.a='x\'yz'",
            'get'=> 'one',
            );
        $this->db_table->expectOnce('select', Array($expected_query));
        $this->db_table->setReturnValue('select', 123);

        // Insert is never called
        $this->db_table->expectNever('insert');

        $r = $this->database->replace_field($meta, $other_meta, 'other', $test);
        $this->assertEqual($r, 123);
        }

    function test_replace_field_not_found()
        {
        $meta = Array(
            'mysql_table'=>'T',
            'fields'=>Array(
                'other' => Array(
                    'type'=>'many_to_one',
                    'lookup'=>'a',
                    'select' => 'ignored',
                    'join' => 'ignored',
                    ),
                ),
            'key' => 'url_path',
            'path_info'=>'url_name',
            );
        $other_meta = Array(
            'mysql_table'=>'T2',
            'fields'=>Array(
                'a'=>Array('type'=>'integer'),
                ),
            'key' => 'other',
            );
        $test = 123;

        // Calls select, which does not find the record
        $this->db_table->expectOnce('select');
        $this->db_table->setReturnValue('select', NULL);

        // This causes insert to be called
        $this->db_table->setReturnValue('nextId', 456);
        $expected_record = Array('a'=>123, 'id'=>456);
        $this->db_table->expectOnce('insert', Array($expected_record));

        $r = $this->database->replace_field($meta, $other_meta, 'other', $test);
        $this->assertEqual($r, 456);
        }
    }
