<?php
// $Id$
// Unit test framework
// James Fryer, 8 Mar
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// If UNIT_TEST is defined, then we are running tests and require the autorun script
if (defined('UNIT_TEST'))
    require_once $CONF['path_lib'] . 'simpletest/autorun.php';
// Otherwise we are running on the other side of a web connection, so just define the constant
else    
    define('UNIT_TEST', 1);

require_once $CONF['path_lib'] . 'simpletest/unit_tester.php';
require_once $CONF['path_lib'] . 'simpletest/web_tester.php';
require_once $CONF['path_lib'] . 'simpletest/reporter.php';
require_once $CONF['path_lib'] . 'simpletest/mock_objects.php';
require_once $CONF['path_etc'] . 'config-unit_test.php';

/// A reporter that prints nothing unless a test fails
class QuietReporter
    extends TextReporter
    {
    function paintHeader($test_name)
        {
        // Store the test name for use later if we output anything
        $this->test_name = $test_name;
        }

    function paintFooter($test_name)
        {
        if ($this->getFailCount() + $this->getExceptionCount() > 0)
            {
            print "$test_name: Failures:\n";
            print "Test cases run: " . $this->getTestCaseProgress() .
                    "/" . $this->getTestCaseCount() .
                    ", Passes: " . $this->getPassCount() .
                    ", Failures: " . $this->getFailCount() .
                    ", Exceptions: " . $this->getExceptionCount() . "\n";
            }
        }

    function paintFail($message)
        {
        SimpleReporter::paintFail($message);
        if ($this->getFailCount() <= 10)
            {
            print  $this->test_name . ": $message\n";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
            print "\n";
            }
        }
    }

// Use the quiet reporter if verbose output has not been requested
if (!getenv('UNITTEST_VERBOSE'))
    SimpleTest::prefer(new QuietReporter());
