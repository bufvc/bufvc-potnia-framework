<?php
// $Id$
// Template utilities
// James Fryer, 25 Sept 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class HermesTitleRecordSummary
    extends RecordSummary
    {
	// icon for collection shakespeare
	var $collection_icon = array('src'=>'icon_collection_hermes.png', 'type'=>'collection', 'alt'=>'DVD Find');
	
    function summarize($record, $summary)
        {
        $tmp = Array();
        if (@$record['date'] != '')
            $tmp[] = substr($record['date'], 0, 4);
        if (isset($record['format']) && !is_array($record['format']))
            $tmp[] = $record['format'];
        else if (is_array(@$record['format']))
            {
            $formats = Array();
            foreach ($summary['format'] as $format)
                {
                if (isset($format['title']))
                    $formats[] = $format['title'];
                }
            if (count($formats) > 0)
                $tmp[] = join(', ', $formats);
            }
        if ($tmp)
            $summary['heading_info'] = join(' ', $tmp);
        if (@$record['alt_title'] != '')
            $summary['subtitle'] = "aka: {$record['alt_title']}";
        $summary['fields'] = Array(
            Array('label'=>'Director', 'value'=>@$record['director']),
            Array('label'=>'Producer', 'value'=>@$record['producer']),
            Array('label'=>'Subject', 'value'=>@$record['subject']),
            Array('label'=>'Distribution', 'value'=>@$record['distribution']),
            );
		// add collection icon
		if (@$this->collection_icon) {
			$summary['icons'][] = $this->collection_icon;
		}

        return $summary;
        }
    }
