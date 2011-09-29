<?php
// $Id$
// ParsedQuery class
// James Fryer, 26 July 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Represents a query as a tree starting from the $root 
class ParsedQuery
    {
    /// The root node of tree representing the query string.
    /// This is parsed right-to-left so that e.g. A OR B AND C AND D...
    /// groups the OR clauses together modified by the string of AND clauses
    /// (parentheses can be used)
    var $root;
    
    // Message set if an error occurred
    var $error_message = '';

    function __construct($root=NULL)
        {
        $this->root = $root;
        }

    /// "Normalise" the tree, bringing all OR operators to the top.
    /// This uses De Morgan's Laws to rewrite the tree.
    /// I.e. convert ((A OR B) AND C) -> ((A AND C) OR (B AND C))
    ///              ((A OR B) NOT C) -> ((A NOT C) OR (B NOT C))
    ///              (A NOT (B OR C)) -> ((A NOT B) NOT C)
    /// see e.g. http://en.wikipedia.org/wiki/De_Morgan's_laws
    function normalise()
        {
        if ($this->root != NULL)
            $this->root = $this->root->normalise();
        }

    /// "Flatten" the tree, i.e. convert a tree to a list of individual queries
    /// which are to be combined with 'OR'. Each query is an array of clauses.
    /// Each clause contains the operator which joins it to the previous clause.
    /// E.g., the expression (a=w AND b=x) OR (c=y NOT d=z) will map to:
    /// 0:((oper:, index:a, relation:=, subject:w), (oper:AND, index:b, relation:=, subject:x)),
    /// 1:((oper:, index:c, relation:=, subject:y), (oper:NOT, index:d, relation:=, subject:z))
    /// NB the oper for the first element is always ''
    function flatten()
        {
        if ($this->error_message != '' || $this->root == NULL)
            return NULL;
        if (!$this->root->_is_normal())
            return $this->_set_error("Can't flatten un-normalised parse tree");
        // Walk the tree adding terminal nodes to the list
        $result = Array();
        $this->_flatten($result, $this->root);
        return $result;
        }

    // Traverse the tree to create a list of clause arrays
    function _flatten(&$result, $node)
        {
        // Have we found some non-OR clauses? In which case gather them
        if (isset($node->clause) || $node->oper != 'OR')
            {
            $clauses = Array();
            $this->_gather_clauses($clauses, $node);
            $result[] = $clauses;
            }
        // Otherwise, recurse down the tree
        else {
            $this->_flatten($result, $node->left);
            $this->_flatten($result, $node->right);
            }
        }

    // Collect all clauses from a node
    // The clauses include the operator which affects them
    // If 'index' is set only the clauses matching that index are returned, and 'oper' is ignored
    function _gather_clauses(&$result, $node, $oper='', $index='')
        {
        // Is it a clause? In which case add it
        if (isset($node->clause))
            {
            $clause = $node->clause;
            if ($index == '')
                $clause['oper'] = $oper;
            if ($index == '' || $index == $clause['index'])
                $result[] = $clause;
            }
        // Otherwise, recurse down the tree
        else {
            $this->_gather_clauses($result, $node->left, $oper, $index);
            $this->_gather_clauses($result, $node->right, $node->oper, $index);
            }
        }
    
    /// Return an array of clauses whose index matches the parameter
    function find($index)
        {
        if (is_null($this->root))
            return NULL;
        $clauses = Array();
        $this->_gather_clauses($clauses, $this->root, '', $index);
        return $clauses;
        }

    /// Add a node to the tree
    //### TODO: OR, NOT opers (AND is default)
    //### TODO: for convenience, add separate index/value/relation???
    function add($node)
        {
        if (is_null($this->root))
            $this->root = $node;
        else
            $this->root = new QP_TreeOper('AND', $this->root, $node);
        }
    
    /// Remove all clauses whose index matches the argument and rebalance the tree
    function remove($index)
        {
        // Only leaf nodes can be removed
        if (is_null($this->root))
            return;
        if (@$this->root->clause['index'] == $index)
            $this->root = NULL;
        else 
            $this->root = $this->_remove_helper($this->root, $index);
        }
    
    /// Remove one clause
    function remove_one($index, $value, $relation='=')
        {
        if (is_null($this->root))
            return;
        if (@$this->root->clause['index'] == $index
            && @$this->root->clause['relation'] == $relation
            && @$this->root->clause['subject'] == $value)
            $this->root = NULL;
        else
            $this->root = $this->_remove_helper($this->root, $index, $value, $relation);
        }
    
    /// Replace a specific clause with a given node
    function replace($index, $value, $relation, $node)
        {
        if (is_null($this->root))
            return;
        if (@$this->root->clause['index'] == $index
            && @$this->root->clause['relation'] == $relation
            && @$this->root->clause['subject'] == $value)
            $this->root = $node;
        else
            $this->root = $this->_replace_helper($this->root, $index, $value, $relation, $node);
        }
    
    /// Return a parsable string representation of the query
    function to_string()
        {
        if (is_null($this->root))
            return '';
        return $this->root->to_string();
        }
    
    /// Perform a simple match against the tree
    //### TODO: Does not examine operators
    //### TODO: QueryMatcher class??? would also be useful in memory DS
    function match($name, $value, $relation='=')
        {
        $clauses = $this->find($name);
        foreach ($clauses as $clause)
            {
            if (($clause['subject'] == $value || 
                    is_array($value) && in_array($clause['subject'], $value))
                 && $clause['relation'] == $relation)
                return TRUE;
            }
        }
    
    // if subject is specified then remove one, otherwise remove all
    function _remove_helper($node, $index_to_remove, $subject=NULL, $relation='=')
        {
        if (!isset($node->left->clause) && @$node->left)
            $node->left = $this->_remove_helper($node->left, $index_to_remove, $subject, $relation);
        if (!isset($node->right->clause) && @$node->right)
            $node->right = $this->_remove_helper($node->right, $index_to_remove, $subject, $relation);
        // remove one
        if (!is_null($subject))
            {
            if (@$node->left->clause['index'] == $index_to_remove
                && @$node->left->clause['relation'] == $relation
                && @$node->left->clause['subject'] == $subject)
                return $node->right;
            else if (@$node->right->clause['index'] == $index_to_remove
                && @$node->right->clause['relation'] == $relation
                && @$node->right->clause['subject'] == $subject)
                return $node->left;
            else
                return $node;
            }
        
        if (@$node->left->clause['index'] == $index_to_remove)
            return $node->right;
        else if (@$node->right->clause['index'] == $index_to_remove)
            return $node->left;
        else
            return $node;
        }
    
    // Recurse down the tree until the matching clause is found, when found replace with new node
    function _replace_helper($node, $index, $subject, $relation, $new_node)
        {
        if (!isset($node->left->clause) && @$node->left)
            $node->left = $this->_replace_helper($node->left, $index, $subject, $relation, $new_node);
        if (!isset($node->right->clause) && @$node->right)
            $node->right = $this->_replace_helper($node->right, $index, $subject, $relation, $new_node);
        if (@$node->left->clause['index'] == $index
            && @$node->left->clause['relation'] == $relation
            && @$node->left->clause['subject'] == $subject)
            $node->left = $new_node;
        else if (@$node->right->clause['index'] == $index
            && @$node->right->clause['relation'] == $relation
            && @$node->right->clause['subject'] == $subject)
            $node->right = $new_node;
        return $node;
        }
    
    // Need to clone the root node when cloning
    function __clone()
        {
        if (is_object($this->root))
            $this->root = clone $this->root;
        }
    
    // Set the error message
    function _set_error($message)
        {
        $this->error_message = $message;
        }
    }

