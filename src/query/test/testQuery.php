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
Mock::generate('Query');
Mock::generate('QueryCache');
Mock::generate('QueryEncoder');
Mock::generate('QueryCriteria');

// Useful assertions for checking query objects
class QueryTestCaseBase
    extends UnitTestCase
    {
    var $config = Array(
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
        $this->query = $this->new_query();
        }

    function new_query( $criteria_defs=NULL )
        {
        global $MODULE;
        $config = $this->config;
        if ($criteria_defs)
            $config['criteria_defs'] = $criteria_defs;
        $config['is_page_size_mandatory'] = FALSE;
        unset($MODULE->query_config);//### TEMP -- to remove the query_name field
        return QueryFactory::create($MODULE, $config);
        }
    
    function assertError($error_code)
        {
        $this->assertEqual($this->query->error_code, $error_code, 'error code not set');
        $this->assertNotEqual($this->query->error_message, '', 'error message not set');
        }

    function assertNoError()
        {
        $this->assertEqual($this->query->error_code, 0, 'error code set');
        $this->assertEqual($this->query->error_message, '', 'error message set');
        }

    function assertResultsInfo($results_count=0)
        {
        global $CONF;
        $this->assertEqual($this->query->info['results_count'], $results_count, 'results count');
        $page_count = (int)(($results_count + $this->query->get_page_size() - 1)/$this->query->get_page_size());
        $this->assertEqual($this->query->info['page_count'], $page_count, 'page count: %s');
        // Check prev and first pages
        if ($results_count == 0 || $this->query->page == 1)
            {
            $this->assertEqual($this->query->info['page_prev_url'], '', 'prev set');
            $this->assertEqual($this->query->info['page_first_url'], '', 'first page set');
            }
        else if ($results_count)
            {
            $criteria = Array('page'=>$this->query->page - 1);
            $this->assertEqual($this->query->info['page_prev_url'], $this->query->url($criteria), 'prev unset');
            $this->assertEqual($this->query->info['page_first_url'], $this->query->url(Array('page'=>1)), 'first page unset');
            }
        // Check next and last pages
        if ($results_count == 0 || $this->query->page == $page_count)
            {
            $this->assertEqual($this->query->info['page_next_url'], '', 'next set');
            $this->assertEqual($this->query->info['page_last_url'], '', 'last page set');
            }
        else if ($results_count)
            {
            $criteria = Array('page'=>$this->query->page + 1);
            $this->assertEqual($this->query->info['page_next_url'], $this->query->url($criteria), 'next unset');
            $this->assertEqual($this->query->info['page_last_url'], $this->query->url(Array('page'=>$page_count)), 'last page unset');
            }
        //### TODO: Add $query->page_msg_format = "Page %d of %d";
        if ($results_count)
            $this->assertNotEqual($this->query->info['page_message'], '', 'page_message unset');
        else
            $this->assertEqual($this->query->info['page_message'], '', 'page_message set');
        // Check paged results counts
        if ($results_count)
            {
            $this->assertEqual($this->query->info['first_in_page'],
                    (($this->query->page-1) * $this->query->get_page_size()) + 1, 'first_in_page');
            $this->assertEqual($this->query->info['last_in_page'],
                    min($this->query->info['first_in_page'] + $this->query->get_page_size() - 1, $results_count), 'last_in_page');
            }
        else {
            $this->assertEqual($this->query->info['first_in_page'], 0, 'first_in_page not 0');
            $this->assertEqual($this->query->info['last_in_page'], 0, 'last_in_page not 0');
            }
        //### TODO: Add $query->results_msg_format = "Results %d to %d of %d";
        if ($results_count == 0)
            {
            $this->assertEqual($this->query->info['results_message'], 'No results', 'results_message 0 results');
            $this->assertEqual($this->query->info['results_message_unpaged'], 'No results', 'results_message unpaged 0 results');
            }
        else if ($results_count == 1)
            {
            $this->assertEqual($this->query->info['results_message'], '1 result', 'results_message 1 result');
            $this->assertEqual($this->query->info['results_message_unpaged'], '1 result', 'results_message unpaged 1 result');
            }
        else if ($results_count <= $this->query->get_page_size())
            {
            $this->assertEqual($this->query->info['results_message'], $results_count . ' results', 'results_message 1 page');
            $this->assertEqual($this->query->info['results_message_unpaged'], $results_count . ' results', 'results_message unpaged 1 page');
            }
        else {
            $this->assertEqual($this->query->info['results_message'],
                    $this->query->info['first_in_page'].'-'.$this->query->info['last_in_page'].' of '.$results_count.' results', 'results_message >1 page');
            $this->assertEqual($this->query->info['results_message_unpaged'], $results_count . ' results', 'results_message unpaged >1 page');
            }

        // Check page_urls array (intermediate page links)
        if ($results_count == 0)
            $this->assertEqual(count($this->query->info['page_urls']), 0, 'page_urls not empty');
        else if ($page_count <= $CONF['intermediate_pages_size'])
            $this->assertEqual(count($this->query->info['page_urls']), $page_count, 'page_urls size: %s');
        else
            $this->assertEqual(count($this->query->info['page_urls']), $CONF['intermediate_pages_size'], 'page_urls size: %s');
        
        // check highlighted record
        if ($results_count && $this->query->page == 1)
            $this->assertTrue(isset($this->query->info['highlighted_record']) && $this->query->info['highlighted_record'] > 0);
        else
            $this->assertFalse(isset($this->query->info['highlighted_record']));
        }
    }

