<?php
// $Id$
// Record paging for view record screen
// James Fryer, 18 May 09
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
        $record_prev_url = $RECORD['record_prev_url']['module']->url('index', $RECORD['record_prev_url']['url']);
    else
        $record_prev_url = $MODULE->url('index',  $RECORD['record_prev_url']);
    $tmp[] = "<a href=\"$record_prev_url\" class=\"previous-page\">&lsaquo;&nbsp;Previous</a>\n";
    }
if (isset($RECORD['record_next_url']))
    {
    if (is_array($RECORD['record_next_url']))
        $record_next_url = $RECORD['record_next_url']['module']->url('index', $RECORD['record_next_url']['url']);
    else
        $record_next_url = $MODULE->url('index',  $RECORD['record_next_url']);
    $tmp[] = "<a href=\"$record_next_url\" class=\"next-page\">Next&nbsp;&rsaquo;</a>\n";
    }

	// print record pagination only if there are pointers 
	if (count($tmp) > 0) {
		print ('<div class="paging">'.join($tmp).'</div>'); 
	}
?>
<? if (isset($RECORD['results_message'])): ?><div class="results-messages" style="text-align:center"><span><?=$RECORD['results_message']?></span> | <a href="<?=$QUERY->url()?>">&laquo;&nbsp;Back to results</a></div><? endif ?>
<? endif ?>
