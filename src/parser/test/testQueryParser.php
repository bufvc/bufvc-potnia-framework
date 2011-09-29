<?php
// $Id$
// Tests for QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'parser/QueryParser.class.php');
require_once($CONF['path_src'] . 'parser/test/BaseParserTestCase.class.php');

class QueryParserFactoryTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->factory = new QueryParserFactory();
        }
    function test_simple_qp()
        {
        $parser = $this->factory->new_parser('any_string');
        $this->assertEqual('simplequeryparser', strtolower(get_class($parser)));
        }
    }

class SimpleQueryParserTestCase
    extends BaseParserTestCase
    {
    function test_empty()
        {
        $tree = $this->parser->parse('');
        $this->assertNull($tree->root);
        $this->assertNoError($this->parser);
        }

    function test_tokenise()
        {
        $tests = Array(
            '' => Array(),
            'a=x y ' => Array('a=x y'),
            'a' => Array('a'),
            '{ a' => Array('a'),
            'a}' => Array('a'),
            '{a}' => Array('a'),
            '{a} AND {b}' => Array('a', 'AND', 'b'),
            '{a}OR{b}' => Array('a', 'OR', 'b'),
            '{a}OR ({b} AND {c})' => Array('a', 'OR', '(', 'b', 'AND', 'c', ')'),
            '{a()}' => Array('a()'), // Parens within braces ignored
            ' { a = b "c ( d )" } ' => Array('a = b "c ( d )"'), // Whitespace preserved within clauses
            '{a="(b)"}{c=d}' => Array('a="(b)"', 'c=d'),
            '( {a=x} ) ' => Array('(', 'a=x', ')'),
            );
        foreach ($tests as $test=>$expected)
            {
            $tokens = $this->parser->tokenize($test);
            $this->assertEqual($tokens, $expected, 'input: ' . $test . '; got: ' . print_r($tokens, TRUE));
            }
        }

    function test_single_clause()
        {
        $tests = Array(
            '{a=x}',
            '{ a  = x   }',    // Whitespace is ignored
            'a=x',      // Missing braces
            '{a=x',     // Missing one brace
            'a=x}',     // Missing other brace
            '{a=x}',

            '( {a=x} ) ',    // Various with parens
            '(a=x)',
            '(a=x})',
            );
        $expected = Array('index'=>'a', 'relation'=>'=', 'subject'=>'x');
        $this->assertParse($tests, $expected);
        }

    function test_trailing_equals_is_part_of_subject()
        {
        $tests = Array(
            '{a=x=}',
            '{ a  = x= }',    // Whitespace is ignored
            'a=x=',      // Missing braces
            '{a=x=',     // Missing one brace
            'a=x=}',     // Missing other brace
            '{a=x=}',

            '( {a=x=} ) ',    // Various with parens
            '(a=x=)',
            '(a=x=})',
            );
        $expected = Array('index'=>'a', 'relation'=>'=', 'subject'=>'x=');
        $this->assertParse($tests, $expected);
        }

    function test_relation()
        {
        $tests = Array(
            '='=>'{a=x}',
            '=='=>'{a ==x}',
            '<='=>'{a<=  x}',
            '>='=>' { a  >= x }',
            '<>'=>'{a<>x}',
            '>'=>'{a>x}',
            '<'=>'{a<x}',
            '&'=>'{a&x}',
            );
        foreach ($tests as $relation=>$q)
            {
            $tree = $this->parser->parse($q);
            $expected = Array('index'=>'a', 'relation'=>$relation, 'subject'=>'x');
            $this->assertNode($tree->root, $expected);
            }
        }

    function test_default_index()
        {
        $tests = Array(
            '{=x}',
            '{x}',
            '{x',       // A bit broken
            '{  x }',   // Whitespace is ignored
            'x',        // Special case
            );
        $expected = Array('index'=>'default', 'relation'=>'=', 'subject'=>'x');
        $this->assertParse($tests, $expected);
        }

    function test_empty_subject()
        {
        $tests = Array(
            '{a=}',
            );
        $expected = Array('index'=>'a', 'relation'=>'=', 'subject'=>'');
        $this->assertParse($tests, $expected);
        }

    function test_AND()
        {
        $tests = Array(
            '{a=x} and {b=y}',  // Explicit
            '{a=x} {b=y}',      // Implicit
            '{a=x}{b=y}',       // No whitespace
            '{a=x}ANd     {b=y}',  // Case insensitive
            );
        $expected = Array('oper' => 'AND',
            'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
            'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
            );
        $this->assertParse($tests, $expected);
        }

    function test_OR()
        {
        $tests = Array(
            '{a=x} or {b=y}',   // Explicit
            '{a=x}oR{b=y}',     // No whitespace
            );
        $expected = Array('oper' => 'OR',
            'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
            'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
            );
        $this->assertParse($tests, $expected);
        }

    function test_NOT()
        {
        $tests = Array(
            '{a=x} NOT {b=y}',   // Explicit
            );
        $expected = Array('oper' => 'NOT',
            'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
            'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
            );
        $this->assertParse($tests, $expected);
        }

    function test_macro_expansion()
        {
        //### FIXME: Index defs only needed here for macros -- better to have separate expand_macros function in parsed query
        // Simple expansion
        $this->parser = new SimpleQueryParser(Array('a' => Array('type'=>'macro', 'value'=>'b=?'),));
        $query_string = "{a=123}";
        $expected = Array('index'=>'b', 'relation'=>'=', 'subject'=>'123');
        $tree = $this->parser->parse($query_string);
        $this->assertTree($tree, $expected);

        // Multiple clauses
        $this->parser = new SimpleQueryParser(Array('a' => Array('type'=>'macro', 'value'=>'{b=?}OR{c=?}'),));
        $query_string = "{a=123}";
        $expected = Array('oper' => 'OR',
            'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'123'),
            'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'123'),
            );
        $tree = $this->parser->parse($query_string);
        $this->assertTree($tree, $expected);

        // Implicit parentheses round macros
        $this->parser = new SimpleQueryParser(Array('a' => Array('type'=>'macro', 'value'=>'{b=?}OR{c=?}'),));
        $query_string = "{x=789}{a=123}";
        $expected = Array('oper' => 'AND',
            'left' => Array('index'=>'x', 'relation'=>'=', 'subject'=>'789'),
            'right' => Array('oper'=>'OR',
                'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'123'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'123')
                ),
            );
        $tree = $this->parser->parse($query_string);
        $this->assertTree($tree, $expected);
        }

    function test_precedence_is_right_to_left()
        {
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
        $this->assertParse($tests, $expected);

        $tests = Array(
            '{a=x} OR {b=y} AND {c=z}',
            );
        $expected = Array('oper' => 'AND',
            'left' => Array('oper'=>'OR',
                'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
                'right' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y')
                ),
            'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
            );
        $this->assertParse($tests, $expected);
        }

    function test_parens()
        {
        $tests = Array(
            '{a=x} AND ({b=y} OR {c=z})',
            );
        $expected = Array('oper' => 'AND',
            'left' => Array('index'=>'a', 'relation'=>'=', 'subject'=>'x'),
            'right' => Array('oper'=>'OR',
                'left' => Array('index'=>'b', 'relation'=>'=', 'subject'=>'y'),
                'right' => Array('index'=>'c', 'relation'=>'=', 'subject'=>'z')
                ),
            );
        $this->assertParse($tests, $expected);
        }

    function test_sort_is_no_longer_special_case()
        {
        $tests = Array('{sort=foo}',);
        $expected = Array('index'=>'sort', 'relation'=>'=', 'subject'=>'foo');
        $this->assertParse($tests, $expected);
        }

    // Test a list of query strings against an expected tree
    function assertParse($query_strings, $expected, $verbose=0)
        {
        foreach ($query_strings as $q)
            {
            $tree = $this->parser->parse($q);
            $this->assertTree($tree, $expected);
            if ($verbose)
                {
                print "* $q\n";
                print_r($tree->root);
                }
            }
        }
    }

