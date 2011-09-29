<?php
// $Id$
// Aggregate storage is used to combine several DSs into one
// James Fryer, 11 Aug 08, 5 Aug 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'parser/ParsedQueryAdaptor.class.php');

/// Aggregate storage is used to combine several DS tables into one
/*                'components' => Array(
                    '1' => Array(
                        'ds' => $this->mock_ds1,
                        'table' => '/test1',
                        'title' => 'ds1',
                        'description' => 'descr 1',
                        'module' => module name or object
                        ),
*/
class DataSource_AggregateStorage
    {
    /// AggregateStorageSearchBuffer: Buffer search results from the various component datasources
    var $buffer;
    
    function __construct()
        {
        global $CONF;
        // Select sequential or concurrent searching
        if (!$CONF['unit_test_active'] && @$CONF['fed_search_mode'] == 'concurrent')
            $this->buffer = new AggregateStorageCurlMultiSearchBuffer();        
        else
            $this->buffer = new AggregateStorageSearchBuffer();
        }
        
    /// Search can take a 'components' clause which can be an array of component names or indices
    function search(&$ds, $table, $tree, $offset, $max_count)
        {
        // Normalise the offset to the current page
        if ($max_count > 0)
            $offset = (int)($offset / $max_count) * $max_count;
        $all_results = $this->_do_searches($ds, $table, $tree, $offset, $max_count);
        // Aggregate the results and get facets etc.
        $total = 0;
        $aggregated_data = Array();
        $comp_summaries = Array();
        $facets = Array();
        $sort = NULL;
        // These records will always be shown first
        $priority_records = Array();
        foreach ($all_results as $comp_results)
            {
            $comp = $comp_results['comp'];
            $r = $comp_results['results'];
            // Get module
            $module = is_string($comp['module']) ? Module::load($comp['module']) : $comp['module'];
            // Process results
            $weight = @$comp['weight']  ? $comp['weight'] : 1.0;
            if ($r['total'])
                {
                // Get sort definition
                if (is_null($sort))
                    $sort = $this->_get_sort_config($tree, $comp);

                // restricted modules have special handling
                // namely the search results are removed but the actual total is preserved in total_found
                $restricted = @$comp['restricted'];
                if ($restricted)
                    {
                    $r['total_found'] = $r['total'];
                    $r['total'] = 0;
                    $r['count'] = 0;
                    $r['data'] = Array();
                    unset($r['facets']);
                    }
                
                // Aggregate total
                $total += $r['total'];
                
                // Add module to results
                for ($i = 0; $i < count($r['data']); $i++)
                    {
                    $r['data'][$i]['module'] = $module;
                    $r['data'][$i]['_weight'] = $weight;
                    }

                // If in relevance sort, prioritise the first record from each module
                if ($sort['type'] == 'rel' && !$restricted)
                    $priority_records[] = array_shift($r['data']);

                $aggregated_data = array_merge($aggregated_data, $r['data']);
                
                // Set up results summaries
                $summary = $comp;
                $summary['name'] = $comp_results['comp_name'];
                $summary['module'] = $module;
                $summary['total'] = @$r['total_found'] ? $r['total_found'] : $r['total'];
                $summary['accuracy'] = $r['accuracy'];
                $comp_summaries[] = $summary;
                
                // add facet information
                if (isset($r['facets']))
                    $facets = $this->_add_facets($facets, $r['facets']);
                }
            }

        // Sort and trim the data. Priority records are put at the front unsorted
        if (count($aggregated_data))
            usort($aggregated_data, '_DS_AggregateStorage_cmp_' . $sort['type']);
        if (count($priority_records))
            $aggregated_data = array_merge($priority_records, $aggregated_data);
        $aggregated_data = array_slice($aggregated_data, 0, $max_count);

        if ($total <= $max_count)
            $result = $ds->_make_search_results_array($aggregated_data, $total, $offset, $max_count, 'exact');
        else
            $result = $ds->_make_search_results_array($aggregated_data, $max_count, $offset, $max_count, 'exceeds');
        $result['components'] = $comp_summaries;
        if (!empty($facets))
            $result['facets'] = $facets;
        return $result;
        }

    // Perform all the searches and return the results in an array:
    //  'comp_name'=>, 'comp'=>, 'results'=>
    private function _do_searches(&$ds, $table, $tree, $offset, $max_count)
        {
        $meta = $ds->_get_meta($table);
        $query_components = $this->_list_components($meta, $tree);
        $tree->remove('components');

        // Get the query string to pass to the components
        $query_string = $tree->to_string();

        // Search all the components
        $result = Array();
        debug_start_timer();
        foreach ($meta['components'] as $comp_name=>$comp)
            {
            // Check if the current component has been excluded
            if ($query_components && !in_array($comp_name, $query_components))
                continue;
            
            // Rewrite the query string
            $query_string_temp = $this->_modify_query($query_string, $comp, $tree);
            if (is_null($query_string_temp))
                continue;
            
            // Do search
            $this->buffer->search($comp_name, $comp, $query_string_temp, $offset, $max_count);
            }
        debug_stop_timer("Duration (seq)");
        return $this->buffer->get_results();
        }

    // List the components in the parsed query tree
    //### FIXME: Restructure query parser to make this kind of thing easier
    private function _list_components($meta, $tree)
        {
        $result = NULL;
        $components = $tree->find('components');
        if ($components)
            {
            $tmp = $components[0]['subject'];
            if (!is_array($tmp))
                $tmp = explode(',', $tmp);
            // Convert numeric indices to strings
            $result = Array();
            $names = array_keys($meta['components']);
            foreach ($tmp as $candidate)
                {
                if (is_numeric($candidate))
                    $candidate = @$names[$candidate];
                if ($candidate != '')
                    $result[] = $candidate;
                }
            }
        return $result;
        }
    
    // Aggregate facet data
    private function _add_facets($facets, $new_facets)
        {
        foreach ($new_facets as $group=>$types)
            {
            // special case, accuracy field
            if ($group == 'accuracy')
                {
                if (!isset($facets['accuracy']))
                    $facets['accuracy'] = $types;
                // exact does not overwrite the other types
                else if ($types != 'exact')
                    $facets['accuracy'] = $types;
                continue;
                }
            // add facet values
            foreach ($types as $name=>$value)
                {
                if (!isset($facets[$group][$name]))
                    $facets[$group][$name] = $value;
                else
                    $facets[$group][$name] += $value;
                }
            }
        return $facets;
        }
            
    // Make component-specific modifications to query string
    protected function _modify_query($query_string, $comp, $tree)
        {
        // use query adaptor if available
        if (isset($comp['adaptor']))
            {
            $adaptor = new ParsedQueryAdaptor();
            $tmp_meta = $comp['ds']->_get_meta($comp['table']);
            $converted_tree = $adaptor->convert($tree, $comp['adaptor'], @$tmp_meta['search']['index']);
            // if the converted tree is null, do not search this module
            if (is_null($converted_tree))
                return NULL;
            $query_string = $converted_tree->to_string();
            }
        // Additional query parts
        if (@$comp['query_add'])
            $query_string .= '('.$comp['query_add'].')';
        return $query_string;
        }
    
    // Get the sort configuration array for the aggregate search
    // FIXME: Gets first component's sort, possibly a better method could be used
    private function _get_sort_config($tree, $comp)
        {
        $sort_field = $tree->find('sort');
        $sort_field = @$sort_field[0]['subject'] != '' ? $sort_field[0]['subject'] : 'default';
        $tmp = $comp['ds']->retrieve($comp['table']);
        $result = @$tmp['search']['index']['sort.' . $sort_field];
        // Normalise type field
        if (!in_array(@$result['type'], Array('desc', 'rel')))
            $result['type'] = 'asc';
        return $result;
        }
        
    // Only search is implemented
    function create(&$ds, $url, $record) {}
    function retrieve(&$ds, $url) {}
    function update(&$ds, $url, $record) {}
    function delete(&$ds, $url) {}
    }

