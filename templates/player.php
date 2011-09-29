<?php
// $Id$
// Pop-up player window
// Phil Hansen, 04 Nov 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$TITLE = 'Playing: ' . $RECORD['title'];

// Get playlist URL
$playlist_url = $MODULE->url('index', $RECORD['url'] . '?format=xspf');
// check for a specified file
if (isset($_REQUEST['file']))
    $playlist_url .= '&file='.$_REQUEST['file'];
$playlist_url = urlencode($playlist_url);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-GB" lang="en-GB">
<head>
<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/thecontrols.css">
<title><?=$SITE_TITLE . ': ' . $TITLE?></title>
<SCRIPT TYPE="text/javascript">
<?php /*### TODO: Move JS to a script file ###*/?>
// Close the player window and return to the original window.
function close_player()
    {
    if (!(window.focus && window.opener))
        return true;
    window.opener.focus();
    window.close();
    return false;
    }
</SCRIPT>
</head>

<body>
<h1 class="title_project"><?=$SITE_TITLE?></h1>
<h2><?=$TITLE?></h2>

<? if (count(@$RECORD['media']) > 0): ?>
<div id="player">
<embed
  src="<?=$CONF['url']?>/mediaplayer/mediaplayer.swf"
  width="470"
  height="20"
  bgcolor="#ffffff"
  allowscriptaccess="always"
  allowfullscreen="true"
  flashvars="file=<?=$playlist_url?>&autostart=true"
/>
</div>
<? endif ?>

<p><a href="<?=$RECORD['url']?>" onClick="return close_player()">Close</a></p>

</body>
</html>