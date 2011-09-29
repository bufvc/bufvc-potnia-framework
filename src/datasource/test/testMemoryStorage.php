<?php
// $Id$
// Tests for DataSource class
// James Fryer, 11 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

// Test the default memory storage handler
class MemoryStorageTestCase
    extends UnitTestCase
    {
    function setup()
        {
        // Config is not needed by this storage but data array is
        $this->ds = new DataSource(Array());
        $this->storage = new DataSource_MemoryStorage();
        $this->parser = new SimpleQueryParser();
        }

    function test_crud()
        {
        // Test data
        $url = '/test/path';
        $input = Array(
            'title' => "A 'test' item",
            'description' => "Test 'description'",
            );

        // We expect the URL to be returned as part of the data
        // Why? So if create changes it, we know that it has been changed.
        $expected_record = $input;
        $expected_record['url'] = $url;

        // Create
        $new_url = $this->storage->create($this->ds, $url, $input);
        $this->assertEqual($new_url, $url);
        //### TODO: create should make id unique

        // Retrieve
        $record = $this->storage->retrieve($this->ds, $url);
        $this->assertEqual($record, $expected_record);

        // Update
        $expected_record['title'] = "A 'test fnord'";
        $this->assertTrue($this->storage->update($this->ds, $url, $expected_record));

        // Retrieve updated
        $record = $this->storage->retrieve($this->ds, $url);
        $this->assertEqual($record, $expected_record);

        // Delete
        $this->assertTrue($this->storage->delete($this->ds, $url));

        // Can't retrieve deleted record
        $this->assertFalse($this->storage->retrieve($this->ds, $url));

        // Deleting twice returns NULL
        $this->assertFalse($this->storage->delete($this->ds, $url));
        }

    function test_bang_random()
        {
        $record = $this->storage->retrieve($this->ds, '/test/!random');
        $this->assertNotNull($record);
        }

    function test_immutable_url()
        {
        $url = '/test/path';
        $input = Array(
            'title' => "title",
            );

        // Create a record, change the URL and update it
        $url = $this->storage->create($this->ds, $url, $input);
        $record = $this->storage->retrieve($this->ds, $url);
        $record['url'] = '/test/foo';
        $record = $this->storage->update($this->ds, $url, $record);
        $record = $this->storage->retrieve($this->ds, $url);

        // The URL has not changed
        $this->assertEqual($record['url'], $url);
        }

    function test_create_dupe_url()
        {
        $url = '/test/path';
        $input = Array(
            'title' => "title",
            );

        // Create
        $url1 = $this->storage->create($this->ds, $url, $input);
        $this->assertEqual($url1, $url);

        // Request same URL -- get a unique one
        $url2 = $this->storage->create($this->ds, $url, $input);
        $this->assertNotEqual($url2, $url);
        }

    // Add some test records to search for
    function add_search_records($count)
        {
        for ($i = 0; $i < $count; $i++)
            {
            $n = $i+1;
            $url = '/test/path' . $n;
            $record = Array(
                'title' => 'title' . $n,
                'description' => 'descr' . $n,
                );
            $this->storage->create($this->ds, $url, $record);
            }
        }

    function test_search_all()
        {
        $this->add_search_records(2);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 1000);
        $this->assertResults($results, 0, 2, 2);
        $this->assertEqual($results['data'][0]['title'], 'title1');
        $this->assertEqual($results['data'][1]['title'], 'title2');
        }

    function test_search_single()
        {
        $this->add_search_records(2);
        // Title
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse('title1'), 0, 1000);
        $this->assertResults($results, 0, 1, 1);
        $this->assertEqual($results['data'][0]['title'], 'title1');
        $this->assertEqual($results['data'][0]['summary'], 'descr1');
        // Description
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse('descr2'), 0, 1000);
        $this->assertResults($results, 0, 1, 1);
        $this->assertEqual($results['data'][0]['title'], 'title2');
        $this->assertEqual($results['data'][0]['summary'], 'descr2');
        }

    function test_search_none()
        {
        $this->add_search_records(2);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse('foobar'), 0, 1000);
        $this->assertResults($results, 0, 0, 0);
        }

    function test_search_first_page()
        {
        $this->add_search_records(4);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 2);
        $this->assertResults($results, 0, 2, 4);
        $this->assertEqual($results['data'][0]['title'], 'title1');
        }

    function test_search_middle_page()
        {
        $this->add_search_records(4);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 1, 2);
        $this->assertResults($results, 1, 2, 4);
        $this->assertEqual($results['data'][0]['title'], 'title2');
        }

    function test_search_last_page()
        {
        $this->add_search_records(4);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 2, 100);
        $this->assertResults($results, 2, 2, 4);
        $this->assertEqual($results['data'][0]['title'], 'title3');
        }

    // Offset/count set to 0 returns result count only
    function test_search_count_only()
        {
        $this->add_search_records(2);
        $results = $this->storage->search($this->ds, '/test', $this->parser->parse(''), 0, 0);
        $this->assertResults($results, 0, 0, 2);
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
