<?php
// $Id$
// Count accesses to a record
// James Fryer, 11 Aug 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class DataSource_StatsStorage 
    extends DataSource_MemoryStorage
    {
    var $querystats = NULL;
    
    function __construct(&$ds, &$pear_db=NULL)
        {
        // check the DS config for the pear db object (this is the same method used
        // by the Mysql storage handler)
        if (is_null($pear_db))
            {
            foreach ($ds->_data as $meta)
                {
                if (isset($meta['pear_db']))
                    $this->pear_db = $meta['pear_db'];
                }
            }
        else
            $this->pear_db = $pear_db;
        $this->stats = new RecordStats($this->pear_db);
        }
    
    // Get view count/score
    function retrieve($ds, $url)
        {
        // special case, top viewed records
        if (strpos($url, '/stats/topviewed') === 0)
            {
            $count = substr($url, 17) ? (int)substr($url, 17) : 0;
            return $this->stats->retrieve_top_viewed($count);
            }
        // special case, most popular queries
        else if (strpos($url, '/stats/topqueries') === 0)
            {
            if (is_null($this->querystats))
                $this->querystats = new QueryStats($this->pear_db);
            $count = substr($url, 18) ? (int)substr($url, 18) : 0;
            return $this->querystats->retrieve_top_queries($count);
            }
        else if (!$this->stats->exists($url))
            $this->stats->create($url);
        else
            $this->stats->increment($url);        
        return $this->stats->retrieve($url);        
        }
    
    // Update score
    function update(&$ds, $url, $record)
        {
        if (!$this->stats->exists($url))
            $this->stats->create($url);
        $this->stats->score($url, @$record['username'], @$record['score']);
        return $this->stats->retrieve($ds, $url);
        }
    }
   
/// Track record statistics
class RecordStats
    {
    function __construct($pear_db)
        {
        $this->pear_db = $pear_db;
        $this->pear_db->loadModule('Extended', null, false);
        }
        
    /// Does the record exist?
    function exists($url)
        {
        $url = $this->pear_db->quote($url, 'text');
        $sql = "SELECT id FROM RecordStats WHERE url=$url";
        $result = $this->pear_db->getOne($sql);
        return MDB2::isError($result) ? 0 : $result;
        }

    /// Create a new record
    function create($url)
        {
        $url = $this->pear_db->quote($url, 'text');
        $sql = "INSERT INTO RecordStats SET url=$url";
        $this->pear_db->exec($sql);
        }

    /// Fetch a record's data
    function retrieve($url)
        {
        $url = $this->pear_db->quote($url, 'text');
        //### $score_sql = "0 as score, 0 as score_count, 0 as score_high, 0 as score_low";
        $sql = "SELECT RecordStats.id, count, SUM(sc.score) as score, AVG(sc.score) as score_avg, COUNT(sc.score) as score_count, 
                    $url AS url FROM RecordStats 
                    LEFT JOIN RecordScore sc ON sc.record_id=RecordStats.id WHERE url=$url";
        $result = $this->pear_db->getRow($sql, NULL, NULL, NULL, MDB2_FETCHMODE_ASSOC);
        return MDB2::isError($result) ? NULL : $result;
        }

    /// Increment a record's counter
    function increment($url)
        {
        $url = $this->pear_db->quote($url, 'text');
        $sql = "UPDATE RecordStats SET count=count+1 WHERE url=$url";
        $this->pear_db->exec($sql);
        }

    /// Add/subtract to a record's score
    function score($url, $username, $score)
        {
        if ($username == '')
            return;
        $url = $this->pear_db->quote($url, 'text');
        $username = $this->pear_db->quote($username, 'text');
        $sql = "DELETE RecordScore FROM RecordScore JOIN RecordStats ON record_id=RecordStats.id AND url=$url WHERE username=$username";
        $this->pear_db->exec($sql);
        $sql = "INSERT INTO RecordScore SET username=$username, score=$score, record_id=(SELECT id FROM RecordStats WHERE url=$url)";
        $this->pear_db->exec($sql);
        }
    
    /// Fetch the top viewed records
    function retrieve_top_viewed($count=0)
        {
        $sql = "SELECT * FROM RecordStats ORDER BY count DESC";
        if ($count > 0)
            $sql .= " LIMIT $count";
        $result = $this->pear_db->queryAll($sql, NULL, MDB2_FETCHMODE_ASSOC);
        return MDB2::isError($result) ? NULL : $result;
        }
    }

/// Access QueryStats
class QueryStats
    {
    function __construct($pear_db)
        {
        $this->pear_db = $pear_db;
        }
    
    /// Fetch most popular queries
    function retrieve_top_queries($count=0)
        {
        $sql = "SELECT * FROM QueryStats ORDER BY count DESC";
        if ($count > 0)
            $sql .= " LIMIT $count";
        $result = $this->pear_db->queryAll($sql, NULL, MDB2_FETCHMODE_ASSOC);
        return MDB2::isError($result) ? NULL : $result;
        }
    }