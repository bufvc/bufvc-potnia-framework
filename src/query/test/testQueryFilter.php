<?php
// $Id$
// Test query filters
// James Fryer, 27 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('DataSource');
Mock::generate('Query');
Mock::generate('Module');

class ZeroResultsQueryFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new ZeroResultsQueryFilter();
        $this->module = new MockModule();
        $this->factory = new QueryFactory($this->module);
        $this->query = $this->factory->new_query();
        $this->query->filters = Array($this->filter);
        }

    function test_non_zero_results_ignored()
        {
        $results = Array('total'=>101);
        $this->filter->after_search($results, $this->query, Array());
        $this->assertFalse(isset($this->query->filter_info['suggest']));
        }

    function test_random_record()
        {
        $results = Array('total'=>0);
        $this->module->expectOnce('retrieve', Array($this->query->get_table() . '/!random'));
        $this->module->setReturnValue('retrieve', Array('url'=>'/test/x', 'title'=>'foo'));
        $this->filter->after_search($results, $this->query, Array());
        $info = $this->query->filter_info['suggest'][0];
        $this->assertEqual('foo', $info['title']);
        $this->assertTrue('' <> $info['message']);
        global $MODULE;
        $this->assertEqual($MODULE->url('index', '/test/x'), $info['location']);
        }

    function test_random_record_no_title()
        {
        $results = Array('total'=>0);
        $this->module->expectOnce('retrieve', Array($this->query->get_table() . '/!random'));
        $this->module->setReturnValue('retrieve', Array());
        $this->filter->after_search($results, $this->query, Array());
        $this->assertFalse(isset($this->query->filter_info['suggest']));
        }
    }

class ExportPrintFilterTestCase
    extends UnitTestCase
    {
    function test_query_facet()
        {
        // Test parameters: Result total, expected hidden (T/F), max_export value (def. 3)
        $tests = Array(
            'too_many' => Array(4, 1), 
            'just_right' => Array(3, 0),
            'max_export_disabled' => Array(4, 0, 0),
            'no_results' => Array(0, 1),
            );
        global $CONF;
        $old_conf = $CONF['max_export'];
        foreach ($tests as $name=>$test)
            {
            $this->filter = new ExportPrintFilter();
            $this->query = new MockQuery();
            $this->query->expectOnce('get_list', Array('export_formats'));
            $this->query->setReturnValue('get_list', Array());
            $this->query->filter_info = Array();            
            $results = Array('total'=>$test[0]);
            $expect_hidden = $test[1];
            $CONF['max_export'] = is_null(@$test[2]) ? 3 : $test[2];
            $this->filter->after_search($results, $this->query, Array());
            $block = $this->query->filter_info['sidebar'][0];
            $this->assertEqual($expect_hidden, $block->hidden);
            }
        $CONF['max_export'] = $old_conf;
        }

    function test_results_facet()
        {
        $this->filter = new ExportPrintFilter();
        $this->query = new MockQuery();
        $this->query->setReturnValue('get_list', Array());
        $record = Array();
        $this->filter->after_get_record($record, $this->query, 'url', NULL);
        $block = $record['sidebar'][0];
        $this->assertFalse($block->hidden);
        }
    }

class SearchResultsFacetsFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new SearchResultsFacetsFilter();
        global $MODULE;
        $this->factory = new QueryFactory($MODULE);
        $this->query = $this->factory->new_query();
        }
    
    function test_facets_added()
        {
        $results = Array(
            'facets' => Array(
                'facet_media_type' => Array(),
                'facet_availability' => Array(),
                'facet_genre' => Array(),
                'junk' => Array(),
                ),
            'total' => 1,
            );
        $this->filter->after_search($results, $this->query, null);
        $this->assertEqual(3, count($this->query->filter_info['sidebar']));
        }
    
    function test_no_facets()
        {
        $results = Array(
            'facets' => Array(),
            'total' => 1,
            );
        $this->filter->after_search($results, $this->query, null);
        $this->assertTrue(empty($this->query->filter_info['sidebar']));
        }
    
    function test_no_facets_if_no_results()
        {
        $results = Array(
            'facets' => Array(
                'facet_media_type' => Array(),
                'facet_availability' => Array(),
                'facet_genre' => Array(),
                'junk' => Array(),
                ),
            'total' => 0,
            );
        $this->filter->after_search($results, $this->query, null);
        $this->assertTrue(empty($this->query->filter_info['sidebar']));
        }
    
    function test_media_type_facet()
        {
        $results = Array(
            'facets' => Array(
                'facet_media_type' => Array(
                    'moving_image'=>1,
                    'audio'=>2,
                    'documents'=>3,
                    ),
                ),
            'total' => 1,
            );
        $this->filter->after_search($results, $this->query, null);
        $block = $this->query->filter_info['sidebar'][0];
        $this->assertEqual('Media Type', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $expected_items = Array(
            Array('label'=>'Moving Image', 'value'=>'1', 'url'=>$this->query->url(Array('facet_media_type'=>'moving_image'))),
            Array('label'=>'Audio', 'value'=>'2', 'url'=>$this->query->url(Array('facet_media_type'=>'audio'))),
            Array('label'=>'Documents', 'value'=>'3', 'url'=>$this->query->url(Array('facet_media_type'=>'documents'))),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    function test_availability_facet()
        {
        $results = Array(
            'facets' => Array(
                'facet_availability' => Array(
                    '30'=>1,
                    '20'=>2,
                    '10'=>3,
                    ),
                ),
            'total' => 1,
            );
        $this->filter->after_search($results, $this->query, null);
        $block = $this->query->filter_info['sidebar'][0];
        $this->assertEqual('Availability', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $expected_items = Array(
            Array('label'=>'Online', 'value'=>'1', 'url'=>$this->query->url(Array('facet_availability'=>'30'))),
            Array('label'=>'To Order', 'value'=>'2', 'url'=>$this->query->url(Array('facet_availability'=>'20'))),
            Array('label'=>'Record only', 'value'=>'3', 'url'=>$this->query->url(Array('facet_availability'=>'10'))),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    function test_genre_facet()
        {
        $results = Array(
            'facets' => Array(
                'facet_genre' => Array(
                    'tv'=>1,
                    'radio'=>2,
                    'cinema'=>3,
                    'shakespeare'=>4,
                    'other'=>5,
                    ),
                ),
            'total' => 1,
            );
        $this->filter->after_search($results, $this->query, null);
        $block = $this->query->filter_info['sidebar'][0];
        $this->assertEqual('Genre', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $expected_items = Array(
            Array('label'=>'Television', 'value'=>'1', 'url'=>$this->query->url(Array('facet_genre'=>'tv'))),
            Array('label'=>'Radio', 'value'=>'2', 'url'=>$this->query->url(Array('facet_genre'=>'radio'))),
            Array('label'=>'Cinema news', 'value'=>'3', 'url'=>$this->query->url(Array('facet_genre'=>'cinema'))),
            Array('label'=>'Shakespeare productions', 'value'=>'4', 'url'=>$this->query->url(Array('facet_genre'=>'shakespeare'))),
            Array('label'=>'Other', 'value'=>'5', 'url'=>$this->query->url(Array('facet_genre'=>'other'))),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    }

class ExportICalendarFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new ExportICalendarFilter();
        global $MODULE;
        $this->factory = new QueryFactory($MODULE);
        $this->query = $this->factory->new_query();
        }
    
    function test_query_sidebar()
        {
        // Test parameters: Result total, expected hidden (T/F), max_export value (def. 3)
        $tests = Array(
            'too_many' => Array(4, 1), 
            'just_right' => Array(3, 0),
            'max_export_disabled' => Array(4, 0, 0),
            'no_results' => Array(0, 1),
            );
        global $CONF;
        $old_conf = $CONF['max_export'];
        foreach ($tests as $name=>$test)
            {
            $this->query->filter_info = Array();            
            $results = Array('total'=>$test[0]);
            $expect_hidden = $test[1];
            $CONF['max_export'] = is_null(@$test[2]) ? 3 : $test[2];
            $this->filter->after_search($results, $this->query, Array());
            $block = $this->query->filter_info['sidebar'][0];
            $this->assertEqual($expect_hidden, $block->hidden);
            }
        $CONF['max_export'] = $old_conf;
        }

    function test_results_sidebar()
        {
        $record = Array();
        $this->filter->after_get_record($record, NULL, '/123', NULL);
        $block = $record['sidebar'][0];
        $this->assertFalse($block->hidden);
        $this->assertEqual('iCalendar', $block->vars['items'][0]['label']);
        $this->assertTrue(strpos($block->vars['items'][0]['url'], 'index.php/123.ical') !== FALSE);
        }
    }

class TestMediaLocationFilter
    extends MediaLocationFilter
    {
    protected function create_ticket($resource_url)
        {
        return $resource_url . '_changed';
        }
    }
    
class MediaLocationFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new TestMediaLocationFilter();
        }
        
    function test_record_media_no_ticket()
        {
        global $CONF;
        $this->query = new MockQuery();
        $this->query->module = new DummyModule();
        $record = Array('media'=>Array(
            Array('location'=>'foo'),
            Array('location'=>'bar'),
            ));
        $this->filter->after_get_record($record, $this->query, 'url', NULL);
        // Original location is preserved in 'orig_location'
        $this->assertEqual($record['media'][0]['orig_location'], 'foo');
        $this->assertEqual($record['media'][1]['orig_location'], 'bar');
        // Location is changed to protected version
        $this->assertEqual($record['media'][0]['location'], $CONF['url_media'] . '/dummy/foo');
        $this->assertEqual($record['media'][1]['location'], $CONF['url_media'] . '/dummy/bar');
        }

    function test_record_media_ticket()
        {
        global $CONF;
        $CONF['url_ironduke'] = 'foo';
        $this->query = new MockQuery();
        $this->query->module = new DummyModule();
        $record = Array('media'=>Array(
            Array('location'=>'foo'),
            Array('location'=>'bar'),
            ));
        $this->filter->after_get_record($record, $this->query, 'url', NULL);
        // Original location is preserved in 'orig_location'
        $this->assertEqual($record['media'][0]['orig_location'], 'foo');
        $this->assertEqual($record['media'][1]['orig_location'], 'bar');
        // Location is changed to protected version
        $this->assertEqual($record['media'][0]['location'], 'file://dummy/foo_changed');
        $this->assertEqual($record['media'][1]['location'], 'file://dummy/bar_changed');
        $CONF['url_ironduke'] = '';
        }

    function test_no_media()
        {
        $record = Array();
        $this->filter->after_get_record($record, 'query', 'url', NULL);
        }

    function test_no_location()
        {
        $record = Array('media'=>Array(
            Array('something'=>'foo'),
            ));
        $this->filter->after_get_record($record, 'query', 'url', NULL);
        }
    }

class BlockAllFieldsSearchFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new BlockAllFieldsSearchFilter();
        global $MODULE;
        $this->factory = new QueryFactory($MODULE);
        $this->query = $this->factory->new_query();
        $this->query->criteria_container = new QueryCriteria(Array(
            Array('name'=>'x'),
            Array('name'=>'y', 'type'=>QC_TYPE_FLAG),
            ));
        }

    function test_with_criteria()
        {
        $criteria = Array('x'=>'123', 'y'=>'456');
        $this->filter->before_search($this->query, $criteria);
        $this->assertEqual($this->query->error_code, 0);
        }
    
    function test_criteria_already_set()
        {
        $criteria = Array('x'=>'123', 'y'=>'456');
        $this->query->set_criteria_values($criteria);
        $this->filter->before_search($this->query, NULL);
        $this->assertEqual($this->query->error_code, 0);
        }
    
    function test_empty_criteria()
        {
        $criteria = Array('x'=>'', 'y'=>'', 'junk'=>'junk');
        $this->filter->before_search($this->query, $criteria);
        // error code was set
        $this->assertTrue($this->query->error_code != 0);
        }
    
    function test_with_configured_default()
        {
        $this->query->criteria_container['y']->set_default('foo');
        $criteria = Array('y'=>'123');
        $this->filter->before_search($this->query, $criteria);
        $this->assertEqual($this->query->error_code, 0);
        
        $criteria = Array('x'=>'123', 'y'=>'');
        $this->filter->before_search($this->query, $criteria);
        $this->assertEqual($this->query->error_code, 0);
        
        $criteria = Array('y'=>'foo');
        $this->filter->before_search($this->query, $criteria);
        // error code was set
        $this->assertTrue($this->query->error_code != 0);
        }
    }

class SearchResultsFacetTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->query = new MockQuery();
        $this->query->setReturnValue('url', 'url');
        $this->query->setReturnValue('has_criteria', false);
        // use media type test data
        $this->labels = Array(
            'facet_moving_image' => 'Moving Image',
            'facet_audio' => 'Audio',
            'facet_documents' => 'Documents',
            );
        $this->data = Array(
            'facet_moving_image'=>1,
            'facet_audio'=>2,
            'facet_documents'=>0, // facets with 0 will not be visible
            );
        }

    function test_ctor()
        {
        $block = new SearchResultsFacet('Media Type', '', $this->labels, 'facet_media_type', $this->data, $this->query);
        $this->assertEqual('Media Type', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $this->assertFalse($block->hidden);
        $expected_items = Array(
            Array('label'=>'Moving Image', 'value'=>'1', 'url'=>'url'),
            Array('label'=>'Audio', 'value'=>'2', 'url'=>'url'),
            );
        $this->assertEqual($expected_items, $block->vars['items']);
        }
    
    function test_ctor_adds_selected_facets()
        {
        $this->query->setReturnValueAt(0, 'has_criteria', true);
        $block = new SearchResultsFacet('Media Type', '', $this->labels, 'facet_media_type', $this->data, $this->query);
        $this->assertEqual(count($block->selected), 1);
        $this->assertEqual($block->selected['Moving Image']['type'], 'facet_media_type');
        }
    
    // 0 priority
    function test_empty_lists_hidden()
        {
        $data = Array(
            'moving_image'=>0,
            'audio'=>0,
            'documents'=>0,
            );
        $block = new SearchResultsFacet('Media Type', '', $this->labels, 'facet_media_type', $data, $this->query);
        $this->assertEqual('Media Type', $block->vars['title']);
        $this->assertEqual('', $block->vars['description']);
        $this->assertTrue($block->hidden);
        }
    
    function test_ctor_sets_auth_warning_flag()
        {
        $this->query->module->name = 'dummy';
        // flag not set if availability: online not included
        $block = new SearchResultsFacet('Availability', '', $this->labels, 'facet_availability', $this->data, $this->query);
        $this->assertFalse(isset($block->show_auth_warning));
        
        // add availability online to results
        $this->labels['30'] = 'Online';
        $this->data['30'] = 2;
        $block = new SearchResultsFacet('Availability', '', $this->labels, 'facet_availability', $this->data, $this->query);
        // the flag has been set to false
        $this->assertTrue(isset($block->show_auth_warning));
        $this->assertFalse($block->show_auth_warning);
        
        $this->query->module->name = 'bund';
        $block = new SearchResultsFacet('Availability', '', $this->labels, 'facet_availability', $this->data, $this->query);
        // the flag has been set to true
        $this->assertTrue(isset($block->show_auth_warning));
        $this->assertTrue($block->show_auth_warning);
        }
    
    function test_is_selected()
        {
        $block = new SearchResultsFacet('Test', '', Array(), '', Array(), '');
        // empty items
        $this->assertFalse($block->is_selected(NULL));
        $this->assertFalse($block->is_selected(Array()));
        // empty selected array
        $this->assertFalse($block->is_selected(Array('label'=>'abc')));
        // set some selected values
        $block->selected = Array(
            '123' => '',
            'abc' => '',
            'foo' => '',
            );
        $this->assertFalse($block->is_selected(Array('label'=>'bar')));
        $this->assertTrue($block->is_selected(Array('label'=>'abc')));
        $this->assertTrue($block->is_selected(Array('label'=>'foo')));
        }
    
    function test_add_extra_text()
        {
        $this->query->setReturnValueAt(0, 'has_criteria', true);
        $this->query->module->name = 'bund';
        $this->labels = Array('30'=>'Online');
        $this->data = Array('30'=>1);
        $block = new SearchResultsFacet('Media Type', '', $this->labels, 'facet_media_type', $this->data, $this->query);
        $result = $block->render();
        // the 'Remove' link has been added one time
        $this->assertEqual(preg_match('/Remove/', $result), 1);
        // the locked icon has been added one time
        $this->assertEqual(preg_match('/tip-warning/', $result), 1);
        }
    }
if (function_exists('pspell_new')):
class SpellingCorrectorFilterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->filter = new SpellingCorrectorFilter();
        global $MODULE;
        $this->factory = new QueryFactory($MODULE);
        $this->query = $this->factory->new_query();
        $this->query->criteria_container = new QueryCriteria(Array(
            Array(
                'name' => 'q',
                'qs_key'=> Array('q','adv'),
                'qs_key_index' => Array( 'title'=>'title', 'description'=>'description', 'person'=>'person' ),
                'label' => 'Search for',
                'render_label' => "Search for",
                'index' => 'default',
                'render_default' => 'Search for',
                'list' => 'list_search',
                'advanced_value_count' => 3,
                'is_primary' => TRUE,
                ),
            ));
        $this->results = Array('total'=>'3');
        }

    function test_empty_query_ignored()
        {
        $criteria = Array('q'=>'');
        $this->filter->after_search($this->results, $this->query, $criteria);
        $this->assertFalse(isset($this->query->filter_info['spelling']));
        }
    
    function test_empty_advanced_query_ignored()
        {
        $criteria = Array('q' => Array(
            0 => Array('v'=>'', 'index'=>''),
            1 => Array('index'=>''),
            2 => Array('index'=>'test'),
            ));
        $this->filter->after_search($this->results, $this->query, $criteria);
        $this->assertFalse(isset($this->query->filter_info['spelling']));
        }
    
    function test_correct_words_ignored()
        {
        $criteria = Array('q'=>'some test data');
        $this->filter->after_search($this->results, $this->query, $criteria);
        $this->assertFalse(isset($this->query->filter_info['spelling']));
        }
    
    function test_spelling_is_corrected()
        {
        $criteria = Array('q'=>'ssome ttest dataa');
        $expected = 'some test data';
        $this->filter->after_search($this->results, $this->query, $criteria);
        $this->assertEqual($this->query->filter_info['spelling'], $expected);
        // check some display values
        $info = $this->query->filter_info['suggest'][0];
        $this->assertTrue(preg_match('/Did you mean:/', $info['message']), 'expected string not found');
        $this->assertTrue(preg_match('/some test data/', $info['message']), 'expected string not found');
        }
    
    function test_spelling_is_corrected_advanced_query()
        {
        $criteria = Array('q' => Array(
            0 => Array('v'=>'ssome', 'index'=>''),
            1 => Array('v'=>'test', 'index'=>'title'),
            2 => Array('v'=>'dataa', 'index'=>'description'),
            ));
        $expected = Array(
            0 => Array('spelling'=>'some', 'index'=>''),
            2 => Array('spelling'=>'data', 'index'=>'description'),
            );
        $this->filter->after_search($this->results, $this->query, $criteria);
        $this->assertEqual($this->query->filter_info['spelling'], $expected);
        // check some display values
        $info = $this->query->filter_info['suggest'][0];
        $this->assertTrue(preg_match('/Did you mean:/', $info['message']), 'expected string not found');
        $this->assertTrue(preg_match('/\'some\' in/', $info['message']), 'expected string not found');
        $this->assertTrue(preg_match('/\'test\' in title/', $info['message']), 'expected string not found');
        $this->assertTrue(preg_match('/\'data\' in description/', $info['message']), 'expected string not found');
        }
    }
endif;
