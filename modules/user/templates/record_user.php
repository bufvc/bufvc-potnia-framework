<?php
// $Id$
// View generic record for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>
<dl class='row'>
<? if (@$RECORD['login']): ?><dt>Login</dt><dd><?=$RECORD['login']?></dd><? endif ?>
<? if (@$RECORD['name']): ?><dt>Name</dt><dd><?=$RECORD['name']?></dd><? endif ?>
<? if (@$RECORD['email']): ?><dt>Email</dt><dd><?=$RECORD['email']?></dd><? endif ?>
<? if (@$RECORD['root']): ?><dt>Root</dt><dd>Yes</dd><? endif ?>
<? if (@$RECORD['rights_full']): ?>
<? 
$rights = Array();
foreach($RECORD['rights_full'] as $right)
    $rights[] = $right['title'];
?>
<dt>Rights</dt><dd><?=join('; ', $rights)?></dd>
<?endif?>
</dl>