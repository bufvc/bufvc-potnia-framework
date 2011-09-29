<?php
// $Id$
// Query Cache class
// Alexander Veenendaal, 03 June 10; Phil Hansen, 31 Aug 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Exception subclass for QueryCriteriaCache errors
class QueryCacheException extends Exception {}

/// The Query Criteria Cache class contains the cached search criteria and cached search results.
/// It also contains all cache related functions.
class QueryCriteriaCache
    {
    /// Cache of results from last search
    var $results;

    /// Cache of criteria from last search
    var $criteria;

    // cache size (a size of 0 disables the cache)
    var $size;

    function __construct($size=0)
        {
        $this->results = NULL;
        $this->criteria = NULL;
        $this->size = $size;
        }

    // Equality check for the given record and the specified cache index
    function compare_record($index, $record)
        {
        return $this->results['data'][$index]['url'] == $record['url'];
        }

    // Return the url link for the given cache index
    function set_record_link($index)
        {
        return $this->results['data'][$index]['url'];
        }

    // Check if the data is cached for the given offset
    function hit($criteria, $offset)
        {
        // no cache
        if( !($this->criteria instanceof QueryCriteria) )
            return FALSE;
        if( !$this->criteria->compare($criteria) )
            return FALSE;
        // offset is outside bounds of current cached data
        if ($offset < @$this->results['offset'] ||
            $offset > $this->size + @$this->results['offset'] - 1)
            return FALSE;
        return TRUE;
        }

    // Set cache data
    function set($results, $criteria)
        {
        if (is_null($results))
            {
            $this->results = NULL;
            $this->criteria = NULL;
            }
        else
            {
            $this->results = $results;
            if( $criteria instanceof QueryCriteria )
                $this->criteria = clone $criteria;
            else if( is_null($criteria) )
                $this->criteria = NULL;
            else
                throw new QueryCacheException("invalid type for criteria: " . gettype($criteria) );
            }
        }

    // Get data from cache or NULL if empty
    function get($offset, $page_size)
        {
        if (is_null($this->results) || !isset($this->results['data']))
            return NULL;
        $results = $this->results;
        $results['data'] = array_slice($this->results['data'], $offset - $this->results['offset'], $page_size);
        $results['offset'] = $offset;
        $results['count'] = min($page_size, max($results['total'] - $offset, 0));
        return $results;
        }

    // Helper function for performing searches
    // This function will populate the cache if caching is enabled
    function search(&$ds, $table_name, $query_string, $offset, $page_size, $criteria)
        {
        // caching is disabled
        if ($this->size == 0)
            $results = $ds->search($table_name, $query_string, $offset, $page_size);
        // get full results from datasource and populate cache
        else 
            {
            // calculate the lower offset for the cache
            // attempt to start the cache 25% below offset, leaving 75% above offset
            $lower_offset = max($offset - ($this->size/4), 0);
            $results = $ds->search($table_name, $query_string, $lower_offset, $this->size);
            $this->set($results, $criteria);
            
            // get results from cache
            $results = $this->get($offset, $page_size);
            }
        return $results;
        }
    }
