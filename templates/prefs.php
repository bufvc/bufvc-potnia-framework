<?php
// $Id$
// User Preferences template
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

$module_template = $MODULE->find_template('prefs');
?>
<? if ($USER->has_right('save_data')): ?>
<p>Use the form below to set your preferences for using this site and be sure to click 'Save' when you are finished. These preferences will be remembered each time you visit.</p>
<div class="forms-holder">
<form method="POST">
<fieldset class="userset">
<legend>Your details</legend>
<label for="email">Email</label><input type="text" id="email" name="email" value="<?=$USER->email?>" size="50" />
<label for="name">Name</label><input type="text" id="name" name="name" value="<?=$USER->name?>" size="50" />
</fieldset>
<? if (@$CONF['user_timeout'] > 0): ?>
<fieldset class="loginset">
<legend>Login preferences</legend>
<label for="timeout">Login expires after</label><select id="timeout" name="timeout"><?= html_options($CONF['user_timeout_options'], isset($USER->prefs['timeout']) ? $USER->prefs['timeout'] : $CONF['user_timeout'], NULL, TRUE)?></select>
<p class="muted">If you are not active on the site within the time you specify here then your account will automatically be logged out for security</p>
</fieldset>
<? endif ?>
<fieldset class="searchprefs">
<legend>Search preferences</legend>
<label for="search_mode">Search mode</label><select id="search_mode" name="search_mode"><?= html_options(Array('default'=>'Basic', 'advanced'=>'Advanced'), @$USER->prefs['search_mode'])?></select>
<p class="muted">Your choice will affect the level of detail on the search form, choosing Basic will give you fewer fields to specify for a quicker search and Advanced will allow you to be more specific with your search queries</p>
<label for="page_size">Results per page</label><select id="page_size" name="page_size"><?= html_options(Array('10'=>'10', '50'=>'50', '100'=>'100'), @$USER->prefs['page_size'])?></select>
<p class="muted">Your choice here will determine how many results are shown per page on large result sets</p>
</fieldset>
<? if ($module_template != '') /* Load module-specific template */
    require_once $module_template;
?>
<fieldset class="submitset"><input type="submit" name="submit" id="submit" class="save-button" value="Save" /></fieldset>
</form>
</div>
<? endif ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
