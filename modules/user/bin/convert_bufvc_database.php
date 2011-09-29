<?php
// $Id$
// Convert user data
// James Fryer, 21 Oct 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$_ENV['MODULE'] = 'trilt';
require_once(dirname(realpath(__FILE__)) . '/../../../web/include.php');
// require_once($CONF['path'] . 'modules/trilt/src/TriltQueryConverter.class.php');

$db_name = $argv[1]; //### FIXME: error handling
db_init($CONF['db_user'], $CONF['db_pass'], $CONF['db_server'], $db_name, FALSE);

// Dummy classes
class ZueryList {};
class Zuery {};

// Single quotes in the old database are escaped with this string
define('_UDB_ESCAPE', '&;!');

// $converter = new TriltQueryConverter();
$sql = "SELECT id,data FROM User";
$users = db_get_all($sql);
foreach ($users as $user)
    {
    $id = $user[0];
    xlog(3, "Converting user: $id");

    // Insert default rights
    $sql = "INSERT INTO UserRight (user_id, right_id)
        SELECT $id, id FROM Rights WHERE name IN('" . join("', '", $CONF['default_user_rights']) . "')";
    db_query($sql);

    // Convert data
    $userdata = $user[1];
    // Avoid clashing with the new classes
    $userdata = str_ireplace('"query', '"Zuery', $userdata);
    // Remove old escape hack
    $userdata = str_replace(_UDB_ESCAPE, '\\', $userdata);
    $legacy_data = @unserialize($userdata);
    if ($legacy_data === FALSE)
        {
        xlog(3, "No data: $id");
        continue;
        }

    $prefs = Array();
    $saved_searches_active = Array();

    // Listings
    if (isset($legacy_data['listings_multi_ids']))
        $prefs['listings_channels'] = $legacy_data['listings_multi_ids'];

    // Auto alerts
    if (isset($legacy_data['saved_searches']))
        {
        $list = new QueryList();
        $query_list = $legacy_data['saved_searches'];
        $prefs['saved_search_day'] = $query_list->alert_day;
        foreach ($query_list as $legacy_query)
            {
            $saved_searches_active[] = $legacy_query->alert_is_active ? "1" : "0";
            // $converted_query = $converter->convert($legacy_query);
            // $list->add($converted_query);
            }
        $saved_searches = str_replace("'", "\\'", serialize($list));
        $sql = "INSERT INTO
            UserData
        SET
            user_id=$id,
            name = 'saved_searches',
            value = '$saved_searches'
        ";
        db_query($sql);
        }

    // Save prefs
    if (count($prefs) > 0)
        {
        $prefs = str_replace("'", "\\'", serialize($prefs));
        $sql = "INSERT INTO
            UserData
        SET
            user_id=$id,
            name = '_prefs',
            value = '$prefs'
        ";
        db_query($sql);
        }

    // Save active flags
    if (count($saved_searches_active) > 0)
        {
        $saved_searches_active = str_replace("'", "\\'", serialize($saved_searches_active));
        $sql = "INSERT INTO
            UserData
        SET
            user_id=$id,
            name = 'saved_searches_active',
            value = '$saved_searches_active'
        ";
        db_query($sql);
        }
    }

// Drop data field
$sql = "ALTER TABLE User DROP data";
db_query($sql);

// Update seq fields
$sql = "UPDATE UserData_seq SET id=(SELECT MAX(id) + 1 FROM UserData)";
db_query($sql);
?>
