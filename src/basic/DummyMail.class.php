<?php
// $Id$
// Dummy mailer for testing
// James Fryer, 2 Dec 09
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

/// Use this class instead of a real Mail class for tests
/// It writes the mail body to /tmp/recipients.eml
class DummyMail
    extends Mail
    {
    function DummyMail()
        {
        global $CONF;
        $this->path = $CONF['path_tmp'];
        }
        
    /// "Send" an email by saving it to a file
    function send($recipients, $headers, $body)
        {
        // This code copied from the Mail.php pear class -- shame they didn't template it...
        $this->_sanitizeHeaders($headers);
        if (is_array($recipients))
            $recipients = join(', ', $recipients);
        list($from,$text_headers) = Mail::prepareHeaders($headers);
        $filename = $this->path . $recipients;
        file_put_contents($filename, "$text_headers\r\n\r\n$body\r\n");
        }

    /// Get the contents of the last email sent to the recipients
    function get_last_mail($recipients)
        {
        $filename = $this->path . $recipients;
        return @file_get_contents($filename);
        }

    /// Remove the email file
    function cleanup($recipients)
        {
        $filename = $this->path . $recipients;
        @unlink($filename);
        }
    }
