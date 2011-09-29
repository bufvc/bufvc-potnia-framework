<?php
// $Id$
// Default index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<h3>Hermes</h3>
<p>This is a demo module named Hermes. It is a database of 1000 DVD titles that you are free to use to familiarise yourself with the functionality within the BUFVC Potnia Framework. Exploring the default interfaces for features like Marked Records and Search History may help when it comes to modifying the code for these features.</p>

<div class="search-form clearfix">
<?=$QUERY->search_form('homepage')?>
</div>

<div style="clear:both"></div>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
