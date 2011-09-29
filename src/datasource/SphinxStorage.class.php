<?php
// $Id$
// MySQL storage for DataSource
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'parser/SqlGenerator.class.php');
require_once($CONF['path_src'] . 'parser/SphinxGenerator.class.php');
    
/// Sphinx storage handler -- search only, requires MySQL
/// Requires 'sphinx' to be set in the search block
/// If 'sphinx_key' is set in the table it will be used to match records when they are retrieved from MySQL
class DataSource_SphinxStorage
    {
    function DataSource_SphinxStorage(&$ds, &$database=NULL) //###
        {
        if (is_null($database))
            {
            $this->db = null;
            // check the DS config for the pear db object
            foreach ($ds->_data as $meta)
                {
                if (isset($meta['pear_db']))
                    $this->db = $meta['pear_db'];
                }
            if (!is_null($this->db))
                $database = new DS_Mysql_Database($this->db);
            }
        $this->database = $database;
        }

    function search(&$ds, $table, $tree, $offset, $max_count)
        {
        global $STRINGS;
        if (is_null($this->db))
            return $ds->_set_error(500, $STRINGS['error_500_db']);
        $meta = $ds->_get_meta($table);
        
        // Should we use sphinx at all with this table?
        if (!@$meta['search']['sphinx'])
            return NULL;
        
        // Check if sphinx is available on this system
        $sphinx_generator = $this->_new_sphinx_generator($meta);
        if (is_null($sphinx_generator))
            return NULL;
        
        // Get the Sphinx index to search in
        $sphinx_index = $this->_find_sphinx_index($sphinx_generator, $meta, $tree);
        
        // Get group by config
        $group_by = $this->_find_group_by($sphinx_generator, $meta, $tree);
        if (empty($group_by))
            $group_by = NULL;
        
        // Add main query
        $query_id = $sphinx_generator->add_query($sphinx_index, $tree, $offset, $max_count, $group_by);
        if (is_null($query_id))
            {
            if (@$sphinx_generator->error_message != '')
                $ds->_set_error(400, 'Sphinx generation error: ' . $sphinx_generator->error_message);
            return NULL;
            }
        
        // Add facets query
        $has_facets = isset($meta['facets']);
        if ($has_facets)
            $facets_query_id = $sphinx_generator->add_group_by($sphinx_index, 'facets');
        
        // Call Sphinx
        $all_sphinx_results = $this->_run_sphinx_queries($ds, $sphinx_generator, $query_id);
        if (is_null($all_sphinx_results))
            return NULL;
        $search_result = $all_sphinx_results[$query_id];
        
        // No results?
        if (!isset($search_result["matches"]))
            return $ds->_make_search_results_array(Array(), 0, 0, 0);

        // Process results
        $max_weight = 1.0;
        $sphinx_matches = Array();
        foreach ($search_result["matches"] as $sphinx_record)
            {
            $sphinx_matches[$sphinx_record['id']] = $sphinx_record;
            $max_weight = max(@$sphinx_record['weight'], $max_weight);
            }
        $ids = array_keys($sphinx_matches);
        
        // Get result data from mysql
        $sql_generator = $this->_new_sql_generator($meta);
        $sphinx_key = 't.id';
        if (@$meta['search']['sphinx_key'] != '')
            $sphinx_key = $meta['search']['sphinx_key'];
        $sql = $sql_generator->to_sql(NULL, 'none') . " WHERE $sphinx_key IN(" . join(',', $ids) . ")";
        xlog(4, $sql, 'SPHINX');
        $r = $this->db->queryAll($sql, NULL, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($r))
            return $ds->_set_error(500, 'Search: ' . $r->message . ' ' . $r->userinfo);
        
        // Results come back from MySQL in arbritrary order so we need to resort them
        $sort_field = $sphinx_generator->sort_field();
        foreach ($r as $record)
            {
            $id = $record['id'];
            $sphinx_record = $sphinx_matches[$id];
            if (isset($sphinx_record['attrs']['facet_details']))
                $record['facet_details'] = $sphinx_record['attrs']['facet_details'];
            // Handle sort field
            //### TODO: If not in aggregated search, we don't need this
            if ($sort_field == 'rel')
                $record['_sort'] = $sphinx_record['weight'] / $max_weight;
            else if ($sort_field == 'date')
                $record['_sort'] = strftime('%Y-%m-%d %H:%M:%S', $sphinx_record['attrs'][$sort_field]);
            // sorting by string ordinal, retrieve the actual string from the record data
            else if (strrpos($sort_field, '_ord'))
                {
                // check if the record contains a field matching the sort field (e.g. title_ord)
                // otherwise look for a field without the _ord suffix (e.g. title)
                if (isset($record[$sort_field]))
                    $record['_sort'] = $record[$sort_field];
                else
                    {
                    $record_field = substr($sort_field, 0, strrpos($sort_field, '_ord'));
                    $record['_sort'] = isset($record[$record_field]) ? $record[$record_field] : $sphinx_record['attrs'][$sort_field];
                    }
                }
            else if ($sort_field != '')
                $record['_sort'] = $sphinx_record['attrs'][$sort_field];
            $sphinx_matches[$id] = $record;
            }
        $search_result['processed_results'] = array_values($sphinx_matches);
        
        // Make results array to return
        $r = @$search_result['processed_results'];
        $total = @$search_result['total'];
        $result = $ds->_make_search_results_array($r, $total, $offset, $max_count);
        $result['total_found'] = @$search_result['total_found'];
        
        // Add facets
        if (isset($facets_query_id))
            $result['facets'] = $this->_get_facet_summary($ds, $meta, $all_sphinx_results[$facets_query_id], $total);
            
        return $result;
        }

    private function _new_sphinx_generator($meta)
        {
        $result = NULL;
        // To allow injection for tests
        if (@$this->sphinx_generator != NULL)
            $result = $this->sphinx_generator;
        else if (@$meta['search']['sphinx'])
            $result = new SphinxGenerator(@$meta['search']['index']);
        if (is_null(@$result->sphinx()))
            return NULL;
        else
            return $result;
        }

    //### FIXME: copied from Mysql Storage
    function _new_sql_generator($meta)
        {
        // To allow injection for tests
        if (@$this->generator != NULL)
            return $this->generator;
        $table = $meta['mysql_table'] . ' t';
        // Set default field list
        $tmp = @$meta['search']['fields'];
        // Default search
        if ($tmp == '')
            $tmp = Array("t.title,'' AS summary");
        //### ID added, not in mysql storage sql generator 
        $sphinx_key = 't.id';
        if (@$meta['search']['sphinx_key'] != '')
            $sphinx_key = $meta['search']['sphinx_key'];
        $tmp[] = $sphinx_key;
        $url_select = 't.';
        $url_select .= isset($meta['fields']['url']['select']) ? $meta['fields']['url']['select'] : 'id';
        $tmp[] = "CONCAT('/{$meta['key']}/', $url_select) AS url";
        $tmp[] = "'{$meta['key']}' AS _table";
        $fields = join(',', $tmp);
        $index_defs = @$meta['search']['index'];
        //### Replace the default join for sphinx if specified
        if (isset($meta['search']['index']['sphinx_join']))
            $index_defs['join'] = $index_defs['sphinx_join'];
        return new SqlGenerator($table, $fields, $index_defs);
        }

    // Get the Sphinx index to search in
    //### TODO: this should include module name!
    private function _find_sphinx_index($sphinx_generator, $meta, $tree)
        {
        $result = $sphinx_generator->find_sphinx_config_value($tree, 'sphinx_index');
        if ($result == '')
            $result = @$meta['search']['sphinx_index'];
        if ($result == '')
            $result = $meta['key'];
        return $result;
        }
    
    // Get the Sphinx group by config array
    private function _find_group_by($sphinx_generator, $meta, $tree)
        {
        $result = $sphinx_generator->find_sphinx_config_value($tree, 'sphinx_group_by');
        if (!is_array($result))
            $result = @$meta['search']['sphinx_group_by'];
         return $result;
        }

    // Run sphinx queries, return array of query results arrays, or NULL on error
    private function _run_sphinx_queries($ds, $sphinx_generator, $query_id)
        {
        // Call Sphinx
        $sphinx = $sphinx_generator->sphinx();
        $result = $sphinx->RunQueries();
        
        // Check for error in both sphinx client and first result set
        $error_message = $sphinx->GetLastError();
        if ($error_message != '')
            return $ds->_set_error($sphinx->IsConnectError() ? 500 : 400, 'Sphinx: ' . $error_message);
        $first_result = array_slice($result, 0, 1);
        $error_message = @$first_result[0]['error'];
        if ($error_message != '')
            return $ds->_set_error(400, 'Sphinx: ' . $error_message);
        return $result;
        }
    
    // Get the facets summary from a Sphinx groupby results set
    private function _get_facet_summary($ds, $meta, $facets_result, $total)
        {
        if (is_array(@$facets_result['matches']))
            {
            // Get the total result for each facet ID
            $facet_counts = Array();
            foreach ($facets_result['matches'] as $record_info)
                {
                $facet_id = $record_info['attrs']['@groupby'];
                $facet_counts[$facet_id] = $record_info['attrs']['@count'];
                }
            // Build the facets result array
            $result = Array();
            foreach ($meta['facets'] as $facet_id => $facet)
                {
                if (isset($facet_counts[$facet_id]))
                    $result[$facet['type']][$facet['name']] = $facet_counts[$facet_id];
                else if ($facet['select'] == 'all')
                    $result[$facet['type']][$facet['name']] = $total;
                }
            $result['accuracy'] = 'exact';
            return $result;
            }
        // Errors in facet search are non-fatal
        else if (@$facets_result['error'] != '')
            $ds->log(1, 'Sphinx facet search: ' . $facets_result['error']);
        }
        
/*### Facet functions -- may be needed???
    // Add facet details bitfield to sql search query
    // This will add a 'facet_details' field to the search results
    // Config spec:
    //   An array of values given as 'integer value' => 'comparison'
    //    e.g. '128' => 't.flag=1'
    //   Use 'all' to designate values for all records
    function _add_facet_details($sql, $details)
        {
        $facets = Array();
        foreach ($details as $value=>$detail)
            $facets[] = ($detail == 'all') ? $value : "IF($detail,$value,0)";
        $facets = join(' | ', $facets); // bitwise OR
        $sql = preg_replace('/ FROM/', ",$facets AS facet_details FROM", $sql);
        return $sql;
        }
###*/
    }
