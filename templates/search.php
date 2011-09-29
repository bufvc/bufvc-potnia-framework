<?php
// $Id$
// Search form for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

// Used to decide which screen elements to show
$has_results = count($QUERY->results) > 0;
$has_criteria = $QUERY->has_criteria();
// Should we use Javascript to hide the search form?
// First ask the config, then the GET var, then only hide it if there are results
$hide_search_form = !$CONF['always_show_search_form'] && !$SHOW_SEARCH_FORM && $has_results;

?>
<? if ($has_results || $has_criteria): ?>
<? if ($CONF['module_mode'] == 'simple'): /* Show search summary with simple menu */?>
<?=$QUERY->criteria_string(QUERY_STRING_TYPE_HTML,'edit')?>
<? endif /* Simple menu mode */ ?>
<? if (@$QUERY->filter_info['suggest']): foreach($QUERY->filter_info['suggest'] as $suggestion):?>
<p><?=$suggestion['message']?></p>
<? endforeach; endif ?>

<? if (!$CONF['always_show_search_form']): ?>
<ul class="search-form-menu">
<? if ($hide_search_form): ?><li class="refine-search"><a href="javascript:show_hide('search-form')">Refine search</a></li><? endif ?>
<? if ($CONF['module_mode'] == 'simple'): /* Show new search link here in simple module mode*/ ?>
<li><a href="<?=$QUERY->url_new()?>">New search</a></li>
<? endif ?>
</ul>
<? endif ?>
<? endif ?>

<div class="search-form clearfix" id="search-form" <?=($hide_search_form)?'style="display:none;"':''?>>
<?=$QUERY->search_form($ADVANCED_SEARCH ? 'advanced' : 'basic') ?>
</div>

<? if ($has_results): ?>
<? // paging top and bottom are diferent... so I've added argument to function, default paging display at the top
function print_paging()
    {
    global $CONF, $QUERY;
?>
<div class="results-paging">
	<div class="results-messages"><?=$QUERY->info['results_message']?> | <?=$QUERY->info['page_message']?></div>

<div class="paging clearfix">
<? if ($QUERY->info['page_first_url']): ?><a href="<?=$QUERY->info['page_first_url']?>" class="first">&laquo;&nbsp;First</a><? else: ?><span class="first">&laquo;&nbsp;First</span><? endif ?>
<div class="spacer clearfix">
<? if ($QUERY->info['page_prev_url']): ?><a href="<?=$QUERY->info['page_prev_url']?>" class="prev">&lsaquo;&nbsp;Prev</a><? else: ?><span class="prev">&lsaquo;&nbsp;Prev</span><? endif ?>
<? if (!empty($QUERY->info['page_urls'])): ?>
<? foreach ($QUERY->info['page_urls'] as $page_num=>$page_url)
        {
        if ($QUERY->page == $page_num)
            print("<span class='page-num-current'>".format_number($page_num)."</span>");
        else
            print("<a href=\"$page_url\">".format_number($page_num)."</a>");
        } ?>
<?endif?>
<? if ($QUERY->info['page_next_url']): ?><a href="<?=$QUERY->info['page_next_url']?>">Next&nbsp;&rsaquo;</a><? else: ?><span>Next&nbsp;&rsaquo;</span><? endif ?>
</div>
<? if ($QUERY->info['page_last_url']): ?><a href="<?=$QUERY->info['page_last_url']?>" class="last">Last&nbsp;&raquo;</a><? else: ?><span class="last">Last&nbsp;&raquo;</span><? endif ?>

</div>

<div class="results-actions">
	<? if (count(@$QUERY->get_list('sort')) > 1): ?>
	    Sort results by  
        <?= html_query_criterion_list($QUERY, 'sort', Array(
	        'class' => 'resort',
	        'data-request_uri' => $_SERVER['REQUEST_URI'],
	        'data-url' => $QUERY->url(Array('sort'=>''), 'page'),
	        'data-current' => @$QUERY['sort']->get_value(),
	        ), FALSE )?>
	<? endif ?>

<? if ($QUERY->info['results_count'] <= $CONF['max_export']): ?>
<select name="export" onchange="export_format(this.value)"><?= html_options($QUERY->get_list('export_formats'), '', 'Save results as')?></select>

<SCRIPT TYPE="text/javascript">
// reload the page with the selected export format specified
function export_format(format)
    {
    url = location.href;
<? if (substr_count($_SERVER['REQUEST_URI'], '?') == 1): ?>
    url += '&';
<? else: ?>
    url += '?';
<? endif ?>
    if (format != '')
        window.location = url + 'format=' + format;
    }
</SCRIPT>
<? endif ?>

</div>
</div>
<? } // end function print_paging

print_paging();
?>

<?if ($CONF['marked_records_size']):?>
<form action="<?=$MODULE->url('marked')?>" method="POST" class="results_form" name="mark_records">
<? endif ?>
<ol class="results" id="results">
<? $highlight = ($QUERY->page == 1) ? @$QUERY->info['highlighted_record'] : NULL; ?>
<?=format_record_list($QUERY->results, $result_urls, -1, FALSE, $highlight)?>
</ol>

<div class="results-actions">
<?if ($CONF['marked_records_size']):?>
<input type="hidden" name="all-marked-url" value="<?=$app_menu['marked']['url']?>" />
<input type="hidden" name="results" value="<?=implode(';', $result_urls) ?>" />
<input type="hidden" name="redirect_url" value="<?=$QUERY->url() ?>" />
<? if (!$CONF['submit_forms_with_script']): ?>
<input type="submit" name="mark_results" value="Mark Selected Records" />
<? endif /* JS buttons */ ?>
<? if ($QUERY->info['results_count'] + $MARKED_RECORDS->count() <= $CONF['marked_records_size']): ?>
<input type="submit" name="mark_all_results" value="Mark All Records" />
<? endif /* mark all button */ ?>
<? if (!$CONF['submit_forms_with_script'] || $QUERY->info['results_count'] + $MARKED_RECORDS->count() <= $CONF['marked_records_size']): ?>
<a href="<?=$MODULE->url('help', '/marked')?>"  onClick="return popup_help(this)">About marked records&nbsp;&raquo;</a>
<? endif /* help text */ ?>
</form>
<? endif /* Marked records */ ?>
</div>
<? if ($QUERY->info['page_count'] > 1) print_paging(); ?>
<? endif ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
