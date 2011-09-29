<?php
// $Id$
// Test multi-module over HTTP
// James Fryer, 1 July 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');


/// Test for multiple modules
class MultiModuleTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->search_url = $MODULE->url('search');
        //### $this->record_url = $MODULE->url('index');
        }
        
    function test_dummy2_module_exists()
        {
        $module = Module::load('dummy2');
        $this->assertFalse(is_null($module));
        $this->assertEqual('dummy2', $module->name);
        }
        

    function test_debug_module_on_query()
        {
        // By default uses dummy module
        global $MODULE;
        $this->get($this->search_url);
        $this->assertPage('Basic Search', 200, $MODULE);
        
        // Pass dummy2 module as arg
        $module = Module::load('dummy2');
        $this->get($this->search_url . '?debug_module=dummy2');
        $this->assertPage('Basic Search', 200, $module);
        //$this->showsource();//###
/*        
        $this->assertPage('Basic Search');
        $this->assertHeader('content-type', 'text/html; charset='.$MODULE->charset);
        $this->assertTag('form', NULL, 'method', 'GET');
        $this->assertField('q', '');
        $this->assertField('page_size', '10');
        $this->assertField('sort', '');
        $this->assertFieldById('submit', 'Search');

        // Results are not present
        $this->assertNoTag('ul', '', 'class', 'results');
*/        
        }
    }
