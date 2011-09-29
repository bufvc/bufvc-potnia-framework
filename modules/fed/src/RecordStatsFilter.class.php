<?php
// $Id$
// Plugin to track record views
// James Fryer, 1 Nov 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Track the number of times a record has been viewed
class RecordStatsFilter
    extends QueryFilter
    {
    function after_get_record(&$record, $query, $url, $params)
        {
        global $CONF;
        $module = Module::load('fed');
        $ds = $module->get_datasource();
        $result = $ds->retrieve('/stats' . $url);
        $record['view_count'] = @$result['count'];
        $record['view_count_msg'] = 'This record has been viewed ' . @$result['count'] . ' ' . pluralise(@$result['count'], 'time') . '.';
        $record['sidebar'][] = new RecordScoreFacet($record, $query, $result, $url, $CONF['url'] . '/score.php');
        }
    }

// Show the users the score for this record. Allow them to vote on the record
class RecordScoreFacet
    extends SidebarBlock
    {
    var $msg = '';
    function __construct($record, $query, $stats, $url, $vote_url)
        {
        global $USER, $CONF;
        $allow_guest_votes = $CONF['debug'] && !defined('UNIT_TEST');
        $can_vote = $USER->has_right('save_data') || $allow_guest_votes;
        $show_score = $stats['score'] > 0;
        if (!($can_vote || $show_score))
            $hidden = 1;
        if ($show_score)
            {
            $avg = (int)($stats['score_avg'] * 100);
            $this->msg .= "{$stats['score']} out of {$stats['score_count']} people found this record useful, a rating of $avg%.";
            }
        if ($can_vote)
            {
            $redirect_url = $record['module']->url('index', $url);
            $this->msg .= <<<_EOT_
<form method="post" action="$vote_url">
Was this record useful to you? 
<select name="score"><option value="1">Yes</option><option value="0">No</option></select>
<input type="hidden" value="$url" name="record">
<input type="hidden" value="$redirect_url" name="redirect_url">
<input type="submit" value="Rate">
</form>
_EOT_;
            }
        parent::__construct('Rate this record', $this->msg, NULL, NULL, @$hidden);
        }
    }
