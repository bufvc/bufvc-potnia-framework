<?php
// $Id$
// Query class
// Alexander Veenendaal, 11 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('QC_TYPE_TEXT', 'text');
define('QC_TYPE_LIST', 'list');
define('QC_TYPE_FLAG', 'flag');
define('QC_TYPE_SORT', 'sort');
define('QC_TYPE_DATE_RANGE', 'drange');
define('QC_TYPE_OPTION', 'opt');

define('QC_STATUS_OK', 'ok');
define('QC_STATUS_NOTE', 'note');
define('QC_STATUS_WARNING', 'warn');
define('QC_STATUS_ERROR', 'err');

define('QC_OP_EQUAL', 'eq');
define('QC_OP_AND', 'and');
define('QC_OP_OR', 'or');
define('QC_OP_NOT', 'not');

define('QC_RELATION_EQ', '=');
define('QC_RELATION_GT', '>');
define('QC_RELATION_LT', '<');
define('QC_RELATION_GTE', '>=');
define('QC_RELATION_LTE', '<=');
define('QC_RELATION_NEQ', '<>');

// Query search modes
//### TODO AV : not sure they should exist here
define('QC_MODE_BASIC', 'basic');
define('QC_MODE_ADVANCED','adv');


define('QC_DATE_RANGE_QS_POSTFIX_START', '_start');
define('QC_DATE_RANGE_QS_POSTFIX_END', '_end');


define('QC_DATE_YEAR', 0);
define('QC_DATE_MONTH', 1);
define('QC_DATE_DAY',2);
define('QC_DATE_HOUR', 3);
define('QC_DATE_MINUTE', 4);

// 
//// an exception subclass specifically for QueryCriterion errors
// 
class QueryCriterionException extends Exception {}

