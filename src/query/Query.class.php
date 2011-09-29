<?php
// $Id$
// Query class
// James Fryer, 8 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'query/QueryFactory.class.php');
require_once($CONF['path_src'] . 'query/QueryCriterion.class.php');
require_once($CONF['path_src'] . 'query/QueryCriteriaCache.class.php');
require_once($CONF['path_src'] . 'query/QueryDataSourceEncoder.class.php');

// Query errors
define('QUERY_ERROR_NONE', 0);
define('QUERY_ERROR_CRITERIA', 1);
define('QUERY_ERROR_EMPTY', 2);
define('QUERY_ERROR_NOT_FOUND', 3);

///### TODO AV : the prefix QUERY_STRING_TYPE suggests its something to do with 
/// the incoming QS variables - think of an alternate name and change
define('QUERY_STRING_TYPE_HTML', 'html');
define('QUERY_STRING_TYPE_TEXT', 'text');
define('QUERY_STRING_TYPE_HTML_EDIT', 'html.edit' );
define('QUERY_STRING_TYPE_HTML_FORM', 'html.form' );
define('QUERY_STRING_TYPE_JSON', 'json');

// Future maybe: define('QUERY_STRING_TYPE_HTML_FORM', 'html.form' );

$_query_error_messages = Array(
    QUERY_ERROR_CRITERIA => $STRINGS['error_query_criteria'],
    QUERY_ERROR_EMPTY => $STRINGS['error_query_empty'],
    QUERY_ERROR_NOT_FOUND => $STRINGS['error_query_not_found'],
    );

/**
* The Query class
The query class has three main areas of responsibility:
 - Input of queries
 - Output of queries and query results
 - Execution of queries

This ends up with a rather fat class. But it seems the simplest way to do things.
The application level programmer, don't have to worry about any of these
concerns because the Query class takes care of it.

** Input
 - Accept input criteria from HTTP
 - Validate criteria
 - Maintain criteria state

** Output
 - Convert to representations. This is done by templates using the fields from this class.
    - Search
       - HTML form: templates
       - Readable text: to_string
       - URL: url()

    - Results
       - HTML list: templates
       - List in other formats: templates
       - Paging navigation, page size limits: ->page, info array

** Execution
 - search(): get page of results of current query also in ->results array
 - get_record(): Get single record (makes life easier for developers)
 - get_list(): get all records of a type (useful for dropdown lists)
 
** Creation
To create a query, use QueryFactory. Probably you can use QueryFactory::create($module)

 */
