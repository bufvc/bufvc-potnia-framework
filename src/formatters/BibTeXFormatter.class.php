<?php
// $Id$
// BibTeX Formatter for IRN/LBC project
// Phil Hansen, 08 Nov 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The BibTeXFormatter class provides functions for parsing a
    record and formatting it in BibTeX format.

    The bibtex field mappings are specified in the table field definitions in the DataSource.
    Any additional fields can be specified in a field called 'bibtex_element_extras' in the
    table definition.
*/
class BibTeXFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'bibtex_element';
    
    /// Element label for the record url
    var $url_label = 'url';
    
    /// Line break string
    var $newline = "\r\n";
    
    /// The string to use when joining elements with multiple values
    var $join_string = ', ';
    
    /// Specify a separator string to use between records in a list
    var $record_separator = "\r\n";
    
    /// Specify a file name extension
    var $file_ext = '.bib';
    
    /// Get record header
    function get_record_header($record)
        {
        // replace characters that shouldn't appear in a citation key
        // including: whitespace ? " @ ' , # \ } { ~ % =
        $citation = preg_replace('/[\s\?"@\',\#\\}{~%=]/', '-', $record['url']);
        return "@Misc {" . $citation . ",";
        }
    
    /// Get record footer
    function get_record_footer($record)
        {
        return "}";
        }
    
    /// Get formatted output for a single element
    function get_element_output($name, $value)
        {
        // double quotes need to be wrapped in braces {}
        $value = str_replace('"', '{"}', $value);
        return wordwrap($name.' = "'.$value, 75, "\r\n")."\",";
        }
    
    /// Join values
    function join_values($name, $value)
        {
        // special case, multiple authors - joined by " and "
        if ($name == 'author')
            return join(' and ', $value);
        return parent::join_values($name, $value);
        }
    }
?>
