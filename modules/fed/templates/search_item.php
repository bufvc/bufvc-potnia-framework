<?php
// $Id$
// Search form for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$is_advanced = $MODE == 'advanced';
?>

<fieldset class="searchset-<?=$MODE?>">
<? if (!$is_advanced): ?>	
		<?= html_query_criterion_input($QUERY, 'q', Array(
	        'placeholder'=>"Leave blank for all records",
	         ), TRUE ); ?>

<? else: /* Advanced search form*/ ?>
    <ol class="advanced-input">
<?php
    $criterion = $QUERY['q'];
for ($i = 0; $i < $criterion->advanced_value_count; $i++ )
    {
    $root_key = $criterion->get_qs_key() . '['. $i . ']';
    if ($i == 0)
        print '<li><label for="' . $root_key . '[v]">' . $criterion->label . ':</label>';
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
    print '</select></li>';
    }
?>
</ol>
<? endif /*Basic/Advanced*/?>
	<input type="submit" id="submit" value="Search" />
</fieldset>


<? /* Dates and other criteria */ ?>

<fieldset>
	<legend>Date range</legend>
	<label for="date_start">Year:</label>
	<?= html_query_criterion_list($QUERY, 'date_start' )?>
	<label for="date_end">to</label>
	<?= html_query_criterion_list($QUERY, 'date_end' )?>
	<?= html_query_criterion_help($QUERY, 'date')?>
	<a href="#" title="Reset date range" class="all-dates js-only" id="year">&laquo;&nbsp;Reset date</a>
</fieldset>
<?php  if ($is_advanced): /*--- Collections ---*/?>
<fieldset>
	<?= html_query_criterion_checkbox($QUERY, 'components', NULL, TRUE)?>
</fieldset>
<?php /*--- Media Types ---*/?>
<fieldset>
	<?= html_query_criterion_checkbox($QUERY, 'facet_media_type', NULL, TRUE)?>
</fieldset>
<?php /*--- Availability ---*/?>
<fieldset>
	<?= html_query_criterion_checkbox($QUERY, 'facet_availability', NULL, TRUE)?>
</fieldset>
<?php /*--- Genre ---*/?>
<fieldset>
	<?= html_query_criterion_checkbox($QUERY, 'facet_genre', NULL, TRUE)?>
</fieldset>
<? endif /*Advanced*/?>

<fieldset class="sort_by_set">
	<legend>Results display:</legend>
    <ul>
        <li><?= html_query_criterion_list($QUERY, 'sort', NULL, TRUE )?></li>
        <li><?= html_query_criterion_list($QUERY, 'page_size', NULL, TRUE )?> results per page</li>
    </ul>
</fieldset>

    <? /*--- Results displaying - Sort/paging controls ----*/?>

	<? /*--- Help on searching and advanced search links ---*/?>
	    <fieldset id="more-options">
	            <a href="<?=$MODULE->url('search', '?mode=' . ($is_advanced ? 'basic' : 'advanced') . '&editquery=1')?>"><?=$is_advanced ? 'Basic search' : 'Advanced search'?></a><span>&nbsp;|&nbsp;</span>
	            <a href="<?=$MODULE->url('help', '/' . ($is_advanced ? 'advsearch' : 'search'))?>" target="_blank">Help on searching</a>
			<? if ($CONF['always_show_search_form']): ?>
	            <span>&nbsp;|&nbsp;</span><a href="<?=$MODULE->url('search', '?mode=new')?>">New search</a>
			<? endif ?>
	    </fieldset>
