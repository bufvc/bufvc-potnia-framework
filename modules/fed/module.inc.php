<?php
// $Id$
// Module definition file
// James Fryer, 23 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once 'src/StatsStorage.class.php';
require_once 'src/RecordStatsFilter.class.php';

/// This class overrides the existing Aggregate storage used to 
/// build search results from all modules in order to capture searches
/// for Calais types ( Entity,Tag,Category ).
///
class DataSource_FedStorage
    extends DataSource_AggregateStorage
    {
    function __construct()
        {
        parent::__construct();
        $this->calais_module = Module::load('calais');
        if( $this->calais_module )
            $this->calais_ds = $this->calais_module->get_datasource();
        }
    
    // overrides the existing aggregateStorage search function to capture
    // any calais item related searches
    function search(&$ds, $table, $tree, $offset, $max_count)
        {
        $flat_tree = $tree->flatten();
        $index = @$flat_tree[0][0]['index'];
        switch( $index )
            {
        case 'entity_name':
        case 'category_name':
        case 'tag_name':
            $query_string = $tree->to_string();
            $results = $this->calais_ds->search('/title', $query_string, $offset, $max_count);
            // The URL of each result needs to be rebuilt in order for it to be useable 
            // on the fed screen
            foreach( $results['data'] as &$r )
                {
                $r['o_url'] = $r['url'];
                $r['modname'] = Module::name_from_url( $r['url'], '/title' );
                $r['url'] = $this->normalise_calais_url($r['url'], $r['modname'] );
                }
            return $results;
            }
        return parent::search(&$ds, $table, $tree, $offset, $max_count);
        }
    
    // Removes parts of a url which the calais module may have added
    function normalise_calais_url( $url, $remove_prefix )
        {
        $parts = preg_split("/\//m", $url);
        array_shift( $parts );
        array_shift( $parts );
        if( $parts[0] == $remove_prefix )
            array_shift( $parts );
        return '/' . join('/', $parts);
        }
    }
    
