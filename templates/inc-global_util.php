<?php
// $Id$
// Presentation utilities for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Get the HTML utilities
require_once $CONF['path_src'] . '/basic/htmlutil.inc.php';

// BUFVC facet bitfield values
define('FACET_MOVING_IMAGE', 0x1);
define('FACET_AUDIO', 0x2);
define('FACET_DOCUMENTS', 0x4);
define('FACET_30', 0x80);
define('FACET_20', 0x100);
define('FACET_10', 0x200);
define('FACET_TV', 0x4000);
define('FACET_RADIO', 0x8000);
define('FACET_CINEMA', 0x10000);
define('FACET_SHAKESPEARE', 0x20000);
define('FACET_OTHER', 0x40000);

define('FACET_ICON_WIDTH', 16);
define('FACET_ICON_HEIGHT', 16);

/// Provide a summary of a single record
///### TODO: merge with exportformatter???
class RecordSummary
    {
    /// Should we show the module in the field list? Useful in multi-module mode
    //### or have a default list of fields
    var $show_module_in_field_list = FALSE;
    
    /// Icon mappings for facet bitfield values
    var $icon_mappings = Array(
        FACET_TV           => Array('src'=>'icon_genre_tv.png', 'type'=>'genre', 'alt'=>'Television'),
        FACET_RADIO        => Array('src'=>'icon_genre_radio.png', 'type'=>'genre', 'alt'=>'Radio'),
        FACET_CINEMA       => Array('src'=>'icon_genre_cinemanews.png', 'type'=>'genre', 'alt'=>'Cinema news'),
        FACET_SHAKESPEARE  => Array('src'=>'icon_genre_shk.png', 'type'=>'genre', 'alt'=>'Shakespeare productions'),
        FACET_OTHER        => Array('src'=>'icon_genre_other.png', 'type'=>'genre', 'alt'=>'Other'),
        FACET_DOCUMENTS    => Array('src'=>'icon_media_document.png', 'type'=>'media_type', 'alt'=>'Digitised Documents'),
        FACET_30           => Array('src'=>'icon-online.png', 'type'=>'availability', 'alt'=>'Online'),
        FACET_20           => Array('src'=>'icon-toorder.png', 'type'=>'availability', 'alt'=>'To order'),
        FACET_10           => Array('src'=>'icon-recordonly.png', 'type'=>'availability', 'alt'=>'Record only'),
        FACET_MOVING_IMAGE => Array('src'=>'icon_media_movingimage.png', 'type'=>'media_type', 'alt'=>'Moving image'),
        FACET_AUDIO        => Array('src'=>'icon_media_audio.png', 'type'=>'media_type', 'alt'=>'Audio'),
        );

    // Icon type titles
    //### TODO: centralise this somewhere (e.g. strings.php???)
    var $icon_type_titles = Array(
        'collection' => "Collection",
        'media_type' => "Media Types",
        'genre' => "Genre",
        'availability' => "Availability",
        'extras' => "Extras",
        );
    
    /// Extract summary data from the record
    /// This can be anything supported by the render function but at present:
    ///     heading         The title of the record as displayed in the summary
    ///     heading_info    Additional information to display in the heading, e.g. date
    ///     location        The full URL of the item
    ///     subtitle        Displayed beneath the heading
    ///     fields          Array of (label=>,value=>,[url=>])
    ///     icons           Array of (src=>,alt=>)
    ///###  TODO: fields list could be generated from DS??? although this way is quicker
    ///     description     A paragraph of text about the record
    function summarize($record, $summary)
        {
        return $summary;
        }
    
    /// Convert the record summary to HTML
    function render($summary)
        {
        global $CONF;
        $result = "<h3><a href=\"" . $summary['location'] . "\">$summary[heading]";
        if (@$summary['heading_info'] != '')
            $result .= ' (' . $summary['heading_info'] . ')';
        $result .= "</a>";
        $result .= "</h3>\n";
        if (@$summary['subtitle'] != '')
            $result .= "<h4>{$summary['subtitle']}</h4>\n";
        if (@count($summary['fields']))
            {
            $tmp = Array("<dl>\n");
            foreach ($summary['fields'] as $f)
                {
                if (@$f['value'] != '')
                    {
                    if (@$f['url'] != '')
                        $tmp[] = "<dt>{$f['label']}</dt><dd><a href=\"{$f['url']}\">{$f['value']}</a></dd>\n";
                    else
                        $tmp[] = "<dt>{$f['label']}</dt><dd>{$f['value']}</dd>\n";
                    }
                }
            $tmp[] = "</dl>\n";
            $result .= join('', $tmp);;
            }
        $result .= '<p>' . $summary['description'] . '</p>';
		
		// collect the icons in groups but first 
		// find collection icon and move it to the begining
		if (@count($summary['icons'])) 
            {			
			// Move icon collection temp vars
			$tmp['icons'] = array();
			$tmp2 = array();			
			foreach ($summary['icons'] as $icon) 
                {
				if (@$icon['type'] != 'collection') 
					$tmp['icons'][] = $icon;
				else
					$tmp2 = $icon;
                }
			
			// insert icon collection at the begining and restore summary icons
			array_unshift($tmp['icons'], $tmp2);
			$summary['icons'] = $tmp['icons'];
			
			// show panel of icons and extras, collection goes first
			$result .= '<div class="record-icons">';			

			foreach ($summary['icons'] as $icon) 
                {
				if (@$icon['type'] != '') 
                    {
                    $url = $CONF['url_images'] . '/' . $icon['src'];
                    $result .= " <span class=\"record-icons-label-wrapper\">";
					$result .= "<img src=\"$url\" alt=\"".$icon['alt']."\" title=\"".$this->icon_type_titles[$icon['type']].": ".$icon['alt']."\" width=\"" . FACET_ICON_WIDTH . "\" height=\"" . FACET_ICON_HEIGHT . "\">";
					$result .= "<span class=\"record-icons-label\"> ".$icon['alt']."</span></span>";
                    }
                }

			// close icons panel
			$result .= '</div>';
            }
		
        return $result;
        }
    
    /// Convert the record summary to minimal HTML -- title and URL only
    function render_minimal($summary)
        {
        return '<a href="' . $summary['location'] . '">' . $summary['heading'] . '</a>';
        }

    /// Format a record 
    /// Mode can be 'minimal' for a one-line summary or null for full summary
    function format($record, $mode=NULL)
        {
        $summary = $this->_summarize_default($record);
        if ($mode == 'minimal')
            return $this->render_minimal($summary);
        else
            return $this->render($summary);
        }
    
    function __construct($module)
        {
        $this->module = $module;
        }
    
    // If you want to change the default, change 'summarize' above
    private function _summarize_default($record)
        {
        //# Heading has parts: title, date, url, 
        $result = Array();
        $result['location'] = @$record['location'];
        if ($result['location'] == '')
            $result['location'] = $this->module->url_record($record);
        $result['heading'] = @$record[title];
        if ($result['heading'] == '')
            $result['heading'] = '<em>(Untitled record)</em>';
        //### FIXME: should have format string for date
        if (@$record['date'] != '')
            $result['heading_info'] = substr($record['date'], 0, 4);
        $result['description'] = $this->description($record);
        if (isset($record['facet_details']))
            $result['icons'] = $this->_get_icons($record['facet_details']);
        $result = $this->summarize($record, $result);
        if ($this->show_module_in_field_list)
            $result['fields'][] = Array('label'=>'Collection', 'value'=>@$record['module']->title);
		else
			$result['collection'] = array('label'=>'Collection', 'value'=>@$record['module']->title);
        return $result;
        }

    // Helper functions 
    function description($record)
        {
        global $CONF;
        //### need to strip tags -- should be under test
        if (isset($record['summary']))
            $result = $record['summary'];
        else if (isset($record['description']))
            $result = $record['description'];
        if (@$result != '')
            return string_truncate($result, $CONF['summary_length'] + 10);
        }
    
    // Helper function - parse bitfield values and gather icons
    function _get_icons($facet_details)
        {
        $result = Array();
        foreach ($this->icon_mappings as $value=>$icon)
            {
            if ($value & $facet_details)
                $result[] = $icon;
            }
        return $result;
        }
    }

