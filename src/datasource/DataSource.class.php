<?php
// $Id$
// DataSource class
// James Fryer, 11 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once($CONF['path_src'] . 'datasource/MysqlDataSource.class.php');
require_once($CONF['path_src'] . 'datasource/AggregateStorage.class.php');
require_once($CONF['path_src'] . 'datasource/SphinxStorage.class.php');
require_once($CONF['path_src'] . 'parser/QueryParser.class.php');

/** The DataSource class provides search and CRUD facilities on a set of tables.
    There is always a 'meta' table available to allow for introspection.
    At present there are no schemas available, it is assumed
    Typical tables might be Title, Programme, Broadcast, Event, Person, Keyword, etc.
    This class is conceived as a wrapper round a webservice. Therefore the operations
    map directly to HTTP methods (GET, POST, PUT, DELETE) and the error codes are
    directly based on HTTP status codes (400 etc.)

* To add a new query application
(notes towards documentation)
 - Subclass Query, add the datasource class, criteria definitions, list definitions.
   Should be testable with default DS.

 - Create new search template page for the Query.

 - Subclass DataSource. Define the tables that the datasource knows about.

 - Create new record template pages for the tables.

 - Subclass DataSource_MysqlStorage. At the moment retrieve(), _new_queryparser() and
   _escape_field() need to be defined.

 - In _new_queryparser(), the QueryParser class needs table name, fields and index
   definitions supplied.

This process should be simplified. It should not be necessary to create a new storage
type for each DS, mysql storage should be table-driven. The QueryParser index definitions
could be derived from the DS table definitions.

** Table definition (config) format and options (WORK IN PROGRESS)
The DS expects an array of table definitions. Each table in the array should use the
name by which it will be referenced, e.g. a table Programme might use 'prog' and will
be referenced using '/prog'.

* Table config values include:
    title - the title of the table
    description - a brief description
    mutable - TRUE/FALSE designating if the table can be modified
    storage - the storage handler type, e.g. 'memory' or 'mysql'
    fields - an array of table fields (to be defined by the storage handler)
    search - search fields and indexes (see notes in QueryParser class)

* Mysql fields config
A table using mysql storage should give a 'mysql_table' field to specify the actual
table name in the database.

Mysql field config values include:
    require - 1/true if required field
    type - general types map to database types, 
           there are also special type fields (more below)
    size - size of the data field
    default - specify a default value
    hide - if true this field will not be returned from retrieve

Special type fields
    many_to_one field - config values include
        select - which DB fields to select
        join - any DB joins
        mysql_field - name of the M:1 id field in the table
        lookup - specify the column name to use for looking up existing values
        default_id - specify a default id
        
    many_to_many field - config values include
        related_to - specify the table config to use for this table field, this
                     field only needs to be specifed if the table name differs from
                     the field name. If the link table has more than two keys, this
                     field should be an array specifying all of the relevant tables.
        link - the DB link table name
        select - which DB fields to select
        join - any DB joins
        keys - fields in the link table
        link_columns - an array of additional columns to save in the link table itself
                       e.g. you may have a link table with two keys and an additional
                       field (perhaps a date or flag value)
        lookup - specify the column name to use for looking up existing values.
                 If the link table has more than two keys, this field should be an
                 array specifying the relevant lookup columns in the other tables.
        order - order results by this DB field
        get - specify the query 'get' type, typically this will be left blank for 
              'all', or 'col' can be used if you only want the column value returned
              (and not contained in an array)
        split - specify the character(s) to use for splitting the field, e.g. a 
                person field could use a split of '; *' in order separate names
                with semi-colons
        related_field_map - an array of arrays (1 array for each related table) to
                            define field mapping for lookup and insert
                            e.g. Array(Array(), Array('role'=>'name')) would not
                            map any values for the first related table, but for the
                            second related table it would map a value in 'role' to
                            'name'
        
    one_to_many - config values include
        foreign_key - name of the 1:M id field in the related table
        select - which DB fields to select
    
    implode - config values include
        keys - array of fields (from the table definition) to merge in this field
        implode - the character(s) to use for joining the fields

*/
class DataSourceBase
    {
    /// The current error status
    var $error_code = 0;
    var $error_message = NULL;

    /// Perform a search on  a table.
    /// The query string can be a simple string, or prefixed with a scheme (e.g. 'cql:') ###TODO: scheme
    /// If the query string is empty, this means 'return all records in the table'
    /// The offset into the results and the maximum number of records to return are required
    /// Returns an array of records containing the following fields:
    ///     - data: The array of results
    ///     - count: The number of results returned in this request
    ///     - offset: The index into the table of the first returned result
    ///     - total: The total number of matching results
    ///     - accuracy: How to interpret 'total', can be one of 'exact', 'approx', 'exceeds' ###TBD
    /// If an error occurs, NULL is returned, check error_code and error_message in object
    function search($table, $query_string, $offset, $max_count)
        {
        $this->_set_error(405, 'Method not allowed');
        }

    /// Create a new record in a table
    /// URL can't be set directly, but a 'slug' field can be sent as a hint.
    /// Returns the created record, or NULL on error
    function create($table, $data)
        {
        $this->_set_error(405, 'Method not allowed');
        }

    /// Get a single record
    /// Return an array containing the record, or NULL if not found
    ///### FIXME: Change name... to 'get'???
    function retrieve($url, $parameters=NULL)
        {
        $this->_set_error(405, 'Method not allowed');
        }

    /// Change a record's fields
    /// Return updated record or NULL on error
    function update($url, $record)
        {
        $this->_set_error(405, 'Method not allowed');
        }

    /// Remove a record
    function delete($url)
        {
        $this->_set_error(405, 'Method not allowed');
        }

    // Set error code+message
    function _set_error($code=0, $message='')
        {
        if ($code)
            $this->log(1, "Error: $code $message");
        $this->error_code = $code;
        $this->error_message = $message;
        }

   function log($level, $message)
        {
        xlog($level, $message, 'DATASOURCE');
        }
    }

