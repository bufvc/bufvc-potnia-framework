<?php
// $Id$
// Spelling Corrector
// Phil Hansen, 2 August 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Contains functions for checking spelling and offering corrections
/// This class uses Pspell
class SpellingCorrector
    {
    // pspell object
    var $pspell_link;
    
    // dictionary to use
    var $dict = "en_GB";
    
    function __construct()
        {
        if (!function_exists('pspell_new'))
            $this->pspell_link = NULL;
        else
            $this->pspell_link = pspell_new($this->dict, "", "", "", PSPELL_FAST);
        }
    
    /// Check each word in the given string against the dictionary
    /// If a word is incorrect then get a suggested spelling
    /// Returns NULL if pspell is not available
    /// Returns '' if all words are correct or there are no suggestions to offer
    /// Otherwise returns a new string with the incorrect words replaced with a suggested spelling
    function check($string)
        {
        if (is_null($this->pspell_link))
            return NULL;
        
        $words = explode(' ', $string);
        $new_words = Array();
        foreach ($words as $word)
            {
            if (!pspell_check($this->pspell_link, $word))
                {
                $suggestions = pspell_suggest($this->pspell_link, $word);
                // get suggestions from pspell
                // for now simply use the first suggestion in the list
                if (count($suggestions) > 0)
                    {
                    $has_hyphen = (strpos($word, '-') !== FALSE);
                    $suggestion = $suggestions[0];
                    // look for a suggestion that is one word (not two as pspell sometimes does)
                    // also ignore suggestions with hyphens if the given word is not hyphenated
                    if (strpos($suggestion, ' ') !== FALSE || (!$has_hyphen && strpos($suggestion, '-') !== FALSE))
                        {
                        // only search through the first 20 suggestions
                        $count = min(20, count($suggestions));
                        for ($i = 1; $i < $count; $i++)
                            {
                            if (strpos($suggestions[$i], ' ') === FALSE && $has_hyphen)
                                {
                                $suggestion = $suggestions[$i];
                                break;
                                }
                            else if (strpos($suggestions[$i], ' ') === FALSE && !$has_hyphen && strpos($suggestions[$i], '-') === FALSE)
                                {
                                $suggestion = $suggestions[$i];
                                break;
                                }
                            }
                        }
                    $new_words[] = strtolower($suggestion);
                    }
                else
                    $new_words[] = $word;
                }
            // keep correct words
            else
                $new_words[] = $word;
            }
        $suggestion = join(' ', $new_words);
        return ($suggestion != $string) ? $suggestion : '';
        }
    }
?>