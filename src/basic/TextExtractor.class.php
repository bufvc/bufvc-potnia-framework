<?php
// $Id$
// Class for TextExtractor
// Ali Macdonald 8 April 2011
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class TextExtractor
    {
    var $error_message;
    var $supported_formats = array();
    // paths to required utilities
    var $catdoc = '/usr/bin/catdoc';
    var $ppthtml = '/usr/bin/ppthtml';
    var $pdftotext = '/usr/bin/pdftotext';

    public function __construct()
        {
        // store details of each format's name, extension and supported status
        $this->supported_formats = $this->formats();
        }

    /// formats supported by class
    public function formats()
        {
        // include whether or not each format is supported by machine
        $formats = array
            (
            '.doc'=>array('name'=>'Word','supported'=>is_executable($this->catdoc),'method'=>'use_catdoc'),
            '.docx'=>array('name'=>'Word 2007','supported'=>TRUE,'method'=>'docxtotext'),
            '.txt'=>array('name'=>'Plain text','supported'=>TRUE,'method'=>'use_file_get_contents'),
            '.pdf'=>array('name'=>'Adobe PDF', 'supported'=>is_executable($this->pdftotext),'method'=>'use_pdftotext'),
            '.rtf'=>array('name'=>'Rich Text Format', 'supported'=>is_executable($this->catdoc),'method'=>'use_catdoc'),
            '.ppt'=>array('name'=>'PowerPoint', 'supported'=>is_executable($this->ppthtml),'method'=>'use_ppthtml')
            );
            return $formats;
        }

    /// method to return text from a passed file
    /// for file uploads $_FILES['file']['name'] would be $data_filename and 
    /// pass $_FILES['file']'tmp_name'] as $filename
    public function extract($filename, $data_filename = NULL)
        {
        if(!is_file($filename))
            {
            $this->error_message = 'The file '.$filename.' does not exist';
            return NULL;
            }
        // extract the file extension, use $data_filename if passed        
        $extension =  strtolower(strrchr($data_filename != '' ? $data_filename : $filename,'.'));
        if(@!$this->supported_formats[$extension]['supported'])
            {
            $this->error_message = '<p>The filetype you have submitted, '.$extension.', is not currently supported.</p>';
            return NULL;
            }
        // extension is supported so run command to extract text
        $fn = $this->supported_formats[$extension]['method'];
        return $this->$fn($filename);
        }

    private function use_catdoc($filename)
        {
        return shell_exec($this->catdoc . ' -w ' . escapeshellarg($filename) . ' 2>/dev/null');
        }

    private function use_ppthtml($filename)
        {
        return shell_exec($this->ppthtml . ' ' . escapeshellarg($filename) . ' 2>/dev/null');
        }

    private function use_pdftotext($filename)
        {
        return shell_exec($this->pdftotext . ' ' . escapeshellarg($filename) . ' - 2>/dev/null'); 
        }

    private function use_file_get_contents($filename)
        {
        return file_get_contents($filename);
        }

    /// method to use ZipArchive as a holder for MS Word docx files which are XML bundles 
    private function docxtotext($filename)
        {
        // docx path to the body content of the document
        $dataFile = "word/document.xml";
        $zip = new ZipArchive;
        if (true === $zip->open($filename))
            {
            if (($index = $zip->locateName($dataFile)) !== false) 
                {
                $text = $zip->getFromIndex($index);
                $xml = DOMDocument::loadXML($text, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                return trim(strip_tags($xml->saveXML()));
                }
            $zip->close();
            }
        }
    }