/// A DS with pluggable storage handlers
class DataSource
    extends DataSourceBase
    {
    // In-memory datasources are stored here
    var $_data = Array();

    // Storage handlers
    var $_storage = Array();
    
    // Normalisation flags
    var $enable_query_normalisation = 1;
    var $enable_storage_normalisation = 1;
    
    // Parser factory
    var $_parser_factory;

	/// Requires an array of table definitions //### TODO: document config
    function DataSource($table_defs, &$storage_factory=NULL)
        {
        // Add the table types, incl meta data
        $default_defs = Array(
            'meta'=> Array(
                'title'=>'Meta-data',
                'description'=>'Information about all tables',
                'mutable'=>0,
                'query_criteria_defined'=>0,
                'storage'=>'memory'
                ),
            );
        $table_defs = array_merge($default_defs, $table_defs);
        foreach ($table_defs as $key=>$meta)
            {
            $meta['_table'] = 'meta';
            $meta['key'] = $key;
            $url = '/' . $key;
            $meta['url'] = $url;
            $this->_data[$url] = $meta;
            }
        $this->_parser_factory = new QueryParserFactory();
        if (is_null($storage_factory))
            $storage_factory = new _DataSourceStorageFactory($this);
        // Create the storage handlers
        foreach ($this->_data as $meta)
            {
            $this->_add_storage_handler($storage_factory, $meta['storage']);
            $this->_add_storage_handler($storage_factory, @$meta['storage_search']);
            }
        $this->_data['/meta']['names'] = $this->_get_meta_table_names();
        $this->_data['/meta']['query_criteria_defined'] = $this->_has_query_criteria();
        }

    private function _add_storage_handler($storage_factory, $name)
        {
        if ($name != '' && !isset($this->_storage[$name]))
            $this->_storage[$name] = $storage_factory->new_storage($name);
        }

    // Get list of strings representing the table names located at /meta/names
    private function _get_meta_table_names()
        {
        $result = Array();        
        // add the table keys to the results, taking care to remove
        // the / prefix which isn't needed
        foreach ($this->_data as $name=>$table)
            $result[] = substr($name, 1);
        return $result;
        }

    // determine whether query criteria are present
    private function _has_query_criteria()
        {
        foreach ($this->_data as $key=>$meta)
            {
            if (isset($meta['query_criteria']))
                return TRUE;
            }
        return FALSE;
        }
        
    function search($table, $query_string, $offset, $max_count)
        {
        $this->log(2, "Search: $table/$query_string ($offset, $max_count)");
        $this->_set_error();

        // Sanity checks
        if ($offset < 0 || $max_count < 0)
            return $this->_set_error(400, 'Invalid search arguments');

        // Get table info
        $meta = $this->_get_meta($table);
        if (is_null($meta))
            return NULL;
        $table = '/' . $meta['key'];
        
        // Parse the query
        if ($this->enable_query_normalisation)
            $query_string = $this->normalise_search_query($query_string);
        $parser = $this->_parser_factory->new_parser($query_string, @$meta['search']['index']);
        $tree = $parser->parse($query_string);
        if (isset($meta['search']['where']))
            $tree = $this->_add_where_clause($meta['search']['where'], $query_string, $tree, $parser);
        $tree = $this->_rewrite_parsed_query($meta, $tree);

        // Dispatch to storage handler, trying search-specific handler first
        $result = NULL;
        $handler = @$this->_storage[$meta['storage_search']];
        if (!is_null($handler))
            {
            $this->log(4, "Search: trying storage_search");
            $result = $handler->search($this, $table, $tree, $offset, $max_count);
            if ($this->error_code)
                return NULL;
            }
        if (is_null($result))
            {
            $this->log(4, "Search: using default search storage");
            $handler = $this->_storage[$meta['storage']];
            $result = $handler->search($this, $table, $tree, $offset, $max_count);
            }       

        // No results is an error condition.
        if (is_null($result))
            $result = Array('total'=>0);
        if (@$result['total'] == 0 && $this->error_code == 0)
            $this->_set_error(400, 'Bad request');

        return $result;
        }
    
    // Modify the tree per the configuration
    private function _rewrite_parsed_query($meta, $tree)
        {
        // apply query adaptor if specified
        if (isset($meta['adaptor']))
            {
            $adaptor = new ParsedQueryAdaptor();
            $converted_tree = $adaptor->convert($tree, $meta['adaptor'], @$meta['search']['index']);
            // 'ignored' searches not currently handled (this was a fed requirement)
            if (!is_null($converted_tree))
                {
                $tree = $converted_tree;
                $this->log(4, "Converted Search: " . @$meta['_table'] . $converted_tree->to_string());
                }
            }
        return $tree;
        }
        
    function _make_search_results_array($data, $total, $offset, $max_count, $accuracy='exact')
        {
        $result = Array();
        $result['data'] = $data;
        $result['offset'] = $offset;
        $result['total'] = $total;
        $result['count'] = min($max_count, max($total - $offset, 0));
        $result['accuracy'] = $accuracy;
        return $result;
        }
    
    // Add the specified where clause to the tree
    // If the index specified in the clause is already present in the tree, do not replace
    // Only simple single clauses are supported at this point
    function _add_where_clause($clause, $query_string, $tree, $parser)
        {
        $where_tree = $parser->parse($clause);
        $index = @$where_tree->root->clause['index'];
        // not a clause
        if (!$index)
            return $tree;
        // this index is already preset in query string, don't replace
        $existing = $tree->find($index);
        if (!empty($existing))
            return $tree;
        $query_string .= $where_tree->to_string();
        return $parser->parse($query_string);
        }
    
    /// Normalise query strings
    /// All double quotes are normalised to ASCII double quotes
    /// All single quotes are treated as an apostrophe and normalised to the UTF-8 apostrophe
    function normalise_search_query($query_string)
        {
        // normalise any double quotes to ASCII double quote
        $double_quotes = Array(
            "\xE2\x80\x9C", // (U+201C) left double quotation mark
            "\xE2\x80\x9D", // (U+201D) right double quotation mark
            "\xE2\x80\x9E", // (U+201E) double low-9 quotation mark
            "\xE2\x80\x9F", // (U+201F) double high-reversed-9 quotation mark
            "\xE2\x80\xB3", // (U+2033) double prime
            "\xE3\x80\x83", // (U+3003) ditto mark
            chr(147),       // left double curly quote in windows
            chr(148),       // right double curly quote in windows
            );
        $query_string = str_replace($double_quotes, '"', $query_string);
        // normalise any single quotes to UTF-8 apostrophe (i.e. U+2019 right single quotation mark)
        $single_quotes = Array(
            "\xE2\x80\x98", // (U+2018) left single quotation mark
            "\xE2\x80\x9A", // (U+201A) single low-9 quotation mark
            "\xE2\x80\x9B", // (U+201B) single high-reversed-9 quotation mark
            "\x60",         // (U+0060) grave accent
            "\xC2\xB4",     // (U+00B4) accute accent
            "\xCC\x80",     // (U+0300) combining grave accent
            "\xCC\x81",     // (U+0301) combining accute accent
            "\xE2\x80\xB2", // (U+2032) prime
            chr(145),       // left single quote in windows
            chr(146),       // right single quote in windows
            "'",            // ASCII single quote
            );
        $query_string = str_replace($single_quotes, "\xE2\x80\x99", $query_string);
        return $query_string;
        }

    function create($table, $record)
        {
        $this->log(2, "Create: $table" . (isset($record['slug']) ? '/' . $record['slug'] : ''));
        xlog_r($record);
        $this->_set_error();

        // Get table info
        $meta = $this->_get_meta($table);
        if (is_null($meta))
            return NULL;
        $table = $meta['key']; // Normalises table name

        // Check permissions
        if (!@$meta['mutable'])
            return $this->_set_error(405, 'Method not allowed');
        // Get a potential URL for the new record
        $url = $this->_make_url($meta, $record);
        // Get required/optional fields
        $tmp = Array();
        foreach ($meta['fields'] as $key=>$field_info)
            {
            // check for a default value for missing fields
            if (!isset($record[$key]) && isset($field_info['default']))
                $record[$key] = $field_info['default'];
            if (@$field_info['require'] && !isset($record[$key]))
                return $this->_set_error(400, 'Bad request');
            $tmp[$key] = @$record[$key];
            }
        $record = $tmp;
        $record['_table'] = $table;
        $record['url'] = $url;
        // Dispatch to storage handler
        if ($this->enable_storage_normalisation)
            $record = $this->normalise_storage_data($record);
        $new_url = $this->_storage[$meta['storage']]->create($this, $url, $record);
        if (is_null($new_url))
            return NULL;
        // Return record
        return $this->_storage[$meta['storage']]->retrieve($this, $new_url);
        }

    function retrieve($url, $parameters=NULL)
        {
        $this->log(2, "Retrieve: $url");
        $this->_set_error();

        $meta = $this->_get_meta($url);
        if (is_null($meta))
            return NULL;
        // If we are retrieving a table, just return table info
        if ($meta['path_info'] == '')
            return $meta;
        $result = $this->_storage[$meta['storage']]->retrieve($this, $url);
        if (is_null($result) && $this->error_code == 0)
            $this->_set_error(404, 'Not found');
        // check for copyright message
        if (!is_null($result) && isset($this->copyright))
            $result['copyright'] = $this->copyright;
        return $result;
        }

    function update($url, $record)
        {
        $this->log(2, "Update: $url");
        // xlog_r($record);
        $this->_set_error();

        $meta = $this->_get_meta($url);
        if (is_null($meta))
            return NULL;
        // Check permissions
        if (!@$meta['mutable'])
            return $this->_set_error(405, 'Method not allowed');
        if ($this->enable_storage_normalisation)
            $record = $this->normalise_storage_data($record);
        $result = $this->_storage[$meta['storage']]->update($this, $url, $record);
        if (!$result && $this->error_code == 0)
            return  $this->_set_error(404, 'Not found');
        if (is_null($result))
            return NULL;
        return $this->_storage[$meta['storage']]->retrieve($this, $url);
        }

    function delete($url)
        {
        $this->log(2, "Delete: $url");
        $this->_set_error();

        $meta = $this->_get_meta($url);
        if (is_null($meta))
            return NULL;
        // Check permissions
        if (!@$meta['mutable'])
            return $this->_set_error(405, 'Method not allowed');
        $result = $this->_storage[$meta['storage']]->delete($this, $url);
        if (is_null($result) && $this->error_code == 0)
            $this->_set_error(404, 'Not found');
        return $result;
        }
    
    /// Normalise data before storing
    /// Leave double quotes alone
    /// Normalise apostrophes (i.e. those that follow a letter)
    /// Normalise open single quotes (i.e. after a space, before a letter)
    function normalise_storage_data($record)
        {
        // for arrays call the function recursively
        if (is_array($record))
            {
            foreach ($record as $key=>$value)
                $record[$key] = $this->normalise_storage_data($value);
            }
        else
            {            
            // normalise apostrophes
            $match = "'|\xE2\x80\x98|\xE2\x80\x9A|\xE2\x80\x9B|\x60|\xC2\xB4|".
                     "\xCC\x80|\xCC\x81|\xE2\x80\xB2|".chr(145)."|".chr(146);
            $record = preg_replace("/([A-Za-z0-9])(".$match.")/ie", "'\\1'.\"\xE2\x80\x99\".''", $record);
            // normalise open single quotes
            $match = "'|\xE2\x80\x99|\xE2\x80\x9A|\xE2\x80\x9B|\x60|\xC2\xB4|".
                     "\xCC\x80|\xCC\x81|\xE2\x80\xB2|".chr(145)."|".chr(146);
            $record = preg_replace("/( )(".$match.")([A-Za-z0-9])/ie", "'\\1'.\"\xE2\x80\x98\".'\\3'.''", $record);
            }
        return $record;
        }

    // Get table metadata
    // The table name can be a URL e.g. '/test', 'test', and 'test/foo'
    // will all be treated as 'test'
    // The array returned contains url, key, mutable, table, and path_info
    function _get_meta($table)
        {
        // Normalise URL
        $regs = Array();
        // Regex splits path into head and tail on the first non-leading slash
        ereg('^/*([^/]+)/*(.*)$', $table, $regs);
        $url = '/' . @$regs[1];
        if (!isset($this->_data[$url]))
            return $this->_set_error(404, 'Not Found (' . $table . ')');
        $result = $this->_data[$url];
        $result['path_info'] = @$regs[2];
        return $result;
        }

    // Make the URL from the slug
    function _make_url($meta, $record)
        {
        $table = $meta['key'];

        // Get slug
        if (isset($meta['slug']))
            $slug = $record[$meta['slug']];
        else if (isset($record['slug']))
            $slug = $record['slug'];
        else
            $slug = rand();
        $slug = $this->_fix_slug($slug);
        $url = '/' . $table . '/' . $slug;
        return $url;
        }

    // Remove unacceptable characters from a slug string
    function _fix_slug($slug)
        {
        // Change spaces to underscores, remove other non-alumn except hyphen
        $slug = str_replace(' ', '_', $slug);
        $slug = ereg_replace('[^[:alnum:]_./-]+', '', $slug);
        $slug = strtolower($slug);
        return $slug;
        }
    }

