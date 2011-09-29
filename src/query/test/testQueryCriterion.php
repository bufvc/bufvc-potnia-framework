<?php
// $Id$
// Tests for QueryCriterion class
// Alexander Veenendaal, 11 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'query/QueryCriterion.class.php');

class QueryCriterionTestCase
    extends UnitTestCase
    {
    function setup()
        {
        }
    
    /// Asserts that two arrays are the same. Unlike assertEqual, this will also work on 'deep' arrays
    function assertEqualArray( $a1, $a2 )
        {
        if( $this->assertEqual( $a1, $a2 ) )
            return $this->assertEqual( serialize($a1), serialize($a2) );
        else
            return TRUE;
        }

    function run_common_tests( $criterion, $type, $attributes )
        {
        $this->assertEqual( $type, $criterion->type );

        $this->assertEqual( $attributes['label'], $criterion->label );
        $this->assertEqual( $attributes['name'], $criterion->name );
        }

    function test_create_no_name()
        {
        $this->expectException( new QueryCriterionException('no name specified'));
        $criterion = QueryCriterionFactory::create( Array( 'label' => 'Testing' ) );
        }
        
    function test_create()
        {
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'tst', 'label' => 'Testing' ) );
        $this->assertNotNull( $criterion );
        } 
    
    function test_get_values()
        {
        global $CONF;
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertNull( $criterion->get_default() );
        $this->assertNull( $criterion->get_value() );
        $this->assertNull( $criterion->get_rendered_value() );
        
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search',
            'value' => 'apple pie'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertNull( $criterion->get_default() );
        $this->assertEqual( $attributes['value'], $criterion->get_value() );
        $this->assertEqual( $attributes['value'], $criterion->get_rendered_value() );
        
        
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search',
            'default' => 'something'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( $attributes['default'], $criterion->get_default() );
        $this->assertEqual( $attributes['default'], $criterion->get_value() );
        $this->assertEqual( $attributes['default'], $criterion->get_rendered_value() );
        
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search',
            'render_default' => 'splendid'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertNull( $criterion->get_default() );
        $this->assertNull( $criterion->get_value() );
        
        $this->assertEqual( $attributes['render_default'], $criterion->get_rendered_value() );
        
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search',
            'render_default' => 'splendid',
            'default' => 'pie'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( $attributes['default'], $criterion->get_default() );
        $this->assertEqual( $attributes['default'], $criterion->get_value() );
        $this->assertEqual( $attributes['render_default'], $criterion->get_rendered_value() );
        
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search',
            'render_default' => 'splendid',
            'default' => 'pie',
            'value' => 'summertime'
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( $attributes['default'], $criterion->get_default() );
        $this->assertEqual( $attributes['value'], $criterion->get_value() );
        $this->assertEqual( $attributes['value'], $criterion->get_rendered_value() );
        }
        
    function test_rendered_value()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text',
            'label' => 'Search',
            'render_default' => 'pie'
            ) );
        
        $this->assertNull( $criterion->get_value() );
        $this->assertEqual( 'pie', $criterion->get_rendered_value() );
        $criterion->set_value( 'cheese' );
        $this->assertEqual( 'cheese', $criterion->get_rendered_value() );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text',
            'label' => 'Search',
            'default' => 'pickle'
            ) );
        $this->assertEqual( 'pickle', $criterion->get_rendered_value() );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text',
            'label' => 'Search',
            'default' => 'jam',
            'render_default' => 'honey',
            ) );
        $this->assertEqual( 'honey', $criterion->get_rendered_value() );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text',
            'label' => 'Search',
            'default' => 'hummus',
            'render_default' => 'tzatziki',
            'value' => 'guacamole',
            ) );
        $this->assertEqual( 'guacamole', $criterion->get_rendered_value() );
        }
        
    function test_get_qs_key_values()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text',
            'label' => 'Search',
            'default' => 'jam',
            'render_default' => 'honey',
            ) );
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        
        $criterion->set_value( 'jam' );
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        }
    
    function test_text()
        {
        $attributes = Array(
            'name' => 'text',
            'label' => 'Search' 
            );
        $criterion = QueryCriterionFactory::create( $attributes );
        
        $this->run_common_tests( $criterion, QC_TYPE_TEXT, $attributes );
        
        $this->assertTrue( $criterion->is_set_to_default() );
        $this->assertEqual( $criterion->get_value(), $criterion->get_default() );
        
        
        $criterion->set_value( 'something' );
        $this->assertFalse( $criterion->is_set_to_default() );
        
        $this->assertNotEqual( $criterion->get_value(), $criterion->get_default() );
        $this->assertTrue( $criterion->status == QC_STATUS_OK );
        
        $this->assertEqual( $criterion->get_qs_key(), $attributes['name'] );
        }
        
    function test_parse_qs_key()
        {
        global $CONF;
        $this->assertEqual( QueryCriterion::parse_qs_key('swedish'), Array('swedish', 0, NULL) );
        $this->assertEqual( QueryCriterion::parse_qs_key('swedish_chef'), Array('swedish_chef', 0, NULL) );
        
        $this->assertEqual( QueryCriterion::parse_qs_key('adv_q1'), Array('adv', 0, 'q') );
        $this->assertEqual( QueryCriterion::parse_qs_key('adv_index2'), Array('adv', 1, 'index') );
        $this->assertEqual( QueryCriterion::parse_qs_key('adv_oper20'), Array('adv', 19, 'oper') );

        $this->assertEqual( QueryCriterion::parse_qs_key('david[990]["value"]'), Array('david',990, 'value') );
        $this->assertEqual( QueryCriterion::parse_qs_key("tennant[1]['value']"), Array('tennant', 1, 'value') );
        $this->assertEqual( QueryCriterion::parse_qs_key("tom[0]['index']"), Array('tom', 0, 'index') );
        $this->assertEqual( QueryCriterion::parse_qs_key("baker[10][toast]"), Array('baker', 10, 'toast') );
        $this->assertEqual( QueryCriterion::parse_qs_key("mocha[0]"), Array('mocha', 0, NULL) );
        $this->assertEqual( QueryCriterion::parse_qs_key("date_start[2]"), Array('date_start', 2,NULL) );
        }
        
    function test_set_get_array()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'villain' ));
        $value = Array('the_master', 'davros');
        $criterion->set_value( Array('the_master', 'davros') );
        $this->assertEqual( 1, count($criterion) );
        $this->assertEqual( $value, $criterion->get_value() );
        $this->assertEqual( $value, $criterion->get_value(0) );
        }
        
    function test_set_get_advanced()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'engine' ));
        $this->assertEqual( 0, count($criterion) );
        $criterion->set_value( 'percy' );
        $this->assertEqual( 'percy', $criterion->get_value() );
        $this->assertEqual( 'percy', $criterion->get_value(0) );
        $this->assertEqual( 'percy', $criterion->get_value('engine[0]') );
        
        $this->assertEqual( 1, count($criterion) );
        
        $criterion->set_value( 'thomas', 'engine[0]' );
        $criterion->set_value( 'henry', 'engine[1]' );
        
        $this->assertEqual( 'thomas', $criterion->get_value() );
        $this->assertEqual( 'thomas', $criterion->get_value(0) );
        $this->assertEqual( 'thomas', $criterion->get_value('engine[0]') );
        $this->assertEqual( 'henry', $criterion->get_value(1) );
        $this->assertEqual( 'henry', $criterion->get_value('engine[1]') );

        $criterion->set_value( 'cuthbert', 'engine' );
        $this->assertEqual( 'cuthbert', $criterion->get_value() );
        $this->assertEqual( 1, count($criterion) );
        
        $criterion->clear();
        
        $this->assertEqual( 0, count($criterion) );
        $this->assertNull( $criterion->get_value() );
        $this->assertNull( $criterion->get_value(1) );
        }
        
    function test_get_index_value()
        {
        // non-advanced should not return higher indexes
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'engine' ));
        $criterion->set_value( 'percy' );
        $this->assertEqual( 'percy', $criterion->get_value() );
        $this->assertEqual( 'percy', $criterion->get_value(0) );
        $this->assertEqual( '', $criterion->get_value(1) );
        $this->assertEqual( '', $criterion->get_value(2) );
        
        $criterion->set_value( 'henry', 'engine[1]' );
        $this->assertEqual( 'percy', $criterion->get_value(0) );
        $this->assertEqual( 'henry', $criterion->get_value(1) );
        $this->assertEqual( '', $criterion->get_value(2) );
        $this->assertEqual( '', $criterion->get_value(3) );
        }
        
    function test_set_advanced_raw()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'q' ));
        
        $this->assertEqual( 0, count($criterion) );
        
        $raw_value = Array(
            Array( 'v'=>'something', 'index'=>'title' ),
            Array( 'v'=>'good', 'index'=>'default', 'oper'=>'or' ),
            Array( 'v'=>'isgoingtohappen', 'index'=>'default', 'oper'=>'or' ),
            );
        $criterion->set_value( $raw_value );
        $this->assertEqual( 3, count($criterion) );
        
        $this->assertEqual( 'something', $criterion->get_value() );
        $this->assertEqual( 'title', $criterion->get_index() );
        $this->assertEqual( 'good', $criterion->get_value(1) );
        $this->assertEqual( 'default', $criterion->get_index(1) );
        $this->assertEqual( 'or', $criterion->get_operator(1) );
        $this->assertEqual( 'isgoingtohappen', $criterion->get_value(2) );
        $this->assertEqual( 'default', $criterion->get_index(2) );
        $this->assertEqual( 'or', $criterion->get_operator(2) );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'q' ));
        $criterion->set_value( $raw_value, 'q' );
        $this->assertEqual( 3, count($criterion) );
        $this->assertEqual( 'something', $criterion->get_value() );
        $this->assertEqual( 'good', $criterion->get_value(1) );
        $this->assertEqual( 'isgoingtohappen', $criterion->get_value(2) );
        }
        

    function test_does_qs_key_match()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text', 'qs_key' => 'adv', 'label' => 'Search'
            ));
        
        $this->assertFalse( $criterion->does_qs_key_match('text') );
        
        // matches qskey not name
        $this->assertFalse( $criterion->does_qs_key_match('text[0]') );
        $this->assertTrue( $criterion->does_qs_key_match('adv[0]') );
        
        $this->assertFalse( $criterion->does_qs_key_match('text[0][\'oper\']') );
        $this->assertTrue( $criterion->does_qs_key_match('adv[2000]["q"]') );
        }
    
    
    
    function test_text_expanded()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text', 'qs_key' => 'adv', 'label' => 'Search'
            ) );
        
        $this->assertFalse( $criterion->is_expanded() );
        $this->assertFalse( $criterion->is_advanced() );
        
        $criterion->set_value( 'paul', 'adv[0]' );
        $criterion->set_value( 'title', 'adv[0][index]' );
        $criterion->set_value( 'and', 'adv[0][oper]' );
        
        $this->assertEqual( $criterion->get_value(), 'paul' );
        $this->assertEqual( $criterion->get_rendered_value(), 'paul' );
        $this->assertEqual( $criterion->get_index(), 'title' );
        $this->assertEqual( $criterion->get_operator(), QC_OP_AND );
        
        // its an advanced crit because the index has been set (in this case)
        //$this->assertTrue( $criterion->does_contain_advanced_values() );
        $this->assertTrue( $criterion->is_expanded() );
        $this->assertTrue( $criterion->is_advanced() );
        
        $criterion->set_value( 'daniels', "adv[0]['q1']" );
        $criterion->set_value( 'default', 'adv[0]["index"]' );
        $criterion->set_value( 'or', 'adv[0]["oper"]' );
        
        $this->assertEqual( $criterion->get_value(), 'daniels' );
        $this->assertEqual( $criterion->get_index(), 'default' );
        $this->assertEqual( $criterion->get_operator(), QC_OP_OR );
        
        $criterion->set_value( 'paul', 'adv[0]' );
        $criterion->set_value( 'title', 'adv[0]["index"]' );
        $criterion->set_value( 'and', 'adv[0]["oper"]' );
        
        $criterion->set_value( 'daniels', 'adv_q2' );
        $criterion->set_value( 'default', 'adv[1][index]' );
        $criterion->set_value( 'or', 'adv_oper2' );
        
        // true because more than one value has been set
        // $this->assertTrue( $criterion->does_contain_advanced_values() );
        $this->assertTrue( $criterion->is_expanded() );
        $this->assertTrue( $criterion->is_advanced() );

        $criterion_value = $criterion->get_value();
        $criterion_index = $criterion->get_index();
        $criterion_operator = $criterion->get_operator();

        $this->assertEqual( $criterion->get_value(0), 'paul' );
        $this->assertEqual( $criterion->get_index(0), 'title' );
        $this->assertEqual( $criterion->get_operator(0), QC_OP_AND );
        
        $this->assertEqual( $criterion->get_value(1), 'daniels' );
        $this->assertEqual( $criterion->get_index(1), 'default' );
        $this->assertEqual( $criterion->get_operator(1), QC_OP_OR );
        
        $expected = Array(
            'adv_q1' => 'paul', 'adv_index1' => 'title', 'adv_oper1' => 'and',
            'adv_q2' => 'daniels', 'adv_index2' => 'default', 'adv_oper2' => 'or'
            );
        $this->assertEqual( $expected, $criterion->get_qs_key_values(FALSE,FALSE) );
        
        $expected = Array(
            'adv[0][v]' => 'paul', 'adv[0][index]' => 'title', 'adv[0][oper]' => 'and',
            'adv[1][v]' => 'daniels', 'adv[1][index]' => 'default', 'adv[1][oper]' => 'or'
            );
            
        $this->assertEqual( $expected, $criterion->get_qs_key_values(FALSE,TRUE) );
        
        $expected = Array(
            'adv[0][v]' => 'steve', 'adv[0][index]' => 'forename', 'adv[0][oper]' => 'or',
            'adv[1][v]' => 'jobs', 'adv[1][index]' => 'surname', 'adv[1][oper]' => 'not'
            );
            
        $criterion->set_value( 'steve', 'adv_q1' );
        $criterion->set_value( 'forename', 'adv_index1' );
        $criterion->set_value( 'or', 'adv_oper1' );
        
        $criterion->set_value( 'jobs', 'adv[1]' );
        $criterion->set_value( 'surname', 'adv[1]["index"]' );
        $criterion->set_value( 'not', 'adv[1]["oper"]' );
        
        $this->assertEqual( $expected, $criterion->get_qs_key_values(FALSE,TRUE) );
        }
        
        
    
    
    function test_text_expanded_clear()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text', 'qs_key' => 'adv', 'label' => 'Search'
            ) );
        
        $criterion->set_value( 'paul', 'adv_q1' );
        $criterion->set_value( 'title', 'adv_index1' );
        $criterion->set_value( 'and', 'adv_oper1' );
        
        // $this->assertTrue( $criterion->does_contain_advanced_values() );
        $this->assertTrue( $criterion->is_expanded() );
        $this->assertTrue( $criterion->is_advanced() );
        
        $expected = Array(
            'adv_q1' => 'paul', 'adv_index1' => 'title', 'adv_oper1' => 'and'
            );

        $this->assertEqual( $expected, $criterion->get_qs_key_values(FALSE,FALSE) );
        
        $expected = Array(
            'adv[0][v]' => 'paul', 'adv[0][index]' => 'title', 'adv[0][oper]' => 'and'
            );
        $this->assertEqual( $expected, $criterion->get_qs_key_values() );
        $this->assertEqual('title', $criterion->get_index() );
        
        $criterion->set_value();
        
        // $this->assertTrue( $criterion->is_magic );
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        $this->assertFalse( $criterion->is_expanded() );
        $this->assertFalse( $criterion->is_advanced() );
        $this->assertEqual( NULL, $criterion->get_index() );
        }
        
    function test_expanded_clear_with_values()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text', 'qs_key' => 'adv', 'label' => 'Search'
            ) );

        $CONF['dt'] = 1;
        $criterion->set_value( 'paul', 'adv_q1' );
        $criterion->set_value( 'title', 'adv_index1' );
        $criterion->set_value( 'and', 'adv_oper1' );

        // $this->assertTrue( $criterion->does_contain_advanced_values() );
        $this->assertTrue( $criterion->is_expanded() );
        $this->assertTrue( $criterion->is_advanced() );
        
        // setting the value again using the name will clear existing advanced values
        $criterion->set_value( 'mike', 'text' );
        $this->assertFalse( $criterion->is_expanded() );
        $this->assertFalse( $criterion->is_advanced() );
        }
        
    function test_list()
        {
        // first a version which won't work
        $attributes = Array(
            'name' => 'listworthy',
            'label' => 'Select',
            'type' => QC_TYPE_LIST,
            );
        
        // if no list is specified, then no list is returned
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( null, $criterion->get_list() );
        
        $attributes = Array(
            'name' => 'list',
            'label' => 'Select',
            'type' => QC_TYPE_LIST,
            'list' => 'test_list',
            );
        
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( 'test_list', $criterion->get_list() );
        $this->run_common_tests( $criterion, QC_TYPE_LIST, $attributes );
        
        //### TODO AV : because the list values are retrieved at runtime, how do you test setting and getting values?
        }

    function test_sort()
        {
        // first a version which won't work
        $attributes = Array(
            'name' => 'sort',
            'label' => 'Sort by',
            'type' => QC_TYPE_SORT,
            );

        // $this->expectException( new QueryCriterionException('no list specified'));
        $criterion = QueryCriterionFactory::create( $attributes );
        $this->assertEqual( null, $criterion->get_list() );

        $attributes = Array(
            'name' => 'sort',
            'label' => 'Sort by',
            'type' => QC_TYPE_SORT,
            'list' => 'test_sort',
            );

        $criterion = QueryCriterionFactory::create( $attributes );
        $this->run_common_tests( $criterion, QC_TYPE_SORT, $attributes );
        $this->assertEqual( 'test_sort', $criterion->get_list() );
        }
        
    function test_sort_qs_key_values()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create(Array(
                'name' => 'page_size',
                'label' => 'Display',
                'type' => QC_TYPE_SORT,
                'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                'is_renderable' => FALSE,
                'is_encodable' => FALSE,
                'default' => 10,
                'value' => 10,
                ));
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        $this->assertEqual( Array('page_size' => 10), $criterion->get_qs_key_values(TRUE) );
        $criterion->set_value('50');
        $this->assertEqual( Array('page_size' => 50), $criterion->get_qs_key_values() );
        $criterion->set_value();
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        $criterion->set_value(10);
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );
        }

    function test_qs_values()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'x', 'value' => 0
            ));
        $this->assertFalse( $criterion->is_set_to_default() );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'x'=>0 ) );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'q', 'value' => 'emmy', 'index' => 'default', 'render_default' => 'all records', 'advanced_value_count' => 3
            ));
        $criterion->ensure_values_are_arrays();
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'q'=>'emmy') );
        }
    
    
    function test_compare()
        {
        global $CONF;
        // Zero differs from blank string
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'x', 'value' => 0) );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'x', 'value' => '') );
        
        $this->assertFalse( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertFalse( $criterion_a->is_value_equal( '' ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'x', 'value' => '') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'x', 'value' => '0') );
        
        $this->assertFalse( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertFalse( $criterion_a->is_value_equal( '0' ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'x', 'value' => 'banana') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'x', 'value' => 'orange') );
        
        $this->assertFalse( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertFalse( $criterion_a->is_value_equal( 'pineapple' ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'x', 'value' => 'wumpus') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'y', 'value' => 'wumpus') );
        
        $this->assertTrue( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertTrue( $criterion_a->is_value_equal( 'wumpus' ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'beethoven', 'value' => '') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'chopin') );
        
        $this->assertTrue( $criterion_a->is_value_equal( $criterion_b ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'taste', 'default' => 'minty') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'flavour', 'value' => 'minty') );
        
        $this->assertTrue( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertTrue( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertTrue( $criterion_a->is_value_equal( 'minty' ) );
        $this->assertFalse( $criterion_a->is_value_equal( 'sweet' ) );
        
        $criterion_a = QueryCriterionFactory::create( Array('name' => 'taste') );
        $criterion_b = QueryCriterionFactory::create( Array('name' => 'taste') );
        $criterion_a->set_value( 'peachy' );
        $criterion_b->set_value( 'peachy' );
        $criterion_a->set_value( 'vaguely', 'taste[0][index]' );
        $criterion_b->set_value( 'slightly', 'taste[0][index]' );

        $this->assertFalse( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertFalse( $criterion_b->is_value_equal( $criterion_a ) );
        
        $criterion_a->set_value( 'vaguely', 'taste[0][index]' );
        $criterion_b->set_value( 'vaguely', 'taste[0][index]' );
        $this->assertTrue( $criterion_a->is_value_equal( $criterion_b ) );
        $this->assertTrue( $criterion_b->is_value_equal( $criterion_a ) );
        }
        
    function test_multiple_names()
        {
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'q',  'qs_key' => Array('testing','adv') ) );
        
        // getting the qs key still returns the first value
        $this->assertEqual( $criterion->get_name(), 'q' );
        $this->assertEqual( $criterion->get_qs_key(), 'testing' );
        
        // however it will match against both keys
        $this->assertTrue( $criterion->does_qs_key_match('testing') );
        $this->assertTrue( $criterion->does_qs_key_match('adv') );
        // $this->assertTrue( $criterion->does_qs_key_match('q') );
        }
        
    function test_get_render_details()
        {
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'value' => 'banana' ) );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'fruit', 'label'=>'fruit', 'value'=>'banana' ) );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'value' => '' ) );
        $this->assertEqual( $criterion->get_render_details(), Array() );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'label' => 'Yellow Fruit', 'value' => 'banana' ) );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'fruit', 'label'=>'Yellow Fruit', 'value'=>'banana' ) );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'label' => 'Yellow Fruit', 'value' => 'banana', 'is_renderable'=>FALSE ) );
        $this->assertEqual( $criterion->get_render_details(), Array() );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'label' => 'Yellow Fruit', 'render_label' => 'Curved Fruit', 'value' => 'banana' ) );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'fruit', 'label'=>'Curved Fruit', 'value'=>'banana' ) );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'fruit', 'render_label' => 'Curved Fruit', 'value' => 'banana' ) );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'fruit', 'label'=>'Curved Fruit', 'value'=>'banana' ) );
        }
    
    function test_get_render_details_for_list()
        {
        global $CONF;
        $list = Array( ''=>'-- Any Genre --', 'adv'=>'Advertisement', 'biog'=>'Biography' );
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category' ) );
        $this->assertEqual( $criterion->get_render_details(), Array() );
        
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category', 'value'=>'biog' ) );

        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'biog' ) );
        $this->assertEqual( $criterion->get_render_details($list), Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'Biography' ) );
        }

    function test_get_render_details_for_date_range()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );
        
        // default returns nothing to render
        $this->assertEqual( $criterion->get_render_details(), Array() );
        
        $criterion->set_value( 1974 );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'date', 'label' => 'Date from', 'value' => '1974 to 1982' ) );
        }

    function test_get_render_details_for_advanced()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'text', 'qs_key' => 'adv', 'label' => 'Search'
        ) );
        $list = Array( ''=>'All fields', 'title'=>'Title', 'description'=>'Description' );

        $criterion->set_value( 'paul', 'adv[0]' );
        $criterion->set_value( 'title', 'adv[0][index]' );
        $criterion->set_value( 'and', 'adv[0][oper]' );
        
        // $this->assertEqual( $criterion->get_render_details($list), Array( 'label' => 'Search', 'value' => "'paul' in Title" ) );
        
        $criterion->set_value( 'peter', 'adv[1]' );
        $criterion->set_value( 'description', 'adv[1][index]' );
        $criterion->set_value( 'or', 'adv[1][oper]' );
        
        $this->assertEqual( $criterion->get_render_details($list), 
            Array( 'name' => 'text', 'label' => 'Search', 'value' => "'paul' in Title OR 'peter' in Description" ) );
        }

    function test_primary_field()
       {
       $criterion = QueryCriterionFactory::create( Array(
           'name' => 'text', 'label' => 'Search', 'render_default' => 'all records'
       ));
       $this->assertEqual( $criterion->get_render_details(), Array() );

       $criterion = QueryCriterionFactory::create( Array(
           'name' => 'text', 'label' => 'Search', 'render_default' => 'all records', 'is_primary' => TRUE
       ));
       $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'text', 'label' => 'Search', 'value' => 'all records', 'is_primary' => TRUE) );
       }
   
   function test_marked_as_advanced()
       {
       $criterion = QueryCriterionFactory::create( Array(
           'name' => 'text', 'label' => 'Search', 'mode' => QC_MODE_ADVANCED
       ));
       $this->assertFalse( $criterion->is_expanded() );
       $this->assertTrue( $criterion->is_advanced() );
       }

    function test_help_field()
        {
        $criterion = QueryCriterionFactory::create( Array(
               'name' => 'text', 'label' => 'Search', 'help' => 'try typing',
           ));
        $this->assertEqual( 'try typing', $criterion->get_help_text() );
        }
        
    function test_index_from_qs_key()
        {
        global $CONF;
        // specifying a qs_key_index means that the index can be set from the incoming
        // qs_key
        $criterion = QueryCriterionFactory::create( Array( 
            'name' => 'q',  
            'qs_key' => Array('testing','adv'),
            'qs_key_index' => Array( 'testing'=>'test_index', 'pig'=>'pig_index'),
            'index' => 'default',
            ));
        
        $this->assertEqual( 'default', $criterion->get_index() );
        
        $criterion->set_value( 'september' );
        $this->assertEqual( 'default', $criterion->get_index() );
        
        $criterion->set_value( 'october', 'testing' );
        $this->assertEqual( 'test_index', $criterion->get_index() );
        
        $criterion->set_value( 'october', 'pig' );
        $this->assertEqual( 'pig_index', $criterion->get_index() );
        
        // $criterion->set_value( 'truffle', 'q[0][v]' );
        // $this->assertEqual( 'pig_index', $criterion->get_index() );
        // 
        // $criterion->set_value( 'other_index', 'q[1][v]' );
        // $this->assertEqual( 'test_index', $criterion->get_index(1) );
        }
        
    function test_qs_key_index()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array( 
            'name' => 'q',  
            'qs_key' => Array('testing','adv'),
            'qs_key_index' => Array( 'testing'=>'test_index', 'pig'=>'pig_index', 'other'=>'other_index'),
            'index' => 'default',
            ));
        
        // keys from qs_key_index will also be returned as part of the qskeys
        $expected = Array('testing', 'adv', 'pig', 'other');
        $this->assertEqualArray( $expected, $criterion->get_qs_key(TRUE) );
        
        // the qs_key_index should override the index property - if the qs_key is specified
        $this->assertEqual( 'default', $criterion->get_index() );
        
        $criterion->set_value( 'october', 'testing' );
        $this->assertEqual( 'test_index', $criterion->get_index() );
        }
    }

