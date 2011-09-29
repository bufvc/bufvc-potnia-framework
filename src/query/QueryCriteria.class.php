<?php
// $Id$
// QueryCriteria class
// Alexander Veenendaal, 03 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


/**
* an exception subclass specifically for QueryCriteria errors
*/
class QueryCriteriaException extends Exception {}


/**
*   A Container for QueryCriterion instances
*/
class QueryCriteria
    extends BaseContainer
    {
    
    //// An array of QS keys mapped to criterion instances
    var $criteria_qs_keys = Array();

    /// Criteria can be constructed from array of QueryCriterion objects, or
    /// arrays which are converted to criteria
    function __construct( $initial=NULL ) 
        {
        if( is_array($initial) )
            {
            foreach( $initial as $criterion )
                {
                if (is_array($criterion))
                    $criterion = QueryCriterionFactory::create($criterion);
                $this[] = $criterion;
                }
            }
        else if( $initial instanceof QueryCriteria )
            {
            foreach( $initial as $key => $criterion )
                $this[] = clone $criterion;
            }
        }
        
    function __clone()
        {
        foreach($this->array as $name=>$criterion)
            $this->array[$name] = clone $criterion;        
        $this->populate_qs_keys();
        }
        
    /// Iterates throught the criteria array (which is a map of criteria names to objects), and
    /// populates the criteria_qs_keys with a map of criteria qs_keys to objects. Criterion instances
    /// such as date ranges will have multiple qs_keys and therefore have multiple entries in 
    /// the criteria_qs_keys array.
    private function populate_qs_keys()
        {
        $this->criteria_qs_keys = Array();
        foreach( $this->array as $name => $criterion)
            {
            $qs_key = $criterion->get_qs_key(TRUE);
            
            // in certain circumstances, the criterion may act as a composite of
            // two QS values.
            if( is_array($qs_key) )
                {
                foreach( $qs_key as $qsk )
                    $this->criteria_qs_keys[ $qsk ] = $criterion;
                }
            else
                $this->criteria_qs_keys[ $qs_key ] = $criterion;
            }
        }
    
    

    /// An ArrayAccess method which allows a QueryCriterion object to be added
    /// The object is added by specifying a name for the query criterion. If no name
    /// is specifyed, then the name is taken from the QueryCriterion instance.
    /// Only QueryCriterion instances may be added - attempts to add any other type will throw an exception
    function offsetSet($offset, $criterion) 
        {
        if (!($criterion instanceof QueryCriterion))
            {
            if( is_null($offset) )
                throw new QueryCriteriaException('offset must be specified for non QueryCriterion value');
            $criterion = QueryCriterionFactory::create( Array( 'name' => $offset, 'value' => $criterion ) );
            }
        
        // if the offset is null, use the criterions name as the key
        if( $offset == '' )
            $offset = $criterion->name;
        
        $this->array[$offset] = $criterion;
        
        // add to the qs_key container
        $qs_key = $criterion->get_qs_key(TRUE);
        
        // in certain circumstances, the criterion may act as a composite of
        // two QS values.
        if( is_array($qs_key) )
            {
            foreach( $qs_key as $qsk )
                $this->criteria_qs_keys[ $qsk ] = $criterion;
            }
        else
            $this->criteria_qs_keys[ $qs_key ] = $criterion;
        }
    
    /// ArrayAccess interface obligation. removes a value from this array
    function offsetUnset($offset)
        {
        $criterion = $this->array[$offset];
        $qs_key = $criterion->get_qs_key();
        
        if( is_array($qs_key) )
            {
            foreach( $qs_key as $qsk )
                unset($this->criteria_qs_keys[ $qsk ]);
            }
        else
            unset($this->criteria_qs_keys[ $qs_key ]);
        
        unset($this->array[$offset]);
        }
        

    /// Returns the QueryCriterion object that maps to the specified QueryString Key
    function get_by_qs_key( $qs_key )
        {
        // look for a direct match
        if( isset($this->criteria_qs_keys[$qs_key]) )
            return $this->criteria_qs_keys[$qs_key];
        foreach( $this->array as $name=>$criterion )
            if( $criterion->does_qs_key_match($qs_key) )
                return $criterion;
        return NULL;
        }
        

    /// Returns an array of all the criterias qs_keys mapped to values
    function get_qs_key_values()
        {
        $result = Array();
        foreach( $this->array as $name=>$criterion )
            {
            // ask each criterion to return an array of its qs_keys->values
            $result = array_merge( $result, $criterion->get_qs_key_values() );
            }
        return $result;
        }
        
    /// Returns an Array of label/value arrays describing each of the criteria in this
    /// container.
    /// a list may be passed which will be used to render criteria that need it. 
    /// To cope with the fact that different criteria may need different lists, the argument
    /// can also be a map of criteria name => list
    /// NOTE AV : passing lists down isn't a great solution
    function get_render_details($list_for_advanced=NULL)
        {
        $result = Array();
        foreach( $this->array as $name=>$criterion )
            {
            $list = $list_for_advanced;
            if( is_array($list_for_advanced) )
                {
                if( !is_null($criterion->list) && @isset($list_for_advanced[$criterion->list]) )
                    {
                    $list = $list_for_advanced[$criterion->list];
                    }
                else if( isset($list_for_advanced[$criterion->name]) )
                    {
                    $list = $list_for_advanced[$criterion->name];
                    }
                }
            $details = $criterion->get_render_details($list);
            if( count($details) > 0 )
                $result[] = $details;
            }
        return $result;
        }
        
    /// Returns an Array of list names that are required by the contained criterion to properly render 
    ///### TODO AV : a lot of the logic should be moved into the QC class ( or subclasses... )
    function get_required_list_names()
        {
        $result = Array();
        foreach( $this->array as $name=>$criterion )
            {
            if( !$criterion->is_renderable )
                continue;
            if( is_array($criterion->list) )
                continue;
            if( ($criterion->type == QC_TYPE_FLAG || $criterion->type == QC_TYPE_OPTION) 
                && !is_null($criterion->get_list()) )
                {
                $result[] = $criterion->get_list();
                continue;
                }
            if( $criterion->type != QC_TYPE_LIST && 
                $criterion->type != QC_TYPE_SORT &&
                !($criterion->type == QC_TYPE_TEXT && $criterion->is_expanded()) )
                continue;
            if( $criterion->is_set_to_default() )
                continue;
            $value = $criterion->get_rendered_value();
            if( empty($value) )
                continue;
            if( !is_null($criterion->list) )
                $result[] = $criterion->list;
            else
                $result[] = $criterion->name;
            }
        return $result;
        }
    
    /// Returns TRUE if this container and the other 'match'
    function compare( $other_container, $ignore_keys=NULL, $compare_by_qs_key_not_name=FALSE )
        {
        if (is_null($other_container) )
            return FALSE;
        if ( !($other_container instanceof QueryCriteria) || count($this) != count($other_container) )
            return FALSE;
        
        // handle passing a single string
        if( !is_null($ignore_keys) && !is_array($ignore_keys) )
            $ignore_keys = Array( $ignore_keys );
        
        foreach ($this->array as $key=>$criterion) // loop through each criteria
            {
            // ignore this value if required
            if( !is_null($ignore_keys) && in_array($key, $ignore_keys) )
                continue;
            if( !$criterion->is_value_equal( @$other_container[$key] ) )
                return FALSE;
            }
        
        return TRUE;
        }
    
    /// Get lists defined in criteria in an array indexed by qs_key
    function get_lists()
        {
        $result = Array();
        foreach ($this->array as $name => $criterion)
            {
            switch( $criterion->type )
                {
            case QC_TYPE_DATE_RANGE:
                $range_dates = $criterion->get_range_array();
                $qs_keys = $criterion->get_qs_key();
                
                $dates = Array(''=>$range_dates[0] );
                for($i=1; $i < count($range_dates); $i++ )
                    $dates[$range_dates[$i]] = $range_dates[$i];
                $result[$qs_keys[0]] = $dates;
            
                $reversed_range_dates = array_reverse($range_dates);
                $dates = Array(''=>$reversed_range_dates[0] );
                for($i=1; $i < count($reversed_range_dates); $i++ )
                    $dates[$reversed_range_dates[$i]] = $reversed_range_dates[$i];
                $result[$qs_keys[1]] = $dates;
                break;
            case QC_TYPE_SORT:
            case QC_TYPE_LIST:
            case QC_TYPE_FLAG:
            case QC_TYPE_OPTION:
                if( is_array($criterion->list) )
                    {
                    $result[$criterion->get_qs_key()] = $criterion->list;
                    }
                break;
                }
            }
        return $result;
        }

    /// Sets criteria values from an array of name=>value. Absent criteria are set to default.
    function set_values($attributes=NULL)
        {
        global $CONF;
        // reset all criteria back to their defaults
        foreach( $this as $name=>$criterion )
            $criterion->set_value( NULL );
        if( is_null($attributes) )
            return;
        
        // iterate through each of the criteria in this query, and apply incoming attributes to it
        foreach ($attributes as $name=>$value)
            {
            // find the associated criterion
            $criterion = $this->get_by_qs_key($name);
            // if( @$CONF['debug_trace'] ) println("setting $name on " . get_class($criterion) );
            if( is_null($criterion) == FALSE && $value != '' )
                $criterion->set_value( $value, $name );
            }
        }

    /// Get the URL query string for the criteria
    function query_string($criteria_override=NULL, $criteria_remove=NULL, $page=1)
        {
        $args = Array();
        $criteria = clone $this;
        $q_added = FALSE;

        // Add pseudo-criteria e.g. page
        if ($page > 1)
            $criteria['page'] = $page;
        
        // Criteria parameter overrides member vars
        if (!is_null($criteria_override))
            {
            foreach( $criteria_override as $key=>$value )
                $criteria[$key] = $value;
            }
        if( !is_null($criteria_remove) )
            {
            if( is_string($criteria_remove) )
                $criteria[$criteria_remove] = NULL;
            else if( is_array($criteria_remove) )
                {
                foreach( $criteria_remove as $key )
                    {
                    if( isset($criteria[$key]) )
                        unset($criteria[$key]);
                    }
                }
            // removing criteria reverts us back to the first page
            if( isset($criteria['page']) )
                unset($criteria['page']);
            }

        // Build URL
        foreach( $criteria as $name=>$criterion)
            {            
            if( !is_object($criterion) )
                $raw_values = Array( $name => $criterion );
            else
                $raw_values = $criterion->get_qs_key_values();
            foreach( $raw_values as $qs_key=>$value )
                {
                if( $qs_key == 'q' )
                    $q_added = TRUE;
                if( is_array($value) )
                    {
                    foreach ($value as $sub_value)
                        {
                        if ($sub_value)
                            $args[] = $qs_key.'[]='.urlencode($sub_value);
                        }
                    }
                else
                    $args[] = $qs_key.'='.urlencode($value);
                }
            }
        // Handles 'q' as a special case, so we can propagate an empty search. Also
        // if 'page' is present, 'q' needs to be added if there are no other criteria
        if( !$q_added && isset($criteria['page']) || (!$q_added && $criteria_remove && count($args) <= 0) )
            {
            array_unshift($args, 'q=');
            }
        
        return join('&', $args);
        }

    /// Are there any non-default criteria defined here?
    //### TODO: rename
    function has_criteria($criteria=NULL)
        {
        if (is_null($criteria))
            $criteria = $this;        
        foreach ($criteria as $name=>$criterion)
            {
            if( is_object($criterion) )
                {
                if( $criterion->is_set_to_default() == FALSE )
                    return TRUE;
                }
            else
                {
                if( isset($this[$name]) && $this[$name]->get_value() == $criterion )
                    return TRUE;
                }
            }
        return FALSE;
        }

    /// Check if an array contains allowed criteria
    /// Always operates on array arg, as invalid criteria can't be set
    function has_allowed($criteria)
        {
        foreach( $criteria as $qs_key=>$value )
            {
            if (!is_null($this->get_by_qs_key($qs_key)))
                return TRUE;
            }
        return FALSE;
        }
        
    /// Does this container/the array have criteria marked as advanced?
    function has_advanced($criteria=NULL)
        {
        if (is_null($criteria))
            {
            foreach( $this->array as $name=>$criterion )
                {
                if( $criterion->is_advanced() )
                    return TRUE;
                }
            }
        else {
            foreach( $criteria as $qs_key=>$value )
                {
                $criterion = $this->get_by_qs_key($qs_key);
                
                // does this criteria exist in this query
                if( is_null($criterion) || is_null($value) )
                    continue;
                $criterion_other = clone $criterion;
                $criterion_other->set_value( $value, $qs_key );
                if( $criterion_other->is_advanced() )
                    return TRUE;
                }
            }
        
        return FALSE;
        }
    }
