<?php
define('DEMO_MODULE', 'hermes');

$base_url = 'http://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["PHP_SELF"]);
$base_path = realpath(dirname(realpath(__FILE__)) . '/..') . '/';
$config_php = $base_path . '/etc/config.php';
if (file_exists($config_php))
   fatal('E01',  'You are already configurated');
else if (!is_writable($base_path . 'etc/'))
   fatal('E02',  "Can't write config file: make etc/ writable");
else if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
    //### TODO: fix inconsistent names
    $conf = Array(
        'url' => $base_url,
        'module' => DEMO_MODULE,
        'db_user' => $_POST['db_user'],
        'db_pass' => $_POST['db_pass'],
        'db_database' => $_POST['db_database'],
        'db_server' => $_POST['db_server'],
        'db_wart' => '',
        );
    // If database exists -- bail out
    if (database_exists($conf))
       fatal('E03',  "Database exists");
    //### TODO: Error handling
    //### 2. Fields not filled in -- all req'd except password    
    
    // Create config
    $f = fopen($config_php, 'w');
    fwrite($f, make_config_file($conf));
    fclose($f);
    
    // Create database
    $mysql_args = "--host={$conf['db_server']} --user={$conf['db_user']} --password={$conf['db_pass']}";
    $shell_cmd = "{$base_path}bin/modinstall -m \"$mysql_args\" -s {$conf['module']} {$conf['db_database']}";
    $response = Array();
    call_shell($shell_cmd, $response);
    
    // Print response
    //### TODO: should redirect???
    print html_thanks($base_url);
    }
else {
    print html_installer_form();
    }

function make_config_file($conf)
    {
    global $base_path;
    $date = strftime('%c');
    ob_start();
    require_once $base_path . 'etc/config-installer-template.php';
    //### return ob_get_clean();    
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
    }
   
function html_header($title)
    {
    return <<< _EOT_
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<head>
	<title>$title</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="stylesheet" type="text/css" href="css/reset.css" media="all" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="css/grid.css" media="screen, tv" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="css/default.css" media="screen, tv" charset="utf-8" />
	<!--[if lte IE 7]><link rel="stylesheet" href="css/ie7.css" type="text/css" media="screen, tv, projector" /><![endif]-->
	<link rel="stylesheet" type="text/css" href="css/print.css" media="print" charset="utf-8" />
    <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="js/potnia.js"></script>
</head>
<body class="install">
<div class="page" style="overflow:hidden">
    <div class="header-wrapper clearfix">
		    <div class="column_24 header" id="header">
			    <div class="column_10 branding">
				    <div class="column_2 logotype"><a href="./" title="BUFVC &middot; Homepage"><img src="components/bufvc_logo_solo.gif" alt="BUFVC Logo" style="float:left" width="67" height="70" /></a></div>
				    <div class="column_8 last_column" >
                        <h3 class="slogan"><em>BUFVC</em></h3>
					    <h2 class="bufvc"><a href="./" title="$title &middot; Homepage">Potnia</a></h2>
					    <h3 class="slogan" style="margin-top:10px"><em>Framework</em></h3>
				    </div>
			    </div>
		    </div>
    </div> <!-- Header ends -->
    <div class="title-wrapper"><h2 class="title-project">$title</h2></div>
    <div class="column_14 last_column prepend-6">
    <h1>$title</h1>
_EOT_;
    }

function html_footer()
    {
    return <<< _EOT_
    </div>
</body>
</html>
_EOT_;
    }
    
function html_installer_form()
    {
    $result = html_header('Install the BUFVC Potnia Framework');
    $result .= <<< _EOT_
        <p>This form will let you install the BUFVC Potnia Framework providing you have already:
            <ol>
                <li>Made the var/ folder writeable</li>
                <li>Made the etc/ folder writeable (these permissions should be reverted post installation)</li>
                <li>Got a MySQL username and password on this server</li>
            </ol>
        </p>
        <div class="forms-holder">
            <form method="post" id="installer-form">
                <fieldset>
                    <label for="sitename">Site name:</label>
                    <input name="sitename" value="">
                    <label for="db_database">MySQL database name:</label>
                    <input name="db_database" value="">
                    <label for="db_user">MySQL user:</label>
                    <input name="db_user" value="">
                    <label for="db_pass">MySQL password:</label>
                    <input name="db_pass" value="">
                    <label for="db_server">MySQL hostname:</label>
                    <input name="db_server" value="localhost">
                </fieldset>
                <fieldset>
                    <input type="submit" id="submit" class="save-button" value="Install" />
                </fieldset>
            </form>
        </div>
        <p>For help and more information about the project visit the project homepage at <a href="http://potnia.org" title="BUFVC Potnia Framework project homepage">http://potnia.org</a></p>
    </div> 
_EOT_;
    $result .= html_footer();
    return $result;
    }

function html_thanks($url)
    {
    $result = html_header('Thanks');
    $result .= <<< _EOT_
<p>Thank you for installing Potnia! Now you can <a href="$url/">visit the site</a> and don't forget to change back the permissions on your etc/ directory, leaving it writeable is not very secure!</p>
_EOT_;
    $result .= html_footer();
    return $result;
    }

function database_exists($conf)
    {
    global $ICONF;
    mysql_connect($conf['db_server'], $conf['db_user'], $conf['db_pass']);
    $result = mysql_select_db($conf['db_database'] . $conf['db_wart']);
    mysql_close();
    return $result;
    }

function fatal($code, $message)
    {
    exit(sprintf("%s: %s\n", $code, $message));
    }

// Call a shell command, terminate on error
function call_shell($shell_cmd, &$response)
    {
    $return = 0;
    exec($shell_cmd . ' 2>& 1', $response, $return);
    if ($return)
        {
        if (count($response) == 1)
            $message = $response[0];
        else if (count($response) > 1)
            $message = '<pre>' . join("\n", $response) . '</pre>';
        else           
            $message = 'Shell error : ' . $shell_cmd;
        fatal('E04', $message);
        }
    }