class QueryCriterionDateRangeTestCase
    extends UnitTestCase
    {
    
    function test_date_range()
        {
        $attributes = Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            );
        $criterion = QueryCriterionFactory::create( $attributes );

        // the default start and end dates should be from the year zero to today
        $this->assertEqual( Array("1970-01-01",date('Y-m-d')), $criterion->get_range() );
        $this->assertEqual( Array("1970-01-01",date('Y-m-d')), $criterion->get_default() );
        $this->assertEqual( Array("1970-01-01",date('Y-m-d')), $criterion->get_value() );

        // check that the start and end range values are set 
        $criterion->set_range( Array( "1974", "1984" ) );
        $this->assertEqual( Array("1974-01-01","1984-12-31"), $criterion->get_range() );
        $this->assertEqual( Array("1974-01-01","1984-12-31"), $criterion->get_default() );
        $this->assertEqual( Array("1974-01-01","1984-12-31"), $criterion->get_value() );

        $criterion->set_range( Array( "2000", "2010" ) );

        // setting values not in ascending order should flag an error
        $criterion->set_value( Array("2005","2001") );
        $this->assertTrue( $criterion->status == QC_STATUS_ERROR );

        // same values for hi + lo are fine
        $criterion->set_value( Array("2000","2000") );
        $this->assertTrue( $criterion->status == QC_STATUS_OK );

        $qs_key = $criterion->get_qs_key();
        $this->assertIsA( $qs_key, 'Array' );

        $this->assertEqual( $qs_key[0], $criterion->name . QC_DATE_RANGE_QS_POSTFIX_START );
        $this->assertEqual( $qs_key[1], $criterion->name . QC_DATE_RANGE_QS_POSTFIX_END );

        // setting no value, reverts the value to defaults
        $criterion->set_range( Array( "2000", "2010" ) );
        $criterion->set_value();
        $this->assertEqual( Array("2000-01-01","2010-12-31"), $criterion->get_value() );

        // setting a value by the qs key
        $criterion->set_value( "2003", $criterion->name . QC_DATE_RANGE_QS_POSTFIX_START );
        $this->assertEqual( Array("2003-01-01","2010-12-31"), $criterion->get_value() );

        $this->assertEqual( "2003-01-01", $criterion->get_value( $criterion->name . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "2010-12-31", $criterion->get_value( $criterion->name . QC_DATE_RANGE_QS_POSTFIX_END) );

        $criterion->set_value( "2006", $criterion->name . QC_DATE_RANGE_QS_POSTFIX_END );
        $this->assertEqual( Array("2003-01-01","2006-12-31"), $criterion->get_value() );

        $criterion->set_value( "2002" );
        $this->assertEqual( Array("2002-01-01","2006-12-31"), $criterion->get_value() );

        // setting the value by the date ranges name defaults to setting the start date
        $criterion->set_value();
        $criterion->set_value( "2003-06-06", $criterion->name );
        $this->assertEqual( Array("2003-06-06","2010-12-31"), $criterion->get_value() );
        }
        
    function test_date_range_oob()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( "1974", "1984" ),
            ));
            
        // check that setting a single value that is out of range flags an error
        $criterion->set_value( "1986" );
        $this->assertTrue( $criterion->status == QC_STATUS_ERROR );
        $this->assertEqual( $criterion->error_qs_key, Array('date_start' => TRUE) );
        // $this->assertEqual( Array("1974-01-01","1984-01-01"), $criterion->get_range() );

        $criterion->set_value( "1982" );
        $this->assertTrue( $criterion->status == QC_STATUS_OK );
        
        // set both values ( this is a dual criterion after all ) to acceptable values
        $criterion->set_value( Array("1982-05-05","1983-12-25") );
        $this->assertTrue( $criterion->status == QC_STATUS_OK );
        $this->assertEqual( Array("1982-05-05","1983-12-25"), $criterion->get_value() );

        $criterion->set_value( Array("1971-05-05","1986-12-25") );
        $this->assertTrue( $criterion->status == QC_STATUS_ERROR );
        $this->assertEqual( $criterion->error_qs_key, Array('date_start' => TRUE, 'date_end' => TRUE) );
        // note however the values are still set. always desirable ? certainly for listings it is
        $this->assertEqual( Array("1971-05-05","1986-12-25"), $criterion->get_value() );
        }

    function test_date_range_invalid_qs_key()
        {
        $attributes = Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            );  
        $criterion = QueryCriterionFactory::create( $attributes );

        /// ### NOTE AV : calling expectException will prevent any successive tests from running - nice documentation guys
        $this->expectException( new QueryCriterionException("invalid qs key specified: INVALID") );
        $criterion->set_value( "2020", "INVALID" );
        }

    function test_date_range_initial_value()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );
        
        $this->assertEqual( Array("1973-01-01","1982-12-31"), $criterion->get_range() );
        $this->assertEqual( Array("1973-01-01","1982-12-31"), $criterion->get_default() );
        $this->assertEqual( Array("1973-01-01","1982-12-31"), $criterion->get_value() );
        
        $this->assertEqual( "1973-01-01", $criterion->get_range('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1982-12-31", $criterion->get_range('date' . QC_DATE_RANGE_QS_POSTFIX_END) );

        $this->assertEqual( "1973-01-01", $criterion->get_default('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1982-12-31", $criterion->get_default('date' . QC_DATE_RANGE_QS_POSTFIX_END) );

        $this->assertEqual( "1973-01-01", $criterion->get_value('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1982-12-31", $criterion->get_value('date' . QC_DATE_RANGE_QS_POSTFIX_END) );
                
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            'value' => Array( 1974, 1981 ),
            ) );

        $this->assertEqual( Array("1973-01-01","1982-12-31"), $criterion->get_range() );
        $this->assertEqual( Array("1973-01-01","1982-12-31"), $criterion->get_default() );
        $this->assertEqual( Array("1974-01-01","1981-12-31"), $criterion->get_value() );

        $this->assertEqual( "1973-01-01", $criterion->get_range('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1982-12-31", $criterion->get_range('date' . QC_DATE_RANGE_QS_POSTFIX_END) );

        $this->assertEqual( "1973-01-01", $criterion->get_default('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1982-12-31", $criterion->get_default('date' . QC_DATE_RANGE_QS_POSTFIX_END) );

        $this->assertEqual( "1974-01-01", $criterion->get_value('date' . QC_DATE_RANGE_QS_POSTFIX_START) );
        $this->assertEqual( "1981-12-31", $criterion->get_value('date' . QC_DATE_RANGE_QS_POSTFIX_END) );
        }
    
    function test_date_range_this_year()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 'this_year', 'this_year' ),
            ) );
            
        $this->assertEqual( Array(date('Y') . "-01-01", date('Y') . "-12-31"), $criterion->get_range() );
        }
    


    function test_date_range_default()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 1981 ),
            ) );

        $this->assertTrue( $criterion->is_set_to_default() );

        $criterion->set_value( 1975 );
        $this->assertFalse( $criterion->is_set_to_default() );

        $criterion->set_value( 1974 );
        $this->assertTrue( $criterion->is_set_to_default() );

        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'value' => Array( 1988, 2002 ),
            ) );

        $this->assertTrue( $criterion->is_set_to_default() );
        $criterion->set_value( 2000, "date" . QC_DATE_RANGE_QS_POSTFIX_END );
        $this->assertFalse( $criterion->is_set_to_default() );
        }

    function test_date_range_incomplete_range()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1896 ),
            ) );

        $this->assertEqual( Array("1896-01-01",date('Y-m-d')), $criterion->get_range() );
        $this->assertEqual( Array("1896-01-01",date('Y-m-d')), $criterion->get_default() );
        $this->assertEqual( Array("1896-01-01",date('Y-m-d')), $criterion->get_value() );
        }

    function test_date_range_qs_values()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );

        $this->assertIsA( $criterion, 'QueryCriterionDateRange' );

        // default values should return nothing
        $this->assertEqual( $criterion->get_qs_key_values(), Array() );

        // a single date value should return just the year
        $criterion->set_value( "1974" );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1974' ) );

        $criterion->set_value( Array( "1976", "1977") );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1976', 'date_end'=>'1977' ) );

        $criterion->set_value( Array("1973", "1981") );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_end'=>'1981' ) );

        $criterion->set_value( Array("1973-02", "1982") );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1973-02' ) );

        $criterion->set_value( Array("1973-02", "1981-11") );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1973-02', 'date_end'=>'1981-11-30' ) );
        }

    function test_to_date()
        {
        $criterion = QueryCriterionFactory::create( Array( 'name' => 'testing', 'type' => QC_TYPE_DATE_RANGE ) );

        $this->assertEqual( "2010-03-03", QueryCriterionDateRange::to_date("2010-03-03") );
        $this->assertEqual( "2010-03-03", $criterion->to_date("2010-3-3") );
        $this->assertEqual( "2010-03-01", $criterion->to_date("2010-3") );
        $this->assertEqual( "2010-01-01", $criterion->to_date("2010") );

        $this->assertEqual( "2000-01-31", $criterion->to_date("2000.1.31", FALSE) );
        $this->assertEqual( "2010-12-31", QueryCriterionDateRange::to_date("2010", FALSE) );   
        $this->assertEqual( "2010-06-30", $criterion->to_date("2010.6", FALSE) );
        $this->assertEqual( "2010-07-31", QueryCriterionDateRange::to_date("2010-7", FALSE) );
        $this->assertEqual( "2010-02-28", $criterion->to_date("2010/2", FALSE) );
        $this->assertEqual( "1999-09-30", QueryCriterionDateRange::to_date("1999.9", FALSE) );

        $this->assertEqual( "2000-02-01", QueryCriterionDateRange::to_date("2000.1.32", FALSE) );
        $this->assertEqual( "2000-01-31", QueryCriterionDateRange::to_date("2000.2.0", FALSE) );

        $this->assertEqual( "1666-12-31", QueryCriterionDateRange::to_date("1666", FALSE) );
        $this->assertEqual( "0982-01-01", QueryCriterionDateRange::to_date("982") );
        }

    function test_date_trim()
        {
        $this->assertEqual( "2010", QueryCriterionDateRange::date_trim( "2010-01-31", "2010-01-31" ) );
        $this->assertEqual( "2010", QueryCriterionDateRange::date_trim( "2010-01-01", "2010-01-01" ) );
        $this->assertEqual( "2010-02", QueryCriterionDateRange::date_trim( "2010-02-01", "2010-01-01" ) );
        $this->assertEqual( "2010-02-05", QueryCriterionDateRange::date_trim( "2010-02-05", "2010-01-01" ) );
        }

    function test_get_range_array()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );

        $result = $criterion->get_range_array();
        $this->assertEqual( 10, count($result) );
        $this->assertEqual( 1973, $result[0] );
        $this->assertEqual( 1982, $result[count($result)-1] );
        }

    function test_date_set()
        {
        $this->assertEqual( "2005-01-01", QueryCriterionDateRange::date_set( "2001", 2005 ) );
        $this->assertEqual( "2005-01-01", QueryCriterionDateRange::date_set( "2001", 2005, 0 ) );
        $this->assertEqual( "2005-07-01", QueryCriterionDateRange::date_set( "2001-07", 2005, 0 ) );
        $this->assertEqual( "2005-12-03", QueryCriterionDateRange::date_set( "2001-12-03", 2005 ) );
        
        $this->assertEqual( "2001-03-01", QueryCriterionDateRange::date_set( "2001", 3, 1 ) );
        $this->assertEqual( "2001-04-01", QueryCriterionDateRange::date_set( "2001-02", 4, 1 ) );
        $this->assertEqual( "2010-09-22", QueryCriterionDateRange::date_set( "2010-01-22", 9, 1 ) );
        
        $this->assertEqual( "2001-01-16", QueryCriterionDateRange::date_set( "2001", 16, 2 ) );
        $this->assertEqual( "2001-02-12", QueryCriterionDateRange::date_set( "2001-02", 12, 2 ) );
        $this->assertEqual( "2001-02-09", QueryCriterionDateRange::date_set( "2001-02-15", 9, 2 ) );
        $this->assertEqual( "2010-12-19", QueryCriterionDateRange::date_set( "2010-12-31", 19, 2 ) );
        }
    
    function test_set_value_elements()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 2010 ),
            ) );
        $criterion->set_value( '1975', 'date_start[0]' );
        $criterion->set_value( '2', 'date_start[1]' );
        $criterion->set_value( '26', 'date_start[2]' );
        
        $criterion->set_value( '2001', 'date_end[0]' );
        $criterion->set_value( '10', 'date_end[1]' );
        $criterion->set_value( '3', 'date_end[2]' );
        
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1975-02-26', 'date_end'=>'2001-10-03' ) );
        
        // clear value back to default
        $criterion->set_value();
        $this->assertEqual( Array("1974-01-01","2010-12-31"), $criterion->get_value() );
        
        $criterion->set_value( '19', 'date_end[2]' );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_end'=>'2010-12-19' ) );
        
        $criterion->set_value();
        $criterion->set_value( '5', 'date_start[1]' );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1974-05' ) );
        
        $criterion->set_value();
        $criterion->set_value( '16', 'date[2]' );
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1974-01-16' ) );
        }
        
    function test_get_value_elements()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 2010 ),
            'value' => Array( '1974-05-20', '2010-02-15' ),
            ) );
        
        $this->assertEqual( 1974, $criterion->get_value('date_start[0]') );
        $this->assertEqual( 5, $criterion->get_value('date_start[1]') );
        $this->assertEqual( 20, $criterion->get_value('date_start[2]') );
        
        $this->assertEqual( 2010, $criterion->get_value('date_end[0]') );
        $this->assertEqual( 2, $criterion->get_value('date_end[1]') );
        $this->assertEqual( 15, $criterion->get_value('date_end[2]') );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( '1974-05-20', '2010-02-15' ),
            ) );
        
        $this->assertEqual( 1974, $criterion->get_value('date_start[0]') );
        $this->assertEqual( 5, $criterion->get_value('date_start[1]') );
        $this->assertEqual( 20, $criterion->get_value('date_start[2]') );
        
        $this->assertEqual( 2010, $criterion->get_value('date_end[0]') );
        $this->assertEqual( 2, $criterion->get_value('date_end[1]') );
        $this->assertEqual( 15, $criterion->get_value('date_end[2]') );
        }
        
    function test_get_value_elements_alt()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( '1974-05-20-15-32', '2010-02-15' ),
            ) );
            
        $this->assertEqual( 1974, $criterion->get_value(QC_DATE_YEAR) );
        $this->assertEqual( 5, $criterion->get_value(QC_DATE_MONTH) );
        $this->assertEqual( 20, $criterion->get_value(QC_DATE_DAY) );
        $this->assertEqual( 15, $criterion->get_value(QC_DATE_HOUR) );
        $this->assertEqual( 32, $criterion->get_value(QC_DATE_MINUTE) );
        
        // integer constants only work for the start date...
        }
        
    function test_to_date_relative()
        {
        // two weeks in advance of today
        $correct_date = date('Y-m-d', time() + 1209600);
        $this->assertEqual( $correct_date, QueryCriterionDateRange::to_date( "+1209600" ) );
        
        // last month
        $correct_date = date('Y-m-d', time() - 2419200);
        $this->assertEqual( $correct_date, QueryCriterionDateRange::to_date( "-2419200" ) );
        }
        
        
    function test_to_date_extended()
        {
        $this->assertEqual( "2010-03-03-10-15", QueryCriterionDateRange::to_date("2010-03-03-10-15") );
        }
        
        
    function test_get_value_elements_extended()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 2010 ),
            'value' => Array( '1974-05-20', '2010-02-15' ),
            ) );
            
        $this->assertEqual( 0, $criterion->get_value('date_start[3]') );
        $this->assertEqual( 0, $criterion->get_value('date_start[4]') );
        
        $this->assertEqual( 23, $criterion->get_value('date_end[3]') );
        $this->assertEqual( 59, $criterion->get_value('date_end[4]') );
        
        /// NOTE AV : this is actually more a test of QueryCriterionDateRange::to_date
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( '1974-05-20-13-56', '2010-02-15-11-09' ),
            ) );
            
        $this->assertEqual( 13, $criterion->get_value('date_start[3]') );
        $this->assertEqual( 56, $criterion->get_value('date_start[4]') );
        
        $this->assertEqual( 11, $criterion->get_value('date_end[3]') );
        $this->assertEqual( '09', $criterion->get_value('date_end[4]') );
        }
        
    function test_date_set_extended()
        {
        $this->assertEqual( "2001-01-01-11-00", QueryCriterionDateRange::date_set( "2001", 11, 3 ) );
        $this->assertEqual( "2001-01-01-00-58", QueryCriterionDateRange::date_set( "2001", 58, 4 ) );
        
        $this->assertEqual( "2010-07-12-23-15", QueryCriterionDateRange::date_set( "2010-07-12-09-15", 23, 3 ) );
        $this->assertEqual( "2010-07-12-09-58", QueryCriterionDateRange::date_set( "2010-07-12-09-15", 58, 4 ) );
        }
    
    function test_set_value_elements_extended()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 2010 ),
            ) );
        $criterion->set_value( '1975', 'date_start[0]' );
        $criterion->set_value( '2', 'date_start[1]' );
        $criterion->set_value( '26', 'date_start[2]' );
        $criterion->set_value( '10', 'date_start[3]' );
        $criterion->set_value( '55', 'date_start[4]' );
        
        $criterion->set_value( '2001', 'date_end[0]' );
        $criterion->set_value( '10', 'date_end[1]' );
        $criterion->set_value( '3', 'date_end[2]' );
        $criterion->set_value( '23', 'date_end[3]' );
        $criterion->set_value( '12', 'date_end[4]' );

        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1975-02-26-10-55', 'date_end'=>'2001-10-03-23-12' ) );
        }
        
    function test_set_value_elements_alt()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Year',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1974, 2010 ),
            ) );
            
        $criterion->set_value( '1975', QC_DATE_YEAR );
        $criterion->set_value( '2', QC_DATE_MONTH );
        $criterion->set_value( '26', QC_DATE_DAY );
        $criterion->set_value( '10', QC_DATE_HOUR );
        $criterion->set_value( '55', QC_DATE_MINUTE );
        
        $this->assertEqual( $criterion->get_qs_key_values(), Array( 'date_start'=>'1975-02-26-10-55' ) );
        }
        
    function test_get_render_details_for_date_range_extended()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'label' => 'Date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );

        // default returns nothing to render
        $this->assertEqual( $criterion->get_render_details(), Array() );

        $criterion->set_value( '1974-05-15-11-45' );
        $this->assertEqual( $criterion->get_render_details(), Array( 'name' => 'date', 'label' => 'Date from', 'value' => '1974-05-15 11:45 to 1982' ) );
        }
        
    function test_get_timestamp()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, '1982-07-25-16-46' ),
            ) );
        
        $this->assertEqual( mktime( 0,0,0, 1,1,1973 ), $criterion->get_timestamp() );
        $this->assertEqual( mktime( 0,0,0, 1,1,1973 ), $criterion->get_timestamp(0) );
        $this->assertEqual( mktime( 0,0,0, 1,1,1973 ), $criterion->get_timestamp('date_start') );
        
        $this->assertEqual( mktime( 16,46,0, 7,25,1982 ), $criterion->get_timestamp(1) );
        $this->assertEqual( mktime( 16,46,0, 7,25,1982 ), $criterion->get_timestamp('date_end') );
        }
        
    function test_get_list()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'date',
            'type' => QC_TYPE_DATE_RANGE,
            'range' => Array( 1973, 1982 ),
            ) );
        // When retrieving a list related to date, the particular qs key should always be passed
        $this->assertEqual( 'date_start', $criterion->get_list() );
        $this->assertEqual( 'date_start', $criterion->get_list('date_start') );
        $this->assertEqual( 'date_end', $criterion->get_list('date_end') );
        }
    }

