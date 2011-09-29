<?php
// $Id$
// Tests for TextExtractor
// Ali Macdonald 8 April 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk
define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class TextExtractorTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->extractor = new TextExtractor();
        }

    function test_all_formats()
		{
		$this->assertTrue(isset($this->extractor->supported_formats['.txt']));
		$this->assertTrue(isset($this->extractor->supported_formats['.doc']));
		$this->assertTrue(isset($this->extractor->supported_formats['.docx']));
		$this->assertTrue(isset($this->extractor->supported_formats['.pdf']));
		$this->assertTrue(isset($this->extractor->supported_formats['.ppt']));
		}
        
    function test_filenotfound()
        {
        $files = array('test.txt','test.doc','test.docx','file not found', 'file not found.foo', 'test.ppt', 'test.pdf');
        foreach($files as $filename)
            {
            $this->extractor = new TextExtractor();
            $this->assertEqual('',$this->extractor->error_message);
            $r = $this->extractor->extract($filename);
            $this->assertNull($r);  
            $this->assertNotEqual('',$this->extractor->error_message, $filename); 
            }
        }
        
    function test_missing_catdoc()
        {  
        global $CONF; 
        $filename = $CONF['path_test'] . 'data/test.doc';
        $this->extractor->catdoc = 'foobar';
        $r = $this->extractor->extract($filename);
        $this->assertNull($r); 
        }
        
    function test_missing_ppthtml()
        {  
        global $CONF; 
        $filename = $CONF['path_test'] . 'data/test.ppt';
        $this->extractor->ppthtml = 'foobar';
        $r = $this->extractor->extract($filename);
        $this->assertNull($r); 
        }
        
    function test_missing_pdftotext()
        {  
        global $CONF; 
        $filename = $CONF['path_test'] . 'data/test.pdf';
        $this->extractor->pdftotext = 'foobar';
        $r = $this->extractor->extract($filename);
        $this->assertNull($r); 
        }   
        
    function test_text()
        {
        global $CONF;
        $filename = $CONF['path_var'] . 'test.txt';
        $content = 'This is a text file ' . rand();
        file_put_contents($filename, $content);
        $r = $this->extractor->extract($filename);
        $this->assertEqual($content, $r);
        unlink($filename);
        }
        
    function test_text_with_alt_name()
        {
        global $CONF;
        $filename = $CONF['path_var'] . 'test.badext';
        $content = 'This is a text file ' . rand();
        file_put_contents($filename, $content);
        $r = $this->extractor->extract($filename);
        $this->assertNotEqual($content, $r);
        unlink($filename);
        }
        
    function test_doc()
        {
        global $CONF;
		//don't run test if utility not on this machine
		if($this->extractor->supported_formats['.doc']['supported'])
			{
		    $filename = $CONF['path_test'] . 'data/test.doc';
		    $content = "This is a Word document.\n";
		    $r = $this->extractor->extract($filename);
		    $this->assertEqual($content, $r);
			}    
        }
        
    function test_rtf()
        {
        global $CONF;
		//don't run test if utility not on this machine
		if($this->extractor->supported_formats['.rtf']['supported'])
			{
		    $filename = $CONF['path_test'] . 'data/test.rtf';
		    $content = "This is an RTF document.\n";
		    $r = $this->extractor->extract($filename);
		    $this->assertEqual($content, $r); 
			}    
        }
        
    function test_docx()
        {
        global $CONF;
        $filename = $CONF['path_test'] . 'data/test.docx';
        $content = "This is a Word DOCX document.";
        $r = $this->extractor->extract($filename);
        $this->assertEqual($content, $r);      
        }

    function test_ppt()
        {
        global $CONF;
		//don't run test if utility not on this machine
		if($this->extractor->supported_formats['.ppt']['supported'])
			{
			$filename = $CONF['path_test'] . 'data/test.ppt';
			$content = "\n".$filename."\n&nbsp;\nCreated with pptHtml\n\n"; //not happy with this - this is a watermark this version of ppthtml inserts
			$r = $this->extractor->extract($filename);
			$this->assertEqual($content, html2txt($r));
			}   
        }

	function test_pdf()
        {
        global $CONF;
		//don't run test if utility not on this machine
		if($this->extractor->supported_formats['.pdf']['supported'])
			{
		    $filename = $CONF['path_test'] . 'data/test.pdf';
		    $content = "This is a pdf document";
		    $r = $this->extractor->extract($filename);
		    $this->assertEqual($content, substr($r,0,-3));  //cannot identify 3 evidently added line returns in pdf, tried \r, \n, \xc2\xa0
			}
        }
    }
