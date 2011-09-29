<?php
// $Id$
// Utilities for Invocrown demo
// James Fryer, 27 June 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/* ### FIXME This file is a mishmash of functions:
 - Misc utilities
 - Http utilities
 - String utilities
 - Array utilities
 - Logging
 - Other stuff which should have its own module
Some of this should be spun off into its own files. Most obviously logging, string capitalisation.
*/
 
/// From a sequence of integers 0...n, produce an apparently random sequence
function rand_walk($n)
    {
    $MODULUS = 0x1000000; // or any power of 2
    // Convert the seed to a non-sequential integer
    $K = 1-1/sqrt(3);           // a mysterious constant
    $C = (int)(($MODULUS * $K)/2);  // another strange value ...
    if (($C % 2) == 0)
        $C += 1;            //   ... which must be odd
    $A = 4*(9967)+1;          // another strange constant which affects
                            // 'how random' the pattern you get will be.
                            // Almost any value will do which satisfies:
                            //   5 <= A < N.
    $n = (($A*$n)+$C) % $MODULUS;
    return $n;
    }

/// Generate a unique, random-looking 10-digit ID string
/// Note that the upper digits are randomly generated, so this function
/// will not return the same result for the same input. However, the result
/// for n will be different from the result for any other n.
function make_unique_id($n)
    {
    $s = sprintf('%08u', rand_walk($n));
    $lower = substr($s, -6);
    $upper = sprintf('%04d', mt_rand(0, 9999));
    return $upper . $lower;
    }

/// Send a request to the HTTP server.
/// Return Array(status, body) or NULL on failure
function http_request($url, $method='GET', $data=NULL, $headers=NULL, $auth=NULL)
    {
    global $CONF;
    if (is_null($headers))
        $headers = Array();
    if (is_null($data))
        $data = Array();
    if (is_array($data))
        {
        $content_type = 'application/x-www-form-urlencoded';
        $data = http_build_query($data);
        }
    else
        $content_type = 'text/plain';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);//###debug
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // Return response as string
    curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $CONF['user_agent']); //some sites such as wikipedia require USERAGENT
    if ($method == 'POST') //### TODO: Need other methods
        {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLINFO_CONTENT_TYPE, $content_type);
        }
    if(!is_null($auth))
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
    $body = curl_exec($ch);
    if ($body === FALSE)
        return NULL;
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    return Array($status, $body);
    }

/// Remove a prefix from a string, if it exists.
/// ex: $url = strip_prefix($url, 'http://');
function strip_prefix($s, $prefix)
    {
    $len = strlen($prefix);
    if (substr($s, 0, $len) == $prefix)
        $s = substr($s, $len);
    return $s;
    }
    
// returns true if a given string ($str) ends with the string ($ends_with)
function string_ends_with( $str, $end_with)
    {
    if( strlen($str) <= 0 )
        return FALSE;
    return @substr_compare($str, $end_with, -strlen($end_with), strlen($end_with)) == 0;
    }

//strips out all unwanted tags, styles, javascript and CDATA from retrieved HTML
//found on php.net within comments at function.strip-tags.php
function html2txt($document)
	{
	$search = array
		(
		'@<script[^>]*?>.*?</script>@si',  // Strip out javascript
        '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
        '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
        '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
		);
	$text = preg_replace($search, '', $document);
	return $text;
	}


/// Copy variables from an assoc array to an object
function copy_array_to_object(&$array, &$object)
    {
    foreach ($array as $name=>$value)
        $object->$name = $value;
    }

// Helper for 'interpolate'
function _interpolate_error_handler($code, $msg)
	{
	xlog(1, "Error $code: $msg", 'interpolate');
	}

/// Expand a string containing variables in PHP format, using values from an array.
/// E.g. interpolate('$x', Array('x'=>'foo')) will return 'foo'.
function interpolate($_s, $_a)
	{
    // Escape quotes!
    $_s = str_replace('"', '\"', $_s);
	$old_error_handler = set_error_handler('_interpolate_error_handler');
	extract($_a);
	$result = eval('return "' . $_s . '";');
	if ($old_error_handler != '')
		set_error_handler($old_error_handler);
	return $result;
	}