// Compare on _sort field
function _DS_AggregateStorage_cmp_asc($a, $b)
    {
    if (@$a['_sort'] == @$b['_sort'])
        return 0;
    else if (@$a['_sort'] > @$b['_sort'])
        return 1;
    else
        return -1;
    }

// Compare on _sort field
function _DS_AggregateStorage_cmp_desc($a, $b)
    {
    if (@$a['_sort'] == @$b['_sort'])
        return 0;
    else if (@$a['_sort'] < @$b['_sort'])
        return 1;
    else
        return -1;
    }

// Compare on _sort field * _weight field
function _DS_AggregateStorage_cmp_rel($a, $b)
    {
    $aa = (int)(@$a['_sort'] * $a['_weight'] * 1000);
    $bb = (int)(@$b['_sort'] * $b['_weight'] * 1000);
    if ($aa == $bb)
        return 0;
    else if ($aa < $bb)
        return 1;
    else
        return -1;
    }

// Helper class for performing searches. This version calls the search function directly
class AggregateStorageSearchBuffer
    {
    private $results = Array();
    
    // Add a search to the buffer
    function search($comp_name, $comp, $query_string, $offset, $max_count)
        {
        $module = $this->get_module($comp);
        $module->log(3, 'Fed search in ' . $module->name);
        $this->results[] = Array(
            'comp_name'=>$comp_name,
            'comp'=>$comp,
            'results'=>$comp['ds']->search($comp['table'], $query_string, $offset, $max_count),
            );
        }

    // Get the results of all queries, as an array
    //  'comp_name'=>, 'comp'=>, 'results'=>
    function get_results()
        {
        return $this->results;
        }
    
    //### FIXME: REALLY need a way to avoid having these resolve module functions all over the place
    protected function get_module($comp)
        {
        global $MODULE;
        $module = @$comp['module'];
        if (is_string($module))
            return Module::load($module);
        else if ($module != '')
            return $module;
        else
            return $MODULE;
        }
    }

