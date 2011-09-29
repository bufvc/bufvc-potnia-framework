<?php
// $Id$
// Terms and conditions
// Phil Hansen, 12 Feb 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_templates'] . 'inc-header.php';

?>
<h3>Terms of Use</h3>

<p>The BUFVC/Bournemouth University Independent Local Radio and LBC collections
resource is made available for personal study and teaching through the
following sub-licence which is applicable only to Educational Establishments
as defined by Statutory Instrument. The rights owners of the collections are
pleased that their works can be used for bona fide learning, teaching and
research, but it is the users' responsibility to ensure that these collections
are used only to the extent permitted by rights owners and thereby encourage
participation from other content providers for the future.

<p>Note: Nothing in these Terms of Use shall constitute a waiver of any
statutory right available from time to time under the Copyright, Designs and
Patents Act 1988 or any amending legislation.

<p>TAKING INTO ACCOUNT AT ALL TIMES THE RESTRICTIONS WHICH APPLY TO THE
COLLECTIONS AND ANY ASSOCIATED MATERIALS AND PROVIDING YOU ARE OPERATING IN AN
EDUCATIONAL INSTITUTION AS DEFINED BY STATUTORY INSTRUMENT WITH A SECURE
NETWORK FOR DELIVERY TO STUDENTS AND STAFF ON THE PREMISES AND AT A DISTANCE

<h3>YOU MAY</h3>

<p>1. access the collections and any associated materials by means of a Secure
Network in order to search, retrieve and listen to recordings. For the
avoidance of doubt a Secure Network (whether stand-alone network or a virtual
network with the internet) is only accessible to staff and students of this
institution whose identities are authenticated by your institution at the time
of log-in and periodically thereafter consistent with current practice and
whose conduct is subject to regulation by your institution. A cache server or
other server or network which can be accessed by anyone other than yourself is
not a Secure Network for these purposes

<p>2. where downloading is enabled in the delivery (not all collections or
titles within collections are enabled for download), incorporate parts of the
collections and any associated materials in printed or electronic course and
study packs or multi-media works in the course of instruction for your
educational institution. Each item shall carry appropriate and due
acknowledgement of the source, title and copyright owner.

<p>3. where downloading is enabled by the delivery, incorporate whole or parts
of the collections, associated materials and adaptations of any associated
materials in printed or electronic form in, assignments, portfolios (including
non-public display thereof) and in dissertations, including reproductions of
the dissertation for personal use and library deposit, if such use conforms to
the customary and usual practice of your institution. Each item shall carry
appropriate and due acknowledgement;

<p>4. play parts of the collections and any associated materials for the
purpose of promotion of the collections and any associated materials, testing
of the product, or for training of users who have agreed to these User Terms
and Conditions

<p>5. make such copies of and network training material as may be required for
the purpose of using the collections and any associated materials in
accordance with these User Terms and Conditions.

<h3>YOU MAY NOT</h3>

<p>6. use all or part of any of the collections (including any associated
materials) in any way which is contrary to any restrictions or specific use
arrangements which may be appended to any of the collections (including any
associated materials) and/or notified to you by any other means by your
institution

<p>7. use the collections (or any associated materials) in any way beyond that
which is permitted. For the avoidance of doubt this includes but is not
limited to: any activity which might be commercial in character, generate
revenue, selling, reselling, exposing for hire, redistributing, publishing or
otherwise make the information contained in the collections (including any
associated materials) available in any manner or on any media beyond that
which is permitted

<p>8. adapt the content in any way

<p>9. remove, obscure or modify copyright notices, text acknowledging or other
means of identification or disclaimers as they may appear

<p>10. make printed or electronic copies of the whole or parts of the
collections and any associated materials for any purpose, beyond those
authorised by this User Agreement

<p>11. display or distribute any part of the collections or any associated
materials on any electronic network, including without limitation the Internet
and the World Wide Web, and any other distribution medium now in existence or
hereinafter created, other than by a Secure Network

<p>12. permit anyone else other than yourself to access or use the collections
or any associated materials

<?/* ### TODO: need to decide whether to have this feature or not
<form name="terms_form" method="POST" action="{$site_url}/index.php" style="margin:0">
<p><input type="checkbox" name="accepted"{if $accepted_terms} CHECKED{/if}>
I agree to the terms and conditions of use above.<br>
<input type="submit" name="accept_terms" value="Continue">
</form>
###*/?>

<?php require_once $CONF['path_templates'] . 'inc-footer.php' ?>
