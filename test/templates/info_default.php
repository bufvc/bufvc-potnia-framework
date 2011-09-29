<?php
// $Id$
// Default index page
// Phil Hansen, 10 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<p>Using template info_default</p>

<form id="search" method="GET" action="<?=$MODULE->url('search')?>">
    <div class="search-form clearfix">
        <div class="form_leftcol">
            <fieldset class="searchset">
                <?= html_query_criterion_input($QUERY, 'q', NULL, TRUE ); ?> in all fields
            </fieldset>
            <fieldset class="controlset">
                <ol>
                    <li class="years to_the_left solo">
                          <label for ="">Year:</label>
                          <?= html_query_date_list($QUERY, 'date_start')?>
                          <label class="label_between">to</label>
                          <?= html_query_date_list($QUERY, 'date_end')?>
                          <?= html_query_criterion_help($QUERY, 'date')?>
                    </li>
                    <li>
                        <?= html_query_criterion_list($QUERY, 'category', NULL, TRUE )?>
                    </li>
                </ol>
            </fieldset>
        </div>
        <div class="form_rightcol last">
            <fieldset class="submitset"><input type="submit" id="submit" value="Search" />
                <ol>
                    <li><a href="<?=$MODULE->url('search', '?mode=advanced')?>">Advanced search &nbsp;&raquo;</a></li>
                    <li><a href="<?=$MODULE->url('help', '/search')?>" onClick="return popup_help(this)">Help on searching&nbsp;&raquo;</a></li>
                </ol>
            </fieldset>
            <fieldset class="sort_by_set">
                <ul>
                    <li><?= html_query_criterion_list($QUERY, 'sort', NULL, TRUE )?></li>
                    <li><?= html_query_criterion_list($QUERY, 'page_size', NULL, TRUE )?> results per page</li>
                </ul>
            </fieldset>
        </div>
    </div>
    <input id='search_data' type='hidden' value='<?= $QUERY->criteria_string(QUERY_STRING_TYPE_JSON)?>'/>
</form>

<div style="clear:both"></div>
<p>Links to more information about the project will go here.</p>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
