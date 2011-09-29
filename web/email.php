<?php
// $Id$
// Email results/marked records
// James Fryer, 2 Dec 2009
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/*### TODO
    - Speed limit
    - Mail header, footer
###*/

include './include.php';

$TITLE = 'Email Records';
$TEMPLATE = 'email_records';

// This variable contains the body of the email to be sent, without boilerplate
$EMAIL_BODY = NULL;

// How many emails have we sent so far this session?
$COUNT_EMAILS_SENT = @$_SESSION['COUNT_EMAILS_SENT'];

// Users can email marked records or search results based on URL
$mode = str_replace('/', '', @$_SERVER['PATH_INFO']);

$separator_length = 42;
$separator1 = str_repeat('=', $separator_length) ."\n";
$separator2 = str_repeat('-', $separator_length) ."\n";

$url_query = NULL;

// Does user have right to send email?
if (!$USER->has_right('save_data'))
    {
    // set status and error message
    header("HTTP/1.0 401 Unauthorized");
    $MESSAGE = 'You are not allowed to email records.'; //###$STRINGS['error_401_email'];
    $MESSAGE_CLASS = 'error-message';
    }

// Too many emails sent
else if ($COUNT_EMAILS_SENT >= $CONF['max_emails_per_session'])
    {
    $MESSAGE = 'You cannot send more emails in this session.';
    $MESSAGE_CLASS = 'error-message';
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
        header('HTTP/1.0 403 Forbidden');
    }

// User email not set
else if ($USER->email == '')
    {
    $MESSAGE = 'You must set your email address in preferences before you can email records'; //###$STRINGS['error_..._email'];
    $MESSAGE_CLASS = 'error-message';
    }

// Collect the data
else if ($mode == 'marked')
    {
    $TITLE = 'Email Marked Records';
    if ($MARKED_RECORDS->count() > 0)
        {
        $result = $MODULE->format_records($MARKED_RECORDS->get_all(), 'text', $CONF['marked_records_size'], $separator2);
        $EMAIL_BODY = $separator1 . $result . $separator1;
        $email_subject = 'Your marked records from BUFVC';
        }
    }
else if ($mode == 'search')
    {
    $TITLE = 'Email Search Results';
    if ($QUERY->has_allowed_criteria($_REQUEST))
        {
        $criteria = $_REQUEST;
        $criteria['page'] = 1;
        // NOTE AV : we alter the QCs default so that it doesn't appear in the query string - this might not be the best way ?
        $QUERY['page_size']->set_default( $CONF['max_export'] );
        $criteria['page_size'] = $CONF['max_export'];
        $results = $QUERY->search($criteria);
        $url_query = '?' . $QUERY->url_query();
        $tmp = Array();
        if ($results)
            {
            $result = $MODULE->format_records($QUERY->results, 'text', $CONF['max_export'], $separator2);
            $EMAIL_BODY = $separator1 . $result . $separator1;
            $email_subject = 'Your search results from BUFVC';
            }
        }
    }
else if ($mode == 'listings')
    {
    $TITLE = 'Email Listings';
    $listings = new $MODULE->listings_class();
    $query = $MODULE->get_session_data('LISTINGS_QUERY');
    if (is_null($query))
        $query = QueryFactory::create($MODULE, $MODULE->listings_query_config );
    $query->set_criteria_values(@$_REQUEST);
    $listings->process_criteria($query);
    // $criteria = $listings->process_criteria(@$_REQUEST, @$query->criteria['channel']);
    if ($query->has_allowed_criteria($_REQUEST))
        {
        $results = $query->search();
        $results = $listings->collate_results($query);
        $url_query = '?' . $query->url_query();
        $tmp = Array();
        if ($results)
            {
            $formatter = $MODULE->new_formatter('text');
            foreach ($results as $day)
                {
                foreach ($day as $result)
                    {
                    // build the url like this to help tests
                    $url = isset($result['prog_id']) ? '/prog/'.$result['prog_id'] : $result['url'];
                    $r = $MODULE->retrieve($url);
                    $tmp[] = $formatter->format($r);
                    }
                }
            $EMAIL_BODY = $separator1 . join($separator2, $tmp) . $separator1;
            $email_subject = 'Your search results from BUFVC';
            }
        }
    }
else if ($mode == 'index')
    {
    $TITLE = 'Email Record';
    $url = @$_REQUEST['url'];
    unset($_REQUEST['url']);
    $RECORD = $QUERY->get_record($url, @$_REQUEST);
    $url_query = $url;
    $req = @$_REQUEST;
    // remove extra request parameters
    $extra = Array('PHPSESSID', 'email', 'note', 'send', 'cancel');
    foreach ($extra as $item)
        unset($req[$item]);
    // if there were request parameters, include in the return query
    if (!empty($req))
        $url_query .= '?'.http_build_query($req);
    if (!is_null($RECORD))
        {
        $result = $MODULE->format_records(Array($RECORD), 'text', 1, $separator2);
        $EMAIL_BODY = $separator1 . $result . $separator1;
        $email_subject = 'Record: '. $RECORD['title'] .' from BUFVC';
        }
    }
else
    $mode = '';

// Send the email on POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $mode != '' && $EMAIL_BODY != '')
    {
    $recipient = @$_POST['email'];
    if ($recipient == '')
        {
        $MESSAGE = 'Invalid email address';
        $MESSAGE_CLASS = 'error-message';
        }
    else {
        if (@$_POST['cancel'] == '')
            {
            $email_headers = Array();
            $email_headers['From'] = $USER->email;
            $email_headers['To'] = $recipient;
            $email_headers['Subject'] = $email_subject;
            $email_headers['Content-Type'] = $MODULE->content_type('text');
            $body = $EMAIL_BODY;
            if (@$_POST['note'] != '')
                {
                $note = substr($_POST['note'], 0, $CONF['email_note_length']);
                $body = $note . "\r\n" . $body;
                }
            $MAILER->send($recipient, $email_headers, $body);
            $_SESSION['COUNT_EMAILS_SENT'] = $COUNT_EMAILS_SENT + 1;
            xlog(2, 'Sent email: From ' . $USER->email . ' to ' . $recipient, 'EMAIL');
            }
        header("Location: " . $MODULE->url($mode, $url_query), TRUE, 303);
        if (@$_POST['cancel'] == '')
            set_session_message(sprintf('Email has been sent to %s.', $recipient), 'info-message');
        exit();
        }
    }

// Display page
header('Content-Type: ' . $MODULE->content_type());
require_once $CONF['path_templates'] . $TEMPLATE . '.php';
