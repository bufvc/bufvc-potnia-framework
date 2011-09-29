<?php
// $Id$
// Tests for QueryLogParser
// Phil Hansen, 22 April 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('MDB2_Driver_mysql', 'MockDB');

class QueryLogParserTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->parser = new QueryLogParser();
        }
    
    function test_parse_line()
        {
        $line = "2011-04-12 16:06:57 +0100 0.0.0.0 fed editor QUERY-START 123 item http://localhost/bufvc/fed/search.php?q=apple&sort=relevance \"{default=apple}{sort=relevance} | Search All BUFVC for: apple\n\"";
        $expected = Array(
            '123' => Array(
                'date' => '2011-04-12',
                'time' => '16:06:57',
                'timezone' => '+0100',
                'ip' => '0.0.0.0',
                'module' => 'fed',
                'user' => 'editor',
                'qid' => '123',
                'table' => 'item',
                'url' => 'http://localhost/bufvc/fed/search.php?q=apple&sort=relevance',
                'query' => '{default=apple}{sort=relevance}',
                'readable' => "Search All BUFVC for: apple\n",
                ),
            );
                
        // empty line ignored
        $this->parser->parse_line('');
        $this->assertTrue(empty($this->parser->stats));
        // invalid line ignored
        $this->parser->parse_line('A B C');
        $this->assertTrue(empty($this->parser->stats));
        
        $this->parser->parse_line($line);
        $this->assertEqual($this->parser->stats, $expected);
        }
    }

class QueryLogStatsTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->parser = new QueryLogStats();
        }
    
    function test_process_query()
        {
        $query = Array(
            'date' => '2011-04-12',
            'time' => '16:06:57',
            'timezone' => '+0100',
            'ip' => '0.0.0.0',
            'module' => 'fed',
            'user' => 'editor',
            'qid' => '123',
            'table' => 'item',
            'url' => 'url',
            'query' => 'criteria',
            'readable' => "details",
            'count' => '1',
            'accuracy' => 'exact',
            'duration' => '1',
            );
        $expected = "2011-04-12\t16:06:57\t+0100\t0.0.0.0\tfed\teditor\t123\titem\turl\tcriteria\tdetails\t1\texact\t1\n";
        ob_start();
        $this->parser->process_query($query);
        $result = ob_get_contents();
        ob_end_clean();
        $this->assertEqual($result, $expected);
        }
    
    function test_finish_parse()
        {
        $query = Array(
            'date' => '2011-04-12',
            'time' => '16:06:57',
            'timezone' => '+0100',
            'ip' => '0.0.0.0',
            'module' => 'fed',
            'user' => 'editor',
            'qid' => '123',
            'table' => 'item',
            'url' => 'url',
            'query' => 'criteria',
            'readable' => "details",
            );
        $this->parser->stats['123'] = $query;
        $expected = "2011-04-12\t16:06:57\t+0100\t0.0.0.0\tfed\teditor\t123\titem\turl\tcriteria\tdetails\t-1\t\t\n";
        ob_start();
        $this->parser->finish_parse();
        $result = ob_get_contents();
        ob_end_clean();
        $this->assertEqual($result, $expected);
        }
    }

class QueryLogCountTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->parser = new QueryLogCount('dummy');
        $this->parser->db = new MockDB();
        $this->parser->last_date = strtotime('2011-04-22 11:00:00');
        }
    
    function test_check_additional()
        {
        // date before last seen
        $this->assertFalse($this->parser->check_additional('2011-04-21', '12:00:00', '', '', 'dummy', '', '', '', ''));
        // time before last seen
        $this->assertFalse($this->parser->check_additional('2011-04-22', '10:00:00', '', '', 'dummy', '', '', '', ''));
        // date/time same as last seen
        $this->assertFalse($this->parser->check_additional('2011-04-22', '11:00:00', '', '', 'dummy', '', '', '', ''));
        // incorrect module
        $this->assertFalse($this->parser->check_additional('2011-04-23', '12:00:00', '', '', 'dummy2', '', '', '', ''));
        // date and module ok
        $this->assertTrue($this->parser->check_additional('2011-04-23', '12:00:00', '', '', 'dummy', '', '', '', ''));
        }
    
    function test_process_query()
        {
        $query = Array(
            'date' => '2011-04-12',
            'time' => '16:06:57',
            'timezone' => '+0100',
            'ip' => '0.0.0.0',
            'module' => 'fed',
            'user' => 'editor',
            'qid' => '123',
            'table' => 'item',
            'url' => 'url',
            'query' => 'criteria',
            'readable' => "details",
            'count' => '1',
            'accuracy' => 'exact',
            'duration' => '1',
            );
        $expected = Array(
            'url' => Array(
                'url' => 'url',
                'count' => 1,
                'date' => '2011-04-12 16:06:57',
                'module' => 'fed',
                'search_table' => 'item',
                'criteria' => 'criteria',
                'details' => 'details',
                'results_count' => 1,
                ),
            );
        // first time seen, query is added
        $this->parser->process_query($query);
        $this->assertEqual($this->parser->queries, $expected);
        // additional time, count is increased and date/results_count updated
        $query['date'] = '2011-04-13';
        $query['count'] = '5';
        $expected['url']['count'] = 2;
        $expected['url']['date'] = '2011-04-13 16:06:57';
        $expected['url']['results_count'] = 5;
        $this->parser->process_query($query);
        $this->assertEqual($this->parser->queries, $expected);
        }
    
    function test_finish_parse()
        {
        $query = Array(
            'url' => 'url',
            'count' => 1,
            'date' => '2011-04-12 16:06:57',
            'module' => 'fed',
            'search_table' => 'item',
            'criteria' => 'criteria',
            'details' => 'details',
            'results_count' => 1,
            );
        $this->parser->queries['url'] = $query;
        $this->parser->db->setReturnValueAt(0, 'quote', "'url'");
        // retrieve query
        $this->parser->db->expectOnce('queryRow', Array("SELECT * FROM QueryStats WHERE url='url'", NULL, 2));
        // create called once
        $this->parser->db->expectOnce('exec');
        $this->parser->finish_parse();
        }
    }