<?php
// $Id$
// Base class for parsing the query log file
// Phil Hansen, 20 April 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Contains functions for parsing the query log data 
class QueryLogParser
    {
    // storage hash table
    var $stats;
    
    function __construct()
        {
        $this->stats = Array();
        }
    
    /// Parse the given log file
    function parse($filename)
        {
        $lines = $this->read_input($filename);
        
        foreach ($lines as $line)
            {
            $this->parse_line($line);
            }
        
        $this->finish_parse();
        }
    
    /// Parse a single line from the log
    /// QUERY-START lines need to be matched to QUERY-END lines
    function parse_line($line)
        {
        if (empty($line))
            return;
        
        // ignore invalid lines
        $tmp = explode(' ', $line);
        if (count($tmp) < 9)
            return;
        
        // split the line into values that are the same for both types
        list($date, $time, $timezone, $ip, $module, $user, $type, $qid, $remainder) = explode(' ', $line, 9);
        
        if (!$this->check_additional($date, $time, $timezone, $ip, $module, $user, $type, $qid, $remainder))
            return;
        
        // process a start line
        if ($type == 'QUERY-START')
            {
            list($table, $url, $query) = explode(' ', $remainder, 3);
            // strip quotes
            $query = substr($query, 1, -1);
            list($query, $readable) = explode(' | ', $query);
            // store values in hash table
            $this->stats[$qid] = Array(
                'date'=>$date,
                'time'=>$time,
                'timezone'=>$timezone,
                'ip'=>$ip,
                'module'=>$module,
                'user'=>$user,
                'qid'=>$qid,
                'table'=>$table,
                'url'=>$url,
                'query'=>$query,
                'readable'=>$readable,
                );
            }
        else if ($type == 'QUERY-END' && isset($this->stats[$qid]))
            {
            list($count, $accuracy, $duration) = explode(' ', $remainder);
            $query = $this->stats[$qid];
            $query['count'] = $count;
            $query['accuracy'] = $accuracy;
            $query['duration'] = $duration;
            $this->process_query($query);
            unset($this->stats[$qid]);
            }
        }
    
    /// Finish processing a query
    /// To be implemented by subclasses
    function process_query($query)
        {
        }
    
    /// Perform any additional validity checks
    /// To be implemented by subclasses
    function check_additional($date, $time, $timezone, $ip, $module, $user, $type, $qid, $remainder)
        {
        return TRUE;
        }
    
    /// Any final parsing steps
    /// To be implemented by subclasses
    function finish_parse()
        {
        }
    
    /// Read from a file or stdin if no file defined
    /// Return an array of lines
    function read_input($filename=NULL)
        {
        if ($filename == '')
            $filename = 'php://stdin';
        return file($filename, FILE_IGNORE_NEW_LINES);
        }
    }

/// Processes a query log file and outputs information in a tab-separated format
class QueryLogStats
    extends QueryLogParser
    {
    /// Parse the given log file
    function parse($filename)
        {
        // print header line (fields are separated by tabs)
        $header = "date\ttime\ttimezone\tip\tmodule\tuser\tqid\ttable\turl\tquery\treadable\tcount\taccuracy\tduration";
        print($header . "\n");
        parent::parse($filename);
        }
    
    /// Finish processing a query
    function process_query($query)
        {
        // output data
        print($query['date']."\t".$query['time']."\t".$query['timezone']."\t".
            $query['ip']."\t".$query['module']."\t".$query['user']."\t".
            $query['qid']."\t".$query['table']."\t".$query['url']."\t".
            $query['query']."\t".$query['readable']."\t".
            $query['count']."\t".$query['accuracy']."\t".$query['duration']."\n");
        }
    
    /// Any final parsing steps
    function finish_parse()
        {
        // process orphan lines
        foreach ($this->stats as $line)
            {
            // use -1 for count and blank for accuracy and duration
            print($line['date']."\t".$line['time']."\t".$line['timezone']."\t".
                $line['ip']."\t".$line['module']."\t".$line['user']."\t".
                $line['qid']."\t".$line['table']."\t".$line['url']."\t".
                $line['query']."\t".$line['readable']."\t-1\t\t\n");
            }
        }
    }

