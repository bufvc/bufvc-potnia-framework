<?php
// $Id$
// Tests for Formatter class
// Phil Hansen, 17 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

Mock::generate('ExportFormatterUtil');

/// DataSource for testing with formatters
class FormatterDataSource
    extends DataSource
    {
    function FormatterDataSource()
        {
        $config = Array(
            'test' => Array(
                'title'=>'Test',
                'summary'=>'Test table',
                'mutable'=>TRUE,
                'storage'=>'memory',
                'fields'=>Array(
                    'title' => Array('require'=>1, 'dc_element'=>'title', 'text_label'=>'Title', 'bibtex_element'=>'title', 'atom_element'=>'title', 'ical_element'=>'summary'),
                    'summary' => Array('dc_element'=>'description', 'text_label'=>'Description', 'bibtex_element'=>'abstract', 'atom_element'=>'summary', 'ical_element'=>'description'),
                    'files' => Array(),
                    'keywords' => Array('dc_element'=>'subject', 'text_label'=>'Keywords', 'bibtex_element'=>'keywords', 'ical_element'=>'categories'),
                    'category' => Array('dc_element'=>'subject', 'text_label'=>'Genre', 'bibtex_element'=>'keywords'),
                    'people' => Array('bibtex_element'=>'author', 'atom_element'=>'contributor'),
                    'double_array' => Array('dc_element'=>'double_array', 'text_label'=>'Double array', 'bibtex_element'=>'double_array', 'atom_element'=>'double_array'),
                    'location' => Array('atom_element'=>'link'),
                    'date' => Array('ical_element'=>'dtstart', 'ical_element_config'=>Array('description'=>'Second description')),
                    'date2' => Array('ical_element'=>'dtstart', 'ical_element_config'=>Array('dtend'=>'end_date')),
                    'end_date'=> Array(),
                    ),
                'dc_element_static' => Array(
                    'creator' => 'Invocrown Ltd',
                    ),
                'dc_element_extras' => Array(
                    'location' => 'new_label',
                    ),
                'bibtex_element_static' => Array(
                    'publisher' => 'Invocrown Ltd',
                    ),
                'bibtex_element_extras' => Array(
                    'location' => 'new_label',
                    ),
                'text_label_static' => Array(
                    // this adds a static field
                    'Additional' => 'some other data',
                    ),
                'text_label_extras' => Array(
                    // this inserts the field for this export type
                    'location' => 'New label',
                    ),
                'ical_element_static' => Array(
                    'comment' => 'Invocrown Ltd',
                    ),
                'ical_element_extras' => Array(
                    'location' => 'location',
                    ),
                ),
            );
        DataSource::DataSource($config);
        $this->add_mock_data();
        }

    /// Function to help with unit tests
    function add_mock_data()
        {
        $this->create('test', Array('slug'=> 'single', 'title'=>'single', 'summary'=>'Test item', 'files'=>Array('file1.mp3'),
            'keywords'=>Array('keyword1 \'A&B\'<b>', 'keyword2'), 'category'=>Array('test genre'), 'people'=>Array('A', 'B'),
            'double_array'=>Array(Array('name'=>'test1', 'junk'=>'junk1'),Array('name'=>'test2', 'junk'=>'junk2')),
            'location'=>'http://example.com',
            'date'=>'2011-01-29', 'date2'=>'2011-01-31', 'end_date'=>'2011-02-01'));
        }
    }