/// Replace a filename's extension
function replace_ext($filename, $ext=NULL)
	{
	// Get the position of the final dot
	$dotpos = strrpos($filename, '.');
	// Avoid directories
	$old_ext = substr($filename, $dotpos);
	if (strpos($old_ext, '/') !== FALSE)
		$dotpos = FALSE;
	if ($dotpos === FALSE)
		$result = $filename;
	else
		$result = substr($filename, 0, -(strlen($filename) - $dotpos));
	if (!is_null($ext))
		$result = $result . '.' . $ext;
	return $result;
	}

//---------------------------------------------------------
// Logging functions

class LogLevel
    {
    const ERROR         = 1;
    const COMMANDS      = 2;
    const NAVIGATION    = 3;
    const DEBUGGING     = 4;
    }

/// Log level
///  1 - error messages
///  2 - commands
///  3 - navigation
///  4 - debugging
function xlog($level, $message, $category=NULL, $file=NULL)
    {
    global $CONF, $USER;
		
    if ($level > $CONF['log_level'])
        return;
    // Keep 'file' as default to support existing code
    if ($file != NULL && is_array($file))
        {
        extract($file);
        if (is_array($file))
            $file = NULL;
        }
			
    $now = date('Y-m-d H:i:s O');
    $ip = '-';
    if (isset($_SERVER['REMOTE_ADDR']))
        $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!@$modname)
        $modname = isset($CONF['module']) ? $CONF['module'] : '-';

	$user = @$USER;
	if (defined('ADMIN_MODE'))
        $user_name = 'ADMIN';
    else if (isset($user->login))
        $user_name = $user->login;
    else
        $user_name = '-';

    if ($category === NULL)
        $category = '-';

    // Omit some data in unit test log, helps if comparing logs from different runs
    if (defined('UNIT_TEST'))
        $s = "$modname $user_name $category \"$message\"\n";
    else
        $s = "$now $ip $modname $user_name $category $message\n";

    if (is_null($file))
        $file = $CONF['log_file'];
    @error_log($s, 3, $file);
    }

/// Dump var value
function xlog_r($var, $msg=NULL)
    {
    if (is_null($var))
        $s = '(NULL)';
    else
        $s = print_r($var, 1);
    if ($msg)
        $s = $msg . ":\n" . $s;
    xlog(4, $s, 'DEBUG');
    }

/// logs a backtrace (without arguments) 
function xlog_backtrace($msg=NULL)
    {
    $s = '';
    $trace = debug_backtrace();
    $first = TRUE;
    foreach( $trace as $entry )
        {
        // skip the first entry (which will be to this function)
        if( $first )
            {
            $first = FALSE;
            continue;
            }
        $s .= $entry['file'] . ' ' . $entry['line'] . ' in ' . $entry['function'] . "\n";
        }
    if ($msg)
        $s = $msg . ":\n" . $s;
    xlog(4, $s, 'DEBUG');
    }

/// prints out a backtrace (without arguments) 
function print_backtrace()
    {
    $s = '';
    if( !is_cli() )
        $s .= '<pre>';
    $trace = debug_backtrace();
    $first = TRUE;
    foreach( $trace as $entry )
        {
        // skip the first entry (which will be to this function)
        if( $first )
            {
            $first = FALSE;
            continue;
            }
        $s .= $entry['file'] . ':' . $entry['line'] . ' in ' . $entry['function'] . "\n";
        }
    if( !is_cli() )
        $s .= '</pre>';
    print( $s );
    }
    
/// prints out a variable with additional formatting if the process is running as a web server
function print_var( $var, $force_html_tags=FALSE )
    {
    if( !is_cli() || $force_html_tags )
        print('<pre>');
    if( $var == '' )
        print('NULL');
    else
        print_r( $var );
    if( !is_cli() || $force_html_tags )
        print('</pre>');
    }

/// Wrapper for exec which logs command call
function xexec($cmd, $log_level=6)
	{
	xlog($log_level, 'Command: ' . $cmd, 'EXEC');
	exec($cmd);
	}

