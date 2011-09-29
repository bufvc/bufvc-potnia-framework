<?php
// $Id$
// Standard query filters
// James Fryer, 27 May 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// The QueryFilter can be added to the query->filters array. Filters are called in sequence
/// after a search or get_record operation
/// Filters can add blocks to the query->filter_info['sidebar'] array
class QueryFilter
    {
    /// Called before a search is performed.  The search criteria are passed
    /// to this function and can be modified.
    function before_search($query, &$criteria)
        {
        }
    /// Called after a search is performed. The results are passed to this function 
    /// and can be modified, note the Query class only stores results[data]
    /// A search filter typically adds data to $query->filter_info
    function after_search(&$results, $query, $criteria)
        {
        }
    /// Called after a record is retrieved.
    /// A retrieve filter typically adds data to $record
    function after_get_record(&$record, $query, $url, $params)
        {
        }
    }

/// This query filter looks for zero result searches, and adds helpful message/link
/// to $query->filter_info['suggest']
class ZeroResultsQueryFilter
    extends QueryFilter
    {
    var $random_record_msg = "Sorry, we found no results matching your search criteria. <a href=\"%s\">View a random record</a> from this resource.";

    function after_search(&$results, $query, $criteria)
        {
        if ($results['total'] == 0)
            {
            global $MODULE;
            $random_record = $query->module->retrieve($query->get_table() . '/!random');
            if (@$random_record['title'] == '')
                return;
            $result = Array('title'=>$random_record['title']);
            $result['location'] = $MODULE->url('index', $random_record['url']);
            $result['message'] = sprintf($this->random_record_msg, $result['location']);
            $query->filter_info['suggest'][] = $result;
            }
        }
    }

class ExportPrintFilter
    extends QueryFilter
    {
    var $printer__link = '<span class="label"><a href="#" onclick="javascript:window.print()">Print</a></span>';
    function after_search(&$results, $query, $criteria)
        {
        global $CONF;
        $hidden = $results['total'] == 0 || ($results['total'] > $CONF['max_export'] && $CONF['max_export'] != 0);
        $items = Array();
        $formats = $query->get_list('export_formats');
        foreach ($formats as $name=>$label)
            {
            if ($name == 'printer')
                $items[] = $this->printer__link;
            else {
                $items[] = Array(
                    'label' => $label,
                    'url' => $query->url(Array('format'=>$name)),
                    );
                }
            }
        $query->filter_info['sidebar'][] = new SidebarBlock('Save As', NULL, $items, NULL, $hidden);
        }

    function after_get_record(&$record, $query, $url, $params)
        {
        global $MODULE;
        $items = Array();        
        $module = @$record['module'] ? $record['module'] : $MODULE;
        $formats = $query->get_list('export_formats');
        foreach ($formats as $name=>$label)
            {
            if ($name == 'printer')
                $items[] = $this->printer__link;
            else {
                $items[] = Array(
                    'label' => $label,
                    'url' => $module->url('index', $url . '.' . $name),
                    );
                }
            }
        $record['sidebar'][] = new SidebarBlock('Save As', NULL, $items);
        }
    }

/// This query filter looks for Datasource facets in the search results,
/// and adds SearchResultsFacet blocks for each facet group.
class SearchResultsFacetsFilter
    extends QueryFilter
    {
    // recognized types of facet data (this also specifies display order)
    var $types = Array('facet_genre', 'facet_availability', 'facet_media_type');
    
    // titles lookup
    var $titles = Array(
        'facet_genre' => 'Genre',
        'facet_availability' => 'Availability',
        'facet_media_type' => 'Media Type',
        );
    
    // labels lookup
    var $labels = Array(
        'facet_genre' => Array(
            'tv' => 'Television',
            'radio' => 'Radio',
            'cinema' => 'Cinema news',
            'shakespeare' => 'Shakespeare productions',
            'other' => 'Other',
            ),
        'facet_availability' => Array(
            '30' => 'Online',
            '20' => 'To Order',
            '10' => 'Record only',
            ),
        'facet_media_type' => Array(
            'moving_image' => 'Moving Image',
            'audio' => 'Audio',
            'documents' => 'Documents',
            ),
        );
    
    function after_search(&$results, $query, $criteria)
        {
        if (isset($results['facets']) && @$results['total'] > 0)
            {
            foreach ($this->types as $type)
                {
                if (isset($results['facets'][$type]))
                    $query->filter_info['sidebar'][] = new SearchResultsFacet($this->titles[$type], '', $this->labels[$type], $type, $results['facets'][$type], $query);
                }
            }
        }
    }

