<?php
// $Id$
// Edit screen for user module
// Phil Hansen, 05 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>

<dl class="edit-form">
	<fieldset class="editset">
<? if ($IS_NEW) : ?>
<dt>Slug</dt><dd><input type="text" name="slug" value="<?=htmlentities(@$_POST['slug'])?>" /></dd>
<? endif ?>
<dt>Name</dt><dd><input type="text" name="name" value="<?=htmlentities(isset($_POST['name']) ? $_POST['name'] : @$RECORD['name'])?>" /></dd>
<dt>Title</dt><dd><input type="text" name="title" value="<?=htmlentities(isset($_POST['title']) ? $_POST['title'] : @$RECORD['title'])?>" /></dd>
</fieldset>
<fieldset class="submitset">
<? if ($IS_NEW) : ?>
<dt></dt><dd><input type="submit" id="submit" value="Create" /></dd>
<? else : ?>
<dt></dt><dd><input type="submit" id="submit" value="Save" />
<input type="submit" name="delete" value="Delete" onClick="return confirm('Do you want to delete this record?');" /></dd>
<? endif ?>
</fieldset>
</dl>