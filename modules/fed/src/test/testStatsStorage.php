<?php
// $Id$
// Count record accesses
// James Fryer, 11 Aug 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');

Mock::generate('RecordStats');
Mock::generate('QueryStats');

// Integration test for counter storage
class StatsStorageIntegrationTestCase
    extends UnitTestCase
    {
    var $config = Array(
        'test' => Array(
            'title'=>'Test',
            'description'=>'Test viewcount table',
            'mutable'=>TRUE,
            'storage'=> 'stats',
            ),
        );

    function setup($config=NULL)
        {
        global $MODULE;
        if (is_null($config))
            {
            $config = $this->config;
            $config['test']['pear_db'] = $MODULE->get_pear_db();
            }
        $this->ds = new DataSource($config);
        }

    function test_crud()
        {
        $url = '/test/item1';

        // Retrieve
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual(1, $record['count']);

        // Retrieve again increments the counter
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        $this->assertEqual(2, $record['count']);

        // A different record has its own counter
        $record = $this->ds->retrieve($url . '2');
        $this->assertNoError();
        $this->assertEqual(1, $record['count']);
        }

    function test_score()
        {
        $url = '/test/item2';

        // Default score data
        $record = $this->ds->retrieve($url);
        $this->assertNoError();
        $this->assertEqual($record['url'], $url);
        $this->assertEqual(0, $record['score']);
        $this->assertEqual(0, $record['score_count']);
        //### maybe???
        //### $this->assertEqual(0, $record['score_high']);
        //### $this->assertEqual(0, $record['score_high_count']);
        //### $this->assertEqual(0, $record['score_low']);
        //### $this->assertEqual(0, $record['score_low_count']);
        //### $this->assertEqual(0, $record['score_your_vote']);
        
        // Add score
        $record = $this->ds->update($url, Array('username'=>'test_user', 'score'=>1));
        $this->assertEqual($record['url'], $url);
        $this->assertEqual(1.0, $record['score']);
        $this->assertEqual(1, $record['score_count']);
        }

    //### FIXME: these functions should be in a base class
    function assertNoError()
        {
        $this->assertTrue($this->ds->error_code == 0, 'error_code: ' . $this->ds->error_code);
        $this->assertTrue($this->ds->error_message == '', 'error_message: ' . $this->ds->error_message);
        }
    }

class StatsStorageTestCase
    extends UnitTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->ds = new DataSource(Array());
        $this->storage = new DataSource_StatsStorage($this->ds, $MODULE->get_pear_db());
        $this->storage->stats = new MockRecordStats();
        }

    function test_retrieve_not_found()
        {
        $this->storage->stats->expectOnce('exists', Array('bar'));
        $this->storage->stats->setReturnValue('exists', 0);
        $this->storage->stats->expectNever('increment');
        $this->storage->stats->expectOnce('create', Array('bar'));
        $this->storage->stats->expectOnce('retrieve', Array('bar'));
        $this->storage->stats->setReturnValue('retrieve', 'foo');
        $record = $this->storage->retrieve($this->ds, 'bar');
        $this->assertEqual('foo', $record);
        }

    function test_retrieve_found()
        {
        $this->storage->stats->expectOnce('exists', Array('bar'));
        $this->storage->stats->setReturnValue('exists', 1);
        $this->storage->stats->expectNever('create');
        $this->storage->stats->expectOnce('increment', Array('bar'));
        $this->storage->stats->expectOnce('retrieve', Array('bar'));
        $this->storage->stats->setReturnValue('retrieve', 'foo');
        $record = $this->storage->retrieve($this->ds, 'bar');
        $this->assertEqual('foo', $record);
        }
    
    function test_retrieve_top_viewed()
        {
        $this->storage->stats->expectOnce('retrieve_top_viewed', Array(0));
        $this->storage->stats->expectNever('retrieve');
        $result = $this->storage->retrieve($this->ds, '/stats/topviewed');
        }
    
    function test_retrieve_top_viewed_with_size()
        {
        $this->storage->stats->expectOnce('retrieve_top_viewed', Array(10));
        $this->storage->stats->expectNever('retrieve');
        $result = $this->storage->retrieve($this->ds, '/stats/topviewed/10');
        }
    
    function test_retrieve_top_queries()
        {
        $this->storage->querystats = new MockQueryStats();
        $this->storage->querystats->expectOnce('retrieve_top_queries', Array(10));
        $this->storage->stats->expectNever('retrieve');
        $result = $this->storage->retrieve($this->ds, '/stats/topqueries/10');
        }
    }

class RecordStatsTestCase
    extends UnitTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->stats = new RecordStats($MODULE->get_pear_db());
        }
        
    function test_create_exists_retrieve()
        {
        $this->assertFalse($this->stats->exists('r1'));
        $this->stats->create('r1');
        $this->assertTrue($this->stats->exists('r1'));
        $record = $this->stats->retrieve('r1');
        $this->assertEqual('r1', $record['url']);
        $this->assertEqual(1, $record['count']);
        }
        
    function test_increment()
        {
        $this->stats->create('r2');
        $this->stats->increment('r2');
        $record = $this->stats->retrieve('r2');
        $this->assertEqual(2, $record['count']);
        }

    function test_score()
        {
        // No votes
        $this->stats->create('r3');
        $record = $this->stats->retrieve('r3');
        $this->assertEqual(0, $record['score']);
        $this->assertEqual(0, $record['score_count']);
    
        // One vote
        $this->stats->score('r3', 'user', 0.5);
        $record = $this->stats->retrieve('r3');
        $this->assertEqual(0.5, $record['score']);
        $this->assertEqual(0.5, $record['score_avg']);
        $this->assertEqual(1, $record['score_count']);

        // Two votes
        $this->stats->score('r3', 'user2', -1);
        $record = $this->stats->retrieve('r3');
        $this->assertEqual(-0.5, $record['score']);
        $this->assertEqual(2, $record['score_count']);
        $this->assertEqual(-0.25, $record['score_avg']);

        // Multiple votes overwrite
        $this->stats->score('r3', 'user2', 0.5);
        $record = $this->stats->retrieve('r3');
        $this->assertEqual(1.0, $record['score']);
        $this->assertEqual(2, $record['score_count']);
        
        // User can vote on a different item
        $this->stats->create('r3a');
        $this->stats->score('r3a', 'user', 0.5);
        $record = $this->stats->retrieve('r3a');
        $this->assertEqual(0.5, $record['score']);
        $this->assertEqual(1, $record['score_count']);
        }        

    function test_score_blank_name_ignored()
        {
        $this->stats->create('r4');
        $this->stats->score('r4', '', 1);
        $record = $this->stats->retrieve('r4');
        $this->assertEqual(0, $record['score']);
        $this->assertEqual(0, $record['score_count']);
        }
    
    function test_retrieve_top_viewed()
        {
        // buffer the counts so that these records are higher than records in other tests
        $records = Array(
            'top1' => 3,
            'top2' => 5,
            'top3' => 4,
            );
        foreach ($records as $url=>$count)
            {
            $this->stats->create($url);
            for ($i = 1; $i <= $count; $i++)
                $this->stats->increment($url);
            }
        $result = $this->stats->retrieve_top_viewed(3);
        // confirm the order
        $this->assertEqual('top2', $result[0]['url']);
        $this->assertEqual('top3', $result[1]['url']);
        $this->assertEqual('top1', $result[2]['url']);
        }
    }