class FedModule
    extends Module
    {
    var $name = 'fed';
    var $query_config = Array(
        'cache_class'=>'FedQueryCache',
        'filters' => Array('QueryComponentsFilter', 'SearchResultsFacetsFilter','RelatedQueryFilter','UserLoginReminderFilter'),
        );
    var $title = 'All BUFVC';
    var $version = '0.24';
    var $feed_size = 10;
    var $restricted_modules = Array('tvtip', 'thisweek');
    
    function new_datasource()
        {
        global $CONF;
        $config = Array(
            'item' => Array(
                'title'=>'Test',
                'description'=>'Test table',
                // 'storage'=> 'aggregate',
                'storage'=>'fed',
                // Will have module etc added in loop below
                'components' => Array(
                    'trilt' => Array(
                        'table' => '/prog',
                        'adaptor' => Array(
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_30' => Array(
                                'index' => 'facet_availability',
                                'value' => '30',
                                ),
                            // ** ADDED BELOW after module created
                            // 'date' => Array(
                                // 'index' => 'date',
                                // 'relation' => '>=',
                                // 'function' => Array($module, 'limit_date'),
                                // ),
                            ),
                        ),
                    'bund' => Array(
                        'table' => '/story',
                        'adaptor' => Array(
                            'audio' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'audio',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'radio' => Array(
                                'index' => 'facet_genre',
                                'value' => 'radio',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            ),
                        ),
                    'hermes' => Array(
                        'table' => '/title',
                        'query_add' => '{shakespeare=0}',
                        'adaptor' => Array(
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'radio' => Array(
                                'index' => 'facet_genre',
                                'value' => 'radio',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_10' => Array(
                                'index' => 'facet_availability',
                                'value' => '10',
                                ),
                            ),
                        ),
                    'shk' => Array(
                        'table' => '/title',
                        'adaptor' => Array(
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'radio' => Array(
                                'index' => 'facet_genre',
                                'value' => 'radio',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'avail_10' => Array(
                                'index' => 'facet_availability',
                                'value' => '10',
                                ),
                            ),
                        ),
                    'tvtip' => Array(
                        'table' => '/prog',
                        'adaptor' => Array(
                            'moving_image' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'moving_image',
                                ),
                            'audio' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'audio',
                                ),
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'radio' => Array(
                                'index' => 'facet_genre',
                                'value' => 'radio',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_30' => Array(
                                'index' => 'facet_availability',
                                'value' => '30',
                                ),
                            'avail_20' => Array(
                                'index' => 'facet_availability',
                                'value' => '20',
                                ),
                            ),
                        ),
                    'thisweek' => Array(
                        'table' => '/prog',
                        'adaptor' => Array(
                            'moving_image' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'moving_image',
                                ),
                            'audio' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'audio',
                                ),
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'radio' => Array(
                                'index' => 'facet_genre',
                                'value' => 'radio',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_30' => Array(
                                'index' => 'facet_availability',
                                'value' => '30',
                                ),
                            'avail_20' => Array(
                                'index' => 'facet_availability',
                                'value' => '20',
                                ),
                            ),
                        ),
                    'lbc' => Array(
                        'table' => '/segment',
                        'adaptor' => Array(
                            'moving_image' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'moving_image',
                                ),
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_20' => Array(
                                'index' => 'facet_availability',
                                'value' => '20',
                                ),
                            'avail_10' => Array(
                                'index' => 'facet_availability',
                                'value' => '10',
                                ),
                            ),
                        ),
                    'ilrsouth' => Array(
                        'table' => '/segment',
                        'adaptor' => Array(
                            'moving_image' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'moving_image',
                                ),
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_20' => Array(
                                'index' => 'facet_availability',
                                'value' => '20',
                                ),
                            'avail_10' => Array(
                                'index' => 'facet_availability',
                                'value' => '10',
                                ),
                            ),
                        ),
                    'ilrsharing' => Array(
                        'table' => '/segment',
                        'adaptor' => Array(
                            'moving_image' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'moving_image',
                                ),
                            'documents' => Array(
                                'index' => 'facet_media_type',
                                'value' => 'documents',
                                ),
                            'tv' => Array(
                                'index' => 'facet_genre',
                                'value' => 'tv',
                                ),
                            'cinema' => Array(
                                'index' => 'facet_genre',
                                'value' => 'cinema',
                                ),
                            'other' => Array(
                                'index' => 'facet_genre',
                                'value' => 'other',
                                ),
                            'shakespeare' => Array(
                                'index' => 'facet_genre',
                                'value' => 'shakespeare',
                                ),
                            'avail_20' => Array(
                                'index' => 'facet_availability',
                                'value' => '20',
                                ),
                            'avail_10' => Array(
                                'index' => 'facet_availability',
                                'value' => '10',
                                ),
                            ),
                        ),
                    ),
                'query_criteria' => Array(
                    Array(
                        'name' => 'q',
                        'qs_key'=> Array('q','adv'),
                        'qs_key_index' => Array( 'entity'=>'entity_name', 'topic'=>'category_name', 'category'=>'category_name', 'tag'=>'tag_name' ),
                        'label' => "Search for",
                        'render_label' => "Search {$this->title} for",
                        'index' => 'default',
                        'render_default' => 'All records',
                        'list' => 'list_search',
                        'advanced_value_count' => 3,
                        'is_primary' => 1,
                        ),
                    Array(
                        'name' => 'date',
                        'label' => 'Year',
                        'type' => QC_TYPE_DATE_RANGE,
                        'range' => Array('1896', 'this_year'),
                        'add_lists' => TRUE,
                        ),
                    Array(
                        'name' =>'components',
                        'label' => 'Collections',
                        'type' => QC_TYPE_FLAG,
                        'list' => 'components',
                        ),
                    Array(
                        'name' =>'facet_media_type',
                        'label' => 'Media types',
                        'type' => QC_TYPE_FLAG,
                        'list' =>'facet_media_type',
                        ),
                    Array(
                        'name' =>'facet_availability',
                        'label' => 'Availability',
                        'type' => QC_TYPE_FLAG,
                        'list' =>'facet_availability',
                        ),
                    Array(
                        'name' => 'facet_genre',
                        'label' => 'Genre',
                        'type' => QC_TYPE_FLAG,
                        'render_default' => 'Any Genre',
                        'list' => 'facet_genre',
                        ),
                    Array(
                        'name' => 'sort',
                        'label' => 'Sort By',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('relevance'=>'Relevance', 'date_asc'=>'Date (oldest first)', 'date_desc'=>'Date (newest first)', 'title'=>'Title'),
                        'is_renderable' => FALSE,
                        ),
                    Array(
                        'name' => 'page_size',
                        'label' => 'Display',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        'default' => 10
                        ),
                    ),
                'query_lists'=> Array(
                    'facet_media_type' => Array(
                        'moving_image'=>'Moving Image',
                        'audio'=>'Audio',
                        'documents'=>'Documents',
                        ),
                    /* Proposed availability classification
                     10 We do not have the content
                     11 There was content, but it no longer exists (e.g. Dr Who "The Highlanders")

                     20 There is content, and it exists, but we don't have it
                     21 We know who has it, online (e.g. NFO)
                     22 We know who has it, offline (e.g. collections)

                     30 There is content, we have it right here 
                     31 We have it somewhere else on our site (e.g. BoB)
                     32 You can order it from us (e.g. OA ordering)
                    */
                    'facet_availability' => Array(
                        30=>'Online',
                        20=>'To Order',
                        10=>'Record only',
                        ),
                    'facet_genre' => Array(
                        'tv'=>'Television',
                        'radio'=>'Radio',
                        'cinema'=>'Cinema news',
                        'shakespeare'=>'Shakespeare productions',
                        'other'=>'Other',
                        ),
                        
                    
                    /// NOTE AV : I've re-enabled this to allow the calais types to properly work
                    'list_search' => Array(
                        '' => 'All fields',
                        'title' => 'Title',
                        'category_name' => 'Category',
                        'entity_name' => 'Entity',
                        'tag_name' => 'Tag',
                        ),
                    ),
                ),
            'stats' => Array(
                'pear_db'=>$this->get_pear_db(),
                'title'=>'Record statistics',
                'storage'=>'stats',
                'mutable'=>TRUE,
                'fields'=>Array(),
                'atom_element_extras' => Array(
                    'date' => 'updated',
                    'title' => 'title',
                    'summary' => 'summary',
                    'link' => 'link',
                    ),
                ),
            );
        
        // Load DS+url into config array
        $tmp = $config['item']['components'];
        $query_components = Array();
        foreach ($tmp as $name=>$value)
            {
            $module = Module::load($name);
            if (!is_null($module))
                {
                $value['ds'] = $module->get_datasource();
                $value['module'] = $module;
                $value['title'] = $module->title;
                $value['subtitle'] = $module->subtitle;
                $value['description'] = $module->description;
                $value['icon'] = $module->icon;
                $tmp[$name] = $value;
                // Build list for query module selection
                $query_components[$name] = $module->title;
                }
            $tmp[$name]['restricted'] = $this->is_restricted($name);
            }
        $config['item']['components'] = $tmp;
        
        // finish trilt adaptor
        if (isset($config['item']['components']['trilt']['module']))
            {
            $config['item']['components']['trilt']['adaptor']['date'] = Array(
                'index' => 'date',
                'relation' => '>=',
                'function' => Array($config['item']['components']['trilt']['module'], 'limit_date'),
                );
            }
        
        // Set up components list for query
        $config['item']['query_lists']['components'] = $query_components;

        // return new DataSource($config);
        $ds = new DataSource($config);
        // return new FedDataSource( $ds );
        return $ds;
        }
    
    /// Is a given module currently restricted
    function is_restricted($name)
        {
        global $USER;
        return !$USER->is_registered() && in_array($name, $this->restricted_modules);
        }
    
    /// Handle Atom feeds depending on path
    function get_feed($path=NULL)
        {
        $formatter = $this->new_formatter('atom');
        $update_time = $formatter->format_atom_date(date('Y-m-d H:i:s'));
        
        if ($path == 'records')
            {
            $records = $this->_get_top_viewed_records($update_time);
            $title = ' Top Viewed Records';
            }
        // queries
        else
            {
            $records = $this->_get_top_queries($formatter);
            $title = ' Most Popular Queries'; 
            }
        
        $result = $formatter->get_header($this, $update_time, 'British Universities Film & Video Council', $title, '/'.$path);
        foreach ($records as $record)
            {
            $result .= "  <entry>\n";
            $result .= $formatter->format($record);
            $result .= "  </entry>\n\n";
            }
        $result .= $formatter->get_footer();
        return $result;
        }
    
    // Retrieve each full record entry and prepare atom fields for display
    function _get_top_viewed_records($date)
        {
        $ds = $this->get_datasource();
        $records = $ds->retrieve('/stats/topviewed/'.$this->feed_size);
        foreach ($records as $key=>$record)
            {
            $url = strip_prefix($record['url'], '/stats');
            $result = $this->retrieve($url);
            $record['date'] = $date;
            $record['title'] = @$result['title'];
            $record['summary'] = isset($result['description']) ? $result['description'] : @$result['summary'];
            $record['link'] = $result['module']->url('index', $result['url']);
            $record['_table'] = 'fed/stats';
            $records[$key] = $record;
            }
        return $records;
        }
    
    // Retrieve the top queries and prepare atom fields for display
    function _get_top_queries($formatter)
        {
        $ds = $this->get_datasource();
        $records = $ds->retrieve('/stats/topqueries/'.$this->feed_size);
        foreach ($records as $key=>$record)
            {
            $summary = str_replace('\n', "\n", $record['details']);
            $record['date'] = $formatter->format_atom_date($record['date']);
            $record['title'] = $summary;
            $record['summary'] = nl2br("$summary\n$record[results_count]");
            $record['summary'] .= ($record['results_count'] == 1) ? ' result' : ' results';
            $record['summary'] = nl2br($record['summary']);
            $record['link'] = $record['url'];
            $record['_table'] = 'fed/stats';
            $records[$key] = $record;
            }
        return $records;
        }
    }

