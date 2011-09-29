<?php
// $Id$
// Edit screen for database applications
// Phil Hansen, 5 Sep 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
?>
<? if (count(@$TABLES) > 0): ?>
<p>Create a new record of type:</p>
<ul class="results">
<?
foreach ($TABLES as $table)
    {
    if (@$table['mutable'] == true) // table is mutable
        print("<li><a href=\"" . $MODULE->url('edit', '/' . $table['key']) . "\">$table[title]</a></li>\n");
    else // not mutable
        print("<li>$table[title]</li>\n");
    } ?>
</ul>
<? endif ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