///
/// A base class for representing Query controls
///
class QueryCriterion
    implements Countable
    {    
    /// unique identifier
    var $name = NULL;
    
    /// the QueryString key used. In the abscence of a value, the name
    /// is used
    var $qs_key = NULL;
  
    /// The User visible description of this instance. Most commonly used for label elements in html forms
    var $label = '';
    
    /// A label used for rendering situations.
    /// The main example for use if with QC_TYPE_FLAG, when the rendering of a label after the input field is
    /// not desired but a sensible (ie non-name) label needs to be returned in get_render_details.
    /// TODO AV : decide whether this is the best solution
    var $render_label;
    
    /// 
    var $value = NULL;
    
    /// 
    var $index = NULL;
    
    /// The default value for index which is set at the point of instantiation and
    /// is reverted to when the value of the instance is cleared.
    var $index_default = NULL;
    
    /// a map of qs_keys to index values
    var $qs_key_index = NULL;
    
    /// affects the combination of this criterion within a query
    var $operator = QC_OP_AND;
    
    var $relation = QC_RELATION_EQ;
    
    /// the value that this criterion is populated from in the absence of another choice. if this 
    /// criteria is of type LIST or SORT, then setting this value to a key from 'var list' will 
    /// select that as the default value.
    var $default = NULL;
    
    /// In the absence of an assigned value for this instance, this value will be used for
    /// rendering. Not the same as the $default value, because this field is for cosmetic/rendering
    /// purposes only.
    var $render_default = NULL;
  
    /// what kind of value this criteria represents
    var $type = QC_TYPE_TEXT;
  
    /// indicates whether this field will be considered as part of the query
    var $is_enabled = TRUE;
  
    /// a html renderer may choose to render this criteria as a hidden field
    var $is_visible = TRUE;
  
    /// indicates whether this is something that should be displayed only in an advanced view
    var $mode = QC_MODE_BASIC;
    
    /// used to indicate or flag issues or problems.
    var $status = QC_STATUS_OK;
  
    /// accompanying text to further elaborate on status message
    var $status_message = '';
  
    /// if this instance is in error, then this var will be set to the qs_key reporting the error.
    /// This is particularly useful in the case of date ranges, when one or both of the values may
    /// be out of bounds
    var $error_qs_key = NULL;
  
    /// if this criteria is of type LIST or SORT, then this value will be used to populate it.
    var $list = NULL;
  
    /// this variable contain the upper and lower acceptable bounds of the value, given as a two 
    /// element Array in the form Array( low, high ). When used in conjunction with the 
    /// type QC_TYPE_DATE_RANGE, the values will be two string dates in the 
    /// form "YYYY-MM-DD". When applied to a QC_TYPE_TEXT, this value could contain 
    /// two numbers indicating the minimum and maxium characters in a string.
    var $range = NULL;
  
    /// indicates whether this criterion will be renderable
    ///### TODO AV : this is a temporary hack to prevent certain criterion (such as sort and date) being rendered
    ///          out in html/text views.
    var $is_renderable = TRUE;
    
    /// Used to 'reserve' the number of advanced values that this instance will contain 
    var $advanced_value_count = NULL;
    
    /// Primary criteria are granted special privileges, such as being non-removable as always returning a default
    /// value when rendering
    /// NOTE AV : still experimental...
    var $is_primary = TRUE;
    
    /// when rendering the list, this var indicates whether keys should be
    /// numeric or not
    var $use_integer_list_keys = FALSE;
    
    /// Whether this instance is able to be included as part of an encoded query.
    /// Used for certain fields which are used in 'meta' roles, such as page_size - a field
    /// which affects the query in a non-direct way.
    /// This field is used primarily by a query encoder
    var $is_encodable = TRUE;
    
    /// a string that may be used in the presentation of this criteria to supply useful
    /// information on its usage.
    var $help = '';
    
    function __construct($attributes = nil)
        {
        // default attributes for a criterion
        static $defaults = array(
            'type' => QC_TYPE_TEXT,
            'is_enabled' => TRUE,
            'is_visible' => TRUE,
            'operator' => QC_OP_AND,
            'status' => QC_STATUS_OK,
            'status_message' => '',
            'list' => NULL,
            'range' => NULL,
            'is_renderable' => TRUE,
            'mode'=> QC_MODE_BASIC,
            'is_primary' => FALSE,
            );

        // merge defaults with incoming attributes
        $attributes = array_merge( $defaults, $attributes );

        if( !isset($attributes['name']) )
            throw new QueryCriterionException('no name specified');

        $this->applyAttributes($attributes);

        if( is_null($this->qs_key) )
            {
            $this->qs_key = $this->name;
            }
        }
        
    /// Applies any values from the incoming associative array onto the instances variables
    function applyAttributes($attributes)
        {
        $this->name = $attributes['name'];
        $this->type = $attributes['type'];
        $this->is_renderable = $attributes['is_renderable'];
        $this->list = $attributes['list'];
        
        if( isset($attributes['label']) )
            $this->label = $attributes['label'];
        if( isset($attributes['render_label']) )
            $this->render_label = $attributes['render_label'];
        // note that range must be set before value, otherwise values aren't set
        if( isset($attributes['range']) )
            $this->set_range( $attributes['range'] );
        if( isset($attributes['value']) )
            $this->set_value( $attributes['value'] );
        if( isset($attributes['default']) )
            $this->set_default( $attributes['default'] );
        if( isset($attributes['render_default']) )
            $this->render_default = $attributes['render_default'];
        if( isset($attributes['mode']) )
            $this->mode = $attributes['mode'];
        if( isset($attributes['qs_key']) )
            $this->qs_key = $attributes['qs_key'];
        if( isset($attributes['index']) )
            {
            $this->index = $attributes['index'];
            $this->index_default = $attributes['index'];
            }
        if( isset($attributes['relation']) )
            $this->relation = $attributes['relation'];
        if( isset($attributes['advanced_value_count']) && is_int($attributes['advanced_value_count']) && $this->type == QC_TYPE_TEXT )
            $this->advanced_value_count = $attributes['advanced_value_count'];
        if( isset($attributes['is_primary']) )
            $this->is_primary = $attributes['is_primary'];
        if( isset($attributes['use_integer_list_keys']) )
            $this->use_integer_list_keys = $attributes['use_integer_list_keys'];
        if( isset($attributes['is_encodable']) )
            $this->is_encodable = $attributes['is_encodable'];
        if( isset($attributes['help']) )
            $this->help = $attributes['help'];
        if( isset($attributes['qs_key_index']) && is_array($attributes['qs_key_index']))
            $this->qs_key_index = $attributes['qs_key_index'];
        }

    // will return TRUE if this instances value is the
    // same as the default. The optional index parameter
    // is used for situations where this value deals with
    // Array values, such as date ranges.
    ///### TODO AV : this function is starting to ramble - probably an argument for refactor into subclasses
    function is_set_to_default($index=NULL)
        {
        global $CONF;

        if( is_null($this->value) )
            return TRUE;
        
        if( is_null($this->default) )
            {
            if( $this->value === 0 )
                return FALSE;
            if( is_null($this->value) )
                return TRUE;
            }

        if( !is_null($index) && is_array($this->value) )
            {
            if( @$this->value[$index] == $this->default[$index] )
                return TRUE;
            }
        if( is_array($this->default) && !is_array($this->value) )
            {
            return FALSE;
            // return $this->value == $this->default[0];
            }
        
        if( $this->value == $this->default )
            return TRUE;

        return FALSE;
        }
       
    /// Sets the default value for this criterion, the value that
    /// is returned in the absence of a real value
    function set_default( $default )
        {
        $this->default = $default;
        }
        

    /// Returns the index corresponding to the given qs key
    function qs_key_to_index( $qs_key )
        {
        if( is_int($qs_key) )
            return $qs_key;
        list($name,$index,$field) = $this->parse_qs_key( $qs_key );
        return $index;
        }
    
    /// Resets the values of this instance back to initial
    function clear( $from_qs_key=NULL )
        {
        }
    
    /// The presence of the from_qs_key parameter will allow a specific part of the value
    /// to be set if appropriate.
    ///### TODO AV : implement the validation logic 
    function set_value( $value=NULL, $from_qs_key=NULL, $and_validate=TRUE )
        {
        global $CONF;
        $this->status = QC_STATUS_OK;
        
        if( is_null($value) )
            $value = $this->get_default();
        
        $this->value = $value;

        return $this->status;
        }
        
    //// 
    function apply_value( $index,$field,$value )
        {
        global $CONF;
        
        switch( $field )
            {
            case 'index':
                $this->index[$index] = $value;
                break;
            case 'relation':
                $this->relation[$index] = $value;
                break;
            case 'oper':
                $this->operator[$index] = $this->to_operator( $value, FALSE );
                break;
            default:
                $this->value[$index] = $value;
                if( is_null($value) )
                    {
                    $this->index[$index] = '';
                    $this->operator[$index] = QC_OP_AND;
                    $this->relation[$index] = QC_RELATION_EQ;
                    }
                break;
            }
        }

    //// Parses a given string and returns an array containing the name, index and field of the passed value
    static function parse_qs_key( $qs_key, $use_new_notation_only=FALSE )
        {
        global $CONF;
        /// incoming qs_key will normally be a string, but just in case we get passed a direct index...
        if( is_int($qs_key) )
            return Array( NULL, $qs_key, NULL );
        
        if( preg_match( "/^([a-zA-Z\_]+)\[([a-zA-Z\_0-9]+)\](\[[(\'|\"]?([a-zA-Z]+)[(\'|\"]?\])?/", $qs_key, $elements ) )
            {
            $name = $elements[1];
            $index = $elements[2];
            //### TODO AV : urgh, regexp comes back with two results if the field is present
            $field = ( count($elements) >= 5 ) ? $elements[4] : NULL;
            return Array($name,$index,$field);
            }
        
        if( !$use_new_notation_only && preg_match( "/^([a-zA-Z]+)_([a-zA-Z]+)\[?([0-9]+)/", $qs_key, $elements ) )
            {
            $name = $elements[1];
            $field = $elements[2];
            $index = $elements[3] - 1;
            return Array($name,$index,$field);
            }
        
        return Array( $qs_key, 0, NULL );
        }
    
    
    //// Returns TRUE if the incoming value matches the name of this instance
    function does_qs_key_match( $qs_key )
        {
        list($name,$index,$field) = $this->parse_qs_key( $qs_key );
        
        if( is_null($name) )
            return FALSE;
        
        $this_qs_key = $this->qs_key;
        if( is_null($this_qs_key) )
            $this_qs_key = $this->name;
        
        if( is_array($this_qs_key) )
            {
            if( in_array($name, $this_qs_key) )
                return TRUE;
            }
        return $name == $this_qs_key;
        }

    /// Returns TRUE if this instance is by its own definition expanded
    /// from its original form
    function is_expanded()
        {
        return FALSE;
        }
        
    /// Returns TRUE if this instance has been marked or otherwise 
    /// has characteristics which suggest is usage is advanced.
    function is_advanced()
        {
        return $this->mode == QC_MODE_ADVANCED;
        }

    // Returns the number of values this instance contains
    function count()
        {
        global $CONF;
        if( is_null($this->value) )
            return 0;
        elseif( is_array($this->value) )
            $value_count = count($this->value);
        else
            $value_count = 1;
        
        return $value_count;
        }
    
        
    /// Checks whether the three fields concerned with magicness are
    /// ready to act as arrays; if they aren't they get converted to 
    /// arrays (with fields intact) 
    function ensure_values_are_arrays()
        {
        if( !is_array($this->value) )
            {
            if( !is_null($this->value) )
                $this->value = Array( $this->value );
            else
                $this->value = Array();
            }
        }
    
    /// Returns the unique identifier of this instance
    function get_name()
        {
        $result = $this->name;
        
        if( is_array($result) )
            $result = $result[0];
            
        return $result;
        }
        
    /// Returns the user facing string of text used for hints about this instances usage
    function get_help_text()
        {
        return $this->help;
        }
    
    /// Returns the qs key that identifies this instance. 
    /// Since a querycriterion may be identified by many
    /// qs keys, only the first one is returned unless otherwise
    /// requested.
    function get_qs_key($return_all_possible_values=FALSE)
        {
        global $CONF;
        $result = $this->name;
        
        if( !is_null($this->qs_key) )
            {
            $result = $this->qs_key;
            if( is_array($this->qs_key_index) )
                {
                // merge the qskeys and qs_key_index arrays into one
                $keys = array_keys($this->qs_key_index);
                foreach( $keys as $key )
                    if( array_search( $key, $result ) === FALSE )
                        $result[] = $key;
                }
            }
            
        
        if( is_array($result) && !$return_all_possible_values )
            $result = $result[0];

        return $result;
        }
        
    
    // 
    //// Converts an incoming value to an operator value 
    // 
    function to_operator( $value, $set_instance_var=TRUE )
        {
        $value = trim(strtolower($value));
        $result = QC_OP_AND;
        
        switch( $value )
            {
            case 'or':
                $result = QC_OP_OR;
                break;
            case 'not':
                $result = QC_OP_NOT;
                break;
            default:
                // //### NOTE AV : Maybe should flag an error if unidentified ?
                $result = QC_OP_AND;
                break;
            }
        
        if( $set_instance_var )
            $this->operator = $result;
        
        return $result;
        }
        
    /// Converts the QC_OP constant defined in this instance
    /// into a string
    function operator_to_string( $index = NULL )
        {
        $operator = $this->operator;
        
        if( !is_null($index) && is_array($this->operator) )
            $operator = @$this->operator[$index];
        else if( is_array($this->operator) )
            $operator = @$this->operator[0];
            
        switch( $operator )
            {
            case QC_OP_OR:
                return 'or';
            case QC_OP_NOT:
                return 'not';
            default:
                return 'and';
            }
        }
    
    /// Converts the parameter to a boolean TRUE or FALSE value. Returns the default
    /// value if unidentified
    function to_bool_old( $value )
        {
        global $CONF;
        if( is_bool($value) )
            return $value;
        else if( is_string($value) )
            {
            $value = trim(strtolower($value));
            
            if( $value == '1' || $value == 'y' || $value == 'true' || $value == 'yes' || $value == 'on' )
                return TRUE;
            else if( $value == '0' || $value == 'n' || $value == 'false' || $value == 'no' || $value == 'off' )
                return FALSE;
            }
        else if( is_integer($value) )
            {
            return $value > 0;
            }
        // nothing has been identified, so set the error flag and return the default value
        $this->status = QC_STATUS_ERROR;
        return $this->get_default();
        }
    
    
    // If the passed value is a boolean, the result reference paramter is set to its value
    // Returns TRUE if a successful conversion occured, FALSE otherwise
    function is_bool( $value, &$result=NULL )
        {
        global $CONF;
        if( is_bool($value) )
            {
            $result = $value;
            return TRUE;
            }
        else if( is_string($value) )
            {
            $value = trim(strtolower($value));

            if( $value == '1' || $value == 'y' || $value == 'true' || $value == 'yes' || $value == 'on' )
                {
                $result = TRUE;
                return TRUE;
                }
                
            else if( $value == '0' || $value == 'n' || $value == 'false' || $value == 'no' || $value == 'off' )
                {
                $result = FALSE;
                return TRUE;
                }
            }
        else if( $value === 1 )
            {
            $result = TRUE;
            return TRUE;
            }
        else if( $value === 0 )
            {
            $result = FALSE;
            return TRUE;
            }
        return FALSE;
        }

    //
    //// Returns TRUE if the passed criterion object or value
    //// is equal to this instances value
    ////### TODO AV : compare date values 
    // 
    function is_value_equal( $other )
        {
        global $CONF;
        if( $other instanceof QueryCriterion )
            {
            if( !$this->value_compare( $this->get_value(), $other->get_value() ) )
                return FALSE;
            
            if( is_array($this->value) )
                {
                if( count($this) != count($other) )
                    {
                    return FALSE;
                    }
                // look for advanced values
                for ($i = 0; $i < $this->count(); $i++)
                    {                    
                    if( !$this->value_compare( @$this->value[$i], @$other->value[$i] ) )
                        return FALSE;
                    if( !$this->value_compare( @$this->index[$i], @$other->index[$i] ) )
                        return FALSE;
                    if( !$this->value_compare( @$this->relation[$i], @$other->relation[$i] ) )
                        return FALSE;
                    }
                }
            }
        else if( !$this->value_compare($this->get_value(), $other ) )
            return FALSE;
        return TRUE;
        }

    /// Returns TRUE if the two values are equal
    private static function value_compare( $first, $second )
        {
        if (($first === '' && ($second === 0 || $second === '0')) ||
            (($first === 0 || $first === '0') && $second === ''))
            return FALSE;
        if( empty($first) && !empty($second) )
            return FALSE;
        if ( $first != $second)
            return FALSE;
        return TRUE;
        }
    
    /// returns the instances value. 
    /// If the first parameter is not null, the value is returned from the
    /// part of the value that matches the qs_key.
    /// If the value, or part of the value, is empty, setting the 2nd parameter
    /// to TRUE will return defaults as well
    function get_value( $from_qs_key=NULL, $or_default_if_empty=TRUE )
        {
        global $CONF;
        if( !is_null($this->value) || !$or_default_if_empty)
            {
            $index = $this->qs_key_to_index($from_qs_key);
            return $this->value;
            }
        return $this->get_default($from_qs_key);
        }
    

    
    function get_index( $from_qs_key=NULL )
        {
        return NULL;
        }
        
    function get_relation( $from_qs_key=NULL )
        {
        if( is_array($this->relation) )
           return $this->relation[ $this->qs_key_to_index($from_qs_key) ];
        return $this->relation;
        }
    
    function get_operator( $from_qs_key=NULL )
        {
        return NULL;
        }
    
    function get_default( $from_qs_key=NULL )
        {
        if( is_array($this->value) && !is_null($from_qs_key) )
            return $this->default[ $this->qs_key_to_index($from_qs_key) ];
        return $this->default;
        }

    /// Returns the name of the list associated with this instance
    function get_list( $from_qs_key=NULL )
        {
        if( !is_null($this->list) )
            return $this->list;
        return null;
        }

    /// Returns the value suitable for presentation  
    function get_rendered_value( $or_default_if_empty=TRUE )
        {
        $result = $this->get_value(NULL, FALSE);
        if( $result == '' )
            $result = $this->render_default;
        if( $result == '' )
            $result = $this->get_value(NULL,TRUE);
        return $result;
        }
        
    
    /// Returns an array of this instances qs_key(s) mapped to arrays of value/index/operator
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_clauses($include_default_values=FALSE)
        {
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $qs_key[0];
        
        if( is_null($this->value) == FALSE )
            $result[$qs_key] = Array( $this->value, NULL, NULL );

        return $result;
        }
    
    /// Returns an array of this instances qs_key(s) mapped to its value(s)
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_values($include_default_values=FALSE, $new_format=TRUE)
        {
        global $CONF;
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $this->qs_key[0];
        // if(@$CONF['debug_trace'] ) println('set to default ' . $this->is_set_to_default() );
        if( !is_null($this->value) 
            && (($include_default_values && $this->is_set_to_default()) || !$this->is_set_to_default() ))
            $result[$qs_key] = $this->value;
        return $result;
        }

    /// A common version of the get_render_details function intended for
    /// sub-classes to use.
    /// Instead of returning the render details, it returns a boolean indicating
    /// whether an empty result has been returned. 
    /// The result of the function is set as the 2nd paremter reference.
    function get_render_details_base( $list_for_advanced, &$result=NULL )
        {
        global $CONF;
        $result = Array();

        if( !$this->is_renderable )
            return FALSE;

        if( $this->is_set_to_default() && !$this->is_primary )
            return FALSE;        
        $value = $this->get_rendered_value();
        if( empty($value) )
            return FALSE;
        $label = empty($this->label) ? $this->name : $this->label;
        if( !is_null($this->render_label) )
            $label = $this->render_label;
        
        $result['name'] = $this->name;
        $result['label'] = $label;
        $result['value'] = $value;

        if( $this->is_primary )
            $result['is_primary'] = TRUE;
        return TRUE;
        }
    
    /// Returns a key-value array of labels to values, used for
    /// rendering this instance.
    function get_render_details( $list_for_advanced=NULL )
        {
        $this->get_render_details_base( $list_for_advanced, $result );
        return $result;
        }
    
    ///### TODO AV : make this more meaningful
    function __toString() 
        {
        return "QueryCriterion(" . $this->name .")";
        }
    }


