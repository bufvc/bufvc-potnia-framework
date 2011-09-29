<?php
// $Id$
// Tests for DataSourceTraverser
// James Fryer, 8 July 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('DataSource');

class FakeDataSourceTraverser
    extends DataSourceTraverser
    {
    // Log calls to process_record
    var $log;
    function process_record($record)
        {
        $this->log .= $record . ' ';
        }
    function process_error()
        {
        $this->log .= 'ERROR ';
        }
    }

class DataSourceTraverserTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new MockDataSource();
        $this->traverser = new FakeDataSourceTraverser($this->ds, 2);
        }
    
    function test_no_results()
        {
        $this->ds->expectOnce('search', Array('table', 'query', 0, $this->traverser->page_size));
        $this->ds->setReturnValue('search', Array('total'=>0));
        $this->traverser->for_all('table', 'query');
        $this->assertNull($this->traverser->log);
        }
        
    function test_one_page()
        {
        $this->ds->expectOnce('search', Array('table', 'query', 0, $this->traverser->page_size));
        $this->ds->setReturnValue('search', Array('total'=>2, 'count'=>2, 'offset'=>0, 'data'=>Array('A', 'B')));
        $this->traverser->for_all('table', 'query');
        $this->assertEqual('A B ', $this->traverser->log);
        }
        
    function test_multiple_page()
        {
        $this->ds->expectCallCount('search', 2);
        $this->ds->expectAt(0, 'search', Array('table', 'query', 0, $this->traverser->page_size));
        $this->ds->setReturnValueAt(0, 'search', Array('total'=>3, 'count'=>2, 'offset'=>0, 'data'=>Array('A', 'B')));
        $this->ds->expectAt(1, 'search', Array('table', 'query', 2, $this->traverser->page_size));
        $this->ds->setReturnValueAt(1, 'search', Array('total'=>3, 'count'=>1, 'offset'=>2, 'data'=>Array('C')));
        $this->traverser->for_all('table', 'query');
        $this->assertEqual('A B C ', $this->traverser->log);
        }
        
    function test_ds_error()
        {
        $this->ds->setReturnValue('search', NULL);
        $this->traverser->for_all('table', 'query');
        $this->assertEqual('ERROR ', $this->traverser->log);
        }        

    function test_count_can_be_less_than_requested()
        {
        // This DS has three items but decides to send them 1,2 instead of 2,1
        // No DS currently does it, but the traverser should accept it
        $this->ds->expectCallCount('search', 2);
        $this->ds->expectAt(0, 'search', Array('table', 'query', 0, $this->traverser->page_size));
        $this->ds->setReturnValueAt(0, 'search', Array('total'=>3, 'count'=>1, 'offset'=>0, 'data'=>Array('A')));
        $this->ds->expectAt(1, 'search', Array('table', 'query', 1, $this->traverser->page_size));
        $this->ds->setReturnValueAt(1, 'search', Array('total'=>3, 'count'=>2, 'offset'=>1, 'data'=>Array('B', 'C')));
        $this->traverser->for_all('table', 'query');
        $this->assertEqual('A B C ', $this->traverser->log);
        }

    function test_one_page_with_limit()
        {
        $this->ds->expectOnce('search', Array('table', 'query', 1, 1));
        $this->ds->setReturnValue('search', Array('total'=>2, 'count'=>1, 'offset'=>1, 'data'=>Array('A')));
        $this->traverser->for_all('table', 'query', 1, 1);
        $this->assertEqual('A ', $this->traverser->log);
        }        

    function test_multiple_page_with_limit()
        {
        $this->ds->expectCallCount('search', 2);
        $this->ds->expectAt(0, 'search', Array('table', 'query', 1, $this->traverser->page_size));
        $this->ds->setReturnValueAt(0, 'search', Array('total'=>5, 'count'=>2, 'offset'=>1, 'data'=>Array('A', 'B')));
        $this->ds->expectAt(1, 'search', Array('table', 'query', 3, 1));
        $this->ds->setReturnValueAt(1, 'search', Array('total'=>5, 'count'=>1, 'offset'=>3, 'data'=>Array('C')));
        $this->traverser->for_all('table', 'query', 1, 3);
        $this->assertEqual('A B C ', $this->traverser->log);
        }

    function test_exceeds_limit()
        {
        $this->ds->expectOnce('search', Array('table', 'query', 1, $this->traverser->page_size));
        $this->ds->setReturnValue('search', Array('total'=>2, 'count'=>1, 'offset'=>1, 'data'=>Array('A')));
        $this->traverser->for_all('table', 'query', 1, 2);
        $this->assertEqual('A ', $this->traverser->log);
        }        
    }
