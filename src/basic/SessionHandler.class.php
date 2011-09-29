<?php
// $Id: NetworkProvider.class.php,v 1.1 2006/09/01 16:44:38
// Saves sessions
// David Sanders, 19 Mar 07
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

define('SESSION_TIMEOUT', 60*60);

//set sessions handler on php
$_session = new PearDBSession($db);
session_set_save_handler (Array(&$_session, 'open'),
                          Array(&$_session, 'close'),
                          Array(&$_session, 'read'),
                          Array(&$_session, 'write'),
                          Array(&$_session, 'destroy'),
                          Array(&$_session, 'gc'));

class PearDBSession
    {
    function PearDBSession(&$db)
        {
        $this->db = $db;
        }

    /// Open session
    function open($path, $name)
        {
        return isset($this->db);
        }

    /// Close session
    function close()
        {
        // This is used for a manual call of the session gc function
        $this->gc(0);
        return TRUE;
        }

    /// Read session data from database
    function read($ses_id)
        {
        $session_sql = "SELECT * FROM Session WHERE ses_id = '$ses_id'";
        $session_res = $this->db->getRow($session_sql, Array(), DB_FETCHMODE_ASSOC);
        if ($session_res)
            {
            $ses_data = $session_res['ses_value'];
            return $ses_data;
            }
        else
            return '';
        }

    /// Write new session data to database
    function write($ses_id, $data)
        {
        $newdata = addslashes($data);
        $session_sql = "UPDATE Session SET ses_time='" . time()
                     . "', ses_value='$newdata' WHERE ses_id='$ses_id'";
        $session_res = $this->db->query($session_sql);
        if (!$session_res)
            return FALSE;
        if ($this->db->affectedRows() > 0)
            return TRUE;
        $session_sql = "INSERT INTO Session (ses_id, ses_time, ses_start, ses_value)"
                     . " VALUES ('$ses_id', '" . time()
                     . "', '" . time() . "', '$newdata')";
        $session_res = $this->db->query($session_sql);
        return $session_res;
        }

    /// Destroy session record in database
    function destroy($ses_id)
        {
        $session_sql = "DELETE FROM Session WHERE ses_id = '$ses_id'";
        $session_res = $this->db->query($session_sql);
        return $session_res;
        }

    // Garbage collection removes old sessions
    function gc($life)
        {
        $ses_life = time() - SESSION_TIMEOUT;
        $session_sql = "DELETE FROM Session WHERE ses_time < $ses_life";
        $session_res = $this->db->query($session_sql);
        return $session_res;
        }

    /// Count users online
    function users()
        {
        $users_sql = "SELECT COUNT(ses_id) FROM Session";
        $users_res = $this->db->getOne($users_sql);
        return $users_res;
        }
    }

?>
