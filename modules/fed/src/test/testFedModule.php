<?php
// $Id$
// Tests for Federated Search module
// James Fryer, 28 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../../web/include.php');

Mock::generate('Query');
Mock::generate('Module');

// In order to get the fed module working, change allowed modules and reload
$CONF['allowed_modules'] = Array('fed', 'demo', 'ilrsouth', 'ilrsharing', 'lbc', 'shk', 'mig', 'hermes', 'user', 
    'trilt', 'tvtip', 'thisweek','calais','bund');
Module::flush();
$MODULE = Module::load('fed');

class FedModuleTestCase
    extends UnitTestCase
    {
    function setup()
        {
        global $MODULE;
        $this->query = QueryFactory::create($MODULE);
        }
        
    function test_cache_compare()
        {
        $module = new DummyModule();
        $module->name = 'module1';
        $module2 = new DummyModule();
        $module2->name = 'module2';
        $this->query->_cache->results = Array(
            'data'=>Array(
                0=>Array('url'=>'/segment/123', 'module'=>$module),
                1=>Array('url'=>'/segment/123', 'module'=>$module2),
                ),
            );
        $record = Array('url'=>'/segment/123', 'module'=>'module2');
        $record2 = Array('url'=>'/segment/456', 'module'=>'module1');
        $this->assertFalse($this->query->_cache->compare_record(0, $record));
        $this->assertTrue($this->query->_cache->compare_record(1, $record));
        $this->assertFalse($this->query->_cache->compare_record(0, $record2));
        }
        
    function test_to_string()
        {
        $this->query->search(Array('q'=>''));
        $this->assertTrue($this->query->criteria_string() != '');
        }
    }

class SearchInModuleBlockTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->mod1 = new MockModule();
        $this->mod1->title = 'mod1';
        $this->mod2 = new MockModule();
        $this->mod2->title = 'mod2';
        $this->query = new MockQuery();
        $this->query->info['components'] = Array(
            Array(
                'module'=>$this->mod1,
                'total'=>123,
                ),
            Array(
                'module'=>$this->mod2,
                'total'=>1,
                ),
            );
        }
        
    function test_components()
        {
        $this->mod1->expectOnce('url', Array('search', '?q='));
        $this->mod1->setReturnValue('url', 'url1');
        $this->mod2->expectOnce('url', Array('search', '?q='));
        $this->mod2->setReturnValue('url', 'url2');
        $this->query->setReturnValue('url_query', 'q=');

        $block = new SearchInModuleBlock($this->query, FALSE);
        $this->assertFalse($block->hidden);
        $this->assertEqual('Collections', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $expected_items = Array(
            Array('label'=>'mod1', 'value'=>'123', 'url'=>'url1'),
            Array('label'=>'mod2', 'value'=>'1', 'url'=>'url2'),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    function test_components_use_fed_search()
        {
        $this->mod1->setReturnValue('url', 'url1');
        $this->mod2->setReturnValue('url', 'url2');
        $this->query->setReturnValueAt(0, 'url_query', 'q=&components=0');
        $this->query->setReturnValueAt(1, 'url_query', 'q=&components=1');

        $block = new SearchInModuleBlock($this->query, TRUE);
        // ### TODO: the urls are dependent on the fed module, is there a way to mock the fed module for these tests?
        $fed_module = Module::load('fed');
        $expected_items = Array(
            Array('label'=>'mod1', 'value'=>'123', 'url'=>$fed_module->url('search', '?q=&components=0')),
            Array('label'=>'mod2', 'value'=>'1', 'url'=>$fed_module->url('search', '?q=&components=1')),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    function test_components_with_restricted()
        {
        $this->mod1->expectOnce('url', Array('search', '?q='));
        $this->mod1->setReturnValue('url', 'url1');
        // url function not called
        $this->mod2->expectNever('url');
        $this->query->setReturnValue('url_query', 'q=');
        // set restricted flag for mod2
        $this->query->info['components'][1]['restricted'] = TRUE;

        $block = new SearchInModuleBlock($this->query, FALSE);
        $this->assertFalse($block->hidden);
        $this->assertEqual('Collections', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $expected_items = Array(
            Array('label'=>'mod1', 'value'=>'123', 'url'=>'url1'),
            Array('label'=>'mod2', 'value'=>'1', 'url'=>''),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    // This test is dependent on the current user being 'guest'
    // i.e. does not have right trilt_user
    function test_add_extra_text()
        {
        $this->mod1->title = 'trilt';

        $block = new SearchInModuleBlock($this->query, FALSE);
        $result = $block->render();
        // the locked icon has been added one time (for trilt)
        $this->assertEqual(preg_match('/tip-lock/', $result), 1);
        }
    }
    
class AllBufvcFilterTestCase
    extends UnitTestCase
    {
    //### FIXME: rewrite_query should be moved elsewhere e.g. query utils???
    function setup()
        {
        $this->filter = new AllBufvcFilter();
        }
        
    function test_rewrite_query_basic()
        {
        // Basic
        $this->assertNull($this->filter->rewrite_query(Array()));
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query(Array('q'=>'foo')));
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query(Array('quxx'=>'foo')));
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query(Array('q'=>'foo', 'quux'=>'bar')));
        // 'q' is preferred
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query(Array('quux'=>'bar', 'q'=>'foo')));
        }
        
    function test_rewrite_query_dates_preserved()
        {
        // Dates preserved
        $criteria = Array('quxx'=>'foo', 'date_start'=>1, 'date_end'=>2, 'date'=>3);
        $expected = Array('q'=>'foo', 'date_start'=>1, 'date_end'=>2, 'date'=>3);
        $this->assertEqual($expected, $this->filter->rewrite_query($criteria));
        $criteria = Array('date_start'=>1);
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('q'=>'', 'date_start'=>1);
        $this->assertNull($this->filter->rewrite_query($criteria));
        }
        
    function test_rewrite_query_numerics_ignored()
        {
        $criteria = Array('q'=>'123');
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('quxx'=>'123');
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('q'=>'123', 'quxx'=>'123');
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('q'=>'123', 'quxx'=>'foo');
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query($criteria));
        }
        
    function test_rewrite_query_bools_ignored()
        {
        $criteria = Array('q'=>TRUE);
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('q'=>'', 'quux'=>TRUE);
        $this->assertNull($this->filter->rewrite_query($criteria));
        $criteria = Array('quux'=>TRUE);
        $this->assertNull($this->filter->rewrite_query($criteria));
        }

    function test_rewrite_query_list_lookup()
        {
        $query = new MockQuery();
        $query->expectOnce('get_list', Array('quux'));
        $query->setReturnValue('get_list', Array(123=>'foo'));
        $criteria = Array('quux'=>'123');
        $this->assertEqual(Array('q'=>'foo'), $this->filter->rewrite_query($criteria, $query));
        }
    }
    
class UserLoginReminderFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        global $USER, $MESSAGE, $_SESSION;
        $this->filter = new UserLoginReminderFilter();
        // load default user
        $USER = USER::instance();
        // clear the session for each test
        $_SESSION = Array();
        // apparently this gets set from another test, clear it here to be certain
        $MESSAGE = '';
        }
    
    function test_message_set()
        {
        global $USER, $MESSAGE, $MESSAGE_CLASS;
        $USER->login = 'guest';
        $this->filter->before_search(NULL, NULL);
        // the message and session flag have been set
        $this->assertTrue(preg_match('/log in/', $MESSAGE));
        $this->assertEqual($MESSAGE_CLASS, 'info-message');
        $this->assertEqual($_SESSION['FED_LOGIN_REMINDER_SHOWN'], TRUE);
        // the message is only set once and will not be set again this session
        $MESSAGE = '';
        $this->filter->before_search(NULL, NULL);
        $this->assertEqual($MESSAGE, '');
        $this->assertEqual($_SESSION['FED_LOGIN_REMINDER_SHOWN'], TRUE);
        }
    
    function test_message_not_shown_for_logged_in_users()
        {
        global $MESSAGE;
        $this->assertEqual($MESSAGE, '');
        $this->filter->before_search(NULL, NULL);
        $this->assertEqual($MESSAGE, '');
        $this->assertFalse(@$_SESSION['FED_LOGIN_REMINDER_SHOWN']);
        }
    
    function test_message_does_not_overwrite_existing()
        {
        global $USER, $MESSAGE;
        $MESSAGE = 'some error message';
        $USER->login = 'guest';
        $this->filter->before_search(NULL, NULL);
        $this->assertEqual($MESSAGE, 'some error message');
        $this->assertFalse(@$_SESSION['FED_LOGIN_REMINDER_SHOWN']);
        }
    }
