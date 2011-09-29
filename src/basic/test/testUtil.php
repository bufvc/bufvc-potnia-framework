<?php
// $Id$
// Test utilities
// James Fryer, 12 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class TestGetObjectId
	{ function TestGetObjectId($fields=NULL) {$this->id=1;} }
class TestGetObjectIdBad
	{ function TestGetObjectIdBad() {} }

class UtilTestCase
    extends UnitTestCase
    {
    function test_expand_urls()
        {
        $tests = Array(
            '' => '',
            'foo' => 'foo',
            'http://example.com' => '<a href="http://example.com">http://example.com</a>',
            "http://1\nhttp://2" => "<a href=\"http://1\">http://1</a>\n<a href=\"http://2\">http://2</a>",
            'http://example.com/foo/bar/' => '<a href="http://example.com/foo/bar/">http://example.com/foo/bar/</a>',
            'www.example.com' => '<a href="http://www.example.com">www.example.com</a>',
            "www.1\nwww.2" => "<a href=\"http://www.1\">www.1</a>\n<a href=\"http://www.2\">www.2</a>",
            'www.1; www.2' => '<a href="http://www.1">www.1</a>; <a href="http://www.2">www.2</a>',
            'http://www.abc.ac.uk/def' => '<a href="http://www.abc.ac.uk/def">http://www.abc.ac.uk/def</a>',
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual(expand_urls($test), $expected, $test . ': %s');
        }

    function test_make_search_links()
        {
        // Defaults
        $this->assertTrue(is_array(make_search_links('', 'x:%s')));
        $this->assertEqual(make_search_links('', 'x:%s'), Array(), 'empty: %s');

        // Scalar
        $this->assertEqual(make_search_links('foo', 'x:%s'), Array(html_link('x:foo', 'foo')), 'scalar: %s');

        // Simple array
        $this->assertEqual(make_search_links(Array('foo', 'bar'), 'x:%s'),
                Array(html_link('x:foo', 'foo'), html_link('x:bar', 'bar')),
                'simple array: %s');
        $this->assertEqual(make_search_links(Array(), 'x:%s'), Array(), 'empty array: %s');

        // 2D array
        $test = Array(
            Array('key'=>'F', 'title'=>'foo'),
            Array('key'=>'B', 'title'=>'bar'),
            );
        $expect = Array(
            html_link('x:F', 'foo'),
            html_link('x:B', 'bar'),
            );
        $this->assertEqual(make_search_links($test, 'x:%s'), $expect, '2D array: %s');

        // 2D with override
        $test = Array(
            Array('a'=>'F', 'b'=>'foo'),
            Array('a'=>'B', 'b'=>'bar'),
            );
        $this->assertEqual(make_search_links($test, 'x:%s', 'a', 'b'), $expect, '2D array with different keys: %s');

        // URLs are escaped
        $this->assertEqual(make_search_links('foo bar', 'x:%s'), Array(html_link('x:foo+bar', 'foo bar')), 'escaping: %s');

        // Missing title defaults to key
        $this->assertEqual(make_search_links(Array('key'=>'foo'), 'x:%s'), Array(html_link('x:foo', 'foo')), 'missing title: %s');
        }

    function test_numericentities()
        {
        $raw = 'AB-' . chr(163) . '-cd';
        $cooked = 'AB-&#163;-cd';
        $this->assertTrue(htmlnumericentities($raw) == $cooked);
        }

    function test_hex2bin()
        {
        $this->assertTrue(strtoupper(hex2bin('61626364')) == 'ABCD');
        }

    function test_pluralise()
        {
	  	$this->assertTrue(pluralise(0, 'record') == 'records');
	  	$this->assertTrue(pluralise(1, 'record') == 'record');
	  	$this->assertTrue(pluralise(2, 'record') == 'records');
	  	$this->assertTrue(pluralise(0, 'fish', '') == 'fish');
	  	$this->assertTrue(pluralise(1, 'fish', '') == 'fish');
	  	$this->assertTrue(pluralise(2, 'fish', '') == 'fish');
	  	$this->assertTrue(pluralise(0, 'dish', 'es') == 'dishes');
	  	$this->assertTrue(pluralise(1, 'dish', 'es') == 'dish');
	  	$this->assertTrue(pluralise(2, 'dish', 'es') == 'dishes');
	  	$this->assertTrue(pluralise(0, 'radi', 'i', 'us') == 'radii');
	  	$this->assertTrue(pluralise(1, 'radi', 'i', 'us') == 'radius');
	  	$this->assertTrue(pluralise(2, 'radi', 'i', 'us') == 'radii');
	  	$this->assertTrue(pluralise(0, 'm', 'ice', 'ouse') == 'mice');
	  	$this->assertTrue(pluralise(1, 'm', 'ice', 'ouse') == 'mouse');
	  	$this->assertTrue(pluralise(2, 'm', 'ice', 'ouse') == 'mice');
		}

	function test_get_id_from_object()
		{
		$obj = new TestGetObjectId();
		$arr = Array('id' => 1);
		$num = 1;
		$this->assertTrue(get_id_from_object($obj) == 1);
		$this->assertTrue(get_id_from_object($arr) == 1);
		$this->assertTrue(get_id_from_object($num) == 1);
		$obj = new TestGetObjectIdBad();
		$arr = Array();
		$this->assertTrue(get_id_from_object($obj) == 0);
		$this->assertTrue(get_id_from_object($arr) == 0);
		}

	function test_interpolate()
		{
		$a = Array('x'=>'test', 'y'=>"foo'bar", 'z'=>'jan";fu');
		$this->assertTrue(interpolate('', $a) == '');
		$this->assertTrue(interpolate('x', $a) == 'x');
		$this->assertTrue(interpolate('$x', $a) == $a['x']);
		$this->assertTrue(interpolate('$y', $a) == $a['y']);
        $this->assertTrue(interpolate('$z', $a) == $a['z']);
        $this->assertTrue(interpolate('"$z', $a) == '"' . $a['z']);
		}

	function test_replace_ext()
		{
		$this->assertTrue(replace_ext('foo') == 'foo');
		$this->assertTrue(replace_ext('foo.') == 'foo');
		$this->assertTrue(replace_ext('foo', 'bar') == 'foo.bar');
		$this->assertTrue(replace_ext('foo.bar') == 'foo');
		$this->assertTrue(replace_ext('foo.bar', 'JAN') == 'foo.JAN');
		$this->assertTrue(replace_ext('sna.foo.bar') == 'sna.foo');
		$this->assertTrue(replace_ext('sna.foo.bar', 'JAN') == 'sna.foo.JAN');
		$this->assertTrue(replace_ext('sna.foo/bar') == 'sna.foo/bar');
		$this->assertTrue(replace_ext('sna.foo/bar', 'baz') == 'sna.foo/bar.baz');
		}

	function test_make_unique_id()
		{
		$seen = Array();
		for ($i = 100000; $i < 100010; $i++)
			{
			$s = make_unique_id($i);
			$this->assertTrue(strlen($s) == 10);
			$this->assertTrue(!isset($seen[$s]));
			$seen[$s] = 1;
			}
		}

	function test_strip_control_codes()
		{
        $this->assertTrue(strip_control_codes('') == '');
        $this->assertTrue(strip_control_codes('abc') == 'abc');
        $this->assertTrue(strip_control_codes("\0A") == 'A');
        $this->assertTrue(strip_control_codes("\0A\001B\n") == 'AB');
        $this->assertTrue(strip_control_codes("\0X\0Y\0Z") == 'XYZ');
        $this->assertTrue(strip_control_codes("\0\001\0\002\010ABC") == 'ABC');
		}

	function test_array_to_assoc()
		{
		$this->assertTrue(array_to_assoc('abc') == NULL);
		$this->assertTrue(array_compare(array_to_assoc(Array()), Array()));

        // Default operation
		$a = Array(
			Array('foo', 'jan', 'xx'),
			Array('bar', 'xx', 'xx'),
			Array('baz', 'yum', 'xx'),
			);
		$expected = Array(
			'foo' => Array('jan', 'xx'),
			'bar' => Array('xx', 'xx'),
			'baz' => Array('yum', 'xx'),
			);
		$actual = array_to_assoc($a);
		$this->assertTrue(array_compare($actual, $expected));

        // Using a numeric key
        $a = Array(
            Array('foo', 'jan', 'xx'),
            Array('bar', 'xx', 'xx'),
            Array('baz', 'yum', 'xx'),
            );
        $expected = Array(
            'jan' => Array('foo', 'xx'),
            'xx' => Array('bar', 'xx'),
            'yum' => Array('baz', 'xx'),
            );
        $actual = array_to_assoc($a, 1);
        $this->assertTrue(array_compare($actual, $expected));

        // Using assoc array
        $a = Array(
            Array('name'=>'foo', 'id'=>'jan', 'title'=>'xx'),
            Array('name'=>'bar', 'id'=>'xx', 'title'=>'xx'),
            Array('name'=>'baz', 'id'=>'yum', 'title'=>'xx'),
            );
        $expected = Array(
            'jan' => Array('name'=>'foo', 'title'=>'xx'),
            'xx' => Array('name'=>'bar', 'title'=>'xx'),
            'yum' => Array('name'=>'baz', 'title'=>'xx'),
            );
        $actual = array_to_assoc($a, 'id');
        $this->assertTrue(array_compare($actual, $expected));
    }
    
    function test_array_is_assoc()
        {
        $this->assertFalse( array_is_assoc(NULL) );
        $this->assertFalse( array_is_assoc("im_an_associative_array") );
        
        // an empty array could still be associative ?
        $this->assertTrue( array_is_assoc(Array()) );
        $this->assertFalse( array_is_assoc(Array(1)) );
        $this->assertFalse( array_is_assoc(Array(1,2,3)) );
        
        $this->assertTrue( array_is_assoc(Array('a'=>'b')) );
        $this->assertFalse( array_is_assoc(Array( 0 => 1, 1 => 2)) );
        $this->assertFalse( array_is_assoc(Array( 0 => 1, 1 => 2)) );
        $this->assertFalse( array_is_assoc(Array( '0' => 1, '1' => 2)) );
        // out of order indices cause associativeness, even when beginning from 0
        $this->assertTrue( array_is_assoc(Array( 0 => 1, 2 => 1)) );
        $this->assertTrue( array_is_assoc(Array( 1 => 1, 2 => 2)) );
        }

    function test_array_compare()
        {
        $this->assertTrue(array_compare(Array(), Array()));
        $this->assertTrue(array_compare(Array(1,2,3), Array(1,2,3)));

        $this->assertFalse(array_compare(Array(1,2,3), Array(1,2)));
        $this->assertFalse(array_compare(Array(1,2,3), Array(1,2,4)));

        // test NULL values
        $this->assertTrue(array_compare(Array(1,2,NULL), Array(1,2,NULL)));
        $this->assertTrue(array_compare(Array(1,2,'3'=>NULL), Array(1,2,'3'=>NULL)));
        $this->assertFalse(array_compare(Array(1,2,'3'=>NULL), Array(1,2,'3'=>3)));
        $this->assertFalse(array_compare(Array(1,2,'3'=>NULL), Array(1,2,'4'=>NULL)));

        // test NULL vs. Empty values
        $this->assertTrue(array_compare(Array(1,2,NULL), Array(1,2,'')));
        $this->assertFalse(array_compare(Array(1,2,NULL), Array(1,2,' ')));
        }

    function test_array_knit()
        {
        // Test with empty arrays/NULL. Note if the second array is NULL
        //	the first array should be returned unchanged
        $this->assertTrue(array_knit(Array(2=>'fb'), NULL) === Array(2=>'fb'));
        $this->assertTrue(array_knit(NULL, Array()) == NULL);
        $this->assertTrue(array_knit(Array(), Array()) === Array());
        $a1 = Array(
            1=>Array('foo'),
            2=>'bar',
            3=>Array('baz'),
            5=>Array('sna'),
            );
        $a2 = Array(
            1=>Array('abc'),
            2=>Array('def'),
            4=>Array('ghi'),
            5=>'jkl',
            );

        $expected = Array(
            1=>Array('foo', 'abc', 'xx'),
            2=>Array('bar', 'def', 'xx'),
            3=>Array('baz', 'xx', 'xx'),
            5=>Array('sna', 'jkl', 'xx'),
            // Array('xx', 'ghi', 'xx'),
            );
        $actual = array_knit($a1, $a2, 2, 'xx');
        $this->assertTrue(array_compare($actual, $expected));
        }

    function test_array_translate()
        {
        $config = Array('A'=>'X', 'B'=>'X');

        // Empty config always returns the array
        $this->assertIdentical(array_translate(Array(), Array()), Array());
        $this->assertIdentical(array_translate(Array('A', 'B', 'C'), Array()), Array());

        // Empty array always returns itself
        $this->assertIdentical(array_translate(Array(), $config), Array());

        // Items in config are converted
        $this->assertIdentical(array_translate(Array('A'), $config), Array('X'));
        $this->assertIdentical(array_translate(Array('A', 'B'), $config), Array('X'));

        // Items not in config are not converted
        $this->assertIdentical(array_translate(Array('A', 'B', 'C'), $config), Array('X', 'C'));

        // Order is preserved
        $this->assertIdentical(array_translate(Array('C', 'A', 'B'), $config), Array('C', 'X'));
        $this->assertIdentical(array_translate(Array('B', 'C', 'A', ), $config), Array('X', 'C'));

        // Duplicates are removed
        $this->assertIdentical(array_translate(Array('A', 'C', 'B', 'C', 'A'), $config), Array('X', 'C'));

        // The threshold must be reached
        $this->assertIdentical(array_translate(Array('A'), $config, 2), Array('A'));
        $this->assertIdentical(array_translate(Array('A', 'B'), $config, 2), Array('X'));
        $this->assertIdentical(array_translate(Array('A', 'C'), $config, 2), Array('A', 'C'));
        $this->assertIdentical(array_translate(Array('A', 'B', 'C'), $config, 2), Array('X', 'C'));
        $this->assertIdentical(array_translate(Array('A', 'C', 'B'), $config, 2), Array('X', 'C'));
        $this->assertIdentical(array_translate(Array('A', 'B', 'C'), $config, 3), Array('A', 'B', 'C'));
        }

    function test_iso_date_to_string()
        {
        $this->assertEqual(iso_date_to_string(''), '');
        $this->assertEqual(iso_date_to_string('2001-02'), 'Feb 2001');
        $this->assertEqual(iso_date_to_string('2002-03-04'), '4 Mar 2002');
        $this->assertEqual(iso_date_to_string('2004-00'), '2004');
        $this->assertEqual(iso_date_to_string('2008-00-00'), '2008');
        $this->assertEqual(iso_date_to_string('2005-05-00'), 'May 2005');
        //### TODO: Pass format parameters iso_date_to_string($iso, $full_fmt, $month_fmt)
        }
    
    function test_fix_datetime_string()
        {
        $this->assertEqual(fix_datetime_string('2010-01-01'), '2010-01-01 00:00:00');
        $this->assertEqual(fix_datetime_string('2010-01-01 01'), '2010-01-01 01:00:00');
        $this->assertEqual(fix_datetime_string('2010-01-01 02:03'), '2010-01-01 02:03:00');
        $this->assertEqual(fix_datetime_string('2010-01-01 02:03:04'), '2010-01-01 02:03:04');
        $this->assertEqual(fix_datetime_string('2010-01-01', '23:59:59'), '2010-01-01 23:59:59');
        $this->assertEqual(fix_datetime_string('2010-01-01 01', '23:59:59'), '2010-01-01 01:59:59');
        $this->assertEqual(fix_datetime_string('2010-01-01 02:03', '23:59:59'), '2010-01-01 02:03:59');
        $this->assertEqual(fix_datetime_string('2010-01-01 02:03:04', '23:59:59'), '2010-01-01 02:03:04');
        }
        
    function test_seconds_to_string()
        {
        $this->assertEqual(seconds_to_string(''), '00:00:00');
        $this->assertEqual(seconds_to_string('1'), '00:00:01');
        $this->assertEqual(seconds_to_string('61'), '00:01:01');
        $this->assertEqual(seconds_to_string('3661'), '01:01:01');
        }

    // Sort an assoc
    function test_sort_by_key()
        {
        $data = Array();
        sort_by_key($data, 'id');
        $this->assertTrue(array_compare($data, Array()));

        $data = Array(
            Array('id'=>5, 'name'=>'snaf'),
            Array('id'=>2, 'name'=>'bar'),
            Array('id'=>1, 'name'=>'foo'),
            );
        $expect = Array(
            Array('id'=>1, 'name'=>'foo'),
            Array('id'=>2, 'name'=>'bar'),
            Array('id'=>5, 'name'=>'snaf'),
            );
        sort_by_key($data, 'id');
        //print_r($actual);
        $this->assertTrue(array_compare($data, $expect));

        $expect = Array(
            Array('id'=>2, 'name'=>'bar'),
            Array('id'=>1, 'name'=>'foo'),
            Array('id'=>5, 'name'=>'snaf'),
            );
        sort_by_key($data, 'name');
        //print_r($actual);
        $this->assertTrue(array_compare($data, $expect));
        }

    function test_stripslashes_deep()
        {
        // prepare some arrays
        $a1 = Array("apostrophe's", "apostrophe''s", "\"quotes\"", "\"\"quotes\"\"");
        $a2 = Array("apostrophe\'s", "apostrophe\'\'s", "\\\"quotes\\\"", "\\\"\\\"quotes\\\"\\\"");
        $a3 = Array(Array("apostrophe's"), Array(Array("apostrophe''s")), Array("\"quotes\""), Array("\"\"quotes\"\""));
        $a4 = Array(Array("apostrophe\'s"), Array(Array("apostrophe\'\'s")), Array("\\\"quotes\\\""), Array("\\\"\\\"quotes\\\"\\\""));

        // test a single string
        $this->assertTrue("apostrophe's" == stripslashes_deep("apostrophe\'s"));

        // test arrays
        $this->assertTrue(array_compare($a1, stripslashes_deep($a2)));
        $this->assertTrue(array_compare($a3, stripslashes_deep($a4)));
        }

    function test_string_truncate()
        {
        // 134 characters string
        $string = 'abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz';
        $newString = string_truncate($string, 110);
        $this->assertTrue(strlen($newString) <= 110);
        }

    function test_string_hanging_indent()
        {
        // long test string
        $string = 'abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz';
        $result = string_hanging_indent($string, 20, 70);
        $expected =  "                    abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "                    mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz\n";
        $expected .= "                    abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "                    mnopqrst uvwxyz\n";
        $this->assertEqual($result, $expected);

        $result = string_hanging_indent($string, 12, 51);
        $expected =  "            abcdefghi jkl mnopqrst uvwxyz abcdefghi\n";
        $expected .= "            jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "            mnopqrst uvwxyz abcdefghi jkl mnopqrst\n";
        $expected .= "            uvwxyz abcdefghi jkl mnopqrst uvwxyz\n";
        $this->assertEqual($result, $expected);

        // add title
        $result = string_hanging_indent($string, 20, 70, "My String:");
        $expected =  "My String:          abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "                    mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz\n";
        $expected .= "                    abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "                    mnopqrst uvwxyz\n";
        $this->assertEqual($result, $expected);

        $result = string_hanging_indent($string, 12, 51, "My String:");
        $expected =  "My String:  abcdefghi jkl mnopqrst uvwxyz abcdefghi\n";
        $expected .= "            jkl mnopqrst uvwxyz abcdefghi jkl\n";
        $expected .= "            mnopqrst uvwxyz abcdefghi jkl mnopqrst\n";
        $expected .= "            uvwxyz abcdefghi jkl mnopqrst uvwxyz\n";
        $this->assertEqual($result, $expected);

        // Does not break long words by default
        $string = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz';
        $result = string_hanging_indent($string, 20, 70);
        $expected =  "                    $string\n";
        $this->assertEqual($result, $expected);

        // Cut option breaks long words
        $string = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz';
        $result = string_hanging_indent($string, 20, 70, '', "\n", TRUE);
        $expected =  "                    abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwx\n";
        $expected .= "                    yzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuv\n";
        $expected .= "                    wxyzabcdefghijklmnopqrstuvwxyz\n";
        $this->assertEqual($result, $expected);

        // test with custom line break
        $string = 'abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz';
        $result = string_hanging_indent($string, 20, 70, '', "\r\n");
        $expected =  "                    abcdefghi jkl mnopqrst uvwxyz abcdefghi jkl\r\n";
        $expected .= "                    mnopqrst uvwxyz abcdefghi jkl mnopqrst uvwxyz\r\n";
        $this->assertEqual($result, $expected);
        }
        
    function test_string_ends_with()
        {
        $string = 'abcdefghijklmnopqrstuvwxyz';
        
        $this->assertTrue( string_ends_with($string, $string) );
        $this->assertTrue( string_ends_with($string, 'opqrstuvwxyz') );
        $this->assertTrue( string_ends_with($string, 'z') );
        
        $this->assertFalse( string_ends_with($string, 'a') );
        }

    
    function test_xmlentities()
        {
        $this->assertEqual(xmlentities('A & B'), 'A &amp; B');
        $this->assertEqual(xmlentities('<b>'), '&lt;b&gt;');
        $this->assertEqual(xmlentities('A\'B'), 'A&#39;B');
        $this->assertEqual(xmlentities("<Black> & \"White\" & 'Red' & <Green>"), "&lt;Black&gt; &amp; &quot;White&quot; &amp; &#39;Red&#39; &amp; &lt;Green&gt;");
        }

    function test_remove_invalid_xml()
        {
        $this->assertEqual(remove_invalid_xml(''), '');
        $this->assertEqual(remove_invalid_xml(null), '');
        $this->assertEqual(remove_invalid_xml('test'), 'test');
        $this->assertEqual(remove_invalid_xml("te\nst\t"), "te\nst\t");
        $this->assertEqual(remove_invalid_xml("te\0st"), "test");
        $this->assertEqual(remove_invalid_xml("te\x85st"), "te\x85st");
        }

    function test_title_case()
        {
        $tests = Array(
            '' => '',
            'COMPLETE WORKS' => 'Complete Works',
            'ADVERTISING/TRAILERS/PROMOS' => 'Advertising/Trailers/Promos',
            'OPERAS AND MUSICALS' => 'Operas and Musicals',
            'SOUND RECORDINGS(COMMERCIAL)' => 'Sound Recordings(Commercial)',
            'henry iv' => 'Henry IV',
            'Shakespearean Tragedy: \'hamlet\'' => 'Shakespearean Tragedy: \'Hamlet\'',
            'Merchant of Venice, the' => 'Merchant of Venice, The',
            'Shaw Vs. Shakespeare 1: the Character of Caesar' => 'Shaw Vs. Shakespeare 1: The Character of Caesar',
            'Henry Vi. Part 1' => 'Henry VI. Part 1',
            'Now Playing at Her Majesty\'S Theatre' => 'Now Playing at Her Majesty\'s Theatre',
            'how oft when thou, my music, music plays\'t?' => 'How Oft when Thou, My Music, Music Plays\'t?',
            'William Shakespeare\'s `a Midsummer Night\'s Dream\'' => 'William Shakespeare\'s `A Midsummer Night\'s Dream\'',
            'Shakespeare\'s \'Henry Iv\': History and Kings' => 'Shakespeare\'s \'Henry IV\': History and Kings',
            'Peter Greenaway: Anatomy of a Film-maker' => 'Peter Greenaway: Anatomy of a Film-Maker',
            'b.b.c. the voice of britain' => 'B.B.C. The Voice of Britain',
            'Acting With...jack Shepherd: Shaping Up for Shakespeare' => 'Acting with...Jack Shepherd: Shaping Up for Shakespeare',
            'Shakespeare In...and out' => 'Shakespeare in...and out',
            'Hamlet...performed Through the Art of Silence' => 'Hamlet...Performed Through the Art of Silence',
            'Acting Shakespeare with Ian Mckellen' => 'Acting Shakespeare with Ian McKellen',
            'mclintock \'mcbride\' mcrestofword,' => 'McLintock \'McBride\' McRestofword,',
            'Important, D\'Aimer, L\'' => 'Important, d\'Aimer, L\'',
            'DRAMA&LIT OPERA XX' => 'Drama&Lit Opera XX',
            '\'i was Considered a Very Ordinary Boy\'' => '\'I was Considered a Very Ordinary Boy\'',
            '1984 (Bbc)' => '1984 (BBC)',
            'Ac or Dc electricity' => 'AC or DC Electricity',
            );

        foreach ($tests as $test=>$expected)
            $this->assertEqual(title_case($test), $expected);
        }


    function test_array_merge_or()
        {
        $one = Array( 'hello', 'there', 'world' );
        $two = Array();
        $this->assertEqual( array_merge_or( $one, $two ), $one );

        $two = Array('hello', 'there');
        $this->assertEqual( array_merge_or( $one, $two ), $one );

        $one = Array();
        $two = Array('hello', 'there', 'world');
        $this->assertEqual( array_merge_or( $one, $two ), $two );

        $one = Array('hello', 'there');
        $two = Array('hello', 'there', 'world');
        $this->assertEqual( array_merge_or( $one, $two ), $two );

        $one = Array('hello', NULL, 'world');
        $two = Array('hello', 'there', 'world');
        $this->assertEqual( array_merge_or( $one, $two ), $two );

        $one = Array('hello', 'there', 'world');
        $two = Array('hello', NULL, 'world');
        $this->assertEqual( array_merge_or( $one, $two ), $one );

        $one = Array( 'alpha' => 'hello', 'beta' => 'world' );
        $two = Array('beta' => 'world' );
        $this->assertEqual( array_merge_or( $one, $two ), $one );

        $one = Array( 'alpha' => 'hello', 'beta' => 'world' );
        $two = Array( 'gamma' => 'there' );
        $this->assertEqual( array_merge_or( $one, $two ), Array( 'alpha' => 'hello', 'beta' => 'world', 'gamma' => 'there') );

        $one = Array( 'alpha' => 'hello', 'beta' => 'world' );
        $two = Array( 'alpha' => 'hello', 'beta' => 'world', 'gamma' => 'there' );
        $this->assertEqual( array_merge_or( $one, $two ), $two );

        $one = Array( 0, 1, 2, 3 );
        $two = Array( 4, 5 );
        $this->assertEqual( array_merge_or( $one, $two ), $one );

        $one = Array( 0, 1, 2, 3 );
        $two = Array( 4, 5, 6, 7, 8 );
        $this->assertEqual( array_merge_or( $one, $two ), Array( 0, 1, 2, 3, 8 ) );
        
        $one = "hello";
        $two = Array( "hello", "there", "world" );
        $this->assertEqual( array_merge_or( $one, $two ), $two );
        
        $one = Array( "hello", "there", "world" );
        $two = "hello";
        $this->assertEqual( array_merge_or( $one, $two ), $one );
        }
    
    function test_format_number()
        {
        $tests = Array(
            '' => '',
            '0' => '0',
            '123' => '123',
            '12345' => '12,345',
            '123456789' => '123,456,789',
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual(format_number($test), $expected);
        }
        
	}

/*### Used for testing strip_codes()
function dump_str($prompt, $s)
	{
	print $prompt . ': ';
	for ($i = 0; $i < strlen($s); $i++)
		printf('%d ', ord($s{$i}));
	print "\n";
	}
###*/
