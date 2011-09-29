<?php
// $Id$
// Tests for QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'parser/test/BaseParserTestCase.class.php');

Mock::generate('DataSource');

class ParsedQueryAdaptorTestCase
    extends BaseParserTestCase
    {
    function setup()
        {
        parent::setup();
        $this->adaptor = new ParsedQueryAdaptor();
        $this->config = Array(
            // replace
            Array(
                'index' => 'a',
                'value' => 'x',
                'query_string' => "abc=xyz",
                ),
            // search all
            Array(
                'index' => 'b',
                'value' => 'y',
                'query_string' => '',
                ),
            // ignore
            Array(
                'index' => 'c',
                'value' => 'z',
                ),
            // function
            Array(
                'index' => 'f',
                'function' => Array($this, 'limit_criteria'),
                ),
            );
        }
    
    function limit_criteria($value)
        {
        // change a value of '2'
        if ($value == '2')
            $value = 'x2';
        // ignored search value
        if ($value == 'ignore')
            $value = NULL;
        return $value;
        }
    
    function limit_criteria_default($value)
        {
        if ($value == '')
            $value = 'default';
        return $value;
        }
    
    function test_convert()
        {
        $tests = Array(
            // Empty tree, nothing changed
            Array(
                'query_string' => '',
                'expected_tree' => NULL,
                ),
            // search-all queries return empty tree
            Array(
                'query_string' => 'b=y',
                'expected_tree' => NULL,
                ),
            // replace one clause
            Array(
                'query_string' => 'a=x',
                'expected_tree' => Array('index'=>'abc', 'subject'=>'xyz', 'relation'=>'='),
                ),
            // unrecognized index is unchanged
            Array(
                'query_string' => '{d=w}',
                'expected_tree' => Array('index'=>'d', 'subject'=>'w', 'relation'=>'='),
                ),
            // function - value unchanged
            Array(
                'query_string' => '{f=1}',
                'expected_tree' => Array('index'=>'f', 'subject'=>'1', 'relation'=>'='),
                ),
            // function - value changed
            Array(
                'query_string' => '{f=2}',
                'expected_tree' => Array('index'=>'f', 'subject'=>'x2', 'relation'=>'='),
                ),
            // put it all together
            Array(
                'query_string' => '{a=x}{b=y}{d>w}{f=2}',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('oper' => 'AND',
                        'left' => Array('index'=>'abc', 'relation'=>'=', 'subject'=>'xyz'),
                        'right' => Array('index'=>'d', 'relation'=>'>', 'subject'=>'w'),
                        ),
                    'right' => Array('index'=>'f', 'relation'=>'=', 'subject'=>'x2'),
                    ),
                ),
            );
        // ignored queries return NULL
        $tree = $this->parser->parse("c=z");
        $converted_tree = $this->adaptor->convert($tree, $this->config);
        $this->assertTrue(is_null($converted_tree));
        $tree = $this->parser->parse("{b=y}{c=z}");
        $converted_tree = $this->adaptor->convert($tree, $this->config);
        $this->assertTrue(is_null($converted_tree));
        // ignored query from function call
        $tree = $this->parser->parse("{f=ignore}");
        $converted_tree = $this->adaptor->convert($tree, $this->config);
        $this->assertTrue(is_null($converted_tree));
        
        foreach ($tests as $test)
            {
            $tree = $this->parser->parse($test['query_string']);
            $converted_tree = $this->adaptor->convert($tree, $this->config);
            $this->assertTree($converted_tree, $test['expected_tree']);
            }
        }
    
    function test_convert_function_with_default()
        {
        $this->config[] = Array(
            'index' => 'g',
            'function' => Array($this, 'limit_criteria_default'),
            );
        $tests = Array(
            // Empty tree, default added
            Array(
                'query_string' => '',
                'expected_tree' => Array('index'=>'g', 'subject'=>'default', 'relation'=>'='),
                ),
            Array(
                'query_string' => '{a=x}',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'abc', 'subject'=>'xyz', 'relation'=>'='),
                    'right' => Array('index'=>'g', 'subject'=>'default', 'relation'=>'='),
                    ),
                ),
            );
        foreach ($tests as $test)
            {
            $tree = $this->parser->parse($test['query_string']);
            $converted_tree = $this->adaptor->convert($tree, $this->config);
            $this->assertTree($converted_tree, $test['expected_tree']);
            }
        }
    }