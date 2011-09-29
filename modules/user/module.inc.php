<?php
// $Id$
// Module definition file
// Phil Hansen, 04 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class UserModule
    extends Module
    {
    var $name = 'user';
    var $query_config = Array(
        // 'query_class'=>'UserQuery'
    );
    var $title = 'User Management';
    var $version = '0.2';
    var $user_right = 'user_admin';
    var $edit_right = 'user_admin';

    function new_datasource()
        {
        global $CONF;
        $config = Array(
            'user' => Array(
                'title'=>'User',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'pear_db'=>$this->get_pear_db(),
                'mysql_table'=>'User',
                'query_criteria' => Array(
                    Array(
                        'name' => 'q',
                        'qs_key'=> Array('q','adv'),
                        'label' => 'Search for',
                        'render_label' => "Search $this->title for",
                        'index' => 'default',
                        'render_default' => $CONF['search_prompt'],
                        'list' => 'list_search',
                        'advanced_value_count' => 3,
                        'is_primary' => TRUE, // means that it can't be removed and shows up if default
                        ), // query
                    Array(
                        'name' => 'sort',
                        'label' => 'Sort by',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('login'=>'Login'),
                        'is_renderable' => FALSE,
                        ),
                    Array(
                        'name' => 'page_size',
                        'label' => 'Display',
                        'type' => QC_TYPE_SORT,
                        'list' => Array('10'=>'10', '50'=>'50', '100'=>'100'),
                        'is_renderable' => FALSE,
                        'is_encodable' => FALSE,
                        'default' => 10
                        ),
                    ),
                'query_lists'=> Array(
                    'list_search' => Array(
                        '' => 'All fields',
                        'email' => 'Email',
                        'name' => 'Name',
                        ),
                    ),
                'fields'=>Array(
                    'url' => Array(
                        'select'=>'id',
                        ),
                    'login' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'200',
                        ),
                    'email' => Array(
                        'type'=>'varchar',
                        'size'=>'200',
                        'default'=>''
                        ),
                    'name' => Array(
                        'type'=>'varchar',
                        'size'=>'200',
                        'default'=>''
                        ),
                    'root' => Array(
                        'type'=>'boolean',
                        'default'=>'0',
                        ),
                    'rights' => Array(
                        'type'=>'many_to_many',
                        'link'=>'UserRight',
                        'select' => 'name',
                        'keys'=>Array('user_id', 'right_id'),
                        'get'=>'col',
                        'lookup'=>'name',
                        'split'=>'; *',
                        ),
                    'rights_full' => Array(
                        'type'=>'many_to_many',
                        'related_to' => 'rights',
                        'link'=>'UserRight',
                        'select' => 'name,title',
                        'keys'=>Array('user_id', 'right_id'),
                        'order'=>'name',
                        ),
                    'user_data' => Array(
                        'type'=>'one_to_many',
                        'foreign_key'=>'user_id',
                        'select' => 'user_id, name, value',
                        'keep_related'=>true,
                        'lookup'=>'name',
                        ),
                    # Extra fields for BUFVC Off-Air Ordering application
                    'telephone_number' => Array(
                        'type'=>'varchar',
                        'size'=>'40',
                        'default'=>''
                        ),
                    'institution' => Array(
                        'type'=>'many_to_one',
                        'select' => 'Institution.name AS institution',
                        'join' => 'LEFT JOIN Institution ON institution_id=Institution.id',
                        'text_label'=>'Institution',
                        ),
                    'institution_id' => Array(
                        'type'=>'integer',
                        'default'=>0,
                        ),
                    'offair_notifications' => Array(
                        'type'=>'integer',
                        'default'=>1,
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.login,t.email,t.name,t.root"),
                    'index' => Array(
                        'default' => Array('type'=>'fulltext', 'fields'=>'t.login,t.email,t.name'),
                        'login' => Array('type'=>'string', 'fields'=>'t.login'),
                        'email' => Array('type'=>'fulltext', 'fields'=>'t.email'),
                        'name' => Array('type'=>'fulltext', 'fields'=>'t.name'),
                        'institution_id' => Array('type'=>'number', 'fields'=>'t.institution_id'),
                        'right' => Array('type'=>'fulltext', 'fields'=>'Rights.name',
                            'join' => Array('JOIN UserRight ON UserRight.user_id=t.id',
                                'JOIN Rights ON Rights.id=UserRight.right_id')),
                        'sort.login' => Array('type'=>'asc', 'fields'=>'t.login'),
                        'sort.default' => Array('type'=>'asc', 'fields'=>'t.login'),
                        ),
                    ),
                ),

            // Rights
            'rights' => Array(
                'title'=>'Rights',
                'description'=>'User rights',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Rights',
                'fields'=>Array(
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'title' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.name,t.title,t.name AS `key`"),
                    'index' => Array(
                        'default' => Array('type'=>'fulltext', 'fields'=>'t.name,t.title'),
                        ),
                    ),
                ),
            
            // Institution
            'institution' => Array(
                'title'=>'Institutions',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'Institution',
                'fields'=>Array(
                    'url' => Array(
                        'select'=>'id',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.name AS title,t.id AS `key`"),
                    'index' => Array(
                        'default' => Array('type'=>'fulltext', 'fields'=>'t.name'),
                        'sort.default' => Array('type'=>'asc', 'fields'=>'t.name'),
                        ),
                    ),
                ),

            // User saved data
            'user_data' => Array(
                'title'=>'User Data',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'UserData',
                'fields'=>Array(
                    'user_id' => Array(
                        'require'=>1,
                        'type'=>'integer',
                        ),
                    'name' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'255',
                        ),
                    'value' => Array(
                        'require'=>1,
                        'type'=>'text',
                        ),
                    ),
                'search'=>Array(
                    'fields' => Array("t.name,t.user_id,t.id AS `key`"),
                    'index' => Array(
                        'user' => Array('type'=>'number', 'fields'=>'t.user_id'),
                        ),
                    ),
                ),

            // User events
            'user_event' => Array(
                'title'=>'User Events',
                'description'=>'',
                'mutable'=>TRUE,
                'storage'=>'mysql',
                'mysql_table'=>'UserEvent',
                'fields'=>Array(
                    'user_id' => Array(
                        'require'=>1,
                        'type'=>'integer',
                        ),
                    'date' => Array(
                        'require'=>1,
                        'type'=>'timestamp',
                        ),
                    'event' => Array(
                        'require'=>1,
                        'type'=>'varchar',
                        'size'=>'20',
                        ),
                    ),
                'search'=>Array(
                    ),
                ),
            );
        $ds = new Datasource($config);
        // disable normalisation
        $ds->enable_query_normalisation = 0;
        $ds->enable_storage_normalisation = 0;
        return $ds;
        }
    
    function menu($user=NULL)
        {
        $result = parent::menu($user);
        // Add a 'Migration' link to the menu
        $result['migrate'] = Array('title'=>'Migrate Users', 'url'=>$this->url('migrate'));
        return $result;
        }
    
    /// Add any extra handling for posted data from the edit page before saving
    function process_edit_data(&$posted_data, $record, &$is_new)
        {
        if (isset($posted_data['clear']))
            {
            $this->delete_user_data($record);
            global $STRINGS;
            set_session_message($STRINGS['user_data_delete'], 'info-message');
            $posted_data['redirect_url'] = $this->url('edit', $record['url']);
            }
        }
    
    /// Deletes all user data for the given user
    function delete_user_data($record)
        {
        $ds = $this->get_datasource();
        $r = $ds->search('/user_data', "{user=".$record['id']."}", 0, 50);
        foreach ($r['data'] as $data)
            $ds->delete($data['url']);
        }
    }
