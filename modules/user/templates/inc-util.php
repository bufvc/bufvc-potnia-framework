<?php
// $Id$
// Template utilities
// James Fryer, 25 Sept 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class UserUserRecordSummary
    extends RecordSummary
    {
    function summarize($record, $summary)
        {
        $summary['heading'] = $record['login'];
        $summary['location'] = $this->module->url('edit', $record['url']);
        $summary['fields'] = Array(
            Array('label'=>'Name', 'value'=>@$record['name']),
            Array('label'=>'Email', 'value'=>@$record['email'], 'url'=> 'mailto:' . @$record['email']),
            );
        return $summary;
        }
    }
