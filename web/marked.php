<?php
// $Id$
// View Marked Records
// Phil Hansen, 17 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $STRINGS['marked_title'];
$TEMPLATE = 'marked';

// Get the output format
$output_format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'html';

// If the format is email, redirect to the 'send email' page
if ($output_format == 'email')
    {
    header("Location: " . $MODULE->url('email', '/marked'), TRUE, 303);
    exit();
    }

// check for a form POST
else if (is_array($_POST) && count($_POST) > 0)
    {
    // Mark/unmark search results
    if (isset($_POST['mark_results']))
        {
        // remove the submit value
        unset($_POST['mark_results']);

        // get the list of all results that were on the page
        if (isset($_POST['results']))
            {
            $allResults = explode(';', $_POST['results']);
            unset($_POST['results']);
            }
        else // possible error?
            $allResults = Array();

        // loop through each result checking for any records that were unmarked
        // we do all removals as a first pass so that when we start adding records we can be sure of our record count (for the limit)
        foreach ($allResults as $url)
            {
            if (!isset($_POST[$url]) && $MARKED_RECORDS->exists($url)) // this record was unmarked
                $MARKED_RECORDS->remove($url); // remove the record
            }

        // loop through each record that was checked on the page
        foreach ($_POST as $url=>$value)
            {
            // has not been marked yet
            if (!$MARKED_RECORDS->exists($url) && in_array($url, $allResults))
                {
                if ($MARKED_RECORDS->can_add($url))
                    $MARKED_RECORDS->add($url);
                else
                    $MESSAGE = $STRINGS['error_records_limit'];
                }
            }

        set_session_message($STRINGS['marked_update'], 'info-message');
        }

    // mark all search results in Query
    else if (isset($_POST['mark_all_results']))
        {
        // clone the query object and run the search with a larger page size
        $large_query = clone($QUERY);
        $large_query->set_page_size( $CONF['marked_records_size'], TRUE );
        // $large_query->page_size = $CONF['marked_records_size'];
        $large_query->search();
        // add each result if not already marked
        foreach ($large_query->results as $r)
            {
            // Get module for this record
            if (isset($r['module']))
                $module = $r['module'];
            else
                $module = $MODULE;
            $url = $r['url'];
            if (!$MARKED_RECORDS->exists($url) && $MARKED_RECORDS->can_add($url))
                $MARKED_RECORDS->add($url);
            }
        set_session_message($STRINGS['marked_update'], 'info-message');
        }
    
    // mark all records on viewed records history screen
    else if (isset($_POST['mark_all_viewed']))
        {
        $records = Array();
        if (isset($_POST['results']))
            $records = explode(';', $_POST['results']);
        foreach ($records as $url)
            {
            if (!$MARKED_RECORDS->exists($url) && $MARKED_RECORDS->can_add($url))
                $MARKED_RECORDS->add($url);
            }
        set_session_message($STRINGS['marked_update'], 'info-message');
        }

    // mark a specified record
    else if (isset($_POST['mark_record']))
        {
        $from_ajax = @$_POST['ajax'];
        $url = $_POST['url'];
        // Has not been marked yet
        if (!$MARKED_RECORDS->exists($url))
            {
            if ($MARKED_RECORDS->can_add($url))
                {
                $MARKED_RECORDS->add($url);
                $status_message = $STRINGS['record_mark'];
                if (!$from_ajax)
                    set_session_message($status_message, 'info-message');
                else
                    $ajax_record = $MARKED_RECORDS->get($url);
                }
            // records are full
            else {
                $status_message = $STRINGS['error_records_limit'];
                if (!$from_ajax)
                    set_session_message($status_message, 'error-message');
                }
            }
        }

    // unmark a specified record
    else if (isset($_POST['unmark_record']))
        {
        $from_ajax = @$_POST['ajax'];
        $url = $_POST['url'];
        if ($MARKED_RECORDS->exists($url))
            {
            if ($from_ajax)
                $ajax_record = $MARKED_RECORDS->get($url);
            $MARKED_RECORDS->remove($url); // remove the record
            $status_message = $STRINGS['record_unmark'];
            if (!$from_ajax)
                set_session_message($status_message, 'info-message');
            }
        }

    // Unmark all records
    else if (isset($_POST['unmark_all']))
        {
        // clear marked list
        $MARKED_RECORDS = new MarkedRecord();
        }

    // Mark/unmark from marked page
    else if (isset($_POST['update']))
        {
        $records = $MARKED_RECORDS->get_all();
        foreach ($records as $url=>$value) // loop through each marked record
            {
            if (!isset($_POST[$url])) // this record was removed
                $MARKED_RECORDS->remove($url);
            }
        }

    $_SESSION['MARKED_RECORDS'] = $MARKED_RECORDS; // store the marked records back in session

    // if we came from an ajax call, return a JSON encoded object
    // The JSON contains fields:
    //   message: status message
    //   title: record title
    //   location: full URL for the record
    if (@$from_ajax)
        {
        $data = Array();
        $data['message'] = (@$status_message) ? $status_message : '';
        if (isset($ajax_record))
            {
            $data['title'] = @$ajax_record['title'];
            $data['location'] = $ajax_record['module']->url('index', $ajax_record['url']);
            }
        header('Content-Type: ' . $MODULE->content_type('json'));
        echo json_encode($data);
        exit();
        }

    if (isset($_POST['redirect_url']))
        {
        // redirect to the specified url and return status 303
        header("HTTP/1.1 303 See Other");
        header("Location: " . $_POST['redirect_url']);
        exit();
        }
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type($output_format));
// look for a formatter
$formatter = $MODULE->new_formatter($output_format);
if (!is_null($formatter))
    {
    header('Content-Disposition: attachment; filename='.date('Ymd').'-marked_records'.$formatter->file_ext);
    print $MODULE->format_records($MARKED_RECORDS->get_all(), $output_format, $CONF['marked_records_size']);
    exit;
    }
// look for template
else
    require_once $MODULE->find_template($TEMPLATE, $output_format);