/// Decode an ASCII string from hex characters
function hex2bin($s)
    {
    return pack('H*', $s);
    }

/// Truncate a string
function string_truncate($string, $length = 80, $etc = '...', $break_words = false)
    {
    if ($length == 0)
        return '';
    if (strlen($string) > $length)
        {
        $length -= strlen($etc);
        if (!$break_words)
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));

        return substr($string, 0, $length).$etc;
        }
    else
        return $string;
    }

// Format a string with a hanging indent
// The indent and line length should be specified
// An optional left hand 'title' can be specified
// A custom line break string can be specified (default "\n")
// The string is word wrapped
function string_hanging_indent($string, $indent = 0, $line_length = 80, $title = '', $break = "\n", $cut=FALSE)
    {
    $string_length = $line_length - $indent;
    $strings = wordwrap($string, $string_length, "@@", $cut);
    $strings = explode("@@", $strings);
    $padding = str_repeat(' ', $indent);
    $result = '';
    for ($i=0; $i < count($strings); $i++)
        {
        if ($i == 0 && $title != '')
            $result .= str_pad($title, $indent);
        else
            $result .= $padding;
        $result .= $strings[$i] . $break;
        }
    return $result;
    }

/// Compare two arrays, return TRUE if they are the same
function array_compare($a1, $a2)
    {
    if (count($a1) != count($a2))
        return FALSE;
    foreach ($a1 as $key=>$value)
        {
        // special case, both values are empty
        if (array_key_exists($key, $a2) && empty($a2[$key]) && empty($value))
            continue; // this is considered equal so continue to the next key-value pair

        if (!isset($a2[$key]) || $a2[$key] != $value)
            return FALSE;
        }
    return TRUE;
    }

/** "Knit" two assoc arrays together into a single assoc array containing
    the union of the values. Keys not present in the first array are ignored.
    Given these two arrays:
        1=> 'foo', 'abc'
        2=> 'bar', 'def'
        4=> 'baz', 'ghi'
    and
        1=> 'jan'
        3=> 'yoops'
        4=> 'yum'
    will result in:
        1=>'foo', 'abc', 'jan'
        2=>'bar', 'def', NULL
        3=>'baz', 'ghi', 'yum'
    $count and $pad are used to pad the results, so all rows can be
        kept at the same length.
    If the values are scalar, they are converted to arrays before combining them
*/
function array_knit($a1, $a2, $count=0, $padding=NULL)
    {
    if (!is_array($a1))
        return NULL;
    if (!is_array($a2))
        return $a1;
    $result = Array();
    foreach ($a1 as $key=>$value)
        {
        if (!is_array($value))
            $value = Array($value);
        if (isset($a2[$key]))
            {
            $new = $a2[$key];
            if (!is_array($new))
                $new = Array($new);
            $new = array_pad($new, $count, $padding);
            }
        else
            $new = array_fill(0, $count, $padding);
        $result[$key] = array_merge($value, $new);
        }
    return $result;
    }

/// Convert a table (2D array) in this format:
///     ((id, name,), (id2, name2,),...)
/// to this format:
///     (id=>name, id2=>name2, ...)
function table_get_assoc($array, $name_field='name', $id_field='id', $result=NULL)
    {
    if (is_null($result))
        $result = Array();
    if( isset($array) )
        {
        foreach ($array as $a)
            {
            if (isset($a[$id_field]))
                $result[$a[$id_field]] = $a[$name_field];
            }
        }
    return $result;
    }

/// Convert a sequential array to an associative array, using an
/// element of each row as the key
function array_to_assoc($array, $keypos=0)
	{
	if (!is_array($array))
		return NULL;
	$result = Array();
	foreach ($array as $a)
		{
        $key = $a[$keypos];
        if (is_numeric($keypos))
            array_splice($a, $keypos, 1);
        else
            unset($a[$keypos]);
        $result[$key] = $a;
		}
    return $result;
	}
	
/// Returns TRUE if the passed value is an associative array
function array_is_assoc($array)
    {
    return (is_array($array) && (count($array)==0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))) )));
    }

