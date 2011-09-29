<?php
// $Id$
// View generic record for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
?>
<p>Using template record_test2</p>
<dl class="record">
<dt>Title</dt><dd><?=$RECORD['title']?></dd>
</dl>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
