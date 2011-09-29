<?php
// $Id$
// Tests for Sphinx query generation
// James Fryer, 2011-03-09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once $CONF['path_lib'] . 'sphinxapi.php';

Mock::generate('SphinxClient', 'MockSphinx');

// Exercises the to_sql() function which expects a modified tree
class SphinxGeneratorTestCase
    extends UnitTestCase
    {
    function setup($sort_default=NULL)
        {
        $index_defs = Array(
            'a' => Array('type'=>'string', 'fields'=>'t.A'),
            'b' => Array('type'=>'fulltext', 'fields'=>'t.B1,t.B2,t.B3'),
            'c' => Array('type'=>'number', 'fields'=>'t.C'),
            'd' => Array('type'=>'fulltext', 'fields'=>'t.D1', 'default_tables'=>1),
            'e' => Array('type'=>'datetime', 'fields'=>'t.E1'),
            'f' => Array('type'=>'blank', 'fields'=>'t.F1'),
            'g' => Array('select'=>'t.a,t.b', 'type'=>'string', 'fields'=>'t.A'),
            'h' => Array('type'=>'date', 'fields'=>'t.H1'),
            'j' => Array('type'=>'fulltext', 'fields'=>'t.A', 'sphinx_fields'=>'J1,J2'),
            'k' => Array('type'=>'replace_join', 'sphinx_index'=>'new_index'),
            'badtype' => Array('type'=>'undefined_type', 'fields'=>'unused'),
            'sort.s1' => Array('type'=>'asc', 'fields'=>'t.S1'),
            'sort.s2' => Array('type'=>'desc', 'fields'=>'t.S2'),
            'sort.s3' => Array('type'=>'desc', 'fields'=>'j.S3', 'join'=>'JOIN J j ON j.x=t.a'),
            'sort.s4' => Array('type'=>'rel'),
            'sort.s5' => Array('type'=>'rel', 'weight'=>'0.6 * c'),
            'sort.s6' => Array('type'=>'asc', 'fields'=>'t.S6', 'additional_sort'=>'t.A6'),
            'sort.s7' => Array('type'=>'desc', 'fields'=>'t.S7', 'additional_sort'=>'t.A7 DESC'),
            'sort.s8' => Array('type'=>'asc', 'fields'=>'t.S1', 'sphinx_fields'=>'J1'),
            );
        if ($sort_default != '')
            $index_defs['sort.default'] = $sort_default;
        $this->sphinx = new MockSphinx();
        $this->sphinx->_socket = FALSE; // Suppress error message
        $this->builder = new SphinxGenerator($index_defs, $this->sphinx);
        }

    function parse_query($query_string)
        {
        $factory = new QueryParserFactory();
        $parser = $factory->new_parser($query_string);
        return $parser->parse($query_string);
        }

    function test_all_records()
        {
        $tree = $this->parse_query('');
        //### ResetFilters???
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->setReturnValue('AddQuery', 'return-value-passed-up');
        // Assumes $CONF['sphinx_max_matches'] = 56;
        $this->sphinx->expectOnce('SetLimits', Array(12, 34, 56));
        $this->sphinx->expectOnce('SetArrayResult', Array(TRUE));
        $this->sphinx->expectNever('SetMatchMode');
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        $this->assertEqual('return-value-passed-up', $r);
        }

    function test_simple_search()
        {
        $tree = $this->parse_query('a=foo');
        //### ResetFilters???
        $this->sphinx->expectOnce('AddQuery', Array('@A foo', 'test-index', '*'));
        $this->sphinx->expectOnce('SetLimits', Array(12, 34, 56));
        $this->sphinx->expectOnce('SetArrayResult', Array(TRUE));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_apostrophes_removed()
        {
        $tree = $this->parse_query("a='y\xE2\x80\x99"); // U+2019, apostrophe
        $this->sphinx->expectOnce('AddQuery', Array('@A y', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sphinx_accessor()
        {
        $tree = $this->parse_query('foo');
        $this->assertEqual($this->sphinx, $this->builder->sphinx());
        }
    
    //### TODO: implement OR using multiple queries and merging ???
    function test_OR_not_supported()
        {
        $tree = $this->parse_query('{a=b}OR{b=c}');
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNull($r);
        $this->assertNoError();
        }

    function test_query_is_normalised()
        {
        // This query is rejected because it contains OR -- but in order to
        // recognise this, it must be normalised by add_query
        $tree = $this->parse_query('{a=x}AND({a=b}OR{b=c})');
        $this->sphinx->expectNever('AddQuery');
        $this->sphinx->expectNever('SetMatchMode');
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNull($r);
        $this->assertNoError();
        }

    function test_string_equals()
        {
        $tree = $this->parse_query('a=x*');
        $this->sphinx->expectOnce('AddQuery', Array('@A x*', 'test-index', '*'));
        $this->sphinx->expectOnce('SetLimits', Array(12, 34, 56));
        $this->sphinx->expectOnce('SetArrayResult', Array(TRUE));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    // Can't do string filtering by relation other than equals
    function test_string_unsupported_opers()
        {
        $relations = Array('<', '>', '>=', '<=');
        $relations[] = '<>'; //### TEMP!
        foreach ($relations as $relation)
            {
            $tree = $this->parse_query("a{$relation}x");
            $r = $this->builder->add_query('test-index', $tree, 12, 34);
            $this->assertNull($r);
            $this->assertNoError();
            }
        }

    function test_fulltext()
        {
        // Boolean expression is rewritten
        $tree = $this->parse_query('b=y OR z');
        $this->sphinx->expectOnce('AddQuery', Array('@(B1,B2,B3) y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_fulltext_bad_oper()
        {
        // Boolean expression is rewritten
        $tree = $this->parse_query('b<>y OR z');
        $this->sphinx->expectNever('AddQuery');
        $this->sphinx->expectNever('SetMatchMode');
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNull($r);
        $this->assertNoError();
        }

    function test_fulltext_default_tables()
        {
        $tree = $this->parse_query('d=y OR z');
        $this->sphinx->expectOnce('AddQuery', Array('y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_fulltext_sphinx_field()
        {
        // Boolean expression is rewritten
        $tree = $this->parse_query('j=y OR z');
        $this->sphinx->expectOnce('AddQuery', Array('@(J1,J2) y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_number()
        {
        $tree = $this->parse_query('{c=123}{c<>123}{c>123}{c>=123}{c<123}{c<=123}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilter', 2);
        // These first two are strings because expect is strict about int/string comparison
        $this->sphinx->expectAt(0, 'SetFilter', Array('C', Array('123'), FALSE), '%s [=]');
        $this->sphinx->expectAt(1, 'SetFilter', Array('C', Array('123'), TRUE), '%s [<>]');
        $this->sphinx->expectCallCount('SetFilterRange', 4);
        $this->sphinx->expectAt(0, 'SetFilterRange', Array('C', 124, PHP_INT_MAX, FALSE), '%s [>]');
        $this->sphinx->expectAt(1, 'SetFilterRange', Array('C', 123, PHP_INT_MAX, FALSE), '%s [>=]');
        $this->sphinx->expectAt(2, 'SetFilterRange', Array('C', 124, PHP_INT_MAX, TRUE), '%s [<]');
        $this->sphinx->expectAt(3, 'SetFilterRange', Array('C', 123, PHP_INT_MAX, TRUE), '%s [<=]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_number_list()
        {
        $tree = $this->parse_query('{c=123,456,789}{c<>987,654,321}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilter', 2);
        $this->sphinx->expectAt(0, 'SetFilter', Array('C', Array('123','456','789'), FALSE), '%s [=(,)]');
        $this->sphinx->expectAt(1, 'SetFilter', Array('C', Array('987','654','321'), TRUE), '%s [<>(,)]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_datetime_single()
        {
        $tree = $this->parse_query('{e=2009-06-01}{e<>2009-06-01}{e>2009-06-01}{e>=2009-06-01}{e<2009-06-01}{e<=2009-06-01}'); 
        $t = strtotime('2009-06-01');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilter', 2);
        $this->sphinx->expectAt(0, 'SetFilter', Array('E1', Array($t), FALSE), '%s [=]');
        $this->sphinx->expectAt(1, 'SetFilter', Array('E1', Array($t), TRUE), '%s [<>]');
        $this->sphinx->expectCallCount('SetFilterRange', 4);
        $this->sphinx->expectAt(0, 'SetFilterRange', Array('E1', $t + 1, PHP_INT_MAX, FALSE), '%s [>]');
        $this->sphinx->expectAt(1, 'SetFilterRange', Array('E1', $t, PHP_INT_MAX, FALSE), '%s [>=]');
        $this->sphinx->expectAt(2, 'SetFilterRange', Array('E1', $t + 1, PHP_INT_MAX, TRUE), '%s [<]');
        $this->sphinx->expectAt(3, 'SetFilterRange', Array('E1', $t, PHP_INT_MAX, TRUE), '%s [<=]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_datetime_range()
        {
        $tree = $this->parse_query('{e=2009-06-01,2009-06-30}{e=2009-06-01 02,2009-06-30 10}
                {e=2009-06-01 02:30,2009-06-30 10:55}{e=2009-06-01 02:30:15,2009-06-30 10:55:30}'); 
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilterRange', 4);
        $t_min = strtotime('2009-06-01T00:00:00');
        $t_max = strtotime('2009-06-30T23:59:59');
        $this->sphinx->expectAt(0, 'SetFilterRange', Array('E1', $t_min, $t_max, FALSE), '%s [=(,)]');
        $t_min = strtotime('2009-06-01T02:00:00');
        $t_max = strtotime('2009-06-30T10:59:59');
        $this->sphinx->expectAt(1, 'SetFilterRange', Array('E1', $t_min, $t_max, FALSE), '%s [=(,)h]');
        $t_min = strtotime('2009-06-01T02:30:00');
        $t_max = strtotime('2009-06-30T10:55:59');
        $this->sphinx->expectAt(2, 'SetFilterRange', Array('E1', $t_min, $t_max, FALSE), '%s [=(,)hm]');
        $t_min = strtotime('2009-06-01T02:30:15');
        $t_max = strtotime('2009-06-30T10:55:30');
        $this->sphinx->expectAt(3, 'SetFilterRange', Array('E1', $t_min, $t_max, FALSE), '%s [=(,)hms]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_date_single()
        {
        $tree = $this->parse_query('{h=2009-06-01}{h<>2009-06-01}{h>2009-06-01}{h>=2009-06-01}{h<2009-06-01}{h<=2009-06-01}'); 
        $t = strtotime('2009-06-01');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilter', 2);
        $this->sphinx->expectAt(0, 'SetFilter', Array('H1', Array($t), FALSE), '%s [=]');
        $this->sphinx->expectAt(1, 'SetFilter', Array('H1', Array($t), TRUE), '%s [<>]');
        $this->sphinx->expectCallCount('SetFilterRange', 4);
        $this->sphinx->expectAt(0, 'SetFilterRange', Array('H1', $t + 1, PHP_INT_MAX, FALSE), '%s [>]');
        $this->sphinx->expectAt(1, 'SetFilterRange', Array('H1', $t, PHP_INT_MAX, FALSE), '%s [>=]');
        $this->sphinx->expectAt(2, 'SetFilterRange', Array('H1', $t + 1, PHP_INT_MAX, TRUE), '%s [<]');
        $this->sphinx->expectAt(3, 'SetFilterRange', Array('H1', $t, PHP_INT_MAX, TRUE), '%s [<=]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_date_range()
        {
        $tree = $this->parse_query('{e=2009-06-01,2009-06-30}'); 
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectCallCount('SetFilterRange', 1);
        $t_min = strtotime('2009-06-01T00:00:00');
        $t_max = strtotime('2009-06-30T23:59:59');
        $this->sphinx->expectAt(0, 'SetFilterRange', Array('E1', $t_min, $t_max, FALSE), '%s [=(,)]');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_blank_not_supported()
        {
        $tree = $this->parse_query('{f=ignored}');
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNull($r);
        $this->assertNoError();
        }

    function test_AND()
        {
        $tree = $this->parse_query('{a=x} AND {b=y OR z}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x @(B1,B2,B3) y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_AND_is_default_oper()
        {
        $tree = $this->parse_query('{a=x}{b=y OR z}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x @(B1,B2,B3) y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function TODO_test_special_chars()
        {
        $tree = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>"x'y", 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>"x'y \"w' z\"", 'oper'=>'AND'),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x\\'y' AND MATCH(t.b1,t.b2) AGAINST('+x\\'y +\"w\\' z\"' IN BOOLEAN MODE)";
        $this->assertSql($tree, $expect);
        }

    function test_NOT()
        {
        $tree = $this->parse_query('{a=x} NOT {b=y OR z}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x -@(B1,B2,B3) y | z', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_NOT_disallowed_at_start()
        {
        $tree = $this->parse_query('NOT {a=x}');
        $this->sphinx->expectNever('AddQuery');
        $this->sphinx->expectNever('SetMatchMode');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertError();
        }

    function test_sort_all_records()
        {
        $tree = $this->parse_query('{sort=s1}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S1 ASC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_default()
        {
        $this->setup(Array('type'=>'asc', 'fields'=>'t.S'));
        $tree = $this->parse_query('');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S ASC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_none()
        {
        $this->setup(Array('type'=>'asc', 'fields'=>'t.S'));
        $tree = $this->parse_query('{sort=none}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectNever('SetMatchMode');
        $this->sphinx->expectNever('SetSortMode');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_simple()
        {
        $tree = $this->parse_query('{a=x}{sort=s1}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S1 ASC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_desc()
        {
        $tree = $this->parse_query('{a=x}{sort=s2}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S2 DESC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function TODO_test_sort_OR()
        {
        $tree = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "(SELECT *,t.s AS _sort FROM Test t WHERE t.a='x') UNION (SELECT *,t.s AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)) ORDER BY _sort";
        $this->assertSql($tree, $expect, 's1');
        }

    function TODO_test_sort_additional_fields()
        {
        $tree = $this->parse_query('{a=x}{sort=s7}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S DESC,A DESC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_relevance()
        {
        $tree = $this->parse_query('{a=x}{sort=s4}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_RELEVANCE));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }
        
    function test_sort_sphinx_fields()
        {
        $tree = $this->parse_query('{a=x}{sort=s8}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'J1 ASC'));
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertNoError();
        }

    function test_sort_field()
        {
        $tree = $this->parse_query('{a=x}{sort=s1}');
        $this->assertEqual('', $this->builder->sort_field());
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertEqual('S1', $this->builder->sort_field());
        $this->assertNoError();
        }

    function test_sort_field_is_set_to_sphinx_fields()
        {
        $tree = $this->parse_query('{a=x}{sort=s8}');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertEqual('J1', $this->builder->sort_field());
        $this->assertNoError();
        }

    function test_sort_field_is_set_to_rel()
        {
        $tree = $this->parse_query('{a=x}{sort=s4}');
        $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertEqual('rel', $this->builder->sort_field());
        $this->assertNoError();
        }

    function MAYBE_test_sort_relevance_with_weight()
        {
        // relevance sort with weight string added
        $this->setup();
        $tree = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,(MATCH(t.b1,t.b2) AGAINST('y z') + 0.6 * c) AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) ORDER BY _sort DESC";
        $this->assertSql($tree, $expect, 's5');
        }
    
    function test_group_by()
        {
        $group_by = Array(
            'attribute'=>'test_id', 
            'func' => SPH_GROUPBY_ATTR,
            );
        $tree = $this->parse_query('{sort=s1}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectOnce('SetSortMode', Array(SPH_SORT_EXTENDED, 'S1 ASC'));
        $this->sphinx->expectOnce('SetGroupBy', Array('test_id', SPH_GROUPBY_ATTR, '@group DESC'));
        $this->builder->add_query('test-index', $tree, 12, 34, $group_by);
        $this->assertNoError();
        }
    
    function test_group_by_with_sortmode()
        {
        $group_by = Array(
            'attribute'=>'test_id', 
            'func' => SPH_GROUPBY_ATTR,
            'sortmode' => Array(SPH_SORT_ATTR_DESC, 'date'),
            );
        $tree = $this->parse_query('{sort=s1}');
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->expectAt(0, 'SetSortMode', Array(SPH_SORT_EXTENDED, 'S1 ASC'));
        $this->sphinx->expectAt(1, 'SetSortMode', Array(SPH_SORT_ATTR_DESC, 'date'));
        $this->sphinx->expectOnce('SetGroupBy', Array('test_id', SPH_GROUPBY_ATTR, 'S1 ASC'));
        $this->builder->add_query('test-index', $tree, 12, 34, $group_by);
        $this->assertNoError();
        }
    
    function test_group_by_with_sortmode_and_rel()
        {
        $group_by = Array(
            'attribute'=>'test_id', 
            'func' => SPH_GROUPBY_ATTR,
            'sortmode' => Array(SPH_SORT_ATTR_DESC, 'date'),
            );
        $tree = $this->parse_query('{a=x}{sort=s4}');
        $this->sphinx->expectOnce('AddQuery', Array('@A x', 'test-index', '*'));
        $this->sphinx->expectAt(0, 'SetSortMode', Array(SPH_SORT_RELEVANCE));
        $this->sphinx->expectAt(1, 'SetSortMode', Array(SPH_SORT_ATTR_DESC, 'date'));
        $this->sphinx->expectOnce('SetGroupBy', Array('test_id', SPH_GROUPBY_ATTR, '@relevance DESC'));
        $this->builder->add_query('test-index', $tree, 12, 34, $group_by);
        $this->assertNoError();
        }

    function test_error_bad_index()
        {
        $tree = $this->parse_query('{undefined=x}');
        $this->sphinx->expectNever('AddQuery');
        $this->sphinx->expectNever('SetMatchMode');
//        $this->sphinx->expectNever('SetLimits'); ???
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertError();
        $this->assertNull($r);
        }

    function test_error_unknown_type()
        {
        $tree = $this->parse_query('{badtype=x}');
        $this->sphinx->expectNever('AddQuery');
        $this->sphinx->expectNever('SetMatchMode');
//        $this->sphinx->expectNever('SetLimits'); ???
        $r = $this->builder->add_query('test-index', $tree, 12, 34);
        $this->assertError();
        $this->assertNull($r);
        }
    
    function test_add_group_by()
        {
        $this->sphinx->expectOnce('AddQuery', Array('', 'test-index', '*'));
        $this->sphinx->setReturnValue('AddQuery', 'quux');
        $this->sphinx->expectOnce('SetGroupBy', Array('foo', SPH_GROUPBY_ATTR));
        $r = $this->builder->add_group_by('test-index', 'foo');
        $this->assertEqual('quux', $r);
        $this->assertNoError();
        }

    function test_add_group_by_uses_same_query()
        {
        $tree = $this->parse_query('a=foo');
        $this->sphinx->expectCallCount('AddQuery', 2);
        $this->builder->add_query('test-index', $tree, 12, 34);
        // Both calls to AddQuery receive the same query string
        $this->sphinx->expectAt(0, 'AddQuery', Array('@A foo', 'test-index', '*'));
        $this->sphinx->expectAt(1, 'AddQuery', Array('@A foo', 'test-index', '*'));
        $this->sphinx->expectAtLeastOnce('SetMatchMode', Array(SPH_MATCH_EXTENDED2));
        $this->sphinx->setReturnValue('AddQuery', 'quux');
        $r = $this->builder->add_group_by('test-index', 'foo');
        $this->assertEqual('quux', $r);
        }
    
    function test_find_sphinx_config_value()
        {
        // no match
        $tree = $this->parse_query('a=x');
        $this->assertEqual($this->builder->find_sphinx_config_value($tree, 'sphinx_index'), '');
        $tree = $this->parse_query('{a=x}AND{b=y}AND{c=z}');
        $this->assertEqual($this->builder->find_sphinx_config_value($tree, 'sphinx_index'), '');
        // match found
        $tree = $this->parse_query('k=1');
        $this->assertEqual($this->builder->find_sphinx_config_value($tree, 'sphinx_index'), 'new_index');
        $tree = $this->parse_query('{a=x}AND{b=y}AND{c=z}AND{k=1}');
        $this->assertEqual($this->builder->find_sphinx_config_value($tree, 'sphinx_index'), 'new_index');
        }

    function assertNoError()
        {
        $this->assertEqual($this->builder->error_message, '', 'error message');
        }

    function assertError()
        {
        $this->assertNotEqual($this->builder->error_message, '', 'error message');
        }
    }
