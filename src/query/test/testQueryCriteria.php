<?php
// $Id$
// Tests for QueryCriteria
// Alexander Veenendaal, 03 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');
require_once($CONF['path_src'] . 'query/QueryCriteria.class.php');

class QueryCriteriaTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->container = new QueryCriteria();
        }

    function test_constructor()
        {
        $criteria = Array(
            QueryCriterionFactory::create(Array('name' => 'willpower', 'qs_key' => 'WP', 'value' => 'sixtytwo')),
            QueryCriterionFactory::create(Array('name' => 'perception', 'qs_key' => 'PE','value' => 'fifty')),
            QueryCriterionFactory::create(Array('name' => 'luck', 'qs_key' => 'LK','value' => 'eightyone')),
            // Accepts either arrays or objects
            Array('name' => 'composure', 'qs_key' => 'CM', 'value' => 'fourtynine'),
           );
        
        $this->container = new QueryCriteria($criteria);
        $this->assertEqual(4, count($this->container));
        $this->assertEqual('fifty', $this->container['perception']->get_value());
        
        $other_container = new QueryCriteria($this->container);
        $this->assertEqual(4, count($other_container));
        $this->assertEqual('fourtynine', $this->container['composure']->get_value());
        
        $other_container['perception']->set_value('high');
        $this->assertNotEqual($other_container['perception']->get_value(), $this->container['perception']->get_value());
        }
        
    function test_adding()
        {
        $criterion = QueryCriterionFactory::create(Array('name' => 'mood', 'value' => 'happy'));
        $this->container['mood'] = $criterion;    
        
        $this->assertEqual(1, count($this->container));
        $this->assertEqual($criterion, $this->container['mood']);
        $this->assertTrue(isset($this->container["mood"]));
        
        //### TODO: Allow array to be added if it contains name field?
        $criterion = QueryCriterionFactory::create(Array('name' => 'charisma', 'value' => 'high'));
        $this->container[] = $criterion;
        
        $this->assertEqual(2, count($this->container));
        $this->assertEqual($criterion, $this->container['charisma']);
        $this->assertTrue(isset($this->container["charisma"]));
        
        // ensure that we are storing references
        $criterion->set_value(23);
        $this->assertEqual(23, $this->container['charisma']->get_value());
        
        $criterion_get = $this->container['charisma'];
        $criterion_get->set_value(99);
        $this->assertEqual(99, $this->container['charisma']->get_value());
        }
    
    function test_adding_invalid_value_integer()
        {
        // ensure that only querycriterion objects can be added
        $this->expectException(new QueryCriteriaException('offset must be specified for non QueryCriterion value'));
        $this->container[] = 34;
        }
    
    function test_adding_invalid_value_string()
        {
        // ensure that only querycriterion objects can be added
        $this->expectException(new QueryCriteriaException('offset must be specified for non QueryCriterion value'));
        $this->container[] = 'i really am a QueryCriterion, honest';
        }
    
    function test_adding_values()
        {
        $this->container['page_size'] = 12;
        $this->assertEqual(1, count($this->container));
        $this->assertTrue(isset($this->container["page_size"]));
        $this->assertEqual(12, $this->container['page_size']->get_value());
        }
        
    function test_set_values_to_defaults()
        {
        $this->container = new QueryCriteria(Array(
            Array('name' => 'x'),
            Array('name' => 'y')));
        // assign default values for x and y
        $this->container['x']->set_default('foo');
        $this->container['y']->set_default('123');
        $this->container->set_values();
        $this->assertEqual('foo', $this->container['x']->get_value());
        $this->assertEqual('123', $this->container['y']->get_value());
        }
        
    function test_set_indexes_to_defaults()
        {
        // NOTE AV : this really should be a querycriterion test (and it is), but its here now so...
        $this->container = new QueryCriteria(Array( Array('name' => 'x', 'index'=>'default') ));
        $this->assertEqual('default', $this->container['x']->get_index() );
        $this->container->set_values( Array('x' => 'foo'));
        $this->assertEqual('default', $this->container['x']->get_index() );
        
        // index changes...
        $this->container->set_values( Array('x' => 'foo', 'x[0][index]' => 'title' ));
        $this->assertEqual('title', $this->container['x']->get_index() );
        
        // index reverts back to default
        $this->container->set_values( Array( 'x' => 'foo' ));
        $this->assertEqual('default', $this->container['x']->get_index() );
        }

    function test_set_values()
        {
        $this->container = new QueryCriteria(Array(
            Array('name' => 'x'),
            Array('name' => 'y')));
        $this->container['y']->set_default('123');
        $this->container->set_values(Array('x'=>'foo'));
        $this->assertEqual('foo', $this->container['x']->get_value());
        $this->assertEqual('123', $this->container['y']->get_value());

        $this->container->set_values(Array('x'=>'bar', 'y'=>'quux'));
        $this->assertEqual('bar', $this->container['x']->get_value());
        $this->assertEqual('quux', $this->container['y']->get_value());
        }

    function test_removing()
        {
        $criterion_a = QueryCriterionFactory::create(Array('name' => 'dexterity', 'value' => 46));
        $criterion_b = QueryCriterionFactory::create(Array('name' => 'constitution', 'value' => 92));
        
        $this->container[] = $criterion_a;
        $this->container[] = $criterion_b;
        $this->assertTrue(isset($this->container["dexterity"]));
        $this->assertTrue(isset($this->container["constitution"]));
        $this->assertEqual(2, count($this->container));
        
        unset($this->container['constitution']);
        $this->assertEqual(1, count($this->container));
        $this->assertTrue(isset($this->container["dexterity"]));
        $this->assertFalse(isset($this->container["constitution"]));
        
        unset($this->container['dexterity']);
        $this->assertEqual(0, count($this->container));
        $this->assertFalse(isset($this->container["dexterity"]));
        $this->assertFalse(isset($this->container["constitution"]));
        }
        
    function test_iterator()
        {
        $criteria = Array(
            QueryCriterionFactory::create(Array('name' => 'wisdom', 'value' => 72)),
            QueryCriterionFactory::create(Array('name' => 'intelligence', 'value' => 69)),
            QueryCriterionFactory::create(Array('name' => 'strength', 'value' => 13)),
            QueryCriterionFactory::create(Array('name' => 'comeliness', 'value' => 100)),
           );
        
        foreach($criteria as $criterion)
            $this->container[] = $criterion;
        
        $count = 0;
        foreach ($this->container as $name => $criterion)
            {
            $this->assertEqual($this->container[$name], $criterion);
            $count++;
            }
        
        $this->assertEqual(count($criteria), $count);
        }

    function test_get_by_qs_key()
        {
        $this->container[] = QueryCriterionFactory::create(Array('name' => 'moxie', 'qs_key' => 'MX', 'value' => 'thirtynine'));
        $this->container[] = QueryCriterionFactory::create(Array('name' => 'chutzpah', 'qs_key' => 'CZ', 'value' => 67));
        $this->container[] = QueryCriterionFactory::create(Array('name' => 'power_index', 'qs_key' => 'PI', 'value' => 77));

        $this->assertNotNull($this->container->get_by_qs_key('CZ'));
        $this->assertNotNull($this->container->get_by_qs_key('CZ[0]'));
        $this->assertNotNull($this->container->get_by_qs_key('CZ[0][index]'));
        $this->assertNull($this->container->get_by_qs_key('C'));
        $this->assertNotNull($this->container->get_by_qs_key('CZ_q0'));
        $this->assertNull($this->container->get_by_qs_key('CZ_qqqq'));

        $this->assertEqual(67, $this->container->get_by_qs_key('CZ')->get_value());
        $this->assertEqual(77, $this->container->get_by_qs_key('PI')->get_value());
        $this->assertEqual('thirtynine', $this->container->get_by_qs_key('MX')->get_value());
        
        $criterion = $this->container->get_by_qs_key('CZ');
        $criterion->set_value('surprisingly average');
        $this->assertEqual('surprisingly average', $this->container->get_by_qs_key('CZ')->get_value());
        }

    function test_get_by_qs_key_multiple()
        {
        global $CONF;
        $this->container[] = QueryCriterionFactory::create(Array('name' => 'deception', 'qs_key' => Array('DE','DC'), 'value' => '68%'));
        
        $this->assertNotNull($this->container->get_by_qs_key('DE'));
        $this->assertNotNull($this->container->get_by_qs_key('DC'));
        $this->assertNotNull($this->container->get_by_qs_key('DE[0][v]'));
        $this->assertNotNull($this->container->get_by_qs_key('DC[0][index]'));
        
        $this->assertEqual('68%', $this->container->get_by_qs_key('DE')->get_value());
        $this->assertEqual('68%', $this->container->get_by_qs_key('DC')->get_value());
        }
    
    function test_remove_with_qs_key()
        {
        $criterion_a = QueryCriterionFactory::create(Array('name' => 'dexterity', 'qs_key' => 'DX', 'value' => 46));
        $criterion_b = QueryCriterionFactory::create(Array('name' => 'constitution', 'qs_key' => 'CT', 'value' => 92));
        $this->container[] = $criterion_a;
        $this->container[] = $criterion_b;
        
        // unsetting a key also removes any ability to get by qs key
        unset($this->container['dexterity']);
        $this->assertEqual(1, count($this->container));
        $this->assertFalse(isset($this->container["dexterity"]));
        $this->assertNull($this->container->get_by_qs_key('DX'));
        }
           
    function test_clone()
        {
        $criteria = Array(
            QueryCriterionFactory::create(Array('name' => 'willpower', 'qs_key' => 'WP', 'value' => 'sixtytwo')),
            QueryCriterionFactory::create(Array('name' => 'perception', 'qs_key' => 'PE','value' => 'fifty')),
            QueryCriterionFactory::create(Array('name' => 'luck', 'qs_key' => 'LK','value' => 'eightyone')),
            QueryCriterionFactory::create(Array('name' => 'composure', 'qs_key' => 'CM', 'value' => 'fourtynine')),
           );

        foreach($criteria as $criterion)
            $this->container[] = $criterion;
        
        $other_container = clone $this->container;
        
        $other_container['willpower']->set_value(65);
        $other_container['perception']->set_value('low');
        $other_container['luck']->set_value('seven');
        $other_container['composure']->set_value('pretty');
        
        $this->assertNotEqual($this->container['willpower']->get_value(), $other_container['willpower']->get_value());
        $this->assertNotEqual($this->container['perception']->get_value(), $other_container['perception']->get_value());
        $this->assertNotEqual($this->container['luck']->get_value(), $other_container['luck']->get_value());
        $this->assertNotEqual($this->container['composure']->get_value(), $other_container['composure']->get_value());
        
        $criterion = $this->container->get_by_qs_key('LK');
        $criterion->set_value('22');
        $this->assertNotEqual($this->container['luck']->get_value(), $other_container['luck']->get_value());
        $criterion->set_value('seven');
        $this->assertEqual($this->container['luck']->get_value(), $other_container['luck']->get_value());
        }
        
    function test_compare()
        {
        $this->container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => 'pointy_hat')),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => 'pointy_sword')),
           ));
        
        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => 'pointy_hat')),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => 'pointy_sword')),
           ));
            
        // compare with same criteria
        $this->assertTrue($this->container->compare($other_container));
        
        // compare with different criteria
        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
           ));
        
        // compare with different values
        $this->assertFalse($this->container->compare($other_container));
        
        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => 'long_beard')),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => 'pointy_sword')),
           ));
        
        $this->assertFalse($this->container->compare($other_container));
        
        // compare with NULL
        $this->assertFalse($this->container->compare(NULL));
        }

    function test_compare_null()
        {
        $this->container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => NULL)),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => '')),
           ));

        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => NULL)),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => NULL)),
           ));
        
        $this->assertTrue($this->container->compare($other_container));
        }
            
    function test_compare_zero()
        {
        $this->container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'x', 'value' => 0)),
            QueryCriterionFactory::create(Array('name' => 'y', 'value' => '')),
           ));
        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'x', 'value' => '')),
            QueryCriterionFactory::create(Array('name' => 'y', 'value' => 0)),
           ));

        $this->assertFalse($this->container->compare($other_container));
        }
        
    function test_compare_ignore()
        {
        $this->container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => 'pointy_hat')),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => 'pointy_sword')),
           ));

        $other_container = new QueryCriteria(Array(
            QueryCriterionFactory::create(Array('name' => 'elf', 'qs_key' => 'ELF', 'value' => 'pointy_ears')),
            QueryCriterionFactory::create(Array('name' => 'wizard', 'qs_key' => 'WIZ', 'value' => 'long_beard')),
            QueryCriterionFactory::create(Array('name' => 'warrior', 'qs_key' => 'WAR', 'value' => 'pointy_sword')),
           ));
            
        // ignore the fact that the wizard values don't match
        $this->assertTrue($this->container->compare($other_container, 'wizard'));
        
        // ignore the fact that both the wizard and warrior have mismatched values
        $other_container['warrior']->set_value('goes_by_name_of_conan');
        $this->assertTrue($this->container->compare($other_container, Array('wizard','warrior')));
        }
        
    function test_get_lists()
        {
        $this->assertIdentical(Array(), $this->container->get_lists());
        $this->container = new QueryCriteria(Array(
            Array('name' => 'q', 'value' => 'foo'),
            Array('name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(2008,2010)),
            Array('name' => 'L', 'type'=>QC_TYPE_LIST, 'list' => Array('foo')),
            Array('name' => 'Ignored', 'type'=>QC_TYPE_LIST, 'list' => 'foo'),
           ));
        $expected = Array('date_start'=>Array('' => '2008', '2009'=>'2009', '2010'=>'2010'), 
            'date_end'=>Array('2008' => '2008', '2009'=>'2009', ''=>'2010'), 
            'L' => Array('foo'),
           );
        $this->assertEqual($expected, $this->container->get_lists());
        }

    function test_query_string()
        {
        $this->assertEqual($this->container->query_string(), '');

        // Criteria are added if they are not empty
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'value' => 'foo' )
            ));
        $this->assertEqual($this->container->query_string(), 'q=foo');
        
        // Criteria is array
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'value' => Array('123', '456') )
            ));
        $this->assertEqual($this->container->query_string(), 'q[]=123&q[]=456');
        // array with blank values
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'value' => Array('', '') )
            ));       
        $this->assertEqual($this->container->query_string(), '');

        // encode the criteria values
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'value' => '"foo bar"' )
            ));
        $this->assertEqual($this->container->query_string(), 'q=%22foo+bar%22');

        // Criteria values of 0 are allowed
        // set the allowed criteria (avoid using q for this part as q is a special case)    
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'value' => '0' ),
            Array( 'name' => 'y', 'value' => 0 ),
            ));
        $this->assertEqual($this->container->query_string(), 'x=0&y=0');
        //### TODO:
        //###   - extra criteria
        //###   - Other paging vars        
        }
               
    function test_query_string_date_range()
        {
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'x' ),
                Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) ));
        // default values are not included in query_string
        $this->assertEqual($this->container->query_string(), '');
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'x' ),
                Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010), 'value'=>Array(1990,1999) ) 
                ));
        
        $this->assertEqual($this->container->query_string(), 'date_start=1990&date_end=1999');
        
        $this->container->set_values( Array('date_start'=>'1991') );
        $this->assertEqual($this->container->query_string(), 'date_start=1991');
        
        $this->container->set_values( Array('date_end'=>'2009') );
        $this->assertEqual($this->container->query_string(), 'date_end=2009');
        }
    
    function test_query_string_with_page()
        {
        // Page is added if non-zero
        // 'q' is added if page is present
        $this->container = new QueryCriteria();
        $this->assertEqual($this->container->query_string(NULL, NULL, 123), 'q=&page=123');
        
        // 0, 1 ignored
        $this->assertEqual($this->container->query_string(NULL, NULL, 0), '');
        $this->assertEqual($this->container->query_string(NULL, NULL, 1), '');
        
        $this->container = new QueryCriteria(Array(
            Array('name'=>'q', 'value'=>'') 
            ));
        $this->assertEqual($this->container->query_string(NULL, NULL, 62), 'q=&page=62');
        
        // Criteria parameter overrides page param
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'value' => 'bar' )
            ));
        $this->assertEqual($this->container->query_string(NULL, NULL, 456), 'q=bar&page=456'); 
        $local_criteria = Array('q'=>'foo', 'page'=>123);
        $this->assertEqual($this->container->query_string($local_criteria, NULL, 456), 'q=foo&page=123');
        }
        
    function test_has_criteria()
        {
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x' ),
            Array( 'name' => 'y' ) 
            ));
        
        $this->assertFalse($this->container->has_criteria());
        $this->container->set_values( Array('x'=>1) );
        $this->assertTrue($this->container->has_criteria());

        // use default criteria
        $this->container['x']->set_default('foo');

        // Implicit
        $this->container->set_values();
        // if only default criteria is set then has_criteria should return FALSE
        $this->assertFalse($this->container->has_criteria());
        $this->container->set_values(Array('x'=>1));
        $this->assertTrue($this->container->has_criteria());
        
        // Argument
        $this->assertFalse($this->container->has_criteria(Array()));
        $this->assertFalse($this->container->has_criteria(Array('x'=>'foo')));
        $this->assertTrue($this->container->has_criteria(Array('x'=>1)));
        $this->assertFalse($this->container->has_criteria(Array('junk'=>'junk')));
        $this->assertFalse($this->container->has_criteria(Array('x'=>'')));
        // blank value
        $this->container['x']->set_default('');
        $this->container->set_values(Array('x'=>''));
        $this->assertTrue($this->container->has_criteria(Array('x'=>'')));
        $this->assertFalse($this->container->has_criteria(Array('x'=>'foo')));
        $this->assertFalse($this->container->has_criteria(Array('x'=>1)));
        }
    
    function test_has_criteria_dates()
        {
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) 
                ));
        $this->assertFalse($this->container->has_criteria());
        
        $this->container->set_values( Array('date_start'=>'1991') );
        $this->assertTrue($this->container->has_criteria());
        }

    function test_has_allowed()
        {
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x' ),
            ));
            
        $this->assertTrue($this->container->has_allowed(Array('x'=>'')));
        $this->assertTrue($this->container->has_allowed(Array('x'=>'', 'bar'=>123)));
        $this->assertFalse($this->container->has_allowed(Array()));
        $this->assertFalse($this->container->has_allowed(Array('bar'=>123)));
        }

    function test_has_advanced() 
        {
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'list'=>'field_options' )  
            ));
        $this->assertFalse($this->container->has_advanced(Array('q'=>'bar')));
        
        $base = Array(
            'q[0][v]' => 'something', "q[0][index]"=>'title', "q[0][oper]"=>'and',
            'q[1][v]' => 'good', "q[1][index]"=>'default', "q[1][oper]"=>'or',
            'q[2][v]' => 'isgoingtohappen', "q[2][index]"=>'default', "q[2][oper]"=>'or',
            );
        $incoming = Array( 
            'q' => Array(
                Array( 'v' => 'something', 'index' => 'title' ),
                Array( 'v' => 'good', 'index' => 'default', 'oper' => 'or' ),
                Array( 'v' => 'isgoingtohappen', 'index' => 'default', 'oper' => 'or' ),
                ),
            );
            
        $this->assertTrue($this->container->has_advanced($incoming));
        $this->assertTrue($this->container->has_allowed($incoming));
        
        $this->container->set_values( $base );
        $expected = $this->container->get_qs_key_values();
        $this->container->set_values( $incoming );
        $actual = $this->container->get_qs_key_values();
        $this->assertEqualArray( $expected, $actual );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'q', 'list'=>'field_options', 'mode'=>QC_MODE_ADVANCED )
            ));
        $this->assertTrue($this->container->has_advanced(Array('q'=>'bar')));
        }
        
    function test_define_criteria_magic()
        {
        // Separate test for this as it's a kludge and likely to change...
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'list'=>'field_options' )  
            ));

        $basic = Array(
            'x' => 'something'
            );
        
        // Test criteria lists
        $good = Array(
            'x[0][v]' => 'foo', "x[0][index]"=>'title', "x[0][oper]"=>'and',
            'x[1][v]' => 'bar', "x[1][index]"=>'default', "x[1][oper]"=>'or',
            );
        $missing_start = Array(
            'x[7][q]' => 'bar', 'x[7][\'index\']'=>'default', 'x[7][oper]'=>'or',
            );
        $expect_defaults = Array(
            'x[0][q]' => 'foo',
            );
        $empty_query = Array(
            'x[0][q]' => '',
            );
        $too_high = Array(
            'x[8]' => 'foo',
            );

        
        $this->assertFalse($this->container->has_advanced($basic));
        
        // Magic criteria are recognised by has_advanced        
        $this->assertTrue($this->container->has_advanced($good));
        $this->assertTrue($this->container->has_advanced($missing_start));
        $this->assertFalse($this->container->has_advanced($expect_defaults));
        $this->assertFalse($this->container->has_advanced($empty_query));
        
        // in the new system, there is no such thing as too high
        $this->assertTrue($this->container->has_advanced($too_high));
        
        // Magic criteria are recognised by has_allowed
        $this->assertTrue($this->container->has_allowed($good));
        $this->assertTrue($this->container->has_allowed($missing_start));
        $this->assertTrue($this->container->has_allowed($expect_defaults));
        $this->assertTrue($this->container->has_allowed($empty_query));
        $this->assertTrue($this->container->has_allowed($too_high));
        
        // Magic criteria are not defaulted by set_criteria
        $this->container->set_values(Array());
        $this->assertEqualArray($this->container->get_qs_key_values(), Array());

        // // Magic criteria are accepted by set_criteria
        $this->container->set_values($good);
        $this->assertEqualArray($this->container->get_qs_key_values(), $good );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'list'=>'field_options' )  
            ));
        $this->container->set_values($expect_defaults);
        $this->assertEqualArray($this->container->get_qs_key_values(), Array('x' => 'foo'));//, "x[0]['oper']"=>'and'));
        
        $this->container->set_values($empty_query);
        $this->assertEqualArray($this->container->get_qs_key_values(), Array());
        
        $this->container->set_values($too_high);
        $this->assertEqualArray($this->container->get_qs_key_values(), Array('x[8][v]' => 'foo', "x[8][oper]"=>'and'));
        }
        
    function test_criteria_magic_set_values()
        {
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'y' ),
            Array( 'name' => 'x' ) ));
        
        $this->container->set_values( Array(
            'y' => 'fooz',
            'x_q1' => 'fooq', 'x_index1'=>'title',
            ) );
        
        $expected = Array(
            'y' => 'fooz',
            'x[0][v]' => 'fooq', "x[0][index]"=>'title', "x[0][oper]"=>'and',
            );
        $this->assertEqualArray($this->container->get_qs_key_values(), $expected );
        }
        
    function test_get_render_details()
        {
        global $CONF;
        $this->container = new QueryCriteria();
        $this->assertEqualArray( $this->container->get_render_details(), Array() );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'mode' ) ));
        $this->assertEqualArray( $this->container->get_render_details(), Array() );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'mode', 'value' => 'advanced' ) ));
        $expected = Array( Array('name' => 'mode', 'label'=>'mode', 'value'=>'advanced') );
        $this->assertEqualArray( $this->container->get_render_details(), $expected );
        
        $genre_list = Array( ''=>'-- Any Genre --', 'adv'=>'Advertisement', 'biog'=>'Biography' );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'date', 'label' => 'You selected', 
                    'type' => QC_TYPE_DATE_RANGE, 
                    'range' => Array( 1973, 1982 ),
                    'value' => 1977 ),
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category', 'value'=>'biog') ));
            
        /// NOTE AV : A weakness in this system: you can't change the date label, because it differs depending on the value
        $expected = Array(
            Array( 'name' => 'date', 'label' => 'Date from', 'value' => '1977 to 1982'),
            Array( 'name' => 'genre', 'label' => 'Category', 'value'=>'Biography' ),
            );
        $this->assertEqualArray( $this->container->get_render_details($genre_list), $expected );
        $genre_list = Array( ''=>'-- Any Genre --', 'adv'=>'Advertisement', 'biog'=>'Biography' );
        $text_list = Array( 'default'=>'All fields', 'title'=>'Title', 'description'=>'Description' );

        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'label' => 'State:' ),
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category', 'value'=>'biog') ));

        $this->container->set_values( Array(
            'x[0][v]' => 'foo', "x[0][index]"=>'title', "x[0][oper]"=>'and',
            'x[1][v]' => 'bar', "x[1][index]"=>'default', "x[1][oper]"=>'or',
            'genre' => 'adv',
            ));

        $expected = Array(
            Array( 'name' => 'x', 'label' => 'State:', 'value' => "'foo' in Title OR 'bar' in All fields" ),
            Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'Advertisement' ),
            );

        $this->assertEqualArray( $this->container->get_render_details( Array( 'x'=>$text_list, 'genre'=>$genre_list ) ), $expected );
        }
        
    function test_get_render_details_flag()
        {
        $flag_list = Array( 'Action', 'Adventure', 'Comedy', 'Romantic' );

        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'label' => 'State:' ),
            Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category') ));

        $this->assertEqualArray( $this->container->get_render_details($flag_list), Array() );

        $this->container->set_values( Array(
            'genre[0]' => '1', 'genre[1]' => 0, 'genre[2]' => 0, 'genre[3]' => '1',
            'x' => 'happy'
            ));

        $expected = Array(
            Array( 'name' => 'x', 'label' => 'State:', 'value' => "happy" ),
            Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'Action; Romantic' ),
            );
        $this->assertEqualArray( $this->container->get_render_details($flag_list), $expected );

        $media_list = Array( 1=>'Audio', 2=>'Film', 3=>'Multimedia', 4=>'Radio', 5=>'Television', 6=>'Video' );

        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category', 'list'=>'media_list' )
            ));
        $this->container->set_values( Array(
            'genre[1]' => '1', 'genre[5]' => '1', 'genre[3]' => '1', 
            ));
        $expected = Array(
            Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'Audio; Multimedia; Television' ),
            );
        $this->assertEqualArray( $this->container->get_render_details( Array( 'media_list'=>$media_list ) ), $expected );
        }
        
    function test_get_render_details_flag_single()
        {
        global $CONF;
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category') ));
        
        $this->assertEqualArray( $this->container->get_render_details(), Array() );
        
        $this->container->set_values( Array(
            'genre' => '1'
            ));
        $expected = Array(
            Array( 'name' => 'genre', 'label' => 'Category' ),
            );
        $this->assertEqualArray( $this->container->get_render_details(), $expected );
        }
        
    function test_get_render_details_flag_single_with_list()
        {
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_FLAG, 'label' => 'Category') ));
            
        $genre_list = Array( 'Advertisement', 'Biography', 'Documentary', 'Comedy' );
        
        $this->container->set_values( Array(
            'genre' => '1'
            ));
        
        $expected = Array(
            Array( 'name' => 'genre', 'label' => 'Category', 'value' => 'Biography' ),
            );
        $this->assertEqualArray( $this->container->get_render_details( Array('genre' => $genre_list) ), $expected );
        // print_var( $this->container['genre']->get_render_details( Array('genre' => $genre_list) ) );
        // print_var( $this->container->get_render_details( Array('genre' => $genre_list) ) );
        }
        
    function test_get_render_details_flag_non_integer_keys()
        {
        $film_list = Array( 'inception'=>'nolan', 'breathless'=>'godard', 'empire.s.b'=>'kershner', 'toy_story'=>'lasseter', 'bladerunner'=>'scott' );
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'director', 'type' => QC_TYPE_FLAG, 'label' => 'Directors', 'list'=>'film_list' )
                ));
        $this->container->set_values( Array(
            'director[bladerunner]' => '1', 'director[inception]' => '1', 'director[toy_story]' => '1', 
            ));
        $expected = Array(
            Array( 'name' => 'director', 'label' => 'Directors', 'value' => 'nolan; lasseter; scott' ),
            );
        $this->assertEqualArray( $this->container->get_render_details( Array( 'film_list'=>$film_list ) ), $expected );
        
        // attempt with an alternate way of setting values
        $this->container->set_values( Array(
                'director' => 'bladerunner'
            ));
        $expected = Array(
            Array( 'name' => 'director', 'label' => 'Directors', 'value' => 'scott' ),
            );
        $this->assertEqualArray( $this->container->get_render_details( Array( 'film_list'=>$film_list ) ), $expected );
        }
        
    
    
    function test_get_required_list_names()
        {
        $this->container = new QueryCriteria();
        $this->assertEqualArray( $this->container->get_required_list_names(), Array() );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST) ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array() );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'value'=>'biog') ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'genre' ) );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'list' => 'genre_list', 'value'=>'biog') ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'genre_list' ) );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'label' => 'State:' ),
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category', 'value'=>'biog') ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'genre' ) );
        
        $this->container->set_values( Array(
            'x[0][v]' => 'foo', "x[0][index]"=>'title', "x[0][oper]"=>'and',
            'x[1][v]' => 'bar', "x[1][index]"=>'default', "x[1][oper]"=>'or',
            'genre' => 'adv',
            ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'x', 'genre' ) );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'x', 'label' => 'State:', 'list'=>'list_advanced' ),
            Array( 'name' => 'genre', 'type' => QC_TYPE_LIST, 'label' => 'Category', 'value'=>'biog') ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'genre' ) );
        
        $this->container->set_values( Array(
            'x[0][v]' => 'foo', "x[0][index]"=>'title', "x[0][oper]"=>'and',
            'genre' => 'adv',
            ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'list_advanced', 'genre' ) );
        
        $this->container = new QueryCriteria(Array(
            Array( 'name' => 'media', 'type' => QC_TYPE_FLAG, 'label' => 'Media', 'list' => 'title_format') ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array( 'title_format' ) );
        }
        
    function test_get_required_list_names_from_inlined()
        {
        // Criteria with inline lists don't return required list names
        $this->container = new QueryCriteria(Array(
            Array(  'name' => 'genre',
                    'type' => QC_TYPE_LIST, 
                    'value' => 'biog',
                    'list' => Array( 'ac' => 'Action', 'ad' => 'Adventure', 'biog' => 'Biography' ) ) ));
        $this->assertEqualArray( $this->container->get_required_list_names(), Array() );
        }
        
        
    /// Asserts that two arrays are the same. Unlike assertEqual, this will also work on 'deep' arrays
    function assertEqualArray( $a1, $a2 )
        {
        if( $this->assertEqual( $a1, $a2 ) )
            return $this->assertEqual( serialize($a1), serialize($a2) );
        else
            return TRUE;
        }
        
    function test_date_range_set_from_elements()
        {
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) ));
        $this->assertEqual($this->container->query_string(), '');

        $this->container->set_values( Array('date_start'=>Array( 1 => '01', 2 => '06', 0 => '1977' ), 'date_end'=>Array( 1 => '12', 2=>'17', 0 => '1992' )) );
        $this->assertEqual($this->container->query_string(), 'date_start=1977-01-06&date_end=1992-12-17');
        }
        
    function test_date_range_set_from_elements_extended()
        {
        $this->container = new QueryCriteria(Array(
                Array( 'name' => 'date', 'type'=>QC_TYPE_DATE_RANGE, 'range'=>Array(1974,2010) ) ));
        $this->assertEqual($this->container->query_string(), '');

        $this->container->set_values( 
            Array(  'date_start'=>Array( 1 => '01', 2 => '06', 0 => '1977', 3 => '13', 4 => '59' ), 
                    'date_end'=>Array( 1 => '12', 2=>'17', 0 => '1992', 3 => '9', 4 => '12' )) );
        $this->assertEqual($this->container->query_string(), 'date_start=1977-01-06-13-59&date_end=1992-12-17-09-12');
        }
    }