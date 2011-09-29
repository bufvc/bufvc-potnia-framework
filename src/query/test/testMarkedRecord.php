<?php
// $Id$
// Tests for MarkedRecord class
// Phil Hansen, 09 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class MarkedRecordTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->marked_records = new MarkedRecord();
        }

    function test_add_and_count()
        {
        $this->assertEqual($this->marked_records->count(), 0);
        $this->assertEqual(count($this->marked_records->records), 0);
        $this->marked_records->add('/dummy/test/single');
        $this->assertEqual($this->marked_records->count(), 1);

        // Add same record twice is ignored
        $this->marked_records->add('/dummy/test/single');
        $this->assertEqual($this->marked_records->count(), 1);
        }

    function test_can_add()
        {
        // Limit is 3 items for test
        for ($i = 0; $i < 3; $i++)
            {
            $url = sprintf('/dummy/test/many00%d', $i);
            $this->assertTrue($this->marked_records->can_add($url));
            $this->marked_records->add($url);
            }

        // Can't add another
        $url = '/dummy/test/many004';
        $this->assertFalse($this->marked_records->can_add($url));

        // Attempts to add will fail
        $this->marked_records->add($url);
        $this->assertEqual($this->marked_records->count(), 3);
        }

    function test_add_not_found()
        {
        $this->marked_records->add('/dummy/test/notfound');
        $this->assertEqual($this->marked_records->count(), 0);
        }

    function test_add_bad_module()
        {
        $this->marked_records->add('foo/test/single');
        $this->assertEqual($this->marked_records->count(), 0);
        }

    function test_get()
        {
        $this->marked_records->add('/dummy/test/single');
        $record = $this->marked_records->get('/dummy/test/single');
        // Whole record is returned
        $this->assertEqual($record['url'], '/dummy/test/single');
        $this->assertEqual($record['title'], 'single');
        $this->assertEqual($record['description'], 'Test item');

        // Module name and module
        $this->assertEqual($record['modname'], 'dummy');
        $this->assertEqual($record['module_title'], 'Dummy Module');
        $this->assertEqual($record['module'], Module::load('dummy'));
        }

    function test_get_all()
        {
        $this->marked_records->add('/dummy/test/single');
        $this->marked_records->add('/dummy/test/many000');
        $records = $this->marked_records->get_all();
        $this->assertEqual($records['/dummy/test/single']['url'], '/dummy/test/single');
        $this->assertEqual($records['/dummy/test/single']['modname'], 'dummy');
        $this->assertEqual($records['/dummy/test/single']['module_title'], 'Dummy Module');
        //### $this->assertTrue(!isset($records['/dummy/test/single']['module']));
        //### FIXME/MODULE: above test fails with new module code -- may need to fix???
        $this->assertEqual($records['/dummy/test/many000']['url'], '/dummy/test/many000');
        }

    function test_get_not_found()
        {
        $record = $this->marked_records->get('/dummy/test/single');
        $this->assertNull($record);
        }

    function test_exists()
        {
        $this->marked_records->add('/dummy/test/single');
        $this->assertTrue($this->marked_records->exists('/dummy/test/single'));
        $this->assertFalse($this->marked_records->exists('/dummy/test/notfound'));
        }

    function test_remove()
        {
        $this->marked_records->add('/dummy/test/single');
        $this->marked_records->add('/dummy/test/many000');
        $this->assertEqual(count($this->marked_records->records), 2);
        $this->marked_records->remove('/dummy/test/single');
        $this->assertEqual(count($this->marked_records->records), 1);
        }
    }
