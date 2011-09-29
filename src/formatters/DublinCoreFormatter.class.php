<?php
// $Id$
// Dublin Core Formatter for IRN/LBC project
// Phil Hansen, 17 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The DublinCoreFormatter class provides functions for parsing a
    record and formatting it as Dublin Core XML.

    The DC element mappings are specified in the table field definitions in the DataSource.
    Any additional DC elements can be specified in a field called 'dc_element_extras' in the
    table definition.
*/
class DublinCoreFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'dc_element';

    /// Element label for the record url
    var $url_label = 'identifier';
    
    /// Line break string
    var $newline = "\n";
    
    /// Repeat elements flag
    var $repeat_elements = TRUE;
    
    /// Specify a file name extension
    var $file_ext = '.xml';
    
    /// Get record header
    function get_record_header($record)
        {
        return "<record>";
        }
    
    /// Get record footer
    function get_record_footer($record)
        {
        return "</record>";
        }
    
    /// Get formatted output for a single element
    function get_element_output($name, $value)
        {
        return '<dc:'.$name.'>'.xmlentities($value).'</dc:'.$name.'>';
        }
    
    /// Get any additional elements
    function get_extra_elements()
        {
        // Add the module url as the 'source' element
        return Array($this->get_element_output('source', $this->module->url()));
        }
    
    /// Returns DublinCore header
    function get_header()
        {
        $result = Array();
        $result[] = '<?xml version="1.0"?>';
        $result[] = "<results";
        $result[] = '  xmlns="http://bufvc.ac.uk/"';
        $result[] = '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $result[] = '  xsi:schemaLocation="http://dublincore.org/schemas/xmls simpledc20021212.xsd"';
        $result[] = '  xmlns:dc="http://purl.org/dc/elements/1.1/">';
        $result = join($this->newline, $result).$this->newline;
        return $result;
        }
    
    /// Returns DublinCore footer
    function get_footer()
        {
        return "</results>";
        }
    }
?>
