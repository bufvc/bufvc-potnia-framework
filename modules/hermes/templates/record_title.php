<?php
// $Id$
// View generic record for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

?>
<dl class='row'>
<? if (@$RECORD['subtitle']): ?><dt>Subtitle</dt><dd><?=$RECORD['subtitle']?></dd><? endif ?>
<? if (@$RECORD['alt_title']): ?><dt>Alternative title</dt><dd><?=$RECORD['alt_title']?></dd><? endif ?>
<? if (@$RECORD['description']): ?><dt>Synopsis</dt><dd><?=$RECORD['description']?></dd><? endif ?>
<? if (@$RECORD['title_series']): ?><dt>Series</dt><dd><?=html_link($MODULE->url('search', sprintf('?adv_index1=series&adv_q1=%%22%s%%22', urlencode($RECORD['title_series']))), $RECORD['title_series'])?></dd><? endif ?>
<? if (@$RECORD['language']): ?><dt>Language</dt><dd><?=html_link($MODULE->url('search', '?language=' . $RECORD['language_id']), $RECORD['language'])?></dd><? endif ?>
<? if (@$RECORD['country']): ?><dt>Country</dt><dd><?=join('; ', make_search_links($RECORD['country'], $MODULE->url('search', '?country=%s')))?></dd><? endif ?>
<? if (@$RECORD['title_format']): ?><dt>Medium</dt><dd><?=join('; ', make_search_links($RECORD['title_format'], $MODULE->url('search', '?title_format=%s')))?><? if (@$RECORD['physical_description']): ?>; <?=$RECORD['physical_description']?><? endif ?></dd>
<?
$is_film = false;
foreach($RECORD['title_format'] as $format)
    {
    if ($format['title'] == 'Film')
        {
        $is_film = true;
        break;
        }
    }
?>
<? if ($is_film || $RECORD['is_colour'] || $RECORD['is_silent']): ?>
<dt>Technical information</dt><dd><?=($RECORD['is_colour'] ? 'Colour' : 'Black-and-white') . ' / ' .
        ($RECORD['is_silent'] ? 'Silent' : 'Sound')?></dd><? endif ?>
<? endif ?>
<? if (@$RECORD['date_released']): ?><dt>Year of release</dt><dd><?=$RECORD['date_released']?></dd><? endif ?>
<? if (@$RECORD['date_produced']): ?><dt>Year of production</dt><dd><?=$RECORD['date_produced']?></dd><? endif ?>
<? if (@$RECORD['availability']): ?><dt>Availability</dt><dd><?=$RECORD['availability']?><? if (@$RECORD['price']): ?>; <?=$RECORD['price']?><? endif ?></dd><? endif ?>
<? if (@$RECORD['online_url']): ?><dt>Online URL</dt><dd><a href="<?=$RECORD['online_url']?>"><?=$RECORD['online_url']?></a></dd><? endif ?>
<? if (@$RECORD['online_price']): ?><dt>Online price</dt><dd><?=$RECORD['online_price']?></dd><? endif ?>
<? if (@$RECORD['online_format']): ?><dt>Online format</dt><dd><?=$RECORD['online_format']?></dd><? endif ?>
<? if (@$RECORD['viewfinder'] > 0 && $USER->has_right('edit_record')): ?><dt>Viewfinder</dt><dd><?=$RECORD['viewfinder']?></dd><? endif ?>

<?php
// Additional details
// Gather notes
$notes_fields = Array(
    'notes'=>'Notes',
    'notes_documentation'=>'Documentation',
    'notes_uses'=>'Uses',
    );
$notes = Array();
foreach ($notes_fields as $name=>$label)
    {
    if ($RECORD[$name])
        $notes[] = "<dt>$label</dt><dd>{$RECORD[$name]}</dd>";
    }
if (count($notes))
    print(join("\n", $notes))
