<?php
// $Id$
// Test bot detector
// James Fryer, 23 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../../../web/include.php');

class IdentifyBotTestCase
    extends UnitTestCase
    {
    function test_identify_bot()
        {
        $tests = Array(
            'foobar' => NULL,
            'Yahoo! Slurp' => 'yahoo',
            'Googlebot/2.1' => 'google',
            '(Twiceler-0.9 http://www.cuil.com/twiceler/robot.html)' => 'cuil',
            'msnbot/1.1' => 'bing',
            'FAST Enterprise Crawler 6 / Scirus scirus-crawler@fast.no;' => 'scirus',
            );
        foreach ($tests as $ua=>$expected)
            {
            $r = identify_bot($ua);
            $this->assertEqual(identify_bot($ua), $expected, "<$ua> -> <$r>; expected <$expected>");
            }
        }
	}