class FedQueryCache
    extends QueryCriteriaCache
    {
    // Equality check for the given record and the specified cache index
    function compare_record($index, $record)
        {
        $modname = is_string($record['module']) ? $record['module'] : $record['module']->name;
        return $this->results['data'][$index]['url'] == $record['url'] &&
               $this->results['data'][$index]['module']->name == $modname;
        }

    // Returns an array containing the url link and the module object
    // for the given cache index
    function set_record_link($index)
        {
        $result = Array(
            'url'=>$this->results['data'][$index]['url'],
            'module'=>$this->results['data'][$index]['module'],
            );
        return $result;
        }
    }

/// List of component modules with result counts
class SearchInModuleBlock
    extends SidebarBlock
    {
    /// If set, the block will use fed search, otherwise it will drill into the modules
    var $use_fed_search = TRUE;
    
    var $auth_msg = Array(
        'trilt' => 'searches limited to the last two weeks only. Full access requires login (users from BUFVC member institutions)',
        'tvtip' => 'only available to logged-in users (all HE/FE users and BUFVC members)',
        'this week' => 'only available to logged-in users (all HE/FE users and BUFVC members)',
        'radio' => 'full access to metadata, but the audio requires login (available to all UK HE/FE users)',
        );
    
    function __construct($query, $use_fed_search=NULL)
        {
        //### FIXME: this is a hack, the arg is used for tests only
        if (!is_null($use_fed_search))
            $this->use_fed_search = $use_fed_search;
        if ($this->use_fed_search)
            $fed_module = Module::load('fed');
        $items = Array();
        foreach ($query->info['components'] AS $comp)
            {
            // restricted collections don't have an active link
            if (@$comp['restricted'])
                $url = '';
            else if ($this->use_fed_search)
                $url = $fed_module->url('search', '?' . $query->url_query(Array('page'=>NULL,'components'=>$comp['module']->name)));
            else
                $url = $comp['module']->url('search', '?' . $query->url_query(Array('page'=>NULL)));
            $items[] =  Array('label'=>$comp['module']->title, 'value'=>format_number($comp['total']), 'url'=>$url);
            }
        parent::__construct('Collections', '', $items);
        }
    
    // Add icon and help text for collections requiring authentication
    function add_extra_text($item)
        {
        global $USER;
        $name = strtolower($item['label']);
        $is_radio = in_array($name, Array('lbc/irn', 'ilr south', 'ilr sharing'));
        if ($name == 'trilt' && !$USER->has_right('trilt_user'))
            return '<span title="'.$item['label'].': '.$this->auth_msg['trilt'].'" class="tip-lock">(locked)</span>';
        else if ($is_radio && !$USER->has_right('play_audio'))
            return '<span title="'.$item['label'].': '.$this->auth_msg['radio'].'" class="tip-lock">(locked)</span>';
        else if (isset($this->auth_msg[$name]) && !$USER->is_registered())
            return '<span title="'.$item['label'].': '.$this->auth_msg[$name].'" class="tip-lock">(locked)</span>';
        return '';
        }
    }

