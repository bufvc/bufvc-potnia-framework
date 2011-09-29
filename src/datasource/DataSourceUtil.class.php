<?php
// $Id$
// Proxies, filters, 
// James Fryer, 24 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// A DS which passes all requests on to another DS
class ProxyDataSource
    extends DataSourceBase
    {
    function __construct($datasource)
        {
        $this->_ds = $datasource;
        }
    
    /// Get the underlying DataSource
    function get_datasource()
        {
        return $this->_ds;
        }
        
    function search($table, $query_string, $offset, $max_count)
        {
        $result = $this->_ds->search($table, $query_string, $offset, $max_count);
        $this->_set_error($this->_ds->error_code, $this->_ds->error_message);
        return $result;
        }

    function create($table, $data)
        {
        $result = $this->_ds->create($table, $data);
        $this->_set_error($this->_ds->error_code, $this->_ds->error_message);
        return $result;
        }

    function retrieve($url, $parameters=NULL)
        {
        $result = $this->_ds->retrieve($url, $parameters);
        $this->_set_error($this->_ds->error_code, $this->_ds->error_message);
        return $result;
        }

    function update($url, $record)
        {
        $result = $this->_ds->update($url, $record);
        $this->_set_error($this->_ds->error_code, $this->_ds->error_message);
        return $result;
        }

    function delete($url)
        {
        $result = $this->_ds->delete($url);
        $this->_set_error($this->_ds->error_code, $this->_ds->error_message);
        return $result;
        }
    }

/// Search a DS calling 'process_record' for each record found.
class DataSourceTraverser
    {
    // Datasource
    protected $ds;
    
    // How many records to fetch at a time
    var $page_size;
    
    function __construct($ds, $page_size=1000)
        {
        $this->ds = $ds; 
        $this->page_size = $page_size;
        }
        
    /// Process all records returned by the query
    function for_all($table, $query=NULL, $offset=0, $max_count=0)
        {
        $has_max = $max_count > 0;
        do  {
            $request_count = $has_max ? min($max_count, $this->page_size) : $this->page_size;
            $r = $this->ds->search($table, $query, $offset,  $request_count);
            if (is_null($r))
                {
                $this->process_error();
                break;
                }
            // No records? Do nothing
            else if (@$r['total'] == 0)
                break;
            // Else figure out how many records we need to process
            else if (@$total == 0)
                $total = $has_max ? min($offset + $max_count, $r['total']) : $r['total'];
            $count = @$r['count'];
            for ($i = 0; $i < $count; $i++)
                $this->process_record($r['data'][$i]);
            $max_count -= $count;
            $offset += $count;
            } while ($offset < $total);
        }
    
    /// Process a single record. Subclasses redefine this to do some work
    function process_record($record)
        {
        }

    /// Called when a DS error is encountered
    function process_error()
        {
        }
    }
