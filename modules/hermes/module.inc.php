<?php
// $Id$
// Module definition file
// James Fryer, 23 Jan 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Load DataSource and Query classes
require_once 'src/HermesDataSource.class.php';

class HermesModule
    extends Module
    {
    var $name = 'hermes';
    var $datasource_class = 'HermesDataSource';
    var $query_config = Array(
        'filters' => Array('SearchResultsFacetsFilter'),
        );
    var $title = 'Find DVD';
    var $subtitle = 'A guide to audio visual materials for educational use';
    var $description = "A database of over 30,000 titles, on a range of different formats, specifically chosen for their use in further and higher education.";
    var $version = '0.5';
    var $auto_alert_enabled = true;
    
    function new_datasource()
        {
        return new HermesDataSource($this->get_pear_db());
        }
    }
