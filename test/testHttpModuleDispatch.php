<?php
// $Id$
// Test Document Analyser
// James Fryer, 21 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class ModuleDispatchTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->url = $MODULE->url('module');
        }

    function test_404()
        {
        $this->get($this->url, 404);
        $this->get($this->url . 'notfound.php', 404);
        }
        
    function test_implicit_module()
        {
        $this->get($this->url . '/test.php');
        $this->assertTitle('Test for module dispatch');
        }
        
    function test_explicit_module()
        {
        $this->get($this->url . '/dummy/test.php');
        $this->assertTitle('Test for module dispatch');
        }
    }
