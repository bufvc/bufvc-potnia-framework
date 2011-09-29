<?php
// $Id$
// HTTP endpoint to score a record
// James Fryer, 30 Aug 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

if (@$CONF['url'] == '')
    include '../../../web/include.php';

/* README: To submit a score for a record
    POST to this file with the data:
        record: The ID/url of the record to vote for
        score: The submitted score in the range +/-1
        redirect_url: The URL you want to redirect back to
*/

// Allow guests etc. to vote
$allow_guest_votes = $CONF['debug'] && !defined('UNIT_TEST');

$record_url =  @$_POST['record'];
$score =  @$_POST['score'];

if ($_SERVER['REQUEST_METHOD'] == 'POST')
    $redirect_url = @$_POST['redirect_url'];
if (@$redirect_url == '')
    $redirect_url = $CONF['url'];

if ($USER->has_right('save_data') || $allow_guest_votes)
    {
    $username = $USER->login;
    if ($allow_guest_votes)
        $username .= '.' . rand();
    $MODULE->update('/fed/stats' . $record_url, Array('username'=>$username, 'score'=>$score));
    }

header('Location: ' . $redirect_url, TRUE, 303);
