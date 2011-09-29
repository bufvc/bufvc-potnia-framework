<?php
// $Id$
// Listings base class
// Phil Hansen, 05 Apr 2010
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


/** Manages listings data
*/
class Listings
    {
    var $title = 'Listings';

    /// Days prior to this date may have incomplete data
    var $incomplete_listings_date = '0000-00-00';

    /// Default channel list
    var $default_channel_list;

    function Listings()
        {

        }

    /// Process posted criteria and prepare it for searching
    function process_criteria($query=NULL)
        {

        }

    /// Collate results
    function collate_results($query)
        {

        }
    }
?>