///
/// Generates instances or subclasses of QueryCriterion based on supplied parameters
///
class QueryCriterionFactory
    {
    static function create( $attributes)
        {
        $type = QC_TYPE_TEXT;
        if( isset($attributes['type']) )
            $type = $attributes['type'];
        switch( $type )
            {
            case QC_TYPE_FLAG:
                return new QueryCriterionFlag( $attributes );
            case QC_TYPE_DATE_RANGE:
                return new QueryCriterionDateRange( $attributes );
            case QC_TYPE_LIST:
            case QC_TYPE_SORT:
                return new QueryCriterionList( $attributes );
            case QC_TYPE_TEXT:
                return new QueryCriterionText( $attributes );
            case QC_TYPE_OPTION:
                return new QueryCriterionOption( $attributes );
            default:
                return new QueryCriterion( $attributes );
            }
        }
    }
    
    
    
    
/// The Option type of QueryCriterion represents groups or single of boolean style controls, only
/// one of which may be selected at a time
///
class QueryCriterionOption
    extends QueryCriterion
    {
    /// Returns a key-value array of labels to values, used for
    /// rendering this instance.
    function get_render_details( $list_for_advanced=NULL )
        {
        global $CONF;
        if( !$this->get_render_details_base( $list_for_advanced, $result ) )
            return $result;
        // if( @$CONF['debug_trace'] ) print_var($result);
        if( is_array($this->list) )
            $list_for_advanced = $this->list;

        if( is_array($this->value) )
            {
            $value = Array();
            if( !is_null($list_for_advanced) )
                {
                foreach( $list_for_advanced as $index=>$label )
                    {
                    if( isset($this->value[$index]) && $this->value[$index] )
                        {    
                        $value[] = $label;
                        }
                    }
                }
            $result['value'] = join($value, '; ');
            }
        else
            {
            if( !is_null($list_for_advanced) && isset($list_for_advanced[ $this->value ]) )
                $result['value'] = $list_for_advanced[ $this->value ];
            // If there is no list, and only a single TRUE value, then remove the value altogether.
            // NOTE AV : not sure about this still - this is to allow the rendering of a single flag
            // just to show the label. Really this sort of thing should be decided at the point of rendering
            else if( $result['value'] == TRUE )
                unset( $result['value'] );
            }
        return $result;
        }

    ///### TODO AV : make this more meaningful
    function __toString() 
        {
        return "QueryCriterionOption(" . $this->name .")";
        }
    }

