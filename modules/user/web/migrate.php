<?php // $Id$
// Front end for user migration (standalone)
// James Fryer, 24 July 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Avoid opening database and login checks
$dont_open_database = TRUE;
require '../../../web/include.php';
require_once($MODULE->path . 'src/UserMigrator.class.php');

$TITLE = 'BUFVC account migration tool';
$URL = $MODULE->url('migrate');

// check permissions
if (!$MODULE->has_right($USER))
    {
    // set status and error message
    header("HTTP/1.0 401 Unauthorized");
    $MESSAGE = $STRINGS['error_401'];
    $MESSAGE_CLASS = 'error-message';
    require_once $MODULE->find_template('error');
    exit();
    }

// Figure out which function we are calling

// Step 1, get the list of emails from the user
if ($_SERVER['REQUEST_METHOD'] == 'GET')
    $function = 'start';

// Step 2, ask the user to confirm the action
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['confirmed']))
    $function = 'confirm';

// Step 3, tell the user what happened
else if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['confirmed'])
    $function = 'migrate';

// No idea what to do now
else
    $function = 'error';

// Output the page
include $CONF['path_templates'] . 'inc-header.php';
print $function();
include $CONF['path_templates'] . 'inc-footer.php';

/// Step 1: Enter emails
function start()
    {
    return <<< _EOT_
<h3>Step 1 of 3: Enter emails</h3>
<p>Use this page to migrate one or more users' data, rights, preferences and auto-alerts to a new account.
The user must have set up their new email address before this page can be used.
<p>Enter email addresses, one per line, in the form below. Then press the 'Migrate' button.
<p>You will see the actions that will be taken, and have a chance to confirm them, before proceeding.
<form method="POST">
<textarea name="emails_text" rows="15" cols="40">
</textarea><br>
<input type="submit" value="Migrate">
</form>
_EOT_;
    }

/// Step 2: Confirm
function confirm()
    {
    global $URL;

    // Get email addresses into an array
    $emails = Array();
    foreach (split("\n", $_POST['emails_text']) as $email)
        {
        $email = trim($email);
        if ($email != '')
            $emails[] = $email;
        }
    if (count($emails) == 0)
        return error('You have not entered any email addresses.');

    // Generate the migration confirmation table
    $migrations = build_migrations($emails);
    $can_proceed = FALSE;
    $tmp = Array();
    foreach ($migrations as $m)
        {
        $checkbox = '<input type="checkbox" name="emails[]" value="' . $m->email . '" ' . ($m->status < 100 ? 'CHECKED' : 'DISABLED') . '>';
        $tmp[] = "<tr><td>$checkbox</td><td>{$m->email}</td><td>{$m->status}</td><td>{$m->status_message}</td></tr>\n";
        if ($m->status < 100)
            $can_proceed = TRUE;
        }
    $report = join('', $tmp);
    if ($can_proceed)
        $submit_btn = '<p><input type="submit" value="Confirm">';
    else
        $submit_btn = '<p>Sorry, there are no users that can be migrated. <a href="' . $URL . '">Back to step 1</a>';
    return <<< _EOT_
<h3>Step 2 of 3: Confirm</h3>
<p>Please check the report below. You can uncheck any users if you don't want to import them.
<p>When you are happy with your selection, click 'Confirm' to migrate the users.</p>
<form method="POST">
<table width="100%" border="1">
<tr><th>&nbsp</th><th>Email</th><th colspan="2">Status</th></tr>
$report
</table>
<input type="hidden" name="confirmed" value="1">
$submit_btn
</form>
_EOT_;
    }

/// Step 3: Do migration
function migrate()
    {
    global $URL;
    $emails = @$_POST['emails'];
    if (count($emails) == 0)
        return error('You have not selected any email addresses.');
    $migrations = build_migrations($emails, TRUE);
    $there_were_errors = FALSE;
    foreach ($migrations as $m)
        {
        if ($m->status < 100)
        $action_message = 'Migrated old: ' . $m->old_user['id'] . ' new: ' . $m->new_user['id'];
        else {
            $action_message = 'Migration failed';
            $there_were_errors = TRUE;
            }
        $tmp[] = "<tr><td>{$m->email}</td><td>{$m->status}</td><td>{$m->status_message}</td><td>$action_message</td></tr>\n";
        }

    //### print_r($_POST);//###
    $report = join('', $tmp);
    $errors = $there_were_errors ? '<b>There were errors. Please check the results.</b>' : '';
    return <<< _EOT_
<h3>Step 3 of 3: Finished</h3>
<p>These are the results of the migration. $errors</p>
<table width="100%" border="1">
<tr><th>Email</th><th colspan="2">Status</th><th>Action</th></tr>
$report
</table>
<p><a href="$URL">Back to step 1</a>
_EOT_;
    }

function error($message='There has been a problem with your request')
    {
    global $URL;
    return <<< _EOT_
<h3>Sorry, something has gone wrong</h3>
<p>$message
<p>Please <a href="$URL">try again</a>
_EOT_;
    }

// Create an array of migrations from an array of email addresses
// Complete the migration if 'do_migrations' is set
function build_migrations($emails, $do_migrations=FALSE)
    {
    $migrations = Array();
    foreach ($emails as $email)
        {
        $m = new UserMigrator();
        $m->setup($email);
        if ($do_migrations)
            $m->migrate();
        $migrations[] = $m;
        }
    return $migrations;
    }

?>
