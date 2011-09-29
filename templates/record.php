<?php
// $Id$
// View generic record for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

$module_template = $MODULE->find_template('record_' . Module::table_from_url($RECORD['_table']));
$marked_url = $MODULE->get_marked_url($RECORD);
$citation_template = $MODULE->find_template('citation_' . Module::table_from_url($RECORD['_table']));

?>
<div class="record" id="record-display">
<?php if (($CONF['record_navigation_position'] & 1) != 0) require $CONF['path_templates'] . 'inc-record_navigation.php'; ?>

<? if ($module_template != ''): /* Load module-specific template */
    require_once $module_template;
else: /* No specialised template, show common fields by default */ ?>
<dl class="row">
<? if (@$RECORD['title'] != ''): ?><dt>Title</dt><dd><?=$RECORD['title']?></dd><? endif ?>
<? if (@$RECORD['name'] != ''): ?><dt>Name</dt><dd><?=$RECORD['name']?></dd><? endif ?>
<? if (@$RECORD['date'] != ''): ?><dt>Date</dt><dd><?=$RECORD['date']?></dd><? endif ?>
<? if (@$RECORD['description'] != ''): ?><dt>Description</dt><dd><?=$RECORD['description']?></dd><? endif ?>
</dl>
<? endif /* Default record */ ?>

<?= (isset($RECORD['copyright'])) ? '<p class="copyright">' . $RECORD['copyright'] . '</p>' : '' ?>
</div> <!-- record id -->

<? /* Mark individual record */
// Added back to allow tests to work
//### FIXME: location of marked records should be configurable
if ($CONF['unit_test_active'] && $RECORD != '' && isset($RECORD['url']) && $CONF['marked_records_size'] > 0):
?>
<div id="mark-individual-record">
<form action="<?=$MODULE->url('marked')?>" method="POST">
<input type="checkbox" name="<?=$marked_url?>" value="1" <?=$MARKED_RECORDS->exists($marked_url) ? 'checked="checked"' : '' ?> class="mark-checkbox" data-url_record="<?=$marked_url?>" /> <label>Mark or unmark the record.</label>
<input type="hidden" name="all-marked-url" value="<?=$app_menu['marked']['url']?>" />
<input type="hidden" name="url" value="<?=$marked_url?>" />
<input type="hidden" name="redirect_url" value="<?=$MODULE->url('index', $RECORD['url'])?>" />
<? if ($MARKED_RECORDS->exists($marked_url)): ?>
	<input class="ie-fix" type="submit" name="unmark_record" value="Unmark Record" /> <span id="record-status">This record is marked</span>.
<? else: ?>
	<input class="ie-fix" type="submit" name="mark_record" value="Mark Record" /><br>
<? endif ?>
<a href="<?=$MODULE->url('help', '/marked')?>"  onClick="return popup_help(this)">About marked records&nbsp;&raquo;</a>
</form>
</div>
<? endif /* Mark individual record */ ?>

<?php if (($CONF['record_navigation_position'] & 2) != 0) require $CONF['path_templates'] . 'inc-record_navigation.php'; ?>

<? if (@$URL_EDIT != ''): ?><p><a href="<?=$URL_EDIT?>">Edit this record</a></p><? endif ?>
<?=(isset($RECORD['view_count'])) ? '<div class="record-stats"><h4 class="toggle-next">Record Stats</h4><div class="query-enhancer-content"><p>' . $RECORD['view_count_msg'] . '</p></div></div>' : '' ?>

<? /* Show raw record data in debug view */ ?>
<? if ($CONF['debug']): ?>
<p><a href="javascript:show_hide('debug-record')">Raw record data</a></p>
<div id="debug-record" style="display:none">
<pre>
<?
//### TODO: move to utils
function recursive_unset(&$array, $unwanted_key) {
    unset($array[$unwanted_key]);
    foreach ($array as &$value) {
        if (is_array($value)) {
            recursive_unset($value, $unwanted_key);
        }
    }
}
$tmp_record = $RECORD;
recursive_unset($tmp_record, 'module');
print_r($tmp_record);
?>
</pre>
</div>
<? endif /* Debug view*/ ?>

<?php /*### TEMP: add sidebar blocks TODO: make this configurable ###*/
// Added in reverse order
// Citation
$block = new Block('citation'); 
$block->set_vars(Array('CONF'=>$CONF, 'RECORD'=>$RECORD, 'MODULE'=>$MODULE));
array_unshift($SIDEBAR, $block);
// Mark record
$block = new Block('mark_record'); 
$block->set_vars(Array('CONF'=>$CONF, 'RECORD'=>$RECORD, 'MODULE'=>$MODULE, 'MARKED_RECORDS'=>$MARKED_RECORDS, 'USER'=>$USER));
array_unshift($SIDEBAR, $block);
?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
