<?php
// $Id$
// Record list class
// Alexander Veenendaal, 16 Jun 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/**
*   An ordered container for records, which may have a maximum size. 
*   If a limit is specified in the constructor, then adding records over the limit
*   will cause the last record to be removed.
*/
class RecordList
    extends BaseContainer
    {        
    /// The number of search queries to remember, default is -1 which designates unlimited
    var $limit = -1;
    
    function __construct( $limit=-1 ) 
        {
        $this->limit = $limit;
        }

    /// Adds a record
    function add( $record )
        {
        $this->add_record( $record );
        }
    
    /// Adds a record
    function offsetSet( $offset, $record ) 
        {
        $this->add_record( $record, $offset );
        }
        
    /// Adds a record. If the number of records exceeds the limit,
    /// then the last element is removed.
    /// The behaviour of this container is slightly more complex than
    /// usual, since it has behave both as a map of record urls to records
    /// and as an ordered array.
    private function add_record( $record, $offset=NULL )
        {
        global $MODULE;
        if( !is_array($record) )
            return FALSE;
        
        // must have mimimum 'recordness'
        if( !isset($record['url']) || !isset($record['title']) )
            return FALSE;
        
        // do we already have this record ? If so, pull it out again
        $internal_record = $this->get_by_url( $record['url'] );
        
        // remove if we have it
        if( !is_null($internal_record) )
            $this->offsetUnset( $internal_record['url'] );
        else {
            $module = @$record['module'] ? @$record['module'] : $MODULE;
            $internal_record = $record;
            $internal_record['module_title'] = $module->title;
            $internal_record['modname'] = $module->name;
            }
        
        // add to the beginning of the list
        array_unshift($this->array, $internal_record);

        if (count($this->array) > $this->limit && $this->limit != -1) // the number of queries now exceeds the limit
            array_pop($this->array); // remove the last query on the list
        
        return TRUE;
        }
    
    /// Returns a record with the specified url 
    function get_by_url( $url )
        {
        foreach( $this->array as $record )
            if( $record['url'] == $url )
                return $record;
        return NULL;
        }
        
    /// Returns TRUE if the specified url exists
    /// ArrayAccess interface obligation.
    function offsetExists($url) 
        {
        foreach( $this->array as $index=>$record )
            if( $record['url'] == $url )
                return TRUE;
        return isset($this->array[$url]);
        }
        
    /// Removes the element with the specified url
    /// ArrayAccess interface obligation.
    function offsetUnset($url)
        {
        foreach( $this->array as $index=>$record )
            {
            if( $record['url'] == $url )
                unset($this->array[$index]);
            }
        }
    
    /// Returns the item at the specified offset or NULL if it
    /// doesn't exist. The argument may either be an integer index
    /// or a string url
    /// ArrayAccess interface obligation.
    function offsetGet($url)
        {
        if( is_integer($url) )
            return $this->array[$url];
        
        return $this->get_by_url($url);
        }

    }
