<?php
// $Id$
// Citation template
// James Fryer, 26 Mar 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Look for module-specific template
$citation = $MODULE->get_template('citation_' . Module::table_from_url($RECORD['_table']), Array('RECORD'=>$RECORD, 'MODULE'=>$MODULE)); 
// No module-specific citation, use default
if (is_null($citation))
    {
    $citation = '';
    if (@$RECORD['title'] != '')
        $citation .= '"' . $RECORD['title'] . '"'; 
    }
// Add boilerplate
$citation .= '; ' . $MODULE->url('index', $RECORD['url']) . ' (Accessed '. strftime('%d %b %Y') . ')'; 

print htmlentities($citation, ENT_QUOTES, "UTF-8");
