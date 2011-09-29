<?php
// $Id$
// Search help page
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$TITLE='Help on searching';

require_once $CONF['path_templates'] . 'inc-help_header.php';
?>
<p>Doing a search is easy; the quickest way is to use Basic Search, and just to enter your search terms (the word or phrase that describes what you're looking for) in the search box and click the search button. All search relies on you, the user, entering the right search terms to find what you're looking for.</p>

<p>You can use <a href="<?=$MODULE->url('help', '/searchtips')?>">special language and symbols</a> to refine the words you use in the search box.</p>

<p>You can refine any basic search using the drop-down menus. </p>

<p>You can use 'sort by' and 'results per page' to decide how you wish to see the search results which are returned. </p>

<p><i>Basic Search</i> is the usual option, and will be how you search until you choose <a href="<?=$MODULE->url('help', '/advsearch')?>"><i>Advanced Search</i></a> on the search page.</p>

<h2>More</h2>
<ul class="default">
<li><a href="<?=$MODULE->url('help', '/searchtips')?>">Search Tips</a></li>
<li><a href="<?=$MODULE->url('help', '/advsearch')?>">Advanced Search</a></li>
<li><a href="<?=$MODULE->url('help', '/results')?>">About Search Results</a></li>
</ul>

<?php require_once $CONF['path_templates'] . 'inc-help_footer.php' ?>
