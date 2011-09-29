<?php
// $Id$
// Search help page
// Phil Hansen, 31 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$TITLE='About Search Results';

require_once $CONF['path_templates'] . 'inc-help_header.php';
?>
<p>Once you've clicked the search button, results will be shown in a list below the search form. Results are shown as a list of records from the database, with a title and a brief summary text.</p>

<p>How the list is shown is defined by how you set 'sort by' and 'results per page' on the search form. If you didn't use these options, the page will show the oldest results first, and ten results on each page. To view a record from your search results, click the title.</p>

<p>The number of pages of results is shown in a bar above the individual results; if you have more than one page, click 'Previous' or 'Next' to move between pages.</p>

<h2>More</h2>
<ul class="default">
<li><a href="<?=$MODULE->url('help', '/search')?>">Help on Searching</a></li>
<li><a href="<?=$MODULE->url('help', '/searchtips')?>">Search Tips</a></li>
<li><a href="<?=$MODULE->url('help', '/advsearch')?>">Advanced Search</a></li>
</ul>

<?php require_once $CONF['path_templates'] . 'inc-help_footer.php' ?>
