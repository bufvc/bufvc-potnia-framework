<?php
// $Id$
// Test case base class for DataSource
// James Fryer, 6 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Base class for testing DataSource objects
class BaseDataSourceTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = $this->new_datasource();
        }

    /// Return a datasource object of the type/configuration required for testing
    /// Called by setup()
    function new_datasource()
        {
        return NULL;
        }

    function assertResults($r, $offset, $count, $total, $accuracy='exact')
        {
        $this->assertEqual($r['count'], $count, 'count: %s');
        $this->assertEqual(count($r['data']), $r['count'], 'inconsistent count: %s');
        $this->assertEqual($r['offset'],  $offset, 'offset: %s');
        $this->assertEqual($r['total'], $total, 'total: %s');
        $this->assertEqual($r['accuracy'], $accuracy, 'accuracy: %s');
        }

    function assertError($status)
        {
        $this->assertEqual($this->ds->error_code, $status, 'error_code: %s');
        $this->assertTrue($this->ds->error_message != '', 'missing error_message');
        }

    function assertNoError()
        {
        $this->assertTrue($this->ds->error_code == 0, 'error_code: ' . $this->ds->error_code);
        $this->assertTrue($this->ds->error_message == '', 'error_message: ' . $this->ds->error_message);
        }
    }

?>
