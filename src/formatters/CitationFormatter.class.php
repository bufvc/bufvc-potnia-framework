<?php
// $Id$
// Citation Formatter for IRN/LBC project
// Phil Hansen, 1 April 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The CitationFormatter class provides functions for 
    formatting a record as a citation.
*/
class CitationFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'text_label';
    
    /// Specify a separator string to use between records in a list
    var $record_separator = "----\r\n";
    
    /// Specify a file name extension
    var $file_ext = '-citation.txt';
    
    /// Formats a record for export
    /// This function does not follow the algorithm of the base class
    function format($record)
        {
        $record = $this->_util->format_fields($record);
        
        if (empty($record))
            return '';
        
        $result = $this->module->get_template('inc-citation', Array('RECORD'=>$record, 'MODULE'=>$this->module));
        // the citation template encodes the output with htmlentities, so we decode it
        return html_entity_decode($result, ENT_QUOTES, "UTF-8").$this->newline;
        }
    }
?>
