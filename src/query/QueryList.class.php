<?php
// $Id$
// Query list class
// James Fryer, 7 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// The QueryList class models a collection of queries,
/// e.g. Saved Searches, Search History
///
/// Queries are stored as arrays containing the following information:
///     'text'   - the result of query->criteria_string()
///     'url'    - the result of query->url()
///     'info'   - the query->info array
///     'module' - name of the module that the query belongs to
///     'title'  - title of the module that the query belongs to
///      hash    - a hash of the contents of the query
///
class QueryList
    extends BaseContainer
    {
    /// The number of search queries to remember, default is -1 which designates unlimited
    var $limit = -1;

    function QueryList($limit=-1)
        {
        $this->limit = $limit;
        }

    /// Add a new query to the list
    function add($query)
        {
        global $CONF;
        if (is_a($query, 'Query'))
            {
            // convert the query to our internal representation
            $query = $this->_convert_to_array($query);
            $key = $this->_find($query);
            if ($key !== FALSE) // the query was found in the list
                {
                unset($this->array[$key]); // remove the old query
                }
            array_unshift($this->array, $query);

            if (count($this->array) > $this->limit && $this->limit != -1) // the number of queries now exceeds the limit
                array_pop($this->array); // remove the last query on the list
            }
        }
    
    /// Remove the query at the given index
    function remove($index)
        {
        if (!isset($this->array[$index]))
            return;
        unset($this->array[$index]);
        // reindex the array
        $this->array = array_merge($this->array);
        }

    /// Check if a query is already in the list
    /// Returns TRUE if the list contains the specified query, FALSE otherwise
    function contains($query)
        {
        return $this->_find($query) !== FALSE;
        }

    /// Convert the list into an outline format grouped by similar queries
    /// Returns ( ( head=>query, tail=(array of queries)), ...)
    /// "Similar" means, the 'q' criterion is the same
    function outline()
        {
        $result = Array();    
        foreach ($this->array as $query)
            {
            $key = $query['module'] . serialize(@$query['criteria']['q']);
            if (!isset($result[$key]))
                $result[$key] = Array('head'=>$query, 'tail'=>Array());
            else
                $result[$key]['tail'][] = $query;
            }
        return array_values($result);
        }

    /// Return the index of $query in the list, or FALSE if not found
    function _find($query)
        {
        $result = FALSE;
        // if the given query is still a Query class, convert to our internal representation
        if (is_a($query, 'Query'))
            $query = $this->_convert_to_array($query);

        // loop through each query in the list
        foreach ($this->array as $key=>$value)
            {
            // this query is the same as the new query
            if( $value['hash'] == $query['hash'] )
                {
                $result = $key;
                break; // exit the loop
                }
            }
        return $result;
        }

    /// Returns an array containing the relevant information from the given query
    //### FIXME: should be in Query class???
    function _convert_to_array($query)
        {
        if (!is_a($query, 'Query'))
            return NULL;
        $result = Array();
        $result['text'] = $query->criteria_string();
        $result['url'] = $query->url();
        $result['info'] = $query->info;
        // Store the criteria so we can serialise and reconstruct the query list
        $result['criteria'] = $query->criteria_container->get_qs_key_values();
        $result['module'] = $query->module->name;
        $result['title'] = $query->module->title;        
        $result['hash'] = $query->hash();
        return $result;
        }
    }

?>
