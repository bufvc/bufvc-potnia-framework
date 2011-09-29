<?php
// $Id$
// Module class: represents a query module
// James Fryer, 7 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_src'] . 'query/ModuleResolver.class.php';

/// The Module class models a web interface that connects to a DataSource
class Module
    extends ProxyDataSource
    {
    /// Defines the datasource class
    //### FIXME: This is now deprecated -- instead override new_datasource to return the DS you want to use
    var $datasource_class = 'DummyDataSource';
    
    /// Configuration for this module's query. See QueryFactory.
    //### FIXME: What to do about modules with multiple query types??? Idea: index them by table name
    var $query_config;

    /// Module name, must be set by subclass
    var $name;
    
    /// Export formatter utility class
    var $export_util_class = NULL;

    /// Descriptive fields, to be set by subclass
    var $title;
    var $subtitle;
    var $description;
    var $icon;
    var $version;

    /// Charset to use for html pages, default is 'UTF-8'
    var $charset = 'UTF-8';
    
    // Database charset map to use for DB connections
    var $db_charset = Array(
        'iso-8859-1' => 'latin1',
        'UTF-8' => 'UTF8',
        );

    /// If set then a user must have this right to be able to use the module
    var $user_right;
    
    /// If set then a login form should be displayed
    var $show_login_form;

    /// If set then a user must have this right to be able to edit records
    var $edit_right = NULL;

    /// If set then saved searches are enabled for this module
    var $saved_search_enabled = FALSE;

    /// If set then auto alerts are enabled for this module
    var $auto_alert_enabled = FALSE;
    
    /// Specify any special criteria for use in auto-alert queries
    var $auto_alert_criteria = NULL;
    
    /// If set then 'all fields' searches are allowed for this module
    var $all_fields_search_enabled = TRUE;

    /// Path to this module's files
    var $path;
    
    /// By default the module name will be included in the URL
    var $add_module_to_url = 1;

    // Supported content types
    static $_mime_types = Array(
            'html' => 'text/html',
            'xspf'=>'application/xspf+xml',
            'xml'=>'application/xml',
            'text'=>'text/plain',
            'bibtex'=>'text/plain',
            'atom'=>'application/atom+xml',
            'json'=>'application/json',
            'ical'=>'text/calendar',
            );
    
    // Currently loaded modules
    static $_module_cache = Array();

    /// Load a module. The module name must be in $CONF[allowed_modules].
    /// The module's PHP files will be loaded and an object of type
    /// {name}Module returned. Objects are cached so the same module object is returned
    /// on subsequent calls
    static function &load($name)
        {
        global $CONF;
        Module::log(3, 'Loading: ' . $name . (isset(Module::$_module_cache[$name]) ? ' from cache' : ''), $name);
        $result = NULL;
        if (isset(Module::$_module_cache[$name]))
            return Module::$_module_cache[$name];
        //### TODO: better way to organise dummy modules
        else if ($name == 'dummy')
            $result = new DummyModule();
        else if ($name == 'dummy2')
            {
            $result = new DummyModule();
            $result->name = 'dummy2';
            $result->title = 'Dummy 2';
            }
        else if (in_array($name, $CONF['allowed_modules']))
            {
            $file = $CONF['path_modules'] . $name . '/module.inc.php';
            if (file_exists($file))
                {
                require_once($file);
                $classname = $name . 'module';
                $result = new $classname();
                }
            }
        if (!is_null($result))
            {
            Module::$_module_cache[$name] = $result;
            // Load global and local template utils
            require_once($CONF['path_templates'] . 'inc-global_util.php');
            $file = $result->path . 'templates/inc-util.php';
            if (file_exists($file))
                require_once($file);
            }
        return $result;
        }
    
    /// Clear the module cache
    static function flush()
        {
        Module::$_module_cache = Array();
        }

    /// Get a module name from a URL, or NULL if no name found
    ///### FIXME: currently does not expect the URL base to be present, only the script name
    static function name_from_url($url, $url_prefix=NULL)
        {
        $tmp = Array();
        $url_prefix .= '/';
        $url_prefix = preg_quote($url_prefix, '/');
        if (preg_match("/^$url_prefix([^\/]*)/", $url, $tmp))
            return $tmp[1];
        return NULL;
        }

    /// Convert aliases within a string, used to map incoming URLs to module names
    static function expand_alias($url, $url_prefix=NULL)
        {
        global $CONF;
        if (count($CONF['module_aliases']) == 0)
            return $url;
        $modnames = array_values($CONF['module_aliases']);
        for ($i = 0; $i < count($modnames); $i++)
            $modnames[$i] = $url_prefix . '/' . $modnames[$i];
        $aliases = array_keys($CONF['module_aliases']);
        for ($i = 0; $i < count($aliases); $i++)
            $aliases[$i] = '/' . preg_quote($url_prefix . $aliases[$i], '/') . '/';
        return preg_replace($aliases, $modnames, $url);
        }
        
    // Args are for testing only
    function Module($name=NULL, $datasource_class=NULL)
        {
        global $CONF;
        if ($name != '')
            $this->name = $name;
        if ($datasource_class != '')
            $this->datasource_class = $datasource_class;
        $this->path = $CONF['path_modules'] .  $this->name . '/';

        // Get module right from HTTP query string for debugging
        if (@$CONF['debug'] && isset($_GET['module_right']))
            $this->user_right = $_GET['module_right'];

        // Keep saved search/auto-alert flags in sync
        //### TODO: Find a better way to do this
        if ($this->auto_alert_enabled)
            $this->saved_search_enabled = TRUE;
        // If the user needs a right to see the module, show the login form
        if ($this->user_right != '')
            $this->show_login_form = TRUE;
        // add block 'all fields' search filter
        if (!$this->all_fields_search_enabled)
            {
            $filter = Array('BlockAllFieldsSearchFilter');
            if (isset($this->query_config['filters']) && is_array($this->query_config['filters']))
                $this->query_config['filters'] = array_merge($this->query_config['filters'], $filter);
            else
                $this->query_config['filters'] = $filter;
            }

        // Don't show module name in URL if there is only one module
        if (!$CONF['unit_test_active'])
            $this->add_module_to_url = @$CONF['multi_module'];
        
        // Create DS
        parent::__construct(new ModuleResolver());//###
        }

    /// Get the single datasource for this module
    function &get_datasource()
        {
        if (is_null(@$this->_real_ds))
            $this->_real_ds = $this->new_datasource();
        return $this->_real_ds;
        }
        
    /// Create a new datasource for this module. Can be overridden by subclasses.
    /// Do not call this function from client code, use get_datasource.
    function new_datasource()
        {
        $classname = $this->datasource_class;
        return new $classname($this);
        }    

    function __sleep()
        {
        $this->_real_ds = $this->_pear_mdb2 = NULL;
        return(array_keys(get_object_vars(&$this)));
        }

    /// Get PEAR DB (or MDB2) object for this module
    function &get_pear_db()
        {
        if (!isset($this->_pear_mdb2))
            {
            global $CONF;
            // Module configuration overrides default
            $mod_conf = $CONF['unit_test_active'] ? NULL : @$CONF['modules'][$this->name];
            $user = isset($mod_conf['db_user']) ? $mod_conf['db_user'] : $CONF['db_user'];
            $pass = isset($mod_conf['db_pass']) ? $mod_conf['db_pass'] : $CONF['db_pass'];
            $server =  isset($mod_conf['db_server']) ? $mod_conf['db_server'] : $CONF['db_server'];
            if ($CONF['unit_test_active'])
                $database = $CONF['db_database'];
            else
                $database = isset($mod_conf['db_database']) ? $mod_conf['db_database'] : $this->name . @$CONF['db_wart'];
            $dsn = "mysql://{$user}:{$pass}@{$server}/{$database}";
            $this->log(3, "Connecting: $dsn ({$this->name})");
            // DSN string must have new_link=true in order for multiple connections with different charsets to work
            $this->_pear_mdb2 =& MDB2::factory($dsn."?new_link=true");
            if (PEAR::isError($this->_pear_mdb2))
                {
                $this->log(1, 'Error connecting: ' . $this->_pear_mdb2->message . ' ' . $this->_pear_mdb2->userinfo);
                $this->_pear_mdb2 = NULL;
                }
            else
                {
                // Configure the MDB2 connection
                $this->_pear_mdb2->setCharset($this->db_charset[$this->charset]);
                // Without turning this setting off, MDB2 will treat empty strings (i.e. '') as NULLs when storing
                // We prefer to use empty strings for some fields
                $this->_pear_mdb2->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);
                }
            }
        return $this->_pear_mdb2;
        }

    /// Does a user have the right to use this module?
    function has_right($user)
        {
        return $this->user_right == '' || $user->has_right($this->user_right);
        }

    /// Does a user have the right to edit records in this module?
    function can_edit($user)
        {
        if (is_null($this->edit_right))
            return FALSE;
        return $user->has_right($this->edit_right);
        }

    /// Get the URL to a page within the module
    /// If page name is supplied it will have .php appended to it
    function url($page=NULL, $query=NULL)
        {
        global $CONF;
        // Need aliases as mod=>alias
        $aliases = array_flip($CONF['module_aliases']);
        $result = $CONF['url'];
        if (@$aliases[$this->name] != '')
            $result .= $aliases[$this->name];
        else if ($this->add_module_to_url)
            $result .= '/' . $this->name;
        $query = preg_replace('/^[\/]*(' . join('|', $CONF['allowed_modules']) . ')/', '', $query);
        if ($page != '')
            $result .= '/' . $page . $CONF['php_file_ext'];
        if ($query != '')
            $result .= $query;
        return $result;
        }
        
    /// Get the URL to view a record
    /// This function can be further customized per module
    function url_record($record)
        {
        return $this->url('index', $record['url']);
        }

    /// Get the URL to edit the given record
    /// This function can be further customized per module
    function url_edit($record, $parameters=NULL)
        {
        return $this->url('edit', $record['url']);
        }

    /// Get the URL for a login page redirecting to this module
    /// If page name is supplied it will have .php appended to it
    function url_login($page=NULL, $query=NULL)
        {
        global $CONF;
        return $CONF['url_login'] . '?url=' . urlencode($this->url($page, $query));
        }

    /// Get the menu items for this module
    /// Return an array of menu-name => array(title=>, url=>, type=>)
    /// If a menu item has type=line, a separator is shown
    /// If type is 'common' then the function applies to all modules
    /// If type is missing or null then it is specific to the current module
    function menu($user=NULL)
        {
        global $CONF, $STRINGS;
        $result = Array();
        $separator = Array('type'=>'line');
        $result['home'] = Array('title'=>$this->title, 'url'=>$this->url() . '/');
        $result['search'] = $this->make_search_menu_item();
        $result[] = $separator;
        if ($CONF['marked_records_size'] > 0)
            {
            $count =  @$_SESSION['MARKED_RECORDS'] == '' ? 0 : $_SESSION['MARKED_RECORDS']->count();
            $result['marked'] = Array('title'=>'Marked Records (<span id="marked_count">' . format_number($count) . '</span>)', 'url'=>$this->url('history', '/marked'), 'type'=>'common');
            }
        if ($CONF['search_history_size'] > 0)
            $result['history'] = Array('title'=>'History', 'url'=>$this->url('history'), 'type'=>'common');
        if (!is_null($user) && $user->has_right('save_data'))
            {
            if ($this->saved_search_enabled && $CONF['saved_searches_size'] > 0)
                $result['saved'] = Array('title'=>$STRINGS['saved_title'], 'url'=>$this->url('saved'), 'type'=>'common');
            $result['prefs'] = Array('title'=>'Preferences', 'url'=>$this->url('prefs'), 'type'=>'common');
            }
        $result[] = $separator;
        $result['about'] = Array('title'=>'About ' . $this->title, 'url'=>$this->url('index', '/about'));
        return $result;
        }
    
    /// Get a 'search menu' for this module. This is similar to the main menu but returns 
    /// entries for the search tables only (useful if there is more than 1). If a query
    /// is passed, that query's table will be marked as selected.
    function menu_search($query=NULL)
        {
        global $CONF;
        $result = Array();
        $query_tables = $this->list_query_tables();
        foreach ($query_tables as $name=>$label)
            {
            $item = $this->make_search_menu_item(sprintf('Search %s', $label), $name);
            $item['current'] = !is_null($query) && $name == $query->table_name;
            $result['search_' . $name] = $item;
            }
        return $result;
        }

    // Helper for menus
    private function make_search_menu_item($label='Search', $table_name=NULL)
        {
        global $CONF;
        //### FIXME: should we be getting data from the session here??? have to if we want result count
        $query = QueryFactory::get_session_query($this, FALSE, $table_name);
        if  ($CONF['module_mode'] == 'simple' && $query->info['results_count'] > 0)
            $label .= ' (' . $query->info['results_message_unpaged'] . ')';
        return Array('title'=>$label, 'url'=>$query->url(Array('editquery'=>1)));
        }
        
    /// List the tables which have queries associated with them
    function list_query_tables()
        {
        $result = Array();
        $ds = $this->get_datasource();
        $meta_table = $ds->retrieve('/meta');
        $table_names = $meta_table['names'];
        foreach ($table_names as $table_name)
            {
            $table = $ds->retrieve('/' . $table_name);
            // Ignore tables with no label -- may be a bit subtle.
            if (is_array(@$table['query_criteria']) && @$table['title'] != '')
                $result[$table_name] = $table['title'];
            }
        return $result;        
        }

    /// Writes out a javascript include tag for a module specific js file
    /// Also checks for a global array called $JAVASCRIPTS, and also writes out script tags for
    /// the files that exist ( nb, the js extension is not required ) 
    function include_javascript()
        {
        global $CONF, $JAVASCRIPTS;
        $result = Array();
        
        /// TODO AV : for now, the module specific JS is located in the main javascript directory.
        /// Eventually, it should be placed within the modules only directory structure.
        
        /// Only include the modules javascript file if it exists
        if( file_exists( sprintf('%sweb/js/%s.js', $CONF['path'], $this->name )) )
            $result[] = sprintf('<script type="text/javascript" src="%s/js/%s.js"></script>', $CONF['url'], $this->name);
        
        if( is_array($JAVASCRIPTS) )
            {
            foreach( $JAVASCRIPTS as $js )
                if( file_exists( sprintf('%sweb/js/%s.js', $CONF['path'], $js )) )
                    $result[] = sprintf('<script type="text/javascript" src="%s/js/%s.js"></script>', $CONF['url'], $js);
            }
        return join("\n",$result) . "\n";
        }
        
    /// Writes out a css include tag for a module specific css file 
    /// Also checks for a global array called $STYLESHEETS, and also writes out css include tags for
    /// the files that exist ( nb, the css extension is not required )
    function include_stylesheet()
        {
        global $CONF, $STYLESHEETS;
        $result = Array();
        
        /// Only include the modules javascript file if it exists
        if( file_exists( sprintf('%sweb/css/%s.css', $CONF['path'], $this->name )) )
            $result[] = sprintf('<link rel="stylesheet" type="text/css" href="%s/css/%s.css" media="screen, projector, tv"/>', $CONF['url'], $this->name);
        
        if( is_array($STYLESHEETS) )
            {
            foreach( $STYLESHEETS as $css )
                if( file_exists( sprintf('%sweb/css/%s.css', $CONF['path'], $css )) )
                    $result[] = sprintf('<link rel="stylesheet" type="text/css" href="%s/css/%s.css" media="screen, projector, tv"/>', $CONF['url'], $css);
            }
        return join("\n", $result) . "\n";
        }

    /// Find a template file, looking in module-specific and global templates
    /// Return the full path to the template, or NULL if none found
    /// If this function returns non-NULL, the file exists
    function find_template($template_name, $file_ext=NULL)
        {
        global $CONF;
        if (@Module::$_mime_types[$file_ext] == '')
            $file_ext = 'html';
        // Look for type-specific template
        if ($file_ext != 'html' && file_exists($CONF['path_templates'] . $template_name . '.' . $file_ext . '.php'))
            return $CONF['path_templates'] . $template_name . '.' . $file_ext . '.php';
        // Look in module templates for type-specific
        else if ($file_ext != 'html' && file_exists($this->path . 'templates/' . $template_name . '.' . $file_ext . '.php'))
            return $this->path . 'templates/' . $template_name . '.' . $file_ext . '.php';
        // Look in module templates
        else if (file_exists($this->path . 'templates/' . $template_name . '.php'))
            return $this->path . 'templates/' . $template_name . '.php';
        // Look in global templates
        else if (file_exists($CONF['path_templates'] . $template_name . '.php'))
            return $CONF['path_templates'] . $template_name . '.php';
        // Not found
        else
            return NULL;
        }

    /// Get the contents of a template, or NULL if not found
    /// Optionally an array of vars can be passed which will be in scope for the template
    /// NB it is preferred to include the template directly if possible to avoid the overhead of this function
    function get_template($template_name, $vars=NULL)
        {
        $result = NULL;
        $template = $this->find_template($template_name);
        if ($template != '')
            {
            if (!is_null($vars))
                extract($vars);
            ob_start();
            include $template;
            $result = ob_get_contents();
            ob_end_clean();
            }
        return $result;
        }

    /// Gets a table name from a URL, possibly with a module prefix
    function table_from_url($url)
        {
        if ($url == '')
            return NULL;
        $tmp = Array();
        preg_match('/^[^\/]*\/([^\/]*)/', $url, $tmp);
        if (@$tmp[1] != '')
            return $tmp[1];
        else
            return $url;
        }
        
    /// Get a content type string (including charset if appropriate)
    function content_type($file_ext=NULL)
        {
        // helper map
        $map = Array(
            'citation'=>'text',
            );
        if (isset($map[$file_ext]))
            $file_ext = $map[$file_ext];
        if (@Module::$_mime_types[$file_ext] == '')
            $file_ext = 'html';
        $result = @Module::$_mime_types[$file_ext];
        if ($file_ext == 'html' || $file_ext == 'text')
            $result .= '; charset=' . $this->charset;
        return $result;
        }

    /// Factory function for record summary
    ///### TODO: move
    function new_record_summary($table=NULL)
        {
        if ($table != '')
            {
            $table = preg_replace('/^.*\//', '', $table);
            $classname = $this->name . $table . 'RecordSummary';
            if (class_exists($classname))
                return new $classname($this);
            }
        return new RecordSummary($this);
        }        

    /// Get the marked record url for the given record. Some modules need to qualify the URL
    function get_marked_url($record)
        {
        return $record['url'];
        }

    /// Helper, get a CMS page by name
    /// Returns an array with title and content fields, or NULL if page not found
    /// Modify to the needs of an installation (e.g. WordPress etc.)
    function get_cms_page($pagename)
        {
        return NULL;
        }

    /// Wrapper function around htmlentities
    /// This function calls htmlentities using the charset of the module
    function get_htmlentities($string)
        {
        return htmlentities($string, ENT_COMPAT, $this->charset);
        }

    /// Get the feed (e.g. Atom) for the current module
    /// This function to be implemented by subclasses
    function get_feed($path=NULL)
        {
        }

    /// Add any extra handling for posted data from the edit page before saving
    function process_edit_data(&$posted_data, $record, &$is_new)
        {
        }

    /// Finish any module specific edit processing
    function finish_edit_process(&$posted_data, $record, &$error)
        {
        }

    /// Add any extra handling for saving of user preferences for the module
    function process_prefs(&$posted_data, &$user)
        {
        }
        
    /// Allow module specific processing of criteria values
    function process_criteria($query, &$criteria)
        {
        //### TODO: this should probably be part of a general event system
        }
        
    /// Allow module specific processing of render details
    function process_render_details($query, &$details)
        {
        //### TODO: this should probably be part of a general event system
        }

    /// Get the specified formatter
    /// Returns the formatter or NULL if not found
    function new_formatter($type)
        {
        if (empty($type))
            return NULL;
        // helper map
        $map = Array(
            'xml'=>'DublinCore',
            'ical'=>'ICalendar',
            );
        if (isset($map[$type]))
            $type = $map[$type];
        if (!class_exists($type.'Formatter'))
            return NULL;
        $util = (!is_null($this->export_util_class)) ? new $this->export_util_class() : NULL;
        $formatter = $type.'Formatter';
        return new $formatter($this, $util);
        }
    
    /// Formats a list of records using the specified format
    /// Returns the formatted result
    /// A separator can be specified, otherwise the record_separator in
    /// the formatter will be used.
    function format_records($records, $format, $limit, $separator=NULL)
        {
        $formatter = $this->new_formatter($format);
        if (is_null($formatter))
            return NULL;
        if (is_null($separator))
            $separator = $formatter->record_separator;
        $count = 1;
        $result = $formatter->get_header();
        foreach ($records as $record)
            {
            if ($count > $limit)
                break;
            if ($count > 1)
                $result .= $separator;
            $r = $this->retrieve($record['url'], $record);
            $module = isset($r['module']) ? $r['module'] : $this;
            $f = $module->new_formatter($format);
            $result .= $f->format($r);
            $count++;
            }
        $result .= $formatter->get_footer();
        return $result;
        }
        
    /// Get session data for this module, or NULL if none matches
    function get_session_data($key)
        {
        return @$_SESSION[$key . '/' . $this->name];
        }

    /// Add session data for this module
    function set_session_data($key, $data=NULL)
        {
        $key .= '/' . $this->name;
        if (is_null($data))
            unset($_SESSION[$key]);
        else
            $_SESSION[$key] = $data;
        }
    
    function log($level, $message, $modname=NULL)
        {
        $modname = $modname ? $modname : (isset($this) ? $this->name : NULL);
        xlog($level, $message, 'MOD', Array('modname'=>$modname));
        }
    }

