<?php
// $Id$
// Search help page
// James Fryer, 6 Nov 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$TITLE='Search tips';

require_once $CONF['path_templates'] . 'inc-help_header.php';
?>
<p>Searches are not case sensitive &mdash; so London England is the same as london enGlaNd or LONDON ENGLAND.</p>

<h2>Using And, Or and Not</h2>
<p>The term <b>and</b> narrows a search, retrieving only results including all of the words you have used. In our search, we always assume that if you have included multiple words you mean 'and', so you don't need to include it.</p>

<p>The term <b>or</b> broadens a search, retrieving results including at least one of the words you have used, but not necessarily both.</p>

<p>The term <b>not</b> allows you to exclude words from a search, and is useful where a word has more than one meaning.</p>

<h2>Symbols In Search</h2>
<p>The use of <b>"</b>speech marks<b>"</b> around your search phrase will help you to narrow your search to find results that contain the exact phrase you're looking for. While a search using 'and' will look for those two words anywhere in results, a search using speech marks will find those two words together, side-by-side.</p>

<p>You can also use parentheses <b>(</b>brackets<b>)</b> alongside And, Or and Not to refine your search. For example, entering the search expression <b>(hip or knee) and arthroplasty</b> will retrieve those guidelines containing the terms hip and arthroplasty or the terms knee and arthroplasty.</p>

<h2>Stem Search</h2>

<p>A <b>Stem search</b> can help you find variations on your search terms. An asterisk <b>*</b> is used for a stem search, and will help you find words starting with your search term; for example, <b>earth*</b> will return results for 'earth' and 'earthquake' and so on.</p>

<h2>Stopwords</h2>
<p>Some words will be ignored if you use them, because they don't help refine your search and mean it will take longer to get you results. These are 'Stopwords'.</p>

<p>Stopwords are: and, are, for, he's, i'd, i'll, i'm, i've, isn't, it'd, it'll, it's, its, off, she, than, that, that's, thats, the, this, was.</p>

<p>All words of one character are also stopwords, for example, 'I' or 'a'.</p>

<h2>More</h2>
<ul class="default">
<li><a href="<?=$MODULE->url('help', '/search')?>">Help on Searching</a></li>
<li><a href="<?=$MODULE->url('help', '/advsearch')?>">Advanced Search</a></li>
<li><a href="<?=$MODULE->url('help', '/results')?>">About Search Results</a></li>
</ul>

<?php require_once $CONF['path_templates'] . 'inc-help_footer.php' ?>