/// The Flag type of QueryCriterion represents groups or single of boolean style controls.
///
class QueryCriterionFlag
    extends QueryCriterionOption
    {
    
    /// Sets the default value for this criterion, the value that
    /// is returned in the absence of a real value
    function set_default( $default )
        {
        global $CONF;
        
        if( is_array($default) )
            {
            foreach( $default as $index=>$flag )
                {
                // if the incoming flag value is not explicitly a boolean value, then set the flag as a index
                // NOTE AV : we cannot pass the default array reference as it alters the array, so we have to use
                // the intermediary $result_value instead
                $result_value = FALSE;
                if( !$this->is_bool( $flag, $result_value ) )
                    $this->default[$flag] = 1;
                else
                    $this->default[$index] = $result_value; 
                }
            }
        else
            {
            if( !$this->is_bool( $default, $this->default ) )
                $this->default[$default] = 1;
            }
            // $this->is_bool( $default, $this->default );
        }
        
    /// The presence of the from_qs_key parameter will allow a specific part of the value
    /// to be set if appropriate.
    ///### TODO AV : implement the validation logic 
    function set_value( $value=NULL, $from_qs_key=NULL, $and_validate=TRUE )
        {
        global $CONF;
        $this->status = QC_STATUS_OK;

        if( is_null($value) )
            $this->value = NULL;
        else if( is_array($value) )
            {
            $is_associative = array_is_assoc( $value ) || count($value) == 1;
            $this->ensure_values_are_arrays();
            
            foreach( $value as $index=>$flag )
                {
                // if( @$CONF['debug_trace'] ) println( $is_associative . " working with '$index' '$flag' - " . count($value));
                // NOTE AV : bit of a hack here to get around the fact that php has trouble recognising an
                // associative array when the index is 0
                // if( $is_associative )
                    $this->value[$index] = $flag;
                // else
                    // $this->value[$flag] = 1;
                }
            }
        else
            {
            list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );
            if( !isset($index) )
                $index = 0;
            
            if( $index === 0 && !is_array($this->value) )
                {
                if( is_integer($value) )
                    $this->value[$value] = 1;
                else if( !$this->is_bool( $value, $this->value ) )
                    $this->value[$value] = 1;
                }
            else
                {
                $this->ensure_values_are_arrays();
                $result_value = FALSE;
                if( !$this->is_bool( $value, $result_value ) )
                    $this->value[$value] = 1;
                else
                    $this->value[$index] = $result_value;
                }
            }
        return $this->status;
        }
        
    ///
    ///
    function get_value( $from_qs_key=NULL, $or_default_if_empty=TRUE )
        {
        global $CONF;
        if( !is_null($this->value) || !$or_default_if_empty)
            {
            $index = $this->qs_key_to_index($from_qs_key);
            if( is_array($this->value)  )
                {
                if( is_null($from_qs_key) )
                    return $this->value;
                if( isset($this->value[ $index ]) )
                    return $this->value[ $index ];
                if( isset($this->value[$from_qs_key]) )
                    return $this->value[$from_qs_key];
                return FALSE;
                }
            if( !is_null($from_qs_key) )
                return $this->value && $index === 1;
            return $this->value;
            }
        return $this->get_default($from_qs_key);
        }
        
    
    ///
    ///
    function get_default( $from_qs_key=NULL )
        {
        return $this->default;
        }


    ///
    ///
    function get_qs_key_values($include_default_values=FALSE, $new_format=TRUE)
        {
        global $CONF;
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $this->qs_key[0];

        if( !is_array($this->value) )
            {
            if( $this->value != FALSE )
                $result[$qs_key] = $this->value;
            }
        else
            {
            foreach( $this->value as $index=>$flag )
                {
                if( $flag )
                    $result[ $qs_key . '[' . $index . ']' ] = TRUE;
                }
            }

        return $result;
        }


    ///### TODO AV : make this more meaningful
    function __toString() 
        {
        return "QueryCriterionFlag(" . $this->name .")";
        }
    }
    