/// Test case for ExportFormatter class
class ExportFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new ExportFormatter($this->module);
        $this->url = $this->module->url('index', '/test/single');
        }
    
    function test_fields()
        {
        $this->assertEqual(get_class($this->formatter->_util), 'ExportFormatterUtil');
        $this->assertEqual(get_class($this->formatter->module), 'DummyModule');
        $this->assertEqual($this->formatter->label, 'text_label');
        $this->assertEqual($this->formatter->url_label, 'URL');
        $this->assertEqual($this->formatter->newline, "\r\n");
        $this->assertFalse($this->formatter->repeat_elements);
        $this->assertEqual($this->formatter->join_string, '; ');
        $this->assertEqual($this->formatter->record_separator, '');
        $this->assertEqual($this->formatter->file_ext, '');
        }
    
    function test_format_calls_format_fields()
        {
        $util = new MockExportFormatterUtil();
        $util->expectOnce("format_fields");
        $util->setReturnValue("format_fields", Array());
        $this->formatter = new ExportFormatter($this->module, $util);
        $this->formatter->format(Array());
        }

    function test_format()
        {
        $expected_data  = "Title: single\r\n";
        $expected_data .= "Description: Test item\r\n";
        $expected_data .= "Keywords: keyword1 \xE2\x80\x98A&B\xE2\x80\x99<b>; keyword2\r\n";
        $expected_data .= "Genre: test genre\r\n";
        $expected_data .= "Double array: test1; test2\r\n";
        $expected_data .= "New label: http://example.com\r\n";
        $expected_data .= "Additional: some other data\r\n";
        $expected_data .= 'URL: ' . $this->url . "\r\n";
        
        $record = $this->ds->retrieve('/test/single');
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    
    function test_format_repeat_elements()
        {
        $expected_data  = "Title: single\r\n";
        $expected_data .= "Description: Test item\r\n";
        $expected_data .= "Keywords: keyword1 \xE2\x80\x98A&B\xE2\x80\x99<b>\r\n";
        $expected_data .= "Keywords: keyword2\r\n";
        $expected_data .= "Genre: test genre\r\n";
        $expected_data .= "Double array: test1\r\n";
        $expected_data .= "Double array: test2\r\n";
        $expected_data .= "New label: http://example.com\r\n";
        $expected_data .= "Additional: some other data\r\n";
        $expected_data .= 'URL: ' . $this->url . "\r\n";
        
        $record = $this->ds->retrieve('/test/single');
        $this->formatter->repeat_elements = TRUE;
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    
    function test_format_empty()
        {
        foreach (Array(Array(), NULL) as $test)
            $this->assertEqual($this->formatter->format($test), '');
        }
    
    function test_get_record_url()
        {
        $expected = 'URL: '.$this->module->url('index', 'test');
        $this->assertEqual($this->formatter->get_record_url('test'), $expected);
        }
    
    function test_get_element_output()
        {
        $this->assertEqual($this->formatter->get_element_output('test name', 'test value'), "test name: test value");
        }
    
    function test_join_values()
        {
        $data = Array('value1', 'value2', 'value3');
        $this->assertEqual($this->formatter->join_values('name', $data), join('; ', $data));
        }
    
    function test_get_values_from_arrays()
        {
        $data1 = Array(
            Array('name'=>'A', 'junk'=>'junk'),
            Array('name'=>'B'),
            Array('name'=>'C'),
            );
        $data2 = Array(
            Array('title'=>'A', 'junk'=>'junk'),
            Array('title'=>'B'),
            Array('title'=>'C'),
            );
        $data3 = Array(
            Array('junk'=>'junk'),
            );
        $this->assertEqual($this->formatter->get_values_from_arrays($data1), Array('A', 'B', 'C'));
        $this->assertEqual($this->formatter->get_values_from_arrays($data2), Array('A', 'B', 'C'));
        $this->assertEqual($this->formatter->get_values_from_arrays($data3), Array());
        $this->assertEqual($this->formatter->get_values_from_arrays(Array()), Array());
        }
    
    function test_get_label_map()
        {
        $expected = Array(
            'title'=>'Title',
            'summary'=>'Description',
            'keywords'=>'Keywords',
            'category'=>'Genre',
            'double_array'=>'Double array',
            );
        $table = $this->ds->retrieve('/test');
        $this->assertEqual($this->formatter->get_label_map($table, 'text_label'), $expected);
        }
    }

/// Test case for ExportFormatterUtil class
class ExportFormatterUtilTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->util = new ExportFormatterUtil();
        }

    function test_format_fields()
        {
        $record = Array('a'=>'b', 'c'=>'d', 'e'=>'123');
        // empties
        foreach (Array(Array(), '1', NULL) as $test)
            $this->assertEqual($this->util->format_fields($test), Array());
        $this->assertEqual($this->util->format_fields($record), $record);
        }
    }

