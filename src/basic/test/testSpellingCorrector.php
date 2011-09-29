<?php
// $Id$
// Tests for SpellingCorrector
// Phil Hansen, 2 August 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class SpellingCorrectorTestCase
    extends UnitTestCase
    {
    function setup()
        {
        $this->corrector = new SpellingCorrector();
        }
    
    function test_check()
        {
        // don't run tests if pspell is not available
        if (is_null($this->corrector->pspell_link))
            return;
        
        $tests = Array(
            '' => '',
            'test' => '',
            'test apple' => '',
            'ttest' => 'test',
            'ttest aple' => 'test apple',
            'test aple' => 'test apple',
            'this is a long test example' => '',
            'this is a lonng test exampe' => 'this is a long test example',
            // this test case ignores the first suggestions of 'tit lee' and 'tit-lee'
            'titlee' => 'title',
            'tit-le' => 'title',
            );
        foreach ($tests as $test=>$expected)
            {
            $this->assertEqual($this->corrector->check($test), $expected);
            }
        }
    }