class QueryCriterionFlagTestCase
    extends UnitTestCase
    {
    function test_basic()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));
        
        $criterion->set_value( 0 );
        $this->assertTrue( $criterion->get_value() );
        
        $criterion->set_value();
        $criterion->set_value( FALSE );
        $this->assertFalse( $criterion->get_value() );
        
        $criterion->set_value();
        $criterion->set_value( FALSE, 0 );
        $this->assertFalse( $criterion->get_value() );
        
        $criterion->set_value( TRUE );
        $this->assertTrue( $criterion->get_value() );
        }
        
    function test_is_bool()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            ));
        
        $value = FALSE;
        $this->assertTrue( $criterion->is_bool( TRUE, $value ) );
        $this->assertTrue( $value );
        
        $this->assertTrue( $criterion->is_bool( FALSE, $value ) );
        $this->assertFalse( $value );
        
        $this->assertTrue( $criterion->is_bool( 1, $value ) );
        $this->assertTrue( $value );
        
        $this->assertTrue( $criterion->is_bool( 0, $value ) );
        $this->assertFalse( $value );
        
        $this->assertTrue( $criterion->is_bool( '1', $value ) );
        $this->assertTrue( $value );
        
        $this->assertTrue( $criterion->is_bool( '0', $value ) );
        $this->assertFalse( $value );
        
        $this->assertTrue( $criterion->is_bool( 'y', $value ) );
        $this->assertTrue( $value );
        
        $this->assertTrue( $criterion->is_bool( 'n', $value ) );
        $this->assertFalse( $value );
        
        $this->assertFalse( $criterion->is_bool( 'BING BONG', $value ) );
        $this->assertFalse( $value );
        
        $this->assertFalse( $criterion->is_bool( 5, $value ) );
        $this->assertFalse( $value );
        
        $this->assertFalse( $criterion->is_bool( -1, $value ) );
        $this->assertFalse( $value );
        
        }


    function test_multiple_values()
        {
        global $CONF;
        $list = Array( 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven');

        // alternative constructor for flags
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));
        $this->assertEqual( 0, count($criterion) );
        $this->assertTrue( $criterion->is_set_to_default() );        
        $this->assertEqual( Array(), $criterion->get_render_details() );
        $this->assertEqual( Array(), $criterion->get_qs_key_values() );

        $criterion->set_value(TRUE);
        $this->assertFalse( $criterion->is_set_to_default() );
        $this->assertEqual( Array( 'flag' => 1 ), $criterion->get_qs_key_values() );

        $criterion->set_value(TRUE, 2);
        $this->assertEqual( Array( 'flag[0]' => 1, 'flag[2]' => 1 ), $criterion->get_qs_key_values() );

        $criterion->set_value(TRUE, 4);
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'One; Three; Five' ), $criterion->get_render_details($list) );
        $this->assertEqual( Array( 'flag[0]' => 1, 'flag[2]' => 1, 'flag[4]' => 1 ), $criterion->get_qs_key_values() );

        $criterion->set_value( 0, 'flag[0]' );
        $criterion->set_value( 0, 'flag[1]' );
        $criterion->set_value( 0, 'flag[2]' );
        $criterion->set_value( 1, 'flag[3]' );
        $criterion->set_value( 1, 'flag[4]' );

        $this->assertFalse( $criterion->get_value( 0 ) );

        $this->assertEqual( Array( FALSE, FALSE, FALSE, TRUE, TRUE ), $criterion->get_value() );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Four; Five' ), $criterion->get_render_details($list) );
        $this->assertEqual( Array( 'flag[3]' => 1, 'flag[4]' => 1  ), $criterion->get_qs_key_values() );

        $criterion->set_value( 1, 'flag[2]' );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Three; Four; Five' ), $criterion->get_render_details($list) );
        $this->assertEqual( Array( 'flag[2]' => 1, 'flag[3]' => 1, 'flag[4]' => 1  ), $criterion->get_qs_key_values() );

        $this->assertFalse( $criterion->get_value( 0 ) );
        $this->assertFalse( $criterion->get_value( 1 ) );
        $this->assertTrue( $criterion->get_value( 2 ) );
        $this->assertTrue( $criterion->get_value( 3 ) );

        $criterion->set_value( NULL );
        $this->assertTrue( $criterion->is_set_to_default() );

        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));

        $this->assertEqual( 0, count($criterion) );
        $this->assertFalse( $criterion->get_value( 1 ) );
        $this->assertFalse( $criterion->get_value( 3 ) );
        $this->assertFalse( $criterion->get_value( 6 ) );

        $criterion->set_value( 0, 'flag[1]' );
        $criterion->set_value( 1, 'flag[2]' );
        $criterion->set_value( 0, 'flag[3]' );
        $criterion->set_value( 1, 'flag[4]' );
        $criterion->set_value( 0, 'flag[5]' );
        $criterion->set_value( 1, 'flag[6]' );

        $this->assertEqual( 6, count($criterion) );
        $this->assertFalse( $criterion->get_value( 1 ) );
        $this->assertTrue( $criterion->get_value( 2 ) );
        $this->assertFalse( $criterion->get_value( 3 ) );
        $this->assertTrue( $criterion->get_value( 4 ) );
        $this->assertFalse( $criterion->get_value( 5 ) );
        $this->assertTrue( $criterion->get_value( 6 ) );
        }
        
    function test_get_render_details()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            'value' => Array( 1=>1, 3=>1, 5=>1 ),
            'list' => Array( 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven'),
            ));
        
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Two; Four; Six' ), $criterion->get_render_details() );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            'value' => Array( 'c'=>1, 'e'=>1, 'g'=>1 ),
            'list' => Array( 'a' => 'One', 'b' => 'Two', 'c' => 'Three', 'd' => 'Four', 'e' => 'Five', 'f' => 'Six', 'g' => 'Seven'),
            ));
            
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Three; Five; Seven' ), $criterion->get_render_details() );
        }
        
    function test_get_render_details_single()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));
        
        $list = Array( 1 => 'Animation', 2 => 'Audio', 3 => 'Cinema', 4 => 'Documents' );
        
        $this->assertEqual( Array(  ), $criterion->get_render_details($list) );
        $this->assertEqual( Array(  ), $criterion->get_render_details() );
        
        $criterion->set_value( TRUE );
        
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Animation' ), $criterion->get_render_details($list) );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected' ), $criterion->get_render_details() );
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            'value' => 1,
            'list' => Array( 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven'),
            ));
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'Two' ), $criterion->get_render_details() );
        }
        
    function test_get_render_details_list()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_OPTION,
            'list' => Array(
                'A' => 'TV and Radio',
                'B' => 'TV Only',
                'C' => 'Radio Only',
                )));
        
        $this->assertEqual( Array(  ), $criterion->get_render_details() );
        
        $criterion->set_value( 'A' );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'TV and Radio' ), $criterion->get_render_details() );
        }
        
    function test_get_render_details_list_multi()
        {
        global $CONF;
        $list = Array(
            19 => 'BBC Radio 1',
            54 => 'BBC1 London',
            89 => 'Bravo',
            138 => 'Five',
            172 => 'ITV News',
            );
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'list',
            'label' => 'List',
            'type' => QC_TYPE_LIST,
            'list' => $list ));
        $criterion->set_value( array( 138 ) );
        $this->assertEqual( Array(  'name' => 'list', 'label' => 'List', 'value' => 'Five' ), $criterion->get_render_details($list) );
        
        $criterion->set_value( array( 19, 89, 172 ) );
        $this->assertEqual( Array(  'name' => 'list', 'label' => 'List', 'value' => 'BBC Radio 1, Bravo, ITV News' ), $criterion->get_render_details($list) );
        }
        
    function test_get_render_details_render_label()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'render_label' => 'Text Label',
            'type' => QC_TYPE_FLAG,
            ));
        
        $this->assertEqual( Array(), $criterion->get_render_details() );
        
        $criterion->set_value( TRUE );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Text Label' ), $criterion->get_render_details() );
        }

    function test_non_integer_keys()
        {
        $list = Array( 'alpha'=>'One', 'beta'=>'Two', 'gamma'=>'Three', 'delta'=>'Four', 'epsilon'=>'Five', 'zeta'=>'Six', 'eta' => 'Seven');
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));
        
        $criterion->set_value(TRUE, 'flag[alpha]');
        $this->assertEqual( Array( 'flag[alpha]' => 1 ), $criterion->get_qs_key_values() );
        
        $this->assertTrue( $criterion->get_value('flag[alpha]') );
        $this->assertTrue( $criterion->get_value('alpha') );
        
        $criterion->set_value(TRUE, 'flag[beta]');
        $this->assertEqual( Array( 'flag[alpha]' => 1, 'flag[beta]' => 1 ), $criterion->get_qs_key_values() );
        
        $this->assertTrue( $criterion->get_value('flag[beta]') );
        $this->assertTrue( $criterion->get_value('beta') );
        
        $criterion->set_value(TRUE, 'flag[epsilon]' );
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'Selected', 'value' => 'One; Two; Five' ), $criterion->get_render_details($list) );
        $this->assertTrue( $criterion->get_value('flag[epsilon]') );
        $this->assertTrue( $criterion->get_value('epsilon') );
        }
        
    function test_multiple_default()
        {
        global $CONF;
        $list = Array( 'alpha'=>'One', 'beta'=>'Two', 'gamma'=>'Three', 'delta'=>'Four', 'epsilon'=>'Five', 'zeta'=>'Six', 'eta' => 'Seven');
        
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            'default' => Array('alpha', 'gamma', 'epsilon'),
            ));
        
        // $this->assertEqual( Array(  'name' => 'flag', 'label' => 'flag', 'value' => 'One; Three; Five' ), $criterion->get_render_details($list) );
        // $CONF['debug_trace'] = 1;
        // println("rendered value:");
        // $test = Array( 'alpha' => 1, 'beta' => 1 );
        // print_var( $criterion->get_value() );
        // $CONF['debug_trace'] = 0;
        // print_var( $criterion );
        // exit();
        
        // also check that setting a value will work
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            'value' => Array('zeta'=>1,'beta'=>1),
            ));
        $this->assertEqual( Array(  'name' => 'flag', 'label' => 'flag', 'value' => 'Two; Six' ), $criterion->get_render_details($list) );
        }
        
    function test_direct_set()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'label' => 'Selected',
            'type' => QC_TYPE_FLAG,
            ));
        
        // able to set an index on the flag directly
        $criterion->set_value( 2, 'flag' );
        $this->assertEqual( Array( 'flag[2]' => 1 ), $criterion->get_qs_key_values() );
        
        $criterion->set_value();
        $criterion->set_value( 1, 'flag' );
        $this->assertEqual( Array( 'flag[1]' => 1 ), $criterion->get_qs_key_values() );
        }
        
    function test_set_to_default()
        {
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            'default' => Array( 26=>1, 34=>1, 55=>1, 62=>1 ),
            ));
        $this->assertTrue( $criterion->is_set_to_default() );
        $criterion->set_value( Array( 26=>1, 34=>1, 55=>1, 62=>1 ) );
        $this->assertTrue( $criterion->is_set_to_default() );
        $criterion->set_value();
        $criterion->set_value( 34 );
        $this->assertFalse( $criterion->is_set_to_default() );
        }
        
    function test_set_from_array()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            ));
        $criterion->set_value();
        $criterion->set_value( Array( 22=>1, 23=>1, 25=>1 ) );
        $this->assertTrue( $criterion->get_value(22) );
        $this->assertTrue( $criterion->get_value(23) );
        $this->assertTrue( $criterion->get_value(25) );
        $this->assertFalse( $criterion->get_value(24) );
        
        $criterion->set_value();
        $criterion->set_value( Array( 15 => 1, 16 => 1, 18 => 1) );
        $this->assertTrue( $criterion->get_value(15) );
        $this->assertTrue( $criterion->get_value(16) );
        $this->assertTrue( $criterion->get_value(18) );
        $this->assertFalse( $criterion->get_value(17) );
        
        // NOTE AV - be careful when setting array values that could be interpreted as a non-associative array...
        $criterion->set_value();
        $criterion->set_value( Array( 0 => 1, 1 => 1) );
        $this->assertTrue( $criterion->get_value(0) );
        $this->assertTrue( $criterion->get_value(1) );
        $this->assertFalse( $criterion->get_value(3) );
        
        $criterion->set_value();
        $criterion->set_value( Array( 0 => 1 ) );
        $this->assertTrue( $criterion->get_value(0) );

        $criterion->set_value();
        $criterion->set_value( Array( 0 => 0 ) );
        $this->assertFalse( $criterion->get_value(0) );
        
        }
    
    function test_single_value_get()
        {
        global $CONF;
        $criterion = QueryCriterionFactory::create( Array(
            'name' => 'flag',
            'type' => QC_TYPE_FLAG,
            ));
        $criterion->set_value( TRUE );
        $this->assertFalse( $criterion->get_value( 0 ) );
        $this->assertTrue( $criterion->get_value( 1 ) );
        $this->assertFalse( $criterion->get_value( 2 ) );
        $this->assertFalse( $criterion->get_value( 3 ) );
        $this->assertFalse( $criterion->get_value( 4 ) );
        }
    }