/// Helper class, manages storage objects
class _DataSourceStorageFactory
    {
    function _DataSourceStorageFactory(&$ds)
        {
        $this->_ds = $ds;
        }

    function new_storage($name)
        {
        $classname = 'DataSource_' . $name . 'Storage';
        if (class_exists($classname))
            return new $classname($this->_ds);
        else
            return NULL;
        }
    }

/// Memory storage handler
/// Also base class for other handlers
/// The interface for the functions is simplified from the main DS interface.
/// All preconditions have been checked, and if the function returns NULL an error will be set
class DataSource_MemoryStorage
    {
    /// Perform a search.
    /// Return a paged array of results per DS->search()
    function search(&$ds, $table, $tree, $offset, $max_count)
        {
        if ($table[0] != '/')
            $table = '/' . $table;
        $result = Array();
        
        // Get the query
        //### TODO: Improve this so it can handle more complex queries instead of ignoring all but the first clause
        $tmp = $tree->flatten();
        $query_string = @$tmp[0][0]['subject'];
        
        foreach ($ds->_data as $record)
            {
            // Get the table of this record -- it's the first part of the path,
            //  or /meta if the path has one element
            if (substr_count($record['url'], '/') == 1)
                $record_table = '/meta';
            else
                $record_table = dirname($record['url']);
            if ($table == $record_table &&
                    ($query_string == '' || strpos($record['title'] . $record['description'], $query_string) !== FALSE))
                {
                $record['summary'] = $record['description'];
                $result[] = $record;
                }
            }
        $total = count($result);
        $result = array_slice($result, $offset, $max_count);
        return $ds->_make_search_results_array($result, $total, $offset, $max_count);
        }

    /// Create returns the newly created URL
    function create(&$ds, $url, $record)
        {
        // Make URL unique if necessary
        $tmp = $url;
        $i = 1;
        while (isset($ds->_data[$tmp]))
            $tmp = $url . $i;
        $url = $tmp;
        $ds->_data[$url] = $record;
        $ds->_data[$url]['url'] = $url;
        return $url;
        }

    // Retrieve returns the full record
    function retrieve(&$ds, $url)
        {
        if (isset($ds->_data[$url]))
            return $ds->_data[$url];
        else if (string_ends_with($url, '!random'))
            return $ds->_data[array_rand($ds->_data)];
        else
            return NULL;
        }

    // Update returns TRUE on success
    function update(&$ds, $url, $record)
        {
        if (!isset($ds->_data[$url]))
            return NULL;
        // Copy existing non-special fields
        unset($record['url']);
        foreach ($record as $key=>$value)
            {
            if ($key[0] != '_')
                $ds->_data[$url][$key] = $value;
            }
        return $ds->_data[$url];
        }

    // Delete returns TRUE on success
    function delete(&$ds, $url)
        {
        if (!isset($ds->_data[$url]))
            return NULL;
        else {
            unset($ds->_data[$url]);
            return 1; // Dummy non-NULL value
            }
        }
    }
