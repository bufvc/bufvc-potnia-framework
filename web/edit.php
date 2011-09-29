<?php
// $Id$
// Edit screen
// Phil Hansen, 5 Sep 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

include './include.php';

// Set up the datasource
// If unit tests are active, we require a persistent datasource.
// Otherwise get it from the module.
//### FIXME: This is a bit ugly, could we improve it???
if ($CONF['unit_test_active'])
    $ds = new TestMysqlDataSource($MODULE);
else
    $ds = $MODULE->get_datasource();

// Set up global vars
$TITLE = $STRINGS['edit_title'];
$TEMPLATE = 'edit_default';
$RECORD = NULL;
$IS_NEW = false;

$is_default_screen = true;

// Figure out what action we are taking.
// The default is to show the table list.

// check permissions
if (!($MODULE->can_edit($USER) && $MODULE->has_right($USER)))
    {
    // set status and error message
    header("HTTP/1.0 401 Unauthorized");
    $MESSAGE = $STRINGS['error_401_edit'];
    $MESSAGE_CLASS = 'error-message';
    $is_default_screen = false;
    }

// If there is a path after the URL, get the info from the data source
else if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '' && $_SERVER['PATH_INFO'] != '/')
    {
    // attempt to retrieve the info from the data source
    $RECORD = $ds->retrieve($_SERVER['PATH_INFO']);
    $is_default_screen = false;

    if (is_null($RECORD))
        {
        header("HTTP/1.0 404 Not found");
        $MESSAGE = $STRINGS['error_404_record'];
        $MESSAGE_CLASS = 'error-message';
        $is_default_screen = true;
        }
    else
        {
        // this is info for a table, meaning this is the new item form
        if ($RECORD['_table'] == 'meta')
            {
            $IS_NEW = true;
            $TITLE = $STRINGS['edit_title_new'];
            $table = $RECORD;
            // clear the record array
            $RECORD = Array();
            $RECORD['_table'] = $table['key'];
            }
        $TEMPLATE = 'edit';
        }

    // check for a form POST
    if (is_array($_POST) && count($_POST) > 0)
        {
        $error = false;
        $data = Array();
        // pass to module for any extra handling
        $MODULE->process_edit_data($_POST, $RECORD, $IS_NEW);

        // check for an immediate redirect from module
        if (isset($_POST['redirect_url']))
            {
            header("HTTP/1.1 303 See Other");
            header("Location: " . $_POST['redirect_url']);
            exit();
            }
        // this is a delete
        else if (isset($_POST['delete']))
            {
            $result = $ds->delete($RECORD['url']);
            if (is_null($result))
                {
                header("HTTP/1.0 400 Bad request");
                $MESSAGE = $STRINGS['error_delete_record'];
                }
            else
                {
                set_session_message($STRINGS['item_delete'], 'info-message');
                xlog(2, 'Delete: ' . $RECORD['url'], 'EDIT');

                // redirect to the edit page and return status 303
                header("HTTP/1.1 303 See Other");
                header("Location: " . $MODULE->url('edit'));
                exit();
                }
            }
        // create or update
        else
            {
            if ($IS_NEW)
                {
                $data['slug'] = @$_POST['slug'];
                // get the list of fields from the table info
                if (!isset($table) && isset($RECORD['_table']))
                    $table = $ds->retrieve($RECORD['_table']);
                $fields = $table['fields'];
                }
            else
                {
                // get the table info and store the list of fields for this table
                $tableInfo = $ds->retrieve($RECORD['_table']);
                $fields = $tableInfo['fields'];
                }

            // check all required fields
            foreach ($fields as $field=>$value)
                {
                // missing a required field
                if (isset($value['require']) && $value['require'] && (!isset($_POST[$field]) || $_POST[$field] == ''))
                    {
                    header("HTTP/1.0 400 Bad request");
                    $MESSAGE = $STRINGS['error_required_fields'];
                    $error = true; // set flag
                    break; // exit the loop
                    }

                // make sure this field was on the form
                if (isset($_POST[$field]))
                    {
                    if (is_array($_POST[$field]))
                        {
                        // remove empty values from array
                        foreach ($_POST[$field] as $key=>$value)
                            {
                            if(empty($_POST[$field][$key]))
                                unset($_POST[$field][$key]);
                            }
                        // fix indexes
                        $_POST[$field] = array_merge($_POST[$field]);
                        }
                    $data[$field] = $_POST[$field];
                    }
                }

            // check for error from module
            if (@$_POST['error'])
                {
                header("HTTP/1.0 400 Bad request");
                $MESSAGE = $_POST['error'];
                }
            // confirm no error
            else if (!$error)
                {
                $redirect_url = '';
                if ($IS_NEW) // new item
                    {
                    $result = $ds->create($table['url'], $data); // create the item in the table
                    // check for create error
                    if (is_null($result))
                        {
                        header("HTTP/1.0 400 Bad request");
                        $MESSAGE = $STRINGS['error_create_record'];
                        $error = true;
                        }
                    else
                        {
                        set_session_message($STRINGS['item_create'], 'info-message'); // set message
                        xlog(2, 'Create: ' . $result['url'], 'EDIT');
                        $redirect_url = $MODULE->url('edit', $result['url']);
                        }
                    }
                else // existing item
                    {
                    $result = $ds->update($RECORD['url'], $data); // update the item
                    if (is_null($result))
                        {
                        header("HTTP/1.0 400 Bad request");
                        $MESSAGE = $STRINGS['error_update_record'];
                        $error = true;
                        }
                    else
                        {
                        set_session_message($STRINGS['item_save'], 'info-message'); // set message
                        xlog(2, 'Update: ' . $result['url'], 'EDIT');
                        $redirect_url = $MODULE->url('edit', $result['url']);
                        }
                    }
                // pass to module for any final processing
                $MODULE->finish_edit_process($_POST, $result, $error);

                // check for error from module
                if (@$_POST['error'])
                    {
                    header("HTTP/1.0 400 Bad request");
                    $MESSAGE = $_POST['error'];
                    }
                // confirm still no error
                else if (!$error)
                    {
                    // redirect to the item and return status 303
                    $redirect_url .= isset($_POST['append_url']) ? $_POST['append_url'] : '';
                    header("HTTP/1.1 303 See Other");
                    header("Location: " . $redirect_url);
                    exit();
                    }
                }
            } // end create/update
        } // end POST
    } // end server path

if ($is_default_screen)
    {
    // get the list of tables
    $TABLES = $ds->search('meta', '', 0, 1000);

    if (!is_null($TABLES) && $TABLES['count'] > 0)
        $TABLES = $TABLES['data'];
    }

// Display page to user
header('Content-Type: ' . $MODULE->content_type());
require_once $CONF['path_templates'] . $TEMPLATE . '.php';
