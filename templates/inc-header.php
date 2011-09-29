<?php
// $Id$
// Header for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-GB" lang="en-GB">
<head>
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/reset.css" media="all" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/grid.css" media="screen, tv" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/default.css" media="screen, tv" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/advanced-form.css" media="screen, tv" charset="utf-8" />
	<?= $MODULE->include_stylesheet() ?>
	<!--[if lte IE 7]><link rel="stylesheet" href="<?=$CONF['url']?>/css/ie7.css" type="text/css" media="screen, tv, projector" /><![endif]-->
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/print.css" media="print" charset="utf-8" />
    <script type="text/javascript" src="<?=$CONF['url']?>/js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="<?=$CONF['url']?>/js/potnia.js"></script>
	<?=$MODULE->include_javascript()?>
	<?=@$CONF['analytics_js']?>
	<title><?=$TITLE  . ' &middot; ' . $SITE_TITLE?></title>
</head>

<body class="module-<?=$MODULE->name?>">
<div class="page">
<div class="header-wrapper clearfix">
		<div class="column_24 header" id="header">
			<div class="column_10 branding">
				<div class="column_2 logotype"><a href="<?=$CONF['url']?>" title="BUFVC &middot; Homepage"><img src="<?=$CONF['url']?>/components/bufvc_logo_solo.gif" alt="BUFVC Logo" style="float:left" width="67" height="70" /></a></div>
				<div class="column_8 last_column" >
                    <h3 class="slogan"><em>BUFVC</em></h3>
					<h2 class="bufvc"><a href="<?=$CONF['url']?>" title="BUFVC &middot; Homepage">Potnia</a></h2>
					<h3 class="slogan" style="margin-top:10px"><em>Framework</em></h3>
				</div>
			</div>
			<div class="column_14 last_column head-nav">
			</div>
			<!-- login button -->
			<div id="login-wrapper">					
				<?php if (!$USER->is_registered()): ?>
					<a href="<?=$CONF['url_login'] . '?url=' . urlencode(get_current_url())?>" id="login" class="logged-out"><span class="logged-status">You're are logged out - </span>Login</a>
				<?php else: ?>
				 	<a href="<?=$CONF['url_login']?>?mode=logout" id="login" class="logged-in"><span class="logged-status">You're are logged in - </span>Logout</a>
				<?php endif ?>
			</div>
		</div>
</div> <!-- Header ends -->
<div class="title-wrapper"><h2 class="title-project"><?=$SITE_TITLE?></h2></div>
<div class="body-wrapper">
<div class="content clearfix" id="content">

	<div class="column_5 contnav-wrapper">
	<div class="contnav" id="content-navigation"><? include_once $CONF['path_templates'] . 'inc-main_navigation.php' ?></div>
	</div>
	
	<div class="column_14 center-column <?=@$TEMPLATE?>-content">
	<h1><?=$TITLE?></h1>
	<?=(@$MESSAGE)?"<div id=\"status_message\" class=\"$MESSAGE_CLASS\">$MESSAGE</div>":"<div id=\"status_message\" class=\"info-message\"></div>"?>
