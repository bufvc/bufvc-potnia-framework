<?php
// $Id$
// QueryParser class
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('QP_AND', 3);
define('QP_OR', 2);
define('QP_NOT', 1);

// Error codes
define('QP_ERROR_QUERY', 1);
define('QP_ERROR_CLAUSE', 2);

// These files are mutually dependent
require_once('ParsedQuery.class.php');

/// A factory for parsers
//### TODO: Add JSON QP
class QueryParserFactory
    {
    /// Return the correct kind of parser, depending on the query string
    function new_parser($query_string, $index_defs=NULL)
        {
        // Default
        return new SimpleQueryParser($index_defs);
        }
    }

/// QueryParser classes generate ParsedQuery containing the query clauses in a tree structure
///
/// The SimpleQueryParser supports this format:
///     {} are used to delimit clauses
///     A clause is: index relation subject
///         e.g. {title=foo} (= is the only supported relation)
///     AND/OR/NOT can separate clauses (default is AND)
/// Ex: {default=foo}{title=bar}
class SimpleQueryParser
    {
    // Error status
    var $error_code = 0;
    var $error_message;

    function __construct($index_defs=NULL)
        {
        //### FIXME: Index defs only needed here for macros -- better to have separate expand_macros function in parsed query
        if (is_null($index_defs))
            $index_defs = Array();
        $this->index_defs = $index_defs;
        }

    // Split a query string into tokens
    function tokenize($query_string)
        {
        if ($query_string == '')
            return Array();
        $result = Array();
        // Split on braces and parentheses
        $tokens = preg_split('/(\(|\)|\{|\})/', $query_string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = Array();
        $in_braces = false;
        foreach ($tokens as $token)
            {
            // Ignore empty tokens
            if (trim($token)  == '')
                continue;

            // If we are not inside a brace clause, copy tokens to output
            else if (!$in_braces)
                {
                if ($token  == '{')
                    {
                    $in_braces = true;
                    $next_token = '';
                    }
                else if ($token  != '}')
                    $result[] = trim($token);
                }

            // Otherwise we are inside braces, collect tokens until we see closing brace
            // Note this means parentheses are ignored inside clauses
            else {
                if ($token == '}')
                    {
                    $in_braces = false;
                    $result[] = trim($next_token);
                    }
                else
                    $next_token .= $token;
                }
            }
        // Catch missing closing braces
        if ($in_braces)
            $result[] = trim($next_token);
        return $result;
        }

    /* Implement the following grammar (similar to the grammar below for individual queries):
     * Expression::=
     *   Atom
     *   Atom Atom
     *   Atom Op Atom
     * Atom::=
     *   Token
     *   '(' Expression ')'
     * Op::=
     *   'AND' | 'OR' | 'NOT'
     * Token::=
     *   Inside braces: any string
     *   Outside braces: Parentheses, space-delimited string
     */
    function parse($s)
        {
        if ($s != '')
            {
            $this->tokens = new QP_TokenList($this->tokenize($s));
            $result = $this->_get_expression();
            if ($this->error_message)
                return NULL;
            }
        return new ParsedQuery(@$result);
        }

    private function _get_expression()
        {
        // Get the start of the expression
        $result = $this->_get_atom();
        if (is_null($result) || (!is_object($result) && $result == ')'))
            return NULL;
        $oper = $this->_get_oper();

        // If this is the end of the expression, return the atom
        if (is_null($oper) || $oper == ')')
            return $result;

        // Loop through the remaining tokens
        while (!$this->tokens->is_empty())
            {
            // Get the next atom
            $atom = $this->_get_atom();
            if (is_null($atom))
                break;

            // Create a new operator with it
            $result = new QP_TreeOper($oper, $result, $atom);

            // Get the next oper
            $oper = $this->_get_oper();

            // Check end of expression
            if (is_null($oper) || $oper == ')')
                break;
            }
        return $result;
        }

    // Get an operator from the input stream
    private function _get_oper()
        {
        $oper = $this->tokens->next();
        $oper = strtoupper($oper);
        if ($oper == '')
            return NULL;
        else if ($oper == 'AND' || $oper == 'OR' || $oper == 'NOT' || $oper == ')')
            return $oper;
        // Defaults to AND
        else {
            $this->tokens->pushback();
            return 'AND';
            }
        }

    // Get an atom from the input stream
    private function _get_atom()
        {
        $token = $this->tokens->next();
        if ($token == '')
            return NULL;
        else if ($token == '(')
            return $this->_get_expression();
        else {
            // Create a Clause object
            $tmp = preg_split('/([=<>&]+)/', $token, -1, PREG_SPLIT_DELIM_CAPTURE);
            $index = NULL;
            $relation = '=';
            if (count($tmp) == 1)
                $subject = trim($tmp[0]);
            else if (count($tmp) == 2)
                {
                $index = trim($tmp[0]);
                $subject = trim($tmp[1]);
                }
            else {
                $index = trim($tmp[0]);
                $relation = trim($tmp[1]);
                // Remaining array elements form the subject
                $subject = trim(join('', array_slice($tmp, 2)));
                }
            // Handle default index
            if ($index == '')
                $index = 'default';
            // Expand macros
            else if (@$this->index_defs[$index]['type'] == 'macro')
                {
                $macro = $this->index_defs[$index]['value'];
                $macro = str_replace('?', $subject, $macro);
                $new_tokens = $this->tokenize('(' . $macro . ')');
                $this->tokens->insert($new_tokens, $this->tokens->curr_token);
                return $this->_get_atom();
                }

            return new QP_TreeClause($index, $relation, $subject);
            }
        }

    // Set error code and message
    function _set_error($code=0, $message='')
        {
        if ($code)
            $this->log(1, "Error: $code $message");
        $this->error_code = $code;
        $this->error_message = $message;
        }

   function log($level, $message)
        {
        xlog($level, $message, 'QUERYPARSER');
        }
    }

/// Helper class for parsers, manages token lists
class QP_TokenList
    {
    // The current token
    var $curr_token;

    function QP_TokenList($tokens)
        {
        $this->tokens = $tokens;
        $this->curr_token = 0;
        }

    // Get the next token
    function next()
        {
        $result = NULL;
        if (!$this->is_empty())
            $result = $this->tokens[$this->curr_token++];
        return $result;
        }

    // Push the current token back on the queue
    function pushback()
        {
        if ($this->curr_token > 0)
            $this->curr_token -= 1;
        }

    // Are there any tokens left?
    function is_empty()
        {
        return $this->curr_token >= count($this->tokens);
        }

    // Insert some new tokens
    function insert($new_tokens, $position=NULL)
        {
        if (is_null($position))
            $position = count($this->tokens);
        $left = array_slice($this->tokens, 0, $position);
        $right = array_slice($this->tokens, $position);
        $this->tokens = array_merge($left, $new_tokens, $right);
        }
    }
