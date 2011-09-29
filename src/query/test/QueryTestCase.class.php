<?php
// $File$
// Test cases for Query classes
// James Fryer, 13 Feb 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class QueryTestCase
    extends UnitTestCase
    {
    // The test subject
    var $query_class_name = 'YourQueryClass';

    // Basic query configuration
    var $expected_query_table = 'title';

    // Query criteria
    // (assumes 'magic' criteria are used)
    var $expected_criteria = Array(
            'q'=>'',
            'date_start'=>'',
            'date_end'=>'',
            'category'=>'',
            'sort'=>''
            );

    // Lists
    var $expected_lists = Array(
        'page_size',
        'sort',
        'boolean_op',
        'advanced_table',
        'date_start',
        'date_end',
        'category',
        );
    var $expected_advanced_indexes = Array(
        '',
        'title',
        'description',
        );

    // Dates
    // (both 0 if no date criteria)
    var $start_date = 0;
    var $end_date = 0;

    function setup()
        {
        global $MODULE;
        $this->query = QueryFactory::create($MODULE, Array('query_class'=>$this->query_class_name));
        }

    function test_defaults()
        {
        $this->assertEqual($this->query->table_name, $this->expected_query_table);
        }

    function test_criteria()
        {
        $this->assertEqual($this->query->criteria, $this->expected_criteria);
        }

    function test_magic_criteria()
        {
        $magic_criteria = Array('adv_q1'=>'foo', 'adv_oper1'=>'and', 'adv_index1'=>'');
        $this->query->set_criteria_values(Array('adv_q1'=>'foo'));
        $this->assertEqual($this->query->criteria, array_merge($this->expected_criteria, $magic_criteria));
        }

    function test_lists()
        {
        foreach ($this->expected_lists as $name)
            $this->assertNotNull($this->query->get_list($name));
        }

    function test_advanced_table_list()
        {
        $elems = array_keys($this->query->get_list('advanced_table'));
        $this->assertEqual($elems, $this->expected_advanced_indexes);
        }

    function test_dates_lists()
        {
        if ($this->start_date == 0 && $this->end_date == 0)
            return;
        $expected_dates = range($this->start_date, $this->end_date);

        // Start date
        $dates = $this->query->get_list('date_start');
        $this->assertEqual(count($dates), count($expected_dates));
        $keys = array_merge(Array(''), array_slice($expected_dates, 1));
        $this->assertEqual(array_keys($dates), $keys);
        $this->assertEqual(array_values($dates), $expected_dates);

        // End date
        //### FIXME: stop reversing end date list
        $expected_dates = array_reverse($expected_dates);
        $dates = $this->query->get_list('date_end');
        $this->assertEqual(count($dates), count($expected_dates));
        $keys = array_merge(Array(''), array_slice($expected_dates, 1));
        $this->assertEqual(array_keys($dates), $keys);
        $this->assertEqual(array_values($dates), $expected_dates);
        }
    }

?>
