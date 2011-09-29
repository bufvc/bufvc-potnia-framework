<?php
// $Id$
// Atom Formatter for IRN/LBC project
// Phil Hansen, 09 Jun 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');

/** The AtomFormatter class provides functions for parsing a
    record and formatting it as an Atom feed entry.

    The Atom element mappings are specified in the table field definitions in the DataSource.
*/
class AtomFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'atom_element';
    
    /// Element label for the record url
    var $url_label = 'id';
    
    /// Line break string
    var $newline = "\n";
    
    /// Repeat elements flag
    var $repeat_elements = TRUE;
    
    /// Remove MS Word/Windows characters
    var $char_remove = Array(
        "\x85", // ellipsis
        "\x91", // open single quote
        "\x92", // close single quote/apostrophe
        "\x96", // dash
        "\x93", // open double quote
        "\x94", // close double quote
        );
    
    // Replace with these characters
    var $char_replace = Array(
        '&hellip;', // ellipsis
        '&lsquo;',  // left single quote
        '&rsquo;',  // right single quote
        '&ndash;',  // dash
        '&ldquo;',  // left double quote
        '&rdquo;',  // right double quote
        );
    
    /// return a formatted atom element
    function get_element_output($name, $value)
        {
        $value = xmlentities($value);
        $value = remove_invalid_xml($value);
        $value = $this->replace_chars($value);
        // special case, link
        if ($name == 'link')
            return '    <link href="'.$value.'"/>';
        // special case, author/contributor
        else if ($name == 'author' || $name == 'contributor')
            return $this->_get_author_element($name, $value);
		// Summary should not contain HTML unless declared in the type attribute 
		else if ( $name == 'summary' )
			return '    <summary type="html">' . $value . '</summary>';
        else
            return "    <$name>$value</$name>";
        }
    
    /// return a formatted author/contributor element
    function _get_author_element($name, $value)
        {
        $result  = "    <$name>".$this->newline;
        $result .= "      <name>$value</name>".$this->newline;
        $result .= "    </$name>";
        return $result;
        }
    
    /// Returns Atom feed header
    function get_header($module, $update_time, $author, $title='', $feed_path='')
        {
        $result = Array();
        $result[] = '<?xml version="1.0" encoding="utf-8"?>';
        $result[] = '<feed xmlns="http://www.w3.org/2005/Atom">'.$this->newline;
        $result[] = '  <title>'.xmlentities($module->title.$title).'</title>';
        $result[] = '  <link rel="alternate" href="'.xmlentities($module->url()).'"/>';
        $result[] = '  <link rel="self" href="'.xmlentities($module->url('feeds').$feed_path).'"/>';
        $result[] = '  <updated>'.xmlentities($update_time).'</updated>';
        $result[] = '  <author>';
        $result[] = '    <name>'.xmlentities($author).'</name>';
        $result[] = '  </author>';
        $result[] = '  <id>'.xmlentities($module->url('feeds').$feed_path).'</id>'.$this->newline;
        $result = join($this->newline, $result).$this->newline;
        return $result;
        }
    
    /// Returns Atom feed footer
    function get_footer()
        {
        return '</feed>';
        }
    
    /// Convert MySQL datetimes to ISO/ATOM date format
    /// e.g. 2008-10-14 00:00:00 => 2008-10-14T00:00:00Z
    function format_atom_date($date)
        {
        if (empty($date))
            return '';
        @list($date, $time) = explode(' ', $date);
        if ($time == '')
            $time = '00:00:00';
        return $date . 'T' . $time .'Z';
        }
    
    // Replace unwanted characters
    function replace_chars($str)
        {
        return str_replace($this->char_remove, $this->char_replace, $str);
        }
    }
?>