/// Format an array of records for display
/// $result_urls is filled with a list for 'mark all' commands
// Added extra argument in order to get titles only to display in menu
function format_record_list($records, &$result_urls=NULL, $count=-1, $titles_only=FALSE, $highlight=NULL)
    {
    global $CONF, $MODULE, $USER, $MARKED_RECORDS;
    $result = Array();
    $result_urls = Array();
    $summary_mode = $titles_only ? 'minimal' : NULL;
	$count = $count > 0 ? min($count, count($records)) : count($records);
    $index = 1;
    foreach ($records as $record)
        {
        if (--$count < 0)
            break;
        // Get module for this record
        if (isset($record['modname']))
            $module = Module::load($record['modname']);
        else if (isset($record['module']))
            $module = $record['module'];
        else
            $module = $MODULE;
        $marked_url = $module->get_marked_url($record);
        $result_urls[] = $marked_url;
		// check if it's marked
		if ($CONF['marked_records_size'] && $summary_mode != 'minimal')
		    {
		    // Add marked records checkbox
		    $marked_checkbox = "<input type=\"checkbox\" name=\"$marked_url\" value=\"1\" class=\"mark-checkbox results-checkbox\"";
		    if ($MARKED_RECORDS->exists($marked_url))
		        $marked_checkbox .=  "checked=\"checked\" ";
		    // add AJAX call
		    if ($CONF['submit_forms_with_script'])
		        $marked_checkbox .= ' data-url_record="' . $marked_url . '" ';
		    $marked_checkbox .= "/>";
		    }
		$css_class = "results-record";
        $css_class .= ($index == $highlight) ? ' highlighted-record' : '';

		$str = "<li class=\"{$css_class}\">";
        
		$str .= @$marked_checkbox;
        $summary = $module->new_record_summary(@$record['_table']);
        $str .= $summary->format($record, $summary_mode);
        if ($module->can_edit($USER) && $module->name != 'user')
            $str .= "<p class=\"results-editthis\"><a href=\"".$module->url_edit($record). "\">Edit this record</a></p>\n";
        $result[] = $str . '</li>';	
        $index++;
        }
    return join("\n", $result);
    }