/// Test case for TextFormatter class
class TextFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new TextFormatter($this->module);
        }

    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'text_label');
        $this->assertEqual($this->formatter->url_label, 'URL');
        $this->assertEqual($this->formatter->newline, "\r\n");
        $this->assertEqual($this->formatter->record_separator, "----\r\n");
        $this->assertEqual($this->formatter->file_ext, '.txt');
        }
    
    function test_get_element_output()
        {
        $this->assertEqual($this->formatter->get_element_output('test name', 'test value'), "test name:     test value");
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $expected_data  = "Title:         single\r\n";
        $expected_data .= "Description:   Test item\r\n";
        $expected_data .= "Keywords:      keyword1 \xE2\x80\x98A&B\xE2\x80\x99<b>; keyword2\r\n";
        $expected_data .= "Genre:         test genre\r\n";
        $expected_data .= "Double array:  test1; test2\r\n";
        $expected_data .= "New label:     http://example.com\r\n";
        $expected_data .= "Additional:    some other data\r\n";
        $expected_data .= 'URL:           '.$this->module->url('index', $record['url'])."\r\n";
        
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    }

/// Test case for DublinCoreFormatter class
class DublinCoreFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new DublinCoreFormatter($this->module);
        }

    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'dc_element');
        $this->assertEqual($this->formatter->url_label, 'identifier');
        $this->assertEqual($this->formatter->newline, "\n");
        $this->assertTrue($this->formatter->repeat_elements);
        $this->assertEqual($this->formatter->file_ext, '.xml');
        }
    
    function test_get_record_header()
        {
        $this->assertEqual($this->formatter->get_record_header(Array()), "<record>");
        }
    
    function test_get_record_footer()
        {
        $this->assertEqual($this->formatter->get_record_footer(Array()), "</record>");
        }
    
    function test_get_element_output()
        {
        $result = $this->formatter->get_element_output('test_name', 'test value');
        $expected = "<dc:test_name>test value</dc:test_name>";
        $this->assertEqual($result, $expected);
        }
    
    function test_get_extra_elements()
        {
        $result = $this->formatter->get_extra_elements();
        $expected = Array('<dc:source>'.$this->module->url().'</dc:source>');
        $this->assertEqual($result, $expected);
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $expected_data = <<<EOT
<record>
<dc:title>single</dc:title>
<dc:description>Test item</dc:description>
<dc:subject>keyword1 \xE2\x80\x98A&amp;B\xE2\x80\x99&lt;b&gt;</dc:subject>
<dc:subject>keyword2</dc:subject>
<dc:subject>test genre</dc:subject>
<dc:double_array>test1</dc:double_array>
<dc:double_array>test2</dc:double_array>
<dc:new_label>http://example.com</dc:new_label>
<dc:creator>Invocrown Ltd</dc:creator>

EOT;
        $expected_data .= '<dc:source>'.$this->module->url()."</dc:source>\n";
        $expected_data .= '<dc:identifier>'.$this->module->url('index', $record['url'])."</dc:identifier>\n";
        $expected_data .= "</record>\n";
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    
    function test_get_header_footer()
        {
        $expected_data = <<<EOT
<?xml version="1.0"?>
<results
  xmlns="http://bufvc.ac.uk/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://dublincore.org/schemas/xmls simpledc20021212.xsd"
  xmlns:dc="http://purl.org/dc/elements/1.1/">

EOT;
        $this->assertEqual($this->formatter->get_header(), $expected_data);
        $this->assertEqual($this->formatter->get_footer(), '</results>');
        }
	}