/// Represents a continuum of time between two (lower and upper) dates. 
class QueryCriterionDateRange
    extends QueryCriterion
    {
    
    
    function __construct($attributes = nil)
        {
        parent::__construct($attributes);
        
        // ensure that a default range is set
        if( is_null($this->range) )
            $this->set_range( array("1970-01-01", date('Y-m-d')) );
        if( is_null($this->default) )
            $this->set_default( array("1970-01-01", date('Y-m-d')) );
        }
        
    
    // will return TRUE if this instances value is the
    // same as the default. The optional index parameter
    // is used for situations where this value deals with
    // Array values, such as date ranges.
    function is_set_to_default($index=NULL)
        {
        global $CONF;
        if( is_null($index) )
            {
            if( is_null($this->value) )
                return TRUE;

            $match_a = TRUE;
            $match_b = TRUE;

            if( $this->value[0] != $this->default[0] )
                $match_a = FALSE;

            if( count($this->value) > 1 && $this->value[1] != $this->default[1] )
                $match_b = FALSE;

            if( $match_a && $match_b )
                return TRUE;
            }

        return parent::is_set_to_default($index);
        }
        
    /// Converts an incoming qs_key to a value index
    function qs_key_to_index( $qs_key )
        {
        if( string_ends_with($qs_key, QC_DATE_RANGE_QS_POSTFIX_END) )
            return 1;
        return 0;
        }
        
    /// The presence of the from_qs_key parameter will allow a specific part of the value
    /// to be set if appropriate.
    ///### TODO AV : implement the validation logic 
    function set_value( $value=NULL, $from_qs_key=NULL, $and_validate=TRUE )
        {
        global $CONF;
        $this->status = QC_STATUS_OK;

        if( is_null($value) )
            $value = $this->get_default();

        if( !is_null($from_qs_key) )
            {
            if( is_integer($from_qs_key) )
                {
                $name = $this->name;
                $index = $from_qs_key;
                }
            else
                list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );
            // determine which part of the range the qs key is referring to
            if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_START) || $name == $this->name )
                {
                if( $index > 0 )
                    $value = Array( self::date_set( $this->value[0], $value, $index ) );
                else
                    $value = Array( $value );
                }
            else if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_END) )
                {
                // we overwrite the values we already have
                $new_value = $this->get_value();
                if( $index > 0 )
                    $new_value[1] = self::date_set( $new_value[1], $value, $index );
                else
                    $new_value[1] = $value;
                $value = $new_value;
                }
            else
                {
                //### TODO AV : decide proper behaviour here
                throw new QueryCriterionException('invalid qs key specified: ' . $from_qs_key);
                }
            }
        
        // convert the incoming value into an array if it isn't already
        if( !is_array($value) )
            $value = Array($value);

        $preceeding_value = NULL;

        for($i = 0; $i < min( count($value), count($this->range) ); $i ++)
            {
            $qs_key = $this->qs_key . QC_DATE_RANGE_QS_POSTFIX_START;
            if( $i > 0 )
                $qs_key = $this->qs_key . QC_DATE_RANGE_QS_POSTFIX_END;
            $this->error_qs_key[ $qs_key ] = FALSE;
            if( !$this->is_range_value_valid($value[$i], $preceeding_value) )
                {
                /// NOTE AV - for listings at least, it seems that the invalid date still needs to be
                /// set, but a note of the error taken
                $this->status = QC_STATUS_ERROR;
                $this->error_qs_key[ $qs_key ] = TRUE;
                }
            $this->value[$i] = $this->to_date( $value[$i], $i==0 );
            $preceeding_value = strtotime($this->value[$i]);
            }
        return $this->status;
        }
        
        
    /// Returns TRUE if the specified value falls within this instances start and end values
    function is_range_value_valid( $value, $must_be_greater_than_seconds=NULL )
        {
        $value = $this->to_date( $value );
        $value_secs = strtotime($value);

        if( !is_null($must_be_greater_than_seconds) && $must_be_greater_than_seconds > $value_secs )
            return FALSE;

        if( $value_secs < strtotime($this->range[0]) || ($value_secs > strtotime($this->range[1])) )
            return FALSE;

        return TRUE;
        }


    /// sets the start and end of allowable values for this criterion.
    /// currently this is only applicable to date ranges
    function set_range( $value )
        {
        if( is_array($value) )
            {
            if( is_null($value[0]) )
                $value[0] = date('Y-m-d');
            if( !isset($value[1]) || is_null($value[1]) )
                $value[1] = date('Y-m-d');
            $this->range = array( $this->to_date($value[0]), $this->to_date($value[1], FALSE) );
            $this->default = $this->range;
            }
        }


    /// Returns the default value of this instance
    function get_default( $from_qs_key=NULL )
        {
        if( !is_null($from_qs_key) )
            {
            return $this->get_common_value( $this->range, $from_qs_key );
            /*list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );
            
            $part = $this->default[0];
            if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_END) )
                $part = $this->default[1];
            
            if( $index > 0 )
                {
                $elements = split('-', $part);
                return $elements[$index];
                }
            return $part;//*/
            }
        return $this->default;
        }
            
    /// In the absence of an argument, returns the upper and lower bound values of this instance
    /// When a QS key is specified (and matches) returns either the upper or lower bound. 
    function get_range($from_qs_key=NULL)
        {
        if( !is_null($from_qs_key) )
            {
            return $this->get_common_value( $this->range, $from_qs_key );
            /*list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );

            $part = $this->range[0];
            if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_END) )
                $part = $this->range[1];

            if( $index > 0 )
                {
                $elements = split('-', $part);
                return $elements[$index];
                }
            return $part;//*/
            }
        return $this->range;
        }

    /// returns an array containing all values between the range variable
    /// TODO AV : obviously this is hardwired to return years - explore ways of making this generic
    function get_range_array()
        {
        $result = Array();
        $start_parts = explode( "-", $this->range[0] );
        $end_parts = explode( "-", $this->range[1] );
        foreach (range($start_parts[0], $end_parts[0]) as $year)
            $result[] = $year;
        return $result;
        }

    ///
    function get_qs_key($return_all_possible_values=FALSE)
        {
        global $CONF;
        $result = $this->name;

        if( !is_null($this->qs_key) )
            $result = $this->qs_key;

        if( is_array($result) && !$return_all_possible_values )
            $result = $result[0];

        // because a date range maps to two values, we have to return
        // two variations of the base qskey

        if( $return_all_possible_values && is_array($result) )
            {
            $full_result = Array();
            foreach( $result as $key )
                {
                $full_result[] = $key . QC_DATE_RANGE_QS_POSTFIX_START;
                $full_result[] = $key . QC_DATE_RANGE_QS_POSTFIX_END;
                }
            return $full_result;
            }
        return Array( 
            $result . QC_DATE_RANGE_QS_POSTFIX_START, 
            $result . QC_DATE_RANGE_QS_POSTFIX_END );
            
        }
        
        
    /// A common get for the default, range and value which will interpret
    /// the qs key in the same way
    function get_common_value( $cvalue, $from_qs_key )
        {
        global $CONF;
        list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );
        $part = $cvalue[0]; //$this->value[0];

        if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_END) )
            $part = $cvalue[1];

        if( $index > 0 )
            {
            $elements = split('-', $part);
            // if the date value does not yet have a time part, then add it
            if( $index > 2 && count($elements) <= 3 )
                {
                
                if( string_ends_with($name, QC_DATE_RANGE_QS_POSTFIX_END) )
                    $elements = array_merge( $elements, Array( 23, 59 ) );
                else
                    $elements = array_merge( $elements, Array( 0, 0 ) );
                }
            return $elements[$index];
            }
        return $part;
        }

    /// returns the instances value. 
    /// If the first parameter is not null, the value is returned from the
    /// part of the value that matches the qs_key.
    /// If the value, or part of the value, is empty, setting the 2nd parameter
    /// to TRUE will return defaults as well
    ///
    /// An index may be specified within the qs_key in order to return a particular element of
    /// the date. For example:
    ///     date_start[1] - will return the 2nd or month part of the start date
    function get_value( $from_qs_key=NULL, $or_default_if_empty=TRUE )
        {
        global $CONF;
        if( !is_null($this->value) || !$or_default_if_empty)
            {
            if( !is_null($from_qs_key) )
                return $this->get_common_value( $this->value, $from_qs_key );
            
            // merge the values with the default
            return array_merge_or( $this->value, $this->default );
            }
        return $this->get_default($from_qs_key);
        }
        
    /// Returns a unix timestamp from one of the values
    /// the argument may either be an integer index to one of the values, or
    /// it can be a qs_key
    function get_timestamp( $from_qs_key=NULL )
        {
        $dates = $this->value;
        if( is_null($dates) )
            $dates = $this->default;
        if( is_null($dates) )
            $dates = $this->range;
        
        $index = 0;
        if( !is_null($from_qs_key) )
            {
            if( is_integer($from_qs_key) )
                $index = $from_qs_key;
            else if( string_ends_with($from_qs_key, QC_DATE_RANGE_QS_POSTFIX_END) )
                $index = 1;
            }
        
        $elements = split('-', $dates[$index] );

        $defaults = Array( 0, 0, 0, 0, 0, 0 );
        $elements = array_merge_or( $elements, Array( 0, 0, 0, 0, 0, 0 ) );
        return mktime( $elements[3], $elements[4], $elements[5], $elements[1], $elements[2], $elements[0] );
        }
        
    /// Returns the name of the list associated with this instance
    function get_list( $from_qs_key=NULL )
        {
        if( !is_null($this->list) )
            return $this->list;

        $qs_keys = $this->get_qs_key();
        if( !is_null($from_qs_key) )
            {
            if( in_array( $from_qs_key, $qs_keys) )
                return $from_qs_key;
            }
        // else
        return $qs_keys[0];
        }

    /// Returns the value suitable for presentation  
    function get_rendered_value( $or_default_if_empty=TRUE )
        {
        $result = $this->get_value(NULL, FALSE);

        if( is_null($result) )
            $result = $this->render_default;
        if( is_null($result) )
            $result = $this->get_value(NULL,TRUE);
            
        $result = Array(
            $this->date_trim( $result[0], $this->default[0] ),
            $this->date_trim( $result[1], $this->default[1] )
            );

        return $result;
        }


    /// Returns an array of this instances qs_key(s) mapped to arrays of value/index/operator
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_clauses($include_default_values=FALSE)
        {
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $qs_key[0];

        if( isset($this->value[0]) && is_null($this->value[0]) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default(0)) )
            $result[$qs_key . QC_DATE_RANGE_QS_POSTFIX_START] = Array( 
                $this->date_trim( $this->value[0], $this->default[0] ), NULL, NULL );

        if( isset($this->value[1]) && is_null($this->value[1]) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default(1)) )
            $result[$qs_key . QC_DATE_RANGE_QS_POSTFIX_END] = Array( 
                $this->date_trim( $this->value[1], $this->default[1] ), NULL, NULL );

        return $result;
        }

    /// Returns an array of this instances qs_key(s) mapped to its value(s)
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_values($include_default_values=FALSE, $new_format=TRUE)
        {
        global $CONF;
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $this->qs_key[0];

        if( isset($this->value[0]) && is_null($this->value[0]) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default(0)) )
            {
            $result[$qs_key . QC_DATE_RANGE_QS_POSTFIX_START ] = $this->date_trim( $this->value[0], $this->default[0] );
            }

        if( isset($this->value[1]) && is_null($this->value[1]) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default(1)) )
            {
            $result[$qs_key . QC_DATE_RANGE_QS_POSTFIX_END ] = $this->date_trim( $this->value[1], $this->default[1] );
            }


        return $result;
        }
        
    

    /// Returns a key-value array of labels to values, used for
    /// rendering this instance.
    /// TODO AV : it would be nice to template the datetime according to a strftime/sprintf string formatter from the datasource
    function get_render_details( $list_for_advanced=NULL )
        {
        if( !$this->get_render_details_base( $list_for_advanced, $result ) )
            return $result;
        $value = $result['value'];
        $start_date_elements = split( '-', $value[0] );
        if( count($start_date_elements) == 5 )
            $start_date_elements = join('-', array_slice( $start_date_elements, 0, 3 ) ) . ' ' . join(':', array_slice( $start_date_elements, 3) );
        else
            $start_date_elements = $value[0];
        
        if( $value[0] == $value[1] )
            {
            $result['label'] = 'Date';
            $result['value'] = $start_date_elements;
            }
        else
            {
            $end_date_elements = split( '-', $value[1] );
            if( count($end_date_elements) == 5 )
                $end_date_elements = join('-', array_slice( $end_date_elements, 0, 3 ) ) . ' ' . join(':', array_slice( $end_date_elements, 3) );
            else
                $end_date_elements = $value[1];
            
            $result['label'] = 'Date from';
            $result['value'] = $start_date_elements . ' to ' . $end_date_elements;
            }
        
        return $result;
        }

    function __toString() 
        {
        return "QueryCriterionDateRange(" . $this->name .")";
        }
        
    /// parses and verifies the incoming date
    /// in the abscence of date parts, setting $default_to_earliest to TRUE will
    /// result in the earliest possible date ie, 1st of Jan. Setting to FALSE
    /// produces the opposite behaviour
    ///### TODO AV : should probably report an invalid date in someway, possibly by throwing an exception
    //### TODO AV : this should also probably be in the utils collection 
    static function to_date( $date=NULL, $default_to_earliest=TRUE, $fully_expand=FALSE )
        {
        if( $date == '' )
            {
            if( $fully_expand )
                return date( 'Y-m-d', time() );
            return date( 'Y-m-d-H-i', time() );
            }
        if( $date[0] == '+' )
            return date('Y-m-d', time() + substr($date,1) );
        else if( $date[0] == '-' )
            return date('Y-m-d', time() - substr($date,1) );
        else if( is_string($date) && preg_match('/this_year/i', $date) )
            $date = date('Y');
        
        if( is_array($date) )
            $date_parts = $date;
        else
            $date_parts = preg_split( '/[-\.\/ ]/', $date, -1, PREG_SPLIT_NO_EMPTY );

        // we utilise the fact that setting the day to 0 will result in the
        // last day of the month being set
        $date = array( 1970, 12, 0 );
        if( $default_to_earliest )
            $date = array( 1970, 1, 1 );

        if( @isset($date_parts[0]) )
            $date[0] = $date_parts[0];
        if( @isset($date_parts[1]) )
            $date[1] = $date_parts[1];
        if( @isset($date_parts[2]) )
            $date[2] = $date_parts[2];
        else if( !$default_to_earliest )
            $date[1] += 1;

        $date_instance = date_create();
        date_date_set($date_instance, $date[0], $date[1], $date[2]);
        
        // deal with an extended format, ie hour and minutes
        if( count($date_parts) >= 5 )
            {    
            date_time_set( $date_instance, $date_parts[3], $date_parts[4] );
            return date_format($date_instance, 'Y-m-d-H-i');
            }
        
        if( $fully_expand )
            {
            if( !$default_to_earliest )
                date_time_set( $date_instance, 23, 59 );
            else
                date_time_set( $date_instance, 00, 00 );
            return date_format($date_instance, 'Y-m-d-H-i');
            }
        return date_format($date_instance, 'Y-m-d');
        }


    /// Removes parts of a date string which are the same as the $against date
    /// 2010-04-01 and 2010-04-01 becomes 2010-04 
    static function date_trim( $date, $against, $default_to_earliest=TRUE )
        {
        global $CONF;
        $date_parts = split('-', self::to_date( $date, $default_to_earliest, TRUE ) );
        $against_parts = split('-', self::to_date( $against, $default_to_earliest, TRUE ) );
        
        for( $i=count($date_parts)-1; $i>0; $i-- )
            {
            
            if( $date_parts[$i] == $against_parts[$i] )
                array_pop($date_parts);
            else
                break;
            }
        return join( $date_parts, '-' );
        }
    
    
    /// Sets a date value with an array index
    static function date_set( $date, $value, $index = 0 )
        {
        global $CONF;
        if( $index > 2 )
            $original_date = QueryCriterionDateRange::to_date( $date, TRUE, TRUE );
        else
            $original_date = QueryCriterionDateRange::to_date( $date );
        
        $date = split('-', $original_date);

        if( $index > 0 )
            $date[$index] = sprintf( '%02d', $value );
        else
            $date[$index] = sprintf( '%04d', $value );

        return join( $date, '-' );
        }
    }
    