/// Dummy module is used by tests and if no other module is available
class DummyModule
    extends Module
    {
    var $name = 'dummy';
    var $datasource_class = 'DummyDataSource';
    var $query_config = Array();
    // use trilt classes for HttpListings test
    // var $listings_query = 'TriltListingsQuery';
    var $listings_class = 'TriltListings';
    var $listings_query_config = Array(
        'table_name' => 'listings',
        );
    var $title = 'Dummy Module';
    var $description = 'Dummy module for use in tests and when no other module is available.';
    var $version = '1.0';
    var $add_module_to_url = 0;

    function DummyModule()
        {
        global $CONF;
        // set certain vars for tests
        if (@$CONF['unit_test_active'])
            {
            $this->edit_right = 'edit_record';
            $this->auto_alert_enabled = true;
            }
        Module::Module();
        // Paths are a special case in this module
        $this->path = $CONF['path_test'];
        }

    function new_datasource()
        {
        return new DummyDataSource($this->get_pear_db());
        }
    
    function get_cms_page($pagename)
        {
        // test specific code
        if ($pagename == 'testcms')
            return Array('title' => 'CMS Test', 'content' => '<p>Using CMS page</p>');
        else
            return parent::get_cms_page($pagename);
        }

    // for testing
    function get_feed($path=NULL)
        {
        $formatter = new AtomFormatter($this);
        $result = $formatter->get_header($this, '2009-06-09T00:00:00Z', 'Dummy author');
        $result .= "  <entry>\n";
        $result .= "    <title>Entry title</title>\n";
        if (!is_null($path))
            $result .= "    <summary>path=$path</summary>\n";
        $result .= "  </entry>\n";
        $result .= $formatter->get_footer();
        return $result;
        }

    // for testing
    function process_prefs(&$posted_data, &$user)
        {
        if (!isset($posted_data['test_field']))
            return;
        $user->prefs['test_field'] = $posted_data['test_field'];
        }

    // for testing
    function process_edit_data(&$posted_data, $record, &$is_new)
        {
        if (@$posted_data['title'] == 'Title before saving')
            $posted_data['title'] = 'Title after saving';
        if (@$posted_data['title'] == 'redirect')
            $posted_data['redirect_url'] = $this->url('edit');
        if (@$posted_data['title'] == 'append_url')
            $posted_data['append_url'] = '?foo=bar';
        if (@$posted_data['title'] == 'error')
            $posted_data['error'] = 'Error message';
        }

    // for testing
    function finish_edit_process(&$posted_data, $record, &$error)
        {
        if (@$posted_data['title'] == 'finish_edit')
            $posted_data['append_url'] = '?foo=bar';
        if (@$posted_data['title'] == 'finish_error')
            $posted_data['error'] = 'error while finishing';
        }
    }
