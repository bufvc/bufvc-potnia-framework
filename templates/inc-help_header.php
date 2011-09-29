<?php
// $Id$
// Help window header
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-GB" lang="en-GB">
<head>
<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/thecontrols.css">

<title><?=$SITE_TITLE . ': ' . $TITLE?></title>
<SCRIPT TYPE="text/javascript">
<?php /*### TODO: Move JS to a script file ###*/?>
// Close the help window and return to the original window.
function close_help()
    {
    if (!(window.focus && window.opener))
        return true;
    window.opener.focus();
    window.close();
    return false;
    }
</SCRIPT>

</head>
<body class="help_popup">

<h1><?=$TITLE?></h1>
<p><a href="<?=$RETURN_URL?>" onClick="return close_help()">Close this window</a></p>
<?php if (@$MESSAGE != '') print "<div class=\"$MESSAGE_CLASS\">$MESSAGE</div>" ?>
