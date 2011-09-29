<?php
// $Id$
// Marked Records
// Phil Hansen, 17 Sept 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
?>

<? if ($MARKED_RECORDS->count() > 0): ?>
<form method="POST" name="mark_records" action="<?=$MODULE->url('marked')?>">
<p style="margin-bottom:1em;text-align:right"><select name="export" onchange="export_format(this.value)"><?= html_options($QUERY->get_list('export_formats'), '', 'Save marked records as')?></select></p>
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
<ul class="results">
<?=format_record_list($MARKED_RECORDS->get_all())?>
</ul>
<input type="<?= $CONF['submit_forms_with_script'] ? 'hidden' : 'submit' ?>" name="update" value="Mark Selected Records" />
<input type="submit" name="unmark_all" value="Unmark All Records" onclick="javascript:return confirm('Are you sure you want to unmark all records?')" />
</form>
<? else: ?>
<p>No marked records</p>
<? endif ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