/// Test case for BibTeXFormatter class
class BibTeXFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new BibTeXFormatter($this->module);
        }

    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'bibtex_element');
        $this->assertEqual($this->formatter->url_label, 'url');
        $this->assertEqual($this->formatter->newline, "\r\n");
        $this->assertEqual($this->formatter->join_string, ", ");
        $this->assertEqual($this->formatter->record_separator, "\r\n");
        $this->assertEqual($this->formatter->file_ext, '.bib');
        }
    
    function test_get_record_header()
        {
        $this->assertEqual($this->formatter->get_record_header(Array('url'=>'/test')), "@Misc {/test,");
        $this->assertEqual($this->formatter->get_record_header(Array('url'=>'/test?123')), "@Misc {/test-123,");
        $this->assertEqual($this->formatter->get_record_header(Array('url'=>'/test"\',@{}=')), "@Misc {/test-------,");
        }
    
    function test_get_record_footer()
        {
        $this->assertEqual($this->formatter->get_record_footer(Array()), "}");
        }
    
    function test_get_element_output()
        {
        $result = $this->formatter->get_element_output('test_name', 'test value');
        $expected = "test_name = \"test value\",";
        $this->assertEqual($result, $expected);
        // double quotes are escaped
        $result = $this->formatter->get_element_output('test_name', 'a "test" value');
        $expected = "test_name = \"a {\"}test{\"} value\",";
        $this->assertEqual($result, $expected);
        }
    
    function test_join_values()
        {
        $values = Array('value1', 'val2', 'val3');
        $expected1 = 'value1, val2, val3';
        $expected2 = 'value1 and val2 and val3';
        $this->assertEqual($this->formatter->join_values('test', $values), $expected1);
        $this->assertEqual($this->formatter->join_values('author', $values), $expected2);
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $expected_data = "@Misc {/test/single,\r\n";
        $expected_data .= "title = \"single\",\r\n";
        $expected_data .= "abstract = \"Test item\",\r\n";
        $expected_data .= "keywords = \"keyword1 \xE2\x80\x98A&B\xE2\x80\x99<b>, keyword2\",\r\n";
        $expected_data .= "keywords = \"test genre\",\r\n";
        $expected_data .= "author = \"A and B\",\r\n";
        $expected_data .= "double_array = \"test1, test2\",\r\n";
        $expected_data .= "new_label = \"http://example.com\",\r\n";
        $expected_data .= "publisher = \"Invocrown Ltd\",\r\n";
        $expected_data .= "url = \"" . $this->module->url('index', $record['url']) . "\",\r\n";
        $expected_data .= "}\r\n";
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    }