class Query
    implements ArrayAccess
    {
    /// The search table name
    var $table_name = 'test';
    
    /// The module that this query searches
    var $module;

    /// container for new QueryCriterion instances - will eventually be renamed
    /// to $criteria once new system is proved
    var $criteria_container;
    
    /// The current page of results
    var $results = Array();

    /// Paging criteria
    var $page = 1; // Current page, 1-based

    /// An array of QueryFilter objects which are called after search() and get_record() 
    var $filters = Array();

    /// An array where filters can add information about search results. Cleared before each search.
    var $filter_info = Array();

    // Information about the current results
    var $info = NULL;
    var $_default_info = Array(
        'results_count' => 0,
        'accuracy' => NULL,
        'page_count' => 0,
        'page_prev_url'=>NULL,
        'page_next_url'=>NULL,
        'page_first_url'=>NULL,
        'page_last_url'=>NULL,
        'page_urls'=>Array(),
        'page_message'=>NULL,
        'results_message'=>'No results',
        'results_message_unpaged'=>'No results',
        'first_in_page'=>0,
        'last_in_page'=>NULL,
        );

    /// Used by add_list/get_list
    var $_lists = NULL;

    /// unique query id used for logging
    var $_qid = NULL;

    /// cache object
    var $_cache = NULL;

    /// Current error status
    var $error_code = 0;
    var $error_message = NULL; // User-facing message
        
    function Query($module)
        {
        $this->module = $module;
        // Lists
        $this->_lists = Array();
        $this->_define_default_lists();
        // The first default filter must be the paging filter
        $this->_default_filters = Array(new PagingQueryFilter());
        $this->_set_default_paging();
        }
    
    //### FIXME should be in query factory???
    function _define_default_lists()
        {
        global $CONF, $STRINGS;
        // Default lists used by all modules
        $tmp = Array();
        foreach ($CONF['search_results_page_options'] as $n)
            $tmp[] = $n;
        $this->add_list('page_size', $tmp);
        $this->add_list('boolean_op', Array('and'=>'AND', 'or'=>'OR', 'not'=>'NOT'));
        $export_formats = Array();
        foreach ($CONF['export_formats'] AS $tmp)
            $export_formats[$tmp] = @$STRINGS['export_formats'][$tmp] == '' ? $tmp : @$STRINGS['export_formats'][$tmp];
        $this->add_list('export_formats', $export_formats);
        }

    // Called on serialize
    function __sleep()
        {
        $this->_module_name = $this->module->name;
        //### TODO: Remove _ds
        $this->module = NULL;
        return(array_keys(get_object_vars(&$this)));
        }

    // Called on unserialize
    function __wakeup()
        {
        $this->module = Module::load($this->_module_name);
        unset($this->_module_name);
        }
    
    function __clone()
        {
        // Need to clone the criteria container when cloning
        if (is_object($this->criteria_container))
            $this->criteria_container = clone $this->criteria_container;
        }
        
    /// Search the database and return a page of results
    /// Returns NULL if an error occurred (including no results found)
    /// Sets the following member vars: page, results, results_count
    function search($criteria=NULL)
        {
        global $CONF;
        $this->results = Array();
        $this->filter_info = Array();
        
        // Apply filters
        $filters = array_merge($this->_default_filters, $this->filters);
        foreach ($filters as $f)
            $f->before_search($this, $criteria);
        
        if (!is_null($criteria))
            $this->set_criteria_values($criteria);
        $page_size = $this->get_page_size();
        $offset = ($this->page - 1) * $page_size;
        if ($this->error_code)
            {
            $this->_set_default_paging();
            return NULL;
            }
        // check for cached data
        $results = NULL;
        if ($this->_cache->hit($this->criteria_container, $offset))
            $results = $this->_cache->get($offset, $page_size);
        if (is_null($results))
            {
            $query_string = $this->_encoder->encode();
            xlog(2, "query_string: $query_string");
            // do logging before and after search
            $start_time = microtime_float();
            $this->_log_start($query_string, $this->criteria_string(QUERY_STRING_TYPE_TEXT));
            $results = $this->_cache->search($this->module, $this->get_table(), $query_string, $offset, $page_size, $this->criteria_container);
            $end_time = microtime_float();
            if ($this->module->error_code)
                $this->_set_error(QUERY_ERROR_EMPTY, $this->module->error_message);
            $this->_log_end(is_null($results) ? -1 : $results['total'], round($end_time-$start_time, 4));
            }
        // Apply filters        
        foreach ($filters as $f)
            $f->after_search($results, $this, $criteria);

        $this->results = is_null($results) ? NULL : @$results['data'];

        if (@$results['count'] > 0)
            return $this->results;
        return NULL;
        }
    
    /// Get the full URL for the current query
    function url($criteria_override=NULL, $criteria_remove=NULL)
        {
        $table_name = $this->_is_default_table() ? '' : ('/' . $this->table_name);
        $query_string = $this->url_query($criteria_override, $criteria_remove);
        if ($query_string != '')
            $query_string = '?' . $query_string;
        return $this->module->url('search',  $table_name . $query_string);
        }
        
    // Is this query searching the default table?
    private function _is_default_table()
        {
        $tables = $this->module->list_query_tables();
        if (!is_null($tables))
            {
            $tables = array_keys($tables);
            return $this->table_name == @$tables[0];
            }
        }    
        
    /// Get the query string for the current query
    function url_query($criteria_override=NULL, $criteria_remove=NULL)
        {
        return $this->criteria_container->query_string($criteria_override, $criteria_remove, $this->page);
        }
    
    // Get the full URL for a new search query
    // If not the default table, will add the table name
    function url_new()
        {
        $table_name = $this->_is_default_table() ? '' : ('/' . $this->table_name);
        return $this->module->url('search', $table_name . '?mode=new');
        }

    // adds a new style criterion to this query
    function add_criterion($criterion)
        {
        $this->criteria_container[] = $criterion;
            
        // certain special criterion relate directly to fields
        if( $criterion->name == 'page' )
            $this->page = $criterion->get_value();
        }

    /// Sets values for the criteria
    function set_criteria_values($criteria=NULL)
        {
        $page_size = $this->get_page_size();
        $default_page_size = $this->get_default_page_size();
        
        // allow the module to get a first look at incoming values (they may be modified)
        $this->module->process_criteria($this, $criteria);
        
        // reset all criteria back to their defaults
        $this->criteria_container->set_values($criteria);
        $this->page = isset($criteria['page']) ? $criteria['page'] : 1;
        // retain last page size value even if its reset back to default
        if( isset($this->criteria_container['page_size']) 
                && ($page_size != $default_page_size)
                && $this->criteria_container['page_size']->is_set_to_default() )
            $this->set_page_size( $page_size );
        }

    /// Check if the query/an array has any criteria set
    function has_criteria($criteria=NULL)
        {
        return $this->criteria_container->has_criteria($criteria);
        }

    /// Check if an array contains allowed criteria
    function has_allowed_criteria($criteria)
        {
        return $this->criteria_container->has_allowed($criteria);
        }

    /// Check if an array/current criteria contains criteria marked as advanced
    function has_advanced($criteria=NULL)
        {
        return $this->criteria_container->has_advanced($criteria);
        }

    /// Compare two Query objects for equality
    /// Two queries are the same if their criteria are the same
    function compare($other)
        {
        return is_a($other, 'Query') && $this->criteria_container->compare($other->criteria_container);
        }
        
    /// Returns the query page size
    function get_page_size()
        {
        $qc = $this->criteria_container['page_size'];
        if( $qc )
            return $qc->get_value();
        return 10;
        }
    
    /// Returns the default query page size
    function get_default_page_size()
        {
        $qc = $this->criteria_container['page_size'];
        if( $qc )
            return $qc->get_default();
        return 10;
        }
        
    /// Sets the query page size. the default may also be set
    /// from the same value
    function set_page_size( $page_size, $also_set_default=FALSE )
        {
        $qc = $this->criteria_container['page_size'];
        if( $qc )
            {
            $qc->set_value( $page_size );
            if( $also_set_default )
                $qc->set_default( $page_size );
            }
        }
    
    /// Sets the default page size. the page size value is
    /// unaffected.
    function set_default_page_size( $page_size )
        {
        $qc = $this->criteria_container['page_size'];
        if( $qc )
            $qc->set_default( $page_size );
        }
    
    /// Get a single record by URL
    /// Return an array containing record fields, or NULL if not found/error
    function get_record($url, $parameters=NULL)
        {
        $this->_set_error();
        $result = $this->module->retrieve($url, $parameters);
        if (is_null($result))
            return $this->_set_error(QUERY_ERROR_NOT_FOUND, $this->module->error_message);
        // Apply filters
        foreach (array_merge($this->_default_filters, $this->filters) as $f)
            $f->after_get_record($result, $this, $url, $parameters);
        return $result;
        }

    /// Get all items in a small table (<1000 items)
    /// For use e.g. when building selection lists
    /// Return an array key=>title or NULL on error
    function get_list($table_name)
        {
        //### TODO: add offset, count args???
        $this->_set_error();
        if (isset($this->_lists[$table_name]))
            return $this->_lists[$table_name];
        $r = $this->module->search($this->get_table($table_name), '', 0, 1000);
        if (is_null($r))
            return NULL;
        $result = Array();
        /// TODO AV : this was needed to guard against returning a list for
        /// the media criterion in the bund module - write a test against this situation
        if( !isset($r['data']) )
            return NULL;
        foreach ($r['data'] as $item)
            {
            $key = isset($item['key']) ? $item['key'] : basename($item['url']);
            $result[$key] = $item['title'];
            }
        return $result;
        }
    
    /// Get the qualified table name
    ///### FIXME: remove??? -- not needed in always multimodule branch
    function get_table($table_name=NULL)
        {
        if ($table_name == '')
            $table_name = $this->table_name;
        return $this->module->name . '/' . $table_name;
        }

    /// Add a small table list. Useful for e.g. page size dropdowns
    /// The list will be returned by 'get_list'
    function add_list($table, $data)
        {
        $this->_lists[$table] = $data;
        }

    /// Return a string representation of the query
    /// Setting $plaintext to TRUE will remove all html markup
    /// NOTE AV : DEPRECATED
    function to_string($plaintext=FALSE)
        {
        return $this->criteria_string($plaintext ? 'text' : 'html');
        }
        
    /// Returns a JSON representation of the query
    function to_json()
        {
        $result = Array();
        $values = $this->criteria_container->get_qs_key_values();
        
        foreach( $this->criteria_container as $criterion )
            {
            $details = Array( 'name' => $criterion->get_name() );
            $details['type'] = $criterion->type;
            
            $label = $criterion->label;
            if( $label )
                $details['label'] = $label;
            
            $value = $criterion->get_value(NULL,FALSE);
            if( $value && !$criterion->is_set_to_default() )
                $details['value'] = $value;
            
            $default = $criterion->get_default();
            if( $default )
                $details['default'] = $default;
                
            $list = $criterion->get_list();
            if( !is_null($list) )
                {
                if( is_string($list) )
                    $list = $this->get_list($list);
                if( !is_null($list) )
                    $details['list'] = $list;
                }
            
            $result[] = $details;
            }
        
        return json_encode($result);
        }

    /// Formats the criteria into a string of the specified type.
    function criteria_string($type=QUERY_STRING_TYPE_HTML, $mode=NULL)
        {
        global $MODULE;
        $result = '';
        
        ///### NOTE AV : the fact that this type of output operates on key values and not details
        /// suggests that this doesn't belong here. Its data serialisation rather than data formatting.
        if( $type == QUERY_STRING_TYPE_HTML_FORM )
            {
            $values = $this->criteria_container->get_qs_key_values();
            
            // $result.= '<form action="' . $MODULE->url('saved') . '" method="POST">' . "\n";
            foreach( $values as $qs_key=>$value )
                {
                if( is_array($value) )
                    $value = join(',', $value);
                $result .= '<input type="hidden" name="' . $qs_key . '" value="' . htmlspecialchars($value) . '" />' . "\n";
                }
            // $result .= "</form>\n";
            
            return $result;
            }
            
        if( $type == QUERY_STRING_TYPE_JSON )
            {
            return $this->to_json();
            }
        
        // fetch an array of list names that the criteria need to render
        // NOTE AV - not great!
        $list_requirements = $this->criteria_container->get_required_list_names();
        $criteria_lists = Array();
        
        foreach( $list_requirements as $list_name )
            $criteria_lists[$list_name] = $this->get_list( $list_name );

        $criteria_details = $this->criteria_container->get_render_details( $criteria_lists );
        
        // allow the module to process the criteria details before they get presented
        $this->module->process_render_details( $this, $criteria_details );
        
        foreach( $criteria_details as $detail )
            {
            switch($type)
                {
                case QUERY_STRING_TYPE_TEXT:
                    $result .= $detail['label'];
                    if( isset($detail['value']) )
                        $result .= ': ' . $detail['value'] . "\n";
                    break;
                case QUERY_STRING_TYPE_HTML:
                	
                    $result .= '<dt>' . $detail['label'];
                    // NOTE AV : the use of a colon should probably be a CSS concern
                    if( isset($detail['value']))
                        {
                        $result .= '</dt><dd';
                        if( isset($detail['is_locked']) && $detail['is_locked'] )
                            $result .= ' data-locked="true"';
                        $result .= '>';
                        $result .= $detail['value'];
                        if( $mode == 'edit' && !(isset($detail['is_primary']) && $detail['is_primary'] ) )
                            {
                            if( isset($detail['is_locked']) && $detail['is_locked'] )
                                {
                                // TODO - complete css decoration
                                // $result .= "<a class=\"lock-selected-facet\">This criterion is locked</a>\n";
                                }
                            else
                        	    $result .= '<a class="remove-selected-facet" href="' . $this->url(NULL, $detail['name'] ) . "\">Remove this criterion</a>\n";
                        	}
                        $result .= "</dd>\n";
                        }
                    else
                        $result .= '</dt>';
                    
                    break;
                // case QUERY_STRING_TYPE_HTML_FORM:
                //     $result .= '<input type="hidden" name="' . $detail['name'] . '" value="' . htmlspecialchars($detail['value']) . '" />' . "\n";
                //     break;
                }
            }
        return $result;
        }
    
    /// Get an HTML form for use with this query
    /// Mode is passed as $MODE to the templates and can be basic, advanced, homepage
    /// Looks for template named search_{table}.php
    function search_form($mode)
        {
        global $CONF, $QUERY, $MODULE, $USER; // Globals to pass down
        //### FIXME: $QUERY below should be $this???
        $vars = Array('MODE'=>$mode, 'CONF'=>$CONF, 'QUERY'=>$QUERY, 'MODULE'=>$MODULE, 'USER'=>$USER);
        $url = $this->module->url('search', '/' . $this->table_name);
        $template = 'search_' . $this->table_name;
        $result = $this->module->get_template($template, $vars);
        $result = '<form id="search" method="GET" action="' . $url . '" class="'.$mode.'-search">' . $result;
        $result .= "\n<input id='search_data' type='hidden' value='" . $this->criteria_string(QUERY_STRING_TYPE_JSON) . "'/>\n"; 
        $result .= '</form>';
        return $result;
        }
        
    /// Returns a hash for this query
    ///### TODO AV : move this to QueryCriteria.class once old query impl is removed
    function hash()
        {
        $content = Array();
        $content['_module'] = $this->module->name;
        $values = $this->criteria_container->get_qs_key_values();
        //### TODO AV : criterion should be flagged as being transient so that they are not included
        unset( $values['sort'] );
        // NOTE AV - not yet sure the order is important - after all, the way criteria are
        // defined means the ordering is constant
        sort($values);
        $content = array_merge( $content, $values );
        $dump = var_export($content, TRUE);
        return md5( $dump );
        }
        
    /// Assigns a value to the specified offset.
    /// ArrayAccess interface obligation.
    function offsetSet( $offset, $value ) 
        {
        /// TODO AV : implement this
        }

    /// Returns TRUE if an element at the specified offset exists
    /// A convenient proxy function for access to the criteria_container
    /// ArrayAccess interface obligation. 
    function offsetExists($offset) 
        {
        return isset($this->criteria_container[$offset]);
        }

    /// Removes the QueryCriterion at the specified offset from this container
    /// ArrayAccess interface obligation.
    function offsetUnset($offset)
        {
        /// TODO AV : implement this
        }

    /// Returns the QueryCriterion at the specified offset or NULL if it
    /// doesn't exist. A convenient proxy function for access to the criteria_container
    /// ArrayAccess interface obligation.
    function offsetGet($offset) 
        {
        return $this->criteria_container->offsetGet($offset);
        }
    
    // Set the default paging
    function _set_default_paging()
        {
        $dummy = NULL;
        $this->_default_filters[0]->after_search($dummy, $this, NULL);
        }
        
    // Set error code, message, opt description
    function _set_error($code=0)
        {
        global $_query_error_messages;
        $this->error_code = $code;
        $this->error_message = @$_query_error_messages[$code];
        }

    // Query logging - start
    function _log_start($ds_query, $to_string_query)
        {
        global $CONF;
        // generate the query id
        $this->_qid = getmypid() . microtime_float();
        $message = $this->_qid .' '. $this->table_name .' '. $this->url() .' "'. addslashes($ds_query) .' | '.
                    str_replace("\n", '\n', addslashes($to_string_query)) .'"';
        xlog(1, $message, 'QUERY-START', $CONF['query_log']);
        }

    // Query logging - end
    function _log_end($results, $duration, $accuracy='exact')
        {
        global $CONF;
        $message = "$this->_qid $results $accuracy $duration";
        xlog(1, $message, 'QUERY-END', $CONF['query_log']);
        }
    }

