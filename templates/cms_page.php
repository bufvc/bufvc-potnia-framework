<?php
// $Id$
// Page to display CMS information
// James Fryer, 5 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// This page looks for an array $CMS with entries title (opt) and content

if (@$CMS['title'] != '')
  $TITLE = $CMS['title'];

require_once $CONF['path_templates'] . 'inc-header.php';

print $CMS['content'];
?>
<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
