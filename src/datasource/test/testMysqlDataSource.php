<?php
// $Id$
// Tests for MySQL DataSource class
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
    
// Integration test -- uses the real database
class MysqlDataSourceTestBase
    extends UnitTestCase
    {
    var $config = Array(
        // Test table, used for basic tests on DS CRUD+search
        'test' => Array(
            'title'=>'Test',
            'description'=>'Test table',
            'mutable'=>TRUE,
            'storage'=> 'mysql',

            // Storage type 'mysql' is followed by configuration for the storage layer
            'mysql_table'=>'Test_Title',

            // A list of fields in this table
            // TODO: At present, the retrieve function uses the field names as mysql column names
            //       It would be nice to be able to define the mysql name as an option
            'fields'=>Array(
                'url' => Array(
                    'select'=> 'token',
                    ),
                //### TODO: move tests for this behaviour into this file

                // Most tables will have a title field
                'title' => Array(
                    // Require, type, etc. as PEAR DC_Table for mysql storage
                    'require'=>1,
                    'type'=>'varchar',
                    'size'=>'255',
                    ),
                'description' => Array(
                    // 'require'=>0, // Default
                    'type'=>'text', // translates to 'clob' in DB_Table
                    ),
                ),

            // Defines the way search will be handled for this table.
            //### TODO: This level of search should be available without any configuration
            //###       Remove this block but add explicit test that search config is used by DS
            'search'=>Array(
                // A search must return title, and summary fields
                //### TODO: rename 'select' ???
                //### TODO: change to string per DB_Table query array ???
                'fields' => Array('t.title','t.description AS summary'),

                // The indexes define what criteria are acceptable in a query
                'index' => Array(
                    'default' => Array('type'=>'fulltext', 'fields'=>'t.title,t.description'), //###,t.misc
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
        if (!isset($config['test']['pear_db']))
            $config['test']['pear_db'] = $MODULE->get_pear_db();
        $this->ds = new DataSource($config);
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

    function assertResults($r, $offset, $count, $total, $accuracy='exact')
        {
        $this->assertEqual($r['count'], $count, 'count: %s');
        $this->assertEqual(count($r['data']), $r['count'], 'inconsistent count: %s');
        $this->assertEqual($r['offset'],  $offset, 'offset: %s');
        $this->assertEqual($r['total'], $total, 'total: %s');
        $this->assertEqual($r['accuracy'], $accuracy, 'accuracy: %s');
        }
    }
    
class MysqlDataSourceCrudTestCase
    extends MysqlDataSourceTestBase
    {
    function test_crud()
        {
        $url = '/test/item1';
        $input = Array(
            'slug' => 'item1',
            'title' => "A \"test\" item",
            'description' => "Test \"description\"",
            );

        // Create
        $record = $this->ds->create('/test', $input);
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual($record['title'], $input['title']);
        $this->assertEqual($record['description'], $input['description']);

        // Retrieve
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual($record['title'], $input['title']);
        $this->assertEqual($record['description'], $input['description']);

        // Update
        $record = $this->ds->update($url, Array('title'=>'foo'));
        $this->assertEqual($record['title'], 'foo');
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual($record['title'], 'foo');
        $this->assertEqual($record['description'], $input['description']);

        // Delete
        $this->assertTrue($this->ds->delete($url), 'Delete returns false');
        $this->assertNoError();

        // Item has gone
        $this->assertNull($this->ds->retrieve($url));
        $this->assertError(404);

        // Deleting twice returns NULL
        $this->assertFalse($this->ds->delete($url));
        }

    //### TODO:
    //###   test crud with relations

    function test_many_to_one_crud()
        {
        // Set up DS with a M:1 field
        $config = $this->config;
        $config['test']['fields']['dataset'] = Array(
            'type'=>'many_to_one',
            'select'=>'Test_Dataset.name AS dataset',
            'join'=>'JOIN Test_Dataset ON Test_Title.dataset_id=Test_Dataset.id',
            'lookup'=>'name',
            'mysql_field' => 'dataset_id',
            );
        $config['dataset'] = Array(
            'title'=>'Dataset',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Test_Dataset',
            'fields'=>Array(
                // Most tables will have a title field
                'name' => Array(
                    'require'=>1,
                    'type'=>'varchar',
                    'size'=>'100',
                    ),
                ),
            );
        $this->setup($config);

        // Default items have 'Unknown' dataset
        $record = $this->ds->retrieve('/test/single');
        $this->assertNoError();
        $this->assertEqual($record['dataset'], 'Unknown');

        // Add an item with a new value for M:1 field
        $input = Array(
            'title' => "Test M:1",
            'dataset'=>'New dataset',
            );
        $record = $this->ds->create('/test', $input);
        $this->assertNoError();
        $this->assertEqual($record['dataset'], $input['dataset']);

        // Update item with another value
        $input['dataset'] = 'New dataset 2';
        $record = $this->ds->update($record['url'], $input);
        $this->assertNoError();
        $this->assertEqual($record['dataset'], $input['dataset']);

        // Clean up
        $this->ds->delete($record['url']);
        }
    
    // Implode should work with replaced many_to_one fields
    function test_many_to_one_with_implode()
        {
        // Set up DS with a M:1 field
        $config = $this->config;
        $config['test']['fields']['dataset'] = Array(
            'type'=>'many_to_one',
            'select'=>'Test_Dataset.name AS dataset',
            'join'=>'JOIN Test_Dataset ON Test_Title.dataset_id=Test_Dataset.id',
            'lookup'=>'name',
            'mysql_field' => 'dataset_id',
            );
        // change the description field to be an implode field
        $config['test']['fields']['description'] = Array(
            'type'=>'implode',
            'keys'=>Array('dataset'),
            'implode' => ' @@ ',
            );
        $config['dataset'] = Array(
            'title'=>'Dataset',
            'mutable'=>TRUE,
            'storage'=> 'mysql',
            'mysql_table'=>'Test_Dataset',
            'fields'=>Array(
                // Most tables will have a title field
                'name' => Array(
                    'require'=>1,
                    'type'=>'varchar',
                    'size'=>'100',
                    ),
                ),
            );
        $this->setup($config);

        // Add an item with a new value for M:1 field
        $input = Array(
            'title' => "Test M:1",
            'dataset'=>'New dataset',
            );
        $record = $this->ds->create('/test', $input);
        $this->assertNoError();
        $this->assertEqual($record['description'], $input['dataset']);

        // Update item with another value
        $input['dataset'] = 'New dataset 2';
        $record = $this->ds->update($record['url'], $input);
        $this->assertNoError();
        $this->assertEqual($record['description'], $input['dataset']);

        // Clean up
        $this->ds->delete($record['url']);
        }

    // The tests for read-only related tables exist in DSMysqlDatabaseTestCase
    // This is here to bring it all together and test the related links at a higher level
    function test_related_immutable()
        {
        // Set up DS with a M:M and 1:M related fields
        $config = $this->config;
        $config['test']['fields']['keyword'] = Array(
            'type'=>'many_to_many',
            'link'=>'Test_TitleKeyword',
            'select' => 'title',
            'keys'=>Array('title_id', 'keyword_id'),
            'get'=>'col',
            'lookup'=>'title',
            'split'=>'; *',
            );
        $config['test']['fields']['media'] = Array(
            'type'=>'one_to_many',
            'foreign_key'=>'title_id',
            'select' => 'title',
            );
        $config['keyword'] = Array(
            'title'=>'Keyword',
            'mutable'=>FALSE,
            'storage'=> 'mysql',
            'mysql_table'=>'Test_Keyword',
            'fields'=>Array(
                'title' => Array(
                    'require'=>1,
                    'type'=>'varchar',
                    'size'=>'255',
                    ),
                ),
            );
        $config['media'] = Array(
            'title'=>'Media',
            'mutable'=>FALSE,
            'storage'=>'mysql',
            'mysql_table'=>'Test_Media',
            'fields'=>Array(
                'title' => Array(
                    'type'=>'varchar',
                    'size'=>'255',
                    ),
                'title_id' => Array(
                    'require'=>1,
                    'type'=>'integer',
                    ),
                ),
            );
        $this->setup($config);

        $url = '/test/item2';
        $input = Array(
            'slug' => 'item2',
            'title' => "Test related immutable",
            'keyword' => Array("Keyword1", 'Test 1'),
            'media' => Array(Array('title'=>'Test Title MP3')),
            );

        // Create
        $record = $this->ds->create('/test', $input);
        // only existing keyword is now linked
        $this->assertEqual($record['keyword'], Array('Test 1'));
        $this->assertTrue(empty($record['media']));

        // Update
        $record = $this->ds->update($url, Array('title'=>'foo', 'keyword'=>Array('Keyword 3', 'Test 2')));
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual($record['title'], 'foo');
        // only the new keyword is now linked
        $this->assertEqual($record['keyword'], Array('Test 2'));

        // Delete
        $this->assertTrue($this->ds->delete($url), 'Delete returns false');
        $this->assertNoError();

        // Item has gone
        $this->assertNull($this->ds->retrieve($url));
        $this->assertError(404);
        }
    }
    
class MysqlDataSourceSearchTestCase
    extends MysqlDataSourceTestBase
    {
    function test_search_errors()
        {
        // Invalid count/offset
        $r = $this->ds->search('/test', '', 0, -1);
        $this->assertError(400);
        $this->assertTrue(is_null($r));
        $r = $this->ds->search('/test', '', -1, 10);
        $this->assertError(400);
        $this->assertTrue(is_null($r));

        // No such table
        $r = $this->ds->search('/foo', 'bar', 0, 1000);
        $this->assertError(404);
        $this->assertTrue(is_null($r));
        }

    function test_search_no_results()
        {
        $r = $this->ds->search('/test', 'notfound', 0, 1000);
        $this->assertError(400);
        $this->assertResults($r, 0, 0, 0);
        }

    function test_search_single()
        {
        // No slash on table name
        $r = $this->ds->search('test', 'single', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 1, 1);
        $this->assertEqual($r['data'][0]['url'], '/test/single');
        $this->assertEqual($r['data'][0]['title'], 'single');
        $this->assertEqual($r['data'][0]['summary'], 'Test item');
        }

    function test_search_many()
        {
        $r = $this->ds->search('/test', 'manymany', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 10, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many000');

        // Get a second page (diff size)
        $r = $this->ds->search('test', 'manymany', 10, 12);
        $this->assertNoError();
        $this->assertResults($r, 10, 12, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many010');

        // Get more records than are available
        $r = $this->ds->search('/test', 'manymany', 20, 10);
        $this->assertNoError();
        $this->assertResults($r, 20, 5, 25);
        $this->assertEqual($r['data'][0]['url'], '/test/many020');

        // Can fetch a random record from the results
        $r = $this->ds->search('/test', 'manymany', 0, 25);
        $this->assertResults($r, 0, 25, 25);
        $url = $r['data'][rand(0, 14)]['url'];
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        }

    function test_search_count()
        {
        // Search for 0 returns count only
        $r = $this->ds->search('/test', 'manymany', 0, 0);
        $this->assertNoError();
        $this->assertResults($r, 0, 0, 25);
        }

    function test_search_all()
        {
        $r = $this->ds->search('/test', '', 0, 1000);
        $this->assertNoError();
        // This doesn't account for persistent items
        // created during the test:
        // $this->assertResults($r, 0, 26, 26);
        // so have put equivalent tests inline:
        $this->assertTrue($r['count'] >= 26, 'count');
        $this->assertTrue(count($r['data']) == $r['count'], 'inconsistent count');
        $this->assertTrue($r['offset'] == 0, 'offset');
        $this->assertTrue($r['total'] >= 26, 'total');
        $this->assertTrue($r['accuracy'] == 'exact', 'accuracy');
        }

    function test_search_all_default()
        {
        //### TODO: Need a better way to test default search
        $config = $this->config;
        // No indexes
        unset($config['test']['search']);
        $this->setup($config);

        $r = $this->ds->search('/test', '', 0, 1000);
        $this->assertNoError();
        // This doesn't account for persistent items
        // created during the test:
        // $this->assertResults($r, 0, 26, 26);
        // so have put equivalent tests inline:
        $this->assertTrue($r['count'] >= 26, 'count');
        // Default search provides URL, title, summary
        $this->assertTrue(isset($r['data'][0]['url']));
        $this->assertTrue(isset($r['data'][0]['title']));
        $this->assertTrue(isset($r['data'][0]['summary']));
        }

    function test_search_advanced()
        {
        // Same as 'single'
        $r = $this->ds->search('/test', 'default=single', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 1, 1);

        // Title search
        //### Doesn't work because '010' is shorter than default FT_LEN
        //$r = $this->ds->search('/test', '{title=manymany 010}', 0, 10);
        //### should be: $r = $this->ds->search('/test', 'title=(010 manymany)', 0, 10);
        //$this->assertNoError();
        //$this->assertResults($r, 0, 1, 1);

        // Title search, phrase
        $r = $this->ds->search('/test', '{title="manymany 010"}', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 1, 1);

        // Two searches linked with Boolean
        $r = $this->ds->search('/test', '{default=single} OR {title="manymany 010"}', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 2, 2);

        // Rejects unknown indexes
        $r = $this->ds->search('/test', 'undefined=single', 0, 10);
        $this->assertError(400);
        }
    }
    
require_once $CONF['path_lib'] . 'sphinxapi.php'; // For consts

class MysqlSphinxIntegrationTestCase
    extends MysqlDataSourceTestBase
    {
    function test_search_sphinx()
        {
        // setup sphinx config
        $this->config['test']['storage_search'] = 'sphinx';
        $this->config['test']['search']['sphinx'] = TRUE;
        $this->setup($this->config);
        $r = $this->ds->search('test', 'sphinx', 0, 10);
        $this->assertNoError();
        $this->assertResults($r, 0, 1, 1);
        $this->assertEqual($r['data'][0]['url'], '/test/single');
        $this->assertEqual($r['data'][0]['title'], 'single');
        $this->assertEqual($r['data'][0]['summary'], 'Test item');
        }
    }
