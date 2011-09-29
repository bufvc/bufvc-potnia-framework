<?php
// $Id$
// Email Records form
// James Fryer, 2 Dec 2009
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';
?>

<? if ($USER->has_right('save_data') && $USER->email == ''): ?>
Please <a href="<?=$MODULE->url('prefs')?>">set your email address</a>.
<? elseif (@$EMAIL_BODY != ''): ?>
<p>Use the form below to send records by email.</p>
<form method="post">
To: <input type="text" name="email" value="<?=$USER->email?>" />
<input type="submit" name="send" value="Send Email" />
<input type="submit" name="cancel" value="Cancel" />
<? if ($CONF['email_note_length']): ?>
<p>Enter an optional message (max <?=$CONF['email_note_length']?> chars) in the box below<br>
<textarea name="note" rows="4"><?=@$_POST['note']?></textarea>
</p>
<? endif /* Email note */ ?>
</form>
<? if ($COUNT_EMAILS_SENT): ?>
<p>Emails are limited to a maximum of <?=$CONF['max_emails_per_session']?> per session.
You have sent <?=$COUNT_EMAILS_SENT . ' ' . pluralise($COUNT_EMAILS_SENT, 'email')?>.
<? if ($COUNT_EMAILS_SENT == ($CONF['max_emails_per_session'] - 1)): ?><br /><b>This is the last email you will be able to send in this session.</b><? endif ?>
</p>
<? endif /* Session count */ ?>
<p>The data below will be sent by email:</p>
<pre>
<?= $EMAIL_BODY ?>
</pre>
<? else: ?>
<p>No records to email</p>
<? endif ?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
