<?php
// $Id$
// Saved Searches
// Phil Hansen, 1 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
?>
<? if (count($QUERYLIST) > 0): ?>
<form method="POST">
<? if (count($auto_alerts_enabled) > 0): ?>
<? if ($CONF['debug']): ?>
<p><b>Please note:</b> This is a test server. No auto-alert emails will be sent.</p>
<? endif ?>
<? if (empty($USER->email)): ?>
To use Auto-Alerts please <a href="<?=$MODULE->url('prefs')?>">set up your email address</a>
<? else: ?>
Select the day of the week on which you wish to receive Auto Alerts:
<?
print "<select name=\"day\" ";
// add AJAX call
if ($CONF['submit_forms_with_script'])
    print "onchange=\"update_search_day(this.options[this.selectedIndex].value,'" . $MODULE->url('saved') . "')\" ";
print ">\n";?>
<?=html_options(get_days_of_the_week_values(), @$USER->prefs['saved_search_day'], 'Never', TRUE)?></select>
<br />Results will be mailed to <?=$USER->email?>.
<? endif // email check ?>
<a href="<?=$MODULE->url('help', '/saved')?>"  onClick="return popup_help(this)"><?=$STRINGS['save_search_help']?>&nbsp;&raquo;</a>
<? endif // auto alerts ?>
<div class="querylist">
<?php
foreach ($QUERYLIST as $key=>$query)
    {
    print "<dl><dt>".$query['text']."</dt>";
    if (@$CONF['multi_module'])
        print "<dd><b>Resource:</b> ". $query['title'] ."</dd>";
    // only show the 'active' checkbox if this module is auto-alert enabled
    if (isset($auto_alerts_enabled[$query['module']]))
        {
        print "<dd><b>Active:</b> <input type=\"checkbox\" name=\"$key\" value=\"1\" ";
        if (isset($ACTIVELIST[$key]) && $ACTIVELIST[$key])
            print "checked=\"checked\" ";
        // add AJAX call
        if ($CONF['submit_forms_with_script'])
            print "onclick=\"update_active_saved_search(this,'$key','" . $MODULE->url('saved') . "')\" ";
        print "/></dd>\n";
        }
    print "<dd><a href=\"" . $query['url'] . "\">Results</a></dd>\n";
    print "<dd><input type=\"submit\" name=\"delete\" value=\"Delete\" onclick=\"javascript:this.form['key'].value='$key';return confirm('Are you sure you want to delete this auto alert?')\" /></dd></dl>\n";
    }
?></div>
<input type="hidden" name="key" value="" />
<? if (!@$CONF['submit_forms_with_script']): ?>
<input type="submit" name="update" value="Update" />
<? endif ?>
</form>
<? else: ?>
<p><?=$STRINGS['no_saved_searches']?></p>
<? endif ?>
<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
