<?php
// $Id$
// Navigation menu for database applications
// James Fryer, 2 July 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$default_module = Module::load($CONF['allowed_modules'][0]);

$slidey_history_size = 10;

// Get the last query regardless of module -- this is our navigation query
//### FIXME: needs thought. doesn't belong in the templates.
$nav_query = QueryFactory::get_session_query($MODULE, TRUE);
$is_default_module = $MODULE->name == $default_module->name;
$app_menu = $MODULE->menu($USER);
// Show an indented search menu if there are multiple tables
$search_menu = $MODULE->menu_search($QUERY);
if (count($search_menu) > 1)
    $app_menu['search'] = $search_menu;

// Output menu items, optionally filtering by $type, optionally skipping some items
function format_app_menu($menu, $type=NULL, $skip_items=Array())
    {
    $result = Array();
    $next_style = '';
    foreach ($menu as $name=>$item)
        {
        if (in_array($name, $skip_items))
            continue;
        // Recursively handle sub arrays
        else if (is_array($item) && @$item['url'] == '' && @$item['type'] == '')
            $result[] = '<li><ul>' . format_app_menu($item, $type) . '</ul></li>';
        else if (@$item['type'] == 'line')
            $next_style = ' class="separator"';
        else if (is_null($type) || $type == @$item['type'])
            {
            $result[] = '<li' . (@$item['current'] ? ' class="current"' : '') . $next_style . 
                    '><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
            $next_style = '';
            }
        }
    return join("\n", $result);
    }
?>

<? if ($CONF['module_mode'] == 'simple'): /* Old behaviour */ ?>
<ul class="app-menu home">
<?=format_app_menu($app_menu)?>
</ul>
<? else: ?>
<!-- Main module menu -->
<ul class="app-menu home">
<?
    // Print top-level menu item
    print '<li><p class="menu-title"><a href="' . $default_module->url('index') . '">' . $default_module->title . '</a></p>';
    // Add 'new search' item
    // If we're in the top-level module, print its info
    if ($is_default_module)
        print format_app_menu($app_menu, '', Array('home', 'search', 'about'));
    // Otherwise indent and print the sub-module's info
    else {
        print '<ul>';
        print format_app_menu($app_menu, '', Array('about'));
        print '</ul>';
        }
?> </li>
</ul>

<!-- Query summary -->

<ul class="app-menu search" id="search-menu">
<li>
<? if (!is_null($nav_query) && (count($nav_query->results) > 0 || $nav_query->has_criteria()) && @!$NEW_QUERY): ?>
<p class="menu-title current-search"><a href="<?=$nav_query->url(Array('editquery'=>1))?>">Current Search</a></p>
<?
$str = $nav_query->criteria_string(QUERY_STRING_TYPE_HTML,'edit');

// adding total result to definition list, should be in there
$str .= '<dt class="results-message-unpaged"><a href="' . $nav_query->url() . '">' . $nav_query->info['results_message_unpaged'] . '</a></dt>';
// wrap string in definition list
print '<ul id="current-search-menu"><li><dl class="query">' . $str. '</dl></li></ul>';
//checking if a current query exists whilst a new query is being created
elseif(!is_null($nav_query) && (count($nav_query->results) > 0 || $nav_query->has_criteria()) && $NEW_QUERY): ?>
<p class="menu-title current-search"><a href="<?=$nav_query->url(Array('editquery'=>1))?>">Current Search</a></p>
<ul id="current-search-menu"><li><dl class="query"><p>Awaiting new search criteria</p></dl></li></ul>
<?
//as the current query is not in the history create a holder to add it to the slider
//would prefer to actually add to history when mode=new 
$str = $nav_query->criteria_string(QUERY_STRING_TYPE_HTML);
$str .= '<dt><a href="' . $nav_query->url() . '">' . $nav_query->info['results_message_unpaged'] . '</a></dt>';
$current_search = '<li><dl>' . $str. '</dl></li>';
?>
<? else: ?>
<p class="menu-title current-search">Current Search</p>
<ul id="current-search-menu"><li><dl class="query"><p>No current search.</p></dl></li></ul>
<? endif ?>
</li>
<?=format_app_menu(Array('search' => Array('title'=>'New Search', 'url'=>$nav_query->url_new())))?>
<? 
// past searches
if (count($USER->search_history) > 0){ ?>
<li>
<p class="menu-title previous-search">Previous Searches</p>
	<ul id="previous-search-menu">
<?
if(@$NEW_QUERY && @$current_search)
	print $current_search;
// skip first search?
$queries = $USER->search_history->outline();
for ($i = 1; $i < min($slidey_history_size, count($queries)); $i++)
    {
    $query = $queries[$i]['head'];
    print '<li><dl>'.$query['text'];
    if (@$CONF['multi_module'])
        print '<dt>Resource:</dt><dd>' . $query['title'] .'</dd>';
    print '<dd><a href="' . $query['url'] . '">'. $query['info']['results_message_unpaged'] . "</a></dd></dl></li>";
    }
?>

	<li class="navmenu-link"><a href="<?=($app_menu['history']['url'])?>">View all previous searches</a></li>
</ul>
</li>
<? 
	}

