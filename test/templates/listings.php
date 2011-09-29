<?php
// $Id$
// Listings page
// Phil Hansen, 08 Jul 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

$channel_list = $QUERY->get_list('channel');
define( 'ONE_DAY', 86400 );
?>

<div class="search-form clearfix" id="search-form" >
    <form method="GET" name="listings_form" action="<?=$MODULE->url('listings')?>">
        <div class="form_leftcol">
            <fieldset class="controlset">
                <ol>
<?
// time options for list and grid view
$time_options = Array();
for ($i = 0; $i <= 23; $i++)
    $time_options[$i] = sprintf('%02d', $i) . ':00';

$date_start = $QUERY['date']->get_timestamp();
$date_string = date('Y-m-d', $date_start );
$time = $QUERY['time']->get_value();
// get next/prev day urls
$date_prev = date('Y-m-d', $date_start - ONE_DAY);
$date_next = date('Y-m-d', $date_start + ONE_DAY);
$query_style = $QUERY['style']->get_value();
$query_style = ($query_style == 'grid') ? '&style=grid' : '';
$date_prev = $MODULE->url('listings', '?'.$QUERY->url_query(Array('date'=>$date_prev)).$query_style);
$date_next = $MODULE->url('listings', '?'.$QUERY->url_query(Array('date'=>$date_next)).$query_style);

// get next/prev time urls
if ($QUERY['style']->get_value() == 'grid')
    {
    if ($time == 0)
        $time_prev = $date_prev . '&time=21';
    else
        $time_prev = $MODULE->url('listings', '?'.$QUERY->url_query().'&style=grid&time='.($time-1));
    if ($time == 23)
        $time_next = $date_next . '&time=0';
    else
        $time_next = $MODULE->url('listings', '?'.$QUERY->url_query().'&style=grid&time='.($time+1));
    }
?>
                    <li class="date to_the_left">
                        <label for="date">Date:</label>
                        <?= html_query_criterion_date_field($QUERY, 'date', QC_DATE_MONTH )?>
                        <?= html_query_criterion_date_field($QUERY, 'date', QC_DATE_DAY )?>
                        <?= html_query_criterion_date_field($QUERY, 'date', QC_DATE_YEAR )?>
                    </li>
                    <li class="date to_the_left">
                        <label for="time">Time:</label>
                        <select name="time"><?=html_options($time_options, $time, '', TRUE)?></select>
                    </li>
                    <input type="hidden" name="style" value="<?=$QUERY['style']->get_value()?>">
                </ol>
            </fieldset>
        </div>
        <div class="form_rightcol last">
            <fieldset class="submitset">
                <input type="submit" id="submit" value="View" />
<? if ($QUERY['style']->get_value() == 'list'): ?>
                <input type="submit" name="view_grid" value="Switch to Grid View" />
<? else: ?>
                <input type="submit" name="view_list" value="Switch to List View" />
<? endif ?>
            </fieldset>
        </div>
    </form>
</div>

<? if (count($results) > 0): ?>
<ul class="paging">
<li><? if (@$date_prev): ?><a href="<?=$date_prev?>">&lt; Go to previous day</a><? endif ?></li>
<li><? if (@$date_next): ?><a href="<?=$date_next?>">Go to next day &gt;</a><? endif ?></li>
</ul>

<table class="listings">
<?
//
// Grid view style
//
if ($QUERY['style']->get_value() == 'grid'): ?>
    <thead>
        <tr>
            <th><?
// prev/next time links
$result = '';
if (@$time_prev)
    $result .= '<li><a href="'.$time_prev.'">&lt;&lt;&lt;</a></li>';
if (@$time_next)
    $result .= '<li><a href="'.$time_next.'">&gt;&gt;&gt;</a></li>';
if ($result != '')
    print "<ul>$result</ul>";
else
    print "&nbsp;";
?>
            </th>
<?

// print hour headers
$hour = $time;
for ($i = 0; $i < 3; $i++)
    {
    print "        <th>".sprintf('%02d', $hour).":00</th>\n";
    $hour++;
    if ($hour == 24)
        $hour = 0;
    }
?>
        </tr>
    </thead>
    <tbody>
    <?= $listings->format_grid_view($results, $channel_list, $time, $date_string); ?>
    </tbody>
<?
//
// List view style
//
else: ?>
    <thead>
        <tr>
<?
// print channel name headers
foreach ($results as $channel=>$data)
    {
    print "        <th>".@$channel_list[$channel]."</th>\n";
    }
?>
        </tr>
    </thead>
    <tbody>
    <?= $listings->format_list_view($results, $time, $date_string); ?>
    </tbody>
<? endif ?>
</table>
<? endif ?>

<div style="clear:both"></div>
<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>