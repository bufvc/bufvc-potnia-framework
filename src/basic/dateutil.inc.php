<?php
// $Id$
// Date utils
// Phil Hansen, 2009
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Gets a select of day values
function get_day_options($selected='')
    {
    $result = '';
    for ($i = 1; $i <= 31; $i++)
        {
        $result .= '<option value="' . sprintf('%02d', $i) . '"';
        if ($i == $selected)
            $result .= ' selected="selected"';
        $result .= '>' . sprintf('%02d', $i) . "</option>\n";
        }
    return $result;
    }

/// Returns an array containing the days of the weeks
/// This follows ISO-8601 numeric representation of the day of the week
/// i.e. 1 (for Monday) through 7 (for Sunday)
function get_days_of_the_week_values()
    {
    $days = Array(
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
        );
    return $days;
    }

/// Returns the current day of the week in ISO-8601 numeric representation
/// i.e. 1 (for Monday) through 7 (for Sunday)
/// This can be done using date('N') starting in php 5.1.0 but is done
/// manually here for php 4
function get_current_day_of_the_week()
    {
    $day = date('w');
    if ($day == 0)
        $day = 7;
    return $day;
    }

/// Gets a select of month values using the full month names
function get_month_options($selected='')
    {
    global $STRINGS;
    $months = $STRINGS['months'];

    $result = '';
    if (empty($selected) && $selected != null)
        $selected = date('m', time());
    foreach ($months as $key => $value)
        {
        $result .= '<option value="' . sprintf('%02d', $key) . '"';
        if ($key == $selected)
            $result .= ' selected="selected"';
        $result .= '>' . $value . "</option>\n";
        }
    return $result;
    }

/// Gets a select of year values
/// Values can be displayed asc or desc depending on $asc
function get_year_options($selected='', $start_year=null, $end_year=null, $asc=true)
    {
    if (is_null($start_year) || is_null($end_year) || $end_year < $start_year)
        return;
    
    $result = '';
    if ($asc)
        {
        for ($i = $start_year; $i <= $end_year; $i++)
            {
            $result .= '<option value="' . $i . '"';
            if ($i == $selected)
                $result .= ' selected="selected"';
            $result .= '>' . $i . "</option>\n";
            }
        }
    else
        {
        for ($i = $end_year; $i >= $start_year; $i--)
            {
            $result .= '<option value="' . $i . '"';
            if ($i == $selected)
                $result .= ' selected="selected"';
            $result .= '>' . $i . "</option>\n";
            }
        }
    return $result;
    }

?>