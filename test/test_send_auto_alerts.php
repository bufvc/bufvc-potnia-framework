<?php
// $Id$
// Test send auto-alerts
// Phil Hansen, 24 May 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

// This test case uses the ImprovedWebTestCase in order to easily 
// setup the auto-alert saved search.
class AutoAlertsTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        global $MAILER, $CONF, $MODULE, $STRINGS;
        $this->recipient = 'recipient@example.com';
        $this->sender = $CONF['contact_email'];
        $this->mailer = $MAILER;
        $this->saved_url = $MODULE->url('saved');
        $this->search_url = $MODULE->url('search');
        $this->save_button = $STRINGS['save_search_button'];
        // Set user email
        $user = User::instance($CONF['default_user']);
        $user->email = $this->recipient;
        $user->update();
        }
    
    function teardown()
        {
        global $CONF;
        // Clean up email
        $user = User::instance($CONF['default_user']);
        $user->email = '';
        $user->update();
        
        $user->save_data('saved_searches', new QueryList());
        }
    
    // Helper - create the auto-alert saved search
    function add_auto_alert()
        {
        // add an auto-alert
        $this->get($this->search_url . '?q=many');
        $this->click($this->save_button);
        }
    
    // Helper - set the auto-alert sending day
    function set_auto_alert_day($day)
        {
        $data = Array('set_day'=>1, 'day'=>$day);
        $this->post($this->saved_url, $data);
        }
    
    // No return output and no email sent
    function test_default()
        {
        $result = `../bin/send_auto_alerts`;
        $this->assertTrue(empty($result));
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');
        }
    
    function test_sent()
        {
        global $MODULE;
        // add an auto-alert search
        $this->add_auto_alert();
        $day = get_current_day_of_the_week();
        $false_day = $day - 1;
        if ($day == 1)
            $false_day = 7;
        // set incorrect date
        $this->set_auto_alert_day($false_day);
        // send auto-alerts (no output, no email)
        $result = `../bin/send_auto_alerts`;
        $this->assertTrue(empty($result));
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');
        
        // now set correct day and send auto-alerts
        $this->set_auto_alert_day($day);
        $result = `../bin/send_auto_alerts`;
        $this->assertTrue(empty($result)); // no output
        // email has been sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertNotEqual($mail, '');
        $this->assertTrue(preg_match('/^From: *' . $this->sender . '/im', $mail));
        $this->assertTrue(preg_match('/^To: *' . $this->recipient . '/im', $mail));
        $this->assertTrue(preg_match('/^Subject: Auto-Alert search results/im', $mail));
        $this->assertTrue(preg_match('/^Results: 25/im', $mail));
        $this->assertTrue(preg_match('/^The number of results returned has been limited/im', $mail));
        // confirm the charset has been set (escape the forward slash)
        $this->assertTrue(preg_match('/^Content-Type: *'. str_replace('/', '\/', $MODULE->content_type('text')) .'/im', $mail));
        // Clean up
        $this->mailer->cleanup($this->recipient);
        }
    }
