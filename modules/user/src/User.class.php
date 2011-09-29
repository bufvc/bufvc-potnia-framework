<?php
// $Id$
// User module for IRN/LBC project
// Phil Hansen, 25 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/**
    Types of data that are saved in the User object
*/
{
define( 'USERDATA_SEARCH_HISTORY', '_history_search');
define( 'USERDATA_PREFERENCES', '_prefs');
define( 'USERDATA_SAVED_SEARCHES', 'saved_searches');
define( 'USERDATA_SAVED_SEARCHES_ACTIVE', 'saved_searches_active');
}

/** The User class has the following attributes:
    - login: the user's login name
    - email: the user's email address
    - name: the user's real name

    The class has functions for creating/retrieving/updating users, and functions for
    managing user rights and saving/loading user data.
*/
class User
    {
    /// The user's login name
    var $login;

    /// The user's email address
    var $email;

    // The user's real name
    var $name;

    // Does the user have root priveleges (i.e. all rights)
    var $hasRoot;

    /// The user's internal url for use with datasource
    var $url;

    // User preferences
    var $prefs;

    // Local cache of the user's rights
    var $_rights = NULL;

    // User's non-permanent rights, e.g. if set for session
    var $_transient_rights = Array();

    // Datasource
    var $ds;
    
    // a list of the users past searches
    var $search_history = NULL;

    function User($login_name, $email='', $name='', $hasRoot=false, $url='')
        {
        global $CONF;
        $this->login = $login_name;
        $this->email = $email;
        $this->name = $name;
        $this->hasRoot = $hasRoot;
        $this->url = $url;
        $this->prefs = Array();

        // guest users don't need a datasource
        if ($this->is_registered())
            $this->ds = $this->get_ds();

        // load user preferences
        if ($this->is_registered() && $this->has_right('save_data'))
            {
            $data = $this->load_data('_prefs');
            if (!is_null($data))
                $this->prefs = $data;
                
            $search_history = $this->load_data(USERDATA_SEARCH_HISTORY);
            if (!is_null($search_history))
                $this->search_history = $search_history;
            else
                $this->search_history = new QueryList($CONF['search_history_size']);
            }
        else
            {
            if (!isset($_SESSION['HISTORY']))
                {
                $_SESSION['HISTORY'] = new QueryList($CONF['search_history_size']);
                xlog(2, "creating new history for " . $this->login);
                }
            else
                xlog(2, "loading past history for " . $this->login);
            $this->search_history = $_SESSION['HISTORY'];
            }
        }

    /// Initialise the datasource and return it
    function get_ds()
        {
        $module = Module::load('user');
        return $module->get_datasource();
        }

    /// Retrieves the user object for the given login name
    /// If the login name is empty the 'guest' account is used
    /// If the user is not found it will be created
    function instance($login_name='')
        {
        global $CONF;

        if (empty($login_name)) // use the default user
            $login_name = $CONF['default_user'];

        $user = User::retrieve($login_name); // attempt to retrieve the user

        if (is_null($user)) // does not exist yet
            $user = User::create($login_name); // create the user

        return $user;
        }

    /// Create a new user
    /// Returns the created user or NULL on error
    function create($login_name, $email='', $name='')
        {
        // check the login name
        if (empty($login_name) || strstr($login_name, ' ')) // empty login or login containing spaces is not allowed
            return NULL;

        $ds = USER::get_ds();

        $data = Array(
            'login' => $login_name,
            'email' => $email,
            'name' => $name,
            );

        $r = $ds->create('/user', $data);

        // check for error, use guest account
        if (is_null($r))
            {
            xlog(2, 'Create error, using guest', 'USER');
            return new User('guest', '', 'Guest User', false);
            }

        xlog(2, 'Create: ' . $login_name, 'USER');

        $user = User::retrieve($login_name); // retrieve the new user
        $user->log_event('create');

        // add any default rights
        global $CONF;
        foreach ($CONF['default_user_rights'] as $right)
            $user->set_right($right, TRUE);

        return $user;
        }

    /// Get a user
    /// Returns the user or NULL on error
    function retrieve($login_name)
        {
        // check for guest user
        if ($login_name == 'guest')
            {
            xlog(2, 'Retrieve: ' . $login_name, 'USER');
            return new User('guest', '', 'Guest User', false);
            }

        $ds = USER::get_ds();

        $result = $ds->search('/user', "{login=$login_name}", 0, 10);

        if (is_null($result) || @$result['count'] < 1) // check the result
            return NULL;

        $result = $result['data'][0]; // first result

        xlog(2, 'Retrieve: ' . $login_name, 'USER');

        return new User($login_name, $result['email'], $result['name'], $result['root'], $result['url']);
        }

    /// Change a user's fields
    /// Return NULL on error or if the user does not have the save_data right
    function update()
        {
        if (!$this->has_right('save_data')) // check user right
            return NULL;

        $data = Array(
            'login' => $this->login,
            'email' => $this->email,
            'name' => $this->name,
            'root' => $this->hasRoot,
            );

        $result = $this->ds->update($this->url, $data);
        xlog(2, 'Update: ' . $this->login, 'USER');
        return $result;
        }

    /// Checks if the user has the specified right
    /// Returns true if the user has the right, false otherwise
    function has_right($right)
        {
        $rights = $this->list_rights(); // get the list of rights
        return isset($rights[$right]); // check if the user has the specified right in the list
        }

    /// Sets the specified right of the user to the specified value (TRUE or FALSE)
    /// A right can be set transiently, in which case it is not saved to disk
    function set_right($right, $value, $permanent=TRUE)
        {
        if ($permanent)
            {
            // Remove any transient rights masking this one
            unset($this->_transient_rights[$right]);

            // Get current rights
            $rights = array_keys($this->list_rights());

            // Add right
            if ($value)
                {
                $allRights = $this->list_all_rights();
                if (!isset($allRights[$right])) // right doesn't exist
                    return;
                if ($this->has_right($right)) // already has this right
                    return;
                $rights[] = $right;
                $message = 'Add right: ' . $right . ' for ' . $this->login;
                }

            // Remove right
            else {
                if (!$this->has_right($right)) // does not have this right
                    return;
                foreach ($rights as $key=>$value)
                    {
                    if ($right == $value)
                        unset($rights[$key]);
                    }
                $message = 'Remove right: ' . $right . ' from ' . $this->login;
                }

            // Save rights
            $data = Array('login'=>$this->login, 'rights'=>$rights);
            $this->ds->update($this->url, $data);

            // Clear cached rights
            $this->_rights = NULL;
            }

        // Transient rights
        else {
            if ($value)
                {
                $allRights = $this->list_all_rights();
                $name = isset($allRights[$right]) ? $allRights[$right] : $right;
                $this->_transient_rights[$right] = $name;
                $message = 'Add transient right: ' . $right . ' for ' . $this->login;
                }
            else {
                unset($this->_transient_rights[$right]);
                $message = 'Remove transient right: ' . $right . ' from ' . $this->login;
                }
            }
        if (@$message != '')
            xlog(2, $message, 'USER');
        }

    /// Lists the rights that this user has
    /// Returns an array (name=>title) of rights
    function list_rights()
        {
        // Root has all rights
        if ($this->hasRoot)
            $result = $this->list_all_rights();

        // check if the user's rights have already been cached
        else if (!is_null($this->_rights))
            $result = $this->_rights;

        // check for guest user
        else if ($this->login == 'guest')
            {
            $this->_rights = Array();
            $result = $this->_rights;
            }

        // Load rights from database into cache
        else {
            $r = $this->ds->retrieve($this->url);
            $result = Array();
            if (!is_null($r))
                $result = table_get_assoc($r['rights_full'], 'title', 'name');
            $this->_rights = $result;
            }

        return array_merge($result, $this->_transient_rights);
        }

    /// List all possible rights
    /// Returns an array (name=>title) of rights
    function list_all_rights()
        {
        if (is_null($this->ds))
            return Array();
        $results = $this->ds->search('/rights', '', 0, 1000);
        return table_get_assoc($results['data'], 'title', 'name');
        }

    /// Saves the user data specified by name
    /// The data is serialized before being stored.
    /// Returns NULL on error or if the user does not have the save_data right
    function save_data($name, $value)
        {
        if (!$this->has_right('save_data')) // check user right
            return NULL;

        // prepare data
        $data = Array(
            'login' => $this->login,
            'user_data' => Array(Array('name'=>$name, 'value'=>serialize($value))),
            );

        $result = $this->ds->update($this->url, $data);
        xlog(2, 'Save data: ' . $name . ' for ' . $this->login, 'USER');
        return $result;
        }

    /// Loads the user data specified by name
    /// The data is unserialized upon retrieval.
    /// Returns NULL on error or if the user does not have the save_data right
    function load_data($name)
        {
        if (!$this->has_right('save_data')) // check user right
            return NULL;

        $result = $this->ds->retrieve($this->url);

        if (is_null($result))
            return NULL;

        foreach ($result['user_data'] as $data)
            {
            if ($data['name'] == $name)
                {
                xlog(2, 'Load data: ' . $name . ' for ' . $this->login, 'USER');
                return @unserialize($data['value']);
                }
            }
        
        return NULL;
        }

    // Saves the user preferences
    function save_prefs()
        {
        if (!$this->has_right('save_data')) // check user right
            return NULL;
        $this->save_data('_prefs', $this->prefs);
        }

    // Adds to the users search history
    function add_to_search_history( $query )
        {
        $this->search_history->add($query);
        
        // if the user is registered, then also save their search history, so that
        // it persists between sessions
        if( $this->is_registered() && $this->has_right('save_data') )
            {
            $this->save_data(USERDATA_SEARCH_HISTORY, $this->search_history);
            }
        }
    
    // Clears the user's search history
    function clear_search_history()
        {
        global $CONF;
        $this->search_history = new QueryList($CONF['search_history_size']);
        $_SESSION['HISTORY'] = $this->search_history;
        
        if ($this->is_registered() && $this->has_right('save_data'))
            $this->save_data(USERDATA_SEARCH_HISTORY, $this->search_history);
        }
        
    // Creates a user event in the UserEvent table
    function log_event($event)
        {
        $login = $this->login;
        if (empty($login) || $login == 'guest')
            return NULL;

        // sort of a hack - parse user id out of url
        $data = Array(
            'user_id' => substr($this->url, 6),
            'date' => date("Y-m-d H:i:s"),
            'event' => $event,
            );
        return $this->ds->create('user_event', $data);
        }
    
    // Gets the user's preferred timeout, or default otherwise
    function get_timeout()
        {
        global $CONF;
        return isset($this->prefs['timeout']) ? $this->prefs['timeout'] : $CONF['user_timeout'];
        }
        
    // Returns TRUE if this is a registered user, ie not a guest
    function is_registered()
        {
        return $this->login != 'guest';
        }
    }

?>