class QP_TokenListTestCase
    extends UnitTestCase
    {
    function test_empty()
        {
        $t = new QP_TokenList(Array());
        $this->assertTrue($t->is_empty());
        $this->assertEqual($t->curr_token, 0);
        $this->assertNull($t->next());
        $t->pushback();
        }

    function test_single()
        {
        $t = new QP_TokenList(Array('foo'));
        $this->assertFalse($t->is_empty());
        $this->assertEqual($t->curr_token, 0);
        $this->assertEqual($t->next(), 'foo');
        $this->assertEqual($t->curr_token, 1);
        $this->assertNull($t->next());
        $this->assertTrue($t->is_empty());
        $t->pushback();
        $this->assertFalse($t->is_empty());
        $this->assertEqual($t->next(), 'foo');
        $this->assertNull($t->next());
        $this->assertTrue($t->is_empty());
        }

    function test_many()
        {
        $t = new QP_TokenList(Array('a', 'b'));
        $this->assertFalse($t->is_empty());
        $this->assertEqual($t->curr_token, 0);
        $this->assertEqual($t->next(), 'a');
        $this->assertEqual($t->next(), 'b');
        $this->assertNull($t->next());
        $t->pushback();
        $this->assertEqual($t->next(), 'b');
        $this->assertNull($t->next());
        }

    function test_insert()
        {
        // Insert nothing
        $t = new QP_TokenList(Array('a', 'd'));
        $t->insert(Array(), 1);
        foreach (Array('a', 'd') AS $expect)
            $this->assertEqual($t->next(), $expect);
        $this->assertNull($t->next());

        // Insert in middle
        $t = new QP_TokenList(Array('a', 'd'));
        $t->insert(Array('b', 'c'), 1);
        foreach (Array('a', 'b', 'c', 'd') AS $expect)
            $this->assertEqual($t->next(), $expect);
        $this->assertNull($t->next());

        // Insert at start
        $t = new QP_TokenList(Array('c', 'd'));
        $t->insert(Array('a', 'b'), 0);
        foreach (Array('a', 'b', 'c', 'd') AS $expect)
            $this->assertEqual($t->next(), $expect);
        $this->assertNull($t->next());

        // Insert at end
        $t = new QP_TokenList(Array('a', 'b'));
        $t->insert(Array('c', 'd'));
        foreach (Array('a', 'b', 'c', 'd') AS $expect)
            $this->assertEqual($t->next(), $expect);
        $this->assertNull($t->next());
        }
    }

