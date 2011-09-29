<?php
// $Id$
// Output a record's files in XSPF format
// James Fryer, 6 Oct 08
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

$playlist = new Playlist();

$file = NULL;
if (isset($_REQUEST['file']))
    $file = $_REQUEST['file'];

// check user right
if ($USER->has_right('play_audio'))
    {
    // header
    print '<playlist version="1" xmlns="http://xspf.org/ns/0/">' . "\n";
    print '<title>' . $RECORD['title'] . '</title>' . "\n";
    print  "<trackList>\n";

    if (!is_null($file) && isset($RECORD['media'][$file]))
        print $playlist->generate_single($RECORD['media'][$file]);
    else if (count(@$RECORD['media']) > 0)
        print $playlist->generate_all($RECORD['media']);

    print  "</trackList>\n";
    print '</playlist>' . "\n";
    }
else
    {
    header("HTTP/1.0 403 Forbidden");
    print("You do not have permission to play audio.\n");
    }
?>
