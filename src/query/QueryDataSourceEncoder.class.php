<?php
// $Id$
// Encode query params for datasource
// Alexander Veenendaal, 27 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/** This class encodes the query parameters in a format suitable for the DataSource.
 *  Currently this is a temporary format
 *      {x=foo}{z=bar}
 *  but this is expected to change.
 */
class QueryDataSourceEncoder
    {
    function QueryDataSourceEncoder(&$query)
        {
        $this->query = $query;
        }
        
    /// Convert the query criteria to a format suitable for DataSource 
    function encode()
        {
        global $CONF;
        $tmp = Array();
        $clauses = Array();
        $expanded_clauses = $non_expanded_clauses = Array();
        $needs_wrapping = FALSE;
        $is_value_array = FALSE;
        $this->expanded_clause_count = 0;
        $criteria = $this->query->criteria_container;
        
        // Gather clauses which match allowed criteria
        foreach( $criteria as $name=>$criterion )
            {
           if( !$criterion->is_encodable )
               continue;
            if( $criterion->is_expanded() )
                {
                $expanded_clauses = array_merge( $expanded_clauses, $this->expanded_criterion_to_clause( $criterion ) );
                }
            else
                {
                if( $criterion->type == QC_TYPE_DATE_RANGE )
                    {
                    // will always return both keys being used
                    $qs_keys = $criterion->get_qs_key();
                    
                    // will only return valid values, so if nothing returned, onwards to next
                    $qs_key_values = $criterion->get_qs_key_values();
                    if( count($qs_key_values) <= 0 )
                        continue;
                    
                    // convert each date into proper format if required
                    $dates = $criterion->get_value();
                    $formatted_dates = Array();
                    foreach( $dates as $date )
                        {
                        $date_elements = split('-',$date);
                        if( count($date_elements) == 5 )
                            $date = join('-', array_slice( $date_elements, 0, 3 ) ) . ' ' . join(':', array_slice($date_elements, 3) );
                        $formatted_dates[] = $date;
                        }
                    
                    $dates = join(',', $formatted_dates );
                    $clauses[] = Array( $criterion->name, QC_RELATION_EQ, $dates );
                    $is_value_array = TRUE;
                    }
                else if( $criterion->type == QC_TYPE_FLAG )
                    {
                    $value = $criterion->get_value();
                    $relation = $criterion->get_relation();
                    if( is_array($value) )
                        {
                        $flags_set = Array();
                        foreach( $value as $index=>$flag )
                            if( $flag )
                                $flags_set[] = $index;
                        $value = $this->join_value_array($flags_set);
                        }
                    else if( $value )
                        $value = '1';
                    if( !is_null($value) )
                        $clauses[] = Array( $criterion->get_qs_key(), $relation, $value );
                    }
                else
                    {
                    $index = $criterion->get_index();
                    $relation = $criterion->get_relation();
                    $value = $criterion->get_value();
                    
                    if( is_null($index) )
                        $index = $criterion->get_qs_key();

                    if( is_array($value) )
                        {
                        $value = $this->join_value_array($value);
                        if( $value != '' )
                            $is_value_array = TRUE;
                        }

                    if( preg_match('/^(\s+|\(|")/', $value) )
                        $needs_wrapping = TRUE;
                    
                    if( $value != '')
                        {
                        $clauses[] = Array( $index, $relation, $value );
                        }
                    }
                
                }
            }
        
        if( count($expanded_clauses) > 0 )
            {
            $needs_wrapping = TRUE;
            
            // magic clauses in the prescence of normal clauses should be wrapped
            // with parenthesis
            if( count($clauses) > 0 && $this->expanded_clause_count > 1)
                {
                array_unshift($expanded_clauses, "(");
                $expanded_clauses[] = ")";
                }
            
            $clauses = array_merge($expanded_clauses, $clauses);
            }
        // else
            {
            if( count($clauses) == 1 && $clauses[0][0] == 'default' && !( preg_match('/^(\s+|\(|")/', $clauses[0][2]) ) )
                return $clauses[0][2];
            // if( count($clauses) == 1 && $clauses[0][0] == 'default' )
            if( count($clauses) == 1 && !$needs_wrapping && $clauses[0][0] == 'default' )
                return $clauses[0][2];
            if( count($clauses) == 1 && $clauses[0][0] == 'q' )
                return $clauses[0][2];
            if( count($clauses) > 1 || $is_value_array )
                $needs_wrapping = TRUE;
            }
        
        foreach( $clauses as $clause )
            {
            if( is_array($clause) )
                $tmp[] = '{' . join('',$clause) . '}';
            else
                $tmp[] = $clause;
            }
        
        $result = join('',$tmp);
        
        if( !$needs_wrapping )
            $result = substr( $result, 1, -1 );

        return $result;
        }
        
    // 
    //// Converts a criterion into an Array of Arrays which include only the
    //// fields needed for working with the encoder.
    //
    private function expanded_criterion_to_clause(&$criterion)
        {
        global $CONF;
        $result = Array();
        
        if( is_array($criterion->value) )
            {
            for ($i = 0; $i < count($criterion->value); $i++)
                {
                if( !empty($criterion->value[$i]) )
                    {
                    if( $i < count($criterion->operator) && $criterion->operator[$i] != QC_OP_AND && count($result) > 0 )
                        $result[] = strtoupper($criterion->operator_to_string($i));
                        
                    $relation = QC_RELATION_EQ;
                    if( $i < count($criterion->relation) )
                        $relation = $criterion->relation[$i];
                    //### NOTE AV: probably should read what the default index is from a setting - no good
                    // reading it from previous
                    $index = @$criterion->index[$i];
                    if( is_null($index) )
                        $index = 'default';
                    $result[] = Array( $index, $relation, $criterion->value[$i] );
                    $this->expanded_clause_count++;
                    }
                }
            }
        else
            {
            if( $criterion->operator != QC_OP_AND && count($result) > 0 )
                $result[] = strtoupper($criterion->operator_to_string());
            $result[] = Array( $criterion->index, $criterion->relation, $criterion->value );
            }
        
        return $result;
        }
        
    

    private function join_value_array( &$value_array )
        {
        while( count($value_array) > 0 && $value_array[count($value_array)-1] == '' )
            {
                end($value_array);
                $k = key($value_array);
                unset($value_array[$k]);
            }
        return join(',', $value_array);
        }
    }