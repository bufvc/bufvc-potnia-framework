<?php
// $Id$
// Display a block of HTML from code
// James Fryer, 27 May 10, 7 July 11
// BUFVC Potnia copyright 2011, BUFVC et al. See LICENSE for licensing information (GPL3). See http://potnia.org, http://bufvc.ac.uk

class Block
    {
    var $name = 'default';
    var $vars = Array();
    var $hidden = FALSE;
    
    function __construct($name=NULL)
        {
        if ($name != '')
            $this->name = $name;
        }

    /// Render the block from the template
    function render()
        {
        if (!$this->hidden)
            return $this->_get_template('block-' . $this->name, $this->vars);
        }

    // Need to load the template in this class so '$this'
    private function _get_template($template_name, $vars=NULL)
        {
        global $MODULE;
        $result = NULL;
        $template = $MODULE->find_template($template_name);
        if ($template != '')
            {
            if (!is_null($vars))
                extract($vars);
            ob_start();
            include $template;
            $result = ob_get_contents();
            ob_end_clean();
            }
        return $result;
        }
        
    /// Set the variables which will be in scope when the block is rendered
    function set_vars($vars)
        {
        $this->vars = $vars;
        }
    }

/// A block with title and sub-menu. Uses template 'sidebar_item'
class SidebarBlock 
    extends Block
    {
    /// Items can be an array, which can contain arrays of label,value,url
    function __construct($title, $description=NULL, $items=NULL, $help_text=NULL, $hidden=FALSE)
        {
        parent::__construct('sidebar_item');
        if (is_null($items))
            $items = Array();
        $this->set_vars(compact('title', 'description', 'items', 'help_text'));
        $this->hidden = $hidden;
        }

	// Convert a title to legal CSS class
	protected function css_class_name($title=null) 
		{
		if ($title != '') 
            return str_replace(Array(' ', '/'), "-", strtolower($title));
		}
    
    /// Determines if the given item is currently 'selected'
    /// To be implemented by subclasses
    function is_selected($item)
        {
        return FALSE;
        }
    
    // Can be customized in subclasses to add any extra text to a rendered item
    function add_extra_text($item)
        {
        return '';
        }
    }

