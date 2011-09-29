<?php
// $Id$
// Test Sphinx interface
// James Fryer, 2011-03-10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class SphinxTestCase
    extends UnitTestCase
    {
    function test_new_sphinx()
        {
        $sphinx = new_sphinx();
        $this->assertEqual('DummySphinx', get_class($sphinx));
        }
    }