if (!function_exists('array_combine')) {
// array_combine for older versions
   function array_combine($a, $b) {
      $c = array();
       if (is_array($a) && is_array($b))
           while (list(, $va) = each($a))
               if (list(, $vb) = each($b))
                   $c[$va] = $vb;
               else
                   break 1;
       return $c;
   }
}

/// Take an array (A,B,C) and a configuration (A=>X,B=>X)
/// Return the array with members mapped by the configuration.
/// E.g. with the above config, (A,B,C)->(X,C)
/// If threshold is >1, that many items must be present for translation to take place
function array_translate($array, $config, $threshold=1)
    {
    if ($array == NULL || $config == NULL)
        return array();
    // Count items for threshold handling
    $counts = Array();
    if ($threshold != 1)
        {
        foreach ($array as $item)
            {
            $xlat = @$config[$item];
            if (@$counts[$xlat] == '')
                $counts[$xlat] = 0;
            $counts[$xlat]++;
            }
        }
    // Convert array
    $result = Array();
    $use_config = $threshold == 1;
    foreach ($array as $item)
        {
        $xlat = @$config[$item];
        if ($xlat != '' && ($threshold == 1 || $counts[$xlat] >= $threshold))
            $result[$xlat] = TRUE;
        else
            $result[$item] = TRUE;
        }
    return array_keys($result);
    }
    
// Generally merges arrays in the style of a bitwise OR, so that:
//  if an index exists at one[i] and two[i], result[i] == one[i]
//  if an index exists at one[i] not two[i], result[i] == one[i]
//  if an index exists at two[i] not one[i], result[i] == two[i]
function array_merge_or( $one, $two )
    {
    // start with a copy of the first array
    if( !is_array($one) )
        $result = Array($one);
    else
        $result = $one;
    
    if( !is_array($two) )
        $two = Array( $two );
    
    $two_keys = array_keys($two);
    
    for ($i = 0; $i < count($two_keys); $i++)
        {
        if( !isset($result[$two_keys[$i]]) )
            $result[ $two_keys[$i] ] = $two[ $two_keys[$i] ];
        }
    return $result;
    }

/// Sort a 2D array (array of arrays) by a key in the 2nd-level arrays
/// Return: Sorts $a in place
function sort_by_key(&$a, $key)
    {
    global $_sort_by_key_hack;
    $_sort_by_key_hack = $key;
    return usort($a, '_sort_by_key_cmp');
    }

// Helper for above
$_sort_by_key_hack = NULL;
function _sort_by_key_cmp($a, $b)
    {
    global $_sort_by_key_hack;
    $key = $_sort_by_key_hack;
    if (is_string($a[$key]))
        return strcmp($a[$key], $b[$key]);
    else
        return $a[$key] - $b[$key];
    }

/// Get a single column from a 2D array
/// Returns an array which is a slice from the input array
function table_get_array($array, $field=0, $result=NULL)
    {
    if (is_null($result))
        $result = Array();
    foreach ($array as $a)
    	$result[] = $a[$field];
    return $result;
    }

/// Pluralise a word from stem and optional branches:
///   pluralise($n, 'record')
///   pluralise($n, 'dish', 'es')
///   pluralise($n, 'fish', '')
///   pluralise($n, 'radi', 'i', 'us')
function pluralise($n, $stem, $plural_branch='s', $single_branch='')
	{
	return $stem . (($n == 1) ? $single_branch : $plural_branch);
	}

/// Remove control codes (ASCII 0-31) from a string.
function strip_control_codes($s)
	{
	return preg_replace('/[\x00-\x1F]/', '', $s);
	}

/// Get the 'id' field from an object or array
/// If the value is scalar, simply return it
/// Return NULL if not found
function get_id_from_object($value, $key='id')
	{
	if (is_object($value))
		{
		if (isset($value->$key))
			return $value->$key;
		else
			return NULL;
		}
	else if (is_array($value))
		{
		if (isset($value[$key]))
			return $value[$key];
		else
			return NULL;
		}
	else
		return $value;
	}

//---------------------------------------------------------
// Debug functions
$debug_timer = 0;

