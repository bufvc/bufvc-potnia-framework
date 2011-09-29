<?php
// $Id$
// Test send email page
// James Fryer, 2 Dec 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('UNIT_TEST', 1);
require_once('../web/include.php');

class EmailPageTestCase
    extends ImprovedWebTestCase
    {
    function setup()
        {
        $this->setMaximumRedirects(0); // don't follow redirects
        $module = Module::load('dummy');
        $this->url = $module->url('email');
        $this->search_url = $module->url('search');
        $this->marked_url = $module->url('marked');
        $this->listings_url = $module->url('listings');
        $this->index_url = $module->url('index');
        $this->recipient = 'recipient@example.com';
        $this->sender = 'sender@example.com';
        global $MAILER;
        $this->mailer = $MAILER;
        // Set user email
        $user = User::instance('editor');
        $user->email = $this->sender;
        $user->update();
        }

    function teardown()
        {
        // Clean up email
        $user = User::instance('editor');
        $user->email = '';
        $user->update();
        }

    function test_default()
        {
        $this->get($this->url);
        $this->assertPage('Email Records');
        $this->assertTag('p', 'No records to email');
        }

    function test_default_marked()
        {
        $this->get($this->url . '/marked');
        $this->assertPage('Email Marked Records');
        $this->assertTag('p', 'No records to email');
        }

    function test_bad_get()
        {
        $this->get($this->url . '/foobar');
        $this->assertPage('Email Records');
        $this->assertTag('p', 'No records to email');
        }

    function test_marked()
        {
        global $CONF;
        // Mark a record
        $data = Array('mark_record' => 1, 'url' => '/dummy/test/single');
        $this->post($this->marked_url, $data); // post the data to the marked page

        // Get the page -- we now have a form
        $this->get($this->url . '/marked');
        $this->assertPage('Email Marked Records');
        $this->assertNoTag('p', 'No records to email');
        // The marked records are included to show the user
        $this->assertTag('pre', 'Title: *single');

        // Set the form fields
        $this->assertField('email', $this->sender);
        $this->setField('email', $this->recipient);

        // Send the email
        $this->click('Send Email');

        // Redirected back to marked records page
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->marked_url, "** MAKE SURE $CONF[path_var]tmp IS WRITABLE **");

        // Email has been sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertNotEqual($mail, '');
        $this->assertTrue(preg_match('/^From: *' . $this->sender . '/im', $mail));
        $this->assertTrue(preg_match('/^To: *' . $this->recipient . '/im', $mail));
        $this->assertTrue(preg_match('/^Subject: *Your marked records from BUFVC/im', $mail));
        $this->assertTrue(preg_match('/^Title: *single/im', $mail));

        // Clean up
        $this->mailer->cleanup($this->recipient);
        }

    function test_default_search()
        {
        $this->get($this->url . '/search');
        $this->assertPage('Email Search Results');
        $this->assertTag('p', 'No records to email');
        $this->get($this->url . '/search?q=notfound');
        $this->assertPage('Email Search Results');
        $this->assertTag('p', 'No records to email');
        }

    function test_search()
        {
        // Get the page with search results
        $this->get($this->url . '/search?q=single');
        $this->assertPage('Email Search Results');
        $this->assertNoTag('p', 'No records to email');
        $this->assertTag('pre', 'Title: *single');

        // Set the form fields
        $this->assertField('email', $this->sender);
        $this->setField('email', $this->recipient);
        
        // Send the email
        $this->click('Send Email');

        // Redirected back to marked records page
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->search_url . '?q=single');
        
        // Email has been sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertNotEqual($mail, '');
        $this->assertTrue(preg_match('/^From: *' . $this->sender . '/im', $mail));
        $this->assertTrue(preg_match('/^To: *' . $this->recipient . '/im', $mail));
        $this->assertTrue(preg_match('/^Subject: *Your search results from BUFVC/im', $mail));
        $this->assertTrue(preg_match('/^Title: *single/im', $mail));

        // Clean up
        $this->mailer->cleanup($this->recipient);
        }

    function test_default_listings()
        {
        $this->get($this->url . '/listings');
        $this->assertPage('Email Listings');
        $this->assertTag('p', 'No records to email');
        
        $this->get($this->url . '/listings?date=1955-09-01');
        $this->assertPage('Email Listings');
        $this->assertTag('p', 'No records to email');
        }
    
    // the listings mode works with TvtipListings however
    // TriltListings is used in tests, hack it here to work
    // with trilt
    function test_listings()
        {
        // Get the page with results
        //### FIXME: 2011-01-12 this test fails if it is the 12th of the month!
        $this->get($this->url . '/listings?date=2009-06-12&time=0&channel=54');
        $this->assertPage('Email Listings');
        $this->assertNoTag('p', 'No records to email');
        // loose test for trilt (only url is present)
        $this->assertTag('pre', 'URL: *http');
        
        // Set the form fields
        $this->assertField('email', $this->sender);
        $this->setField('email', $this->recipient);
        
        // Send the email
        $this->click('Send Email');

        // Redirected back to marked records page
        $this->assertResponse(303);
        $query = '?date=2009-06-12%2C2009-06-13&channel=[54]=1';
        $query = '?date_start=2009-06-12&date_end=2009-06-13&time=0&channel[54]=1';
        $this->assertHeader('Location', $this->listings_url . $query);

        // Email has been sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertNotEqual($mail, '');
        $this->assertTrue(preg_match('/^From: *' . $this->sender . '/im', $mail));
        $this->assertTrue(preg_match('/^To: *' . $this->recipient . '/im', $mail));
        $this->assertTrue(preg_match('/^Subject: *Your search results from BUFVC/im', $mail));
        // loose test for trilt (only url is present)
        $this->assertTrue(preg_match('/^URL: *http/im', $mail));

        // Clean up
        $this->mailer->cleanup($this->recipient);
        }
    
    function test_default_index()
        {
        $this->get($this->url . '/index');
        $this->assertPage('Email Record');
        $this->assertTag('p', 'No records to email');
        $this->get($this->url . '/index?url=notfound');
        $this->assertPage('Email Record');
        $this->assertTag('p', 'No records to email');
        }

    function test_index()
        {
        // Get the page with search results
        $this->get($this->url . '/index?url=dummy/test/single');
        $this->assertPage('Email Record');
        $this->assertNoTag('p', 'No records to email');
        $this->assertTag('pre', 'Title: *single');

        // Set the form fields
        $this->assertField('email', $this->sender);
        $this->setField('email', $this->recipient);

        // Send the email
        $this->click('Send Email');

        // Redirected back to view record screen
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->index_url . '/test/single');

        // Email has been sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertNotEqual($mail, '');
        $this->assertTrue(preg_match('/^From: *' . $this->sender . '/im', $mail));
        $this->assertTrue(preg_match('/^To: *' . $this->recipient . '/im', $mail));
        $this->assertTrue(preg_match('/^Subject: *Record:/im', $mail));
        $this->assertTrue(preg_match('/^Title: *single/im', $mail));

        // Clean up
        $this->mailer->cleanup($this->recipient);
        }

    function test_note()
        {
        $this->get($this->url . '/search?q=single');
        $this->setField('email', $this->recipient);
        $this->assertField('note', '');
        $this->setField('note', 'test notexxxtruncated');
        $this->click('Send Email');
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertTrue(preg_match('/^test notexxx/im', $mail));
        $this->assertFalse(preg_match('/^test notexxxt/im', $mail));
        $this->mailer->cleanup($this->recipient);
        }

    function test_email_not_set()
        {
        $user = User::instance('editor');
        $user->email = '';
        $user->update();
        $this->get($this->url . '/search?q=single');
        // Workround for missing 'assertNoField' function...
        $browser = $this->getBrowser();
        $this->assertTrue($browser->getField('email') === NULL);
        $this->assertTag('div', 'You must set your email address in preferences before you can email records', 'class', 'error-message');
        }

    function test_logged_in_users_only()
        {
        $this->get($this->url . '/?debug_user=guest');
        $this->assertPage('Email Records', 401);
        $this->assertTag('div', 'You are not allowed to email records', 'class', 'error-message');
        }

    function test_missing_email()
        {
        $this->post($this->url . '/search?q=single', Array('email'=>''));
        $this->assertPage('Email Search Results');
        $this->assertTag('div', 'Invalid email address', 'class', 'error-message');
        }

    function test_post_requires_mode()
        {
        $post_args = Array('email'=>$this->recipient);

        // Post the form with no mode
        $this->post($this->url, $post_args);

        // No email was sent
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');

        // Bogus mode
        $this->post($this->url . '/foobar', $post_args);
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');
        }

    function test_post_requires_data()
        {
        $post_args = Array('email'=>$this->recipient);

        // Search
        $this->post($this->url . '/search?q=notfound', $post_args);
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');

        // Marked
        $this->post($this->url . '/marked', $post_args);
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');
        }

    function test_cancel_button()
        {
        // Get the page with search results
        $this->get($this->url . '/search?q=single');
        $this->setField('email', $this->recipient);

        // Cancel redirects but does not send mail
        $this->click('Cancel');
        $this->assertResponse(303);
        $this->assertHeader('Location', $this->search_url . '?q=single');
        $mail = $this->mailer->get_last_mail($this->recipient);
        $this->assertEqual($mail, '');
        }

    function test_session_limit()
        {
        global $CONF;
        $this->assertEqual($CONF['max_emails_per_session'], 2);
        $post_data = Array('email'=>$this->recipient);

        // Send two, that's your lot.
        $this->post($this->url . '/search?q=single', $post_data);
        $this->assertResponse(303);
        $this->post($this->url . '/search?q=single', $post_data);
        $this->assertResponse(303);

        // GET shows a message and no form...
        $this->get($this->url . '/search?q=single');
        $this->assertTag('div', 'You cannot send more emails in this session.', 'class', 'error-message');

        // Attempt to post is rejected
        $this->post($this->url . '/search?q=single', $post_data);
        $this->assertResponse(403);
        $this->assertTag('div', 'You cannot send more emails in this session.', 'class', 'error-message');

        $this->mailer->cleanup($this->recipient);
        }
    
    function test_charset()
        {
        $module = Module::load('dummy');
        $this->get($this->url . '/search?q=single');
        $this->setField('email', $this->recipient);
        $this->click('Send Email');
        $mail = $this->mailer->get_last_mail($this->recipient);
        // confirm the charset has been set (escape the forward slash)
        $this->assertTrue(preg_match('/^Content-Type: *'. str_replace('/', '\/', $module->content_type('text')) .'/im', $mail));
        $this->mailer->cleanup($this->recipient);
        }
    }
