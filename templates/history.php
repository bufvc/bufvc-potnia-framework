<?php
// $Id$
// Search History
// James Fryer, 16 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

function history_format_query($query)
    {
    global $CONF;
    $result = '<li><dl>' . $query['text'];
    $result .= '<dd><a href="' . $query['url'] . '">'. $query['info']['results_message_unpaged'] . "</a></dd></dl>";
    return $result;
    }
?>

<div id='tabs-container'>
<h3 class="tab-title" id="performed-searches">Search History</h3>
<div class="querylist" id="searches">
<? if (count($QUERYLIST) > 0): ?>
<ol>
<?php
foreach ($QUERYLIST->outline() as $tmp)
    {
    print history_format_query($tmp['head']);
    if (count($tmp['tail']))
        {
        print "<ol>";
        foreach ($tmp['tail'] as $query)
            print history_format_query($query);
        print "</ol>";
        }
    }
?>
</ol>
<form action="<?=$MODULE->url('history')?>" method="POST" name="clear_history">
<input type="submit" name="clear_history" value="Clear History" onclick="javascript:return confirm('Are you sure you want to clear all searches from the history?')" />
</form>
<? else: ?>
<div><p>No searches</p></div>
<? endif /* QUERYLIST */ ?>
</div>

<h3 class="tab-title" id="viewed-records">Viewed Records</h3>
<div class="recordlist" id="viewed">
<? if (count($RECORDLIST) > 0): ?>
	<?if ($CONF['marked_records_size']):?>
	<form action="<?=$MODULE->url('marked')?>" method="POST" class="results_form" name="mark_records">
	<? endif ?>
<ol class="results">
<?=format_record_list($RECORDLIST, $result_urls)?>
</ol>

<?if ($CONF['marked_records_size']):?>
<div class="results-actions">
<input type="hidden" name="results" value="<?=implode(';', $result_urls) ?>" />
<input type="hidden" name="redirect_url" value="<?=$MODULE->url('history')?>/viewed" />
<? if (!$CONF['submit_forms_with_script']): ?>
<input type="submit" name="mark_results" value="Mark Selected Records" />
<? endif /* JS buttons */ ?>
<? if ((count($RECORDLIST) + $MARKED_RECORDS->count()) <= $CONF['marked_records_size']): ?>
<input type="submit" name="mark_all_viewed" value="Mark All Records" />
<? endif /* mark all button */ ?>
</div>
</form>
<form action="<?=$MODULE->url('history')?>" method="POST" name="clear_viewed">
<input type="submit" name="clear_viewed" value="Clear History" onclick="javascript:return confirm('Are you sure you want to clear all viewed records from the history?')" />
</form>
<? endif /* Marked records */ ?>

<? else: ?>
<div><p>No viewed records</p></div>
<? endif /* RECORDLIST */ ?>
</div>

<?if ($CONF['marked_records_size']):?>
<h3 class="tab-title" id="marked-records">Marked Records</h3>
<div class="recordlist" id="marked">
<? if ($MARKED_RECORDS->count() > 0): ?>
<form method="POST" name="mark_records" action="<?=$MODULE->url('marked')?>">
<p style="margin-bottom:1em;text-align:right"><select name="export" onchange="export_format(this.value)"><?= html_options($QUERY->get_list('export_formats'), '', 'Save marked records as')?></select></p>
<SCRIPT TYPE="text/javascript">
// reload the page with the selected export format specified
function export_format(format)
    {
    url = '<?=$MODULE->url('marked')?>';
    url += '?';
    if (format != '')
        window.location = url + 'format=' + format;
    }
</SCRIPT>
<ul class="results">
<?=format_record_list($MARKED_RECORDS->get_all())?>
</ul>
<input type="hidden" name="redirect_url" value="<?=$MODULE->url('history')?>/marked" />
<input type="<?= $CONF['submit_forms_with_script'] ? 'hidden' : 'submit' ?>" name="update" value="Mark Selected Records" />
<input type="submit" name="unmark_all" value="Unmark All Records" onclick="javascript:return confirm('Are you sure you want to unmark all records?')" />
</form>
<? else: ?>
<p>No marked records</p>
<? endif ?>
<? if (!$CONF['submit_forms_with_script'] || (count($RECORDLIST) + $MARKED_RECORDS->count()) <= $CONF['marked_records_size']): ?>
<br /><a href="<?=$MODULE->url('help', '/marked')?>"  onClick="return popup_help(this)">About marked records&nbsp;&raquo;</a>
<? endif /* help text */ ?>
</div>
<? endif /* Marked Records*/ ?>

</div> <!-- tabs container -->
<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>