?>
<? if (@$RECORD['category']): ?><dt>Subjects</dt><dd><?=join('; ', make_search_links($RECORD['category'], $MODULE->url('search', '?category=%s')))?></dd><? endif ?>
<? if (@$RECORD['keyword']): ?><dt>Keywords</dt><dd><?=join('; ', make_search_links($RECORD['keyword'], $MODULE->url('search', '?adv_index1=keyword&adv_q1=%%22%s%%22'), 'title'))?></dd><? endif ?>
<? if (@$RECORD['related']): ?><dt>Related items</dt>
<?
foreach (@$RECORD['related'] as $r)
    {
    print "<dd><a href=\"" . $MODULE->url('index', $r['url']) . "\">{$r['title']}</a></dd>\n";
    }
?><? endif ?>
</dl>

<? if (@$RECORD['person']): ?>
<h3>Credits</h3>
<dl class="row">
<?php
// Credits -- technical
// Technical roles need to be sorted and collated
$tech_roles = Array(
    'Director'=>Array(),
    'Producer'=>Array(),
    'Cinematographer'=>Array(),
    'Writer'=>Array(),
    'Editor'=>Array(),
    'Screenplay'=>Array(),
    'Adapter for Radio'=>Array(),
    'Adapter for Television'=>Array(),
    'Music'=>Array(),
    'Music Director'=>Array(),
    'Production Design'=>Array(),
    'Costume'=>Array(),
    'Art Direction'=>Array(),
    'Animator'=>Array(),
    'Choreographer'=>Array(),
    'Contributor'=>Array(),
    'Composer'=>Array(),
    );

// Gather up technical and non-technical roles
$cast = Array();
foreach ($RECORD['person'] as $person)
    {
    if ($person['is_technical'])
        $tech_roles[$person['role']][] = $person['name'];
    // Gather up cast
    else
        $cast[] = $person;
    }

foreach ($tech_roles as $role=>$names)
    {
    if ($names)
        print "<dt>$role</dt><dd>" . join('; ', make_search_links($names, $MODULE->url('search', '?adv_index1=person&adv_q1=%%22%s%%22'))) . "</dd>\n";
    }

// Cast -- non-technical
if (count($cast))
    {
    // Yes it's a table. Sue me. Actually this is tabular data, so it's appropriate
    print "<dt>Cast</dt>\n";
    print "<dd><table width=\"70%\">\n";
    foreach ($cast as $person)
        {
        $person_link = html_link($MODULE->url('search', sprintf('?adv_index1=person&adv_q1=%%22%s%%22', urlencode($person['name']))), $person['name']);
        if ($person['role'] == 'Performer')
            $role_link = '&nbsp;';
        else
            $role_link = $person['role']; //### TODO
        $s = "<tr><td>$person_link</td><td>";
        if ($role_link)
            {
            $s .= $role_link;
            }
        $s .= "</td></tr>\n";
        print $s;
        }
    print "</table></dd>\n";
    }
?>
</dl>
<? endif ?>

<? if (@$RECORD['distribution_media'] && count(@$RECORD['distribution_media']) > 0): ?>
<h3>Distribution Formats</h3>
<dl class="row">
<? foreach ($RECORD['distribution_media'] as $media):?>
<? if (@$media['type']): ?><dt>Type</dt><dd><?=$media['type']?></dd><? endif ?>
<? if (@$media['format']): ?><dt>Format</dt><dd><?=$media['format']?></dd><? endif ?>
<? if (@$media['price']): ?><dt>Price</dt><dd><?=$media['price']?></dd><? endif ?>
<? if (@$media['availability']): ?><dt>Availability</dt><dd><?=$media['availability']?></dd><? endif ?>
<? if (@$media['length']): ?><dt>Duration/Size</dt><dd><?=$media['length']?></dd><? endif ?>
<? if (@$media['year']): ?><dt>Year</dt><dd><?=$media['year']?></dd><? endif ?>
<br />
<? endforeach ?>
</dl>
<? endif ?>

