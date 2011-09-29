<?php
// $Id$
// Build Sphinx queries from a parsed tree
// James Fryer, 2011-03-14
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class SphinxGenerator
    {
    var $sphinx;
    private $index;
    private $subjects;
    private $index_defs;
    private $bool_rewriter;
    private $sort_field;
    private $sort_direction;
    var $error_message;

    function __construct($index_defs, $sphinx=NULL)
        {
        $this->index_defs = $index_defs;
        if (is_null($sphinx))
            $sphinx = new_sphinx();
        $this->sphinx = $sphinx;
        // Always use array results, so we can use group by
        if (!is_null($sphinx))
            $this->sphinx->SetArrayResult(TRUE);
        $this->bool_rewriter = new SphinxBooleanQueryRewriter();
        }
    
    /// Add a parsed query to the Sphinx object
    /// Return query index (0..N) or NULL on error
    /// If NULL is returned and error message is empty, then the query can't be handled by this module
    function add_query($sphinx_index, $tree, $offset, $count, $group_by=NULL)
        {
        global $CONF;
        $this->subjects = Array();
        $this->_add_sort($tree);
        $tree->normalise();
        $this->sphinx->SetLimits($offset, $count, $CONF['sphinx_max_matches']);
        $clauses = $tree->flatten();
        // Can't yet handle queries containing OR
        if (count($clauses) > 1)
            return NULL;
        if (is_array($clauses[0]))
            {
            foreach ($clauses[0] as $clause)
                {
                if (!$this->_add_clause($clause))
                    return NULL;
                }
            }
        if (isset($group_by))
            $this->_add_group_by($group_by);
        return $this->_add_query($sphinx_index, $this->subjects, $tree->to_string());
        }

    /// Look for a sphinx config value override in the query tree
    /// the first override found is returned
    function find_sphinx_config_value($tree, $type)
        {
        $clauses = $tree->find('');
        foreach ($clauses as $clause)
            {
            if (isset($this->index_defs[$clause['index']][$type]))
                return $this->index_defs[$clause['index']][$type];
            }
        return '';
        }

    /// Add a "group by" query using the current criteria. This is used for facets.
    /// Return query index (0..N) or NULL on error
    function add_group_by($sphinx_index, $group_field)
        {
        $this->sphinx->SetGroupBy($group_field, SPH_GROUPBY_ATTR);
        return $this->_add_query($sphinx_index, $this->subjects, 'group by');
        }

    /// Get the current Sphinx object, or NULL if error
    function sphinx()
        {
        return $this->sphinx;
        }
    
    /// Get the name of the sort field for the current query, or 'rel' if relevance sort
    function sort_field()
        {
        return $this->sort_field;
        }
    
    // Add the query to Sphinx -- if no subjects, add an "all fields" blank string
    private function _add_query($sphinx_index, $subjects, $log_message)
        {
        if (count($subjects) > 0)
            {
            $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
            $query = join(' ', $subjects);
            $log_message = "$sphinx_index:$query ($log_message)";
            }
        else {
            $query = '';
            $log_message = "$sphinx_index:<empty> ($log_message)";
            }
        xlog(4, $log_message, 'SPHINX'); 
        return $this->sphinx->AddQuery($query, $sphinx_index, $log_message);
        }

    // Add the sort field to sphinx and remove it from the tree
    private function _add_sort(&$tree)
        {
        $sort = $tree->find('sort');
        $sort = @$sort[0]['subject'];
        $tree->remove('sort');
        if ($sort == '')
            $sort = 'default';
        $sort_def = @$this->index_defs['sort.' . $sort];
        if ($sort_def != '')
            {
            if (@$sort_def['type'] == 'rel')
                {
                $this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
                $this->sort_field = 'rel';
                }
            else {                
                // Check for sphinx-specific field
                if (@$sort_def['sphinx_fields'] != '')
                    $field = $sort_def['sphinx_fields'];
                // Remove MySQL table prefix from field name
                else
                    $field = preg_replace('/^.*\./', '', $sort_def['fields']);
                $direction = @$sort_def['type'] == 'desc' ? 'DESC' : 'ASC';
                $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, $field . ' ' . $direction);
                $this->sort_field = $field;
                $this->sort_direction = $direction;
                }
            }
        }
    
    // Add group by settings to sphinx
    // This expects an array of config values
    //   'attribute' - the grouping attribute
    //   'func' - the group function
    //   'sortmode' - (optional) an array specifying the sort mode config to use
    //                e.g. Array(mode, sortby)
    // If a new sortmode is specified then the existing sort mode will be moved to the
    // 'groupsort' parameter of the group by settings
    private function _add_group_by($group_by)
        {
        $group_sort = '@group DESC';
        if (isset($group_by['sortmode']))
            {
            $this->sphinx->SetSortMode($group_by['sortmode'][0], $group_by['sortmode'][1]);
            $group_sort = ($this->sort_field == 'rel') ? '@relevance DESC' : $this->sort_field.' '.$this->sort_direction;
            }
        $this->sphinx->SetGroupBy($group_by['attribute'], $group_by['func'], $group_sort);
        }

    // Add a clause to Sphinx. Adds fulltext subjects. Return TRUE on success
    private function _add_clause($clause)
        {
        $index = @$clause['index'];
        $index_def = @$this->index_defs[$index];
        if ($index_def == '')
            return $this->_set_error('Unknown index: '. $index);
        // Check for sphinx-specific field
        if (@$index_def['sphinx_fields'] != '')
            $field = $index_def['sphinx_fields'];
        // Remove MySQL table prefixes from field names
        else
            $field = preg_replace(Array('/^[^,]*\./', '/,[^,]*\./'), Array('', ','), $index_def['fields']);
        $type = @$index_def['type'];
        //### FIXME: change 'number' to 'int' and 'float' if we want to have float filters
        if ($type == 'number')
            $this->_handle_integer($clause, $field);
        else if ($type == 'datetime' || $type == 'date')
            $this->_handle_datetime($clause, $field);
        else if ($type == 'string' || $type == 'fulltext')
            {
            $subject = $this->_handle_text($clause, $field, $index_def);
            if (is_null($subject))
                return NULL;
            else
                $this->subjects[] = $subject;
            }
        // Not supported
        else if ($type == 'blank')
            return NULL;
        // Ignored without error
        else if ($type == 'replace_join')
            return TRUE;
        // Unknown
        else 
            return $this->_set_error('Unknown type: '. $type);
        return TRUE;
        }
        
    private function _handle_text($clause, $field, $index_def)
        {
        if ($clause['relation'] != '=')
            return NULL;
        $subject = $clause['oper'] == 'NOT' ? '-' : '';
        if (!@$index_def['default_tables'])
            {
            // Wrap comma-separated field names in parens
            if (strchr($field, ',') !== FALSE)
                $subject .= '@('. $field . ') ';
            else
                $subject .= '@'. $field . ' ';
            }
        $value = $clause['subject'];
        // Remove apostrophes
        $value = str_replace(Array("'", "\xE2\x80\x99"), '', $value);
        $subject .= $this->bool_rewriter->convert($value);
        return $subject;
        }
        
    private function _handle_integer($clause, $field)
        {
        $relation = $clause['relation'];
        $exclude = $relation == '<>' || $relation == '<' || $relation == '<=';
        if ($relation == '=' || $relation == '<>')
            {
            $subject = explode(',', $clause['subject']);
            $this->sphinx->SetFilter($field, $subject, $exclude);
            }
        else {
            $subject = (int)$clause['subject'];
            $incr = $relation == '>' || $relation == '<' ? 1 : 0;
            $this->sphinx->SetFilterRange($field, $subject + $incr, PHP_INT_MAX, $exclude);
            }
        }
        
    private function _handle_datetime($clause, $field)
        {
        $relation = $clause['relation'];
        $exclude = $relation == '<>' || $relation == '<' || $relation == '<=';
        if ($relation == '=' || $relation == '<>')
            {
            $subject = explode(',', $clause['subject']);
            if (count($subject) > 1)
                $this->sphinx->SetFilterRange($field, strtotime(fix_datetime_string($subject[0])), strtotime(fix_datetime_string($subject[1], '23:59:59')), $exclude);
            else {
                $subject[0] = strtotime(fix_datetime_string($subject[0]));
                $this->sphinx->SetFilter($field, $subject, $exclude);
                }
            }
        else {
            $subject = strtotime($clause['subject']);
            $incr = $relation == '>' || $relation == '<' ? 1 : 0;
            $this->sphinx->SetFilterRange($field, $subject + $incr, PHP_INT_MAX, $exclude);
            }
        }
        
    // Set the error message
    private function _set_error($message)
        {
        $this->error_message = $message;
        }
    }
