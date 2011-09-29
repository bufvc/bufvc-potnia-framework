<?php
// $Id$
// Rewrite Boolean queries from infix form
// James Fryer, 19 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Convert a Boolean query from infix form to the form used 
/// in MySQL fulltext search, Sphinx Boolean search, etc.
/// 
/// This is tricker than it sounds because of operator precedence and other issues.
/// The following conversions are made:
///    x y      -> +x +y
///    x AND y  -> +x +y
///    x OR y   -> x y
///    x NOT y  -> +x -y
/// Operator precedence is AND, OR, NOT
class BooleanQueryRewriter
    {
    // Input tokens
    var $tokens;

    // Error message, set on error
    var $error_message = NULL;

    function convert($subject_str)
        {
        $subject_str = trim($subject_str);
        if ($subject_str == '')
            return NULL;
        $this->tokens = $this->tokenize($subject_str);
        $result = $this->_get_expression();
        if ($this->error_message)
            return NULL;
        return $result;
        }

    /* Implement the following grammar:
     * Expression::=
     *   Atom
     *   Atom Op Expression
     * Atom::=
     *   Token
     *   '(' Expression ')'
     * Op::=
     *   'AND' | 'OR' | 'NOT'
     * Token::=
     *   Any printing chars except '"', '(', ')'
     *   '"' Any chars '"'
     */

    // Read an expression until one of the following:
    //  1. End of tokens
    //  2. Higher-priority operator found
    //  3. Closing brackets found
    function _get_expression()
        {
        // Get the start of the expression
        $atom = $this->_get_atom();
        if (is_null($atom) || $atom == ')')
            return '';
        $oper = $this->_get_oper();

        // If this is the end of the expression, return the atom
        if (is_null($oper) || $oper == ')')
            return $atom;

        // Format the result string
        $tmp_oper = ($oper == QP_OR) ? QP_OR : QP_AND;
        $result = $this->format_oper($tmp_oper, $atom, TRUE);

        // Loop through the remaining tokens
        while (!$this->tokens->is_empty())
            {
            $prev_oper = $oper;
            $atom = $this->_get_atom();
            if (is_null($atom))
                return $result;
            $oper = $this->_get_oper();

            // End of expression
            if (is_null($oper) || $oper == ')')
                {
                $result .= ' ' . $this->format_oper($prev_oper, $atom);
                break;
                }

            // Same or lower priority
            else if ($oper <= $prev_oper)
                $result .= ' ' . $this->format_oper($prev_oper, $atom);

            // Higher priority
            else {
                // Push back the atom and operator and get the higher-priority expression
                $this->tokens->pushback();
                $this->tokens->pushback();
                $expr = $this->_get_expression();
                $result .= ' ' . $this->format_oper($prev_oper, "($expr)");
                break;
                }
            }
        return $result;
        }

    // Get an operator from the input stream
    function _get_oper()
        {
        // Defaults to AND
        $oper = $this->tokens->next();
        if (is_null($oper))
            return NULL;
        else if ($oper == QP_AND || $oper == QP_OR || $oper == QP_NOT || $oper == ')')
            return $oper;
        else {
            $this->tokens->pushback();
            return QP_AND;
            }
        }

    // Get an atom from the input stream
    function _get_atom()
        {
        $lhs = $this->tokens->next();
        if (is_null($lhs))
            return NULL;
        else if ($lhs == '(')
            {
            $expr = $this->_get_expression();
            return "($expr)";
            }
        else
            return $lhs;
        }

    // Format a value with the correct MySQL operator (+, -, blank)
    function format_oper($oper, $val)
        {
        switch ($oper)
            {
        default:
        case QP_AND:
            return '+' . $val;
        case QP_OR:
            return $val;
        case QP_NOT:
            return '-' . $val;
            }
        }

    // Split a string into tokens
    function tokenize($s)
        {
        // Get the 'raw' tokens including delimiters
        $token_def = '/(\s+|\(|\)|\")/';
        $raw_tokens = preg_split($token_def, $s, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Cook the tokens by removing spaces and joining quoted strings
        $tokens = Array();
        $in_quotes = FALSE;
        $quoted_str = '';
        foreach ($raw_tokens as $token)
            {
            // Handle quoted strings
            if (!$in_quotes)
                {
                if ($token == '"')
                    {
                    $in_quotes = TRUE;
                    $quoted_str = '';
                    }
                else {
                    $token = trim($token);
                    $uctok = strtoupper($token);

                    // Handle operators
                    if ($uctok == 'AND')
                        $tokens[] = QP_AND;
                    else if ($uctok == 'OR')
                        $tokens[] = QP_OR;
                    else if ($uctok == 'NOT')
                        $tokens[] = QP_NOT;

                    // Handle other tokens
                    else if ($token != '')
                        $tokens[] = addslashes($token);
                    }
                }

            // Inside a quoted string, collect tokens until it ends
            else {
                if ($token == '"')
                    {
                    $in_quotes = FALSE;
                    $tokens[] = '"' . addslashes($quoted_str) . '"';
                    }
                else
                    $quoted_str .= $token;
                }
            }
        if ($in_quotes)
            $this->error_message = "Missing quotation marks";
        return new QP_TokenList($tokens);
        }
    }

/// Sphinx accepts this format:
///    x y      -> x y
///    x AND y  -> x y
///    x OR y   -> x | y
///    x NOT y  -> x -y
class SphinxBooleanQueryRewriter
    extends BooleanQueryRewriter
    {
    // Format a value with the correct MySQL operator (+, -, blank)
    function format_oper($oper, $val, $at_start=FALSE)
        {
        switch ($oper)
            {
        default:
        case QP_AND:
            return $val;
        case QP_OR:
            if ($at_start)
                return $val;
            else                
                return '| ' . $val;
        case QP_NOT:
            return '-' . $val;
            }
        }
    }
