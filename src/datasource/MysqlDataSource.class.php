<?php
// $Id$
// MySQL storage for DataSource
// James Fryer, 13 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'parser/SqlGenerator.class.php');

/// MySQL storage handler
class DataSource_MysqlStorage
    {
    function DataSource_MysqlStorage(&$ds, &$database=NULL)
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
        $generator = $this->_new_sql_generator($meta);
        $has_facets = isset($meta['facets']);
        $facets = NULL;
        $sql = $generator->convert($tree);
        if ($sql == '')
            return $ds->_set_error(400, $generator->error_message);
        // special case
        if ($max_count == 0)
            $r = Array();
        // default case, add LIMIT and perform query
        else {
            $sql_with_limit = $this->_add_limit($sql, $offset, $max_count, $has_facets);
            if ($has_facets)
                $sql_with_limit = $this->_add_facet_details($sql_with_limit, $meta['facets']);
            $ds->log(4, 'Search SQL: ' . $sql_with_limit);
            $r = $this->db->queryAll($sql_with_limit, NULL, MDB2_FETCHMODE_ASSOC);
            if (PEAR::isError($r))
                return $ds->_set_error(500, 'Search: ' . $r->message . ' ' . $r->userinfo);
            }
        if ($has_facets)
            $facets =  $this->_get_facet_summary($ds, $meta, $tree);
        $total = $this->_get_total_results($ds, $sql, $max_count, $facets);
        $result = $ds->_make_search_results_array($r, $total, $offset, $max_count);
        if ($has_facets)
            {
            $facets = $this->_fix_facet_totals($facets, $total);
            $result['facets'] = $facets;
            }
        return $result;
        }

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
        $url_select = 't.';
        $url_select .= isset($meta['fields']['url']['select']) ? $meta['fields']['url']['select'] : 'id';
        $tmp[] = "CONCAT('/{$meta['key']}/', $url_select) AS url";
        $tmp[] = "'{$meta['key']}' AS _table";
        $fields = join(',', $tmp);
        $index_defs = @$meta['search']['index'];
        return new SqlGenerator($table, $fields, $index_defs);
        }

    // Helper function to add SQL limit to query
    function _add_limit($sql, $offset, $max_count, $has_facets)
        {
        if ($this->_sql_use_found_rows($sql, $max_count) && !$has_facets)
            $sql = preg_replace('/^SELECT/', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
        $sql .= " LIMIT $offset, $max_count";
        return $sql;
        }

    // Helper function to get the total results from a query
    function _get_total_results(&$ds, $sql, $max_count, &$facets)
        {
        // if facet query was used, get total from that
        if (isset($facets['total']))
            {
            $total = $facets['total'];
            unset($facets['total']);
            return $total;
            }
        // use FOUND_ROWS with SQL_CALC_FOUND_ROWS, otherwise run the query again without limit
        if ($this->_sql_use_found_rows($sql, $max_count))
            $sql = "SELECT FOUND_ROWS();";
        else
            $sql = "SELECT count(*) FROM ($sql) qp_count_t";
        $r_count = $this->db->queryOne($sql);
        if (PEAR::isError($r_count))
            return $ds->_set_error(500, 'Search: ' . $r_count->message . ' ' . $r_count->userinfo);
        return $r_count;
        }

    // Helper function to determine if using SQL FOUND_ROWS
    function _sql_use_found_rows($sql, $max_count)
        {
        return (strpos($sql, " UNION ") === false && $max_count > 0);
        }
    
    // Add facet details bitfield to sql search query
    // This will add a 'facet_details' field to the search results
    function _add_facet_details($sql, $facet_defs)
        {
        $facets = Array();
        foreach ($facet_defs as $value=>$facet)
            {
            if ($value == 'count')
                continue;
            $facets[] = ($facet['select'] == 'all') ? $value : "IF($facet[select],$value,0)";
            }
        $facets = join(' | ', $facets); // bitwise OR
        if (!empty($facets))
            $sql = preg_replace('/ FROM/', ",$facets AS facet_details FROM", $sql);
        return $sql;
        }
    
    // Build and run the query to retrieve the facet summaries
    // Collate and return results
    // Config spec:
    //   -Each facet is an array with 'type', 'name', and a 'select' string
    //    (this does not include the special count field)
    //   -The 'select' field is a single comparison string for use in the
    //    summation function. Multiple comparisons can be combined with OR or
    //    AND within a single string. To designate a type that matches all values
    //    use 'all' as the string.
    //   -'count' can be specified to designate the database field to use for counting
    //    the total results. The default field used for counting is the designated 
    //    select field. This config parameter overrides the default (e.g. trilt)
    function _get_facet_summary(&$ds, $meta, $tree)
        {
        $index_defs = @$meta['search']['index'];
        $facet_defs = $meta['facets'];
        // remove the sort field
        unset($index_defs['sort.default']);
        // get count field
        if (isset($facet_defs['count']))
            {
            $count = $facet_defs['count'];
            unset($facet_defs['count']);
            }
        $fields = Array();
        // get all fields to sum
        foreach ($facet_defs as $facet)
            {
            if ($facet['select'] == 'all')
                continue;
            $fields[] = "SUM(IF($facet[select],1,0)) AS '$facet[name]'";
            }
        // add count to get total
        if (!isset($count))
            $count = isset($meta['fields']['url']['select']) ? 't.'.$meta['fields']['url']['select'] : 't.id';
        $fields[] = "COUNT(DISTINCT $count) AS total";
        $fields = join(',', $fields);
        $generator = new SqlGenerator($meta['mysql_table'].' t', $fields, $index_defs);
        $sql = $generator->convert($tree);
        $ds->log(4, 'Facets SQL: ' . $sql);
        $r = $this->db->queryAll($sql, NULL, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($r))
            return $ds->_set_error(500, 'Search (facet summary): ' . $r->message . ' ' . $r->userinfo);
        // typically only 1 row of results is returned, but for union searches multiple rows are returned
        // in this case sum all rows together - this can contain duplicate values, but we will live with it
        if (count($r) <= 1)
            $accuracy = 'exact';
        else {
            $accuracy = 'approx';
            $tmp = Array();
            for ($i = 0; $i < count($r); $i++)
                {
                foreach ($r[$i] as $name=>$value)
                    @$tmp[$name] += $value;
                }
            $r = Array($tmp);
            }
        // collate results
        $results = Array();
        foreach ($facet_defs as $facet)
            {
            if (!isset($results[$facet['type']]))
                $results[$facet['type']] = Array();
            if ($facet['select'] == 'all')
                $results[$facet['type']][$facet['name']] = $r[0]['total'];
            else
                $results[$facet['type']][$facet['name']] = $r[0][$facet['name']];
            }
        $results['accuracy'] = $accuracy;
        if ($accuracy == 'exact')
            $results['total'] = $r[0]['total'];
        return $results;
        }

    /// Fix facet totals
    /// This function checks each individual facet total, if the total is higher
    /// then the actual results total then it changes the facet total value.
    /// In essence this applies an upper bound to a facet total to help with
    /// situations where duplicate records are counted and the total is incorrect.
    function _fix_facet_totals($facets, $total)
        {
        // Only works on these facet types
        $facet_groups = Array('facet_media_type', 'facet_availability', 'facet_genre');
        foreach ($facet_groups as $group)
            {
            if (isset($facets[$group]))
                {
                foreach ($facets[$group] as $name=>$value)
                    $facets[$group][$name] = min($value, $total);
                }
            }
        return $facets;
        }

    function create(&$ds, $url, $record)
        {
        global $STRINGS,$CONF;
        if (is_null($this->database))
            return $ds->_set_error(500, $STRINGS['error_500_db']);
        // Get table metadata
        $meta = $ds->_get_meta($url);
        // Insert the item
        $record = $this->_replace_fields(&$ds, $meta, $record);
        $id = $this->database->insert($meta, $record);
        if (is_null($id))
            return $this->_set_error($ds, 'Create');
        // Handle special fields
        foreach ($record as $name=>$value)
            {
            if (((!is_array($value) && $value != '') || (is_array($value) && !empty($value)))
                && $this->database->is_related($meta, $name))
                {
                $other_meta = $this->_get_related_meta($ds, $meta, $name);
                $related_ids = Array();
                for ($i=0; $i < count($other_meta); $i++)
                    {
                    // Create the related items
                    $related_ids[$i] = $this->database->insert_related($meta, $other_meta[$i], $id, $value, $i);
                    if (is_null($related_ids[$i]))
                        return $this->_set_error($ds, 'Create related ('.$name.')');
                    }
                // Link this item to the related items
                if (!$this->database->insert_links($meta, $other_meta[0], $id, $related_ids, $value))
                    return $this->_set_error($ds, 'Link related ('.$name.')');
                }
            }
        // Return the new item's URL
        $new_record = $this->database->select($meta, $id);
        return $new_record['url'];
        }

    function retrieve(&$ds, $url)
        {
        global $STRINGS, $CONF;
        if (is_null($this->database))
            return $ds->_set_error(500, $STRINGS['error_500_db']);
        $meta = $ds->_get_meta($url);
        $result = $this->database->select($meta);
        if (is_null($result))
            return $this->_set_error($ds, 'Retrieve');
        // Handle special fields
        foreach (array_keys($meta['fields']) as $name)
            {
            if ($this->database->is_related($meta, $name))
                {
                $other_meta = $this->_get_related_meta($ds, $meta, $name);
                $r = $this->database->select_related($meta, $other_meta[0], $result['id']);
                if (is_null($r))
                    return $this->_set_error($ds, 'Retrieve related');
                $result[$name] = $r;
                }
            }
        return $result;
        }

    function update(&$ds, $url, $record)
        {
        if (is_null($this->database))
            return $ds->_set_error(500, $STRINGS['error_500_db']);
        $meta = $ds->_get_meta($url);
        $record = $this->_replace_fields(&$ds, $meta, $record);
        $id = $this->database->update($meta, $record);
        if (!$id)
            return $this->_set_error($ds, 'Update');
        foreach ($record as $name=>$value)
            {
            if ($this->database->is_related($meta, $name))
                {
                $other_meta = $this->_get_related_meta($ds, $meta, $name);
                if (!$this->database->delete_related($meta, $other_meta[0], $id, $value))
                    return $this->_set_error($ds, 'Update/delete related');
                $related_ids = Array();
                for ($i=0; $i < count($other_meta); $i++)
                    {
                    $related_ids[$i] = $this->database->insert_related($meta, $other_meta[$i], $id, $value, $i);
                    if (is_null($related_ids[$i]))
                        return $this->_set_error($ds, 'Update/insert related ('.$name.')');
                    }
                if (!$this->database->delete_links($meta, $other_meta[0], $id))
                    return $this->_set_error($ds, 'Update/delete links');
                if (!$this->database->insert_links($meta, $other_meta[0], $id, $related_ids, $value))
                    return $this->_set_error($ds, 'Update/insert links');
                }
            }
        // Return TRUE on success
        return $id != 0;
        }

    function delete(&$ds, $url)
        {
        if (is_null($this->database))
            return $ds->_set_error(500, $STRINGS['error_500_db']);
        $meta = $ds->_get_meta($url);
        // Get item ID
        $tmp = $this->database->select($meta);
        if (is_null($tmp))
            return $ds->_set_error(404, 'Delete: not found');
        $id = $tmp['id'];
        // Handle special fields first
        foreach (array_keys($meta['fields']) as $name)
            {
            if ($this->database->is_related($meta, $name))
                {
                $other_meta = $this->_get_related_meta($ds, $meta, $name);
                if (!$this->database->delete_links($meta, $other_meta[0], $id))
                    return $this->_set_error($ds, 'Delete links');
                if (!$this->database->delete_related($meta, $other_meta[0], $id))
                    return $this->_set_error($ds, 'Delete related');
                }
            }
        $result = $this->database->delete($meta, $id);
        if (!$result)
            return $this->_set_error($ds, 'Delete');
        return $result;
        }

    // Call 'replace_field' on fields which require it
    function _replace_fields(&$ds, $meta, $record)
        {
        foreach ($record as $name=>$value)
            {
            if ($this->database->is_replaceable($meta, $name))
                {
                if ($value != '')
                    {
                    $other_meta = $this->_get_related_meta($ds, $meta, $name);
                    $id = $this->database->replace_field($meta, $other_meta[0], $name, $value);
                    $record[$name] = Array('id'=>$id, 'value'=>$value);
                    }
                else if (@$meta['fields'][$name]['default_id'] != '')
                    $record[$name] = $meta['fields'][$name]['default_id'];
                }
            }
        return $record;
        }


    // Copy the database error to the DS
    function _set_error(&$ds, $message)
        {
        if ($this->database->error_code)
            $ds->_set_error($this->database->error_code, $message . ': ' . $this->database->error_message);
        }

    // Get a related table's meta, making adjustments for aliasing
    // Preserve the unaliased key in 'real_key' field
    // Returns an array of metas, functions expecting a single table 
    // meta should simply use $results[0]
    function _get_related_meta(&$ds, $meta, $field_name)
        {
        if (@$meta['fields'][$field_name]['related_to'] != '')
            $table_name = $meta['fields'][$field_name]['related_to'];
        else
            $table_name = $field_name;
        if (!is_array($table_name))
            $table_name = Array($table_name);
        $results = Array();
        foreach ($table_name as $table)
            {
            $result = $ds->_get_meta($table);
            if ($result['key'] != $field_name)
                {
                $result['real_key'] = $result['key'];
                $result['key'] = $field_name;
                }
            $results[] = $result;
            }
        return $results;
        }
    }

