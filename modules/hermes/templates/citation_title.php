<?php
// $Id$
// Citation template for Hermes titles
// Alistair Macdonald, 6 June 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk


    if (@$RECORD['title'] != '')
        print '"' . $RECORD['title'] . '"';
    if (@$RECORD['subtitle'] != '')
        print '; ' . '"' . $RECORD['subtitle']. '"';
    if (@$RECORD['title_series'] != '')
        print '; ' . '"' . $RECORD['title_series']. '"';
    if (isset($RECORD['date_released']))
        print '; ' . $RECORD['date_released'];