/// Format an array of records for display, titles only
function format_record_list_titles($records, $count=-1)
    {
    $dummy_ref = NULL; // O PHP how I love thee...
    return format_record_list($records, $dummy_ref, $count, TRUE);
    }

/// Renders a QueryCriterions related help text
function html_query_criterion_help($query, $name)
    {
    $criterion = $query->criteria_container->get_by_qs_key($name);
    $help = $criterion->get_help_text();
    $result = '';
    
    if( !is_null($help) )
        {
        $result .= '<span class="qc_help" id="' . $name . '_help">';
        $result .= $help;
        $result .= '</span>';
        }
    return $result;
    }

/// Format an input field 
function html_query_criterion_input($query, $name, $additional_fields=NULL, $also_render_label=FALSE)
    {
    $criterion = $query->criteria_container->get_by_qs_key($name);
    $value = $criterion->get_value();
    $result = '';
    
    if( $also_render_label )
        {
        $result .= '<label for="' . $name . '">' . $criterion->label . ':</label>';
        }
    
    $result .= '<input type="text" id="' . $name . '" name="' . $name . '" value="';

    $result .= htmlspecialchars( $value, ENT_QUOTES );
    $result .= '" ';
    
    if( !is_null($criterion->render_default) )
        $result .= 'data-default="' . $criterion->render_default . '" data-default_colour="gray" data-default_colour_off="black" ';
        
    if( $additional_fields )
        foreach( $additional_fields as $field_name=>$field_value )
            {
            $result .= $field_name;
            $result .= '="' . $field_value . '" ';
            }
        
    $result .= '/>';
    
    $result .= html_query_criterion_help($query, $name);
    return $result;
    }
    
