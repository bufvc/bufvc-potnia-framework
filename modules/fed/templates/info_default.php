<?php
// $Id$
// Default index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
$is_registered = $USER->is_registered();

$meta = $MODULE->retrieve('/fed/item');

?>	
<p><strong>BUFVC hosts, curates and delivers 9 substantial online databases relating to film, television and radio content dating from 1896 onwards.</strong> You can now search more than 13 million records in one go, or if you wish, one collection at a time. The content and access of individual collections is listed below.</p>
<p>For more information on this beta site see the <a href="http://bufvc.ac.uk/federatedsearch" title="The new BUFVC federated search environment  &middot; British Universities Film &amp; Video Council">‘about’ page on the BUFVC website</a>. We welcome your feedback.</p>
<? if (!$is_registered): ?>
<p>If you are a BUFVC member or from a HE/FE institution <a href="<?= $CONF['url_login'] ?>" title="Please login" class="remember-login"><strong>please remember to login</strong></a> to enable fuller access.</p>
<? endif ?>

<div class="search-form clearfix">
<?=$QUERY->search_form('homepage')?>
</div>
<div style="clear:both"></div>
<div id="all-collections">
<h2>BUFVC Collections</h2>
<p>Use the links below to explore the individual BUFVC collections.</p>
<ul class="collections-list">
<? foreach ($meta['components'] as $resource):
$module = $resource['module'];

$name = $module->name;
$title = $module->title;

$is_radio = in_array($name, Array('lbc', 'ilrsouth', 'ilrsharing'));
$is_auth_needed = in_array($name, array('trilt', 'tvtip', 'thisweek', 'lbc', 'ilrsouth', 'ilrsharing'));

$tmp = "";

// Tritl only users
if ($name == 'trilt') {
	if ( $name == 'trilt' && (!$is_registered || !$USER->has_right('trilt_user')))
		$tmp .= ' Searches limited to the last two weeks.';
	if ( $name == 'trilt' && !$USER->has_right('trilt_user') && $is_registered )
	    $tmp .=  ' <span class="collection-name-link"><a href="'.$module->url().'" title="Link to '.$title.' collection">' . $title . '</a></span> is available only to BUFVC members. <a href="http://bufvc.ac.uk/membership">Becoming a member?</a>.';
	else if ($name == 'trilt' && !$is_registered)
	$tmp .= '<span>&nbsp;<span class="collection-name-link"><a href="'.$module->url().'" title="Link to '.$title.' collection">' . $title . '</a></span> is available to BUFVC members only, </span>';
} else if ($is_radio) {
	if (!$USER->has_right('play_audio') || !$is_registered) {
		$tmp .= '<span><span class="collection-name-link"><a href="'.$module->url().'" title="Link to '.$title.'" collection>' . $title . '</a></span> has full access to metadata. Audio files are available to HE/FE users only. </span>';
	} 
} else if ($is_auth_needed && !$is_registered) {  // Not trilt, not radio, must be tvtip or thisweek
	$tmp .= '<span><span class="collection-name-link"><a href="'.$module->url().'" title="Link to '.$title.' collection">' . $title . '</a></span> is available to BUFVC members and HE/FE users, </span>';
}

// User is not logged in, add login link
if ($is_auth_needed && !$is_registered )
    $tmp .= 'please&nbsp;<a class="login-link" href="'.$CONF['url_login'].'" title="Log into BUFVC authenticated services">Login&nbsp;<span class="login-link-arrow">&raquo;</span></a>';
?>

<li class="module-<?=$module->name?>">
	<h3><a href="<?=$module->url()?>"><?=$resource['title']?></a></h3>
	<h4><?=$resource['subtitle']?></h4>
	<p><?=$resource['description']?></p>
	<?php if ($tmp != "") : ?>
		<p class="information-collection"><?php print $tmp; ?></p>
	<?php endif ?>
</li>
<? endforeach ?>
</ul>
</div>
<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
