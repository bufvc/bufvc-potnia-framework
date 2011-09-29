<?php
// $Id$
// Base class for importing a csv file (from FileMaker)
// Phil Hansen, 21 Aug 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/** Contains functions for importing data 
    from FileMaker csv files
*/
class CsvConverter
    {
    // store the parsed field order
    var $field_map = NULL;
    
    // Parse a single CSV line from a FileMaker data file
    // Returns an array of fields
    // This currently uses str_getcsv which requires php 5.3
    function parse_line($line, $delimiter = ',')
        {
        return str_getcsv($line, $delimiter, '"', '"');
        }
    
    // Converts a date in DD/MM/YYYY format to YYYY-MM-DD format
    function convert_date($date)
        {
        $date = str_replace('/', '-', $date);
        return date('Y-m-d', strtotime($date));
        }
    
    // Converts each field from latin1 to utf8 using iconv
    // Before converting to utf8 it will remove any MS Word/Windows special
    // characters.
    // Custom From and To charsets can be given as parameters
    function convert_fields($fields, $from_charset='latin1', $to_charset='UTF-8')
        {
        foreach ($fields as $index=>$field)
            {
            // remove MS Word/Windows characters
            $char_remove = Array(
                "\x85", // Ellipsis
                "\x91", // Open single quote
                "\x92", // Close single quote/apostrophe
                "\x96", // dash
                "\x93", // Open double quote
                "\x94", // Close double quote
                // convert vertical tab to newline
                "\x0B",
                );
            $char_normal = Array("...", "'", "'", "-", '"', '"', "\n");
            $field = str_replace($char_remove, $char_normal, $field);
            $fields[$index] = iconv($from_charset, $to_charset, $field);
            }
        return $fields;
        }
    }