/// Test case for AtomFormatter class
class AtomFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new AtomFormatter($this->module);
        }
    
    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'atom_element');
        $this->assertEqual($this->formatter->url_label, 'id');
        $this->assertEqual($this->formatter->newline, "\n");
        $this->assertTrue($this->formatter->repeat_elements);
        }

    function test_get_element_output()
        {
        // normal
        $result = $this->formatter->get_element_output('test_name', 'test value');
        $expected = "    <test_name>test value</test_name>";
        $this->assertEqual($result, $expected);
        // link
        $result = $this->formatter->get_element_output('link', 'test');
        $expected = "    <link href=\"test\"/>";
        $this->assertEqual($result, $expected);
        // author
        $result = $this->formatter->get_element_output('author', 'test value');
        $expected  = "    <author>\n";
        $expected .= "      <name>test value</name>\n";
        $expected .= "    </author>";
        $this->assertEqual($result, $expected);
        // contributor
        $result = $this->formatter->get_element_output('contributor', 'test value');
        $expected  = "    <contributor>\n";
        $expected .= "      <name>test value</name>\n";
        $expected .= "    </contributor>";
        $this->assertEqual($result, $expected);
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        // add a control character (vertical tab) - this should be removed as it's not valid xml
        $record['summary'] = '' . str_replace(' ', ' ', $record['summary']) . '';
        $expected_data = <<<EOT
    <title>single</title>
    <summary type="html">Test item</summary>
    <contributor>
      <name>A</name>
    </contributor>
    <contributor>
      <name>B</name>
    </contributor>
    <double_array>test1</double_array>
    <double_array>test2</double_array>
    <link href="http://example.com"/>

EOT;
        $expected_data .= '    <id>' . $this->module->url('index', $record['url']) . '</id>' . "\n";
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }

    function test_get_header_footer()
        {
        $expected_data = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>Dummy Module</title>

EOT;
        $expected_data .= '  <link rel="alternate" href="'.$this->module->url().'"/>'."\n";
        $expected_data .= '  <link rel="self" href="'.$this->module->url('feeds').'"/>'."\n";
        $expected_data .= <<<EOT
  <updated>2009-06-10T00:00:00Z</updated>
  <author>
    <name>author</name>
  </author>

EOT;
        $expected_data .= '  <id>' . $this->module->url('feeds') . '</id>' . "\n\n";
        $header = $this->formatter->get_header($this->module, '2009-06-10T00:00:00Z', 'author');
        $this->assertEqual($header, $expected_data);
        $footer = $this->formatter->get_footer();
        $this->assertEqual($footer, '</feed>');
        }
    
    function test_get_header_with_title_and_path()
        {
        $expected_data = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>Dummy Module Custom Title</title>

EOT;
        $expected_data .= '  <link rel="alternate" href="'.$this->module->url().'"/>'."\n";
        $expected_data .= '  <link rel="self" href="'.$this->module->url('feeds').'/test"/>'."\n";
        $expected_data .= <<<EOT
  <updated>2009-06-10T00:00:00Z</updated>
  <author>
    <name>author</name>
  </author>

EOT;
        $expected_data .= '  <id>' . $this->module->url('feeds') . '/test</id>' . "\n\n";
        $header = $this->formatter->get_header($this->module, '2009-06-10T00:00:00Z', 'author', ' Custom Title', '/test');
        $this->assertEqual($header, $expected_data);
        }
    
    function test_format_atom_date()
        {
        $tests = Array(
            '2008-10-14 00:00:00' => '2008-10-14T00:00:00Z',
            '2008-10-14' => '2008-10-14T00:00:00Z',
            '2008-10-14 12:13:59' => '2008-10-14T12:13:59Z',
            '' => '',
            NULL => '',
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual($this->formatter->format_atom_date($test), $expected);
        }
    
    function test_replace_chars()
        {
        $tests = Array(
            'test-123' => 'test-123',
            "msword\x96dash" => 'msword&ndash;dash',
            "ellipsis \x85" => 'ellipsis &hellip;',
            "left double quote \x93" => 'left double quote &ldquo;',
            "'" => "'",
            "..." => "...",
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual($this->formatter->replace_chars($test), $expected);
        }
	}

/// Test case for JsonFormatter class
class JsonFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new JsonFormatter($this->module);
        }

    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'text_label');
        $this->assertEqual($this->formatter->record_separator, ',');
        $this->assertEqual($this->formatter->file_ext, '.json');
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $expected_data  = '{"title":{"label":"Title","value":"single"},"summary":{"label":"Description","value":"Test item"},"files":{"label":"","value":["file1.mp3"]},"keywords":{"label":"Keywords","value":["keyword1 \u2018A&B\u2019<b>","keyword2"]},"category":{"label":"Genre","value":["test genre"]},"people":{"label":"","value":["A","B"]},"double_array":{"label":"Double array","value":[{"name":"test1","junk":"junk1"},{"name":"test2","junk":"junk2"}]},"location":{"label":"New label","value":"http:\/\/example.com"},"date":{"label":"","value":"2011-01-29"},"date2":{"label":"","value":"2011-01-31"},"end_date":{"label":"","value":"2011-02-01"},"_table":{"label":"","value":"test"},"url":{"label":"","value":"\/test\/single"},"Additional":{"label":"Additional","value":"some other data"}}';
        
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    
    function test_get_header_footer()
        {
        $this->assertEqual($this->formatter->get_header(), '{"records":[');
        $this->assertEqual($this->formatter->get_footer(), ']}');
        }
    
    function test_add_query_info_fields()
        {
        $str = '{"records":[{"id":{"label":"","value":"1"}},{"id":{"label":"","value":"2"}}]}';
        $query_info = Array(
            'results_count' => '2',
            'accuracy' => 'exact',
            'junk' => 'junk',
            );
        $expected = '{"records":[{"id":{"label":"","value":"1"}},{"id":{"label":"","value":"2"}}],"info":{"results_count":"2","accuracy":"exact"}}';
        $this->assertEqual($expected, $this->formatter->add_query_info_fields($str, $query_info));
        }
    }

