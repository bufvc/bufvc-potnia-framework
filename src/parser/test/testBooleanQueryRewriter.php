<?php
// $Id$
// Tests for QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'parser/BooleanQueryRewriter.class.php');

//### TODO: Merge these test cases

class BooleanQueryRewriterTestCase
    extends UnitTestCase
    {
    function test_conversions()
        {
        $tests = Array(
            // Empty query
            '' => '',
            // Single op
            "A" => "A",

            // Two ops
            "A B" => "+A +B",
            "A AND B" => "+A +B",
            "A OR B" => "A B",
            "A NOT B" => "+A -B",

            // Three ops
            "A B C" => "+A +B +C",
            "A AND B AND C" => "+A +B +C",
            "A OR B OR C" => "A B C",
            "A NOT B NOT C" => "+A -B -C",
            "A AND B OR C" => "+A +B C",
            "A OR B AND C" => "A (+B +C)",
            "A OR B NOT C" => "A B -C",
            "A NOT B AND C" => "+A -(+B +C)",
            "A NOT B OR C" => "+A -(B C)",

            // More ops
            "A B C AND D E F" => "+A +B +C +D +E +F",
            "A OR B AND C AND D OR E NOT F" => "A (+B +C +D E -F)",
            "A AND B OR C AND D OR E NOT F AND G AND H" => "+A +B (+C +D E -(+F +G +H))",
            "A OR B OR C AND D OR E NOT F AND G AND H" => "A B (+C +D E -(+F +G +H))",

            // Parentheses
            'x (y OR z)' => '+x +(y z)',
            "(A)" => "(A)",
            "(A B)" => "(+A +B)",
            "A (B C)" => "+A +(+B +C)",

            // Phrases
            '"x y"' => '"x y"',
            '"x y" z' => '+"x y" +z',

            // Interesting cases
            'x NOT (y "z) w")' => '+x -(+y +"z) w")',

            // Degenerate case, perhaps should be error?
            'x AND'=>'+x',

            // Special chars
            "x'y" => "x\\'y",
            "\"x'y\"" => "\"x\\'y\"",

            // Badly formed strings
            "()" => "()",
            "AND" => "3",
            "(" => "()",
            );

        foreach ($tests as $test_str=>$expected)
            {
            $conv = new BooleanQueryRewriter();
            $actual = $conv->convert($test_str);
            $this->assertTrue($conv->error_message == '');
            $this->assertEqual($actual, $expected, '%s [' . $test_str . ']');
            }
        }
    function test_errors()
        {
        $tests = Array(
            '"',
            );
        foreach ($tests as $test_str)
            {
            $conv = new BooleanQueryRewriter();
            $actual = $conv->convert($test_str);
            $this->assertFalse($conv->error_message == '');
            }
        }
    }

class SphinxBooleanQueryRewriterTestCase
    extends UnitTestCase
    {
    function test_conversions()
        {
        $tests = Array(
            // Empty query
            '' => '',
            // Single op
            "A" => "A",

            // Two ops
            "A B" => "A B",
            "A AND B" => "A B",
            "A OR B" => "A | B",
            "A NOT B" => "A -B",

            // Three ops
            "A B C" => "A B C",
            "A AND B AND C" => "A B C",
            "A OR B OR C" => "A | B | C",
            "A NOT B NOT C" => "A -B -C",
            "A AND B OR C" => "A B | C",
            "A OR B AND C" => "A | (B C)",
            "A OR B NOT C" => "A | B -C",
            "A NOT B AND C" => "A -(B C)",
            "A NOT B OR C" => "A -(B | C)",

            // More ops
            "A B C AND D E F" => "A B C D E F",
            "A OR B AND C AND D OR E NOT F" => "A | (B C D | E -F)",
            "A AND B OR C AND D OR E NOT F AND G AND H" => "A B | (C D | E -(F G H))",
            "A OR B OR C AND D OR E NOT F AND G AND H" => "A | B | (C D | E -(F G H))",

            // Parentheses
            'x (y OR z)' => 'x (y | z)',
            "(A)" => "(A)",
            "(A B)" => "(A B)",
            "A (B C)" => "A (B C)",

            // Phrases
            '"x y"' => '"x y"',
            '"x y" z' => '"x y" z',

            // Interesting cases
            'x NOT (y "z) w")' => 'x -(y "z) w")',

            // Degenerate case, perhaps should be error?
            'x AND'=>'x',

            // Special chars
            "x'y" => "x\\'y",
            "\"x'y\"" => "\"x\\'y\"",

            // Badly formed strings
            "()" => "()",
            "AND" => "3",
            "(" => "()",
            );

        foreach ($tests as $test_str=>$expected)
            {
            $conv = new SphinxBooleanQueryRewriter();
            $actual = $conv->convert($test_str);
            $this->assertTrue($conv->error_message == '');
            $this->assertEqual($actual, $expected, '%s [' . $test_str . ']');
            }
        }
    function test_errors()
        {
        $tests = Array(
            '"',
            );
        foreach ($tests as $test_str)
            {
            $conv = new SphinxBooleanQueryRewriter();
            $actual = $conv->convert($test_str);
            $this->assertFalse($conv->error_message == '');
            }
        }
    }
