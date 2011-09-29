<? /* Mark record sidebar block */ 
$marked_url = $MODULE->get_marked_url($RECORD); 
$app_menu = $MODULE->menu($USER);
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

