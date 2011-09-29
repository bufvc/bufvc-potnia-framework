<?php
// $Id$
// Bot detector
// James Fryer, 23 June 10
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

// Table of robot user agent strings, used by identify_bot
//      Perl RE to match the UA against => bot name
// To add a new bot:
//  1. Add the bot's UA and expected return to the tests in testIdentifyBot.php
//  2. Add an entry below to make the test pass
$bot_user_agents = Array(
    '/Googlebot/'=>'google',
    '/Twiceler/'=>'cuil',
    '/msnbot/'=>'bing',
    '/scirus-crawler/'=>'scirus',
    '/Yahoo. *Slurp/'=>'yahoo',
    );

/// Identify a 'bot' from its UA string.
/// Returns NULL (unidentified), 'google', 'cuil'
function identify_bot($ua)
    {
    global $bot_user_agents;
    foreach ($bot_user_agents as $match=>$name)
        {
        if (preg_match($match, $ua))
            return $name;
        }
    }
