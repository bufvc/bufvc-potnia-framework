<?php
// $Id$
// Tests for QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'parser/QueryParser.class.php');
require_once($CONF['path_src'] . 'parser/SqlGenerator.class.php');

// Tests the high-level convert() function
class SqlConversionTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $factory = new QueryParserFactory();
        $index_defs = Array(
            'default' => Array('type'=>'fulltext', 'fields'=>'t.title,t.summary,t.misc'),
            'keyword' => Array('type'=>'fulltext', 'fields'=>'kw.word',
                'join'=>'JOIN KeywordTest kt ON kt.test_id=Test.id JOIN Keyword kw ON kw.id=kt.keyword_id',
                'exists'=>'Keyword kw JOIN KeywordTest kt ON kw.id=kt.keyword_id WHERE kt.test_id=Test.id'
                ),
            'sort.title' => Array('type'=>'asc', 'fields'=>'t.title'),
            );
        $this->parser = new SimpleQueryParser();
        $this->generator = new SqlGenerator('Test t', 'id', $index_defs);
        }
    
    function convert($query_string)
        {
        $tree = $this->parser->parse($query_string);
        return $this->generator->convert($tree);
        return $result;
        }

    function test_convert()
        {
        // Search all
        $sql = $this->convert('');
        $this->assertEqual($sql, "SELECT id FROM Test t");

        // Single clause
        $sql = $this->convert('{default=foo bar}');
        $this->assertEqual($sql, "SELECT id FROM Test t WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE)");

        // Clauses joined by AND
        $sql = $this->convert('{default=foo bar}{keyword=baz}');
        $this->assertEqual($sql, "SELECT DISTINCT id FROM Test t " .
                "JOIN KeywordTest kt ON kt.test_id=Test.id JOIN Keyword kw ON kw.id=kt.keyword_id " .
                "WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE) " .
                "AND MATCH(kw.word) AGAINST('baz' IN BOOLEAN MODE)");

        // Clauses joined by NOT use 'exists'
        $sql = $this->convert('{default=foo bar}NOT{keyword=baz}');
        $this->assertEqual($sql, "SELECT id FROM Test t " .
                "WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE) " .
                "AND NOT EXISTS(SELECT * FROM Keyword kw " .
                "JOIN KeywordTest kt ON kw.id=kt.keyword_id WHERE kt.test_id=Test.id " .
                "AND MATCH(kw.word) AGAINST('baz' IN BOOLEAN MODE))");

        // Clauses joined by OR
        $sql = $this->convert('{default=foo bar}OR{keyword=baz}');
        $this->assertEqual($sql,"(SELECT id FROM Test t " .
                "WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE)) " .
                "UNION (SELECT id FROM Test t " .
                "JOIN KeywordTest kt ON kt.test_id=Test.id JOIN Keyword kw ON kw.id=kt.keyword_id " .
                "WHERE MATCH(kw.word) AGAINST('baz' IN BOOLEAN MODE))");

        // Single clause, sorted
        $sql = $this->convert('{default=foo bar}{sort=title}');
        $this->assertEqual($sql, "SELECT id,t.title AS _sort FROM Test t WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE) ORDER BY _sort");

        // OR clause, sorted, ORDER BY is at same level as UNION
        $sql = $this->convert('{default=foo bar}OR{keyword=baz}');
        $this->assertEqual($sql,"(SELECT id FROM Test t " .
                "WHERE MATCH(t.title,t.summary,t.misc) AGAINST('+foo +bar' IN BOOLEAN MODE)) " .
                "UNION (SELECT id FROM Test t " .
                "JOIN KeywordTest kt ON kt.test_id=Test.id JOIN Keyword kw ON kw.id=kt.keyword_id " .
                "WHERE MATCH(kw.word) AGAINST('baz' IN BOOLEAN MODE))");

        // Search all, sorted
        $sql = $this->convert('{sort=title}');
        $this->assertEqual($sql, "SELECT id,t.title AS _sort FROM Test t ORDER BY _sort");
        }

    function test_error()
        {
        // Error clear by default
        $this->assertEqual($this->generator->error_message, '');

        $tests = Array(
            QP_ERROR_CLAUSE => '{unknown=foo}',
            QP_ERROR_CLAUSE => '{default="a}',
            );
        foreach ($tests as $code=>$test)
            {
            $this->convert($test);
            // $this->assertEqual($this->parser->error_code, $code);
            $this->assertTrue($this->generator->error_message != '');
            }
        // Successful call clears error
        $sql = $this->convert('{default=foo bar}');
        $this->assertEqual($this->generator->error_message, '');
        }
    }

