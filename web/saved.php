<?php
// $Id$
// View Saved Searches
// Phil Hansen, 1 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up global vars
$TITLE = $STRINGS['saved_title'];
$TEMPLATE = 'saved';

$canSave = $USER->has_right('save_data'); // store the user's permission

// Get the QueryList object
if ($canSave)
    {
    $QUERYLIST = $USER->load_data('saved_searches');
    if (is_null($QUERYLIST)) // no saved data yet
        $QUERYLIST = new QueryList();
    $ACTIVELIST = $USER->load_data('saved_searches_active');
    if (is_null($ACTIVELIST))
        $ACTIVELIST = Array();
    }
// User does not have permission, or saved querylist is broken
if (@$QUERYLIST == NULL)
    {
    $QUERYLIST = new QueryList();
    $ACTIVELIST = Array();
    }

// check for a form POST
if (is_array($_POST) && count($_POST) > 0 && $canSave)
    {
    // determine post type
    if (isset($_POST['save'])) // came from search results
        {
        unset($_POST['save']);

        // create the query with the criteria in the post
        $query = QueryFactory::create($MODULE);
        $query->set_criteria_values($_POST);

        if (!$QUERYLIST->contains($query))
            {
            if (count($QUERYLIST) >= $CONF['saved_searches_size']) // saved searches are full
                {
                $MESSAGE = $STRINGS['error_saved_searches_limit'];
                $MESSAGE_CLASS = 'error-message';
                }
            else
                {
                $QUERYLIST->add($query);
                // saved searches are 'active' by default
                array_unshift($ACTIVELIST, '1');
                if (count($ACTIVELIST) > count($QUERYLIST))
                    array_pop($ACTIVELIST);
                // save the searches
                $USER->save_data('saved_searches', $QUERYLIST);
                $USER->save_data('saved_searches_active', $ACTIVELIST);
                $MESSAGE = $STRINGS['search_save'];
                $MESSAGE_CLASS = 'info-message';
                }
            }
        }
    // came from saved page - delete a search
    else if (isset($_POST['delete']))
        {
        // remove the marked search
        if (isset($_POST['key']))
            {
            $QUERYLIST->remove($_POST['key']);
            unset($ACTIVELIST[$_POST['key']]);
            $ACTIVELIST = array_merge($ACTIVELIST);
            $USER->save_data('saved_searches', $QUERYLIST);
            $USER->save_data('saved_searches_active', $ACTIVELIST);
            $MESSAGE = $STRINGS['search_delete']; // set message
            $MESSAGE_CLASS = 'info-message';
            }
        }
    // came from saved page - update active list and search day
    else if (isset($_POST['update']))
        {
        // update active list
        foreach ($ACTIVELIST as $key=>$value)
            {
            // if the checkbox was submitted, then the search is active
            $ACTIVELIST[$key] = (isset($_POST[$key])) ? '1' : '0';
            }
        $USER->save_data('saved_searches_active', $ACTIVELIST);
        $MESSAGE = $STRINGS['search_active_list_update'];

        // update saved search day
        if (isset($_POST['day']) && $_POST['day'] != @$USER->prefs['saved_search_day'])
            {
            $USER->prefs['saved_search_day'] = $_POST['day'];
            $USER->save_prefs();
            $MESSAGE = get_search_day_set_message($_POST['day']);
            }
        $MESSAGE_CLASS = 'info-message';
        }
    // set a saved search as active
    else if (isset($_POST['set_active']))
        {
        $from_ajax = @$_POST['ajax'];
        $key = $_POST['key'];
        if (isset($ACTIVELIST[$key]))
            {
            $ACTIVELIST[$key] = '1';
            $USER->save_data('saved_searches_active', $ACTIVELIST);
            $MESSAGE = $STRINGS['search_set_active'];
            }
        }
    // remove a saved search from being active
    else if (isset($_POST['remove_active']))
        {
        $from_ajax = @$_POST['ajax'];
        $key = $_POST['key'];
        if (isset($ACTIVELIST[$key]))
            {
            $ACTIVELIST[$key] = '0';
            $USER->save_data('saved_searches_active', $ACTIVELIST);
            $MESSAGE = $STRINGS['search_remove_active'];
            }
        }
    // set the search day
    else if (isset($_POST['set_day']))
        {
        $from_ajax = @$_POST['ajax'];
        $USER->prefs['saved_search_day'] = $_POST['day'];
        $USER->save_prefs();
        $MESSAGE = get_search_day_set_message($_POST['day']);
        }

    // if we came from an ajax call, simply show the message and stop
    if (@$from_ajax)
        {
        echo (@$MESSAGE) ? $MESSAGE : '';
        return;
        }
    }

// check for modules with auto alerts enabled
$auto_alerts_enabled = Array();
foreach ($QUERYLIST as $query)
    {
    $mod = Module::load($query['module']);
    if ($mod->auto_alert_enabled)
        $auto_alerts_enabled[$query['module']] = true;
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type());
require_once $CONF['path_templates'] . $TEMPLATE . '.php';

// Helper function
function get_search_day_set_message($day)
    {
    global $STRINGS;
    if ($day < 1 || $day > 7)
        return $STRINGS['search_day_disabled'];
    $day_names = get_days_of_the_week_values();
    return sprintf($STRINGS['search_day_set'], $day_names[$day]);
    }
