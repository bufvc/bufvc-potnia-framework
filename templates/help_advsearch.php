<?php
// $Id$
// Search help page
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$TITLE='Advanced search';

require_once $CONF['path_templates'] . 'inc-help_header.php';
?>
<p>To use 'Advanced Search', select this option at the bottom of the search page. In Advanced Search, you can carry out up to three searches, to return one set of refined results. Each search an be across all fields, or can be refined to a search of one field in the database using the drop-down menu next to the search box.</p>

<p>All search relies on you, the user, entering the right search terms to find what you're looking for. In each of the three search boxes, you can use <a href="<?=$MODULE->url('help', '/searchtips')?>">special words and symbols</a> to refine the text you put in each search box. These are: 'and', 'or' and 'not'; some symbols like speech marks "; and asterisk for a 'stem search'.</p>

<p>You can also use the 'and', 'or' and 'not' drop-down menus to build a relationship between each of the three search boxes as well.</p>

<p>You can use 'sort by' and 'results per page' to decide how you wish to see the search results which are returned.</p>

<p>Once you have selected 'Advanced Search' the database will assume you want to use this option every time you choose 'Search' from the menu or to start a 'New Search' from the search page. To return to the simpler option, click 'Basic Search' at the bottom of the search page.</p>

<h2>More</h2>
<ul class="default">
<li><a href="<?=$MODULE->url('help', '/search')?>">Help on Searching</a></li>
<li><a href="<?=$MODULE->url('help', '/searchtips')?>">Search Tips</a></li>
<li><a href="<?=$MODULE->url('help', '/results')?>">About Search Results</a></li>
</ul>

<?php require_once $CONF['path_templates'] . 'inc-help_footer.php' ?>