// Exercises the to_sql() function which expects a modified tree
class SqlGeneratorTestCase
    extends UnitTestCase
    {
    function setup($sort_default=NULL)
        {
        $index_defs = Array(
            'a' => Array('type'=>'string', 'fields'=>'t.a'),
            'b' => Array('type'=>'fulltext', 'fields'=>'t.b1,t.b2'),
            'c' => Array('type'=>'number', 'fields'=>'t.c'),
            'd' => Array('type'=>'fulltext', 'fields'=>'t.d1'),
            'e' => Array('type'=>'datetime', 'fields'=>'t.e1'),
            'f' => Array('type'=>'blank', 'fields'=>'t.f1'),
            'g' => Array('select'=>'t.a,t.b', 'type'=>'string', 'fields'=>'t.a'),
            'h' => Array('type'=>'date', 'fields'=>'t.h1'),
            'i' => Array('type'=>'replace_join', 'fields'=>'', 'join'=>Array('JOIN y ON y.id=t.id')),
            'badtype' => Array('type'=>'undefined_type', 'fields'=>'unused'),
            'join1' => Array('type'=>'string', 'fields'=>'j.k', 'join'=>'JOIN J j ON j.x=t.a', 'exists'=>'ignored'),
            'join2' => Array('type'=>'string', 'fields'=>'j.m', 'join'=>'JOIN J j ON j.x=t.a'),
            'join3' => Array('type'=>'string', 'fields'=>'j2.n',
                        'join'=>Array('join'=>'JOIN J j ON j.x=t.a', 'LEFT JOIN J2 j2 ON j2.x=t.b')),
            'exists1' => Array('type'=>'string', 'fields'=>'E.k', 'join'=>'ignored',
                'exists' => 'E JOIN L.e ON E.l WHERE L.t=T.l'),
            'exists2' => Array('type'=>'string', 'fields'=>'E.k', // No 'join'
                'exists' => 'E JOIN L.e ON E.l WHERE L.t=T.l'),
            'sort.s1' => Array('type'=>'asc', 'fields'=>'t.s'),
            'sort.s2' => Array('type'=>'desc', 'fields'=>'t.s'),
            'sort.s3' => Array('type'=>'desc', 'fields'=>'j.s', 'join'=>'JOIN J j ON j.x=t.a'),
            'sort.s4' => Array('type'=>'rel'),
            'sort.s5' => Array('type'=>'rel', 'weight'=>'0.6 * c'),
            'sort.s6' => Array('type'=>'asc', 'fields'=>'t.s', 'additional_sort'=>'t.a'),
            'sort.s7' => Array('type'=>'desc', 'fields'=>'t.s', 'additional_sort'=>'t.a DESC'),
            );
        if ($sort_default != '')
            $index_defs['sort.default'] = $sort_default;
        $this->generator = new SqlGenerator('Test t', '*', $index_defs);
        }

    function test_all_records()
        {
        $test = NULL;
        $expect = "SELECT * FROM Test t";
        $this->assertSql($test, $expect);
        }
        
    function test_select_fields()
        {
        $this->generator = new SqlGenerator('Test t', 'a,b', $this->generator->index_defs);
        $test = NULL;
        $expect = "SELECT a,b FROM Test t";
        $this->assertSql($test, $expect);
        }
        
    function test_additional_select_fields()
        {
        // the select defined in the index def overrides the select fields that the generator
        //  is created with
        $this->generator = new SqlGenerator('Test t', 'c,d', $this->generator->index_defs);
        $test = Array(
            Array(
                Array('index'=>'g', 'relation'=>'=', 'subject'=>'x'),
                ),
            );
        $expect = "SELECT c,d,t.a,t.b FROM Test t WHERE t.a='x'";
        $this->assertSql($test, $expect);
        }

    function test_string()
        {
        $relations = Array('=', '<', '>', '>=', '<=', '<>');
        foreach ($relations as $relation)
            {
            $test = Array(
                Array(
                    Array('index'=>'a', 'relation'=>$relation, 'subject'=>'x', 'oper'=>''),
                    ),
                );
            $expect = "SELECT * FROM Test t WHERE t.a{$relation}'x'";
            $this->assertSql($test, $expect);
            }
        }

    function test_string_with_wildcard()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x*', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a LIKE 'x%'";
        $this->assertSql($test, $expect);
        }

    function test_fulltext()
        {
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)";
        $this->assertSql($test, $expect);
        }

    function test_number()
        {
        $relations = Array('=', '<', '>', '>=', '<=', '<>');
        foreach ($relations as $relation)
            {
            $test = Array(
                Array(
                    Array('index'=>'c', 'relation'=>$relation, 'subject'=>'123', 'oper'=>''),
                    ),
                );
            $expect = "SELECT * FROM Test t WHERE t.c{$relation}123";
            $this->assertSql($test, $expect);
            }
        }

    function test_number_list()
        {
        $test = Array(
            Array(
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'123,456,789', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.c IN (123,456,789)";
        $this->assertSql($test, $expect);
        }

    function test_datetime_single()
        {
        $test = Array(
            Array(
                Array('index'=>'e', 'relation'=>'=', 'subject'=>'2009-06-01', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.e1>='2009-06-01 00:00:00'";
        $this->assertSql($test, $expect);
        }

    function test_datetime_range()
        {
        $test = Array(
            Array(
                Array('index'=>'e', 'relation'=>'=', 'subject'=>'2009-06-01,2009-06-30', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.e1 BETWEEN '2009-06-01 00:00:00' AND '2009-06-30 23:59:59'";
        $this->assertSql($test, $expect);
        }

    function test_datetime_range_with_hour()
        {
        $test = Array(
            Array(
                Array('index'=>'e', 'relation'=>'=', 'subject'=>'2009-06-01 02,2009-06-30 10', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.e1 BETWEEN '2009-06-01 02:00:00' AND '2009-06-30 10:59:59'";
        $this->assertSql($test, $expect);
        }

    function test_datetime_range_with_hour_min()
        {
        $test = Array(
            Array(
                Array('index'=>'e', 'relation'=>'=', 'subject'=>'2009-06-01 02:30,2009-06-30 10:55', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.e1 BETWEEN '2009-06-01 02:30:00' AND '2009-06-30 10:55:59'";
        $this->assertSql($test, $expect);
        }

    function test_datetime_range_with_hour_min_sec()
        {
        $test = Array(
            Array(
                Array('index'=>'e', 'relation'=>'=', 'subject'=>'2009-06-01 02:30:15,2009-06-30 10:59:30', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.e1 BETWEEN '2009-06-01 02:30:15' AND '2009-06-30 10:59:30'";
        $this->assertSql($test, $expect);
        }
    
    function test_date_single()
        {
        $test = Array(
            Array(
                Array('index'=>'h', 'relation'=>'=', 'subject'=>'2009-06-01', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.h1>='2009-06-01'";
        $this->assertSql($test, $expect);
        }
        
    function test_date_range()
        {
        $test = Array(
            Array(
                Array('index'=>'h', 'relation'=>'=', 'subject'=>'2009-06-01,2009-06-30', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.h1 BETWEEN '2009-06-01' AND '2009-06-30'";
        $this->assertSql($test, $expect);
        }
    
    function test_blank()
        {
        $relations = Array('=', '<>');
        foreach ($relations as $relation)
            {
            $test = Array(
                Array(
                    Array('index'=>'f', 'relation'=>$relation, 'subject'=>'junk', 'oper'=>''),
                    ),
                );
            $expect = "SELECT * FROM Test t WHERE t.f1{$relation}''";
            $this->assertSql($test, $expect);
            }
        }
    
    // if subject is 'null' then compare against null instead of empty string
    function test_blank_with_null()
        {
        // IS NULL
        $test = Array(
            Array(
                Array('index'=>'f', 'relation'=>'=', 'subject'=>'null', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.f1 IS NULL";
        $this->assertSql($test, $expect);
        // IS NOT NULL
        $test = Array(
            Array(
                Array('index'=>'f', 'relation'=>'<>', 'subject'=>'null', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.f1 IS NOT NULL";
        $this->assertSql($test, $expect);
        }

    function test_AND()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>'AND'),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x' AND MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)";
        $this->assertSql($test, $expect);
        }

    function test_AND_is_default_oper()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x' AND MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)";
        $this->assertSql($test, $expect);
        }

    function test_special_chars()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>"x'y", 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>"x'y \"w' z\"", 'oper'=>'AND'),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x\\'y' AND MATCH(t.b1,t.b2) AGAINST('+x\\'y +\"w\\' z\"' IN BOOLEAN MODE)";
        $this->assertSql($test, $expect);
        }

    function test_NOT()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>'NOT'),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x' AND NOT MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)";
        $this->assertSql($test, $expect);
        }

    // NOT uses exists config if it is present
    function test_NOT_with_exists()
        {
        //
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'exists1', 'relation'=>'=', 'subject'=>'y', 'oper'=>'NOT'),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x' AND NOT EXISTS(SELECT * FROM E JOIN L.e ON E.l WHERE L.t=T.l AND E.k='y')";
        $this->assertSql($test, $expect);
        }

    // NOT uses exists config if it is present
    function test_AND_with_exists()
        {
        //
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'exists2', 'relation'=>'=', 'subject'=>'y', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x' AND EXISTS(SELECT * FROM E JOIN L.e ON E.l WHERE L.t=T.l AND E.k='y')";
        $this->assertSql($test, $expect);
        }

    function test_OR()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "(SELECT * FROM Test t WHERE t.a='x') UNION (SELECT * FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE))";
        $this->assertSql($test, $expect);
        }

    function test_single_join()
        {
        $test = Array(
            Array(
                Array('index'=>'join1', 'relation'=>'=', 'subject'=>'subj1', 'oper'=>''),
                ),
            );
        // Join also has DISTINCT added
        $expect = "SELECT DISTINCT * FROM Test t JOIN J j ON j.x=t.a WHERE j.k='subj1'";
        $this->assertSql($test, $expect);
        }

    function test_identical_joins_dont_repeat()
        {
        $test = Array(
            Array(
                Array('index'=>'join1', 'relation'=>'=', 'subject'=>'subj1', 'oper'=>''),
                Array('index'=>'join2', 'relation'=>'=', 'subject'=>'subj2', 'oper'=>''),
                ),
            );
        // Join also has DISTINCT added
        $expect = "SELECT DISTINCT * FROM Test t JOIN J j ON j.x=t.a WHERE j.k='subj1' AND j.m='subj2'";
        $this->assertSql($test, $expect);
        }

    function test_multiple_join()
        {
        // Also tests array of join statements in definition
        $test = Array(
            Array(
                Array('index'=>'join1', 'relation'=>'=', 'subject'=>'subj1', 'oper'=>''),
                Array('index'=>'join3', 'relation'=>'=', 'subject'=>'subj2', 'oper'=>''),
                ),
            );
        // Join also has DISTINCT added
        $expect = "SELECT DISTINCT * FROM Test t JOIN J j ON j.x=t.a LEFT JOIN J2 j2 ON j2.x=t.b WHERE j.k='subj1' AND j2.n='subj2'";
        $this->assertSql($test, $expect);
        }

    function test_global_join()
        {
        // Add a global join to the index defs
        $index_defs = Array(
            'join' => Array('JOIN u ON u.id=t.id'),
            'c' => Array('type'=>'number', 'fields'=>'t.c'),
            );
        $this->generator = new SqlGenerator('Test t', '*', $index_defs);
        $test = Array(
            Array(
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'123', 'oper'=>''),
                ),
            );
        // Global JOIN does not have DISTINCT added
        $expect = "SELECT * FROM Test t JOIN u ON u.id=t.id WHERE t.c=123";
        $this->assertSql($test, $expect);
        // query with no clauses
        $expect = "SELECT * FROM Test t JOIN u ON u.id=t.id";
        $this->assertSql(NULL, $expect);
        }
    
    function test_replace_global_join()
        {
        // Add a global join to the index defs
        $index_defs = Array(
            'join' => Array('JOIN u ON u.id=t.id'),
            'c' => Array('type'=>'number', 'fields'=>'t.c'),
            'i' => Array('type'=>'replace_join', 'fields'=>'', 'join'=>Array('JOIN y ON y.id=t.id')),
            );
        $this->generator = new SqlGenerator('Test t', '*', $index_defs);
        $test = Array(
            Array(
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'123', 'oper'=>''),
                Array('index'=>'i', 'relation'=>'=', 'subject'=>'1', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t JOIN y ON y.id=t.id WHERE t.c=123";
        $this->assertSql($test, $expect);
        // query with no clauses
        $test = Array(
            Array(
                Array('index'=>'i', 'relation'=>'=', 'subject'=>'1', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t JOIN y ON y.id=t.id";
        $this->assertSql($test, $expect);
        }
    
    function test_replace_join_with_no_global()
        {
        $test = Array(
            Array(
                Array('index'=>'c', 'relation'=>'=', 'subject'=>'123', 'oper'=>''),
                Array('index'=>'i', 'relation'=>'=', 'subject'=>'1', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t JOIN y ON y.id=t.id WHERE t.c=123";
        $this->assertSql($test, $expect);
        // query with no clauses
        $test = Array(
            Array(
                Array('index'=>'i', 'relation'=>'=', 'subject'=>'1', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t JOIN y ON y.id=t.id";
        $this->assertSql($test, $expect);
        }
    
    function test_replace_join_comes_first()
        {
        $test = Array(
            Array(
                Array('index'=>'join1', 'relation'=>'=', 'subject'=>'subj1', 'oper'=>''),
                Array('index'=>'join3', 'relation'=>'=', 'subject'=>'subj2', 'oper'=>''),
                Array('index'=>'i', 'relation'=>'=', 'subject'=>'1', 'oper'=>''),
                ),
            );
        $expect = "SELECT DISTINCT * FROM Test t JOIN y ON y.id=t.id JOIN J j ON j.x=t.a LEFT JOIN J2 j2 ON j2.x=t.b WHERE j.k='subj1' AND j2.n='subj2'";
        $this->assertSql($test, $expect);
        }

    function test_sort_all_records()
        {
        $test = NULL;
        $expect = "SELECT *,t.s AS _sort FROM Test t ORDER BY _sort";
        $this->assertSql($test, $expect, 's1');
        }

    function test_sort_default()
        {
        $this->setup(Array('type'=>'asc', 'fields'=>'t.s'));
        $test = NULL;
        $expect = "SELECT *,t.s AS _sort FROM Test t ORDER BY _sort";
        $this->assertSql($test, $expect);
        }

    function test_sort_none()
        {
        $this->setup(Array('type'=>'asc', 'fields'=>'t.s'));
        $test = NULL;
        $expect = "SELECT * FROM Test t";
        $this->assertSql($test, $expect, 'none');
        }

    function test_sort_simple()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,t.s AS _sort FROM Test t WHERE t.a='x' ORDER BY _sort";
        $this->assertSql($test, $expect, 's1');
        }

    function test_sort_desc()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,t.s AS _sort FROM Test t WHERE t.a='x' ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's2');
        }

    function test_sort_OR()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "(SELECT *,t.s AS _sort FROM Test t WHERE t.a='x') UNION (SELECT *,t.s AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE)) ORDER BY _sort";
        $this->assertSql($test, $expect, 's1');
        }

    function test_sort_join()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        // If sort has a JOIN clause, DISTINCT is added
        $expect = "SELECT DISTINCT *,j.s AS _sort FROM Test t JOIN J j ON j.x=t.a WHERE t.a='x' ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's3');
        }
    
    function test_sort_additional_fields()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,t.s AS _sort FROM Test t WHERE t.a='x' ORDER BY _sort, t.a";
        $this->assertSql($test, $expect, 's6');
        // DESC
        $expect = "SELECT *,t.s AS _sort FROM Test t WHERE t.a='x' ORDER BY _sort DESC, t.a DESC";
        $this->assertSql($test, $expect, 's7');
        }

    function test_sort_relevance_fulltext()
        {
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,MATCH(t.b1,t.b2) AGAINST('y z') AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_no_fulltext_or_default()
        {
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $expect = "SELECT * FROM Test t WHERE t.a='x'";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_no_fulltext_use_default_no_secondary()
        {
        $this->setup(Array('type'=>'asc', 'fields'=>'t.s'));
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,t.s AS _sort FROM Test t WHERE t.a='x' ORDER BY _sort";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_multiple_searches()
        {
        // multiple searches, relevance generated on fulltext search
        $this->setup();
        $test = Array(
            Array(
                Array('index'=>'a', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y\'y z', 'oper'=>'AND'),
                ),
            );
        $expect = "SELECT *,MATCH(t.b1,t.b2) AGAINST('y\'y z') AS _sort FROM Test t WHERE t.a='x' AND MATCH(t.b1,t.b2) AGAINST('+y\'y +z' IN BOOLEAN MODE) ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_multiple_fulltext_searches()
        {
        // multiple fulltext searches
        // relevance is generated on first search
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                Array('index'=>'d', 'relation'=>'=', 'subject'=>'w x', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,MATCH(t.b1,t.b2) AGAINST('y z') AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) AND MATCH(t.d1) AGAINST('+w +x' IN BOOLEAN MODE) ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_fulltext_default_sort_is_secondary()
        {
        // fulltext search with default sort - default sort is used as secondary sort
        $this->setup(Array('type'=>'asc', 'fields'=>'t.a'));
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,MATCH(t.b1,t.b2) AGAINST('y z') AS _sort,t.a AS _sort2 FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) ORDER BY _sort DESC, _sort2";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_fulltext_default_sort_is_secondary_desc()
        {
        // fulltext search with default sort - default sort is used as secondary sort (DESC)
        $this->setup(Array('type'=>'desc', 'fields'=>'t.a'));
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,MATCH(t.b1,t.b2) AGAINST('y z') AS _sort,t.a AS _sort2 FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) ORDER BY _sort DESC, _sort2 DESC";
        $this->assertSql($test, $expect, 's4');
        }
        
    function test_sort_relevance_with_weight()
        {
        // relevance sort with weight string added
        $this->setup();
        $test = Array(
            Array(
                Array('index'=>'b', 'relation'=>'=', 'subject'=>'y z', 'oper'=>''),
                ),
            );
        $expect = "SELECT *,(MATCH(t.b1,t.b2) AGAINST('y z') + 0.6 * c) AS _sort FROM Test t WHERE MATCH(t.b1,t.b2) AGAINST('+y +z' IN BOOLEAN MODE) ORDER BY _sort DESC";
        $this->assertSql($test, $expect, 's5');
        }

    function test_sort_error()
        {
        $test = NULL;
        $actual = $this->generator->to_sql($test, 'undefinedsort');
        $this->assertError();
        $this->assertNull($actual);
        }

    function test_error_no_input()
        {
        $test = Array();
        $actual = $this->generator->to_sql($test);
        $this->assertError();
        $this->assertNull($actual);
        $test = Array(Array());
        $actual = $this->generator->to_sql($test);
        $this->assertError();
        $this->assertNull($actual);
        }

    function test_error_bad_index()
        {
        $test = Array(
            Array(
                Array('index'=>'undefined', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $actual = $this->generator->to_sql($test);
        $this->assertError();
        $this->assertNull($actual);
        }

    function test_error_unknown_type()
        {
        $test = Array(
            Array(
                Array('index'=>'badtype', 'relation'=>'=', 'subject'=>'x', 'oper'=>''),
                ),
            );
        $actual = $this->generator->to_sql($test);
        $this->assertError();
        $this->assertNull($actual);
        }
    
    function assertSql($test_clauses, $expect, $sort=NULL)
        {
        $actual = $this->generator->to_sql($test_clauses, $sort);
        $this->assertNoError();
        $this->assertEqual($expect, $actual, 'Sql generation: %s');
        }

    function assertNoError()
        {
        $this->assertEqual($this->generator->error_message, '', 'error message');
        }

    function assertError()
        {
        $this->assertNotEqual($this->generator->error_message, '', 'error message');
        }
    }
