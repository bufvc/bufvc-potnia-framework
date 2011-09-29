<?php
// $Id$
// Tests for Module class
// James Fryer, 7 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('User');
Mock::generate('DataSource');

/// Test basic query functions
class ModuleTestCase
    extends UnitTestCase
    {
    function test_module_is_a_datasource()
    	{
    	$module = new Module('foo');
        $this->assertTrue($module instanceof ProxyDataSource);
        }
        
    function test_get_datasource()
    	{
    	$module = new Module('foo', 'MockDataSource');
    	$ds = $module->get_datasource();
        $this->assertEqual(strtolower(get_class($ds)), 'mockdatasource');
        // Reference semantics
        $ds->foo = 'bar';
    	$ds2 = $module->get_datasource();
    	$this->assertEqual($ds2->foo, 'bar');
        }

    function test_new_datasource()
    	{
    	$module = new Module('foo', 'MockDataSource');
    	$ds = $module->new_datasource();
        $this->assertEqual(strtolower(get_class($ds)), 'mockdatasource');
        // No reference semantics
        $ds->foo = 'bar';
    	$ds2 = $module->get_datasource();
    	$this->assertNotEqual(@$ds2->foo, 'bar');
        }

    function test_get_pear_db()
        {
        //### FIXME: not much of a test
        $module = new DummyModule();
        $db = $module->get_pear_db();
        $this->assertNotNull($db);
        }

    function test_descriptive_fields()
        {
        $module = new DummyModule();
        $this->assertEqual($module->name, 'dummy');
        $this->assertEqual($module->title, 'Dummy Module');
        $this->assertEqual($module->subtitle, '');
        $this->assertEqual($module->icon, '');
        $this->assertNotEqual($module->description, '');
        $this->assertEqual($module->version, '1.0');
        // $this->assertEqual($module->path, 'Dummy Module');
        $this->assertNULL($module->user_right);
        }

    function test_path()
        {
        global $CONF;

        // Default module paths
        $module = new Module('foo');
        $this->assertEqual($module->name, 'foo');
        $mod_path = $CONF['path_modules'] . $module->name . '/';
        $this->assertEqual($module->path, $mod_path);

        // The dummy module path is a special case
        $module = new DummyModule();
        $this->assertEqual($module->path, $CONF['path_test']);
        }

    function test_table_from_url()
        {
        $this->assertNull(Module::table_from_url(''));
        $this->assertEqual('table', Module::table_from_url('table'));
        $this->assertEqual('table', Module::table_from_url('/table/y'));
        $this->assertEqual('table', Module::table_from_url('mod/table'));
        $this->assertEqual('table', Module::table_from_url('mod/table/'));
        $this->assertEqual('table', Module::table_from_url('mod/table/item'));
        }

    function test_has_right_default()
        {
        // The default is to return true
        $module = new DummyModule();
        $this->assertNull($module->user_right);
        $user =  new MockUser();
        $user->expectNever('has_right');
        $this->assertTrue($module->has_right($user));
        }

    function test_has_right()
        {
        // If the module has user_right then it is checked and the result returned
        $module = new DummyModule();
        $module->user_right = 'foo';
        $user = new MockUser();
        $user->expectOnce('has_right', Array('foo'));
        $user->setReturnValue('has_right', 'bar');
        $this->assertEqual($module->has_right($user), 'bar');
        }

    function test_can_edit_default()
        {
        // default is to return false
        $module = new Module('foo');
        $this->assertNull($module->edit_right);
        $user = new MockUser();
        $user->expectNever('has_right');
        $this->assertFalse($module->can_edit($user));
        }

    function test_can_edit()
        {
        // If the module has edit_right then the user must have the specified right for editting
        // the DummyModule defaults edit_right to 'edit_record' for tests
        $module = new DummyModule();
        $user = new MockUser();
        $user->expectOnce('has_right', Array('edit_record'));
        $user->setReturnValue('has_right', true);
        $this->assertTrue($module->can_edit($user));
        }

    function test_new_formatter()
        {
        $module = new Module('foo');
        // all valid formatters can be found and loaded
        $this->assertEqual(get_class($module->new_formatter('text')), 'TextFormatter');
        $this->assertEqual(get_class($module->new_formatter('xml')), 'DublinCoreFormatter');
        $this->assertEqual(get_class($module->new_formatter('bibtex')), 'BibTeXFormatter');
        $this->assertEqual(get_class($module->new_formatter('atom')), 'AtomFormatter');
        // returns null if no formatter found
        $this->assertTrue(is_null($module->new_formatter('junk')));
        // returns null if no formatter specified
        $this->assertTrue(is_null($module->new_formatter(NULL)));
        // check default export util calss
        $formatter = $module->new_formatter('text');
        $this->assertEqual(get_class($formatter->_util), 'ExportFormatterUtil');
        // specify a util class
        // use the mock DS simply for testing purposes
        $module->export_util_class = "MockDataSource";
        $formatter = $module->new_formatter('text');
        $this->assertEqual(get_class($formatter->_util), 'MockDataSource');
        }
    
    function test_format_records()
        {
        $records = Array(
            Array('url'=>'/dummy/test/many001'),
            Array('url'=>'/dummy/test/many002'),
            Array('url'=>'/dummy/test/single'),
            );
        $module = new DummyModule();
        // returns null if no formatter found
        $this->assertTrue(is_null($module->format_records(Array(), 'junk', 1)));
        // returns null if no formatter specified
        $this->assertTrue(is_null($module->format_records(Array(), NULL, 1)));
        // check formats
        // xml
        $result = $module->format_records($records, 'xml', 3);
        $this->assertTrue(preg_match('/\bsingle\b/', $result) == 1);
        $matches = Array();
        preg_match_all('/<record>/', $result, $matches);
        $this->assertEqual(count($matches[0]), 3);
        // text
        $result = $module->format_records($records, 'text', 3);
        $this->assertTrue(preg_match('/\bsingle\b/', $result) == 1);
        preg_match_all('/Title:/', $result, $matches);
        $this->assertEqual(count($matches[0]), 3);
        // bibtex
        $result = $module->format_records($records, 'bibtex', 3);
        $this->assertTrue(preg_match('/\bsingle\b/', $result) == 1);
        preg_match_all('/@Misc/', $result, $matches);
        $this->assertEqual(count($matches[0]), 3);
        // check limit
        $result = $module->format_records($records, 'text', 2);
        $this->assertFalse(preg_match('/\bsingle\b/', $result) == 1);
        preg_match_all('/Title:/', $result, $matches);
        $this->assertEqual(count($matches[0]), 2);
        // check default record separator using text formatter
        $result = $module->format_records($records, 'text', 3);
        preg_match_all('/----/', $result, $matches);
        $this->assertEqual(count($matches[0]), 2);
        // check custom record separator
        $result = $module->format_records($records, 'text', 3, '@@@@');
        $this->assertFalse(preg_match('/----/', $result) == 1);
        preg_match_all('/@@@@/', $result, $matches);
        $this->assertEqual(count($matches[0]), 2);
        // check header/footer using xml formatter
        $result = $module->format_records($records, 'xml', 3);
        $this->assertTrue(preg_match('/xml version/', $result) == 1);
        $this->assertTrue(preg_match('/<\/results>/', $result) == 1);
        }

    function test_content_type()
        {
        $module = new DummyModule();

        // Default is HTML
        $this->assertEqual($module->content_type(), 'text/html; charset=' . $module->charset);
        $this->assertEqual($module->content_type('html'), 'text/html; charset=' . $module->charset);
        $this->assertEqual($module->content_type('foobar'), 'text/html; charset=' . $module->charset);

        // Other types
        $this->assertEqual($module->content_type('xspf'), 'application/xspf+xml');
        $this->assertEqual($module->content_type('xml'), 'application/xml');
        $this->assertEqual($module->content_type('text'), 'text/plain; charset=' . $module->charset);
        $this->assertEqual($module->content_type('bibtex'), 'text/plain');
        $this->assertEqual($module->content_type('atom'), 'application/atom+xml');
        
        // mapped type
        $this->assertEqual($module->content_type('citation'), 'text/plain; charset=' . $module->charset);
        }

    function test_url()
        {
        global $CONF;
        $module = new DummyModule();

        // Default URL from config
        $this->assertEqual($module->url(), $CONF['url']);

        // Supplying a page name adds '.php'
        $this->assertEqual($module->url('index'), $CONF['url'] . '/index.php');

        // You can also add query string or path info
        $this->assertEqual($module->url('search', '?q=x'), $CONF['url'] . '/search.php?q=x');

    	// The query is not escaped
    	$this->assertEqual($module->url('', '?&/ '), $CONF['url'] . '?&/ ');

    	// Module name is stripped out
    	$this->assertEqual($module->url('index', '/dummy/foo/bar'), $CONF['url'] . '/index.php/foo/bar');
    	$this->assertEqual($module->url('index', 'dummy/foo/bar'), $CONF['url'] . '/index.php/foo/bar');
        
        // '.php' extension is configurable
        $CONF['php_file_ext'] = 'FOO';
        $this->assertEqual($module->url('index'), $CONF['url'] . '/indexFOO');
        $CONF['php_file_ext'] = '.php';
        }

    function test_url_with_module_path()
        {
        global $CONF;
        // Test behaviour when the add module flag is set
        $module = new DummyModule();
        $module->add_module_to_url = 1;

        // Default URL from config
        $this->assertEqual($module->url(), $CONF['url'] . '/dummy');

        // Supplying a page name adds '.php'
        $this->assertEqual($module->url('index'), $CONF['url'] . '/dummy/index.php');

    	// Module name is stripped out
    	$this->assertEqual($module->url('index', '/dummy/foo/bar'), $CONF['url'] . '/dummy/index.php/foo/bar');
    	$this->assertEqual($module->url('index', 'dummy/foo/bar'), $CONF['url'] . '/dummy/index.php/foo/bar');
        }

    function test_url_with_alias()
        {
        global $CONF;
        $CONF['module_aliases'] = Array(
            '/qu/ux' => 'foo',
            );
        $module = new DummyModule();
        $module->name = 'foo';
        $this->assertEqual($module->url(), $CONF['url'] . '/qu/ux');
        $this->assertEqual($module->url('bar'), $CONF['url'] . '/qu/ux/bar.php');
        }

    function test_url_multi_module()
        {
        global $CONF;
        $module = new DummyModule();
        $module->add_module_to_url = 1;
        // Normal URL has module name added 
        $this->assertEqual($module->url(), $CONF['url'] . '/dummy');
        //### TODO: aliases
        // Module name is stripped out
        $this->assertEqual($module->url('index', 'dummy/quux'), $CONF['url'] . '/dummy/index.php/quux');
        $this->assertEqual($module->url('search', 'dummy/quux'), $CONF['url'] . '/dummy/search.php/quux');        
        $this->assertEqual($module->url('search', 'dummy/quux/123'), $CONF['url'] . '/dummy/search.php/quux/123');        
        // Other queries ignored
        $this->assertEqual($module->url('search', '?a=b'), $CONF['url'] . '/dummy/search.php?a=b');        
        $this->assertEqual($module->url('search', '/some/page'), $CONF['url'] . '/dummy/search.php/some/page');        
        }

    function test_url_multi_module_alias()
        {
        global $CONF;
        $CONF['module_aliases'] = Array(
            '/foo' => 'dummy',
            );
        $module = new DummyModule();
        $module->add_module_to_url = 1;
        $this->assertEqual($module->url('index', 'dummy/quux/123'), $CONF['url'] . '/foo/index.php/quux/123');
        }

    function test_url_record()
        {
        $module = new DummyModule();
        $record = Array('url' => '/foo');
        $this->assertEqual($module->url('index', '/foo'), $module->url_record($record));
        }

    function test_url_edit()
        {
        $module = new DummyModule();
        $record = Array('url' => '/foo/bar');
        $this->assertEqual($module->url_edit($record), $module->url('edit', $record['url']));
        }

    function test_url_login()
        {
        global $CONF;
        $module = new DummyModule();
        $this->assertEqual($module->url_login(), $CONF['url_login'] . '?url=' . urlencode($module->url()));
        $this->assertEqual($module->url_login('foo'), $CONF['url_login'] . '?url=' . urlencode($module->url('foo')));
        $this->assertEqual($module->url_login('foo', '/bar'), $CONF['url_login'] . '?url=' . urlencode($module->url('foo', '/bar')));
        }

    // Requires demo module -- remove this test if you delete the demo
    function test_load()
        {
        global $CONF;
        // Unknown module will return NULL
        $module = Module::load('no-such-module');
        $this->assertNull($module);

        // Only load allowed modules
        $CONF['allowed_modules'] = Array();
        $module = Module::load('demo');
        $this->assertNull($module);

        // Allow the demo module
        $CONF['allowed_modules'] = Array('demo', 'no-such-module');
        $module = Module::load('demo');
        $this->assertNotNull($module);

        // The class is loaded and the correct class returned
        $this->assertTrue(class_exists('demomodule'));
        $this->assertEqual(strtolower(get_class($module)), 'demomodule');
        
        // The global template utilities are loaded
        $this->assertTrue(class_exists('recordsummary'));
        
        // The module-specific template utils are loaded
        // (this const is defined in test/templates/inc-util.php)
        $this->assertTrue(DUMMY_TEMPLATE_UTILS_LOADED);

        // Object is cached
        $module->foo = 'bar';
        $module2 = Module::load('demo');
        $this->assertEqual($module2->foo, 'bar');

        // Non-existent module returns NULL without errors
        $module = Module::load('no-such-module');
        $this->assertNull($module);

        // Reset config
        $CONF['allowed_modules'] = Array();

        // Dummy module can always be loaded
        $module = Module::load('dummy');
        $this->assertEqual(strtolower(get_class($module)), 'dummymodule');

        // Reference semantics
        $module->jan = 'fu';
        $module2 = Module::load('dummy');
        $this->assertEqual($module->jan, $module2->jan);
        }

    // Requires demo module -- remove this test if you delete the demo
    function test_flush()
        {
        global $CONF;

        // Load the demo module
        $CONF['allowed_modules'] = Array('demo');
        $module = Module::load('demo');

        // To show that a new object is returned after a flush
        $module->foo = 'bar';

        // Flush
        Module::flush();

        // Refetch the module
        $module = Module::load('demo');
        $this->assertFalse(isset($module->foo));

        // Reset config
        $CONF['allowed_modules'] = Array();
        }

    function test_name_from_url()
        {
        $name = Module::name_from_url('');
        $this->assertNull($name);
        $name = Module::name_from_url('/foo/bar');
        $this->assertEqual('foo', $name);
        $name = Module::name_from_url('/foo/bar', '/foo');
        $this->assertEqual('bar', $name);
        //### TODO: aliases        
        }
        
    function test_expand_alias()
        {
        global $CONF;
        $CONF['module_aliases'] = Array(
            '/qu/ux' => 'foo',
            '/x' => 'y',
            );
        $this->assertEqual('/a/b/c', Module::expand_alias('/a/b/c'));
        // Simple expansion
        $this->assertEqual('/a/y/b', Module::expand_alias('/a/x/b'));
        // Slashes in alias
        $this->assertEqual('/a/foo/b', Module::expand_alias('/a/qu/ux/b'));
        // With URL prefix
        $this->assertEqual('/a/y/b', Module::expand_alias('/a/x/b', '/a'));
        $this->assertEqual('/c/x/b', Module::expand_alias('/c/x/b', '/a'));
        $this->assertEqual('/aa/x/b', Module::expand_alias('/aa/x/b', '/a'));
        }

    function test_session_data()
        {
        $module = new DummyModule();
        // Check if session array is altered
        $old_count = count($_SESSION);
        $this->assertNull($module->get_session_data('foo'));
        $module->set_session_data('foo', 'bar');
        $this->assertEqual($module->get_session_data('foo'), 'bar');
        $this->assertEqual(count($_SESSION), $old_count + 1);

        // Session data is module-specific
        $module2 = new Module('different');
        $this->assertNull($module2->get_session_data('foo'));
        $module2->set_session_data('foo', 'notbar');
        $this->assertEqual($module2->get_session_data('foo'), 'notbar');
        $module2->set_session_data('foo');

        // Can remove from session
        $module->set_session_data('foo');
        $this->assertNull($module->get_session_data('foo'));
        $this->assertEqual(count($_SESSION), $old_count);
        }

    function test_find_template()
        {
        global $CONF;
        $module = new DummyModule();

        // Where to look for templates
        $path_default = $CONF['path_templates'];
        $path_specific = $CONF['path_test'] . 'templates/';

        // Not found
        $this->assertNull($module->find_template('notfound'));

        // Default path
        $this->assertEqual($module->find_template('inc-header'), $path_default . 'inc-header.php');

        // Specific path
        $this->assertEqual($module->find_template('info_default'), $path_specific . 'info_default.php');

        // Known content type
        $this->assertEqual($module->find_template('record', 'xspf'), $path_default . 'record.xspf.php');

        // HTML/unknown content type
        $this->assertEqual($module->find_template('record', 'html'), $path_default . 'record.php');
        $this->assertEqual($module->find_template('record', 'foobar'), $path_default . 'record.php');
        }

    function test_get_template()
        {
        global $CONF;
        $module = new DummyModule();

        // Not found
        $this->assertNull($module->get_template('notfound'));

        // Test template (found in module dir)
        $result = $module->get_template('test_for_get_template');
        $this->assertNotNull($result);
        $this->assertTrue(ereg('TEST', $result));

        // With variables
        $rand = rand();
        $result = $module->get_template('test_for_get_template', Array('TEST'=>"$rand"));
        $this->assertTrue(ereg("$rand", $result));
        }

    function test_menu()
        {
        // Default menu items for no/unprivileged user
        // Numeric entries represent spacer lines
        $expected = Array('home', 'search', 'marked', 'history', 'about');
        $module = new DummyModule();
        $menu = $module->menu();
        $this->assertTrue(is_array($menu));
        $this->assertIdentical($this->munge_menu($menu), $expected);
        $this->assertEqual($menu['home'], Array('title'=>$module->title, 'url'=>$module->url() . '/'));
        $this->assertEqual($menu[0], Array('type'=>'line'));

        // Normal user gets no extra options
        $user = new MockUser();
        $menu = $module->menu($user);
        $this->assertIdentical($this->munge_menu($menu), $expected);

        // If user has 'save_data' right then some extra options
        $expected = Array('home', 'search', 'marked', 'history', 'saved', 'prefs', 'about');
        $user = new MockUser();
        $user->setReturnValue('has_right', true, Array('save_data'));
        $menu = $module->menu($user);
        $this->assertIdentical($this->munge_menu($menu), $expected);

        // If save_search_enabled var is false then we lose the saved search option
        $module->saved_search_enabled = FALSE;
        $expected = Array('home', 'search', 'marked', 'history', 'prefs', 'about');
        $user = new MockUser();
        $user->setReturnValue('has_right', true, Array('save_data'));
        $menu = $module->menu($user);
        $this->assertIdentical($this->munge_menu($menu), $expected);
        }

    // Numeric menu keys entries represent spacer lines, remove them
    function munge_menu($menu)
        {
        return array_values(array_filter(array_keys($menu), 'is_string'));
        }
        
    function test_menu_search()
        {
        $expected = Array('search_test', 'search_test2');
        $module = new DummyModule();
        $menu = $module->menu_search();
        $this->assertTrue(is_array($menu));
        $this->assertIdentical($this->munge_menu($menu), $expected);
        $this->assertEqual('Search Test', $menu['search_test']['title']);
        $this->assertEqual($module->url('search', '?editquery=1'), $menu['search_test']['url']);
        }
        
    function test_menu_search_current()
        {
        $module = new DummyModule();
        $query = QueryFactory::create($module);
        $query->table_name = 'test';
        $menu = $module->menu_search($query);
        $this->assertTrue($menu['search_test']['current']);
        $this->assertFalse($menu['search_test2']['current']);
        $query->table_name = 'test2';
        $menu = $module->menu_search($query);
        $this->assertFalse($menu['search_test']['current']);
        $this->assertTrue($menu['search_test2']['current']);
        }
        
    function test_list_query_tables()
        {
        $module = new DummyModule();
        $expected = Array('test'=>'Test', 'test2'=>'Test 2'); // , 'listings'=>'');
        $this->assertEqual($expected, $module->list_query_tables());
        }

    function test_auto_alert_enabled_implies_saved_search_enabled()
        {
        // The dummy module only sets auto_alert_enabled, but the default
        // ctor will automatically set the saved_search_enabled flag also
        $module = new DummyModule();
        $this->assertTrue($module->saved_search_enabled);
        $this->assertTrue($module->auto_alert_enabled);
        }
    
    function test_get_marked_url()
        {
        $module = new DummyModule();
        $record = Array('url'=>'/test/single');
        $this->assertEqual($module->get_marked_url($record), '/test/single');
        }
        
    function test_include_javascript()
        {
        /// TODO AV : find appropriate way to test this, if such a way exists
        }

    function test_new_record_summary()
        {
        $module = new Module('foo');
        $summary = $module->new_record_summary();
        $this->assertEqual('recordsummary', strtolower(get_class($summary)));
        $summary = $module->new_record_summary('quux');
        $this->assertEqual('recordsummary', strtolower(get_class($summary)));
        $summary = $module->new_record_summary('bar');
        $this->assertEqual('foobarrecordsummary', strtolower(get_class($summary)));
        $summary = $module->new_record_summary('foo/bar');
        $this->assertEqual('foobarrecordsummary', strtolower(get_class($summary)));
        $summary = $module->new_record_summary('/foo/bar');
        $this->assertEqual('foobarrecordsummary', strtolower(get_class($summary)));
        $summary = $module->new_record_summary('/bar');
        $this->assertEqual('foobarrecordsummary', strtolower(get_class($summary)));
        }
    }

// This class name matches module 'foo', table 'bar'
class FooBarRecordSummary
    {
    }