/// Test basic query functions
class QueryTestCase
    extends QueryTestCaseBase
    {
    function setup()
        {
        global $MODULE;
        $this->query = QueryFactory::create( $MODULE );
        }

    function test_serialize()
        {
        $ser = serialize($this->query);

        // DataSource is set to NULL before serialization
        $this->assertTrue(strpos($ser, '"module";N') !== FALSE);

        // DS restored by unserialization
        $query = unserialize($ser);
        $this->assertNotNull($query->module);
        }

    function test_get_table()
        {
        $this->assertEqual('dummy/test', $this->query->get_table());
        $this->assertEqual('dummy/foo', $this->query->get_table('foo'));
        }
    
    function test_demo_criteria()
        {
        // Default criteria are not an error
        $this->assertNoError();
        // Empty search returns all results
        $this->assertEqual( $this->query['q']->get_value(), '');
        $this->assertEqual( $this->query['text']->get_value(), '' );
        $this->assertEqual( $this->query['sort']->get_value(), '' );
        $results = $this->query->search();
        $this->assertNoError();
        $this->assertTrue($this->query->info['results_count'] >= 26);
        }

    function test_set_criteria_values() 
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('set_values', Array('foo'));
        $this->query->set_criteria_values('foo');
        }

    function test_set_criteria_values_calls_module_process_criteria() 
        {
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->module = new MockModule();
        $this->query->module->expectOnce('process_criteria', Array('*', 'foo'));
        $this->query->set_criteria_values('foo');
        }
        
    
    function test_set_criteria_values_page_size()
        {
        // Page, page_size set via criteria
        $this->query->set_criteria_values(Array('q'=>'foo', 'page'=>789, 'page_size'=>1001));
        $this->assertEqual($this->query->page, 789);
        $this->assertEqual($this->query->get_page_size(), 1001);

        // Page is always set even if not present
        $this->query->set_criteria_values();
        $this->assertEqual($this->query->page, 1);
        $this->assertEqual($this->query->get_page_size(), 1001);
        }

    function test_set_criteria_page_size()
        {
        $this->query = $this->new_query( Array(
            Array( 'name' => 'q', 'value' => 'foo' ),
            Array( 'name' => 'page', 'value' => 789 ),
            Array( 'name' => 'page_size', 'value' => 1001 ),
            ));
        $this->assertEqual( $this->query['q']->get_value(), 'foo' );
        $this->assertEqual($this->query->page, 789);
        $this->assertEqual($this->query->get_page_size(), 1001);

        $this->query = $this->new_query( Array(
            Array( 'name' => 'q', 'value' => 'foo' ),
            ));
        // Page is always set even if not present
        $this->assertEqual($this->query->page, 1);
        }

    function test_set_criteria_date_range()
        {
        $this->query = $this->new_query( Array(
            Array( 'name' => 'q', 'value' => 'foo' ),
            Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ),
            ));
        $this->assertEqual( $this->query['date']->get_value(), Array("1974-01-01","2010-12-31") );
        }

    function test_set_criteria_with_defaults()
        {
        $this->query = $this->new_query( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y' ) ));

        // assign default values for x and y

        $this->query['x']->set_default( 'foo' );
        $this->query['y']->set_default( '123' );
        $this->query->set_criteria_values();
        $this->assertNoError();
        $this->assertEqual( $this->query['x']->get_value(), 'foo' );
        $this->assertEqual( $this->query['y']->get_value(), '123' );

        // set some criteria, default is overridden
        $this->query->set_criteria_values( Array('x'=>'bar', 'ignored'=>'123', 'y'=>'baz') );
        $this->assertNoError();
        $this->assertEqual( $this->query['x']->get_value(), 'bar' );
        $this->assertEqual( $this->query['y']->get_value(), 'baz' );
        }        

    function test_has_criteria()
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('has_criteria', Array('foo'));
        $this->query->criteria_container->setReturnValue('has_criteria', 'quux');
        $this->assertEqual('quux', $this->query->has_criteria('foo'));
        }

    function test_has_allowed_criteria()
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('has_allowed', Array('foo'));
        $this->query->criteria_container->setReturnValue('has_allowed', 'quux');
        $this->assertEqual('quux', $this->query->has_allowed_criteria('foo'));
        }

    function test_has_advanced()
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('has_advanced', Array('foo'));
        $this->query->criteria_container->setReturnValue('has_advanced', 'quux');
        $this->assertEqual('quux', $this->query->has_advanced('foo'));
        }

    function test_compare()
        {
        // Delegated to criteria container
        $other_query = new MockQuery();
        $other_query->criteria_container = 'foo';
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('compare', Array('foo'));
        $this->query->criteria_container->setReturnValue('compare', 'quux');
        $this->assertEqual('quux', $this->query->compare($other_query));
        }

    function test_compare_bad_arg()
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectNever('compare');
        $this->assertFalse($this->query->compare('not a query object'));
        }

    function test_search_no_results()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q' ),
             Array( 'name' => 'adv_text', 'index'=>'default', 'value'=>'?' ),
             Array( 'name' => 'sort', 'value' => ''),
         ));
        $criteria = Array('q'=>'notfound');
        $this->assertNull($this->query->search($criteria));
        $this->assertError(QUERY_ERROR_EMPTY);
        // criteria have been set
        //### TODO AV : there isn't a direct way atm to get the values correctly
        $this->assertEqual($this->query->criteria_container->get_qs_key_values(), Array('q'=>'notfound'));
        $this->assertTrue(count($this->query->results) == 0);
        $this->assertResultsInfo();
        }

    function test_search_no_args()
        {
        // First do a search
        $this->query->search(Array('q'=>'notfound'));

        // Searching with no args repeats the current search, it doesn't reset the criteria
        $this->query->search();
        $this->assertEqual($this->query->criteria_container->get_qs_key_values(), Array('q'=>'notfound'));
        $this->assertTrue(count($this->query->results) == 0);
        }

    function test_search_page_size()
        {
        $this->query->search(Array('page_size'=>18));
        $this->assertEqual($this->query->page, 1);
        $this->assertEqual(count($this->query->results), 18);
        }
    
    function test_export_format_list()
        {
        global $CONF, $STRINGS;
        $old_formats = $CONF['export_formats'];
        $CONF['export_formats'] = Array('foo');
        $STRINGS['export_formats']['foo'] = 'bar';
        // Re-create query with new config
        // $this->query = new DummyQuery();
        $this->query = $this->new_query();
        $this->assertEqual(Array('foo'=>'bar'), $this->query->get_list('export_formats'));
        unset($STRINGS['export_formats']['foo']);
        // Uses format name if no label defined
        // $this->query = new DummyQuery();
        $this->query = $this->new_query();
        $this->assertEqual(Array('foo'=>'foo'), $this->query->get_list('export_formats'));
        // Clean up
        $CONF['export_formats'] = $old_formats;
        }           

    function test_url()
        {
        global $MODULE;
        $this->assertEqual($this->query->url(), $MODULE->url('search'));
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q', 'value' => 'foo' )
             ));
        $this->assertEqual($this->query->url(), $MODULE->url('search', '?' . $this->query->url_query()));
        }        

    function test_url_query()
        {
        // Delegated to criteria container
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('query_string', Array('foo', 'bar', 123));
        $this->query->criteria_container->setReturnValue('query_string', 'quux');
        $this->query->page = 123;
        $this->assertEqual('quux', $this->query->url_query('foo', 'bar'));
        }
    
    // If not the default table, add the table name to the URL string
    function test_url_alt_table()
        {
        global $MODULE;
        $this->query->table_name = 'tbl';
        $this->assertEqual($this->query->url(), $MODULE->url('search', '/tbl'));
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q', 'value' => 'foo' )
             ));
        $this->query->table_name = 'tbl';
        $this->assertEqual($this->query->url(), $MODULE->url('search', '/tbl?' . $this->query->url_query()));
        }
    
    // if not the default table, add the table name to the URL string
    function test_url_new()
        {
        global $MODULE;
        $this->assertEqual($this->query->url_new(), $MODULE->url('search', '?mode=new'));
        $this->query->table_name = 'tbl';
        $this->assertEqual($this->query->url_new(), $MODULE->url('search', '/tbl?mode=new'));
        }

    function test_criteria_string()
        {
        $this->assertPattern('|all records|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        // some criteria
        $this->query->set_criteria_values( Array('q'=>'foo') );
        $this->assertPattern('|Search Dummy Module for: foo|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Search Dummy Module for</dt><dd>foo</dd>|', $this->query->criteria_string(QUERY_STRING_TYPE_HTML));
        $this->assertPattern('|<dt>Search Dummy Module for</dt><dd>foo</dd>|', $this->query->criteria_string());
        }

    // TODO AV - fix this
    // function test_criteria_string_calls_module_process_render_details() 
    //     {
    //     $this->query->criteria_container = new MockQueryCriteria();
    //     $this->query->module = new MockModule();
    //     $this->query->module->expectOnce('process_render_details', Array('*',null ));
    //     $this->query->set_criteria_values( Array('q'=>'foo') );
    //     $this->query->criteria_string();
    //     }

    function test_criteria_string_json()
        {
        global $MODULE;
        $this->query = QueryFactory::create($MODULE);
        $expected = json_encode(Array(
            Array(
                'name' => 'q','type' => 'text','label' => 'Search for',
                'list'=>Array('' => 'All fields','title' => 'Title','description' => 'Description','person' => 'Contributors','keyword' => 'Keywords'),
            ),
            Array( 'name' => 'text','type' => 'text','label' => 'Test' ),
            Array( 'name' => 'category','type' => 'list','label' => 'Genre' ),
            Array( 'name' => 'date','type' => 'drange','label' => 'Year','default'=>Array('1900-01-01','1930-12-31'),
                'list'=>Array(
                    ''=>1900,'1901'=>1901,'1902'=>1902,'1903'=>1903,'1904'=>1904,'1905'=>1905,'1906'=>1906,'1907'=>1907,'1908'=>1908, 
                    '1909'=>1909,'1910'=>1910,'1911'=>1911,'1912'=>1912,'1913'=>1913,'1914'=>1914, '1915'=>1915,'1916'=>1916,'1917'=>1917,
                    '1918'=>1918,'1919'=>1919,'1920'=>1920,'1921'=>1921, '1922'=>1922,'1923'=>1923,'1924'=>1924,'1925'=>1925,'1926'=>1926,
                    '1927'=>1927,'1928'=>1928,'1929'=>1929,'1930'=>1930 ),
            ),
            Array( 'name' => 'sort','type' => 'sort','label' => 'Sort by','list'=>Array('' => 'Date (oldest first)','date_desc' => 'Date (newest first)','title' => 'Title') ),
            Array( 'name' => 'page_size','type' => 'sort','label' => 'Display','default'=>10,'list' => Array('10' => '10','50' => '50','100' => '100') ),
        ));
        $this->assertEqual( $expected, $this->query->criteria_string(QUERY_STRING_TYPE_JSON) );
        }
        
    function test_criteria_string_date_range()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q', 'render_default' => 'All records', 'is_primary'=>TRUE ),
             Array( 'name' => 'date', 'type' => QC_TYPE_DATE_RANGE, 'range'=>Array( 1973, 1982 ) ),
             ));
        $this->query->set_criteria_values(Array('date_start'=>1974));
        $this->assertPattern('|Date from: 1974 to 1982|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Date from</dt><dd>1974 to 1982</dd>|', $this->query->criteria_string());

        $this->query->set_criteria_values(Array('date_start'=>1974, 'date_end'=>1974));
        $this->assertPattern('|Date: 1974|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));

        $this->assertPattern('|<dt>Date</dt><dd>1974</dd>|', $this->query->criteria_string());
        
        $expected = json_encode(Array(
             Array('name'=>'q','type'=>'text'),
             Array(
                 'name'=>'date','type'=>'drange',
                 'value'=>Array('1974-01-01','1974-12-31'),
                 'default'=>Array('1973-01-01','1982-12-31'),
                 'list'=>Array(''=>1973,'1974'=>1974,'1975'=>1975,'1976'=>1976,'1977'=>1977,'1978'=>1978,'1979'=>1979,'1980'=>1980,'1981'=>1981,'1982'=>1982)
             ),
            ));
       
        $this->assertEqual( $expected, $this->query->criteria_string(QUERY_STRING_TYPE_JSON) );
        }

    function test_criteria_string_advanced()
        {
        $this->query->set_criteria_values( Array(
             'q[0][v]' => 'foo', 'q[0][index]'=>'title', 'q[0][oper]'=>'and',
             'q[1][v]' => 'bar', 'q[1][index]'=>'person', 'q[1][oper]'=>'and',
             'q[2][v]' => 'test', "q[2]['oper']" => 'or',
             ));
        $this->assertPattern("|Search Dummy Module for: 'foo' in Title AND 'bar' in Contributors OR 'test' in All fields|",  
            $this->query->criteria_string(QUERY_STRING_TYPE_TEXT) );
        }

    function test_criteria_string_flags()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q', 'render_default' => 'All records', 'is_primary'=>TRUE ),
             Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category', 'list' => 'genres' )
             ));
        $genres_list = Array( 'Action', 'Adventure', 'Comedy', 'Romantic' );
        $this->query->add_list('genres', $genres_list);

        $this->query->set_criteria_values(Array('genre[0]'=>1 ));
        $this->assertPattern('|Category: Action|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Category</dt><dd>Action</dd>|', $this->query->criteria_string());

        $this->query->set_criteria_values(Array('genre[2]' => 1, 'genre[3]' => 1 ));
        $this->assertPattern('|Category: Comedy; Romantic|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Category</dt><dd>Comedy; Romantic</dd>|', $this->query->criteria_string());
        }

    function test_criteria_string_flags_assoc()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category', 'list' => 'genres' )
             ));
        $genres_list = Array( 'ac' => 'Action', 'ad' => 'Adventure', 'com' => 'Comedy', 'rom' => 'Romantic' );
        $this->query->add_list('genres', $genres_list);

        $this->query->set_criteria_values(Array('genre[ad]'=>1 ));
        $this->assertPattern('|Category: Adventure|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Category</dt><dd>Adventure</dd>|', $this->query->criteria_string());

        $this->query->set_criteria_values(Array('genre[com]' => 1, 'genre[rom]' => 1 ));
        $this->assertPattern('|Category: Comedy; Romantic|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Category</dt><dd>Comedy; Romantic</dd>|', $this->query->criteria_string());
        }

    function test_criteria_string_flags_with_list()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'genre', 
                    'type' => QC_TYPE_FLAG, 
                    'label' => 'Category', 
                    'list' => Array( 'Action', 'Adventure', 'Comedy', 'Romantic' ),
                    )
            ));
        $this->query->set_criteria_values(Array('genre'=>2 ));
        $this->assertPattern('|Category: Comedy|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Category</dt><dd>Comedy</dd>|', $this->query->criteria_string());
        }

    function test_criteria_string_flag_single_value()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'allowed', 
                    'type' => QC_TYPE_FLAG, 
                    'label' => 'Allowed', 
                    )
            ));
        $this->query->set_criteria_values(Array('allowed'=>'1' ));
        $this->assertPattern('|Allowed|', $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        $this->assertPattern('|<dt>Allowed</dt>|', $this->query->criteria_string());
        }

    function test_hash()
        {
        $this->query = $this->new_query( Array(
             Array( 'name' => 'q', 'value' => 'foo' )
             ));
        $other_query =  $this->new_query( Array(
              Array( 'name' => 'q', 'value' => 'foo' )
              ));
        $this->assertEqual( $this->query->hash(), $other_query->hash() );

        $other_query =  $this->new_query( Array(
              Array( 'name' => 'q', 'value' => 'boo' )
              ));
        $this->assertNotEqual( $this->query->hash(), $other_query->hash() );

        $this->query = $this->new_query( Array(
            Array( 'name' => 'p', 'value' => 'foo' ),
            Array( 'name' => 'q', 'value' => 'bar' ),
            ));
        $other_query =  $this->new_query( Array(
              Array( 'name' => 'q', 'value' => 'bar' ),
              Array( 'name' => 'p', 'value' => 'foo' ),
              ));
        $this->assertEqual( $this->query->hash(), $other_query->hash() );
        }

    function test_arrayaccess_get()
        {
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('offsetGet', Array('foo'));
        $this->query['foo'];
        }

    function test_arrayaccess_isset()
        {
        $this->query->criteria_container = new MockQueryCriteria();
        $this->query->criteria_container->expectOnce('offsetExists', Array('spark'));
        $this->query->criteria_container->setReturnValue('offsetExists', TRUE);
        $this->assertTrue( isset($this->query['spark']) );
        }
        
    
    function test_search_sets_results_count()
        {
        // Searching for results, then for no results, resets the results count
        $this->query->search(Array('q'=>'single'));
        $this->query->search(Array('q'=>'notfound'));
        $this->assertTrue(count($this->query->results) == 0);
        $this->assertResultsInfo();
        }

    function test_search_single()
        {
        $this->assertResultsInfo();
        $criteria = Array('q'=>'single');
        $results = $this->query->search($criteria);
        $this->assertNoError();
        $this->assertTrue(count($results) == 1);
        $this->assertEqual($results[0]['url'], '/dummy/test/single');

        // Results also in query object
        $this->assertEqual($results, $this->query->results);

        // Results info is set
        $this->assertResultsInfo(1);

        // Only one page
        $this->assertEqual($this->query->page, 1);
        }

    function test_search_many()
        {
        $this->assertEqual($this->query->page, 1);
        $this->assertEqual($this->query->get_page_size(), 10);

        // Get a page
        $criteria = Array('q'=>'many');
        $results = $this->query->search($criteria);
        $this->assertNoError();
        $this->assertTrue(count($results) == 10);
        $this->assertEqual($results[0]['url'], '/dummy/test/many000');
        $this->assertResultsInfo(25);

        // Get next page
        $criteria = Array('q'=>'many', 'page'=>2);
        $results = $this->query->search($criteria);
        $this->assertNoError();
        $this->assertTrue(count($results) == 10);
        $this->assertEqual($results[0]['url'], '/dummy/test/many010');
        $this->assertResultsInfo(25);

        // Page is set to 1 if not supplied in args
        $criteria = Array('q'=>'many');
        $results = $this->query->search($criteria);
        $this->assertNoError();
        $this->assertEqual($results[0]['url'], '/dummy/test/many000');
        $this->assertResultsInfo(25);


        // Results are capped if pagesize too big
        $criteria = Array('q'=>'many', 'page'=>1, 'page_size'=>1000);
        $results = $this->query->search($criteria);
        $this->assertNoError();
        $this->assertResultsInfo(25);
        }

    function test_get_record()
        {
        // Not found
        $record = $this->query->get_record('/notfound');
        $this->assertNull($record);
        $this->assertError(QUERY_ERROR_NOT_FOUND);

        // Success
        $record = $this->query->get_record('/dummy/test/single');
        $this->assertTrue(is_array($record));
        $this->assertEqual($record['url'], '/dummy/test/single');
        $this->assertEqual($record['title'], 'single');
        $this->assertEqual($record['description'], 'Test item');
        $this->assertNoError();
        }

    function test_get_list()
        {
        // Not found
        $list = $this->query->get_list('Foobar');
        // Does not set error
        $this->assertNoError();
        $this->assertNull($list);

        // Set of sets
        $list = $this->query->get_list('meta');
        $this->assertNoError();
        $this->assertTrue(count($list) >= 2);
        $this->assertTrue($list['meta'] == 'Meta-data');
        $this->assertTrue($list['test'] == 'Test');

        // Test data, no key so URL basename is used instead
        $list = $this->query->get_list('test');
        $this->assertTrue($list['single'] == 'single');
        }

    function test_add_list()
        {
        $data = Array('foo'=>'bar', 'baz'=>'plugh');
        $this->query->add_list('test', $data);
        $list = $this->query->get_list('test');
        $this->assertEqual($list, $data);
        }

    function test_demo_lists()
        {
        $this->assertNotNull($this->query->get_list('page_size'));
        $this->assertNotNull($this->query->get_list('sort'));
        $this->assertNotNull($this->query->get_list('list_search'));
        $this->assertNotNull($this->query->get_list('boolean_op'));
        }

    function test_search_form()
        {
        global $CONF, $QUERY, $MODULE, $USER;
        // We need to replace these globals, otherwise we get a segfault due (I think)
        // to recursion inside the mock code
        $old_query = $QUERY;
        $old_module = $MODULE;
        $QUERY = 'q';
        $MODULE = 'm';
        $this->query->table_name = 'quux';
        $this->query->module = new MockModule();
        //### TODO: Rename to inc-search_{table}.php
        $expected_vars = Array('MODE'=>'advanced', 'CONF'=>$CONF, 'QUERY'=>$QUERY, 'MODULE'=>$MODULE, 'USER'=>$USER);
        $this->query->module->expectOnce('get_template', Array('search_quux', $expected_vars));
        $this->query->module->setReturnValue('get_template', 'foo');
        $this->query->module->expectOnce('url', Array('search', '/quux'));
        $this->query->module->setReturnValue('url', 'bar');
        $result = $this->query->search_form('advanced');

        $expected_data = "\n<input id='search_data' type='hidden' value='[{\"name\":\"q\",\"type\":\"text\",\"label\":\"Search for\",\"list\":{\"\":\"All fields\",\"title\":\"Title\",\"description\":\"Description\",\"person\":\"Contributors\",\"keyword\":\"Keywords\"}},{\"name\":\"text\",\"type\":\"text\",\"label\":\"Test\"},{\"name\":\"category\",\"type\":\"list\",\"label\":\"Genre\"},{\"name\":\"date\",\"type\":\"drange\",\"label\":\"Year\",\"default\":[\"1900-01-01\",\"1930-12-31\"],\"list\":{\"\":1900,\"1901\":1901,\"1902\":1902,\"1903\":1903,\"1904\":1904,\"1905\":1905,\"1906\":1906,\"1907\":1907,\"1908\":1908,\"1909\":1909,\"1910\":1910,\"1911\":1911,\"1912\":1912,\"1913\":1913,\"1914\":1914,\"1915\":1915,\"1916\":1916,\"1917\":1917,\"1918\":1918,\"1919\":1919,\"1920\":1920,\"1921\":1921,\"1922\":1922,\"1923\":1923,\"1924\":1924,\"1925\":1925,\"1926\":1926,\"1927\":1927,\"1928\":1928,\"1929\":1929,\"1930\":1930}},{\"name\":\"sort\",\"type\":\"sort\",\"label\":\"Sort by\",\"list\":{\"\":\"Date (oldest first)\",\"date_desc\":\"Date (newest first)\",\"title\":\"Title\"}},{\"name\":\"page_size\",\"type\":\"sort\",\"label\":\"Display\",\"default\":10,\"list\":{\"10\":\"10\",\"50\":\"50\",\"100\":\"100\"}}]'/>\n";
        
        $expected = "<form id=\"search\" method=\"GET\" action=\"bar\" class=\"advanced-search\">foo" . $expected_data . "</form>";
        $this->assertEqual($result, $expected);
        $QUERY = $old_query;
        $MODULE = $old_module;
        }

    function test_search_copies_info()
        {
        $this->query->module = new MockModule();
        $this->query->module->setReturnValue('url', 'ignored');
        $this->query->module->setReturnValue('search', Array(
                'count'=>3,     // Not copied
                'data'=>Array(1, 2, 3), // Copied to ->results
                'offset'=>0,    // Not copied
                'total'=>3,     // Copied to info[results_count]
                'accuracy'=>'foo', // Copied to info[accuracy]
                'extra_fields'=>'bar'    // Other fields copied to info[$name]
                ));
        $this->query->search(Array('q'=>'foo'));
        $this->assertEqual($this->query->info['accuracy'], 'foo');
        $this->assertEqual($this->query->info['results_count'], 3);
        $this->assertEqual($this->query->info['extra_fields'], 'bar');
        }

    function test_query_results_cached()
        {
        // modify page size
        $this->query->set_page_size(4);

        $criteria = Array('q'=>'many');
        $results = $this->query->search($criteria);
        $this->assertEqual(array_slice($this->query->_cache->results['data'], 0, 4), $results);
        $this->assertEqual($this->query->_cache->criteria['q']->get_value(), $criteria['q']);
        $this->assertTrue(count($this->query->_cache->results['data']) > 4);
        }

    function test_cache_when_query_changes()
        {
        // use mock datasource
        $this->query->module = new MockModule();
        // DS search should be called three times as the query changes
        $this->query->module->expectCallCount('search', 3);

        $this->query->module->setReturnValue('search', Array('data'=>Array(1,2), 'count'=>2, 'offset'=>0, 'total'=>2, 'accuracy'=>'exact'));

        $this->query->search(Array('q'=>'many'));

        $this->query->search(Array('q'=>'many')); // Cache hit
        $this->query->search(Array('q'=>'many2'));
        $this->query->search(Array('q'=>'many2', 'sort'=>'title'));
        }

    // If the cache fails (e.g. when the database hangs) an error message was shown 
    // and the object didn't load. Prevent this by replacing invalid caches
    function test_broken_cache_is_replaced()
        {
        // use mock datasource
        $this->query->module = new MockModule();
        // DS search should be called twice because the cache is broken
        $this->query->module->expectCallCount('search', 2);
        $this->query->module->setReturnValue('search', Array('data'=>Array(1,2), 'count'=>2, 'offset'=>0, 'total'=>2, 'accuracy'=>'exact'));
        $this->query->search(Array('q'=>'many'));
        unset($this->query->_cache->results['data']);
        $this->query->search(Array('q'=>'many'));
        }

    function test_uses_cached_results()
        {
        $criteria = Array('q'=>'many');

        $full_data = Array(
            Array('title'=>'many1'),
            Array('title'=>'many2'),
            Array('title'=>'many3'),
            Array('title'=>'many4'),
            Array('title'=>'many5'),
            Array('title'=>'many6'),
            Array('title'=>'many7'),
            Array('title'=>'many8'),
            );

        $expected_data = array_slice($full_data, 0, 4);
        $expected_data_page2 = array_slice($full_data, 4, 4);

        // modify page size
        $this->query->set_page_size(4);
        // $this->query->page_size = 4;

        // use mock datasource
        $this->query->module = new MockModule();
        $this->query->module->setReturnValue('search', Array(
                'count'=>8,
                'data'=>$full_data,
                'offset'=>0,
                'total'=>8,
                'accuracy'=>'exact'));
        // DS search should only be called once
        $this->query->module->expectCallCount('search', 1);

        // perform search
        $results = $this->query->search($criteria);
        $this->assertEqual($expected_data, $results);
        $this->assertEqual($this->query->_cache->results['data'], $full_data);
        $this->assertEqual($this->query->_cache->criteria['q']->get_value(), $criteria['q']);

        // change to page 2
        $results = $this->query->search(Array('q'=>'many', 'page'=>'2'));
        $this->assertEqual($expected_data_page2, $results);
        $this->assertEqual($this->query->_cache->results['data'], $full_data);
        $this->assertEqual($this->query->_cache->criteria['q']->get_value(), $criteria['q']);
        }

    function test_page_outside_cache()
        {
        // generate full data
        $full_data = Array();
        for ($i = 1; $i <= $this->query->_cache->size + 100; $i++)
            $full_data[] = 'many'.$i;

        // calculate page outside cache
        $page = ($this->query->_cache->size / $this->query->get_page_size()) + 1;
        $offset = ($page - 1) * $this->query->get_page_size();

        // get expected data
        $expected_data = array_slice($full_data, 0, 10);
        $expected_data2 = array_slice($full_data, $offset, 10);

        // use mock datasource
        $this->query->module = new MockModule();
        $this->query->module->setReturnValueAt(0, 'search', Array(
                'count'=>10,
                'data'=>array_slice($full_data, 0, $this->query->_cache->size),
                'offset'=>0,
                'total'=>$this->query->_cache->size + 100,
                'accuracy'=>'exact'));
        $this->query->module->setReturnValueAt(1, 'search', Array(
                'count'=>10,
                'data'=>array_slice($full_data, $offset, $this->query->_cache->size),
                'offset'=>$offset,
                'total'=>$this->query->_cache->size + 100,
                'accuracy'=>'exact'));
        // DS search should only be called two times
        $this->query->module->expectCallCount('search', 2);

        global $CONF;


        // perform first search
        $results = $this->query->search(Array('q'=>'many'));
        $this->assertEqual($expected_data, $results);
        // change to page within cache
        $results = $this->query->search(Array('q'=>'many', 'page'=>'2'));

        // change to page outside cache
        // check for the expected results
        $results = $this->query->search(Array('q'=>'many', 'page'=>$page));
        $this->assertEqual($expected_data2, $results);
        // change to page within new cache
        $results = $this->query->search(Array('q'=>'many', 'page'=>$page+1));
        }

    function test_cache_disabled()
        {
        // disable caching
        $this->query->_cache->size = 0;
        $criteria = Array('q'=>'many');
        $results = $this->query->search($criteria);
        // ensure no results cached
        $this->assertTrue(is_null($this->query->_cache->results));
        $this->assertTrue(is_null($this->query->_cache->criteria));
        }

    function test_results_prev_next()
        {
        // set a low cache size for test
        $this->query->_cache->size = 20;
        $criteria = Array('q'=>'many');
        $this->query->search($criteria);
        $record = $this->query->get_record('/dummy/test/many000');
        $this->assertEqual($record['record_next_url'], '/dummy/test/many001');
        $this->assertTrue(!isset($record['record_prev_url']));
        $this->assertEqual($record['results_message'], 'Result 1 of 25');
        $this->assertEqual($this->query->page, 1);

        $record = $this->query->get_record($record['record_next_url']);
        $this->assertEqual($record['record_prev_url'], '/dummy/test/many000');
        $this->assertEqual($record['record_next_url'], '/dummy/test/many002');
        $this->assertEqual($record['results_message'], 'Result 2 of 25');
        $this->assertEqual($this->query->page, 1);

        $record = $this->query->get_record('/dummy/test/many010');
        $this->assertEqual($record['record_prev_url'], '/dummy/test/many009');
        $this->assertEqual($record['record_next_url'], '/dummy/test/many011');
        $this->assertEqual($record['results_message'], 'Result 11 of 25');
        $this->assertEqual($this->query->page, 2);

        // get the boundary record (i.e. last in cache)
        // the 'next result' link should be filled
        $record = $this->query->get_record('/dummy/test/many019');
        $this->assertEqual($record['record_prev_url'], '/dummy/test/many018');
        $this->assertEqual($record['record_next_url'], '/dummy/test/many020');
        $this->assertEqual($record['results_message'], 'Result 20 of 25');
        $record = $this->query->get_record('/dummy/test/many020');
        $this->assertEqual($record['record_prev_url'], '/dummy/test/many019');
        $this->assertEqual($record['record_next_url'], '/dummy/test/many021');
        $this->assertEqual($record['results_message'], 'Result 21 of 25');
        }

    function test_results_message_exceeds()
        {
        $this->query->module = new MockModule();
        // Single page (unlikely in practise!)
        $this->query->module->setReturnValue('search', Array(
                'count'=>3,
                'data'=>Array(Array('url'=>'bar'), 2, 3),
                'offset'=>0,
                'total'=>3,
                'accuracy'=>'exceeds',
                ));
        $this->query->search(Array('q'=>'foo'));
        $this->assertEqual($this->query->info['results_message'], 'Over 3 results', 'simple: %s');

        // Fetching a record has the correct message
        $this->query->module->setReturnValue('retrieve', Array('title'=>'foo', 'url'=>'bar'));
        $record = $this->query->get_record('bar');
        $this->assertEqual($record['results_message'], 'Result 1 of over 3', 'result: %s');
        }
        
    function test_results_message_exceeds_multiple_pages()
        {
        $this->query->module = new MockModule();
        // Multiple pages
        $this->query->module->setReturnValue('search', Array(
                'count'=>3,
                'data'=>Array(1, 2, 3),
                'offset'=>0,
                'total'=>3,
                'accuracy'=>'exceeds',
                ));
        $this->query->set_page_size( 2 );
        $this->query->search(Array('q'=>'foo'));
        $this->assertEqual($this->query->info['results_message'], '1-2 of over 3 results', 'paged: %s');
        }

    // The 'total_found' field if present will be used in user-facing reports
    // but not for page calculation. 
    function test_results_message_with_total_found()
        {
        $this->query->module = new MockModule();
        // Single page
        $this->query->module->setReturnValue('search', Array(
                'count'=>3,
                'data'=>Array(Array('url'=>'bar'), 2, 3),
                'offset'=>0,
                'total'=>3,
                'total_found'=>100,
                'accuracy'=>'exact',
                ));
        $this->query->search(Array('q'=>'foo'));
        $this->assertEqual($this->query->info['results_message'], '100 results', 'simple: %s');
        // print_var($this->query->info);//###

        // Fetching a record has the correct message
        $this->query->module->setReturnValue('retrieve', Array('title'=>'foo', 'url'=>'bar'));
        $record = $this->query->get_record('bar');
        $this->assertEqual($record['results_message'], 'Result 1 of 100', 'result: %s');
        }
        
    function test_results_message_with_total_found_exceeds()
        {
        $this->query->module = new MockModule();
        // Multiple pages
        $this->query->module->setReturnValue('search', Array(
                'count'=>3,
                'data'=>Array(1, 2, 3),
                'offset'=>0,
                'total'=>3,
                'total_found'=>100,
                'accuracy'=>'exceeds', // Accuracy applies to total_found ("over 100 results")
                ));
        $this->query->set_page_size( 2 );
        $this->query->search(Array('q'=>'foo'));
        $this->assertEqual($this->query->info['results_message'], '1-2 of over 100 results', 'paged: %s');
        }
	}

