<?php
// $Id$
// Tests for DataSource proxy
// James Fryer, 24 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('DataSource');

class ProxyDataSourceTestCase
    extends UnitTestCase
    {
    function test_proxy()
        {
        $this->ds = new MockDataSource();
        $proxy = new ProxyDataSource($this->ds);

        $this->ds->expectOnce('search', Array(1, 2, 3, 4));
        $this->ds->setReturnValue('search', 101);
        $this->assertEqual($proxy->search(1, 2, 3, 4), 101);

        $this->ds->expectOnce('create', Array(1, 2));
        $this->ds->setReturnValue('create', 102);
        $this->assertEqual($proxy->create(1, 2), 102);

        $this->ds->expectOnce('retrieve', Array(1, 2));
        $this->ds->setReturnValue('retrieve', 103);
        $this->assertEqual($proxy->retrieve(1, 2), 103);

        $this->ds->expectOnce('update', Array(1, 2));
        $this->ds->setReturnValue('update', 104);
        $this->assertEqual($proxy->update(1, 2), 104);

        $this->ds->expectOnce('delete', Array(1));
        $this->ds->setReturnValue('delete', 105);
        $this->assertEqual($proxy->delete(1), 105);
        }
        
    function test_errors_propagated()
        {
        $this->ds = new MockDataSource();
        $this->ds->error_code = 999;
        $this->ds->error_message = 'msg';

        $proxy = new ProxyDataSource($this->ds);
        $proxy->search(1, 2, 3, 4);
        $this->assertEqual($proxy->error_code, 999);
        $this->assertEqual($proxy->error_message, 'msg');

        $proxy = new ProxyDataSource($this->ds);
        $proxy->create(1, 2);
        $this->assertEqual($proxy->error_code, 999);
        $this->assertEqual($proxy->error_message, 'msg');

        $proxy = new ProxyDataSource($this->ds);
        $proxy->retrieve(1, 2);
        $this->assertEqual($proxy->error_code, 999);
        $this->assertEqual($proxy->error_message, 'msg');

        $proxy = new ProxyDataSource($this->ds);
        $proxy->update(1, 2);
        $this->assertEqual($proxy->error_code, 999);
        $this->assertEqual($proxy->error_message, 'msg');

        $proxy = new ProxyDataSource($this->ds);
        $proxy->delete(1);
        $this->assertEqual($proxy->error_code, 999);
        $this->assertEqual($proxy->error_message, 'msg');
        }

    function test_get_datasource()
        {
        $this->ds = new MockDataSource();
        $proxy = new ProxyDataSource($this->ds);
        $this->ds->foo = 'bar'; // Reference semantics
        $this->assertIdentical($proxy->get_datasource(), $this->ds);
        }
    }
