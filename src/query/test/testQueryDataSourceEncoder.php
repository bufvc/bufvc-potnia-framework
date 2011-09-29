<?php
// $Id$
// Tests for QueryDataSourceEncoder
// Alexander Veenendaal, 27 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'query/QueryDataSourceEncoder.class.php');

// Mocks
Mock::generate('DataSource');
Mock::generate('Module');
Mock::generate('QueryCache');
Mock::generate('QueryEncoder');

class QueryDataSourceEncoderTestCase
    extends UnitTestCase
    {
    var $query_config = Array(
        'table'=>'test',
        'criteria_defs' => Array(
            Array( 'name' => 'q', 'label' => 'Search', 'render_default' => 'All records', 'list' => 'tablelist_advanced_table' ), // query
            Array( 'name' => 'text', 'label' => 'Search' ),
            Array( 'name' => 'sort', 'type' => QC_TYPE_SORT, 'list' => 'dslist_sort', 'is_renderable' => FALSE ),
            ),
        'query_lists' => Array(
            'tablelist_sort' => Array(''=>'Date (oldest first)', 'date'=>'Date (newest first)', 'title'=>'Title' ),
            'tablelist_advanced_table' => Array(''=>'All fields', 'title'=>'Title Only' ),
            ),
        );
        
    function setup()
        {
        $this->query = $this->new_query_from_defs();
        $this->encoder = new QueryDataSourceEncoder($this->query);
        }

    function test_empty()
        {
        // No criteria returns empty string
        $this->query->set_criteria_values();
        $this->assertEqual('', $this->encoder->encode());
        }
    
    function test_simple_criteria()
        {
        global $CONF;
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'foo', 'y'=>'bar', 'z'=>'test'));
        $this->assertEqual($this->encoder->encode(), '{x=foo}{y=bar}');
        }
    
    function test_index()
        {
        global $CONF;
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y', 'index'=>'YYY' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'foo', 'y'=>'bar', 'z'=>'test'));
        $this->assertEqual($this->encoder->encode(), '{x=foo}{YYY=bar}');
        }
    
    function test_relation()
        {
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y', 'relation'=>'>' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'foo', 'y'=>'bar', 'z'=>'test'));
        $expect = '{x=foo}{y>bar}';
        $this->assertEqual($this->encoder->encode(), $expect);
        }

    function test_solo_criteria_have_no_braces()
        {
        // This is basically to keep the memory ds working
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y', 'index'=>'default' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'foo', 'z'=>'test'));
        $expect = 'x=foo';
        $this->assertEqual($this->encoder->encode(), $expect);
        }
    
    function test_solo_criteria_special_cases()
        {
        // Solo criteria are wrapped if they start with '(' or '"'
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y', 'index'=>'default' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'"foo"'));
        $expect = '{x="foo"}';
        $this->assertEqual($this->encoder->encode(), $expect);
        $this->query->set_criteria_values(Array('x'=>' (foo)'));
        $expect = '{x= (foo)}';
        $this->assertEqual($this->encoder->encode(), $expect);
        $this->query->set_criteria_values(Array('y'=>'"foo'));
        $expect = '{default="foo}';
        $this->assertEqual($this->encoder->encode(), $expect);
        $this->query->set_criteria_values(Array('y'=>'(foo'));
        $expect = '{default=(foo}';
        $this->assertEqual($this->encoder->encode(), $expect);
        }
    
    function test_solo_criteria_default_index_ignored()
        {
        global $CONF;
        // If index name is 'default' then a single criteria doesn't have index name
        // This is also to keep the memory ds working
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y', 'index'=>'default' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        $this->query->set_criteria_values(Array('y'=>'bar', 'z'=>'test'));
        $this->assertEqual($this->encoder->encode(), 'bar');
        }
    
    function test_values_can_be_arrays()
        {
        // default is to join values with commas
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values(Array('x'=>'foo', 'y'=>Array('123', '456', ''), 'z'=>'test'));
        $expect = '{x=foo}{y=123,456}';
        $this->assertEqual($this->encoder->encode(), $expect);
        // only 1 clause
        $this->query->set_criteria_values(Array('y'=>Array('123', '456', ''), 'z'=>'test'));
        $expect = '{y=123,456}';
        $this->assertEqual($this->encoder->encode(), $expect);
        // empty array
        $this->query->set_criteria_values(Array('x'=>'foo', 'y'=>Array('', '', ''), 'z'=>'test'));
        $expect = 'x=foo';
        $this->assertEqual($this->encoder->encode(), $expect);
        }
        
    
    function test_demo_criteria()
        {
        // 'q' criteria maps to 'default' index
        // Moved from query test
        $this->query->set_criteria_values(Array('q'=>'bar'));
        $this->assertEqual($this->encoder->encode(), 'bar');
        }
    
    function test_convert_criteria_magic()
        {
        global $CONF;
        // Magic criteria have index names and Boolean operators
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'y' ),
            Array( 'name' => 'x' ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $tests = Array(
            // Missing second index defaults to default
            '{title=this}{default=that}' => Array(
                'x_q1' => 'this', 'x_index1'=>'title',
                'x_q2' => 'that', 'x_oper2'=>'and',
                ),
            // OR
            '{title=food}OR{default=bar}' => Array(
                 'x_q1' => 'food', 'x_index1'=>'title',
                 'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'or',
                 ),
             // NOT
             '{title=foox}NOT{default=bar}' => Array(
                 'x_q1' => 'foox', 'x_index1'=>'title',
                 'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'Not',
                 ),
             // AND is removed
             '{title=fool}{default=bar}' => Array(
                 'x_q1' => 'fool', 'x_index1'=>'title',
                 'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'AND',
                 ),
             // First oper is removed
             '{title=foom}NOT{default=bar}' => Array(
                 'x_q1' => 'foom', 'x_index1'=>'title', 'x_oper1'=>'OR',
                 'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'NOT',
                 ),
             // Unknown opers are removed
             '{title=foot}{default=bar}' => Array(
                 'x_q1' => 'foot', 'x_index1'=>'title',
                 'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'FNORD',
                 ),
            // Magic opers always come first regardless of definition
            '{title=fooq}{y=fooz}' => Array(
                'y' => 'fooz',
                'x_q1' => 'fooq', 'x_index1'=>'title',
                ),
            '{title=foox}{y=foox}' => Array(
                'x_q1' => 'foox', 'x_index1'=>'title',
                'y' => 'foox',
                ),
            // Magic opers are parenthesised when combined with others
            '({title=foop}{default=bar}){y=foop}' => Array(
                'x_q1' => 'foop', 'x_index1'=>'title',
                'x_q2' => 'bar', 'x_index2'=>'default',
                'y' => 'foop',
                ),
            '({title=foo}OR{default=bar}){y=foo}' => Array(
                'x_q1' => 'foo', 'x_index1'=>'title',
                'y' => 'foo',
                'x_q2' => 'bar', 'x_index2'=>'default', 'x_oper2'=>'OR',
                ),
            );
        foreach ($tests as $expect=>$criteria)
            {
            $this->query->set_criteria_values($criteria);
            $this->assertEqual($this->encoder->encode(), $expect);
            }
        }
        
    function test_date_range()
        {
        global $CONF;
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);

        $this->query->set_criteria_values( Array('x'=>'foo') );
        $this->assertEqual($this->encoder->encode(), 'x=foo');

        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1975') );
        // $this->assertEqual($this->encoder->encode(), '{x=foo}{date_start>=1975}');
        $this->assertEqual($this->encoder->encode(), '{x=foo}{date=1975-01-01,2010-12-31}');
        
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1975', 'date_end'=>'2009') );
        // $this->assertEqual($this->encoder->encode(), '{x=foo}{date_start>=1975}{date_end<=2009}');
        $this->assertEqual($this->encoder->encode(), '{x=foo}{date=1975-01-01,2009-12-31}');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_end'=>'2000') );
        // $this->assertEqual($this->encoder->encode(), '{x=foo}{date_end<=2000}');
        $this->assertEqual($this->encoder->encode(), '{x=foo}{date=1974-01-01,2000-12-31}');
        
        // default values dont show
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1974') );
        $this->assertEqual($this->encoder->encode(), 'x=foo');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1974', 'date_end'=>'2010') );
        $this->assertEqual($this->encoder->encode(), 'x=foo');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1974-02') );
        $this->assertEqual($this->encoder->encode(), '{x=foo}{date=1974-02-01,2010-12-31}');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'date_start'=>'1974-02-15') );
        $this->assertEqual($this->encoder->encode(), '{x=foo}{date=1974-02-15,2010-12-31}');
        
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values( Array('date_start'=>'1975') );
        $this->assertEqual($this->encoder->encode(), '{date=1975-01-01,2010-12-31}');
        
        // encode hours and minutes
        $this->query->set_criteria_values( Array('date_start'=>'1974-09-05-16-34') );
        $this->assertEqual($this->encoder->encode(), '{date=1974-09-05 16:34,2010-12-31}');
        }
        
        
    function test_flags()
        {
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'flag', 'type'=>QC_TYPE_FLAG ),
            Array( 'name' => 'flag_neq', 'type'=>QC_TYPE_FLAG, 'relation'=>QC_RELATION_NEQ) ));
        $this->encoder = new QueryDataSourceEncoder($this->query);
        
        $this->query->set_criteria_values( Array('x'=>'foo') );
        $this->assertEqual($this->encoder->encode(), 'x=foo');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'flag'=>'1') );
        $this->assertEqual($this->encoder->encode(), '{x=foo}{flag=1}');
        
        $this->query->set_criteria_values( Array('x'=>'foo', 'flag[0]'=>'0','flag[1]'=>'1','flag[3]'=>'1') );
        $this->assertEqual($this->encoder->encode(), '{x=foo}{flag=1,3}');
        
        // relation is passed through for flags
        $this->query->set_criteria_values( Array('flag_neq'=>'1') );
        $this->assertEqual($this->encoder->encode(), 'flag_neq<>1');
        }

    function assertError($error_code)
        {
        $this->assertTrue($this->query->error_code == $error_code, 'error code not set');
        $this->assertTrue($this->query->error_message != '', 'error message not set');
        }
    
    function assertNoError()
        {
        $this->assertTrue($this->query->error_code == 0, 'error code set');
        $this->assertTrue($this->query->error_message == '', 'error message set');
        }
        
    function new_query_from_defs( $criteria_defs=NULL )
        {
        global $MODULE;
        $config = $this->query_config;
        if ($criteria_defs)
            $config['criteria_defs'] = $criteria_defs;
        unset($MODULE->query_config);//### TEMP
        return QueryFactory::create($MODULE, $config);
        }
        
    function test_non_encodeable_criteria()
        {
        $this->query = $this->new_query_from_defs( Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'page_size', 'type'=>QC_TYPE_OPTION, 'is_encodable' => FALSE),
        ));
        $this->encoder = new QueryDataSourceEncoder($this->query);

        $this->query->set_criteria_values( Array('x'=>'foo', 'page_size'=>1000) );
        $this->assertEqual($this->encoder->encode(), 'x=foo');
        }
	}
