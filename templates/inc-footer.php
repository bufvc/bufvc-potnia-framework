<?php
// $Id$
// Footer for database applications
// James Fryer, 30 Aug 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Include logo when on module default page
if (@$SIDEBAR == '')
    {
    $logo = new Block('logo');
    $logo->set_vars(compact('MODULE', 'CONF'));
    $SIDEBAR = Array($logo);
    }
?>
        </div>
		<div class="sidebar-wrapper column_5 last_column">
		<?if (@$SIDEBAR):?>
		<div class="sidebar" id="sidebar">
		<ul>
			<?php /*---- Blocks -----*/ ?>
			<? foreach ($SIDEBAR as $block): ?>
			<li><?= $block->render()?></li>
			<? endforeach ?>
		</ul>
		</div> <!-- sidebar -->
		<? endif /* Sidebar */ ?>
		</div> <!-- sidebar-wrapper -->
	</div> <!-- body-wrapper -->
</div> <!-- content -->
<hr class="site-location" />
	<div id="footer" class="footer-wrapper">
	<div class="footer">
	<p><?=$MODULE->title?> v<?=$MODULE->version . @$CONF['title_suffix']?> / Engine v<?=$CONF['version']?>.
	<?php if ($CONF['unit_test_active']) print(" <b>Unit tests are active. Current user is " . $USER->login . "</b>");?>
	By using this service, you agree to the <a href="<?=$MODULE->url('index', '/tandc')?>">terms and conditions</a>.
    | <a href="http://potnia.org">Powered by BUFVC Potnia</a> 
    <? if ($CONF['debug']): /* clear session command link */ ?>
    | <a href="<?=$MODULE->url('index', '?clear_session=true')?>">Clear session data</a>
    <? endif ?>
    
	</p>
	</div> <!-- footer -->
	</div> <!-- footer wrapper -->
</div> <!-- page -->
</body>
</html>
