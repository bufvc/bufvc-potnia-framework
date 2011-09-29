<?php
// $Id$
// Test case base class for parsers
// Phil Hansen, 18 Oct 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Base class for testing QueryParser/ParsedQuery objects
class BaseParserTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->parser = new SimpleQueryParser();
        }

    // The expected tree is an array. If an element contains 'oper' then it is
    // an operator and has 'left' and 'right'. Otherwise it's a clause and contains
    // 'index', 'relation' and 'subject'
    function assertTree($tree, $expected)
        {
        $this->assertNoError($tree);
        if (is_null($expected))
            $this->assertNull($tree->root);
        else
            $this->assertNode($tree->root, $expected);
        }

    function assertNode($node, $expected)
        {
        // Operator
        if (isset($expected['oper']))
            {
            $this->assertEqual(strtolower(get_class($node)), 'qp_treeoper', "Not a QP_TreeOper");
            $this->assertEqual($node->oper, $expected['oper'], "Wrong oper: %s");
            $this->assertNode($node->left, $expected['left']);
            $this->assertNode($node->right, $expected['right']);
            }
        // Clause
        else {
            $this->assertEqual(strtolower(get_class($node)), 'qp_treeclause', "Not a QP_TreeClause: " . strtolower(get_class($node)));
            $this->assertEqual($node->clause, $expected, "Bad clause: %s");
            }
        }

    function assertNoError($object)
        {
        $this->assertEqual($object->error_message, '', 'error message: ' . $object->error_message);
        }

    function assertError($object)
        {
        $this->assertNotEqual($object->error_message, '', 'error message');
        }
    }

?>
