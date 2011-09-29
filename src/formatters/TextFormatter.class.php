<?php
// $Id$
// Text Formatter for IRN/LBC project
// Phil Hansen, 04 Nov 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The TextFormatter class provides functions for parsing a
    record and formatting it as plain text.

    The text label mappings are specified in the table field definitions in the DataSource.
*/
class TextFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'text_label';
    
    /// Element label for the record url
    var $url_label = 'URL';
    
    /// Line break string
    var $newline = "\r\n";
    
    /// Specify a separator string to use between records in a list
    var $record_separator = "----\r\n";
    
    /// Specify a file name extension
    var $file_ext = '.txt';
    
    /// Get formatted output for a single element
    function get_element_output($name, $value)
        {
        $result = string_hanging_indent($value, 15, 75, $name . ': ', $this->newline);
        // remove the last newline, this will be added during the final record join
        $result = rtrim($result, $this->newline);
        return $result;
        }
    }
?>
