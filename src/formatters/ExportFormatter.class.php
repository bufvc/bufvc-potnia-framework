<?php
// $Id$
// Base Export Formatter for IRN/LBC project
// Phil Hansen, 05 Nov 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


/** The ExportFormatter class is a base class for the
    various export formatters. It contains the primary function
    for formatting records, and provides functions for
    easy customization on a per format basis.
    
*/
class ExportFormatter
    {
    /// Module object
    var $module;
    
    /// Element label for this formatter
    var $label = 'text_label';
    
    /// Element label for the record url
    var $url_label = 'URL';
    
    /// Line break string
    var $newline = "\r\n";
    
    /// Repeat elements flag
    /// If true, elements with multiple values are repeated
    /// If false, elements with multiple values are joined
    var $repeat_elements = FALSE;
    
    /// The string to use when joining elements with multiple values
    /// Default is to join with semi-colons
    var $join_string = '; ';
    
    /// Specify a separator string to use between records in a list
    var $record_separator = '';
    
    /// Specify a file name extension
    var $file_ext = '';
    
    // utility object
    var $_util = NULL;
    
    function ExportFormatter($module, $util=NULL)
        {
        $this->module = $module;
        if (is_null($util))
            $util = new ExportFormatterUtil();
        $this->_util = $util;
        }
    
    /// Formats a record for export
    function format($record)
        {
        $record = $this->_util->format_fields($record);
        
        if (empty($record))
            return '';
        
        // get table meta data
        $table = $this->module->retrieve($record['_table']);
        $map = $this->get_label_map($table, $this->label);
        
        $result = Array();
        $tmp = $this->get_record_header($record);
        if (!empty($tmp))
            $result[] = $tmp;
        
        foreach ($record as $name=>$value)
            {
            // check for additional labels
            if (isset($table[$this->label.'_extras'][$name]))
                {
                $map[$name] = $table[$this->label.'_extras'][$name];
                unset($table[$this->label.'_extras'][$name]);
                }
            
            // only process fields that map to elements
            if (!isset($map[$name]))
                continue;
            if (empty($value))
                continue;
            
            if (is_array($value))
                {
                // elements with multiple values are repeated
                if ($this->repeat_elements)
                    {
                    foreach ($value as $item)
                        {
                        // special case, handle double indexed arrays
                        if (is_array($item))
                            {
                            if (isset($item['name']))
                                $item = $item['name'];
                            else if (isset($item['title']))
                                $item = $item['title'];
                            }
                        if (empty($item))
                            continue;
                        $result[] = $this->get_element_output($map[$name], $item);
                        }
                    }
                // elements with multiple values are joined
                else
                    {
                    // special case, handle double indexed arrays
                    if (is_array($value[0]))
                        {
                        $value = $this->get_values_from_arrays($value);
                        if (empty($value))
                            continue;
                        }
                    $values = $this->join_values($map[$name], $value);
                    $result[] = $this->get_element_output($map[$name], $values);
                    }
                }
            else
                {
                $result[] = $this->get_element_output($map[$name], $value);
                }
            }
        
        // add any additional static elements from table
        if (isset($table[$this->label.'_static']))
            {
            foreach ($table[$this->label.'_static'] as $name=>$value)
                $result[] = $this->get_element_output($name, $value);
            }
        
        $extra = $this->get_extra_elements();
        if (!empty($extra))
            $result = array_merge($result, $extra);
        
        $url = $this->get_record_url($record['url']);
        if (!empty($url))
            $result[] = $url;
        $tmp = $this->get_record_footer($record);
        if (!empty($tmp))
            $result[] = $tmp;
        return join($this->newline, $result).$this->newline;
        }
    
    /// Get record header
    function get_record_header($record)
        {
        return '';
        }
    
    /// Get record footer
    function get_record_footer($record)
        {
        return '';
        }
    
    /// Get the formatted record url
    function get_record_url($url)
        {
        return $this->get_element_output($this->url_label, $this->module->url('index', $url));
        }
    
    /// Get formatted output for a single element
    function get_element_output($name, $value)
        {
        return "$name: $value";
        }
    
    /// Get any additional elements
    /// This function should return an array of elements
    function get_extra_elements()
        {
        return Array();
        }
    
    /// Join values
    function join_values($name, $value)
        {
        return join($this->join_string, $value);
        }
    
    /// Get any header output for this format
    function get_header()
        {
        return '';
        }
    
    /// Get any footer output for this format
    function get_footer()
        {
        return '';
        }
    
    /// Handle double-indexed content arrays
    /// Valid field name indexes are 'name' or 'title'
    /// Returns an array of values
    function get_values_from_arrays($data)
        {
        $values = Array();
        // get field name
        if (isset($data[0]['name']))
            $field_name = 'name';
        else if (isset($data[0]['title']))
            $field_name = 'title';
        else
            return $values;

        foreach ($data as $item)
            $values[] = $item[$field_name];
        return $values;
        }
    
    // Build map of field names to labels from table data
    function get_label_map($table, $label)
        {
        $map = Array();
        foreach ($table['fields'] as $name=>$value)
            {
            if (isset($value[$label]))
                $map[$name] = $value[$label];
            }
        return $map;
        }
    }

/** Contains functions for export formatters.
    These functions are factored here for easy customization through subclassing.
*/
class ExportFormatterUtil
    {
    /// Perform any field formatting before exporting
    function format_fields($record)
        {
        if (is_null($record) || !is_array($record) || count($record) == 0)
            return Array();
        
        return $record;
        }
    }
?>
