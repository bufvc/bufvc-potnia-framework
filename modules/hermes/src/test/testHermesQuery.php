<?php
// $Id$
// Tests for Hermes query
// Phil Hansen, 03 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');
require_once('../HermesDataSource.class.php');
require_once($CONF['path_src'] . 'datasource/test/BaseDataSourceTestCase.class.php');

class HermesQueryTestCase
    extends BaseDataSourceTestCase
    {
    // The test subject
    var $query_class_name = 'HermesQuery';

    // Basic query configuration
    var $expected_query_table = 'title';

    // Query criteria
    // (assumes 'magic' criteria are used)
    var $expected_criteria = Array(
            'q'=>'',
            'date_start'=>'',
            'date_end'=>'',
            'title_format'=>'',
            'category'=>'',
            'language'=>'',
            'country'=>'',
            'sort'=>'',
            'keyword'=>'',
            'org'=>'',
            );

    // Lists
    var $expected_lists = Array(
        'page_size', 'sort', 'boolean_op', 'advanced_table',
        'date_start', 'date_end', 'title_format', 'category',
        'language', 'country',
        );
    var $expected_advanced_indexes = Array('', 'title', 'series', 'person', 'keyword', 'org');

    // Dates
    // (both 0 if no date criteria)
    var $start_date = 1896;
    var $end_date = 2010;

    function setup()
        {
        global $MODULE;
        $this->module = $MODULE;
        parent::setup();
        global $HERMES_TEST_ITEM;
        $this->test_data = $HERMES_TEST_ITEM;
        $this->query = QueryFactory::create($MODULE);
        }
        
    function new_datasource()
        {
        return new HermesDataSource($this->module);
        }
        
    function test_to_string()
        {
        $criteria = Array(
            "q[0]['value']"=>'foo', "q[0]['index']"=>'title', "q[0]['oper']"=>'and',
            "q[1]"=>'bar', "q[1]['index']"=>'person', "q[1]['oper']"=>'and',
            "q[2]['value']"=>'test', "q[2]['oper']"=>'or',
            );

        // empty criteria
        $this->assertPattern('|all records|', $this->query->criteria_string());

        // advanced criteria
        $this->query->set_criteria_values($criteria);
        $this->assertPattern('|\'foo\' in Title AND \'bar\' in Contributors OR \'test\' in All fields|', 
                $this->query->criteria_string(QUERY_STRING_TYPE_HTML));
                
        // remove the second advanced criteria
        unset($criteria['q[1]']);
        $this->query->set_criteria_values($criteria);
        $this->assertPattern('|\'foo\' in Title OR \'test\' in All fields|', 
                $this->query->criteria_string(QUERY_STRING_TYPE_HTML));
                
        // check plain text version
        $this->assertNoPattern('|<br \/>|', 
                $this->query->criteria_string(QUERY_STRING_TYPE_TEXT));
        }
    }