/// The QueryFilter can be added to the query->filters array. Filters are called in sequence
/// before a search, after a search, or get_record operation
class DummyQueryFilter
    extends QueryFilter
    {
    function __construct($token)
        {
        $this->token = $token;
        }
    function before_search($query, $criteria)
        {
        $query->filter_info['before_search_filter_log'] = @$query->filter_info['before_search_filter_log'] . 'b' . $this->token;
        }
    function after_search(&$results, $query, $criteria)
        {
        $query->filter_info['filter_log'] = @$query->filter_info['filter_log'] . 's' . $this->token;
        }
    function after_get_record(&$record, $query, $url, $params)
        {
        $record['filter_log'] = @$record['filter_log'] . 'r' . $this->token; 
        }
    }

/// Test query's filter chain
class QueryFilterChainTestCase
    extends UnitTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->factory = new QueryFactory($MODULE);
        $this->query = $this->factory->new_query();
        }

    function test_filters()
        {
        $this->query->filters = Array(new DummyQueryFilter('A'), new DummyQueryFilter('B'), );
        $results = $this->query->search(Array('q'=>'single'));
        $this->assertEqual($this->query->filter_info['filter_log'], 'sAsB');
        // 0 results
        $this->query->search(Array('q'=>'notfound'));
        $this->assertEqual($this->query->filter_info['filter_log'], 'sAsB');
        $this->assertEqual($this->query->filter_info['before_search_filter_log'], 'bAbB');
        // record
        $record = $this->query->get_record('/dummy/test/single');
        $this->assertEqual($record['filter_log'], 'rArB');
        }

    function test_filter_info()
        {
        // Filter info empty by default
        $this->assertEqual(Array(), $this->query->filter_info);
        // A search will clear it
        $this->query->filter_info = Array('foo', 'bar');
        $this->query->search(Array('q'=>'single'));
        $this->assertEqual(Array(), $this->query->filter_info);
        }
    }