<? if (@$RECORD['section'] && count(@$RECORD['section']) > 0): ?>
<h3>Sections</h3>
<dl class="row">
<? foreach ($RECORD['section'] as $section):?>
<? if (@$section['title']): ?><dt>Title</dt><dd><?=$section['title']?></dd><? endif ?>
<? if (@$section['description']): ?><dt>Synopsis</dt><dd><?=$section['description']?></dd><? endif ?>
<? if (@$section['notes']): ?><dt>Notes</dt><dd><?=$section['notes']?></dd><? endif ?>
<? if (@$section['duration']): ?><dt>Duration</dt><dd><?=(int)$section['duration']/60 . ' mins'?></dd><? endif ?>
<? if (@$section['is_colour'] || @$section['is_silent']): ?><dt>Technical information</dt><dd>
    <?=trim(($RECORD['is_colour'] ? 'Colour,' : '') . ' ' .
        ($RECORD['is_silent'] ? 'Silent' : ''), ", ")?></dd><? endif ?>
<!--<? if (@$section['number_in_series']): ?><dt>Number in series</dt><dd><?=$section['number_in_series']?></dd><? endif ?>-->
<? if (@$section['distributors_ref']): ?><dt>Distributors ref</dt><dd><?=$section['distributors_ref']?></dd><? endif ?>
<? if (@$section['isbn']): ?><dt>ISBN</dt><dd><?=$section['isbn']?></dd><? endif ?>
<br />
<? endforeach ?>
</dl>
<? endif ?>

<? // Organisations
if (@$RECORD['org']):

// Sort/collate orgs
$org_relations = Array(
    'Production Company'=>Array(),
    'Sponsor'=>Array(),
    'Publisher'=>Array(),
    'Archive'=>Array(),
    'Distributor'=>Array(),
    'Distributor (DVD)'=>Array(),
    'Distributor (VHS)'=>Array(),
    'Distributor (Hire)'=>Array(),
    'Distributor (Sale)'=>Array(),
    'Online Retailer'=>Array(),
    'Related'=>Array(),
    );
foreach ($RECORD['org'] as $org)
    $org_relations[$org['relation']][] = $org;
// We now have our organisations grouped by relation name

foreach ($org_relations as $name=>$orgs): if ($orgs):
?>
<h3><?=$name?></h3>
<?
foreach ($orgs as $org):
//### FIXME: These fields should be merged in the database (possibly excepting postcode)
$address_fields = Array(
    'address_1',
    'address_2',
    'address_3',
    'address_4',
    'town',
    'county',
    'postcode',
    'country',
    );

// Build the address string
$address = Array();
foreach ($address_fields as $name)
    {
    if ($org[$name])
        $address[] = $org[$name];
    }
?>
<dl class="row">
<dt>Name</dt><dd><h4><?=html_link($MODULE->url('search', sprintf('?adv_index1=org&adv_q1=%%22%s%%22', urlencode($org['name']))), $org['name'])?></h4></dd>
<? if ($org['contact_name']): ?><dt>Contact</dt><dd><?= $org['contact_name'] .
    ($org['contact_position'] == '' ? '' : ' (' .  $org['contact_position'] . ')')?></dd><? endif ?>
<? if ($org['email']): ?><dt>Email</dt><dd><?= $org['email']?></dd><? endif ?>
<? if ($org['web_url']): ?><dt>Web</dt><dd><a href="<?= $org['web_url']?>" target="_blank"><?= $org['web_url']?></a> External site opens in new window</dd><? endif ?>
<? if ($org['telephone']): ?><dt>Phone</dt><dd><?= $org['telephone']?></dd><? endif ?>
<? if ($org['fax']): ?><dt>Fax</dt><dd><?= $org['fax']?></dd><? endif ?>
<? if ($address): ?><dt>Address</dt><dd><?= join('<br />', $address)?></dd><? endif ?>
<? if ($org['notes']): ?><dt>Notes</dt><dd><?= $org['notes']?></dd><? endif ?>
</dl>
<? endforeach; endif; endforeach; endif ?>