/// Helper class which provides a database abstraction layer for the storage
/// Each primitive operation works on a single DB_Table object
class DS_Mysql_Database
    {
    /// Error details for propagation to DS
    var $error_code = 0;
    var $error_message = NULL;

    function DS_Mysql_Database(&$db, &$mock_db_table=NULL)
        {
        $this->db =& $db;
        $this->mock_db_table =& $mock_db_table;
        }

    /// Is the field related to another table?
    function is_related($meta, $field)
        {
        $field_type = @$meta['fields'][$field]['type'];
        return $field_type == 'one_to_many' || $field_type == 'many_to_many';
        }

    /// Does the field value need to be replaced?
    function is_replaceable($meta, $field)
        {
        $field_type = @$meta['fields'][$field]['type'] == 'many_to_one';
        $has_lookup = @$meta['fields'][$field]['lookup'] != '';
        return $field_type && $has_lookup;
        }

    /// Insert a record into a table
    /// Return the id of the new record, or NULL on error
    function insert($meta, $record)
        {
        $db_table = $this->_new_db_table($meta);
        return $this->_insert_helper($meta, $db_table, $record);
        }

    // Does the actual work of insertion, used by insert and insert_related
    function _insert_helper($meta, &$db_table, $record)
        {
        // Fix up scalars
        if (!is_array($record))
            $record = Array($record);

        // Filter fields which are not in db_table cols
        $db_record = Array();
        $col_names = array_keys($db_table->col);
        foreach ($record as $field=>$value)
            {
            // If the key is an integer, try to fix it up
            if (is_int($field))
                $field = @$col_names[$field+1]; //+1 to skip 'id'
            // handle many_to_one arrays (store id and value)
            if (@$meta['fields'][$field]['type'] == 'many_to_one' 
                && is_array($value) && isset($value['id']))
                {
                $record[$field] = $value['value'];
                $value = $value['id'];
                }
            // Change field name to match database if required
            if (isset($meta['fields'][$field]['mysql_field']))
                $field = $meta['fields'][$field]['mysql_field'];
            if (isset($db_table->col[$field]))
                $db_record[$field] = $value;
            }

        // Handle special fields
        foreach ($meta['fields'] as $field=>$info)
            {
            if (@$info['type'] == 'implode')
                $db_record[$field] = $this->_implode_field($info['keys'], $record, @$info['implode']);
            // check for a default value for missing fields, this helps for inserting related tables
            if (!isset($db_record[$field]) && isset($info['default']))
                $db_record[$field] = $info['default'];
            }

        // check if the id was already set from the record array, if not then generate it
        if (!isset($db_record['id']) || empty($db_record['id']))
            {
            // Get ID for record
            $next_id = $db_table->nextId();
            if (PEAR::isError($next_id))
                return $this->_set_error($next_id);
            $db_record['id'] = $next_id;
            }

        // Handle URL field
        if ($db_table->url_field != 'id')
            {
            // Check for dupe URL
            $requested_url = $meta['path_info'];
            if ($this->_select_id($meta, $db_table))
                $requested_url .= $db_record['id'];
            $db_record[$db_table->url_field] = $requested_url;
            }

        // Insert the data
        $r = $db_table->insert($db_record);
        if (PEAR::isError($r))
            return $this->_set_error($r);

        // Return the new ID
        return $db_record['id'];
        }

    /// Insert related records
    /// Return an array containing new record IDs, or NULL on error
    function insert_related($meta, $other_meta, $id, $records, $index=0)
        {
        $db_table = $this->_new_db_table($other_meta);
        $related_field = $other_meta['key'];
        $related_info = @$meta['fields'][$related_field];
        if ($related_info == '')
            return NULL;
        $result = Array();
        // Split the records field if necessary
        if ($records == '')
            $records = Array();
        else if (@$related_info['split'] != '' && !is_array($records))
            {
            $records = split($related_info['split'], $records);
            foreach ($records as $key=>$value)
                {
                if (!trim($value))
                    unset($records[$key]);
                }
            }
        foreach ($records as $record)
            {
            // Optionally look for existing items
            $new_id = 0;
            if ($related_info['type'] == 'many_to_many' && @$related_info['lookup'] != '')
                {
                if (is_array($related_info['lookup']))
                    $field = $related_info['lookup'][$index];
                else
                    $field = $related_info['lookup'];
                // substitute mapped fields
                if (isset($related_info['related_field_map']))
                    {
                    foreach ($related_info['related_field_map'][$index] as $original=>$map)
                        {
                        if (isset($record[$original]))
                            $record[$map] = $record[$original];
                        }
                    }
                // Try to use the lookup field, otherwise use the first value in the array, or scalar if not an array
                if (!is_array($record))
                    $value = $record;
                else if (array_key_exists($field, $record))
                    $value = $record[$field];
                else {
                    $tmp = array_slice($record, 0, 1);
                    $value = $tmp[0];
                    }
                $new_id = $this->_select_id($other_meta, $db_table, $field, $value);
                }
            else if ($related_info['type'] == 'one_to_many')
                {
                $foreign_key = $related_info['foreign_key'];
                $record[$foreign_key] = $id;
                }
            // check permmissions
            if (!$new_id && !@$other_meta['mutable'])
                {
                xlog(1, "Error: 405 Insert related: method not allowed", 'DATASOURCE');
                continue;
                }
            // Only insert if we haven't found the item already
            if (!$new_id)
                $new_id = $this->_insert_helper($other_meta, $db_table, $record);
            if (!$new_id)
                return NULL;
            $result[] = $new_id;
            }
        return $result;
        }

    /// Insert links to related records in $other_meta
    /// Return TRUE on success
    function insert_links($meta, $other_meta, $id, $related_ids, $records=NULL)
        {
        $related_field = $other_meta['key'];
        $related_info = $meta['fields'][$related_field];
        if ($related_info['type'] != 'many_to_many' || empty($related_ids))
            return TRUE;
        if (isset($related_ids[0]) && empty($related_ids[0]))
            return TRUE;
        // get additional columns for the link table
        $columns = (isset($related_info['link_columns'])) ? $related_info['link_columns'] : Array();
        $total_keys = count($related_info['keys']);
        // Uses DB rather than DB_Table and generates SQL
        $sql = "INSERT INTO {$related_info['link']} ({$related_info['keys'][0]}";
        for ($i=1; $i < $total_keys; $i++)
            $sql .= ",".$related_info['keys'][$i];
        foreach ($columns as $column)
            $sql .= ",".$column;
        $sql .= ") VALUES ";
        $tmp = Array();
        $total_ids = count($related_ids[0]);
        for ($i = 0; $i < $total_ids; $i++)
            {
            $values = "($id";
            for ($j = 0; $j < $total_keys - 1; $j++)
                {
                $r_id = $related_ids[$j][$i];
                $r_id = is_numeric($r_id) ? $r_id : "'$r_id'";
                $values .= ",$r_id";
                }
            // add additional columns values
            foreach ($columns as $column)
                {
                if (!is_null($records) && isset($records[$i][$column]))
                    {
                    $val = $records[$i][$column];
                    $val = is_numeric($val) ? $val : $this->db->quote($val);
                    $values .= ",".$val;
                    }
                else
                    $values .= ",NULL";
                }
            $values .= ")";
            $tmp[] = $values;
            }
        $sql .= join(',', array_unique($tmp));
        $r = $this->db->query($sql);
        if (PEAR::isError($r))
            return $this->_set_error($r);
        return TRUE;
        }

    /// Get a single record or NULL on error
    /// Optional $id param overrides $meta
    function select($meta, $id=NULL)
        {
        $db_table = $this->_new_db_table($meta);

        // Build the select criteria
        $ds_table_name = $meta['key'];
        $select = Array();
        // Add the columns
        foreach ($db_table->col AS $field=>$info)
            {
            if (!@$info['hide'])
                $select[] = $db_table->table . '.' . $field;
            }
        $join = Array();
        // Add special fields
        foreach($meta['fields'] as $field=>$info)
            {
            if (@$info['type'] == 'many_to_one')
                {
                $join[] = $info['join'];
                $select[] = $info['select'];
                }
            else if (@$info['type'] == 'const')
                {
                $value = addslashes($info['value']);
                $select[] = "'$value' AS $field";
                }
            else if (@$info['type'] == 'sql')
                $select[] = "{$info['value']} AS $field";
            }
        //### TODO: extra joins
        $select[] = "CONCAT('/$ds_table_name/', {$db_table->table}.{$db_table->url_field}) AS url";
        $select[] = "'$ds_table_name' AS _table"; //### FIXME: should be removed

        // Set up the SQL query array
        $query = Array();
        $query['select'] = join(',', $select);
        if ($meta['path_info'] == '!random')
            {
            //### TODO: This should be factored into separate function?
            $query['where'] = "{$db_table->table}.id >= random_id";
            $query['order'] = "{$db_table->table}.id ASC";
            $join[] = "JOIN (SELECT (RAND() * (SELECT MAX(id) FROM {$db_table->table})) AS random_id) AS TRAND";
            }
        else
            $query['where'] = $this->_where_clause($meta, $db_table, $id);
        if ($join)
            $query['join'] = join(' ', $join);
        $query['get'] = 'row';

        // Execute the query
        xlog(4, "Select: " . print_r($query,1), 'DATASOURCE');
        $result = $db_table->select($query, NULL, NULL, 0, 1);
        if (PEAR::isError($result))
            return $this->_set_error($result);
        return $result;
        }

    /// Get related records in $other_meta or NULL on error
    function select_related($meta, $other_meta, $id)
        {
        $db_table = $this->_new_db_table($other_meta);
        $related_field = $other_meta['key'];
        $related_info = $meta['fields'][$related_field];

        // Build the select criteria
        $ds_table_name = isset($other_meta['real_key']) ? $other_meta['real_key'] : $related_field;
        $select = Array($related_info['select']);
        $select[] = "CONCAT('/$ds_table_name/', {$db_table->table}.{$db_table->url_field}) AS url";
        $select[] = "'$ds_table_name' AS _table"; //### FIXME: should be removed

        // Set up the SQL query array
        $query = Array(
            'select' => join(',', $select),
            );

        // Handle different relation types
        if ($related_info['type'] == 'many_to_many')
            {
            $foreign_key = $related_info['keys'][0];
            // check for join, if set then use it, otherwise build it
            if (isset($related_info['join']))
                $query['join'] = $related_info['join'];
            else
                $query['join'] = "JOIN $related_info[link] ON $related_info[link].{$related_info['keys'][1]}=".
                                 "{$db_table->table}.{$db_table->url_field}";
            $query['where'] = "{$related_info['link']}.$foreign_key=$id";
            }
        else {
            $foreign_key = $related_info['foreign_key'];
            $query['where'] = "{$db_table->table}.$foreign_key=$id";
            }
        if (isset($related_info['where']))
            $query['where'] .= " AND $related_info[where]";
        if (isset($related_info['group']))
            $query['group'] = $related_info['group'];
        if (isset($related_info['order']))
            $query['order'] = $related_info['order'];
        $query['get'] = isset($related_info['get']) ? $related_info['get'] : 'all';

        // Execute the query
        xlog(4, "Select related: " . print_r($query,1), 'DATASOURCE');
        $result = $db_table->select($query, NULL, NULL, @$related_info['limit'][0], @$related_info['limit'][1]);
        if (PEAR::isError($result))
            return $this->_set_error($result);
        return $result;
        }

    /// Update a table record
    /// Return the ID of the changed record, or NULL on error
    function update($meta, $record)
        {
        // Fix up scalars
        if (!is_array($record))
            $record = Array($record);
        $db_table = $this->_new_db_table($meta);

        // Filter fields which are not in db_table cols
        // ID and URL fields are never updated
        $db_record = Array();
        $col_names = array_keys($db_table->col);
        foreach ($record as $field=>$value)
            {
            // If the key is an integer, try to fix it up
            if (is_int($field))
                $field = @$col_names[$field+1]; //+1 to skip 'id'
            // handle many_to_one arrays (store id and value)
            if (@$meta['fields'][$field]['type'] == 'many_to_one' 
                && is_array($value) && isset($value['id']))
                {
                $record[$field] = $value['value'];
                $value = $value['id'];
                }
            // Change field name to match database if required
            if (isset($meta['fields'][$field]['mysql_field']))
                $field = $meta['fields'][$field]['mysql_field'];
            if (isset($db_table->col[$field]) && $field != 'id' && $field != $db_table->url_field)
                $db_record[$field] = $value;
            }

        // Handle special fields
        foreach ($meta['fields'] as $field=>$info)
            {
            if (@$info['type'] == 'implode')
                $db_record[$field] = $this->_implode_field($info['keys'], $record, @$info['implode']);
            }

        $r = $db_table->update($db_record, $this->_where_clause($meta, $db_table));
        if (PEAR::isError($r))
            return $this->_set_error($r);
        $result = $this->_select_id($meta, $db_table);
        if (PEAR::isError($result))
            return $this->_set_error($result);
        return $result;
        }

    /// Delete a record
    function delete($meta, $id=NULL)
        {
        $db_table = $this->_new_db_table($meta);
        $result = $db_table->delete($this->_where_clause($meta, $db_table, $id));
        if (PEAR::isError($result))
            return $this->_set_error($result);
        return TRUE;
        }

    /// Delete related records in $other_meta
    function delete_related($meta, $other_meta, $id, $records=NULL)
        {
        $db_table = $this->_new_db_table($other_meta);
        $related_field = $other_meta['key'];
        $related_info = $meta['fields'][$related_field];
        if ($related_info['type'] == 'many_to_many')
            return TRUE;
        // check permissions
        if (!@$other_meta['mutable'])
            {
            xlog(1, "Error: 405 Delete related: method not allowed", 'DATASOURCE');
            return TRUE; // fail silently
            }
        // if keep_related flag is set only delete the specified records, not all related records
        if (@$related_info['keep_related'] && !is_null($records) && isset($related_info['lookup']))
            {
            if (!is_array($records))
                $records = Array($records);
            foreach ($records as $record)
                {
                $field = $related_info['lookup'];
                if (is_array($record))
                    $value = $record[$field];
                else
                    $value = $record;
                $result = $db_table->delete("{$db_table->table}.{$related_info['foreign_key']}=$id AND $field='$value'");
                if (PEAR::isError($result))
                    return $this->_set_error($result);
                }
            }
        else
            {
            $result = $db_table->delete("{$db_table->table}.{$related_info['foreign_key']}=$id");
            if (PEAR::isError($result))
                return $this->_set_error($result);
            }
        return TRUE; //### error handling $result;
        }

    /// Delete links to records in $other_meta
    function delete_links($meta, $other_meta, $id)
        {
        $related_field = $other_meta['key'];
        $related_info = $meta['fields'][$related_field];
        if ($related_info['type'] != 'many_to_many')
            return TRUE;

        // Uses DB rather than DB_Table and generates SQL
        $sql = "DELETE FROM {$related_info['link']} WHERE {$related_info['keys'][0]}=$id";
        $r = $this->db->query($sql);
        if (PEAR::isError($r))
            return $this->_set_error($r);
        return TRUE;
        }

    /// Replace a field, e.g. by looking up a database ID
    /// Return the replaced field value
    function replace_field($meta, $other_meta, $name, $value)
        {
        //### FIXME: what if $value is an array? bail out for now
        if (is_array($value))
            return $value;
        $db_table = $this->_new_db_table($other_meta);
        $related_field = $other_meta['key'];
        $related_info = $meta['fields'][$related_field];
        $lookup = $related_info['lookup'];
        $result = $this->_select_id($other_meta, $db_table, $lookup, $value);
        if (!$result)
            {
            $record = Array($lookup=>$value);
            $result = $this->_insert_helper($other_meta, $db_table, $record);
            }
        return $result;
        }

    // Conversion to DB_Table columns
    var $allowed_cols = Array(
        'char'=>NULL,
        'varchar'=>NULL,
        'smallint'=>NULL,
        'integer'=>NULL,
        'bigint'=>NULL,
        'decimal'=>NULL,
        'double'=>NULL,
        'boolean'=>NULL,
        'date'=>NULL,
        'time'=>NULL,
        'timestamp'=>NULL,
        'clob'=>NULL,
        'text'=>'clob', // Type conversion
        'implode'=>'clob',
        'many_to_one'=>'integer',
        'one-to-one'=>'integer'
        );

    function &_new_db_table($meta)
        {
        if (is_null($meta))
            return $meta;
        if (!isset($meta['mysql_table']))
            { $tmp = NULL; return $tmp;}
        // Hack for unit tests
        if (isset($this->mock_db_table))
            {
            $result = $this->mock_db_table;
            $result->table = $meta['mysql_table'];
            }
        else
            $result = new DB_Table($this->db, $meta['mysql_table']);
        $result->fetchmode = MDB2_FETCHMODE_ASSOC;

        // Set up configuration
        $result->col = Array();
        $result->col['id'] = Array('type'=>'integer');
        $result->url_field = 'id';
        foreach ($meta['fields'] as $name=>$field_info)
            {
            $type = @$field_info['type'];

            // URL field handled as special case
            if ($name == 'url')
                {
                $name = @$field_info['select'];
                $result->url_field = $name;
                $result->col[$name] = Array('type'=>'varchar', 'size'=>255);
                }

            // Other fields copied if they are permitted type
            else if (array_key_exists($type, $this->allowed_cols))
                {
                // Handle field aliasing
                if (isset($field_info['mysql_field']))
                    $name = $field_info['mysql_field'];
                // Horrible special case for avoiding M:1 relations without mysql_field
                if (!isset($field_info['mysql_field']) && $type == 'many_to_one')
                    continue;
                $result->col[$name] = $field_info;
                // Convert type
                if ($this->allowed_cols[$type] != '')
                    $result->col[$name]['type'] = $this->allowed_cols[$type];
                }
            }
        return $result;
        }

    // Get a WHERE clause for the table
    function _where_clause($meta, $db_table, $id=NULL)
        {
        if ($id)
            {
            $url_field = 'id';
            $url_value = $id;
            }
        else {
            $url_field = $db_table->url_field;
            $url_value = $meta['path_info'];
            }
        return "{$db_table->table}.$url_field='$url_value'";
        }

    // Get the ID of a table item from its meta[path_info]
    function _select_id($meta, $db_table, $where_field=NULL, $where_value=NULL)
        {
        if (is_null($meta) || is_null($db_table))
            return NULL;
        if ($where_field == '')
            $where_field = $db_table->url_field;
        if ($where_value == '')
            $where_value = $meta['path_info'];
        $where_value = addslashes($where_value);
        $query = Array(
            'select' => $db_table->table . '.id',
            'where'=> "{$db_table->table}.$where_field='$where_value'",
            'get'=>'one',
            );
        $result = $db_table->select($query);
        if (PEAR::isError($result))
            return $this->_set_error($result);
        return $result;
        }

    // Build a field with type=implode
    function _implode_field($keys, $record, $str)
        {
        $tmp = Array();
        foreach ($keys as $key)
            {
            $value = @$record[$key];
            if (empty($value))
                continue;
            else if (is_array($value))
                {
                if (is_array($value[0]))
                    {
                    foreach ($value as $a)
                        $tmp = array_merge($tmp, array_values($a));
                    }
                else
                    $tmp = array_merge($tmp, array_values($value));
                }
            else
                $tmp[] = $value;
            }
        return implode($str, $tmp);
        }

    // Set error code/message from a PEAR error object
    function _set_error($pear_error, $error_code=500)
        {
        $this->error_code = $error_code;
        $this->error_message = $pear_error->message . ' ' . $pear_error->userinfo;
        }
    }
