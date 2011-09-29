<?php
// $Id$
// Marked records class for IRN/LBC project
// Phil Hansen, 09 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


/** Manage marked records.
*/
class MarkedRecord
    {
    /// The current list of marked records
    var $records = NULL;

    function MarkedRecord()
        {
        $this->records = Array();
        }

    /// Add a new marked record to the list.
    function add($url)
        {
        if (!$this->can_add($url))
            return;

        // Get the module
        $module = $this->_module_from_url($url);
        if (is_null($module))
            return;

        // Get the item
        $record = $module->retrieve($url);
        if (is_null($record))
            return;

        // Add module name (the module is re-loaded when the item is fetched)
        $record['modname'] = $module->name;
        $record['module_title'] = $module->title;

        $this->records[$url] = $record;
        $this->log(2, 'Add: ' . $url);
        }
        
    // Get the module applying to a record URL
    function _module_from_url($url)
        {
        global $MODULE;
        $tmp = explode('/', $url);
        // determine which index contains the mod name (was there a leading slash?)
        $index = (strpos($url, '/') === 0) ? 1 : 0;
        if (@$tmp[$index] != '')
            return Module::load($tmp[$index]);
        else 
            return $MODULE;
        }

    /// Can we add another item?
    function can_add($url)
        {
        global $CONF;
        return $this->count() < $CONF['marked_records_size'];
        }

    // Remove a marked record from the list
    function remove($url)
        {
        unset($this->records[$url]);
        $this->log(2, 'Remove: ' . $url);
        }

    // Determines if the record is already in the list
    function exists($url)
        {
        return isset($this->records[$url]);
        }

    // Get a specified record
    function get($url)
        {
        $result = @$this->records[$url];
        if (is_null($result))
            return NULL;
        // Load module
        $result['module'] = Module::load($result['modname']);
        return $result;
        }

    // Get all records
    function get_all()
        {
        return $this->records;
        }

    // Number of records currently marked
    function count()
        {
        return count($this->records);
        }

   function log($level, $message)
        {
        xlog($level, $message, 'MARKED');
        }
    }
?>
