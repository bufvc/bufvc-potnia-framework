<?php
# $Id$
# Ironduke access lib
# James Fryer, 24 Mar 10
# BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

require_once $CONF['path_src'] . 'basic/XMLParser.class.php';

/// PHP client for IronDuke -- create tickets only
class Ironduke
    {
    function Ironduke($service_url)
        {
        $this->service_url = $service_url;
        }
        
    /// Create a new ticket
    /// Return the new ticket data
    /// If the ticket can't be created, return NULL
    //### TODO: add policy array
    function create_ticket($resource_url)
        { 
        $service_url = $this->service_url . '/'; //### FIXME: the server should be callable without this
        $data = Array('resource_url'=>$resource_url, );
        $response = http_request($service_url, 'POST', $data);
        if (is_null($response) || $response[0] != 201)
            return NULL;
        return $this->_parse_ticket($response[1]);
        }

    // Helper: parse ticket XML
    function _parse_ticket($ticket_xml)
        {
        // This XML parser gives us almost what we need, just tidy it up a bit
        $p = new XMLParser($ticket_xml);
        $result = $p->getOutput();
        if (@$result['ticket'] == '')
            return $result;
        $result = $result['ticket'];
        $result['id'] = $result['token']; //### FIXME: Remove when token renamed in service
        return $result;
        }
    }