/// Represents a list of a particular facet group with result counts
/// Media Type, Availability, or Genre
class SearchResultsFacet //### TODO: Change to QueryFacet? Once this code is integrated???
    extends SidebarBlock
    {
    var $help = Array(
        'facet_genre' => "A group of characteristics that links a set of records. 'Other' relates to records from datasets that cannot easily be classified by genre e.g. the FIND DVDs collection.",
		'facet_availability' => 'Some records contain or lead to moving image and sound assets. Availability describes how easily these can be obtained. If unavailable or availability is unknown, we provide record information.',
		'facet_media_type' => 'These are the types of assets that records contain or lead to.',
        );
		
    function __construct($title, $description, $labels, $type, $data, $query)
        {
        $items = Array();
        $this->selected = Array();
        $this->query = $query;
        // use the labels array to set the display order
        foreach ($labels as $name=>$label)
            {
            if (!isset($data[$name]) || $data[$name] == 0)
                continue;
            $items[] =  Array('label'=>$labels[$name], 'value'=>format_number($data[$name]), 
                    'url'=>$query->url(Array($type=>$name), 'page'));
            // store facets that are currently included in the query string
            if ($query->has_criteria(Array($type=>Array($name=>1))))
                $this->selected[$labels[$name]] = Array('type'=>$type, 'name'=>$name);
            // for availability: online - set auth warning flag
            if ($name == '30')
                $this->show_auth_warning = $this->_has_auth_results($query);
            }
        $hidden = empty($items);
        parent::__construct($title, $description, $items, @$this->help[$type], $hidden);
        }
    
    function is_selected($item)
        {
        if (!isset($item['label']))
            return FALSE;
        return isset($this->selected[$item['label']]);
        }
    
    // Add remove links for selected facets
    // Add authentication warning for Availability: Online if flag set
    function add_extra_text($item)
        {
        $result = '';
        // online - add auth warning
        if ($item['label'] == 'Online' && @$this->show_auth_warning)
            $result .= '<span title="Some results may require external login" class="tip-warning">(warning)</span>';
        // add remove link
        if ($this->is_selected($item))
            {
            $tmp = $this->selected[$item['label']];
            $result .= ' <a href="'.$this->query->url(NULL, $tmp['type']).'" class="remove-selected-facet">Remove</a>';
            }
        return $result;
        }
    
    // Helper function - check if the results include NoS (bund)
    function _has_auth_results($query)
        {
        //### TODO: push this into the modules themselves
        // check module
        if ($query->module->name == 'bund')
            return TRUE;
        // special case, fed
        if ($query->module->name == 'fed' && isset($query->info['components']))
            {
            foreach ($query->info['components'] as $comp)
                {
                if ($comp['module']->name == 'bund')
                    return TRUE;
                }
            }
        return FALSE;
        }
    }

/// This filter adds an iCalendar export link
class ExportICalendarFilter
    extends QueryFilter
    {
    function after_search(&$results, $query, $criteria)
        {
        global $CONF;
        $hidden = $results['total'] == 0 || ($results['total'] > $CONF['max_export'] && $CONF['max_export'] != 0);
        $items = Array();
        $items[] = Array(
            'label' => 'iCalendar',
            'url' => $query->url(Array('format'=>'ical')),
            );
        $query->filter_info['sidebar'][] = new SidebarBlock('Export iCalendar Data', NULL, $items, NULL, $hidden);
        }

    function after_get_record(&$record, $query, $url, $params)
        {
        global $MODULE;
        $items = Array();        
        $module = @$record['module'] ? $record['module'] : $MODULE;
        $items[] = Array(
            'label' => 'iCalendar',
            'url' => $module->url('index', $url . '.ical'),
            );
        $record['sidebar'][] = new SidebarBlock('Export iCalendar Data', NULL, $items);
        }
    }

