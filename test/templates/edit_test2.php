<?php
// $Id$
// Edit screen for database applications
// Phil Hansen, 5 Sep 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>
<p>Using template edit_item_test2</p>

<form method="POST">
<? if ($IS_NEW) : ?>
Slug: <input type="text" name="slug" value="<?=htmlentities(@$_POST['slug'])?>" /><br />
<? endif ?>
Title: <input type="text" name="title" value="<?=htmlentities(isset($_POST['title']) ? $_POST['title'] : @$RECORD['title'])?>" /><br />
Summary: <input type="text" name="summary" value="<?=htmlentities(isset($_POST['summary']) ? $_POST['summary'] : @$RECORD['summary'])?>" /><br />

<? if ($IS_NEW) : ?>
<input type="submit" id="submit" value="Create" />
<? else : ?>
<input type="submit" id="submit" value="Save" />
<input type="submit" name="delete" value="Delete" onClick="return confirm('Do you want to delete this record?');" />
<? endif ?>