/// Two functions to time sections of code
function debug_start_timer()
    {
    global $debug_timer;
    $debug_timer = microtime(TRUE);
    }

function debug_stop_timer($msg="Duration")
    {
    global $debug_timer;
    $debug_timer = microtime(TRUE) - $debug_timer;
    $debug_timer = round($debug_timer, 2);
    xlog(1, "$msg=$debug_timer", "DEBUG-TIMER");
    }

/// Strip slashes from $value which might be an array and which may
/// or may not contain arrays
if (!function_exists('stripslashes_deep'))
	{
	function stripslashes_deep($value)
		{
	   	$value = is_array($value) ?
	               array_map('stripslashes_deep', $value) :
	               stripslashes($value);
	   	return $value;
		}
	}

///returns the next Unique ID from chosen table
function get_next_id($entity_table,$column ='id')
	{
	$sql = "Select MAX($column) AS x from $entity_table";
	$result = db_get_one($sql);
	return $result['x'] + 1;
	}

/// Like htmlentities but always writes numeric entities
function htmlnumericentities($str)
    {
    return preg_replace('/[^!-%\x27-;=?-~ ]/e', '"&#".ord("$0").chr(59)', $str);
    }

/// Encodes the three characters that must be encoded in XML - '&', '>' and '<'
function xmlentities($str)
    {
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('<', '&lt;', $str);
    $str = str_replace('>', '&gt;', $str);
    $str = str_replace('"', '&quot;', $str);
    $str = str_replace("'", '&#39;', $str);
    return $str;
    }

/// Removes invalid characters according to the XML spec
/// For reference see: http://www.w3.org/TR/REC-xml/#charsets
function remove_invalid_xml($str)
    {
    $result = '';
    for ($i=0; $i < strlen($str); $i++)
        {
        // get ascii value of current character
        $c = ord($str{$i});
        // compare against xml allowed characters
        if ($c == 0x9 ||
            $c == 0xA ||
            $c == 0xD ||
            ($c >= 0x20 && $c <= 0xD7FF) ||
            ($c >= 0xE000 && $c <= 0xFFFD) ||
            ($c >= 0x10000 && $c <= 0x10FFFF))
            $result .= chr($c);
        }
    return $result;
    }

/// Convert an ISO date to a string. Handles dates without days:
/// YYYY, YYYY-MM and YYYY-MM-DD are all accepted
function iso_date_to_string($iso)
    {
    // Remove trailing 00 elements
    $iso = ereg_replace('(-00|-00-00)$', '', $iso);

    // We count the dashes to find out how many elements are present
    $ndashes = substr_count($iso, '-');
    if ($ndashes == 0)
        return $iso;
    // If the day is missing, add a dummy day and don't put the day in the format
    else if ($ndashes == 1)
        {
        $iso .= '-01';
        $fmt = '%b %Y';
        }
    else
        $fmt = '%e %b %Y';
    $t = strtotime($iso);
    $result = strftime($fmt, $t);
    return trim($result);
    }

/// Add hours, minutes, seconds to a date time string as required.
function fix_datetime_string($date, $time='00:00:00')
    {
    $date = explode(' ', $date);
    return $date[0] . ' ' . @$date[1] . substr($time, strlen(@$date[1]));
    }
                
/// Format seconds as HH:MM:SS
function seconds_to_string($t)
    {
    return sprintf('%02d:%02d:%02d', $t/3600, ($t/60) % 60, $t % 60);
    }

/// Sets a message in the session
function set_session_message($message, $class)
    {
    $_SESSION['MESSAGE'] = $message; // store the message in the session
    $_SESSION['MESSAGE_CLASS'] = $class; // store the message class
    }

/// Gets a message from the session
function get_session_message(&$message, &$class)
    {
    $message = $_SESSION['MESSAGE']; // retrieve the message and class from session
    $class = $_SESSION['MESSAGE_CLASS'];
    unset($_SESSION['MESSAGE']); // remove the message from the session object
    unset($_SESSION['MESSAGE_CLASS']);
    }

