(function($){
	
	var settings = {
		defaultOptionText : "All fields",
		originalSelect : {
			conditionals : "select-conditional",
			options : "select-option"
		},
		divID : "advanced-controls",
		innerDIV : "enhanced-input",
		tagContainer : 'tag-container',
		buttonsWrap : 'buttons-container'
	}
	
	var internalIndex = 0;
	
	getInternalIndex = function() {
		return internalIndex;
	};
	
	setInternalIndex = function(index){
		internalIndex = index;
	};
	
	advancedform = function($fieldset, options){
		var self = this,
			initialOptionsSelectedIndex = [],
			initialConditionalsSelectedIndex = [], 
			tags = [],
			inputIndexes = [];

		// cache the conditionals dropdown list
		var selectConditionals = $fieldset.find("select." + options.originalSelect.conditionals);
		
		// cache the options
		var selectOptions = $fieldset.find("select." + options.originalSelect.options );
		
		// cache the text fields and remove from original from, can't have same IDs
		var inputFields = $fieldset.find('input[type=text]').remove();
		
		// cache submit button and remove it from form, you can't have two same IDs
		var submitButton = $fieldset.find('input[type=submit]').remove();
		
		// maximun number of the new block of controls, if the backend changes it will change here as well
		var maxNumberTextFields = inputFields.length;
				
		var advancedContainer = $('<div id="'+ options.divID + '" class="clearfix"></div>');
		
		var searchForm = $fieldset.parent('form');
		
		//get initial selected indexes of the options dropdown
		$.each(selectOptions, function(i){
			initialOptionsSelectedIndex.push( selectOptions[i].selectedIndex );
		});

		//get initial selected indexes of the conditionals dropdown list
		$.each(selectConditionals, function(i){
			initialConditionalsSelectedIndex.push( selectConditionals[i].selectedIndex );
		});
		
		// create initial tags depending on the number of textfields
		// and getting the values from the initial selection
		for (var i=0; i < maxNumberTextFields; i++) {
			tags.push( $('<span class="tag-selected">' + selectOptions.eq(i).find('option').eq(initialOptionsSelectedIndex[i]).text() + '</span>') );
		};
		
		// plug the submit button back in the form
		advancedContainer.append(submitButton);
		
		// public method
		$.extend(self, {
			init : function(){
				
				self.updateInputIndexes();
				
				// set Internal Number
        		if (inputIndexes.length === 0) {
        			setInternalIndex(1);
        		}        		
				
				// if no populated inputs found set the default index to 1;
				if ( getInternalIndex() === 1 ) { 
					self.advancedInput(0); 
				} else {
					$.each(inputIndexes, function(i){
					    setInternalIndex(getInternalIndex() + 1);
						self.advancedInput(inputIndexes[i]);
					});				
				};
												
				// plug all to the form
				searchForm.prepend(advancedContainer);
				
				//hide native advanced input fieldset 
				$fieldset.addClass('fieldset-hidden').attr('aria-hidden', true);
			},
			
			// here we create the blocks...
			advancedInput : function (index){
				var menu = self.advancedOptions(index),
				enhancedInput = $('<div class="clearfix ' + options.innerDIV + '" id="' + options.innerDIV + '-' + getInternalIndex() + '"></div>'),
                // advancedInputHtml = $('<div><label for="' + inputFields.eq(index).attr('id') + '">Search for </label>' + inputFields[index].outerHTML + '</div>');
                advancedInputHtml = $('<div><label for="' + inputFields.eq(index).attr('id') + '">Search for </label><input type="text" name="'+inputFields.eq(index).attr('name')+'" value="'+inputFields.eq(index).val()+'" id="'+inputFields.eq(index).attr('id')+'" class="'+inputFields.eq(index).attr('class')+'" /></div>');
								
				menu.find('li').each(function(i){
					var li = $(this);
					li.find('a').click(function(){
						self.updateOptionsIndex(index, i);
						self.updateTag(index, i);
						menu.find('li.selected').removeClass('selected');
						li.addClass('selected');
						return false;
					});
				});
				
				// add conditionals if is not the first block, also hide label
				if (index !== 0) {
					advancedInputHtml.prepend( selectConditionals.eq( index - 1 ));
					advancedInputHtml.find('label').addClass('hide-label');
				};
				
				// created the tags
				var tagContainer = $('<span class="' + options.tagContainer + '"> in: </span>');
				tagContainer.append(tags[index]);
				advancedInputHtml.append(tagContainer);
					
				// create the input field, with label or conditional included
				enhancedInput.append(advancedInputHtml);
								
				// create the options
				enhancedInput.append(menu);
								
				var addRemove = self.addRemoveLinks(index, enhancedInput);
				
				enhancedInput.append(addRemove);
				
				// add current block to the container
				advancedContainer.append(enhancedInput);
			},
			
			addRemoveLinks : function(index, enhancedInput){
				// create the ADD only, ADD and REMOVE, or REMOVE only links
				var addLink = $('<span><a href="#" title="Add another search field" id="add-link-' + getInternalIndex() + '" class="add-link">Add</a></span>');
				var removeLink = $('<span><a href="#" title="Remove current search field" id="remove-link-' + getInternalIndex() + '" class="remove-link">Remove</a></span>');
				
				//----- Lets calculate the state of the buttons, whether they should ber hidden or shown depending on the number of 
                
                // the ADD button
                // Show the add link if this is the FIRST and the ONLY enhanced-input
                if (inputIndexes.length === 0 && getInternalIndex() === 1) { addLink.show(); };
                
                // Hide the FIRST add link if there is MORE than one enhanced-input
                if ( getInternalIndex() === 1 && inputIndexes.length > 1) { addLink.hide(); };
                
                // Hide the addLink if we have reached the maximun number of input fields
                if (maxNumberTextFields === inputIndexes.length) { addLink.hide(); };
                
                
                // the REMOVE button
                // hide the remove link if it's not the very last one - 3  
                if ( getInternalIndex() !== maxNumberTextFields ) { removeLink.hide(); };
                
                // show only the remove if it is at the bottom                 
                if ( (getInternalIndex() > 1) && (getInternalIndex() < maxNumberTextFields) &&  (inputIndexes.length !== maxNumberTextFields) ) { removeLink.show(); };
                			    
				// ADD LINK BUTTON Click events binding
				addLink.find('a').click(function(e){				    
				    // make sure the current input field is not empty before adding a new input field
				    if (enhancedInput.find('input').val() === "") {
				        alert('Please input your query before adding a new field');
				        return false;
				    };
					setInternalIndex( getInternalIndex() + 1 );
					self.advancedInput( getInternalIndex() - 1 );
										
					// Hide the link when adding a new enhanced-input
                    $(this).parent().hide();
                    
                    if (getInternalIndex() > 1 ) {
                        removeLink.hide();
                    };
					
                    return false;
				}).data('add', getInternalIndex());
				
								
				// REMOVE LINK click events binding
				removeLink.find('a').click(function(e){
                                        
                    // change the status of the previous enhanced-input buttons.. 
                    if (getInternalIndex() > 1) {
                        advancedContainer.find('#add-link-' + ( getInternalIndex() - 1)).parent().show();
                        advancedContainer.find('#remove-link-' + ( getInternalIndex() - 1)).parent().show();
                    };
                    
                    // reset the current options dropdown list to the default "all fields"
                    self.updateOptionsIndex(index, 0);
                                        
                    // reset the conditionals dropdown list to default "AND"
                    selectConditionals.eq(index-1)[0].selectedIndex = 0;
                    
                    // empty the value of the current text field
                    inputFields.eq(index).val("");
                    
                    self.updateInputIndexes();
                    
                    // reset the tag label
                    self.updateTag(index, 0);
                                        
                    setInternalIndex( getInternalIndex() - 1 );
										
					// finally remove current block
                    enhancedInput.remove();
                    
					return false;
					
				}).data('remove', getInternalIndex());
				
				// RETURN the buttons add, add-remove or remove with all the events already attached

				// first advanced-input, we never remove the first input
				if (getInternalIndex() === 1 ) {
					return addLink;
				};
				
				// links for enhanced-input between first and last
				if ( getInternalIndex() === maxNumberTextFields -1 )  {
					return addLink.add(removeLink);
				};
				
				// link remove for the last enhanced-input
				if (getInternalIndex() === maxNumberTextFields) {
					return removeLink;
				};
				
			},
			
			// get the value of the initial selected in options dropdown
			getSelectedValue : function(index, itemSelected) {
				return selectOptions.eq(index).find('option').eq(itemSelected).text();
			},
			
			updateTag : function(index, itemSelected) {
				tags[index].text(self.getSelectedValue(index, itemSelected));
			},
						
			updateOptionsIndex : function(index, itemSelected) {
				selectOptions.eq(index)[0].selectedIndex = itemSelected;
                initialOptionsSelectedIndex[index] = itemSelected;
			},
			
			// this function only creates the menu and returns an object
			// see advanced input for event binding
			advancedOptions : function(index) {
				// get the options in current selected list and create list container
				var optionsInList = selectOptions.eq(index).find('option'),
					menuOptions = $('<ul class="advanced-menu-options clearfix"></ul>');
				
				optionsInList.each(function(i){
					var li = $('<li><a href="#">' + $(this).text() + '</a></li>');
					
                    // check if index is equal initial selected option
                    if (initialOptionsSelectedIndex[index] === i) {
                     li.addClass('selected');
                    }; 
                					
					menuOptions.append(li);
				});
				
				return menuOptions;
            },
			
		    updateInputIndexes : function() {
		        // redefine the number of not empty inputs
		        inputIndexes = [];
        		for (var i=0; i < maxNumberTextFields; i+=1) {
        			if (inputFields.eq(i).val() !== "") {
        				inputIndexes.push(i);
        			};
        		};
		    }
						
		});

		self.init();
	};
	
	$.fn.advancedForm = function(options) {
		var tmp = this.data("advancedform");
		if (tmp) { return tmp; }
		
		options = $.extend({}, settings, options);
		
		this.each(function(){
			tmp = new advancedform($(this), options);
			$(this).data('advancedform', tmp);
		});
		return this;
	}
}(jQuery));

// selecting only the advanced seach form in news on screen 
// remove news on screen selector to propagate to all advanced search forms
jQuery(document).ready(function($){
	$('#search fieldset.searchset-advanced.asf-newsonscreen').advancedForm();
});
