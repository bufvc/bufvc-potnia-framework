<?php
// $Id$
// Default index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<h3><?=@$MODULE->subtitle?></h3>
<p><?=@$MODULE->description?></p>
<p>To search the test database, use the form below.</p>

<div class="search-form clearfix">
<?
$search_form = $QUERY->search_form('homepage');
if ($search_form == '')
    $search_form = "<p>Template not defined: <b>{$QUERY->table_name}</b>";
print $search_form;
?>
</div>

<div style="clear:both"></div>
<? /* Further info links etc */ ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