// Improved ucwords function - will properly capitalize words that fall
// directly after an unwanted character
function my_ucwords($string)
    {
    $invalid_characters = array('"',
                                '[\'`]',
                                '-',
                                '\(',
                                '\[',
                                '\/',
                                '&',
                                '<.*?>',
                                '<\/.*?>');

    foreach($invalid_characters as $regex)
        $string = preg_replace('/('.$regex.')/','$1 ',$string);

    $string=ucwords($string);

    foreach($invalid_characters as $regex)
        $string = preg_replace('/('.$regex.') /','$1',$string);

    // fix 'x - i.e. if 'S change to 's - has to have something before the quote
    $string = preg_replace("/(.)(['’]|&#8217;)([A-Z])\b/ie", "'\\1'.'\\2'.strtolower('\\3').''", $string);

    // fix d' - i.e. if D' change to d'
    $string = preg_replace("/(D)(['’]|&#8217;)([A-Z][a-z]+)/", 'd$2$3', $string);

    // fix abbreviations - e.g. B.b.c. => B.B.C.
    while (preg_match("/(\.[a-z]{1,1}\.)/", $string))
        $string = preg_replace("/(\.[a-z]{1,1}\.)/e", "''.strtoupper('\\1').''", $string);

    // handle McNames
    $string = preg_replace("/(Mc)([a-z])([a-z]+)/e", "'\\1'.strtoupper('\\2').'\\3'", $string);

    return $string;
    }

// Function to get proper title case
// That is, capitalizing all words except "small words"
// while also making some words all caps.
// Additions to the default lc, uc, and exact stopword lists
// can be given as parameters.
function title_case($title, $lc_extra=null, $uc_extra=null, $exact_extra=null)
    {
    $title = strtolower($title);

    // words to keep lower case
    $lc_stopwords = array(
        'of','a','the','and','an','or','nor','but','is','if','then',
        'else','when','at','from','by','on','off','for','in','out',
        'over','to','into','with',
        'als','aneb','auf','aus','av','con','de','del',
        'den','der','des','di','die','ds','du','e','en','et',
        'fra','für','ihr','il','im','ist','la','le','les',
        'mit','na','ne','o','och','oder','og','om','per','po',
        'pour','pro','son','til','till','um','un','una','una',
        'und','und','unserer','vom','von','voor','vor',
        'was','we','wsi','y','z','za'
        );
    if (!is_null($lc_extra) && is_array($lc_extra))
        $lc_stopwords = array_merge($lc_stopwords, $lc_extra);

    // words to capitalise
    $uc_stopwords = array(
        'bbc', 'bufvc', 'rsc', 'jfk', 'pa', 'bc', 'ad', 'tv',
        'ac', 'dc',
        'ii', 'iii', 'iv', 'vi', 'vii', 'viii', 'ix', 'xx'
        );
    if (!is_null($uc_extra) && is_array($uc_extra))
        $uc_stopwords = array_merge($uc_stopwords, $uc_extra);

    // special words to handle
    $exact_stopwords = array();
    if (!is_null($exact_extra) && is_array($exact_extra))
        $exact_stopwords = array_merge($exact_stopwords, $exact_extra);

    $foundPunctuation = false; // flag

    $words = explode(' ', $title);
    foreach ($words as $key => $word)
        {
        // special case, two words around ellipsis
        $second = '';
        if (preg_match('/([a-z]+\.\.\.)([a-z]+)/', $word, $matches))
            {
            $word = $matches[1]; // first half
            $second = $matches[2]; // second half

            // handle second half here
            if (in_array($second, $uc_stopwords))
                $second = strtoupper($second);
            else if (array_key_exists($second, $exact_stopwords))
                $second = $exact_stopwords[$second];
            else if (!in_array($second, $lc_stopwords))
                $second = my_ucwords($second);
            }

        // remove trailing punctuation before test
        $string = preg_replace('/[[:punct:]]+$/','', $word);
        // also remove leading punctuation before exact stopwords test
        $string_no_punct = preg_replace('/^[[:punct:]]+/', '', $string);

        // exact stopwords
        if (array_key_exists($string_no_punct, $exact_stopwords))
            $words[$key] = preg_replace('/'.$string_no_punct.'/', $exact_stopwords[$string_no_punct], $word);
        // word to uppercase
        else if (in_array($string_no_punct, $uc_stopwords))
            $words[$key] = strtoupper($word);
        // punctuation was found previously, do ucwords even if small word
        else if ($foundPunctuation)
            {
            $words[$key] = my_ucwords($word);
            $foundPunctuation = false; // reset flag
            }
        // first word or not a small word
        else if ($key == 0 or !in_array($string, $lc_stopwords))
            $words[$key] = my_ucwords($word);
        else
            $words[$key] = $word;

        // finish handling ellipsis special case
        if ($second != '')
            {
            $words[$key] = $words[$key] . $second;
            $word = $second;
            }

        // check for trailing punctuation
        if (preg_match('/[[:punct:]]$/', $word))
            $foundPunctuation = true; // set flag
        }

    $newtitle = implode(' ', $words);
    return $newtitle;
    }

