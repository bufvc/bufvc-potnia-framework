<?php
// $Id$
// Search form for User
// Phil Hansen, 04 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$is_advanced = $MODE == 'advanced';
?>

<div class="form_leftcol">
    <? if (!$is_advanced): ?>
        <fieldset class="searchset">
            <?= html_query_criterion_input($QUERY, 'q', Array('placeholder'=>"Leave blank for all records"), TRUE ); ?> in all fields
        </fieldset>
    <? else: /* Advanced search form*/ ?>
        <fieldset class="advancedset">
        <?php
            $criterion = $QUERY['q'];
        for ($i = 0; $i < $criterion->advanced_value_count; $i++ )
            {
            $root_key = $criterion->get_qs_key() . '['. $i . ']';
            if ($i == 0)
                print '<ol><li><label for="' . $root_key . '[v]">' . $criterion->label . ':</label>';
            else
                {
                print '<li><label for="' . $root_key . '[oper]">';
                print '<select name="' . $root_key . '[oper]" id="' . $root_key . '[oper]">';
                print html_options($QUERY->get_list('boolean_op'), @$criterion->get_operator($i) );
                print '</select></label>';
                }
            print '<input type="text" name="' . $root_key . '[v]" id="' . $root_key . '" value="' . htmlspecialchars(@$criterion->get_value($i), ENT_QUOTES) . '" />';
            print '<label class="label_between" for="' . $root_key . '[index]">in</label>';
            print '<select name="' . $root_key . '[index]" id="' . $root_key . '[index]">';
            print html_options($QUERY->get_list( $criterion->get_list() ), @$criterion->get_index($i));
            print '</select></li></ol>';
            }
        ?>
        </fieldset>
    <? endif /*Basic/Advanced*/?>
<fieldset class="controlset"><ol>

<? if ($is_advanced): ?>

<? endif /*Advanced*/?>
</ol></fieldset>
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
<? /* Sort/paging controls */?>
    <fieldset class="sort_by_set">
        <ul>
            <li><?= html_query_criterion_list($QUERY, 'sort', NULL, TRUE )?></li>
            <li><?= html_query_criterion_list($QUERY, 'page_size', NULL, TRUE )?> results per page</li>
        </ul>
    </fieldset>
</div>
