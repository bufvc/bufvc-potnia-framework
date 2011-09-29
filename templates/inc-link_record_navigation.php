<?php
// $Id$
// Record paging for external link frame
// Phil Hansen, 26 April 11
// Modified from inc-record_navigation.php template (James Fryer, 18 May 09)
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


if (@$QUERY != NULL && $QUERY->info['results_count'] > 0):

 	// it will collect pointers
	$tmp = array(); 

// Handle prev/next urls
// This can be a record URL or an array with 'url' and 'module' (for federated searches)
//### FIXME: normalise this. E.g. have next/prev record pointers?
if (isset($RECORD['record_prev_url']))
    {
    if (is_array($RECORD['record_prev_url']))
        $record_prev_url = $RECORD['record_prev_url']['module']->url('link', $RECORD['record_prev_url']['url']);
    else
        $record_prev_url = $MODULE->url('link', $RECORD['record_prev_url']);
    $tmp[] = "<a href=\"$record_prev_url\" class=\"previous-page\" target=\"_top\">&lsaquo;&nbsp;Previous</a>\n";
    }

if (isset($RECORD['record_next_url']))
    {
    if (is_array($RECORD['record_next_url']))
        $record_next_url = $RECORD['record_next_url']['module']->url('link', $RECORD['record_next_url']['url']);
    else
        $record_next_url = $MODULE->url('link', $RECORD['record_next_url']);
    $tmp[] = "<a href=\"$record_next_url\" class=\"next-page\" target=\"_top\">Next&nbsp;&rsaquo;</a>\n";
    }

if (isset($RECORD['results_message'])): 
    $tmp[] = '<span class="nav-message">'.$RECORD['results_message'].' | <a href="'.$QUERY->url().'" target="_top">&laquo;&nbsp;Back to results</a></span>';
endif;

	// print record pagination only if there are pointers 
	if (count($tmp) > 0) {
		print ('<div class="paging">'.join($tmp).'</div>'); 
	}
?>
<? endif ?>
