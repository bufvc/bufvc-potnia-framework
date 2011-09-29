<?php
// $Id$
// Edit screen for database applications
// Phil Hansen, 5 Sep 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

$module_template = $MODULE->find_template('edit_' . $RECORD['_table']);
?>

<form method="POST">

<? if (@$URL_EDIT != ''): ?><p><a href="<?=$URL_EDIT?>">Edit this record</a></p><? endif ?>
<? if ($module_template != ''): /* Load module-specific template */
    require_once $module_template;
else: /* No specialised template, show common fields by default */ ?>
<dl class="edit-form">
<? if ($IS_NEW) : ?>
<dt>Slug</dt><dd><input type="text" name="slug" value="<?=htmlentities(@$_POST['slug'])?>" /></dd>
<? else : ?>
<p><a href="<?=$MODULE->url('index', $RECORD['url'])?>">View this record</a></p>
<? endif ?>
<dt>Title</dt><dd><input type="text" name="title" value="<?=htmlentities(isset($_POST['title']) ? $_POST['title'] : @$RECORD['title'])?>" /></dd>
<dt>Description</dt><dd><textarea name="description" rows="4" cols="55"><?=htmlentities(isset($_POST['description']) ? $_POST['description'] : @$RECORD['description'])?></textarea></dd>

<? if ($IS_NEW) : ?>
<dt></dt><dd><input type="submit" id="submit" value="Create" /></dd>
<? else : ?>
<dt></dt><dd><input type="submit" id="submit" value="Save" />
<input type="submit" name="delete" value="Delete" onClick="return confirm('Do you want to delete this record?');" /></dd>
<? endif ?>
</dl>
<? endif /* Default record */ ?>
</form>
<div style="clear:both"></div>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
