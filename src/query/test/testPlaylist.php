<?php
// $Id$
// Tests for Playlist class
// Phil Hansen, 19 Feb 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

/// Test case for Playlist class
class PlaylistTestCase
    extends UnitTestCase
    {    
    var $test_media = Array(
        Array('title'=>'File 1', 'location'=>'B001/file.mp3', 'content_type'=>'audio/mpeg',
            'duration'=>'100', 'sort_order'=>'1'),
        Array('title'=>'File 2', 'location'=>'B001/file2.mp3', 'content_type'=>'audio/mpeg',
            'duration'=>'100', 'sort_order'=>'2'),
        Array('title'=>'File 3', 'location'=>'B001/file3.mp3', 'content_type'=>'audio/mpeg',
            'duration'=>'100', 'sort_order'=>'3'),
        Array('title'=>'File 4', 'location'=>'B001/file4.avi', 'content_type'=>'video',
            'duration'=>'0', 'sort_order'=>'4'),
        );
    
    function setup()
        {
        $this->playlist = new Playlist();
        }
    
    function test_generate_single()
        {
        global $CONF;
        $expected1 = "<track><meta rel=\"type\">mp3</meta><location>B001/file.mp3</location></track>\n";
        $expected2 = "<track><meta rel=\"type\">mp3</meta><location>B001/file3.mp3</location></track>\n";        
        $this->assertEqual($this->playlist->generate_single($this->test_media[0]), $expected1);
        $this->assertEqual($this->playlist->generate_single($this->test_media[2]), $expected2);
        }
    
    function test_generate_all()
        {
        $result = $this->playlist->generate_all($this->test_media);
        
        // check for the files
        $this->assertPattern('|B001/file.mp3</location></track>|', $result);
        $this->assertPattern('|B001/file2.mp3</location></track>|', $result);
        $this->assertPattern('|B001/file3.mp3</location></track>|', $result);
        $this->assertNoPattern('|B001/file4.avi</location></track>|', $result);
        }
    }
