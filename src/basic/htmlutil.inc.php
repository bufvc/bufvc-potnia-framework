<?php
// $Id$
// HTML utils
// James Fryer, 2004
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/** Return HTML page header
*/
function html_header($title, $css=NULL)
    {
    return <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
<TITLE>$title</TITLE>
</HEAD>
<BODY>

EOT;
    }

/** Return HTML page footer
*/
function html_footer()
    {
    return <<< EOT
</BODY>
</HTML>

EOT;
    }

/** Return HTML link
*/
function html_link($url, $title='', $type = FALSE, $class = '')
    {
    if (!$title)
        $title = $url;
    if ($type)
        $link_type = ' target="_blank"';
    else 
        $link_type = "";
    if ($class != '')
        $class = ' class="' . $class . '"';
        
    return <<<EOT
<a href="$url"$class title="$title"$link_type>$title</a>
EOT;
    }

/** Return HTML image
*/
function html_image($url, $alt='')
    {
    //### TODO: Width, height
    return <<<EOT
<IMG SRC="$url" ALT="$alt">
EOT;
    }

/** Wrap a string in an HTML tag
*/
function html_tag($s, $tag)
    {
    $etag = $tag; //### TODO: strip off attributes
    return <<<EOT
<$tag>$s</$etag>
EOT;
    }

/** Convert a list to HTML options
    Assoc arrays will use the key as the HTML name and the value as the label
    Sequential arrays will use the value for both the name and value
        unless $use_numeric_keys is set.
    You need to supply your own SELECT tags (or whatever).
    Curr value can be an array for multiple select
*/
function html_options($array, $curr_value=NULL, $prompt=NULL, $use_numeric_keys=0, $charset=NULL)
    {
    global $CONF;
    if (is_null($charset))
        $charset = $CONF['default_charset'];
    $result = Array();
    if ($prompt != '')
        $result[] = "<option value=\"\">-- $prompt --</option>";
    if (is_array($array))
        {
        foreach ($array as $key=>$value)
            {
            // Not a very good test for assoc or list array
            if (!isset($is_assoc))
                $is_assoc = is_string($key);
            if ($is_assoc == 0 && $use_numeric_keys == 0)
                {
                $opt = "<option value=\"".htmlentities($value, ENT_COMPAT, $charset)."\"";
                if ($value != '' && ($value == $curr_value || 
                                     (is_array($curr_value) && in_array($value, $curr_value))))
                    $opt .= ' selected="selected"';
                $opt .= ">".htmlentities($value, ENT_COMPAT, $charset)."</option>";
                }
            else {
                $opt = "<option value=\"$key\"";
                // include special check for a numeric key and curr_value
                if (is_numeric($key) && is_numeric($curr_value))
                    {
                    if ($key == $curr_value)
                        $opt .= ' selected';
                    }
                else
                    {
                    if ($key != '' && ($key == $curr_value || 
                                       (is_array($curr_value) && in_array($key, $curr_value))))
                        $opt .= ' selected';
                    }
                $opt .= ">".htmlentities($value, ENT_COMPAT, $charset)."</option>";
                }
            $result[] = $opt;
            }
        }
    return join("\n", $result);
    }
    
/** Convert a list to HTML checkboxes
    All arrays use the key as the HTML name and the value as the label
    Curr value can be an array for multiple select
*/
function html_checkboxes($array, $name, $curr_value=NULL, $fmt='%s&nbsp;%s')
    {
    $result = Array();
    if (!is_array($array))
        return $result;
    foreach ($array as $key=>$value)
        {
        $html = "<input type=\"checkbox\" value=\"$key\" name=\"$name\"";
        // include special check for a numeric key and curr_value
        if (is_numeric($key) && is_numeric($curr_value))
            {
            if ($key == $curr_value)
                $html .= ' checked="checked"';
            }
        else {
            if ($key != '' && ($key == $curr_value || 
                               (is_array($curr_value) && in_array($key, $curr_value))))
                $html .= ' checked="checked"';
            }
        $html .= ">";
        $result[] = sprintf($fmt, $html, htmlentities($value));
        }
    return join("\n", $result);
    }
    
function _table_cell_is_empty($s)
    {
    $s = trim($s);
    if (is_null($s) || $s == '')
        return '&nbsp;';
    else
        return $s;
    }

function html_table($title, $data, $header=NULL, $help='', $width="100%")
    {
    $result = '';
    $result .= "<h3>$title</h3>\n";
    if ($help)
        $result .= "<p>$help</p>\n";
    $result .= "<table border=1 width=$width>";
    if ($header)
        {
        $result .= "<tr>";
        foreach ($header as $item)
            {
            $item = _table_cell_is_empty($item);
            $result .= "<th>$item</th>\n";
            }
        $result .= "</tr>\n";
        }
    foreach ($data as $row)
        {
        $result .= "<tr>";
        foreach ($row as $item)
            {
            $item = _table_cell_is_empty($item);
            $result .= "<td>$item</td>\n";
            }
        $result .= "</tr>\n";
        }
    $result .= "</table>\n";
    return $result;
    }

// Test for table gen
//### print html_table('test', Array());
//### print html_table('test', Array(Array(1,2,3,4)));
//### print html_table('test', Array(Array(1,2,3,4), Array(5,6,7,8)), Array(1,2,3,4));
