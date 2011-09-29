<?php
// $Id$
// Test template/block functions
// James Fryer, 27 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class BlockTestCase
    extends UnitTestCase
    {
    function test_block()
        {
        // Reads from test/templates/block-default.php
        $block = new Block();
        $this->assertEqual('default', $block->name);
        $this->assertEqual("default block\n", $block->render());
        }

    function test_block_with_template()
        {
        // Reads from test/templates/block-test.php
        $block = new Block('test');
        $this->assertEqual('test', $block->name);
        $this->assertEqual("test block\n", $block->render());
        }

    function test_block_with_vars()
        {
        // Reads from test/templates/block-test.php
        $block = new Block('test');
        $block->set_vars(Array('foo'=>'bar', 'janf'=>'quux'));
        $this->assertEqual("test block\nfoo=bar\njanf=quux\n", $block->render());
        }

    function test_hidden()
        {
        // Reads from test/templates/block-test.php
        $block = new Block('test');
        $block->hidden = TRUE;
        $this->assertEqual('', $block->render());
        }
    }

class SidebarBlockTestCase
    extends UnitTestCase
    {
    var $render_header = '<div class="query-enhancer title"><h4>title</h4><div class="query-enhancer-content">';

    function test_render_no_items()
        {
        $block = new SidebarBlock('title', 'description');
        $expected = $this->render_header . '<p>description</p></div></div>';
        $this->assertEqualIgnoreWS($expected, $block->render());
        $block = new SidebarBlock('title');
        $expected = '<div class="query-enhancer title"><h4>title</h4><div class="query-enhancer-content"></div></div>';
        $this->assertEqualIgnoreWS($expected, $block->render());
        }

    function test_render_with_items()
        {
        $block = new SidebarBlock('title', 'description', 'item');
        $expected = $this->render_header . '<ul><li>item</li></ul>' . '<p>description</p></div></div>';
        $this->assertEqualIgnoreWS($expected, $block->render());
        $block = new SidebarBlock('title', 'description', Array('a', 'b'));
        $expected = $this->render_header . '<ul><li>a</li><li>b</li></ul>' . '<p>description</p></div></div>';
        $this->assertEqualIgnoreWS($expected, $block->render());
        }

    function test_render_with_array_items()
        {
        $items = Array(
            Array('label'=>'foo', 'value'=>'bar'),
            Array('label'=>'foo2', 'value'=>'bar2', 'url'=>'quux'),
            Array('label'=>'foo3'),
            Array('label'=>'foo4', 'url'=>'quux2'),
            );
        $block = new SidebarBlock('title', 'description', $items);
        $expected = $this->render_header . '<ul>' . 
                '<li class="foo"><span class="label">foo:</span> bar</li>' . 
                '<li class="foo2"><span class="label">foo2:</span> <a href="quux">bar2</a></li>' . 
                '<li><span class="label">foo3</span></li>' . 
                '<li><span class="label"><a href="quux2">foo4</a></span></li>' . 
                '</ul>' . '<p>description</p></div></div>';
        $this->assertEqualIgnoreWS($expected, $block->render());
        }

    function assertEqualIgnoreWS($expected, $actual)
        {
        $expected = preg_replace('/\s+/', '', $expected);
        $actual = preg_replace('/\s+/', '', $actual);
        $this->assertEqual($expected, $actual);
        }
    }

