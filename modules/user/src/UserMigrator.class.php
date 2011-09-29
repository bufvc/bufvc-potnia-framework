<?php // $Id$
// Migrate user accounts using email address as key
// James Fryer, 23 July 08, 27 Oct 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/** Used to migrate TRILT accounts from classic Athens to OpenAthensSP
    NOTE: This is a port of an earlier utility, so does not use the DS
    and instead hits the database directly.
*/
class UserMigrator
    {
    // Statuses 0-99 are success, 900+ are failure
    var $status_messages = Array(
        1 => 'Normal user, migrate alerts and institution',
        2 => 'Admin/Editor, migrate alerts, institution, user privilege',
        3 => 'Off-Air Rep, migrate user data, institution, off-air settings and orders',
        900 => 'Internal error',
        901 => 'User not found',
        902 => 'New account not found',
        903 => 'Multiple accounts found matching email address',
        904 => 'Account has already been migrated',
        999 => 'Migration status not valid',
        );

    var $email = NULL;
    var $status = 0;
    var $status_message = '';

    // Old and new user data
    var $old_user = NULL;
    var $new_user = NULL;

    function UserMigrator()
        {
        global $MODULE;
        $this->db = $MODULE->get_pear_db();
        }

    /// Setup the migration
    /// Return a status code
    function setup($email)
        {
        $this->email = $email;
        $users = $this->get_users($email);
        // Not found
        if (is_null($users))
            $this->status = 901;

        // Two accounts found -- can proceed
        else if (count($users) == 2)
            {
            $this->old_user = $users[0];
            $this->new_user = $users[1];
            $user_rights = $this->get_user_rights($this->old_user['id']);
            // Check if migrated already
            if ($this->old_user['name'] == 'MIGRATED')
                $this->status = 904;
            // Offair rep
            else if (in_array('offair_rep', $user_rights))
                $this->status = 3;
            // Admin/editor/OA admin
            else if ($this->old_user['root'] ||
                    in_array('edit_record', $user_rights) ||
                    in_array('offair_admin', $user_rights))
                $this->status = 2;
            // Normal user
            else
                $this->status = 1;
            }

        // One or >3 accounts found
        else if (count($users) == 1 || count($users) > 2)
            {
            $this->old_user = $users[0];
            $this->status = (count($users) == 1) ? 902 : 903;
            }
        $this->status_message = $this->status_messages[$this->status];
        xlog(1, "Examined <{$this->email}>, status: {$this->status} {$this->status_message}", 'MIGRATE');
        return $this->status;
        }

    /// Perform the migration
    /// Setup must have been called
    /// Return a status code
    function migrate()
        {
        // Check we have a valid migration setup
        if ($this->status <= 0 || $this->status >= 100)
            $this->status = 999;
        // Migrate the account
        else {
            if (!$this->db)
                $this->status = 999;
            else {
                $this->_migrate_user_record();
                $this->_migrate_data();
                $this->_migrate_rights();
                $this->_migrate_orders();
                }
            }
        $this->status_message = $this->status_messages[$this->status];
        if ($this->status < 100)
            xlog(1, "Migrated <{$this->email}>, old: {$this->old_user['id']} new: {$this->new_user['id']}", 'MIGRATE');
        else
            xlog(1, "Migrated <{$this->email}>, status: {$this->status} {$this->status_message}", 'MIGRATE');
        return $this->status;
        }

    function _migrate_user_record()
        {
        $phone = addslashes($this->old_user['telephone_number']);
        $sql = "UPDATE User SET
            root = {$this->old_user['root']},
            institution_id = {$this->old_user['institution_id']},
            telephone_number = '$phone',
            offair_notifications = {$this->old_user['offair_notifications']}
            WHERE id = {$this->new_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        // Flag old acct as migrated
        $sql = "UPDATE User SET
            name = 'MIGRATED'
            WHERE id = {$this->old_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        }

    function _migrate_data()
        {
        $sql = "DELETE FROM UserData WHERE user_id={$this->new_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        $sql = "UPDATE UserData SET user_id={$this->new_user['id']} WHERE user_id={$this->old_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        }

    function _migrate_rights()
        {
        $sql = "DELETE FROM UserRight WHERE user_id={$this->new_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        $sql = "UPDATE UserRight SET user_id={$this->new_user['id']} WHERE user_id={$this->old_user['id']}";
        $result = $this->db->query($sql);
        if (PEAR::isError($result))
            $this->status = 999;
        }

    function _migrate_orders()
        {
        // Update the Orders table if the user is a rep
        $user_rights = $this->get_user_rights($this->new_user['id']);
        if (in_array('offair_rep', $user_rights))
            {
            $sql = "UPDATE Orders SET off_air_rep_id={$this->new_user['id']} WHERE off_air_rep_id={$this->old_user['id']}";
            $result = $this->db->query($sql);
            if (PEAR::isError($result))
                $this->status = 999;
            }
        }

    /// Get users matching email address
    /// Return array of values or NULL if no users found
    function get_users($email)
        {
        if (!$this->db)
            return NULL;
        $sql = "SELECT * FROM User WHERE email='$email' ORDER BY id";
        $result = $this->db->queryAll($sql, NULL, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($result) || count($result) == 0)
            return NULL;
        return $result;
        }

    /// Get rights for a user
    /// Return array of rights names
    function get_user_rights($user_id)
        {
        if (!$this->db)
            return NULL;
        $sql = "SELECT name FROM Rights JOIN UserRight ON UserRight.right_id=Rights.id WHERE user_id=$user_id";
        $result = $this->db->queryAll($sql, NULL, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($result))
            return NULL;
        return table_get_array($result);
        }

    /// Get data for a user
    /// Return array of name=>value
    function get_user_data($user_id)
        {
        if (!$this->db)
            return NULL;
        $sql = "SELECT name,value FROM UserData WHERE user_id=$user_id";
        $result = $this->db->queryAll($sql, NULL, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($result))
            return NULL;
        return table_get_assoc($result, 'value', 'name');
        }
    }