/// Create a set of search links from the query->info['components'] array
class QueryComponentsFilter
    extends QueryFilter
    {
    function after_search(&$results, $query, $criteria)
        {
        if (isset($query->info['components']) && @$results['total'] > 0)
            $query->filter_info['sidebar'][] = new SearchInModuleBlock($query);
        }
    }
    
// Offer to search all bufvc from non-fed module
// Must be added to global config
class AllBufvcFilter
    extends QueryFilter
    {
    function after_search(&$results, $query, $criteria)
        {
        if ($query->module->name != 'fed')
            {
            $fed_module = Module::load('fed');
            $criteria = $query->criteria_container->get_qs_key_values();
            // Don't propagate TRILT dates, as it is annoying to get the 2 week date limit if not logged in
            if ($query->module->name == 'trilt')
                unset($criteria['date'], $criteria['date_start'], $criteria['date_end']); 
            $criteria = $this->rewrite_query($criteria, $query);
            if (is_null($criteria))
                return;
            //### FIXME: without this, queries for all records don't have 'q=' in them, this needs to be fixed in QueryCriteria class
            $criteria['page'] = 1; 
            $fed_query = QueryFactory::create($fed_module);
            $url = $fed_query->url($criteria);
            $criteria_msg = '';
            if (@$criteria['q'] != '')
                $criteria_msg = " for <b>{$criteria['q']}</b>";
            $msg = "You are currently searching in <b>{$query->module->title}</b>. <a href=\"$url\">Search all the BUFVC's collections$criteria_msg</a>.";
            $query->filter_info['sidebar'][] = new SidebarBlock('Search All BUFVC', $msg);
            }
        }

    /// Rewrite a query so it can be used in federated search.
    /// Basically copy 'q' and 'date*' across, ignore all others
    /// Return NULL if the query can't be rewritten i.e. is blank or numeric (ID)
    function rewrite_query($criteria, $query=NULL)
        {
        $result = Array();
        // Search for strings only
        // Copy the fields we want across, removing them from the array
        foreach (Array('q', 'date', 'date_start', 'date_end') as $name)
            {
            $value = @$criteria[$name];
            if ($value != '')
                {
                $result[$name] = $value;
                unset($criteria[$name]);
                }
            }
        // If no value specified for 'q', get the first remaining value from the array
        if (@$result['q'] == '' || is_numeric($result['q']) || is_bool($result['q']))
            {
            $values = array_values($criteria);
            $value = @$values[0];
            $result['q'] = $value;
            }
        
        // If we still haven't found a useable value try looking it up in query lists
        if (!is_null($query) && is_numeric(@$result['q']))
            {
            //### FIXME: This does not always select the correct list. See ticket #444
            $keys = array_keys($criteria);
            $list = $query->get_list($keys[0]);
            $result['q'] = @$list[$result['q']];
            }

        if (@$result['q'] == '' || is_numeric($result['q']) || is_bool($result['q']))
            return NULL;
        else
            return $result;
        }
    }

/// Remind users to login
/// The message is only shown once per session
/// If an error/info message has already been set this function will not overwrite it
class UserLoginReminderFilter
    extends QueryFilter
    {
    var $msg = 'Full access to all collections is a privilege of BUFVC membership.
If you are already a BUFVC member, please <a href="%s">log in</a>.
Otherwise you may <a href="http://bufvc.ac.uk/membership">join now</a>.';
    function before_search($query, $criteria)
        {
        global $CONF, $USER, $MESSAGE, $MESSAGE_CLASS;
        if (!$USER->is_registered() && !@$_SESSION['FED_LOGIN_REMINDER_SHOWN'] && empty($MESSAGE))
            {
            $url = $CONF['url_login'] . '?url=' . urlencode(get_current_url());
            // set these fields directly rather than use set_session_message because we want them 
            // displayed on this first page load
            $MESSAGE = sprintf($this->msg, $url);
            $MESSAGE_CLASS = 'info-message';
            $_SESSION['FED_LOGIN_REMINDER_SHOWN'] = TRUE;
            }
        }
    }