// Formats a date list
// date lists do not have help fields rendered after them
function html_query_date_list($query, $name)
    {
    return html_query_criterion_list( $query, $name, NULL, FALSE, Array( 'render_help' => FALSE ) );
    }

/// Format a SELECT list
function html_query_criterion_list($query, $name, $additional_fields=NULL, $also_render_label=FALSE, $other_options=NULL )
    {
    $criterion = $query->criteria_container->get_by_qs_key($name);
    if (is_null($criterion))
       return;
    $render_default = $criterion->render_default;
    // NOTE AV - I've turned this back to returning a default value, since page_size needs its
    $current_value = $criterion->get_value($name); //$criterion->get_value($name, FALSE);
    $result = '';
    $qc_list = $criterion->get_list($name);
    // a returned string is likely to be referencing a db stored list - which we must fetch. a returned array means the list was defined in the datasource config
    if( is_string($qc_list) )
        $qc_list = $query->get_list($criterion->get_list($name));
    $use_integer_list_keys = $criterion->use_integer_list_keys;
    $help = $criterion->get_help_text();
    $render_help = TRUE;
    
    if( $also_render_label )
        {
        $result .= '<label for="' . $name . '">' . $criterion->label . ':</label>';
        }
    
    $result .= '<select name="' . $name . '" id="' . $name . '" ';
    
    if( $other_options )
        foreach( $other_options as $option_name=>$option_value )
            {
            switch( $option_name )
                {
                case 'render_default':
                    $render_default = $option_value;
                    break;
                case 'current_value':
                    $current_value = $option_value;
                    break;
                case 'list':
                    $qc_list = $option_value;
                    break;
                case 'use_integer_list_keys':
                    $use_integer_list_keys = $option_value;
                    break;
                case 'render_help':
                    $render_help = $option_value;
                    break;
                }
            }
    if( $additional_fields )
        foreach( $additional_fields as $field_name=>$field_value )
            {
            $result .= $field_name;
            $result .= '="' . $field_value . '" ';
            }
    $result .= '>';
    $result .= html_options($qc_list, $current_value, $render_default, $use_integer_list_keys );
    $result .= '</select>';
    
    if( $render_help )
        $result .= html_query_criterion_help($query, $name);
    return $result;
    }

// format
function html_query_criterion_date_field( $query, $name, $index )
    {
    $criterion = $query->criteria_container->get_by_qs_key($name);
    $value_o = $criterion->get_value( $name );
    $name = $name . '[' . $index . ']';
    $value = $criterion->get_value( $name );
    $range = $criterion->get_range();
    $range[0] = split('-',$range[0]);
    $range[1] = split('-',$range[1]);
    
    switch( $index )
        {
        case QC_DATE_YEAR:
            $options = get_year_options( $value, $range[0][0], $range[1][0] );
            break;
        case QC_DATE_MONTH:
            $options = get_month_options( $value );
            break;
        case QC_DATE_DAY:
            $options = get_day_options( $value );
            break;
        case QC_DATE_HOUR:
            $options = Array();
            for ($i = 0; $i <= 23; $i++)
                $options[sprintf('%02d', $i)] = sprintf('%02d', $i);
            $options = html_options($options, $value, '', TRUE);
            break;
        case QC_DATE_MINUTE:
            $options = Array();
            for ($i = 0; $i <= 59; $i++)
                $options[sprintf('%02d', $i)] = sprintf('%02d', $i);
            $options = html_options($options, $value, '', TRUE);
            break;
        }
    
    $result = '<select name="' . $name . '">';
    $result .= $options;
    $result .= "</select>\n";
    
    return $result;
    }
    
