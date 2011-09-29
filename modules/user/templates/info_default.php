<?php
// $Id$
// Default index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<h3>Users</h3>
<p>The User module manages all of the users.</p>
<p><a href="<?=$MODULE->url('search', '?q=')?>">Browse all users</a>.</p>

<div class="search-form clearfix">
<?=$QUERY->search_form('homepage')?>
</div>

<p>Links to more info about the project will go here. </p>

<div style="clear:both"></div>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