// Helper classes
/// Add paging to the query results
//### TODO: add unit tests for this and move it to filters/
class PagingQueryFilter
    extends QueryFilter
    {
    // Messages for result count strings
    //### TODO: move to strings array
    var $_results_count_messages = Array(
        'single' => Array(
            'default' => '1 result'
            ),
        '1_page' => Array(
            'default' => '%s results',
            'exceeds' => 'Over %s results',
            ),
        'many_pages' => Array(
            'default' => '%s-%s of %s results',
            'exceeds' => '%s-%s of over %s results',
            ),
        'record' => Array(
            'default' => 'Result %s of %s',
            'exceeds' => 'Result %s of over %s',
            ),
        );

    // Get a 'result X of Y' message (see var $_results_count_messages)
    function get_count_message($name, $accuracy=NULL)
        {
        $a = @$this->_results_count_messages[$name];
        return @$a[$accuracy] != '' ? $a[$accuracy] : @$a['default'];
        }

    function after_search(&$results, $query, $criteria)
        {
        global $CONF;
        $query->info = $query->_default_info;
        if (is_null($results))
            return;
        $page_size = $query->get_page_size();
        // Apart from the fields below, any other results fields are copied verbatim
        $do_not_copy = Array('data', 'total', 'count', 'offset');
        $tmp = $results;
        foreach ($do_not_copy as $name)
            unset($tmp[$name]);
        $query->info = array_merge($query->info, $tmp);

        // Handle results and paging
        $results_count = $results['total'];
        $page_count = (int)(($results_count + $page_size - 1)/$page_size);
        if ($results_count)
            {
            $query->info['results_count'] = $results_count;
            $query->info['page_count'] = $page_count;
            if ($query->page > 1)
                {
                $query->info['page_prev_url'] = $query->url(Array('page'=>$query->page - 1));
                $query->info['page_first_url'] = $query->url(Array('page'=>1));
                }
            if ($query->page < $page_count)
                {
                $query->info['page_next_url'] = $query->url(Array('page'=>$query->page + 1));
                $query->info['page_last_url'] = $query->url(Array('page'=>$page_count));
                }
            $query->info['page_message'] = sprintf('Page %s of %s', format_number($query->page), format_number($page_count));
            $query->info['first_in_page'] = ($query->page - 1) * $page_size + 1;
            $query->info['last_in_page'] = min($query->info['first_in_page'] + $page_size - 1, $results_count);
            // results_message
            $total_found = @$results['total_found'] ? $results['total_found'] : $results_count;
            if ($results_count == 1)
                $query->info['results_message'] = $query->info['results_message_unpaged'] = $this->get_count_message('single');
            else if ($results_count <= $page_size)
                $query->info['results_message'] = $query->info['results_message_unpaged'] = sprintf($this->get_count_message('1_page', @$results['accuracy']), format_number($total_found));
            else {
                $query->info['results_message'] = sprintf($this->get_count_message('many_pages', @$results['accuracy']),
                        format_number($query->info['first_in_page']), format_number($query->info['last_in_page']), format_number($total_found));
                $query->info['results_message_unpaged'] = sprintf($this->get_count_message('1_page', @$results['accuracy']), format_number($total_found));
                }

            // page_urls
            $even = (int)$CONF['intermediate_pages_size'] % 2 == 0;
            $range = (int)((int)$CONF['intermediate_pages_size'] / 2);
            // calculate range of pages to display
            $first = $query->page - $range;
            $last = $query->page + $range;
            if ($even) // special case
                $first += 1;

            // check if we went out of the range
            if ($first < 1)
                $first = 1;
            if ($last > $page_count)
                $last = $page_count;

            // check total pages from first to last
            $pageDiff = $last - $first + 1;

            // not enough pages
            if ($pageDiff < $CONF['intermediate_pages_size'] && ($first != 1 || $last != $page_count))
                {
                // add more at the end
                if ($first == 1)
                    {
                    while ($pageDiff < $CONF['intermediate_pages_size'] && $last < $page_count)
                        {
                        $last += 1;
                        $pageDiff += 1;
                        }
                    }
                // add more at the begninning
                else {
                    while ($pageDiff < $CONF['intermediate_pages_size'] && $first > 1)
                        {
                        $first -= 1;
                        $pageDiff += 1;
                        }
                    }
                }

            // create page urls
            for ($i = $first; $i <= $last; $i++)
                $query->info['page_urls'][$i] = $query->url(Array('page'=>$i));
            }
        // highlight a random record from first X results (1-based)
        if ($CONF['results_highlight_random_sample_size'] > 0 && $results_count && $query->page == 1)
            $query->info['highlighted_record'] = rand(1, min($results_count, $CONF['results_highlight_random_sample_size']));
        }

    // Sets results message, and prev/next links
    // query function depends on the results cache
    function after_get_record(&$record, $query, $url, $parameters)
        {
        if (is_null($query->_cache->results) || empty($query->_cache->results['data']))
            return;
        $page_size = $query->get_page_size();
        // check boundaries of cache first, in order to automatically repopulate cache
        $last_index = count($query->_cache->results['data'])-1;
        // first in cache
        if ($query->_cache->compare_record(0, $record) && $query->_cache->results['offset'] > 0)
            {
            $offset = $query->_cache->results['offset'];
            $query->page = intval($offset / $page_size); // set to previous page
            $query->search(); // get new cache results
            }
        // last in cache
        else if ($query->_cache->compare_record($last_index, $record) &&
                 ($last_index + $query->_cache->results['offset'] + 1) < $query->_cache->results['total'])
            {
            $offset = $last_index + $query->_cache->results['offset'] + 1;
            $query->page = intval($offset / $page_size) + 1; // set to next page
            $query->search(); // get new cache results
            }
        // search the cache for query record
        for ($i=0; $i<count($query->_cache->results['data']); $i++)
            {
            if ($query->_cache->compare_record($i, $record))
                {
                $offset = $i + $query->_cache->results['offset'];
                // set the results message
                $total_found = @$query->_cache->results['total_found'] ? $query->_cache->results['total_found'] : $query->_cache->results['total'];
                $record['results_message'] = sprintf($this->get_count_message('record', @$query->_cache->results['accuracy']),
                        format_number($offset + 1), format_number($total_found));
                // update the search results page number
                if ($offset == 0) // special case
                    $query->page = 1;
                else
                    $query->page = intval($offset / $page_size) + 1;
                // set the links if available
                if ($i > 0)
                    $record['record_prev_url'] = $query->_cache->set_record_link($i-1);
                if ($i < count($query->_cache->results['data']) - 1)
                    $record['record_next_url'] = $query->_cache->set_record_link($i+1);
                }
            }
        }
    }
