<?php
// $Id$
// Playlist generator for IRN/LBC project
// Phil Hansen, 19 Feb 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


/// Output a record's files in XSPF format.
/// Can be used for all files or one particular file.
class Playlist
    {
    // Generates playlist for all files in a given media array
    function generate_all($media)
        {
        global $CONF;
        $result = '';
        
        if (empty($media) || !is_array($media))
            return NULL;
        
        foreach ($media as $file)
            {
            // only add files of type 'audio/mpeg'
            if ($file['content_type'] != 'audio/mpeg')
                continue;
            
            $result .= $this->generate_single($file);
            }
        
        return $result;
        }
    
    // Generates playlist information for a single file
    function generate_single($file)
        {
        global $CONF;
        
        // only add files of type 'audio/mpeg'
        if (empty($file) || $file['content_type'] != 'audio/mpeg')
            return NULL;        
        return '<track><meta rel="type">mp3</meta><location>' . $file['location'] . "</location></track>\n";
        }
    }
