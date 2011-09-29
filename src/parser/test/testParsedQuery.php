<?php
// $Id$
// Tests for QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'parser/QueryParser.class.php');
require_once($CONF['path_src'] . 'parser/ParsedQuery.class.php');
require_once($CONF['path_src'] . 'parser/test/BaseParserTestCase.class.php');

class ParsedQueryTestCase
    extends BaseParserTestCase
    {
    function test_normalise_single()
        {
        // Single statement
        $tests = Array(
            '{a=x}',
            );
        $expected = Array('index'=>'a', 'relation'=>'=', 'subject'=>'x');
        $this->assertNormalised($tests, $expected);
        }

    function test_normalise_AND()
        {
        // AND only
        $tests = Array(
            '{a=x} and {b=y}',  // Explicit
            );
        $expected = Array('oper' => 'AND',
            'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
            'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
            );
        $this->assertNormalised($tests, $expected);
        }

    function test_normalise_OR_already_at_top()
        {
        // OR at top
        $tests = Array(
            '{a=x} AND {b=y} OR {c=z}',
            );
        $expected = Array('oper' => 'OR',
            'left' => Array('oper'=>'AND',
                'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y')
                ),
            'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
            );
        $this->assertNormalised($tests, $expected);
        }

    // ((A OR B) AND C) -> ((A AND C) OR (B AND C))
    function test_normalise__A_OR_B__AND_C()
        {
        $tests = Array(
            '{a=x} OR {b=y} AND {c=z}',
            );
        $expected = Array('oper' => 'OR',
            'left' => Array('oper'=>'AND',
                'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
                ),
            'right' => Array('oper'=>'AND',
                'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
                ),
            );
        $this->assertNormalised($tests, $expected);
        }

    // ((A OR B) NOT C) -> ((A NOT C) OR (B NOT C))
    function test_normalise__A_OR_B__NOT_C()
        {
        $tests = Array(
            '{a=x} OR {b=y} NOT {c=z}',
            );
        $expected = Array('oper' => 'OR',
            'left' => Array('oper'=>'NOT',
                'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
                ),
            'right' => Array('oper'=>'NOT',
                'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
                ),
            );
        $this->assertNormalised($tests, $expected);
        }

    // (A NOT (B OR C)) -> ((A NOT B) NOT C)
    function test_normalise__A_NOT__B_OR_C()
        {
        $tests = Array(
            '{a=x} NOT ({b=y} OR {c=z})',
            );
        $expected = Array('oper' => 'NOT',
            'left' => Array('oper'=>'NOT',
                'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y')
                ),
            'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
            );
        $this->assertNormalised($tests, $expected);
        }

    function test_flatten_errors()
        {
        // Un-normalised
        $query_string = '{a=x} OR {b=y} AND {c=z}';
        $tree = $this->parser->parse($query_string);
        $flat = $tree->flatten();
        $this->assertNull($flat);
        $this->assertError($tree);
        }

    function test_flatten_single()
        {
        $query_string = '{a=x}';
        $expected = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $tree = $this->parser->parse($query_string);
        $flat = $tree->flatten();
        $this->assertNoError($tree);
        $this->assertEqual($flat, $expected);
        }

    function test_flatten_AND()
        {
        $query_string = '{a=x} AND {b=y}';
        $expected = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y', 'oper'=>'AND'),
                ),
            );
        $tree = $this->parser->parse($query_string);
        $flat = $tree->flatten();
        $this->assertNoError($tree);
        $this->assertEqual($flat, $expected);
        }

    function test_flatten_OR()
        {
        $query_string = '{a=x} OR {b=y} AND {c=z}'; # Will be normalised
        $expected = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'z', 'oper'=>'AND'),
                ),
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y', 'oper'=>''),
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'z', 'oper'=>'AND'),
                ),
            );
        $tree = $this->parser->parse($query_string);
        $tree->normalise();
        $flat = $tree->flatten();
        $this->assertNoError($tree);
        $this->assertEqual($flat, $expected);
        }

    function test_flatten_NOT()
        {
        $query_string = '{a=x} OR {b=y} NOT {c=z}'; # Will be normalised
        $expected = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'z', 'oper'=>'NOT'),
                ),
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y', 'oper'=>''),
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'z', 'oper'=>'NOT'),
                ),
            );
        $tree = $this->parser->parse($query_string);
        $tree->normalise();
        $flat = $tree->flatten();
        $this->assertNoError($tree);
        $this->assertEqual($flat, $expected);
        }

    function test_empty_tree()
        {
        // Empty parse tree, not an error
        $tree = $this->parser->parse('');
        $this->assertNoError($tree);
        }

    function test_find()
        {
        $tests = Array(
            '' => NULL,
            '{a=x}{b=y}' => Array(Array('index'=>'a', 'relation'=>'=', 'subject'=>'x')),
            '{a=x}{b=y}{a<z}' => Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                Array('index'=>'a', 'relation'=>'<', 'subject'=>'z'),
                ),
            '{a=x} AND ({b=y} OR {a<z})' => Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                Array('index'=>'a', 'relation'=>'<', 'subject'=>'z'),
                ),
            );
        foreach ($tests as $q=>$expected)
            {
            $tree = $this->parser->parse($q);
            $items = $tree->find('a');
            $this->assertEqual($expected, $items, $q . ' %s');
            }
        }
        
    function test_add()
        {
        $tree = $this->parser->parse('');
        $tree->add(new QP_TreeClause('a', '=', 'x'));
        $this->assertTree($tree, Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'));

        $tree->add(new QP_TreeClause('b', '=', 'y'));
        $expected = Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    );
        $this->assertTree($tree, $expected);

        //### TODO: OR, NOT opers (AND is default)
        //### TODO: for convenience, add separate index/value/relation???
        }

    function test_remove()
        {
        $tests = Array(
            // Empty tree, remove nothing
            Array(
                'query_string' => '',
                'remove_index' => '',
                'expected_tree' => NULL,
                ),
            // Index not found, remove nothing
            Array(
                'query_string' => '{a=x}{b=y}',
                'remove_index' => 'z',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    ),
                ),
            Array(
                'query_string' => '{b=y}',
                'remove_index' => 'a',
                'expected_tree' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                ),
            // Remove whole clause
            Array(
                'query_string' => '{a=x}',
                'remove_index' => 'a',
                'expected_tree' => NULL,
                ),
            // Remove one of two clauses
            Array(
                'query_string' => '{a=x}{b=y}',
                'remove_index' => 'a',
                'expected_tree' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                ),
            Array(
                'query_string' => '{b=y}{a=x}',
                'remove_index' => 'a',
                'expected_tree' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                ),
            // Remove one of three clauses
            Array(
                'query_string' => '{a=x}({b=y}OR{c=z})',
                'remove_index' => 'a',
                'expected_tree' => Array('oper' => 'OR',
                    'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                    ),
                ),
            Array(
                'query_string' => '({a=x}OR{b=y}){c=z}',
                'remove_index' => 'a',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                    ),
                ),
            Array(
                'query_string' => '({b=y}OR{a=x}){c=z}',
                'remove_index' => 'a',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                    ),
                ),
            Array(
                'query_string' => '{b=y}({c=z}OR{a=x})',
                'remove_index' => 'a',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                    ),
                ),
            // Remove multiple clauses
            Array(
                'query_string' => '{a=x}{b=y}({a=xx}{b=yy}{c=z})NOT{a=xxx}',
                'remove_index' => 'a',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('oper' => 'AND',
                        'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'yy'),
                        'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                        ),
                    ),
                ),
            );
        foreach ($tests as $test)
            {
            $tree = $this->parser->parse($test['query_string']);
            $items = $tree->remove($test['remove_index']);
            $this->assertNoError($tree);
            $this->assertTree($tree, $test['expected_tree']);
            }
        }
    
    function test_remove_one()
        {
        $tests = Array(
            // Empty tree, remove nothing
            Array(
                'query_string' => '',
                'remove_index' => '',
                'remove_value' => '',
                'expected_tree' => NULL,
                ),
            // Index not found, remove nothing
            Array(
                'query_string' => '{a=x}{b=y}',
                'remove_index' => 'z',
                'remove_value' => 'c',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    ),
                ),
            // Whole clause does not match, remove nothing
            Array(
                'query_string' => '{a=x}{b=y}',
                'remove_index' => 'a',
                'remove_value' => 'c',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    ),
                ),
            // Remove whole clause
            Array(
                'query_string' => '{a=x}',
                'remove_index' => 'a',
                'remove_value' => 'x',
                'expected_tree' => NULL,
                ),
            // Remove only one clause
            Array(
                'query_string' => '{a=x}{a=y}{a>z}',
                'remove_index' => 'a',
                'remove_value' => 'x',
                'expected_tree' => Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'y'),
                    'right' => Array('index'=>'a', 'relation'=>'>', 'subject'=>'z'),
                    ),
                ),
            // Relation must match
            Array(
                'query_string' => '{a<x}{a=x}',
                'remove_index' => 'a',
                'remove_value' => 'x',
                'remove_relation' => '<',
                'expected_tree' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                ),
            );
        foreach ($tests as $test)
            {
            $tree = $this->parser->parse($test['query_string']);
            if (isset($test['remove_relation']))
                $items = $tree->remove_one($test['remove_index'], $test['remove_value'], $test['remove_relation']);
            else
                $items = $tree->remove_one($test['remove_index'], $test['remove_value']);
            $this->assertNoError($tree);
            $this->assertTree($tree, $test['expected_tree']);
            }
        }
    
    function test_replace()
        {
        $sub_tree = $this->parser->parse("{b=y}");
        $new_node = $sub_tree->root;
        // empty tree
        $tree = $this->parser->parse("");
        $tree->replace('a', 'x', '=', $new_node);
        $this->assertTree($tree, NULL);
        // no match
        $tree = $this->parser->parse("{a=x}");
        $tree->replace('z', 'x', '=', $new_node);
        $this->assertTree($tree, Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'));
        // single clause
        $tree = $this->parser->parse("{a=x}");
        $tree->replace('a', 'x', '=', $new_node);
        $this->assertTree($tree, Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'));
        // replace with a tree
        $tree = $this->parser->parse("{a=x}{b=y}");
        $sub_tree = $this->parser->parse("{c=z}OR{d=w}");
        $new_node = $sub_tree->root;
        $tree->replace('b', 'y', '=', $new_node);
        $expected = Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('oper' => 'OR',
                        'left' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                        'right' => Array('index'=>'d', 'relation'=>'=', 'subject'=>'w'),
                        ),
                    );
        $this->assertTree($tree, $expected);
        }

    function test_match()
        {
        //### TODO: Does not examine operators
        //### TODO: QueryMatcher class??? would also be useful in memory DS
        $tree = $this->parser->parse('{a=x}');
        $this->assertFalse($tree->match('a', 'z'));
        $this->assertFalse($tree->match('a', 'x', '<'));
        $this->assertTrue($tree->match('a', 'x', '='));
        $tree = $this->parser->parse('{a=zzz}{b=y}{a=x}{c=z}');
        $this->assertTrue($tree->match('a', 'x'));
        }
        
    function test_match_array()
        {
        $tree = $this->parser->parse('{a=x}');
        $this->assertTrue($tree->match('a', Array('x','y')));
        $this->assertTrue($tree->match('a', Array('z', 'x','y')));
        $this->assertFalse($tree->match('a', Array('y','z')));
        }
        
    function test_to_string()
        {
        $tests = Array(
            ''=>'',
            'a=x'=>'{a=x}',
            '{a=x}{b=y}'=>'{a=x}AND{b=y}',
            '{a=x}{b=y}OR{c=z}'=>'({a=x}AND{b=y}OR{c=z})',
            '{a=x}({b=y}OR{c=z})'=>'{a=x}AND({b=y}OR{c=z})',
            );
        foreach ($tests as $test=>$expected)
            {
            $tree = $this->parser->parse($test);
            $this->assertEqual($expected, $tree->to_string());
            }
        // special case, null
        $tree = new ParsedQuery(NULL);
        $this->assertEqual('', $tree->to_string());
        }
    
    function test_clone()
        {
        // clone empty tree
        $tree = $this->parser->parse("");
        $tree2 = clone($tree);
        $this->assertTrue(is_null($tree2->root));
        // clone a tree with a single QP_TreeClause
        $tree = $this->parser->parse("{a=x}");
        $tree2 = clone $tree;
        $tree2->root->clause['index'] = 'b';
        // original tree has not changed
        $this->assertTree($tree, Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'));
        // clone a tree with a QP_TreeOper
        $tree = $this->parser->parse("{a=x}{b=y}");
        $tree2 = clone $tree;
        $tree2->root->left->clause['index'] = 'b';
        $tree2->root->oper = 'OR';
        $expected = Array('oper' => 'AND',
                    'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                    'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                    );
        $this->assertTree($tree, $expected);
        // clone a tree with nested QP_TreeOper
        $tree = $this->parser->parse("{a=x}{b=y}OR{c=z}");
        $tree2 = clone $tree;
        $tree2->root->left->left->clause['index'] = 'b';
        $tree2->root->left->right->clause['subject'] = 'aa';
        $tree2->root->oper = 'AND';
        $tree2->root->left->oper = 'OR';
        $expected = Array('oper' => 'OR',
                    'left' => Array('oper' => 'AND',
                        'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                        'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                        ),
                    'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z'),
                    );
        $this->assertTree($tree, $expected);
        }
        
    // Test a normalised parse
    function assertNormalised($query_strings, $expected, $verbose=0)
        {
        foreach ($query_strings as $q)
            {
            $tree = $this->parser->parse($q);
            $tree->normalise();
            $this->assertTree($tree, $expected);
            if ($verbose)
                {
                print "* $q\n";
                print_r($tree->root);
                }
            }
        }
    }

class QP_TreeClauseTestCase
    extends UnitTestCase
    {
    function test_equals()
        {
        $clause = new QP_TreeClause('a', '=', 'b');
        $this->assertTrue($clause->equals(new QP_TreeClause('a', '=', 'b')));
        $this->assertFalse($clause->equals(new QP_TreeClause('b', '=', 'a')));
        $this->assertFalse($clause->equals(new QP_TreeClause('a', '=', 'c')));
        $this->assertFalse($clause->equals(new QP_TreeClause('a', '<', 'b')));
        $this->assertFalse($clause->equals(new QP_TreeClause('', '', '')));
        }
    }
