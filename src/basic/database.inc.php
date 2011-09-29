<?php
// $Id$
// Database handling routines
// James Fryer, 19 Nov 04
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$db = NULL;

// This message is filled in if functions return an error value
$db_error_message = NULL;

// Write a log message
function db_log($level, $message)
    {
    xlog($level, $message, 'DB');
    }

// Initialise database connection
function db_init($user, $passwd, $host, $database, $die_on_error=TRUE, $charset="UTF8")
    {
    global $db;
    // DSN string must have new_link=true in order for multiple connections with different charsets to work
    $dsn = "mysql://{$user}:{$passwd}@{$host}/{$database}?new_link=true";
    $db = MDB2::factory($dsn);
    if (PEAR::isError($db))
        {
        $msg = "Unable to initialise database. Please try again later.";
        $msg = "$msg: $db->message . ' ' . $db->userinfo";
        $db = NULL;
        db_log(1, $msg);
        if ($die_on_error)
            {
            if (defined('UNIT_TEST'))
                print $msg . "\n";
            exit();
            }
        }
    else
        {
        $db->setCharset($charset);
        // remove MDB2_PORTABILITY_EMPTY_TO_NULL - Without turning this setting off, MDB2 will treat empty strings (i.e. '') 
        // as NULLs when storing. We prefer to use empty strings for some fields.
        // remove MDB2_PORTABILITY_FIX_CASE - keep associative fields case sensitive rather than all lower case
        $db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE);
        }
    }

// Helper, call Pear 'query' function, log and return results
// Return: DB_Results object or NULL.
function db_query($sql, $_db=NULL)
    {
    global $db, $db_error_message;
    if ($_db == NULL)
        $_db = $db;
    db_log(5, "Query: $sql");
    $db_error_message = NULL;
    $result = $_db->query($sql);
    if (PEAR::isError($result))
        {
        $db_error_message = $result->getMessage();
        db_log(1, "Query Error: $db_error_message $sql");
        return NULL;
        }
    else {
        db_log(5, "Query results: " . count($result));
        return $result;
        }
    }

// Helper, call Pear 'getAll' function, log and return results
// Return: Results array or NULL
function db_get_all($sql, $fetchmode=MDB2_FETCHMODE_ORDERED, $_db=NULL)
    {
    global $db, $db_error_message;
    if ($_db == NULL)
        $_db = $db;
    db_log(5, "Query: $sql");
    $db_error_message = NULL;
    $result= $_db->queryAll($sql, NULL, $fetchmode);
    if (PEAR::isError($result))
        {
        $db_error_message = $result->message;
        db_log(1, "Query Error: $db_error_message $sql");
        return NULL;
        }
    else {
        db_log(5, "Query results: " . count($result));
        return $result;
        }
    }

// Get one row from the database as an assoc array
// Return assoc array or NULL
function db_get_one($sql, $_db=NULL)
    {
    $result = db_get_all($sql, MDB2_FETCHMODE_ASSOC, $_db);
    if (PEAR::isError($result) || count($result) == 0)
        return NULL;
    else
        return $result[0];
    }

/** Get the last autoincrement ID
*/
function db_last_insert_id()
	{
	$r = db_get_one("SELECT LAST_INSERT_ID() AS id");
	if (is_null($r))
		return NULL;
	else
		return $r['id'];
	}

/** Escape posted data for use in query
	By default will not escape if magic quotes is off
*/
function db_escape($s, $force=0)
    {
    if (defined('UNIT_TEST'))
    	$force = 1;
    if ($force || !get_magic_quotes_gpc())
       return addslashes($s);
    else
       return $s;
    }

?>