// Base class for tree nodes
class QP_TreeNode
    {
    var $oper = NULL;

    // Return a normalised version of the node
    function normalise()
        {
        return $this;
        }

    // Is the tree normalised below this point?
    function _is_normal($parent_oper='OR')
        {
        return TRUE;
        }
    /// Return a parsable representation of the node
    function to_string()
        {
        }
    }

// A clause in the tree
class QP_TreeClause
    extends QP_TreeNode
    {
    function QP_TreeClause($index, $relation, $subject)
        {
        $this->clause = Array(
            'index'=>$index,
            'relation'=>$relation,
            'subject'=>$subject
            );
        }
    function to_string()
        {
        return sprintf('{%s%s%s}', $this->clause['index'], $this->clause['relation'], $this->clause['subject']);
        }
    // Test for equality with another QP_TreeClause object
    function equals($other)
        {
        return ($this->clause == @$other->clause);
        }
    }

// An operator in the tree
class QP_TreeOper
    extends QP_TreeNode
    {
    function QP_TreeOper($oper, $left, $right)
        {
        $this->oper = $oper;
        $this->left = $left;
        $this->right = $right;
        }

    // Return a normalised version of the node
    function normalise()
        {
        $result = $this;
        while (!$result->_is_normal())
            {
            $oper = $result->oper;
            // A NOT (B OR C) => (A NOT B) OR (A NOT C)
            if ($oper == 'NOT')
                {
                if ($result->left->oper == 'OR')
                    {
                    $result = new QP_TreeOper('OR',
                            new QP_TreeOper('NOT', $result->left->left, $result->right),
                            new QP_TreeOper('NOT', $result->left->right, $result->right));
                    }
                else if ($result->right->oper == 'OR')
                    {
                    $result = new QP_TreeOper('NOT',
                            new QP_TreeOper('NOT', $result->left, $result->right->left),
                            $result->right->right);
                    }
                }
            // A AND (B OR C) => (A AND B) OR (A AND C)
            else if ($oper == 'AND')
                {
                if ($result->left->oper == 'OR')
                    {
                    $result = new QP_TreeOper('OR',
                            new QP_TreeOper($oper, $result->left->left, $result->right),
                            new QP_TreeOper($oper, $result->left->right, $result->right));
                    }
                else if ($result->right->oper == 'OR')
                    {
                    $result = new QP_TreeOper('OR',
                            new QP_TreeOper($oper, $result->left, $result->right->left),
                            new QP_TreeOper($oper, $result->left, $result->right->right));
                    }
                }
            $result->left = $result->left->normalise();
            $result->right = $result->right->normalise();
            }
        return $result;
        }

    // Is the tree normalised below this point?
    function _is_normal($parent_oper='OR')
        {
        // Not normalised if we have a non-OR op above an OR, or if children are not normal
        return (!($parent_oper != 'OR' && $this->oper == 'OR') &&
            $this->left->_is_normal($this->oper) && $this->right->_is_normal($this->oper));
        }

    function to_string()
        {
        if ($this->oper == 'OR')
            return '('.$this->left->to_string() . $this->oper . $this->right->to_string().')';
        return $this->left->to_string() . $this->oper . $this->right->to_string();
        }
    
    // Need to clone each node when cloning
    function __clone()
        {
        $this->left = clone $this->left;
        $this->right = clone $this->right;
        }
    }
