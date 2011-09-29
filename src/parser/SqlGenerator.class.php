<?php
// $Id$
// QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'parser/BooleanQueryRewriter.class.php');

/// Given the output of QP_Tree->flatten(), this class will generate an SQL query
/// To configure the converter, pass the following parameters to the constructor:
///     $main_table: The main table to search (e.g. xxx in SELECT ... FROM xxx)
///     $select_fields: The comma-separated list of fields (yyy in SELECT yyy FROM ...)
///     $index_defs: An array of index definitions. The key is the index name
///       as used in the input query. This points to an array containing:
///         type: How the index will be expanded. Can be 'string', 'number'
///             or 'fulltext'. Note that slashes will be escaped.
///         fields: The field(s) that this index maps to. Usually a single field unless
///             the type is fulltext, in which case it can be a comma-separated list.
///         join: One or more JOIN statements required by the tables in the fields element.
///             These need the full JOIN, LEFT JOIN, etc. keywords. Multiple joins should be
///             stored in an array. If different indexes share the same join, it will appear
///             only once in the output provided they are textually identical.
///         exists: SQL to be used in an EXISTS statement. This is used instead of a JOIN
///             when a NOT operator is converted. Include this in M:M tables, e.g. if you have
///             a Keywords table and you need to exclude records with {...}NOT{keyword=foo}
///             Needs the table name, JOINs and a WHERE statement correlating to the outer query
///             ### TODO: Could this be combined with join in some way? The effect is to write the same thing twice
///     If an index definition starts with 'sort.' then it is used as the subject of a sort= clause.
///         In this case the type can be 'asc' or 'desc'. E.g. the index sort.foo will be used
///         when the clause {sort=foo} is seen. The definition 'sort.default' will be used if present
class SqlGenerator
    {
    // The main table select statements will use
    var $main_table;

    // The field list to return from SELECT statements
    var $select_fields;

    // Defines the permitted indexes and how they will be handled
    var $index_defs;

    // The sort definition (NULL if none)
    var $sort_def;

    // Set if DISTINCT is to be added to the SELECT statement
    var $need_distinct;

    // Set if an error occurs
    var $error_message;

    function __construct($main_table, $select_fields, $index_defs)
        {
        $this->main_table = $main_table;
        $this->select_fields = $select_fields;
        $this->index_defs = $index_defs;
        }
    
    /// Convert a ParsedQuery object to a MySQL string
    /// The sort field is extracted, the tree is normalised and flattened, and to_sql is called
    function convert($tree)
        {
        $sort = $tree->find('sort');
        $sort = @$sort[0]['subject'];
        $tree->remove('sort');
        $tree->normalise();
        $clauses = $tree->flatten();
        if ($tree->error_message != '')
            return $this->_set_error($tree->error_message);
        return $this->to_sql($clauses, $sort);
        }
        
    /// Convert a flattened list of clauses to an SQL statement
    /// Default is to return an SQL statement for all records search
    /// Sort will be added unless 'none' is specified
    /// Return the SQL or NULL on error
    function to_sql($clause_sets=NULL, $sort=NULL)
        {
        // Get sort definition, if any
        if ($sort && !isset($this->index_defs['sort.' . $sort]) && $sort != 'none')
            return $this->_set_error('Invalid sort index');
        if ($sort == '')
            $sort = 'default';
        $this->sort_def = @$this->index_defs['sort.' . $sort];
        $this->need_distinct = @$this->sort_def['join'] != '';
        // Get the SQL
        if (is_null($clause_sets))
            {
            // Get any global JOIN fragments used for all queries
            $join_parts = Array();
            $this->_collect_join_parts($join_parts, $this->index_defs);
            $join_sql = join(' ', array_keys($join_parts));
            if ($join_sql != '')
                $join_sql = ' ' . $join_sql;
            $result = $this->_sql_select_prefix() . $join_sql;
            }
        else {
            $this->error_message = '';
            if (count($clause_sets) == 0)
                return $this->_set_error('No clause groups');
            $sql_queries = Array();
            foreach ($clause_sets as $clauses)
                $sql_queries[] = $this->_clauses_to_sql($clauses);
            if (count($sql_queries) == 1)
                $result = $sql_queries[0];
            else {
                $this->need_distinct = FALSE; // Because UNION implies DISTINCT
                $result = '(' . join(') UNION (', $sql_queries) . ')';
                }
            }
        // Add DISTINCT, ORDER BY if required
        if ($result != '')
            {
            if ($this->need_distinct)
                $result = ereg_replace('^SELECT', 'SELECT DISTINCT', $result);
            if ($this->sort_def != '')
                {
                $result .= ' ORDER BY _sort' . ($this->sort_def['type'] == 'rel' | $this->sort_def['type'] == 'desc' ? ' DESC' : '');
                // add any additional sort fields
                if (@$this->sort_def['additional_sort'] != '')
                    $result .= ', ' . $this->sort_def['additional_sort'];
                // add secondary sort for relevance search
                if ($this->sort_def['type'] == 'rel' && isset($this->index_defs['sort.default']))
                    $result .= ', _sort2' . ($this->index_defs['sort.default']['type'] == 'desc' ? ' DESC' : '');
                }
            }
        return $result;
        }

    function _clauses_to_sql($clauses)
        {
        if (count($clauses) == 0)
            return $this->_set_error('No query clauses');
        // Gather the JOIN and WHERE statements for each clause
        $join_parts = Array();
        $default_join = Array();
        // Get any global JOIN fragments used for all queries
        $this->_collect_join_parts($default_join, $this->index_defs);
        $select_fields = Array();
        $where_parts = Array();
        $first = TRUE;
        foreach ($clauses as $clause)
            {
            // Get the Boolean operator applying to this clause
            if ($first)
                {
                // Ignore first operator
                $where = '';
                $first = FALSE;
                }
            else if ($clause['oper'] == 'NOT')
                $where = 'AND NOT ';
            else
                $where = 'AND ';

            // Which index are we considering?
            $index = @$clause['index'];
            $index_def = @$this->index_defs[$index];
            if ($index_def == '')
                return $this->_set_error('Unknown index: '. $index);

            // special type, replace_join
            // this removes the default join and replaces it
            if (@$index_def['type'] == 'replace_join')
                {
                $default_join = Array();
                $this->_collect_join_parts($default_join, $index_def);
                continue;
                }
            
            // if select columns have been defined, add them to the running total
            if( isset($index_def['select']) )
                $select_fields = array_merge( $select_fields, preg_split("/[,]/", $index_def['select'] ) );
            
            // Expand the clause to an SQL WHERE fragment
            $type = $index_def['type'];
            $handler = '_expand_' . $type;
            $fields = @$index_def['fields'];
            if (method_exists($this, $handler))
                $where_fragment = $this->$handler($fields, $clause['relation'], $clause['subject']);
            else
                return $this->_set_error('Unknown type: '. $type);

            // Handle 'exists'
            if (@$index_def['exists'] != '' && ($clause['oper'] == 'NOT' || @$index_def['join'] == ''))
                {
                $where .= "EXISTS(SELECT * FROM {$index_def['exists']} AND $where_fragment)";
                $where_parts[] = $where;
                }

            // Default case, use joins
            else {
                $where .= $where_fragment;
                $where_parts[] = $where;

                // if this was a fulltext search, do relevance sort check
                if ($type == 'fulltext' && @$this->sort_def['type'] == 'rel' && !isset($this->sort_def['fields']))
                    {
                    // add the relevance fields based on this fulltext clause
                    $this->sort_def['fields'] = $this->_expand_relevance($fields, $clause['relation'], $clause['subject']);
                    }

                // Get any JOIN fragments
                $this->_collect_join_parts($join_parts, $index_def);

                // Only add DISTINCT if we have a JOIN
                if (@$index_def['join'] != '')
                    $this->need_distinct = TRUE;
                }
            }
        // Get JOIN fragments needed for sort
        $this->_collect_join_parts($join_parts, $this->sort_def);
        $join_parts = $default_join + $join_parts;
        $join_sql = join(' ', array_keys($join_parts));
        $where_sql = (!empty($where_parts)) ? 'WHERE ' . join(' ', $where_parts) : '';
        if ($join_sql != '' && $where_sql != '')
            $join_sql .= ' ';
        return $this->_sql_select_prefix($select_fields) . ' ' . $join_sql . $where_sql;
        }

     // Get the first part of the SELECT statement
     function _sql_select_prefix( $fields=NULL )
        {
        // Merge the incoming select fields with the existing fields defined on the instance
        $select_fields = Array();
        if( is_null($fields) )
            $fields = Array();
        if( !is_null($this->select_fields) )
            $fields = array_merge(preg_split("/[,]/", $this->select_fields, -1, PREG_SPLIT_NO_EMPTY), $fields );
        foreach( $fields as $field )
            if( !is_null($field) && $field != '*' )
                $select_fields[] = $field;
        if( count($select_fields) <= 0 )
            $select_fields[] = '*';
        $select_fields = join(',', $select_fields);
        
        // check for relevance sort, if not set then switch to default sort
        if (@$this->sort_def['type'] == 'rel' && !isset($this->sort_def['fields']))
            $this->sort_def = @$this->index_defs['sort.default'];

        if ($this->sort_def)
            $select_fields .= ',' . $this->sort_def['fields'] . ' AS _sort';
        
        // add secondary sort field for relevance search
        if (@$this->sort_def['type'] == 'rel' && isset($this->index_defs['sort.default']))
            $select_fields .= ',' . $this->index_defs['sort.default']['fields'] . ' AS _sort2';
        return "SELECT $select_fields FROM {$this->main_table}";
        }

    // Add fragments to JOIN array
    function _collect_join_parts(&$result, $def)
        {
        if (!isset($def['join']))
            return;
        if (is_array($def['join']))
            {
            foreach($def['join'] AS $x)
                $result[$x] = TRUE;
            }
        else
            $result[$def['join']] = TRUE;
        }

    // Convert fields to WHERE clauses
    function _expand_number($fields, $relation, $subject)
        {
        // check for a number list
        if (strpos($subject, ',') !== false)
            return $fields .' IN ('. $subject .')';
        else
            return $fields . $relation . $subject;
        }

    function _expand_string($fields, $relation, $subject)
        {
        // replace * wildcards with %
        $subject = str_replace('*', '%', $subject);
        // check for wildcard %
        if (strpos($subject, '%') !== false)
            $relation = ' LIKE ';
        return $fields . $relation . "'" . addslashes($subject) . "'";
        }

    function _expand_fulltext($fields, $relation, $subject)
        {
        $conv = new BooleanQueryRewriter();
        $result = 'MATCH(' . $fields . ') AGAINST(' .
                "'" . $conv->convert($subject) . "'" . ' IN BOOLEAN MODE)';
        if ($conv->error_message != '')
            return $this->_set_error($conv->error_message);
        else
            return $result;
        }

    function _expand_relevance($fields, $relation, $subject)
        {
        $relevance = 'MATCH(' . $fields . ') AGAINST(' . "'" . addslashes($subject) . "'" . ')';
        if (@$this->sort_def['weight'])
            $relevance = '('. $relevance .' + '. $this->sort_def['weight'] .')';
        return $relevance;
        }
    
    function _expand_date($fields, $relation, $subject)
        {
        $dates = explode(',', $subject);
        // one date value uses a >= search
        if (count($dates) == 1)
            $result = $fields .">='". $dates[0] ."'";
        // two date values uses a BETWEEN...AND search
        else
            $result = $fields ." BETWEEN '". $dates[0] ."' AND '". $dates[1] ."'";
        return $result;
        }

    function _expand_datetime($fields, $relation, $subject)
        {
        $dates = explode(',', $subject);
        $start_date = fix_datetime_string($dates[0]);

        // one date value uses a >= search
        if (count($dates) == 1)
            $result = $fields .">='". $start_date ."'";
        // two date values uses a BETWEEN...AND search
        else {
            $end_date = fix_datetime_string($dates[1], '23:59:59');
            $result = $fields ." BETWEEN '". $start_date ."' AND '". $end_date."'";
            }
        return $result;
        }
    
    // Compare to blank string
    // if $subject is 'null' then compare against NULL rather than blank string
    function _expand_blank($fields, $relation, $subject)
        {
        if (strtolower($subject) == 'null')
            {
            $tmp = ($relation == '=') ? ' IS NULL' : ' IS NOT NULL';
            return $fields . $tmp;
            }
        return $fields . $relation . "''";
        }

    // Set the error message
    function _set_error($message)
        {
        $this->error_message = $message;
        }
    }
