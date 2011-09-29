<?php
// $Id$
// Tests for RecordList
// Alexander Veenendaal, 26 Jun 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class BaseContainerTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->container = new BaseContainer();
        }
        
    function test_set_get()
        {
        $this->container[] = 'alpha';
        $this->container[] = 'beta';
        $this->container['third'] = 'gamma';
        $this->assertTrue( $this->container[0], 'alpha' );
        $this->assertTrue( $this->container[1], 'beta' );
        $this->assertTrue( $this->container['third'], 'gamma' );
        }
        
    function test_unset()
        {
        $this->container['first'] = 'alpha';
        $this->container['second'] = 'beta';
        $this->assertTrue( $this->container->count(), 2 );
        unset($this->container['first']);
        $this->assertNull( $this->container['first'] );
        $this->assertTrue( $this->container->count(), 1 );
        }
        
    function test_exists()
        {
        $this->container['first'] = 'alpha';
        $this->container['second'] = 'beta';
        $this->assertTrue( isset($this->container['first']) );
        $this->assertFalse( isset($this->container['third']) );
        $this->assertFalse( empty($this->container['first']) );
        $this->assertTrue( empty($this->container['third']) );
        }
        
    function test_iterator()
        {
        $items = Array( 'first' => 'alpha', 'second' => 'beta', 'third' => 'gamma' );

        foreach($items as $key => $value)
            $this->container[$key] = $value;

        $count = 0;
        foreach ($this->container as $key => $value )
            {
            $this->assertEqual($items[$key], $value);
            $count++;
            }

        $this->assertEqual(count($this->container), $count);
        }
    
    function test_is_empty()
        {
        $this->assertTrue( $this->container->is_empty() );
        
        $this->container[] = 'alpha';
        $this->container[] = 'beta';
        
        $this->assertFalse( $this->container->is_empty() );
        }
        
    function test_clear()
        {
        $this->assertTrue( $this->container->is_empty() );
        
        $this->container[] = 'alpha';
        $this->container[] = 'beta';
        
        $this->assertFalse( $this->container->is_empty() );
        
        $this->container->clear();
        
        $this->assertTrue( $this->container->is_empty() );
        $this->assertEqual( count($this->container), 0 );
        }
    }
