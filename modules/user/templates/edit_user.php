<?php
// $Id$
// Edit screen for user module
// Phil Hansen, 05 May 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>

<dl class="edit-form">
	<fieldset class="editset">
<? if ($IS_NEW) : ?>
<dt>Slug</dt><dd><input type="text" name="slug" value="<?=$MODULE->get_htmlentities(@$_POST['slug'])?>" /></dd>
<? else : ?>
<p><a href="<?=$MODULE->url('index', $RECORD['url'])?>">View this user</a></p>
<? endif ?>
<dt>Login</dt><dd><input type="text" name="login" value="<?=$MODULE->get_htmlentities(isset($_POST['login']) ? $_POST['login'] : @$RECORD['login'])?>" size="50" /></dd>
<dt>Name</dt><dd><input type="text" name="name" value="<?=$MODULE->get_htmlentities(isset($_POST['name']) ? $_POST['name'] : @$RECORD['name'])?>" /></dd>
<dt>Email</dt><dd><input type="text" name="email" value="<?=$MODULE->get_htmlentities(isset($_POST['email']) ? $_POST['email'] : @$RECORD['email'])?>" /></dd>
<dt>Telephone Number</dt><dd><input type="text" name="telephone_number" value="<?=$MODULE->get_htmlentities(isset($_POST['telephone_number']) ? $_POST['telephone_number'] : @$RECORD['telephone_number'])?>" /></dd>
<dt>Institution</dt><dd><select name="institution_id"><?= html_options($QUERY->get_list('institution'), isset($_POST['institution_id']) ? $_POST['institution_id'] : @$RECORD['institution_id'], 'None', TRUE)?></select></dd>
<dt>Rights</dt><dd id="dd_rights">
<?  if (isset($_POST['rights']))
        {
        $items = $_POST['rights'];
        $post_data = true;
        }
    else
        {
        $items = @$RECORD['rights'];
        $post_data = false;
        }

    $count = (isset($items)) ? count($items) : 0;
    if (isset($items) && count($items) > 0)
        {
        for ($i=0; $i < count($items); $i++)
            {?>
            <select name="rights[]" onchange="check_boxes('rights', 'dd_rights')"><?= html_options($QUERY->get_list('rights'), $items[$i], 'Blank')?></select>
          <?}
        }
    if (!$post_data && count($items) < count($QUERY->get_list('rights')))
        {?>
        <select name="rights[]" onchange="check_boxes('rights', 'dd_rights')"><?= html_options($QUERY->get_list('rights'), isset($items[$count]) ? $items[$count] : '', 'Blank')?></select>
      <?}?>
</dd>
<dt>Root</dt><dd><select name="root"><?=html_options(Array(0=>'No', 1=>'Yes'), isset($_POST['root']) ? $_POST['root'] : @$RECORD['root'], NULL, TRUE)?></select></dd>
</fieldset>
<fieldset class="submitset">
<? if ($IS_NEW) : ?>
<dt></dt><dd><input type="submit" id="submit" value="Create" /></dd>
<? else : ?>
<dt></dt><dd><input type="submit" id="submit" value="Save" />
<input type="submit" name="delete" value="Delete" onClick="return confirm('Do you want to delete this record?');" />
<input type="submit" name="clear" value="Clear Saved Data" onClick="return confirm('This will remove all data fields (saved searches, preferences, etc.).  Are you sure?');" /></dd>
<? endif ?>
</fieldset>
</dl>

<script type="text/javascript">
var rights = new Array();
var rkeys = new Array();
rights[0]='';
rkeys[0]='';
<?
$items = $QUERY->get_list('rights');
$index = 1;
foreach ($items as $key=>$value)
    {
    print "rkeys[$index]='". addslashes($key) . "';\n";
    print "rights[$index]='". addslashes($value) . "';\n";
    $index++;
    }
?>

function check_boxes(name, id_name)
    {
    var boxes = document.getElementsByName(name+'[]');
    var full = true; // flag

    for (i=0; i<boxes.length; i++)
        {
        if (boxes[i].options[boxes[i].selectedIndex].value == '')
            full = false;
        }
    if (boxes.length > 0 && full == true)
        add_box(name, id_name);
    }
function add_box(name, id_name)
    {
    var area = document.getElementById(id_name);
    var new_select = document.createElement("select");
    new_select.name = name+'[]';

    // get correct option array
    if (name == 'rights')
        {
        items = rights;
        keys = rkeys;
        }

    // add options to select
    for(i=0;i<items.length;i++)
        {
        op=document.createElement('OPTION');
        op.setAttribute('value',keys[i]);
        txt=document.createTextNode(items[i]);
        op.appendChild(txt);
        new_select.appendChild(op);
        }

    new_select.setAttribute('onchange', "check_boxes('"+name+"', '"+id_name+"')");
    // add select to form
    area.appendChild(new_select);
    }
</script>