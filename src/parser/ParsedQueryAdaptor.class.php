<?php
// $Id$
// ParsedQueryAdaptor class
// Phil Hansen, 14 Oct 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Modify a parsed query from one module so it is compatible with another module
///
/// An adaptor config is an array of config values for each query string name/value
/// pair to adapt.  Each config should be specified in an array as follows:
///     index: search index to adapt
///     value: search value to match
///     relation: e.g. =, <, > (if not specified defaults to '=')
///     query_string: the query string (in DS format) to use for replacement
///
/// If a given name/value pair should match 'all records' simply leave the 
/// query_string field empty ''.  If a name/value pair is not supported and
/// should be ignored, do not include the query_string field in the config.
/// 
/// A config can also specify a function to be used for modifying the value. This
/// is useful for situations where criteria need to be dynamically adapted. Configs
/// of this type should be specified in an array as follows:
///     index: search index to adapt
///     function: the function to use, typically given as Array($object, 'method_name')
///     relation: e.g. =, <, > (if not specified defaults to '=')
/// 
class ParsedQueryAdaptor
    {
    // Parser factory
    var $_parser_factory;
    
    function ParsedQueryAdaptor()
        {
        $this->_parser_factory = new QueryParserFactory();
        }

    // Convert a given tree based on the given config
    // Returns the converted tree
    // Returns NULL if the original tree contained any clauses to be ignored
    function convert($tree, $config, $index_defs=NULL)
        {
        $new_tree = clone($tree);
        $parser = $this->_parser_factory->new_parser($new_tree->to_string(), $index_defs);
        foreach ($config as $item)
            {
            // relation defaults to '='
            $relation = isset($item['relation']) ? $item['relation'] : '=';
            
            // special case, check for an index that uses a function - e.g. limiting criteria
            if (isset($item['function']))
                {
                $value = $new_tree->find($item['index']);
                $value = @$value[0]['subject'];
                $new_value = call_user_func($item['function'], $value);
                // ignore this search
                if (is_null($new_value) && !is_null($value))
                    return NULL;
                if ($new_value != $value)
                    {
                    $new_tree->remove($item['index']);
                    $new_tree->add(new QP_TreeClause($item['index'], $relation, $new_value));
                    }
                continue;
                }
            
            if (is_null($new_tree->root) || !$new_tree->match($item['index'], $item['value'], $relation))
                continue;
            
            // ignore this search
            if (!isset($item['query_string']))
                return NULL;
            // search all
            else if ($item['query_string'] == '')
                $new_tree->remove_one($item['index'], $item['value'], $relation);
            // replace with the new clause/tree
            else
                {
                $sub_tree = $parser->parse($item['query_string']);
                $new_tree->replace($item['index'], $item['value'], $relation, $sub_tree->root);
                }
            }
        return $new_tree;
        }
    }