/// Protect media records using Ironduke URL shrouding system
class MediaLocationFilter
    extends QueryFilter
    {
    function after_get_record(&$record, $query, $url, $params)
        {
        global $CONF, $MODULE;
        if (!isset($record['media']))
            return;
        for ($i = 0; $i < count($record['media']); $i++)
            {
            if (@$record['media'][$i]['location'] != '')
                {
                $record['media'][$i]['orig_location'] = $record['media'][$i]['location'];
                //### FIXME Assumes all media contains no scheme and points to physical files, global module is record module  etc
                if (@$CONF['url_ironduke'] == '')
                    $location = $CONF['url_media'] . '/' . $query->module->name . '/' . $record['media'][$i]['location'];
                else {
                    $scheme = 'file://';
                    $scheme .= $query->module->name . '/';
                    $location = $this->create_ticket($scheme . $record['media'][$i]['location']);
                    }
                $record['media'][$i]['location'] = $location;
                }
            }
        }
    
    /// Create an Ironduke ticket
    /// Protected so we can replace in tests
    protected function create_ticket($resource_url)
        {
        global $CONF;
        if (@$CONF['url_ironduke'] != '')
            {
            $ironduke = new Ironduke($CONF['url_ironduke']);
            $ticket = $ironduke->create_ticket($resource_url);
            return $ticket['download_url'];
            }
        else
            return $resource_url;
        }
    }

/// For empty searches (all default criteria) set error code and helpful message
/// Setting the query error code stops the query from running the search
class BlockAllFieldsSearchFilter
    extends QueryFilter
    {
    var $blocked_msg = "Sorry, empty searches returning all records have been disabled for this collection. Please set some search criteria.";
    
    function before_search($query, $criteria)
        {
        $tmp = clone($query);
        if (!is_null($criteria))
            $tmp->set_criteria_values($criteria);
        $has_criteria = FALSE;
        
        foreach($tmp->criteria_container as $name=>$criterion)
            {
            // skip any criteria that are not encoded with query
            if( !$criterion->is_encodable )
               continue;
            if (!$criterion->is_set_to_default())
                $has_criteria = TRUE;
            }
        
        if (!$has_criteria)
            {
            $query->error_code = QUERY_ERROR_CRITERIA;
            $query->error_message = $this->blocked_msg;
            }
        }
    }

/// Check the query criteria for spelling errors and put suggestions
/// in $query->filter_info['spelling']
/// It also adds to $query->filter_info['suggest'] for display with search results
class SpellingCorrectorFilter
    extends QueryFilter
    {
    var $msg = 'Did you mean: <a href="%s">%s</a>';
    
    function after_search(&$results, $query, $criteria)
        {
        if (empty($criteria['q']))
            return;
        $corrector = new SpellingCorrector();
        // advanced query
        if (is_array($criteria['q']))
            {
            $result = Array();
            $new_criteria = Array('q'=>$criteria['q']);
            foreach ($criteria['q'] as $key=>$item)
                {
                if (!isset($item['v']) || empty($item['v']))
                    continue;
                $spelling = $corrector->check($item['v']);
                if (!empty($spelling))
                    {
                    $result[$key] = Array('spelling'=>$spelling, 'index'=>@$item['index']);
                    $new_criteria['q'][$key]['v'] = $spelling;
                    }
                }
            if (!empty($result))
                {
                $query->filter_info['spelling'] = $result;
                $tmp_query = clone($query);
                // get the string for display
                $tmp_query->set_criteria_values($new_criteria);
                $display_string = $tmp_query->criteria_string(QUERY_STRING_TYPE_TEXT);
                $display_string = explode(':', $display_string);
                $display_string = $display_string[1];
                // set the new criteria and generate the url
                $new_criteria_full = $criteria;
                $new_criteria_full['q'] = $new_criteria['q'];
                if (isset($new_criteria_full['adv_q1']))
                    $new_criteria_full['adv_q1'] = $new_criteria['q'];
                $tmp_query->set_criteria_values($new_criteria_full);
                $url = $tmp_query->url();
                // build the display array
                $display = Array();
                $display['message'] = sprintf($this->msg, $url, $display_string);
                $query->filter_info['suggest'][] = $display;
                }
            }
        // basic query
        else
            {
            $spelling = $corrector->check($criteria['q']);
            if (!empty($spelling))
                {
                $query->filter_info['spelling'] = $spelling;
                $url = $query->url(Array('q'=>$spelling));
                $result = Array();
                $result['message'] = sprintf($this->msg, $url, $spelling);
                $query->filter_info['suggest'][] = $result;
                }
            }
        }
    }
