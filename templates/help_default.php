<?php
// $Id$
// Default help page
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-help_header.php';

?>
<ul class="default">
<li><a href="<?=$MODULE->url('help', '/search')?>">Help on Searching</a></li>
<li><a href="<?=$MODULE->url('help', '/searchtips')?>">Search Tips</a></li>
<li><a href="<?=$MODULE->url('help', '/advanced')?>">Advanced Search</a></li>
<li><a href="<?=$MODULE->url('help', '/results')?>">About Search Results</a></li>
<li><a href="<?=$MODULE->url('help', '/marked')?>">Marking Records</a></li>
<li><a href="<?=$MODULE->url('help', '/saved')?>">Saving Searches</a></li>
<li><a href="<?=$MODULE->url('help', '/player')?>">Playing Media</a></li>
</ul>

<?php require_once $CONF['path_templates'] . 'inc-help_footer.php' ?>
