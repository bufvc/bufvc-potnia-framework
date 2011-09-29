<?php
// $Id$
// Iframe template for external links 
// Alistair Macdonald, 15 June 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-GB" lang="en-GB">
<head>
    <link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/reset.css" media="all" charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/grid.css" media="screen, tv" charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/default.css" media="screen, tv" charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="<?=$CONF['url']?>/css/link.css" media="screen, tv" charset="utf-8" />
	<script type="text/javascript" src="http://cdn.jquerytools.org/1.2.5/full/jquery.tools.min.js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $(".summary-info").tooltip({
                position: 'bottom center'
            });
        });
    </script>
    <title><?=$TITLE?></title>
</head>

<body class="module-<?=$MODULE->name?>">
<div id="link-header">
    <a href="<?=$CONF['url']?>" title="BUFVC &middot; Homepage" target="_top"><img src="<?=$CONF['url']?>/components/bufvc_logo_solo.gif" alt="BUFVC Logo" style="float:left;width:42px;height:44px;margin-right:5px"/></a>
    <h2 class="bufvc" style="margin-top:0;"><a href="<?=$CONF['url']?>" title="BUFVC &middot; Homepage" target="_top">British Universities Film &amp; Video Council</a></h2>
    <div class="link-nav">
        <a href="<?=$URL?>" target="_top" class="remove-frame">Remove this BUFVC frame [x]</a>
        <div class="record-title">External Site: <?=@$RECORD['title']?> 
        <?if(@$RECORD['description']):?>
            <a href="#site-summary" class="summary-info">[info]</a>
            <div id="site-summary" class="site-summary" style="display:none"><?=@$RECORD['description']?></div>
        <?endif;?>
        </div>
        <? @include_once $CONF['path_templates'] . 'inc-link_record_navigation.php';?>
    </div>
</div>
<div id="frame"><iframe src="<?=$URL?>" width="100%" height="100%"></iframe></div>
</body>
</html>