/// Format one or more checkbox inputs -- New function -- Gabriel
/// I have modified the above function, it's similar but the markup it outputs is different,
/// We want it to provide IDs to each element so we can connect the labels with the IDs, for instance:
/// <input type="checkbox" name="somename" value="1" id="id-somename">
/// <label for="id-somename">Some name</label>   
///  I've added an new argument called list, defaul is set to true, if 'FALSE' provided the function would render inline checkboxes.
/// ### TODO we should pass these arguments as an array and extract the values in the function, it has too many variables at the moment
function html_query_criterion_checkbox($query, $name, $additional_fields=NULL, $also_render_legend=FALSE, $list=false)
    {
	global $CONF;
	$label_css_class = 'implicitly';
    $criterion = $query->criteria_container->get_by_qs_key($name);
    $result = '';
    // display legend tag, not a label
    if( $also_render_legend )
        $result .= '<legend>' . $criterion->label . ':</legend>';
    
    $qc_list = $criterion->get_list($name);
    // a returned string is likely to be referencing a db stored list - which we must fetch. a returned array means the list was defined in the datasource config
    if( is_string($qc_list) )
        $qc_list = $query->get_list($criterion->get_list($name));
    if( is_array($qc_list) )
        {
        $flags = $qc_list;
		if ($list) 
			$result .= '<ol class="checkboxes clearfix">';
        foreach( $flags as $index => $flag )
            {
            $name = $criterion->get_qs_key() . '['. $index . ']';
			$label_id = 'id-'. $criterion->get_qs_key() . '-'. $index ;
            if ($list)
				$result .= '<li class="clearfix">';
			$result .= '<label for="'.$label_id.'" class="'.$label_css_class.' label-'.$label_id.'"><input type="checkbox" name="' . $name . '" value="1" id="'. $label_id .'"';
            if( $criterion->get_value($index) )
                $result .= 'checked="checked" ';
            $result .= '/><span>' . $flag . '</span></label>';
			if ($list)
				$result .= '</li>';
            }
		if ($list)
			$result .= '</ol>';
        }
    else
        {
        $name = $criterion->get_qs_key();
        $label = $criterion->label;
		if($list)
			$result .= "<div class=\"clearfix\">\n";
        $result .= '<label for="id-'.$name.'" class="'.$label_css_class.' label-'.$name.'"><input type="checkbox" name="' . $name . '" value="1" id="id-' . $name . '"';
        if( $criterion->get_value() )
            $result .= 'checked="checked" ';
        $result .= '/><span>' . $label . '</span></label>';
		if ($list)
			$result .= "</div>\n";
        }
    return $result;
    }
    
/// Format markup for an option control
function html_query_criterion_options($query, $name, $additional_fields=NULL, $also_render_legend=FALSE, $list=FALSE)
    {
	$label_css_class = 'implicitly';
    
	$criterion = $query->criteria_container->get_by_qs_key( $name );
    $result = '';
    
    if( $also_render_legend )
        $result .= '<legend>' . $criterion->label . "</legend>\n";
    
    if( !is_null($criterion->get_list()) )
        {
        $options = $query->get_list($name);
		if ($list)
        	$result .= '<ol class="options">' . "\n";
        foreach( $options as $option_value => $option_label )
            {
            $name = $criterion->get_qs_key();// . '['. $index . ']';
			if($list)
				$result .= '<li class="clearfix">';
				
            $result .= '<label class="'.$label_css_class.'"><input type="radio" name="' . $name . '" value="' . $option_value . '" ';
            if( $criterion->get_value() == $option_value )
                $result .= 'checked="checked" ';
            $result .= '/>&nbsp;' . $option_label . '</label>';
			if($list)
				$result .= "</li>\n";
            }
		if($list)
        	$result .= '</ol>';
        }
    return $result;
    }
    
// format markup for a single option control
function html_query_criterion_option( $query, $name, $index )
    {
    $criterion = $query->criteria_container->get_by_qs_key( $name );
    $result = '';
    $list = $query->get_list( $name );
    
    $option_label = $criterion->label;
    $option_value = $list[$index];
    
    $result .= '<input type="radio" name="' . $name . '" value="' . $option_value . '" ';
    if( $criterion->get_value() == $option_value )
        $result .= 'checked="checked" ';
    $result .= '/>&nbsp;' . $option_label . "\n";
    
    return $result;
    }

/* ---- global FORMS elements -------*/
function html_input_submit_basic(){
	
}