$list_needs_closing = true; //wait to see if auto alert form needs to go into this <ul>

?>

<? endif /* Module mode */ ?>
    <!-- Save this search -->
    <? if (@$TEMPLATE == 'search'):
    //### FIXME: this logic should be in a function...
        if ($MODULE->saved_search_enabled && $CONF['saved_searches_size'] > 0 && $QUERY->has_allowed_criteria($_REQUEST) && $USER->has_right('save_data')):?>
            <?if(!@$list_needs_closing ): //need to make ul self contained if no slidey history?>
            <ul class="app-menu">
            <?endif;?>
            <li class="save-search">
            <form action="<?=$MODULE->url('saved')?>" method="POST">
            <?
            print $QUERY->criteria_string( QUERY_STRING_TYPE_HTML_FORM );
            ?>
            <input class="ie-fix" type="submit" name="save" value="<?=$STRINGS['save_search_button']?>" />
            <br /><a href="<?=$MODULE->url('help', '/saved')?>"  onClick="return popup_help(this)"><?=$STRINGS['save_search_help']?>&nbsp;&raquo;</a>
            </form>
            </li>
           <?if(!@$list_needs_closing ):?>
            </ul>
            <?endif;?>
        <?endif;?>
    <? endif /* Search */?>

<?if(@$list_needs_closing ):?>
</ul>
<?endif;?>

<!-- my BUFVC -->
<?if(@$USER->is_registered()):?>
    <ul class="app-menu my-menu">
        <li>
        <p class="menu-title">My BUFVC</p>
        </li>
        <li>
        <p><a href="<?=$MODULE->url('prefs')?>">Preferences</a></p>
        </li>
        <li>
        <p><a href="<?=$MODULE->url('saved')?>">Auto Alerts</a></p>
        </li>
    </ul>
<?endif;?>

<? if (!defined('UNIT_TEST')): /*### temp while we test this new feature ###*/?>
<? /*### ----- Experimental on-screen viewed and marked records ------ ###*/ ?>
<? if (count(@$_SESSION['HISTORY_RECORDS']) || $MARKED_RECORDS->count() > 0): ?>
<ul class="app-menu records" id="menu-records">
<? if (count(@$_SESSION['HISTORY_RECORDS'])): ?>
<li>
<p class="menu-title viewed-records">Viewed Records</p>
<ul>
<?php // ##FIXME - Last argument should be part of CONF var ?>
<?=format_record_list_titles($_SESSION['HISTORY_RECORDS'], 8)?>
<?php //----- james, I added this hack /viewed here to test the tabs----- ?>
<li class="navmenu-link"><a href="<?=($app_menu['history']['url'].'/viewed')?>">All Viewed Records</a></li>
</ul>
</li>
<? endif /* viewed Records */?>

<?php // display marked records 
if( ($MARKED_RECORDS->count()) > 0 ): ?>
	<li>
		<p class="menu-title marked-records">Marked Records</p> 
		<ul id="marked-menulist">
			<?php // ##FIXME - Last argument should be part of CONF var ?>
			<?=format_record_list_titles($MARKED_RECORDS->get_all(), 8);?>
			<li class="navmenu-link"><p><a href="<?=$app_menu['marked']['url']?>">All <?=$app_menu['marked']['title']?></a></p></li>
		</ul>
	</li>
<?php endif /* marked records */ ?>

</ul>
<? endif /* History */?>
<? endif /*### temp unit test check ###*/?>

