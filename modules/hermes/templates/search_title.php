<?php
// $Id$
// Search form for Hermes
// Phil Hansen, 06 Apr 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$is_advanced = $MODE == 'advanced';
?>

<fieldset class="searchset-<?=$MODE?>">
<?php if (!$is_advanced): ?>
	<?= html_query_criterion_input($QUERY, 'q', Array(
        'placeholder'=>"Leave blank for all records",
         ), TRUE ); ?>
		<?php else:  // is advanced search then generate three field sets ?>
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
		<?php endif ?>
<?php // submit button for either basic or advanced search ?>
<input type="submit" id="submit" value="Search" />
</fieldset>
<fieldset>
	<legend>Date range</legend>
      <label for ="date_start">Year</label>
      <?= html_query_date_list($QUERY, 'date_start')?>
      <label for="date_end" class="label_between">to</label>
      <?= html_query_date_list($QUERY, 'date_end')?>
      <?= html_query_criterion_help($QUERY, 'date')?>
		<a href="#" title="Reset date range" class="all-dates js-only" id="year">&laquo;&nbsp;Reset date</a>
</fieldset>
<fieldset>
      <?= html_query_criterion_checkbox($QUERY, 'title_format', null, true)?>
</fieldset>
<fieldset>
	<legend>Category</legend>
	<?= html_query_criterion_list($QUERY, 'category', null, true)?>
</fieldset>
<? if ($is_advanced): ?>
		<fieldset>
			<legend>Locale</legend> <?// I need to find a better label ?>
        <?= html_query_criterion_list($QUERY, 'country', NULL, TRUE )?><br />
        <?= html_query_criterion_list($QUERY, 'language', NULL, TRUE )?>
		</fieldset>
    <? if ($USER->has_right('edit_record')): ?>
        <fieldset>
            <legend>Viewfinder</legend>
            <?= html_query_criterion_checkbox($QUERY, 'viewfinder')?>
        </fieldset>
    <? endif /*editors*/?>
<? endif /*Advanced*/?>



<? /*--- Results displaying - Sort/paging controls ----*/?>
	<fieldset class="sort_by_set">
		<legend>Results display</legend>
	    <ul>
	        <li><?= html_query_criterion_list($QUERY, 'sort', NULL, TRUE )?></li>
            <li><?= html_query_criterion_list($QUERY, 'page_size', NULL, TRUE )?> results per page</li>
	    </ul>
		<div class="fieldset-help">
			<p>Help tooltip goes here...</p>
		</div>
	</fieldset>


<? /*--- Help on searching and advanced search links ---*/?>
    <fieldset id="more-options">
            <a href="<?=$MODULE->url('search', '?mode=' . ($is_advanced ? 'basic' : 'advanced') . '&editquery=1')?>"><?=$is_advanced ? 'Basic search' : 'Advanced search'?></a><span>&nbsp;|&nbsp;</span>
            <a href="<?=$MODULE->url('help', '/' . ($is_advanced ? 'advsearch' : 'search'))?>" target="_blank">Help on searching</a>
		<? if ($CONF['always_show_search_form']): ?>
            <span>&nbsp;|&nbsp;</span><a href="<?=$MODULE->url('search', '?mode=new')?>">New search</a>
		<? endif ?>
    </fieldset>
