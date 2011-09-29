<?php
// $Id$
// Helpers for test routines
// James Fryer, 24 May 05
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define( "POSITION_AFTER", 1);
define( "POSITION_BEFORE", -1);
define( "POSITION_EQUAL", 0);

/// Extra functions for web testing.
class ImprovedWebTestCase
    extends WebTestCase
    {
    // used to mark the position of the last tag match
    var $tag_position = 0;
    
    /// Check for required page elements
    function assertPage($title, $status=200, $module=NULL)
        {
        global $MODULE;
        if (is_null($module))
            $module = $MODULE;
        $this->assertResponse($status);
        $this->assertTitle(new PatternExpectation("|$title.*{$module->title}|"));
        $this->assertTag('h2', $module->title);
        $this->assertTag('h1', $title);
        $this->assertTag('ul', NULL, 'class', 'app-menu');
        // Trap PHP errors
        $this->assertNoPattern('/^<b>(Notice|Error|Warning|Parse error)/im');
        }

    /// Succeeds if tag is present
    function assertTag($name, $content=NULL, $attr_name=NULL, $attr_val=NULL, $mark_position=FALSE)
        {
        $pattern = $this->_make_tag_pattern($name, $content, $attr_name, $attr_val);
        $result = $this->assertPattern( $pattern );
        if( $result && $mark_position )
            {
            preg_match($pattern, $this->_browser->getContent(), $matches);
            $this->tag_position = strpos($this->_browser->getContent(), $matches[0]);
            }
        return $result;
        }

    /// Fails if tag is present
    function assertNoTag($name, $content=NULL, $attr_name=NULL, $attr_val=NULL)
        {
        $this->assertNoPattern($this->_make_tag_pattern($name, $content, $attr_name, $attr_val));
        }
        
    /// Succeeds if the tag is present and its position is either less than, equal to or greater than the last tag assertion
    function assertTagRelativePosition($name, $content=NULL, $attr_name=NULL, $attr_val=NULL, $relative_position=1 )
        {
        $existing_position = $this->tag_position;
        $result = $this->assertTag( $name, $content, $attr_name, $attr_val, TRUE );        
        if( $result )
            {
            $msg = " new:{$this->tag_position} prev:$existing_position";
            if( $relative_position > 0 )
                $this->assertTrue( $this->tag_position > $existing_position, 'exp: >' . $msg );
            else if( $relative_position < 0 )
                $this->assertTrue( $this->tag_position < $existing_position, 'exp: <' . $msg );
            else
                $this->assertTrue( $this->tag_position == $existing_position, 'exp: ==' . $msg);
            }
        }

    /// Get returned content
    function getContent()
        {
        return $this->_browser->getContent();
        }
        
    // Helper for tag functions
    // Make a pattern which looks for the tag content and an attribute name/value pair
    // The content can be anywhere within the tag
    function _make_tag_pattern($name, $content, $attr_name=NULL, $attr_val=NULL)
        {
        if ($attr_name != '')
            {
            if ($attr_val == '')
                $attr_val = '[^"]*';
            $attr_re = ' [^>]*' . $attr_name . '="' . $attr_val . '[ "].*>';
            }
        else
            $attr_re = '( .*>|>)';
        $content_re = ($content == '') ? '.*' : '.*' . $content . '.*';
        return '@<' . $name . $attr_re . $content_re . '<\/' . $name . '>@Uis';
        }
    }

?>
