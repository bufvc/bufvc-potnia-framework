<?php
// $Id$
// Sphinx interface
// James Fryer, 2011-03-10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

if ($CONF['sphinx'])
    require_once $CONF['path_lib'] . 'sphinxapi.php';

// Create a new SphinxClient object
// In unit tests always returns a DummySphinx object
// If sphinx is not enabled returns NULL
function new_sphinx()
    {
    global $CONF;
    if ($CONF['unit_test_active'])
        return new DummySphinx();
    else if (!$CONF['sphinx'])
        return NULL;
    else {
        $sphinx = new SphinxClient();
        $sphinx->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
        return $sphinx;
        }
    }

// Dummy Sphinx object for use in tests
class DummySphinx
    {
    /// Always return one match for 'single' in test DB
    function RunQueries()
        {
        return Array(
            1=>Array(
                'total_found'=>1, //### TODO
                'total'=>1,
                'matches'=>Array(
                    Array('id'=>1,), // 1 is 'single'                    
                    ),
                ),
            );
        }
    // Does nothing
    function AddQuery($query, $index, $log)
        {
        return 1;
        }
    function SetLimits($offset, $count)
        {
        }
    function SetSortMode($m, $arg=NULL)
        {
        }
    function SetMatchMode($m, $arg=NULL)
        {
        }        
    function SetArrayResult($f)
        {
        }        
    function GetLastError()
        {
        }
    function IsConnectError()
        {
        }
    }