// This search buffer uses curl_multi
// No unit tests as we can't test underlying curl implementation
// Based on http://www.rustyrazorblade.com/2008/02/curl_multi_exec/
class AggregateStorageCurlMultiSearchBuffer
    extends AggregateStorageSearchBuffer
    {
    private $ch_multi;
    private $curl_buffer = Array();
    function __construct()
        {
        $this->ch_multi = curl_multi_init();
        }
    
    function search($comp_name, $comp, $query_string, $offset, $max_count)
        {
        // Add search concurrently
        $module = $this->get_module($comp);
        $module->log(3, 'Fed concurrent search in ' . $module->name);
        $query_string = urlencode($query_string);
        $url = $module->url('ws', "{$comp['table']}?query=$query_string&offset=$offset&max_count=$max_count&format=php");
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($this->ch_multi, $ch);
        $this->results[] = Array(
            'comp_name'=>$comp_name,
            'comp'=>$comp,
            'ch'=>$ch
            );
        }

    function get_results()
        {
        // Execute the handles
        debug_start_timer();
        $active = null;
        do  {
            $mrc = curl_multi_exec($this->ch_multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) 
              {
              if (curl_multi_select($this->ch_multi) != -1) 
                 {
                 do  {
                     $mrc = curl_multi_exec($this->ch_multi, $active);
                     } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                 }
             }

        // Gather results and return
        $result = Array();
        foreach ($this->results as $info)
            {
            $content = curl_multi_getcontent($info['ch']);
            $info['results'] = unserialize($content);
            xlog(1, 'Bad/missing results from web service', 'FED DS');
            $result[] = $info;
            }
        debug_stop_timer("Duration (curl)");
        return $result;
        }
    }