/// Format a list of search links
/// The list must be one of: scalar, array, 2D array
/// If a 2D array, items 'title' and 'key' are used by default
/// Returns an array of <a href={sprintf(url_format, key)}>{title}</a>
/// Key values will be url-escaped
function make_search_links($items, $url_format, $key_field='key', $title_field='title', $class='')
    {
    if ($items == '')
        return Array();
    if (!is_array($items))
        $items = Array($items);
    $result = Array();
    foreach ($items as $item)
        {
        $key = is_array($item) ? $item[$key_field] : $item;
        $title = is_array($item) && isset($item[$title_field]) ? $item[$title_field] : $key;
        $result[] = html_link(sprintf($url_format, urlencode($key)), $title, FALSE, $class);
        }
    return $result;
    }

/// Expand well-formed http: URLs in the string so they are clickable links
/// also handles urls like 'www.example.com'
function expand_urls($s)
    {
    // url of type http, https, ftp, file
    $s = preg_replace('/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]/i', '<a href="\0">\0</a>', $s);
    // url starting with 'www.' at the beginning of the string
    $s = preg_replace('/^(www.[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i', '<a href="http://\0">\0</a>', $s);
    // url starting with 'www.' after a space or other break (e.g. newline)
    $s = preg_replace('/([[:space:]()[{}])(www.[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i', '\\1<a href="http://\\2">\\2</a>', $s);
    return $s;
    }

/**
 * Microtime as float for compatibility with php 4
 * "Simple function to replicate PHP 5 behaviour"
 * taken from: http://php.net/manual/en/function.microtime.php
 */
function microtime_float()
    {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
    }

/// Are we running from a CLI?
function is_cli()
    {
    return php_sapi_name() == 'cli' && @$_SERVER['REMOTE_ADDR'] == '';
    }

/// Format large numbers using the locale specific separator (e.g. commas)
/// The number of decimal places and the locale can be specified
function format_number($number, $decimals = 0, $locale = null)
    {
    global $CONF;
    if ($number === '') // just in case
        return '';
    if (is_null($locale))
        $locale = $CONF['locale'];
    // use the specified locale characters
    setlocale(LC_ALL, $locale);
    $locale_info = localeconv();
    return number_format($number, $decimals, $locale_info['decimal_point'], $locale_info['thousands_sep']);
    }

/// Prints a string followed by either a html break tag and a newline if in a server environment
function println($string_message = '', $force_html_tags=FALSE)
    {
    if( !is_cli() || $force_html_tags )
        print "$string_message<br />" . PHP_EOL;
    else
        print $string_message . PHP_EOL;
    }

/// Get the current page URL
function get_current_url()
    {
    $result = 'http';
    if (@$_SERVER["HTTPS"] == "on") 
        $result .= "s";
    $result .= "://";
    if (@$_SERVER["SERVER_PORT"] != "80") 
        $result .= @$_SERVER["SERVER_NAME"] . ":" . @$_SERVER["SERVER_PORT"] . @$_SERVER["REQUEST_URI"];
    else 
        $result .= @$_SERVER["SERVER_NAME"] . @$_SERVER["REQUEST_URI"];
    return $result;
    }
