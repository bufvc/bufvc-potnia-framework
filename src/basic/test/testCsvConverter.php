<?php
// $Id$
// Tests for CsvConverter
// Phil Hansen, 21 Aug 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class CsvConverterTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->importer = new CsvConverter();
        }
    
    function test_parse_line()
        {
        $line = 'A,B,"C","D""E""F","G\H",I';
        $expected = Array('A', 'B', 'C', 'D"E"F','G\H','I');
        $this->assertEqual($this->importer->parse_line($line), $expected);
        }
    
    function test_convert_date()
        {
        $tests = Array(
            '14/02/2010'=>'2010-02-14',
            '03/03/1999'=>'1999-03-03',
            '31/12/1919'=>'1919-12-31'
            );
        foreach ($tests as $test=>$expected)
            $this->assertEqual($this->importer->convert_date($test), $expected);
        }
    
    function test_convert_fields()
        {
        $fields = Array(0=>"A 'B' C", 1=>"Andrés", 3=>'test-123', 4=>'msword–dash', 5=>"AB");
        $expected = Array(0=>"A 'B' C", 1=>"AndrÃ©s", 3=>'test-123', 4=>'msword-dash', 5=>"A\nB");
        $this->assertEqual($this->importer->convert_fields($fields), $expected);
        }
    }