class QueryCriterionText
    extends QueryCriterion
    {
        
    /// Resets the values of this instance back to initial
    function clear( $from_qs_key=NULL )
        {
        if( !is_null($from_qs_key) )
            {
            list($name,$index,$field) = $this->parse_qs_key( $qs_key );
            $this->index[0] = '';
            $this->operator[0] = QC_OP_AND;
            $this->relation[0] = QC_RELATION_EQ;
            $this->value[0] = NULL;
            }
        else
            {
            $this->value = NULL;
            $this->index = NULL;
            $this->operator = QC_OP_AND;
            $this->relation = QC_RELATION_EQ;
            }
        }
        
        
    /// The presence of the from_qs_key parameter will allow a specific part of the value
    /// to be set if appropriate.
    ///### TODO AV : implement the validation logic 
    function set_value( $value=NULL, $from_qs_key=NULL, $and_validate=TRUE )
        {
        global $CONF;
        $this->status = QC_STATUS_OK;
        $index = 0;
        
        if( is_null($value) )
            {
            $value = $this->get_default();
            // reset the other associated values back to their defaults
            $this->index = $this->index_default;
            $this->operator = QC_OP_AND;
            // NOTE AV : enabling this breaks a test in testQueryDataSourceEncoder::test_relation
            // $this->relation = QC_RELATION_EQ;
            }

        if( is_array($value) )
            {
            // examine whether this is incoming raw values, or just an array of basic types
            if( is_array(@$value[0]) )
                {
                // ensure that the index,operator and value fields are all arrays
                $this->ensure_values_are_arrays();
                foreach( $value as $index=>$collection )
                    foreach( $collection as $field=>$field_value )
                        $this->apply_value( $index, $field, $field_value );
                }
            else
                $this->value = Array($value);
            // break;
            }
        else if( is_null($from_qs_key) || $from_qs_key == $this->name || $from_qs_key == $this->qs_key )
            {
            $this->value = $value;
            }
        else
            {
            list($name,$index,$field) = $this->parse_qs_key( $from_qs_key );
            // ensure that the index,operator and value fields are all arrays
            $this->ensure_values_are_arrays();
            $this->apply_value( $index, $field, $value );
            }
            
        // if the values qs key happens to match a value from the qskey index, then set the new
        // index
        if( !is_null($from_qs_key) )
            {
            // if( @$CONF['dt'] ) println( "vals $from_qs_key $value");
            if( is_array($this->qs_key_index) && isset($this->qs_key_index[$from_qs_key]) )
                {
                $this->index[$index] = $this->qs_key_index[$from_qs_key];
                }
            }
        return $this->status;
        }
    
    // will return TRUE if this instances value is the
    // same as the default. The optional index parameter
    // is used for situations where this value deals with
    // Array values, such as date ranges.
    function is_set_to_default($index=NULL)
        {
        // special case, compare value arrays against blank default
        if (is_array($this->value) && $this->default == '')
            {
            foreach ($this->value as $value)
                {
                if ($value != '')
                    return FALSE;
                }
            return TRUE;
            }
        return parent::is_set_to_default($index);
        }
    
    /// Returns TRUE if this instance contains values that are marked as being advanced or by there very
    /// nature are advanced
    function is_expanded()
        {
        global $CONF;
        if( !is_array($this->value) )
            return FALSE;
        if( $this->count() <= 1 && (is_null($this->index[0]) || $this->index[0] == 'default') )
            return FALSE;
        return TRUE;
        }

    /// Returns TRUE if this instance has been marked or otherwise 
    /// has characteristics which suggest is usage is advanced.
    function is_advanced()
        {
        return $this->is_expanded() || parent::is_advanced();
        }
    
    // Returns the number of values this instance contains
    function count()
        {
        global $CONF;
        if( is_null($this->value) )
            return 0;
        elseif( is_array($this->value) )
            $value_count = count($this->value);
            //$value_count = @max(array_keys($this->value))+1;
        else
            $value_count = 1;

        if( is_array($this->value) )
            $value_count = @max(array_keys($this->value))+1;
        $relation_count = @max(array_keys($this->relation))+1;
        $index_count = @max(array_keys($this->index))+1;
        $operator_count = @max(array_keys($this->operator))+1;

        //### NOTE AV : grr at having to do this - might be better to properly dimension arrays on setting?
        return max( $value_count, $relation_count, $index_count, $operator_count );
        }
    
    /// Checks whether the three fields concerned with magicness are
    /// ready to act as arrays; if they aren't they get converted to 
    /// arrays (with fields intact) 
    function ensure_values_are_arrays()
        {
        if( !is_array($this->value) )
            {
            if( !is_null($this->value) )
                $this->value = Array( $this->value );
            else
                $this->value = Array();
            }

        if( !is_array($this->operator) )
            $this->operator = Array( $this->operator );
        if( !is_array($this->index) )
            $this->index = Array( $this->index );
        if( !is_array($this->relation) )
            $this->relation = Array( $this->relation );
        }
        
    /// returns the instances value. 
    /// If the first parameter is not null, the value is returned from the
    /// part of the value that matches the qs_key.
    /// If the value, or part of the value, is empty, setting the 2nd parameter
    /// to TRUE will return defaults as well
    function get_value( $from_qs_key=NULL, $or_default_if_empty=TRUE )
        {
        global $CONF;
        if( !is_null($this->value) || !$or_default_if_empty)
            {
            $index = $this->qs_key_to_index($from_qs_key);

            if( is_array($this->value) && $index < count($this->value) )
                return $this->value[ $index ];
            
            // if we have a basic value, then only respond to the first index
            if( $index == 0 )
                return $this->value;
            }
        return $this->get_default($from_qs_key);
        }
        
    /// Returns the index
    function get_index( $from_qs_key=NULL )
        {
        $index = $this->qs_key_to_index($from_qs_key);
        
        // the qs_key_index value will override any index
        if( isset($this->qs_key_index[$index]) )
            return $this->qs_key_index[$index];
        
        if( is_array($this->index) )
            return $this->index[ $index ];
            
        return $this->index;
        }

    ///
    function get_operator( $from_qs_key=NULL )
        {
        if( is_array($this->index) )
            return $this->operator[ $this->qs_key_to_index($from_qs_key) ];
        return $this->operator;
        }
    
    
    /// Returns an array of this instances qs_key(s) mapped to arrays of value/index/operator
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_clauses($include_default_values=FALSE)
        {
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $qs_key[0];

        if( !is_array($this->value) && is_null($this->value) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default()) )
            {
            $result[$qs_key] = $this->value;
            break;
            }
        if( $this->count() <= 1 && is_null($this->get_index()) )
            {
            if($include_default_values == FALSE && $this->is_set_to_default())
                break;
            $result[$qs_key] = $this->get_value();
            break;
            }

        if( is_array($this->value) )
            {
            for ($i = 0; $i <= count($this->value); $i++)
                {
                if( @!empty($this->value[$i]) )
                    {
                    $key_root = $qs_key . '[' . $i . ']';
                    $result[$key_root] = Array(
                        $this->value[$i],
                        // empty($this->value[$i]) ? "HAHAH" : $this->value[$i],
                        ($i < count($this->index) ? $this->index[$i] : NULL),
                        $this->operator_to_string($i));
                    }
                }
            }
        else
            $result[] = Array( $this->value, $this->index, $this->operator_to_string() );

        return $result;
        }

    /// Returns an array of this instances qs_key(s) mapped to its value(s)
    /// If the parameter is set to TRUE, default values will be returned in the
    /// absence of meaningful values.
    function get_qs_key_values($include_default_values=FALSE, $new_format=TRUE)
        {
        global $CONF;
        $result = Array();
        $qs_key = $this->qs_key;
        if( is_array($qs_key) )
            $qs_key = $this->qs_key[0];


        if( is_null($this->value) )
            return $result;

        if( !is_array($this->value) && is_null($this->value) == FALSE && !($include_default_values == FALSE && $this->is_set_to_default()) )
            {
            $result[$qs_key] = $this->value;
            return $result;
            }
        if( $this->count() <= 1 && (is_null($this->get_index()) || $this->get_index() == 'default') )
            {
            if($include_default_values == FALSE && $this->is_set_to_default())
                return $result;
            $result[$qs_key] = $this->get_value();
            return $result;
            }

        for ($i = 0; $i < $this->count(); $i++)
            {                    
            if( @!empty($this->value[$i]) )
                {
                $key_root = $qs_key . '[' . $i . ']';

                if( $new_format )
                    $result[ $key_root . "[v]"] = $this->value[$i];
                else
                    $result[ $qs_key . '_q' . ($i+1)] = $this->value[$i];

                if( $i < count($this->index) && !empty($this->index[$i]))
                    {
                    if( $new_format )
                        $result[ $key_root . "[index]"] = $this->index[$i];
                    else
                        $result[ $qs_key . '_index' . ($i+1)] = $this->index[$i];
                    }

                if( $new_format )
                    $result[ $key_root . "[oper]" ] = $this->operator_to_string($i);
                else
                    $result[ $qs_key . '_oper' . ($i+1)] = $this->operator_to_string($i);
                }
            }


        return $result;
        }

    
    /// Returns a key-value array of labels to values, used for
    /// rendering this instance.
    function get_render_details( $list_for_advanced=NULL )
        {
        if( !$this->get_render_details_base( $list_for_advanced, $result ) )
            return $result;
        
        if( $this->is_expanded() )
            {
            $clauses = $this->get_qs_key_clauses();
            // if this is an empty query, then don't change the value returned
            // by the call to get_render_details_base
            if( count($clauses) <= 0 )
                return $result;

            $is_first_clause = TRUE;
            $value = '';
            foreach( $clauses as $name=>$clause )
                {
                if( $is_first_clause )
                    $is_first_clause = FALSE;
                else
                    $value .= strtoupper($clause[2]) . ' ';
                if( isset($list_for_advanced[$clause[1]]) )
                    $value .= "'" . $clause[0] . "' in " . $list_for_advanced[$clause[1]] . ' ';
                else
                    $value .= "'" . $clause[0] . "' in " . $clause[1] . ' ';
                }
            $result['value'] = trim($value);
            }
    
        return $result;
        }
    
    function __toString() 
        {
        return "QueryCriterionText(" . $this->name .")";
        }
    }
    
    
class QueryCriterionList
    extends QueryCriterion
    {    
    /// Returns a key-value array of labels to values, used for
    /// rendering this instance.
    function get_render_details( $list_for_advanced=NULL )
        {
        global $CONF;
        $result = null;
        
        if( !$this->get_render_details_base( $list_for_advanced, $result ) )
            return $result;

        $value = $result['value'];

        if( !is_null($list_for_advanced) )
            {
            if( is_array($value) )
                {
                $found = array();
                foreach( $value as $element )
                    if( isset($list_for_advanced[$element]) )
                        $found[] = $list_for_advanced[$element];
                $result['value'] = join(', ', $found );
                }
            else if( isset($list_for_advanced[$value]) )
                {
                $result['value'] = $list_for_advanced[$value];
                }
            }
        return $result;
        }
        
    ///### TODO AV : make this more meaningful
    function __toString() 
        {
        return "QueryCriterionList(" . $this->name .")";
        }
    }
