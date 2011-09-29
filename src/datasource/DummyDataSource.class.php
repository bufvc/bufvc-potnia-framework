<?php
// $Id$
// Demonstration DataSource class with test data
// James Fryer, 11 Aug 08, 23 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class DummyDataSource
    extends DataSource
    {
    function DummyDataSource($pear_db, $storage='memory')
        {
        global $CONF, $MODULE;
        $config = Array(
            // Test table, used for basic tests on DS CRUD+search
            'test' => Array(
                // Metadata about the table
                'title'=>'Test',
                'description'=>'Test table',
                'mutable'=>TRUE,

                // The storage type, either 'memory' or 'mysql', followed by configuration for the storage layer
                'storage'=>$storage,
                // add pear db object for use with mysql storage
                'pear_db'=>$pear_db,
                'mysql_table'=>'Test_Title',
                
                // Dummy query criteria
                'query_criteria' => Array(
                    Array(
                        'name' => 'q',
                        'qs_key'=> Array('q','adv'),
                        'render_label' => "Search $MODULE->title for",
                        'label' => 'Search for',
                        'index' => 'default',
                        'render_default' => $CONF['search_prompt'],
                        'list' => 'list_search',
                        'advanced_value_count' => 3,
                        'is_primary' => TRUE, // means that it can't be removed and shows up if default
                        'help' => 'enter your search term here',
                        ), // query
                    Array(
                        'name' => 'text',
                        'label' => 'Test',
                        'index'=>'default',
                        'mode'=>QC_MODE_ADVANCED, // this value will only show in advanced mode
                        ),
                    Array(
                        'name' => 'category',
                        'label' => 'Genre',
                        'type' => QC_TYPE_LIST,
                        'render_default' => 'Any Genre',
                        'help' => 'choose your genre',
                        ),
                    Array(
                        'name' => 'date',
                        'label' => 'Year',
                        'type' => QC_TYPE_DATE_RANGE,
                        'range' => Array( "1900", "1930" ),
                        'add_lists' => TRUE,
                        'help' => 'select the date range',
                        ), // date
                    Array(
                        'name' => 'sort',
                        'label' => 'Sort by',
                        'type' => QC_TYPE_SORT,
                        'list' => Array(''=>'Date (oldest first)', 'date_desc'=>'Date (newest first)', 'title'=>'Title'),
                        'is_renderable' => FALSE,
                        ), // sort by
                    Array(
                        'name' => 'page_size',
                        'label' => 'Display',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        'default' => 10
                        ),
                    ),
                'query_lists'=> Array(
                    'list_search' => Array(
                        '' => 'All fields',
                        'title' => 'Title',
                        'description' => 'Description',
                        'person' => 'Contributors',
                        'keyword' => 'Keywords',
                        ),
                    ),
                // A list of fields in this table
                // TODO: At present, the retrieve function uses the field names as mysql column names
                //       It would be nice to be able to define the mysql name as an option
                'fields'=>Array(
                        // The URL field is handled specially. It is always the unique identifier for the resource.
                        // By default 'id' is used
                        'url' => Array(
                            'type'=>'url',
                            'select' => 'token',
                            ),

                        // Most tables will have a title field
                        'title' => Array(
                            // Require, type, etc. as PEAR DC_Table for mysql storage
                            'require'=>1,
                            'type'=>'varchar',
                            'size'=>'255',

                            // Dublin Core export field
                            'dc_element'=>'title',
                            // Text export label
                            'text_label'=>'Title',
                            // BIBTeX export field
                            'bibtex_element'=>'title'
                            ),
                        'description' => Array(
                            'require'=>0, // Default
                            'type'=>'text', // translates to 'clob' in DB_Table
                            'dc_element'=>'description',
                            'text_label'=>'Description',
                            'bibtex_element'=>'abstract'
                            ),
                        // hidden flag
                        // Hidden items can't be viewed except by editors
                        'hidden' => Array(
                            'type'=>'integer',
                            'default'=>0,
                            ),
                        // Restricted flag
                        // Anonymous users can't see these items
                        'restricted' => Array(
                            'type'=>'integer',
                            'default'=>0,
                            ),
                        'media' => Array(
                            'type'=>'one_to_many',
                            'foreign_key'=>'title_id',
                            'select' => 'title,location,content_type,size',
                            ),

                        'person' => Array(
                            'type'=>'many_to_many',
                            'link'=>'Test_Participation',
                            //### Need title field for sort -- should fix this
                            'select' => 'name, name as title',
                            // Join condition for link table
                            //### FIXME: join should work for other field types
                            'join'=>'JOIN Test_Participation ON Test_Participation.person_id=Test_Person.id',
                            // First key is "this" table, second key is "other" table
                            'keys'=>Array('title_id', 'person_id'),
                            ),

                        'keyword' => Array(
                            'type'=>'many_to_many',
                            'link'=>'Test_TitleKeyword',
                            //### Need title field for sort -- should fix this
                            'select' => 'title',
                            'join'=>'JOIN Test_TitleKeyword ON Test_TitleKeyword.keyword_id=Test_Keyword.id',
                            'keys'=>Array('title_id', 'keyword_id'),
                            // Will return a single column of results
                            'get'=>'col',
                            ),
                        ),

                // Defines the way search will be handled for this table.
                'search'=>Array(
                    // A search must return title, and summary fields
                    //### TODO: rename 'select'
                    //### TODO: change to string per DB_Table query array
                    'fields' => Array('t.title','t.description AS summary'),

                    // The indexes define what criteria are acceptable in a query
                    'index' => Array(
                        'default' => Array('type'=>'fulltext', 'fields'=>'t.title,t.description'), //###,t.misc
                        'title' => Array('type'=>'fulltext', 'fields'=>'t.title'),
                        //### 'keyword' => Array('type'=>'fulltext', 'fields'=>'kw.word',
                        //### 'join'=>'JOIN KeywordTest kt ON kt.test_id=Test.id JOIN Keyword kw ON kw.id=kt.keyword_id'),
                        'sort.title' => Array('type'=>'asc', 'fields'=>'t.title'),
                        ),
                    ),

                //### FIXME: export stuff belongs here
                ),

            // Table of media files
            'media' => Array(
                // Optional 'slug' entry defines which field is used as slug
                // otherwise 'slug' value is used on creation
                'slug'=>'title',

                'title'=>'Media',
                'description'=>'Media files',
                'mutable'=>TRUE,
                'storage'=>$storage,
                'mysql_table'=>'Test_Media',
                'fields'=>Array(
                    'title' => Array(
                            'require'=>1,
                            'type'=>'varchar',
                            'size'=>'255',
                            ),
                    'location' => Array(
                            'require'=>1,
                            'type'=>'varchar',
                            'size'=>'255',
                            ),
                    'content_type' => Array(
                            'type'=>'varchar',
                            'size'=>'100',
                            ),
                    'size' => Array(
                            'type'=>'integer',
                            ),
                    //### In order to implement 1:M, a FK must be defined on the M side
                    //### Perhaps this could be done automatically?
                    'title_id' => Array(
                            'type'=>'integer',
                            ),
                    //### 'description' => Array('require'=>0),
                    ),
                ),

            // Table of media files
            'person' => Array(
                'slug'=>'name',
                'title'=>'Person',
                'description'=>'Real People',
                'mutable'=>TRUE,
                'storage'=>$storage,
                'mysql_table'=>'Test_Person',
                'fields'=>Array(
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                ),

            // Keywords
            'keyword' => Array(
                'slug'=>'title',
                'title'=>'Keywords',
                'description'=>'Search enhancers. Non-controlled vocabulary.',
                'mutable'=>TRUE,
                'storage'=>$storage,
                'mysql_table'=>'Test_Keyword',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        // This select statement will become "/table/%s AS url"
                        'select'=> 'id',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),

                // This is the default behaviour if no search is configured
                // 'search'=>Array(
                //      'fields' => Array("t.title,'' AS summary"),
                //    ),
                //  ),
                ),

            // Another test table, used to test e.g. template customisation
            'test2' => Array(
                'title'=>'Test 2',
                'description'=>'Another test table',
                'mutable'=>TRUE,
                'storage'=>'memory',
                'fields'=>Array(
                    'title' => Array('require'=>1),
                    'description' => Array('require'=>0),
                    ),
                // This table also has a query definition
                'query_criteria' => Array(
                    Array(
                        'name' => 'q',
                        'qs_key'=> Array('q','adv'),
                        'label' => 'Search for',
                        'index' => 'default',
                        'render_default' => $CONF['search_prompt'],
                        'list' => 'list_search',
                        'advanced_value_count' => 3,
                        'is_primary' => TRUE, // means that it can't be removed and shows up if default
                        'help' => 'enter your search term here',
                        ), // query
                    ), 
                ),
            
            // Another test table, used to test create failure
            'test3' => Array(
                'title'=>'Test 3',
                'description'=>'Another test table',
                'mutable'=>FALSE,
                'storage'=>'memory',
                'fields'=>Array(
                    'title' => Array('require'=>1),
                    'description' => Array('require'=>0),
                    ),
                ),

            // Listings test table
            'listings' => Array(
                'title'=>'',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Test_Broadcast',
                'query_criteria' => Array( // Used in listings test - a copy of Trilt DS
                    Array(
                        'name' => 'date',
                        'type' => QC_TYPE_DATE_RANGE,
                        'range' => Array( '2001-09-07', date('Y-m-d')+1 ),
                        'default' => Array( date('Y-m-d'), date('Y-m-d')+1 ),
                        ),
                    Array(
                        // a control for selecting the hour, its value gets folded back into date
                        'name' => 'time',
                        'type' => QC_TYPE_LIST,
                        'default' => date('G'),
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        ),
                    Array(
                        'name' => 'channel',
                        'label' => 'Channel',
                        'type' => QC_TYPE_FLAG,
                        'list' => Array(
                            '1' => 'Test',
                            '54' => 'BBC1 London',
                            '68' => 'BBC2 London',
                            '175' => 'ITV1 London',
                            '106' => 'Channel 4',
                            '138' => 'Five',
                            ),
                        'default' => Array( 54=>1, 68=>1, 175=>1, 106=>1, 138=>1 ),
                        ),
                    Array(
                        'name' => 'style',
                        'type' => QC_TYPE_OPTION,
                        'list' => Array( 'list', 'grid' ),
                        'default' => 'list',
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        ),
                    Array(
                        'name' => 'view_grid',
                        'type' => QC_TYPE_FLAG,
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        ),
                    Array(
                        'name' => 'view_list',
                        'type' => QC_TYPE_FLAG,
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        ),
                    Array(
                        'name' => 'page_size',
                        'type' => QC_TYPE_SORT,
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        'default' => 1000
                        ),
                    ),
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=>'id',
                        ),
                    'date' => Array(
                        'require'=>1,
                        'type'=>'timestamp',
                        ),
                    'end_date' => Array(
                        'require'=>1,
                        'type'=>'timestamp',
                        ),
                    'bds_id' => Array(
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'prog_id' => Array(
                        'require'=>1,
                        'type'=>'integer',
                        ),
                    'channel' => Array(
                        'type'=>'many_to_many',
                        'link'=>'Test_BroadcastChannel',
                        'select' => 'name,id AS `key`',
                        'keys'=>Array('bcast_id', 'channel_id'),
                        'lookup'=>'name',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.date,t.end_date,t.id,c.name AS channel,c.id AS channel_id,".
                                      "p.title,p.bds_id AS prog_bds_id,p.is_highlighted,".
                                      "TIME_TO_SEC(TIMEDIFF(t.end_date, t.date))+1 AS duration"),
                    'index' => Array(
                        'join' => Array('JOIN Test_Programme p ON p.id=t.prog_id',
                                'JOIN Test_BroadcastChannel bc ON bc.bcast_id=t.id',
                                'JOIN Test_Channel c ON c.id=bc.channel_id'),
                        'default' => Array('type'=>'datetime', 'fields'=>'t.date'),
                        'channel' => Array('type'=>'number', 'fields'=>'bc.channel_id',),
                        'date' => Array('type'=>'datetime', 'fields'=>'t.date'),
                        'sort.date_asc' => Array('type'=>'asc', 'fields'=>'t.date'),
                        'sort.date_desc' => Array('type'=>'desc', 'fields'=>'t.date'),
                        'sort.default' => Array('type'=>'asc', 'fields'=>'t.date'),
                        ),
                    ),
                ),

            // Channel (used by listings test)
            'channel' => Array(
                'title'=>'Channels',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Test_Channel',
                'fields'=>Array(
                    'url' => Array(
                        'type'=>'url',
                        'select'=>'id',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.name AS title,id AS `key`"),
                    ),
                ),
            );
        DataSource::DataSource($config);
        $this->add_mock_data();
        }

    /// Function to help with unit tests
    /// The mock data has the following characteristics:
    ///   - Searches in 'test' table for 'notfound' return no results
    ///   - Searches in 'test' for 'single' return exactly one result
    ///   - Searches in 'test' for 'many' return 25 results
    ///   - Item 'test2/has_template' exists
    ///### FIXME: find some way to remove from base class
    function add_mock_data()
        {
        $this->create('test', Array('slug'=> 'single', 'title'=>'single', 'description'=>'Test item', 'files'=>Array('file1.mp3')));
        for ($i = 0; $i < 25; $i++)
            {
            // Note that 'many' is a stopword in fulltext search so we use the made-up word 'manymany' instead.
            $n = sprintf('%03d', $i);
            // Add the data directly as 'create' is a bit slow.
            $url = '/test/many' . $n;
            $this->_data[$url] = Array('title'=>'manymany ' . $n, 'description'=>'Test item ' . $n, 'files'=>NULL, '_table'=>'test', 'url'=>$url);
            }
        // add hidden record for hidden test
        $this->create('test', Array('slug'=>'hidden', 'title'=>'hidden', 'description'=>'Hidden test item', 'hidden'=>1));
        $this->create('test', Array('slug'=>'restricted', 'title'=>'restricted', 'description'=>'Restricted test item', 'restricted'=>1));
        $this->create('test2', Array('slug'=> 'has_template', 'title'=>'Has template', 'description'=>'Demonstrates template specialisation'));
        }

    function retrieve($url)
        {
        //### FIXME: temp hack to get file list into 'single' record without changing database
        $result = parent::retrieve($url);
        if ($url == '/test/single' && !is_null($result))
            $result['media'] = Array(Array('title'=>'Test MP3', 'location'=>'file1.mp3', 'content_type'=>'audio/mpeg'));
        return $result;
        }
    }

/// Stores data in MySQL table
class TestMysqlDataSource
    extends DummyDataSource
    {
    function TestMysqlDataSource($module)
        {
        DummyDataSource::DummyDataSource($module->get_pear_db(), 'mysql');
        }

    function add_mock_data()
        {
        // Data now defined in SQL
        $this->create('test2', Array('slug'=> 'has_template', 'title'=>'Has template', 'description'=>'Demonstrates template specialisation'));
        }
    }

?>