/// Test case for ICalendarFormatter class
class ICalendarFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new ICalendarFormatter($this->module);
        }
    
    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'ical_element');
        $this->assertEqual($this->formatter->file_ext, '.ical');
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $result = $this->formatter->format($record);
        // check some values, can't do a check against the entire output because timestamps are generated
        $this->assertTrue(preg_match('/PRODID:-\/\/bufvc.ac.uk/', $result));
        $this->assertTrue(preg_match('/X-WR-CALNAME:BUFVC iCal export/', $result));
        $this->assertTrue(preg_match('/X-WR-CALDESC:BUFVC exported iCal data/', $result));
        $this->assertTrue(preg_match('/X-WR-TIMEZONE:Europe\/London/', $result));
        $this->assertTrue(preg_match('/CATEGORIES:keyword1 ‘A&B’<b>/', $result));
        $this->assertTrue(preg_match('/CATEGORIES:keyword2/', $result));
        $this->assertTrue(preg_match('/COMMENT:Invocrown Ltd/', $result));
        $this->assertTrue(preg_match('/DESCRIPTION:Second description/', $result));
        $this->assertTrue(preg_match('/DTSTART;VALUE=DATE:20110129/', $result));
        $this->assertTrue(preg_match('/LOCATION:http:\/\/example.com/', $result));
        $this->assertTrue(preg_match('/SUMMARY:single/', $result));
        $url = $this->module->url('index', $record['url']);
        $url = str_replace('/', '\/', $url);
        $this->assertTrue(preg_match('/URL:'.$url.'/', $result));
        $this->assertTrue(preg_match('/DTSTART;VALUE=DATE:20110131/', $result));
        $this->assertTrue(preg_match('/DTEND;VALUE=DATE:20110201/', $result));
        $this->assertTrue(preg_match('/DESCRIPTION:Test item/', $result));
        }
    
    function test_add_date()
        {
        $tests = Array(
            '2011-01-20' => Array(
                'params' => Array('VALUE'=>'DATE'),
                'value' => Array('year'=>'2011', 'month'=>'01', 'day'=>'20')
                ),
            '2011-01-20 17:30:15' => Array(
                'params' => '',
                'value' => Array('year'=>'2011', 'month'=>'01', 'day'=>'20', 'hour'=>'17', 'min'=>'30', 'sec'=>'15')
                ),
            );
        $calendar = new vcalendar(Array('unique_id' => 'test'));
        $event = &$calendar->newComponent('vevent');
        foreach ($tests as $test=>$expected)
            {
            $this->formatter->add_date($event, 'dtstart', $test);
            $this->assertEqual($event->dtstart, $expected);
            }
        }
	}

/// Test case for CitationFormatter class
class CitationFormatterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->ds = new FormatterDataSource();
        $this->module = Module::load('dummy');
        $this->module->_ds = $this->ds;
        $this->formatter = new CitationFormatter($this->module);
        }

    function test_fields()
        {
        $this->assertEqual($this->formatter->label, 'text_label');
        $this->assertEqual($this->formatter->record_separator, "----\r\n");
        $this->assertEqual($this->formatter->file_ext, '-citation.txt');
        }
    
    function test_format()
        {
        $record = $this->ds->retrieve('/test/single');
        $expected_data  = "\"single\"; ";
        $expected_data .= $this->module->url('index', $record['url']). " (Accessed ".strftime('%d %b %Y').")\r\n";
        
        $result = $this->formatter->format($record);
        $this->assertEqual($result, $expected_data);
        }
    }
