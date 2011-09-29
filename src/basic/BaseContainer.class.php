<?php
// $Id$
// Base Container Class
// Alexander Veenendaal, 26 Jun 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/**
*   Base class for container classes that require the combined 
*   services of the ArrayAccess, Countable and Iterator interfaces.
*/
class BaseContainer
    implements ArrayAccess, Countable, Iterator
    {
    
    /// 
    var $array = Array();
    
    /// Assigns a value to the specified offset.
    /// ArrayAccess interface obligation.
    function offsetSet( $offset, $value ) 
        {
        if( is_null($offset) )
            $this->array[] = $value;
        else
            $this->array[$offset] = $value;
        }
 
    /// Returns TRUE if an element at the specified offset exists
    /// ArrayAccess interface obligation.
    function offsetExists($offset) 
        {
        return isset($this->array[$offset]);
        }
        
    /// Removes the element at the specified offset from this container
    /// ArrayAccess interface obligation.
    function offsetUnset($offset)
        {
        unset($this->array[$offset]);
        }
        
    /// Returns the item at the specified offset or NULL if it
    /// doesn't exist
    /// ArrayAccess interface obligation.
    function offsetGet($offset) 
        {
        return isset($this->array[$offset]) ? $this->array[$offset] : null;
        }

    /// Returns the iterator to the first element
    /// Iterator interface obligation. 
    function rewind() 
        {
        reset($this->array);
        }
    
    /// Returns the current element
    /// Iterator interface obligation
    function current()
        {
        return current($this->array);
        }

    /// Returns the key of current element
    /// Iterator interface obligation.
    function key()
        {
        return key($this->array);
        }

    /// Moves forward to the current element
    /// Iterator interface obligation
    function next()
        {
        return next($this->array);
        }

    /// Returns true if the current position is valid
    /// Iterator interface obligation
    function valid() 
        {
        return $this->current() !== false;
        }

    /// Returns the number of elements in this container
    /// Countable interface obligation
    function count()
        {
        return count($this->array);
        }

    /// Returns TRUE if this container contains no elements
    function is_empty()
        {
        return count($this->array) == 0;
        }
        
    /// Removes all elements from this container
    function clear()
        {
        $this->array = Array();
        }
    }