/// Parse the query log file and store the count for each query
class QueryLogCount
    extends QueryLogParser
    {
    // module name
    var $modname;
    
    // queries storage - update the counts here before accessing the database
    var $queries;
    
    // pear db object
    var $db;
    
    // last seen date, used to ignore previously seen logs
    var $last_date;
    
    function __construct($module)
        {
        parent::__construct();
        $this->modname = $module;
        $this->queries = Array();
        $mod = Module::load($this->modname);
        $this->db = $mod->get_pear_db();
        }
    
    /// Parse the given log file
    function parse($filename)
        {
        $last_date = $this->get_last_date();
        $this->last_date = (is_null($last_date)) ? 0 : strtotime($last_date);
        parent::parse($filename);
        }
    
    /// Perform any additional validity checks
    function check_additional($date, $time, $timezone, $ip, $module, $user, $type, $qid, $remainder)
        {
        // not the correct module
        if ($module != $this->modname)
            return FALSE;
        // ignore queries before the last seen date
        if (strtotime($date.' '.$time) <= $this->last_date)
            return FALSE;
        return TRUE;
        }
    
    /// Finish processing a query
    function process_query($query)
        {
        if (!isset($this->queries[$query['url']]))
            {
            $this->queries[$query['url']] = Array(
                'url' => $query['url'],
                'count' => 1,
                'date' => $query['date'].' '.$query['time'],
                'module' => $query['module'],
                'search_table' => $query['table'],
                'criteria' => $query['query'],
                'details' => $query['readable'],
                'results_count' => $query['count'],
                );
            }
        else
            {
            $this->queries[$query['url']]['count']++;
            $this->queries[$query['url']]['date'] = $query['date'].' '.$query['time'];
            $this->queries[$query['url']]['results_count'] = $query['count'];
            }
        }
    
    /// Any final parsing steps
    function finish_parse()
        {
        // Process the list of queries and store the data in the database
        foreach ($this->queries as $query)
            {
            $record = $this->retrieve($query);
            if (!isset($record['url']))
                $this->create($query);
            else
                $this->update($query, $record);
            }
        }
    
    /// Attempt to retrieve a query stats record
    function retrieve($query)
        {
        $url = $this->db->quote($query['url'], 'text');
        $sql = "SELECT * FROM QueryStats WHERE url=$url";
        $result = $this->db->queryRow($sql, NULL, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result))
            print("Error during retrieve: ".$result->getMessage()."--".$result->getUserInfo()."\n");
        return $result;
        }
    
    /// Create a new query stats record
    function create($query)
        {
        $sql = "INSERT INTO QueryStats (url, count, date, module, search_table, criteria, details, results_count) VALUES ("
            . $this->db->quote($query['url'], 'text') . ", "
            . $this->db->quote($query['count'], 'integer') . ", "
            . $this->db->quote($query['date'], 'timestamp') . ", "
            . $this->db->quote($query['module'], 'text') . ", "
            . $this->db->quote($query['search_table'], 'text') . ", "
            . $this->db->quote($query['criteria'], 'text') . ", "
            . $this->db->quote($query['details'], 'text') . ", "
            . $this->db->quote($query['results_count'], 'integer') . ")";
        $r = $this->db->exec($sql);
        if (PEAR::isError($r))
            print("Error during create: ".$r->getMessage()."--".$r->getUserInfo()."\n");
        }
    
    /// Update an existing query stats record
    function update($query, $record)
        {
        $count = $query['count'] + $record['count'];
        $sql = "UPDATE QueryStats SET count=".$count.", "
            . "date=". $this->db->quote($query['date'], 'timestamp') .", "
            . "results_count=".$query['results_count']
            . " WHERE id=".$record['id'];
        $r = $this->db->exec($sql);
        if (PEAR::isError($r))
            print("Error during update: ".$r->getMessage()."--".$r->getUserInfo()."\n");
        }
    
    /// Retrieve the last seen date
    function get_last_date()
        {
        $sql = "SELECT date FROM QueryStats ORDER BY date DESC";
        $result = $this->db->queryOne($sql);
        if (PEAR::isError($result))
            print("Error retrieving last seen date: ".$result->getMessage()."--".$result->getUserInfo()."\n");
        return $result;
        }
    }
?>