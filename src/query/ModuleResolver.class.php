<?php
// $Id$
// DS wrapper to resolve module name
// James Fryer, 15 Apr 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Enable injection of module
class ModuleResolver_Loader
    {
    function load_module($name)
        {
        return Module::load($name);
        }
    }

class ModuleResolver
    extends DataSourceBase
    {
    function __construct(&$module_loader=NULL)
        {
        if (is_null($module_loader))
            $module_loader = new ModuleResolver_Loader();
        $this->_module_loader = $module_loader;
        }
        
    // Setup $this->module, return the URL with module part removed
    //  or NULL on error. 
    function _load_module($url)
        {
        if ($url[0] == '/')
            $url = substr($url, 1);
        $tmp = explode('/', $url, 2);
        if (count($tmp) < 2)
            {
            $this->_set_error(404, 'Module not found in "' . $url . '"');
            return NULL;
            }
        $module_name = $tmp[0];
        $url = '/' . $tmp[1];
        $this->module = $this->_module_loader->load_module($module_name);
        if (is_null($this->module))
            {
            $this->_set_error(404, 'Module not found in "' . $url . '"');
            return NULL;
            }
        return $url;
        }
        
    // Fix the URL values in a record
    function _fix_record(&$record)
        {
        if (@$record['url'] != '')
            $record['url'] = '/' . $this->module->name . $record['url'];
        if (@$record['_table'] != '')
            $record['_table'] = $this->module->name . '/' . $record['_table'];
        if (is_array($record))
            $record['module'] = $this->module;
        }
        
    function search($table, $query_string, $offset, $max_count)
        {
        $url = $this->_load_module($table);
        if ($url == '')
            return;
        $ds = $this->module->get_datasource();
        $result = $ds->search($url, $query_string, $offset, $max_count);
        if (@$result['count'] > 0)
            {
            //### FIXME: If possible push this down into the SQL 
            for ($i = 0; $i < count($result['data']); $i++)
                {
                $modname = isset($result['data'][$i]['module']) ? $result['data'][$i]['module']->name : $this->module->name;
                $result['data'][$i]['url'] = '/' . $modname . $result['data'][$i]['url'];
                if (@$result['data'][$i]['_table'] != '')
                    $result['data'][$i]['_table'] = $modname . '/' . $result['data'][$i]['_table'];
                }
            }
        if (is_array($result))
            $result['module'] = $this->module;
        $this->_set_error($ds->error_code, $ds->error_message);
        return $result;
        }
        
    function create($table, $data)
        {
        $url = $this->_load_module($table);
        if ($url == '')
            return;
        $ds = $this->module->get_datasource();
        $result = $ds->create($url, $data);
        $this->_fix_record($result);
        $this->_set_error($ds->error_code, $ds->error_message);
        return $result;
        }

    function retrieve($url, $parameters=NULL)
        {
        $url = $this->_load_module($url);
        if ($url == '')
            return;
        $ds = $this->module->get_datasource();
        $result = $ds->retrieve($url, $parameters);
        $this->_fix_record($result);
        $this->_set_error($ds->error_code, $ds->error_message);
        return $result;
        }

    function update($url, $record)
        {
        $url = $this->_load_module($url);
        if ($url == '')
            return;
        $ds = $this->module->get_datasource();
        $result = $ds->update($url, $record);
        $this->_fix_record($result);
        $this->_set_error($ds->error_code, $ds->error_message);
        return $result;
        }

    function delete($url)
        {
        $url = $this->_load_module($url);
        if ($url == '')
            return;
        $ds = $this->module->get_datasource();
        $result = $ds->delete($url);
        $this->_set_error($ds->error_code, $ds->error_message);
        return $result;
        }
    }
