<?php
// $Id$
// JSON Formatter for IRN/LBC project
// Phil Hansen, 20 Sept 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The JsonFormatter class provides functions for parsing a
    record and formatting it as a JSON object.

    All fields in the record are included in the JSON object.
    If text labels are defined for a field, these are included as
    a 'label' field.
*/
class JsonFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'text_label';
    
    /// Specify a separator string to use between records in a list
    var $record_separator = ',';
    
    /// Specify a file name extension
    var $file_ext = '.json';
    
    /// Formats a record for export
    /// This function does not follow the algorithm of the base class
    function format($record)
        {
        $record = $this->_util->format_fields($record);
        $record = $this->_remove_fields($record);
        
        if (empty($record))
            return '';
        
        // get table meta data
        $table = $this->module->retrieve($record['_table']);
        $map = $this->get_label_map($table, $this->label);
        
        $result = Array();
        
        foreach ($record as $name=>$value)
            {
            // check for additional labels
            if (isset($table[$this->label.'_extras'][$name]))
                {
                $map[$name] = $table[$this->label.'_extras'][$name];
                unset($table[$this->label.'_extras'][$name]);
                }
            
            // check for a label and store the field
            $label = isset($map[$name]) ? $map[$name] : '';
            $result[$name] = Array(
                'label' => $label,
                'value' => $value,
                );
            }
        
        // add any additional static elements from table
        if (isset($table[$this->label.'_static']))
            {
            foreach ($table[$this->label.'_static'] as $name=>$value)
                $result[$name] = Array('label'=>$name, 'value'=>$value);
            }

        return json_encode($result);
        }
    
    /// Returns JSON header
    function get_header()
        {
        return '{"records":[';
        }
    
    /// Returns JSON footer
    function get_footer()
        {
        return ']}';
        }
    
    /// Add query info fields for search results to the json object
    function add_query_info_fields($json_str, $info)
        {
        $info = $this->_copy_query_info_fields($info);
        // remove the final '}' first
        $json_str = substr($json_str, 0, strlen($json_str)-1);
        $json_str .= ',"info":'.json_encode($info).'}';
        return $json_str;
        }
    
    /// Helper function - copy relevant info fields from query info array
    /// i.e. this provides a way to ignore any special fields that might
    /// be in the info object (e.g. module)
    function _copy_query_info_fields($info)
        {
        $info_to_copy = Array('results_count', 'accuracy', 'page_count', 'page_prev_url',
            'page_next_url', 'page_first_url', 'page_last_url', 'page_urls', 'page_message',
            'results_message', 'results_message_unpaged', 'first_in_page', 'last_in_page');
        $result = Array();
        foreach ($info_to_copy as $item)
            {
            if (isset($info[$item]))
                $result[$item] = $info[$item];
            }
        return $result;
        }
    
    /// Remove unnecessary fields
    function _remove_fields($record)
        {
        if (isset($record['module']))
            unset($record['module']);
        return $record;
        }
    }
?>
