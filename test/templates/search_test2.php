<?php
// $Id$
// Search form for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// If you need globals other than CONF, MODULE and QUERY declare them
global $SHOW_SEARCH_FORM;

$is_advanced = $MODE == 'advanced';
?>
<div class="form_leftcol">
<p>table:test2</p>
    <fieldset class="searchset">
        <?= html_query_criterion_input($QUERY, 'q', Array('placeholder'=>"Leave blank for all records"), TRUE ); ?> in all fields
    </fieldset>
</div>

<? /* Search button and other controls*/?>
<div class="form_rightcol last">
    <fieldset class="submitset"><input type="submit" id="submit" value="Search" />
        <ol>
            <li><a href="<?=$MODULE->url('search', '?mode=' . ($is_advanced ? 'basic' : 'advanced') . '&editquery=1')?>"><?=$is_advanced ? 'Basic search' : 'Advanced search'?>&nbsp;&raquo;</a></li>
            <li><a href="<?=$MODULE->url('help', '/' . ($is_advanced ? 'advsearch' : 'search'))?>"  onClick="return popup_help(this)">Help on searching&nbsp;&raquo;</a></li>
<? if ($CONF['always_show_search_form']): ?>
            <li><a href="<?=$MODULE->url('search', '?mode=new')?>">New search&nbsp;&raquo;</a></li>
<? endif ?>
        </ol>
    </fieldset>
</div>
