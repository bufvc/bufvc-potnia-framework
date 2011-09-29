<?php
// $Id$
// iCalendar Formatter for BUFVC Directories project
// Phil Hansen, 28 Jan 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once('ExportFormatter.class.php');
require_once($CONF['path_lib'] . 'iCalcreator/iCalcreator.class.php');

/** The iCalendar formatter class provides functions for parsing a
    record and formatting it as an ical data file.

    The ical element mappings are specified in the table field definitions in the DataSource.
*/
class ICalendarFormatter
    extends ExportFormatter
    {
    /// Element label for this formatter
    var $label = 'ical_element';
    
    /// Specify a file name extension
    var $file_ext = '.ical';
    
    /// The calendar object
    var $calendar;
    
    function ICalendarFormatter($module, $util=NULL)
        {
        parent::__construct($module, $util);
        // set some calendar level properties
        $this->calendar = new vcalendar(Array('unique_id' => 'bufvc.ac.uk'));
        $this->calendar->setProperty('method', 'PUBLISH');
        $this->calendar->setProperty('X-WR-CALNAME', 'BUFVC iCal export');
        $this->calendar->setProperty('X-WR-CALDESC', 'BUFVC exported iCal data');
        $this->calendar->setProperty('X-WR-TIMEZONE', 'Europe/London');
        }
    
    /// Formats a record for export
    /// This funciton does not follow the algorithm of the base class
    /// It uses the ICalcreator lib to create and setup the calendar
    function format($record)
        {
        $record = $this->_util->format_fields($record);
        
        if (empty($record))
            return '';
        
        // get table meta data
        $table = $this->module->retrieve($record['_table']);
        $map = $this->get_label_map($table, $this->label);
        
        $dates = Array();
        $other = Array();
        
        foreach ($record as $name=>$value)
            {
            // check for additional labels
            if (isset($table[$this->label.'_extras'][$name]))
                {
                $map[$name] = $table[$this->label.'_extras'][$name];
                unset($table[$this->label.'_extras'][$name]);
                }
            
            if (!isset($map[$name]))
                continue;
            if (empty($value))
                continue;
            
            // process date
            if ($map[$name] == 'dtstart')
                {
                $date_data = Array();
                $date_data['dtstart'] = $value;
                
                // look for config array
                if (isset($table['fields'][$name][$this->label.'_config']))
                    {
                    foreach ($table['fields'][$name][$this->label.'_config'] as $extra_name=>$extra_value)
                        {
                        // check if this points to another record field
                        if (isset($record[$extra_value]))
                            {
                            if (!empty($record[$extra_value]))
                                $date_data[$extra_name] = $record[$extra_value];
                            }
                        else
                            $date_data[$extra_name] = $extra_value;
                        }
                    }
                $dates[] = $date_data;
                }
            // process regular element
            else
                {
                if (is_array($value))
                    {
                    // elements with multiple values are repeated
                    $tmp = Array();
                    foreach ($value as $item)
                        {
                        // special case, handle double indexed arrays
                        if (is_array($item))
                            {
                            if (isset($item['name']))
                                $item = $item['name'];
                            else if (isset($item['title']))
                                $item = $item['title'];
                            }
                        if (empty($item))
                            continue;
                        $tmp[] = $item;
                        }
                    if (!empty($tmp))
                        $other[$map[$name]] = $tmp;
                    }
                else
                    {
                    $other[$map[$name]] = $value;
                    }
                }
            }
        
        // add any additional static elements from table
        if (isset($table[$this->label.'_static']))
            {
            foreach ($table[$this->label.'_static'] as $name=>$value)
                $other[$name] = $value;
            }
        
        $other['url'] = $this->module->url('index', $record['url']);
        
        // create the events, number of dates determines number of events
        foreach ($dates as $date)
            {
            $event = &$this->calendar->newComponent('vevent');
            $data = $other;
            // date values override regular values
            foreach ($date as $name=>$value)
                $data[$name] = $value;
            
            foreach ($data as $name=>$value)
                {
                if ($name == 'dtstart' || $name == 'dtend')
                    $this->add_date($event, $name, $value);
                else if (is_array($value))
                    {
                    foreach ($value as $item)
                        $event->setProperty($name, $item);
                    }
                else
                    $event->setProperty($name, $value);
                }
            }
        return $this->calendar->createCalendar();
        }
    
    /// Parse given date and add it to the event
    /// Datetimes and dates are handled differently
    /// Expects a date in MySQL format:
    ///   either 2008-10-14
    ///   or     2008-10-14 00:00:00
    function add_date(&$event, $name, $value)
        {
        // date
        if (strpos($value, ':') === FALSE)
            {
            list($year, $month, $day) = explode('-', $value);
            $event->setProperty($name, Array('year'=>$year, 'month'=>$month, 'day'=>$day),
                                Array('VALUE'=>'DATE'));
            }
        // datetime
        else
            {
            list($date, $time) = explode(' ', $value);
            list($year, $month, $day) = explode('-', $date);
            list($hour, $min, $sec) = explode(':', $time);
            $event->setProperty($name, $year, $month, $day, $hour, $min, $sec);
            }
        }
    }
?>
