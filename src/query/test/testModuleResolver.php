<?php
// $Id$
// Test DS wrapper to resolve module
// James Fryer, 15 Apr 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'query/ModuleResolver.class.php');

// Mocks
Mock::generate('DataSource');
Mock::generate('Module');

// Fake module loader, always returns the mock module
// Used to inject mock module into resolver
class FakeModuleLoader
    {
    function __construct(&$module_to_return)
        {
        $this->module = $module_to_return;
        }
    function load_module($name)
        {
        if ($this->module)
            $this->module->name = $name;
        return $this->module;
        }
    }

/// Test basic query functions
class ModuleResolverTestCase
    extends UnitTestCase
    {
    // Test with a real data source
    function test_end_to_end()
        {
        $module = new Module('foo');
        $record = $module->retrieve('/dummy/test/single');
        $this->assertEqual('/dummy/test/single', $record['url']);
        $this->assertEqual('single', $record['title']);
        $this->assertEqual('dummy', $record['module']->name);
        }
        
    function test_initial_slash_not_required()
        {
        $ds = new ModuleResolver();
        $record = $ds->retrieve('/dummy/test/single');
        $this->assertEqual('/dummy/test/single', $record['url']);
        $record = $ds->retrieve('dummy/test/single');
        $this->assertEqual('/dummy/test/single', $record['url']);
        }

    function setup()
        {
        $this->ds = new MockDataSource();
        $this->module = new MockModule();
        $this->loader = new FakeModuleLoader($this->module);
        $this->resolver = new ModuleResolver($this->loader);
        }

    function setup_crud($function, $args, $return=NULL)
        {
        if (is_null($return))
            $return = Array('url'=>'/x/y', '_table'=>'x');
        $this->module->expectOnce('get_datasource');
        $this->module->setReturnValue('get_datasource', $this->ds); 
        $this->ds->expectOnce($function, $args);
        $this->ds->setReturnValue($function, $return); 
        }
        
    function assertCrudReturn($module, $r, $expected=-1)
        {
        if ($expected === -1)
            $expected = Array('url'=>'/' . $this->module->name . '/x/y', '_table'=>'foo/x');
        $this->assertNoError();
        $this->assertEqual($module, $this->module->name);
        if (is_array($r))
            {
            $this->assertEqual($this->module->name, $r['module']->name);
            unset($r['module']);
            }   
        $this->assertEqual($expected, $r);
        }

    function test_search()
        {
        $results = Array('count'=>1, 'data'=>Array(Array('url'=>'/x/y', '_table'=>'x')));
        $expected = Array('count'=>1, 'data'=>Array(Array('url'=>'/foo/x/y', '_table'=>'foo/x')));
        $this->setup_crud('search', Array('/bar', 'query', 1, 2), $results);
        $r = $this->resolver->search('/foo/bar', 'query', 1, 2);
        $this->assertCrudReturn('foo', $r, $expected);
        }

    function test_search_with_module_in_results()
        {
        // Aggregate DS puts the module in the results, so this should override the calling module
        $module = new Module('mod');
        $results = Array('count'=>1, 'data'=>Array(Array('url'=>'/x/y', '_table'=>'x', 'module'=>$module)));
        $expected = Array('count'=>1, 'data'=>Array(Array('url'=>'/mod/x/y', '_table'=>'mod/x', 'module'=>$module)));
        $this->setup_crud('search', Array('/bar', 'query', 1, 2), $results);
        $r = $this->resolver->search('/foo/bar', 'query', 1, 2);
        $this->assertCrudReturn('foo', $r, $expected);
        }

    function test_retrieve()
        {
        $this->setup_crud('retrieve', Array('/bar', 'params'));
        $r = $this->resolver->retrieve('/foo/bar', 'params');
        $this->assertCrudReturn('foo', $r);
        }

    function test_create()
        {
        $this->setup_crud('create', Array('/bar', 'data'));
        $r = $this->resolver->create('/foo/bar', 'data');
        $this->assertCrudReturn('foo', $r);
        }

    function test_update()
        {
        $this->setup_crud('update', Array('/bar', 'data'));
        $r = $this->resolver->update('/foo/bar', 'data');
        $this->assertCrudReturn('foo', $r);
        }

    function test_delete()
        {
        $this->setup_crud('delete', Array('/bar'), 1);
        $r = $this->resolver->delete('/foo/bar');
        $this->assertCrudReturn('foo', $r, 1);
        }

    function test_module_not_found()
        {
        $tmp = NULL;
        $this->loader = new FakeModuleLoader($tmp);
        $this->resolver = new ModuleResolver($this->loader);
        $this->module->expectNever('get_datasource');
        $this->ds->expectNever('retrieve');
        $r = $this->resolver->retrieve('/foo/bar');
        $this->assertError(404);
        $this->assertNull($r);
        }

    function test_module_missing()
        {
        $this->ds->expectNever('retrieve');
        $r = $this->resolver->retrieve('/foo');
        $this->assertError(404);
        $this->assertNull($r);
        }

    function test_errors_propagated()
        {
        $this->module->setReturnValue('get_datasource', new DataSourceBase()); 
        $r = $this->resolver->retrieve('/foo/bar');
        // Error propagated from default
        $this->assertError(405);
        }
    
    function test_errors_cleared()
        {
        $this->setup_crud('retrieve', Array('/bar', 'params'));
        $this->resolver->error_code = 999;
        $this->resolver->error_message = 'msg';
        $r = $this->resolver->retrieve('/foo/bar', 'params');
        $this->assertCrudReturn('foo', $r);
        }
    
    function assertError($status)
        {
        $this->assertEqual($this->resolver->error_code, $status, 'error_code: %s');
        $this->assertTrue($this->resolver->error_message != '', 'missing error_message');
        }

    function assertNoError()
        {
        $this->assertTrue($this->resolver->error_code == 0, 'error_code: ' . $this->resolver->error_code);
        $this->assertTrue($this->resolver->error_message == '', 'error_message: ' . $this->resolver->error_message);
        }
	}
