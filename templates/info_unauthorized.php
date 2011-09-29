<?php
// $Id$
// Default unauthorized info page
// Phil Hansen, 07 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<h3><?=@$MODULE->subtitle?></h3>
<p><?=@$MODULE->description?></p>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
