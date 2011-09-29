<?php
// $Id$
// Builder for Query classes
// James Fryer, 24 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Use the QueryFactory to construct Query instances. See the configure() function for a list of options.
/// Use the create() function for most purposes.
class QueryFactory
    {
    /// Worker function, returns a new query with given module, config, values
    static function create($module, $config=NULL, $criteria_values=NULL)
        {
        $factory = new QueryFactory($module);
        $config = $factory->guess_config($config);
        $factory->configure($config);
        $result = $factory->new_query($criteria_values);
        return $result;
        }
        
    private $module;
    private $config;
    function __construct($module)
        {
        $this->module = $module;
        $config = Array();
        $this->configure($config);
        $this->util = new QueryFactoryUtil($this->module);
        }
    
    /// Set the configuration for the query. Options are:
    ///    table_name: Each query has a table name, the module supplies the default
    ///    old_criteria_defs: Old-style definitions
    ///    criteria_defs (new defs from DS): New definitions, gathered from DS. Can be passed in directly for test or allow specific criteria lists to be built.
    ///    cache_class: The class used for caching. Can be subclassed for specific modules. Would be better to pass in object not class.
    ///    encoder_class: The class used to encode queries for the DS.
    ///    filters (and default filters): list of filters for query
    ///    query_lists: The lists gathered from DS top-level, DS table, (lists also come from the criteria)
    ///    is_page_size_mandatory: Page size is nearly always an integral part of a query, so if none is specified in config a default one is created
    function configure($config=NULL)
        {
        if (is_null($config))
            $config = Array();
        $this->config = $config;
        // Default cache, encoder
        if (@$this->config['cache_class'] == '')
            $this->config['cache_class'] = 'QueryCriteriaCache';
        if (@$this->config['encoder_class'] == '')
            $this->config['encoder_class'] = 'QueryDataSourceEncoder';
        }
        
    /// Guess the best configuration from the module and parameter
    function guess_config($extra_config=NULL)
        {
        global $CONF;
        $result = @$this->module->query_config;
        if ($result == '')
            $result = Array();
        if (is_array($extra_config))
            $result = array_merge($result, $extra_config);

        if (@$result['table_name'] == '')
            {
            $table_name = $this->util->get_default_table();
            if ($table_name != '')
                $result['table_name'] = $table_name;
            }
        if (@$result['criteria_defs'] == '')
            {
            $criteria_defs = $this->util->get_criteria_defs($result['table_name']);
            if ($criteria_defs != '')
                $result['criteria_defs'] = $criteria_defs;
            }
        if (@$result['query_lists'] == '')
            {
            $query_lists = $this->util->get_query_lists($result['table_name']);
            if ($query_lists != '')
                $result['query_lists'] = $query_lists;
            }

        // Filters
        if (is_array(@$result['filters']) && is_array(@$CONF['query_filters']))
            $result['filters'] = array_merge($CONF['query_filters'], $result['filters']);
        else if (is_array(@$CONF['query_filters']))
            $result['filters'] = $CONF['query_filters'];
            
        return $result;
        }    
        
    /// Create a new query from the current configuration, setting criteria if any
    function new_query($criteria_values=NULL)
        {
        global $CONF,$USER;
        $result = new Query($this->module);
        $is_page_size_mandatory = TRUE;
        if (isset($this->config['is_page_size_mandatory']))
            $is_page_size_mandatory = $this->config['is_page_size_mandatory'];
        
        // Table name
        if (@$this->config['table_name'] != '')
            $result->table_name = $this->config['table_name'];        
        
        // Cache
        $cache_class = $this->config['cache_class'];
        $result->_cache = new $cache_class(@$CONF['query_cache_size']);
        
        // Encoder
        $encoder_class = $this->config['encoder_class'];
        $result->_encoder = new $encoder_class($result);
        
        // Filters
        if (@$this->config['filters'])
            {
            // Instantiate if necessary
            $filters = Array();
            foreach ($this->config['filters'] as $f)
                {
                if (is_string($f))
                    {
                    if (class_exists($f))
                        $filters[] = new $f();
                    else
                        xlog(1, 'Filter not found: ' . $f, 'QUERY');
                    }
                else
                    $filters[] = $f;
                }
            $result->filters = $filters;
            }
        
        // Lists
        if (@$this->config['query_lists'])
            {
            foreach ($this->config['query_lists'] as $list_name => $list)
                $result->add_list($list_name, $list);
            }
            
        $result->criteria_container = new QueryCriteria();
        if (@$this->config['criteria_defs'])
            {
            foreach ($this->config['criteria_defs'] as $criterion)
                {
                if ($criterion instanceof QueryCriterion)
                    $result->add_criterion($criterion);
                else if (is_array($criterion))
                    $result->add_criterion(QueryCriterionFactory::create($criterion));
                }
            if ($criteria_values)
                $result->set_criteria_values($criteria_values);
            }
        
        // if no page size QC has been added, then add the default if required
        if( $is_page_size_mandatory && !isset($result['page_size']) )
            $result->add_criterion( $this->create_page_size_criterion() );
        
        // ask the criterion for any lists (date ranges for example)
        foreach ($result->criteria_container->get_lists() as $qs_key => $list)
            $result->add_list($qs_key, $list);

        // set default page size based on user prefs
        if (isset($result['page_size']) && isset($USER->prefs['page_size']) )
            $result['page_size']->set_default( $USER->prefs['page_size'] );
        return $result;
        }
    
    /// Generates a page_size QC
    private function create_page_size_criterion()
        {
        $result = QueryCriterionFactory::create(Array(
                'name' => 'page_size',
                'label' => 'Display',
                'type' => QC_TYPE_SORT,
                'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                'is_renderable' => FALSE,
                'is_encodable' => FALSE,
                'default' => 10,
                ));
        return $result;
        }
    
    /// Get a copy of the configuration
    function get_config()
        {
        return $this->config;
        }

    /// Save a query to the session
    ///### Experimental
    ///### hack to see if module mode is a good idea or not
    static function set_session_query($module, $query)
        {
        $_SESSION['QUERY'] = $query;
        $module->set_session_data('QUERY' . '/' . $query->table_name, $query);
        }
        
    /// Get the current query from the session.
    /// If ignore_module_session is false, look in the module's session
    /// If it's set, look in the global session
    /// Either way, create a new query for the given module if no query found
    ///### TODO: Experimental, does too much, may be in wrong place
    ///### FIXME: Util param is for tests only, apalling kludge
    static function get_session_query($module, $ignore_module_session=FALSE, $table_name=NULL, $util=NULL)
        {
        // Util for testing only
        if (is_null($util))
            $util = new QueryFactoryUtil($module);
        if ($table_name == '')
            $table_name = $util->get_default_table();
        if ($ignore_module_session)
            $result = @$_SESSION['QUERY'];
        else 
            $result = $module->get_session_data('QUERY' . '/' . $table_name);
        if (is_null($result))
            $result = QueryFactory::create($module, Array('table_name'=>$table_name));
        return $result;
        }
    }

/// Utilities for the QueryFactory, kept separate to make testing easier
///### FIXME: name is poor. Perhaps this belongs elsewhere -- it's mainly DS manipulation.
class QueryFactoryUtil
    {
    private $module;
    function __construct($module)
        {
        $this->module = $module;
        }
    
    /// Get the default table name for this factory's module, or NULL if none found
    function get_default_table()
        {
        $tables = $this->module->list_query_tables();
        $tables = array_keys($tables);
        return @$tables[0];
        }

    /// Get criteria definitions (new style) from the factory's module, or NULL if none found
    function get_criteria_defs($table_name)
        {
        $ds = $this->module->get_datasource();
        $table = $ds->retrieve('/' . $table_name);
        return @$table['query_criteria'];
        }

    /// Get the query lists defined in the DS in /query_lists and /table_name/query_lists
    function get_query_lists($table_name)
        {
        $result = Array();
        $ds = $this->module->get_datasource();
        $lists = $ds->retrieve('/query_lists');
        if (is_array($lists))
            $result = $lists;
        $table = $ds->retrieve('/' . $table_name);
        if (is_array(@$table['query_lists']))
            $result = array_merge($result, $table['query_lists']);
        return $result;
        }
    }
