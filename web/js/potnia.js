// Gabriel Hernandez
// 6 dec 2010
// plugin add hover class...
(function($){
    
    var settings = {
        hoverClass : "-hover"
    }
    
    function hoverclass($elem, options){
        var hoverClasses = $elem.attr('class').split(' ');
            
            $.each(hoverClasses,function(i){
                hoverClasses[i]+=options.hoverClass;
            });

            hoverClasses = hoverClasses.join(' ');
            // hoverClasses += ' hover';
        
            //apply hover classes
            $elem.hover(
                function(){ $(this).addClass(hoverClasses); },
                function(){ $(this).removeClass(hoverClasses); }
            );
    }
    
    $.fn.hoverClass = function(options) {
        
        // return existing instance
        var api = this.data("hoverclass");
        if (api) { return api; }
        
        options = $.extend({}, settings, options);
        
        this.each(function(){
            api = new hoverclass($(this), options);
            $(this).data("hoverclass", api);
        });
        
        return this;
    }
})(jQuery);

// Mark - unmark records
// Gabriel Hernandez - BUFVC
// 20 Nov 2010
// first 
(jQuery)(function($){

    var defaults = {
        textContainer : '#marked_count',
        listContainer : '#viewed-records',
        attrUrlRecord : 'name',
        ajaxType : 'POST',
        markedMenuList : '#marked-menulist',
        titleContainer : 'h3',
        menuContainer : '#content-navigation',
        menuRecords : '#menu-records',
        htmlMenuRecords : '<ul class="app-menu records" id="menu-records"></ul>',
        htmlMarkedMenuList : '<ul id="marked-menulist"><li class="navmenu-link"><a>All Marked Records (<span id="marked_count"></span>)</a></li></ul>',
        wrappMarkedMenuList : '<li></li>',
        titleMarkedMenuList : '<p class="menu-title marked-records">Marked Records</p>',
        maxShown : '6',
        recordStatus : "#record-status",
        statusMessage : "#status_message"
    }
        
    function markunmark($container, options) {
        var $self = $(this),
            $checkboxes = $container.find("input[type=checkbox]"),
            $textContainer = $(options.textContainer),
            markedCounter = parseInt($textContainer.html()),
            $form = $checkboxes.parents('form'),
            urlPost = $form.attr('action'),
            allMarkedUrl = $("input[name='all-marked-url']").val();
            $menuList = $(options.markedMenuList),
            $menuRecords = $(options.menuRecords),
            $statusMessage = $(options.statusMessage);
            
            // public methods
            $.extend($self, {
                init : function(){
                    // are there checkboxes to work with?
                    if($checkboxes.length > 0) {                        
                        if(!markedCounter)
                            markedCounter = 0;
                        // is record page    
                        if($checkboxes.length == 1)
                            $self.hideSubmit();
                        
                        // bind click event to checkboxes
                        $checkboxes.click(function(event){
                            var $checkbox = $(this),
                                urlRecord = $checkbox.attr(options.attrUrlRecord),
                                sendtoserver;
                            
                            // prepare data for post... 
                            sendtoserver = "url=" + escape(encodeURI(urlRecord)) + '&ajax=1';
                                
                            if ($checkbox.is(':checked')) {
                                $self.markRecord(sendtoserver);
                            }
                            else {
                                $self.unmarkRecord(sendtoserver);
                            }
                        });
                    }
                },
                
                hideSubmit : function() {
                    $form.find('input[type=submit]').hide();
                },
                
                updateMessage : function(message){
                    $statusMessage.html(message)
                    .show()
                    .animate({opacity: 1.0}, 1000)
                    .fadeOut('slow');
                },
                
                updateCounter : function() {
                    if ($textContainer.length > 0)
                        $textContainer.html(markedCounter);
                    else 
                        $textContainer = $(options.textContainer).html(markedCounter);
                        
                },
                                
                createMenuList : function(){
                    $menuList = $(options.htmlMarkedMenuList).appendTo(options.menuRecords);
                    // add link for view all mark records list
                    $menuList.find('.navmenu-link a').attr('href', allMarkedUrl);
                    $menuList.wrap(options.wrappMarkedMenuList);
                    $menuList.parent().prepend(options.titleMarkedMenuList);
                    $self.updateCounter();
                },
                
                removefromMenu : function (fromServer){
                        $menuList.find('li').each(function(i){
                            if ( $(this).text() == fromServer.title) {
                                // console.log(i + ':' + $(this).text());
                                // ##TODO move this html to options section
                                $(this).append(' <span style="color:red">removing...</span>').fadeOut('slow', function(){ 
                                    $(this).remove(); 
                                });
                            }
                            else return;
                        });
                        
                },
                
                addtoMenu : function (fromServer) {
                    // create menu on the fly if it doesn't exist, is there any viewed?
                    if ($menuRecords.length == 0) {
                        $menuRecords = $(options.htmlMenuRecords).appendTo(options.menuContainer);
                        $self.createMenuList();
                    } else if ($menuList.length == 0){
                         $self.createMenuList();
                    }        
                        $('<li><a href="' + fromServer.location + '">' + fromServer.title + '</a></li>').prependTo($menuList);
                                
                },
                
                markRecord : function (sendtoserver){
                    sendtoserver += "&mark_record=1";
                    $self.updateState(sendtoserver);
                    // console.log('from mark:' + fromServer);
                    markedCounter++;
                    $self.updateCounter();
                },
                
                unmarkRecord : function (sendtoserver){
                    sendtoserver += "&unmark_record=1";
                    $self.updateState(sendtoserver);
                    // console.log('from unmarked: ' + fromServer);
                    markedCounter--;
                    $self.updateCounter();
                },
                
                // mark or unmarked record with Ajax
                updateState : function(sendtoserver) {
                      $.ajax({
                      type: options.ajaxType,
                      url: urlPost,
                      data: sendtoserver,
                      dataType : 'json',
                      success: function(fromServer,status,xhr){
                        if (fromServer.message == "Record marked") {
                            $self.addtoMenu(fromServer);
                            // console.log(fromServer.message + ':' + fromServer.location + ':' + fromServer.title);
                        } else if (fromServer.message == "Record unmarked"){
                            $self.removefromMenu(fromServer);
                            // console.log(fromServer.message + ':' + fromServer.location + ':' + fromServer.title);    
                        }
                        
                        $self.updateMessage(fromServer.message);
                    }
                    });
                }
            });
            
        // initialise     
        $self.init();
    }
    
    $.fn.markUnmark = function(options) {
        
        var api = this.data("markunmark");
        // return existing instance
        if (api) { return api; }
        
        // merge defaults with passed options
        options = $.extend(true, {}, defaults, options);
        
        this.each(function(){
            api = new markunmark($(this), options);
            $(this).data("markunmark", api);
        });
        
        return options.api ? api : this;
    }
});

/*---- tabs plugin ----*/
// Gabriel Hernandez - BUFVC
(jQuery)(function($){
    var defaults = {
        fromElement : "h3.tab-title",
        cssActiveclass : "active",
        initialIndex : 0,
        listTabs : "ul",
        classTabs : "tabs",
        classNext : "tab-content",
        uriSearches : "searches",
        uriViewed : "viewed",
        uriMarked : "marked"
    }
    
    function Tabs($selector, options) {
        var self = this,
             $headings = $(options.fromElement, $selector),
            $tabContent = $headings.next().addClass(options.classNext),
            current, requestedTab = 0;
            windowURL = parseUri(window.location.href);
        
        $.extend(self, {
            init: function(){
                $headings.hide();
                $tabContent.hide();
                // find index of requested tab content
                $tabContent.each(function(index){
                    if ( $(this).attr('ID') == windowURL.file )
                    options.initialIndex = index;
                });    
                $tabContent.eq(options.initialIndex).show();    
                // let's build the tabs
                $($selector.prepend('<' + options.listTabs + ' class="' + options.classTabs + ' clearfix"' + '></' + options.listTabs + '>'));     
                $headings.each(function(){
                    var label = $(this).text();
                    var hashAttr = $(this).attr('id');
                    $(options.listTabs + '.' + options.classTabs, $selector).append('<li id="' + hashAttr + '">' + label + '</li>');
                    // id duplication, remove
                    $(this).removeAttr('id');
                });
                
                var tabs = $(options.listTabs + '.' + options.classTabs + ' li', $selector);
                
                tabs.eq(options.initialIndex).addClass(options.cssActiveclass);
                
                tabs.click(function() {
                    // click on active tab
                    if (current === tabs.index($(this)) )
                        { return self; }
                    tabs.removeClass(options.cssActiveclass);    
                    $tabContent.hide();
                    $(this).addClass(options.cssActiveclass)
                    current = tabs.index($(this));
                    $tabContent.eq(current).show();
                    return self;
                });         
            }
        });
        // start
        self.init();
    }

    // jQuery plugin implementation
    $.fn.Tabs = function(options){
                
        // setup configutation
        options = $.extend({}, defaults, options);        
                
        this.each(function(){
            api = new Tabs($(this), options);
            $(this).data("tabsdata", api);
        });  
        return options.api ? api : this;
    }
});

// remove facet plugin
// Gabriel Hernandez
(function($){    
    var defaults = {
        enhancedClass : 'enhanced-remove-facet',
        innerClass : 'inner-remove-facet',
        parentClass : 'selected-facet-wrapper',
        inner : '<span></span>',
        replaceText : 'x',
        hover : 'hover'
        }
    
    function removefacet($selector, options) {
        var self = this,
            link, 
            originalText,
            $innerElem,
            $parent;
                
        $.extend(self, {
            
            init : function(){
                link = $selector.attr('href');
                originalText = $selector.text();
                
                // work on individual links
                $selector.each(function(i){
                    var $this = $(this);
                    
                    $parent = $this.parent().addClass(options.parentClass)
                    .bind('mouseenter', function(e){
                        $(this).addClass(options.hover);
                        $innerElem.addClass(options.hover);
                    }).
                    bind('mouseleave', function(e){
                        $(this).removeClass(options.hover);
                        $innerElem.removeClass(options.hover);
                    });
                    
                    // remove original text and replace it with icon
                    $this.html(options.inner + options.replaceText)
                    .attr('title', originalText)
                    // eliminate any classs
                    .removeClass()
                    // add this classes
                    .addClass(options.enhancedClass);          
                    // inserted image-replacement element
                    $innerElem = $this.find('span');
                    $innerElem.addClass(options.innerClass);
                });
            },
            
            entering : function(e){
                    // $innerElem.addClass(options.hover);
                    // $parent.addClass(options.hover);
                },
            
            goingout : function(e){
                // $innerElem.removeClass(options.hover);
                // $parent.removeClass(options.hover);
            }
        });
        
        // Is there anything to work with? initialise
        if ($selector.length > 0) {
            self.init();
        }
    }
    
    // plugin implementation
    $.fn.removeFacet = function(options){
        options = $.extend({}, defaults, options);
        this.each(function(){
            api = new removefacet($(this), options);
        });
        return options.api ? api : this;
    }
})(jQuery);

(function($){
    $.fn.lockFacet = function(options) {
        options = options || {}; // $.extend({}, defaults, options);
        this.each(function(){
            var $this = $(this);
            // console.log( $this.attr('href') );
            
        });
        return options.api ? api : this;
    }
})(jQuery);


// tip help icon in the titles of side bar
// this pulgin adds a help icon to the titles in the sidebar
(function($){
    var settings = {
        inner : '<span class="help-icon">?</span>',
        titleClass : "tip-help"
    }
    
    function titlehelp($title, options) {
        var self = this,
            text,
            $innerElem;
            
        // methods
        $.extend(self, {
            
            init: function(){
                // create jquery object
                $innerElem = $(options.inner);
                $title.append($innerElem)
                .removeClass(options.titleClass);
                
                text = $title.attr("title");
                // remove title attribute from title and pass it to tip
                $title.removeAttr("title");
                
                $innerElem.data("title", text)
                .attr("title", text);
                // remove this when ready
                if (window.console) { console.log($innerElem.text()); }
            }
        });
        
        // initialise
        if ($title.length > 0)
                self.init();
    }
    
    $.fn.titleHelp = function(options) {
        
        // return if already created
        var tmp = this.data("titlehelp");
        if (tmp) { return tmp; }
        
        options = $.extend({}, settings, options);
        this.each(function(){
            tmp = new titlehelp($(this), options);
            $(this).data("titlehelp", tmp);
        });
        return this;
    }

})(jQuery);

// tips for help section in sidebar,
// with help and based in other tips plugins.
(function($) {         
    var tiphelp = {
        conf : { 
            
            // default effect variables
            effect: 'toggle',            
            fadeOutSpeed: "fast",
            predelay: 0,
            delay: 30,
            opacity: 1,            
            tip: 0,
            // 'top', 'bottom', 'right', 'left', 'center'
            position: ['top', 'center'], 
            offset: [0, 0],
            relative: false,
            cancelDefault: true,
            
            // type of event 
            events : ['mouseenter', 'mouseleave'],
    
            layout: '<div/>',
            tipClass: 'tip-text'
        } 
    };
    
    var effects = { 
        toggle: [ 
            function(done) { 
                var conf = this.getConf(), tip = this.getTip(), o = conf.opacity;
                if (o < 1) { tip.css({opacity: o}); }
                tip.show();
                done.call();
            },
            
            function(done) { 
                this.getTip().hide();
                done.call();
            } 
        ],
                
        fade: [
            function(done) { 
                var conf = this.getConf();
                this.getTip().fadeTo(conf.fadeInSpeed, conf.opacity, done); 
            },  
            function(done) { 
                this.getTip().fadeOut(this.getConf().fadeOutSpeed, done); 
            } 
        ]        
    };   

    /* calculate tip position relative to the trigger */      
    function getPosition(trigger, tip, conf) {    

        // get origin top/left position 
        var top = conf.relative ? trigger.position().top : trigger.offset().top, 
             left = conf.relative ? trigger.position().left : trigger.offset().left,
             pos = conf.position[0];

        top  -= tip.outerHeight() - conf.offset[0];
        left += trigger.outerWidth() + conf.offset[1];
                
        // adjust Y        
        var height = tip.outerHeight() + trigger.outerHeight();
        if (pos == 'center')     { top += height / 2; }
        if (pos == 'bottom')     { top += height; }
        
        // adjust X
        pos = conf.position[1];     
        var width = tip.outerWidth() + trigger.outerWidth();
        if (pos == 'center')     { left -= width / 2; }
        if (pos == 'left')       { left -= width; }     
        
        return {top: top, left: left};
    }        

    function TipHelp(trigger, conf) {
        var self = this, 
             fire = trigger.add(self),
             tip,
             timer = 0,
             pretimer = 0, 
             title = trigger.attr("title"),
             effect = effects[conf.effect],
             shown,
                 
             evt = conf.events; 
        
        // bind trigger event - show  
        trigger.bind(evt[0], function(e) {

            clearTimeout(timer);
            if (conf.predelay) {
                pretimer = setTimeout(function() { self.show(e); }, conf.predelay);    
                
            } else {
                self.show(e);    
            }
            
        // bind trigger event - hide
        }).bind(evt[1], function(e)  {
            clearTimeout(pretimer);
            if (conf.delay)  {
                timer = setTimeout(function() { self.hide(e); }, conf.delay);    
                
            } else {
                self.hide(e);        
            }
            
        }); 
        
        // remove default title
        if (title && conf.cancelDefault) { 
            trigger.removeAttr("title");
            trigger.data("title", title);            
        }        
        
        $.extend(self, {
            show: function(e) {  

                // tip not initialized yet
                if (!tip) {

                // tip from titles
                     if (title) { 
                        tip = $(conf.layout).addClass(conf.tipClass).appendTo(document.body)
                            .hide().append(title);

                    // tip from next element, will use at some point
                    } else {    
                        tip = trigger.next();  
                        if (!tip.length) { tip = trigger.parent().next(); }      
                    }
                    // nothing to work with
                    if (!tip.length) { throw "Cannot find tip help for " + trigger;    }
                } 
                 
                 if (self.isShown()) { return self; }  
                
                 // stop previous animation
                 tip.stop(true, true);                  
                 
                // get position
                var pos = getPosition(trigger, tip, conf);            
        
                // restore title for single tip help element
                if (conf.tip) {
                    tip.html(trigger.data("title"));
                }
                
                // set position of tip
                tip.css({position:'absolute', top: pos.top, left: pos.left});                    
                
                shown = true;
                
                // invoke effect 
                effect[0].call(self, function() {
                    e.type = "onShow";
                    shown = 'full';
                    fire.trigger(e);         
                });                    

                // tooltip events       
                var event = conf.events;

                if (!tip.data("ready")) {
                    
                    tip.bind(event[0], function() { 
                        clearTimeout(timer);
                        clearTimeout(pretimer);
                    });
                    
                    if ( event[1] ) {                     
                        tip.bind(event[1], function(e) {
    
                            // being moved to the trigger element
                            if (e.relatedTarget != trigger[0]) {
                                trigger.trigger(evt[1].split(" ")[0]);
                            }
                        }); 
                    } 
                    
                    tip.data("ready", true);
                }
                
                return self;
            },
            
            hide: function(e) {

                if (!tip || !self.isShown()) { return self; }
                
                shown = false;
                
                effects[conf.effect][1].call(self, function() {
                    e.type = "onHide";
                    fire.trigger(e);         
                });
                
                return self;
            },
            
            isShown: function(fully) {
                return fully ? shown == 'full' : shown;    
            },
                
            getConf: function() {
                return conf;    
            },
                
            getTip: function() {
                return tip;    
            },
            
            getTrigger: function() {
                return trigger;    
            }        
        });
        
        // bind onhide and onShow
        $.each("onHide,onShow".split(","), function(i, name) {    
            // configuration
            if ($.isFunction(conf[name])) { 
                $(self).bind(name, conf[name]); 
            }
        });        
    }
        
    // jQuery plugin implementation
    $.fn.tiphelp = function(conf) {
        
        // return existing instance
        var api = this.data("tip_help");
        if (api) { return api; }

        conf = $.extend(true, {}, tiphelp.conf, conf);
                
        this.each(function() {
            api = new TipHelp($(this), conf); 
            $(this).data("tip_help", api); 
        });
        return this;
    };
}) (jQuery);


// history slider
(jQuery) (function($){
    
    var settings = {
        currentSearch : '#current-search-menu',
        containerElem : 'li',
        prevID : 'hs-prev',
        nextID : 'hs-next',
        hsNavID : 'hs-nav',
        hsCounterID : 'hs-navcounter',
        disable : 'disable',
        nsElement : 'ns-element'
    }
    
    function historyslider($prevSearch, options) {
        var self = this,
            prevSearchContainer = $prevSearch.parent(options.containerElem),
            $currentSearch = $(options.currentSearch),
            currSearchContainer = $currentSearch.parent(options.containerElem),
            $currentSearchElems, $prev, $next, $hsCounter, $hsNav, 
            index = 0, initPos;
    
        $.extend(self, {
            init : function(){
                
                // insert previous searches into current search menu, 
                // except the last item wich is the link to the history page
                $prevSearch.find('li').not(':last').each(function(i){
                    $(this).appendTo($currentSearch);
                });
                
                // cache list elements -children- and apply styles on the fly
                $currentSearchElems = $currentSearch.find('li').each(function(i){
                    $(this).css({'float':'left', 'width': self.elemWidth() }).addClass(options.nsElement);
                });
                
                currSearchContainer.css({'overflow':'hidden', 'position' : 'relative'})
                .addClass('hs-container')
                // uncomment line below in case we want the History page link
                .after(function(){return $prevSearch.find('li:last');})
                //
                // Create history slider navigation
                .after('<li id="' + options.hsNavID + '"><a id="' + options.nextID + '" title="prev"><<</a><span id="' + options.hsCounterID + '">&nbsp;</span><a id="' + options.prevID +'" title="next">>></a></li>');
                
                // cache history slider navigation, arrows and ounter elements to use later
                $hsNav = $('#' + options.hsNavID);
                $prev = $hsNav.find('#' + options.prevID);
                $next = $hsNav.find('#' + options.nextID);
                $hsCounter = $hsNav.find('#' + options.hsCounterID);
                
                // style current search menu
                $currentSearch.css({'width':self.totalSize(), 'position':'relative'}).addClass('clearfix');
                // save current search initial position
                initPos = $currentSearch.position().left;
            
                
                // disable arrows if begining or end of the list            
                if ( self.getIndex() == 0 ) { $prev.addClass(options.disable); }
                if (self.getIndex() == ( self.getNumElements()-1) ) { $next.addClass(options.disable); }
                
                // bind click functions to navigation arrows
                $prev.bind('click', function(e){
                    self.prev(e, $(this));
                    e.preventDefault();
                });
                
                $next.bind('click', function(e){
                    self.following(e, $(this));
                    e.preventDefault();
                });
                
                // remove previous search menu
                prevSearchContainer.remove();
                // update the text in the counter
                self.updateCounter();
            },
            
            elemWidth : function(){
                return currSearchContainer.width();
            },
            
            elemOwidth : function () {
                return $currentSearchElems.eq(self.getIndex()).outerWidth();
            },
                        
            totalSize : function() {
                return (self.elemWidth()+20) * $currentSearchElems.length;
            },
            
            getIndex : function() {
                return index;
            },
            
            getNumElements:function(){
                return $currentSearchElems.length;
            },
            
            following : function(e, $trigger) {
                if ( self.getIndex() < self.getNumElements()-1  ) {
                    index++;
                    self.updateCounter();
                    if ($prev.hasClass(options.disable)) { $prev.removeClass(options.disable); }
                    if (self.getIndex() == self.getNumElements()-1 ) { $trigger.addClass(options.disable); }
                    self.moveSlider(1);
                }
                return self; 
            },
            
            prev : function(e, $trigger){
                if (self.getIndex() > 0 ) {
                    index--;
                    self.updateCounter();
                    if ($next.hasClass(options.disable)) { $next.removeClass(options.disable); }
                    if ( self.getIndex() == 0) { $trigger.addClass(options.disable); }
                    self.moveSlider(-1);
                } 
                return self;
            },
            
            moveSlider : function(offset){                        
                $currentSearch.animate({
                    left :  ( ($currentSearch.position().left - initPos ) + (self.elemOwidth() * (-1*offset) )  ) + 'px' },
                    'fast'
                    );
                    if(window.console) { console.log( 'position:' + $currentSearch.position().left + 'px'); }
                return self;
            },
            
            updateCounter : function(){
                $hsCounter.text((self.getIndex() + 1) + ' / ' + self.getNumElements() );
            }
            
        });
        
        if ($prevSearch.length > 0 && $currentSearch.length > 0) {
            self.init();
        }
    }
        
    $.fn.historySlider = function(options) {
        // return existing instance
        var tmp = this.data("historyslider");
        if (tmp) { return tmp; }
        
        options = $.extend({}, settings, options);
        
        this.each(function(){
            tmp = new historyslider($(this), options);
            $(this).data("historyslider", tmp);
        });
        return this;
    }

});

// hide icons set names
// Enhance results list plugin
(function($){
    $.fn.enhanceResults = function(){
        return $(this).each(function() {
            
            // configuration for hover intent plugin
            var configHover = {
                // change here for slower of faster reaction. Value in miliseconds.
                interval: 500, 
                // callback function for mouse-enter and mouse-leave events
                // only applied to show and hide icons
                over : function(){
                    $(this).find('.record-icons-label').show();
                },
                out : function(){
                    $(this).find('.record-icons-label').hide();
                }
            };
            
            $(this).hoverClass();
            // hide all icon labels
            $(this).find('.record-icons-label').hide();
                
            // bind mouse enter and mouse leave functions to each li
            $(this).hoverIntent(configHover)
            .hover(function(){
                $(this).next().addClass('results-record-next');                
            }, function(){
                $(this).next().removeClass('results-record-next');
            });
        });
    }
})(jQuery);

// plugin to add CSS classes to external links and internal links but searches.
// this would tell the user what sort of link it is, external or another search
(jQuery) (function($){
    
    var settings = {
        linkExternal : "link-external",
        linkSearch : "link-search",
        linkRecord : "link-record"
    }
    
    function classlink($link, options){
        var self = this,
        windowURL = window.location,
        linkText = $link.text(),
        // this var complements parseUri function
        parseUrioptions = {
            strictMode: false,
            key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
            q:   {
                name:   "queryKey",
                parser: /(?:^|&)([^&=]*)=?([^&]*)/g
            },
            parser: {
                strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
                loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
            }
        };
        
        $.extend(self, {
            init : function(){

                var tmpLink = self.parseUri($link.attr('href'));
                
                if ( tmpLink.host == windowURL.host ) {
                    // if file is empty then it means it points at a record
                    if (tmpLink.file == "" ) {
                        // $link.addClass(options.linkRecord);
                        $link.attr("title", "View record: " + linkText);
                    // it is a search
                    } else if (tmpLink.file == "search.php") {
                        $link.addClass(options.linkSearch);
                        $link.attr("title", "Search for: " + linkText);
                    }
                } else {
                    $link.addClass(options.linkExternal);
                    $link.attr("title", "External website");
                };
            },
            
            // The original function comes from:
            // parseUri 1.2.2
            // (c) Steven Levithan <stevenlevithan.com>
            // MIT License
            //--------------------
            // It has been ported into jQuery by Gabriel 
            parseUri : function (windowURL) {
                var    o   = parseUrioptions,
                    m   = o.parser[o.strictMode ? "strict" : "loose"].exec(windowURL),
                    uri = {},
                    i   = 14;

                while (i--) uri[o.key[i]] = m[i] || "";

                uri[o.q.name] = {};
                uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
                    if ($1) uri[o.q.name][$1] = $2;
                });

                return uri;
            }
            
        });
        
        self.init();
    }
    
    $.fn.classLink = function(options){
        var tmp = this.data("classlink");
        if (tmp) { return tmp; }
        
        options = $.extend({}, settings, options);
        
        this.each(function(){
            tmp = new classlink($(this), options);
            $(this).data("classlink", tmp);
        });
        return this;
    }
});

// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
///  
/// This should be global, it seems I will use it several times
parseUri.options = {
    strictMode: true,
    key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
    q:   {
        name:   "queryKey",
        parser: /(?:^|&)([^&=]*)=?([^&]*)/g
    },
    parser: {
        strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
        loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
    }
}

function parseUri(windowURL) {
    var    o   = parseUri.options,
        m   = o.parser[o.strictMode ? "strict" : "loose"].exec(windowURL),
        uri = {},
        i   = 14;

    while (i--) uri[o.key[i]] = m[i] || "";

    uri[o.q.name] = {};
    uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
        if ($1) uri[o.q.name][$1] = $2;
    });

    return uri;
}
///---- parseURI parse ULR

///  Expand collapse next element plugin
/// Gabriel
(jQuery) (function(){
jQuery.fn.toggleNext = function (options) {
    var settings = {
        headingClass : "expand-heading",
        expandClass : "expand-content"
    };
    
    // extend options
    var options = $.extend(settings, options);
    
    return jQuery(this).each(function(){
        var headings = jQuery(this);
        var expandContent = headings.next();
        
        // hide all contents
        expandContent.hide();
        
        // add class to heading and insert arrow to show
        headings.addClass(options.headingClass)
            .append('<span class="arrow-status"> Show</span>')
            .wrapInner('<a class="toggler" href="#"></a>');
        
        expandContent.addClass(options.expandClass);
        
        headings.click(function(){        
            expandContent.toggle();    
            
            if (expandContent.is(':visible')) {
                jQuery(this).addClass('arrow-down').removeClass('arrow-up')
                .attr('aria-hidden',true)
                .find('.arrow-status').text(' Hide');                
            }
            else if (expandContent.is(':hidden')) {
                jQuery(this).removeClass('arrow-down').addClass('arrow-up')
                .attr('aria-hidden',false)
                .find('.arrow-status').text(' Show');
            }
            // stop default
            return false;
        });
    });
}
});

// Little plugin to put the Record Citation in a input field for copy functionality
// Gabriel A Hernandez C
// BUFVC - 14 February 2011  
(function($){
    $.fn.recordCitation = function(){
        return $(this).each(function(){
            $(this).css('position', 'relative');
            var $citationContainer = $(this).find('.query-enhancer-content');
            var $citation = $citationContainer.find('p');
            var citationText = $citation.text();
            var stylesClass = 'citation-input-field';
            // hide original citation
            $citation.hide();        
            
            // create textarea field to hold citation    
            $citationContainer.append('<div class="citation-input-container"><p class="citation-input-heading">Copy and paste citation in your document or email: <span id="close-citation"><a href="#" title="close citation window">x</a></span></p><textarea class="' + stylesClass + '"></textarea></div>');
            $inputField = $citationContainer.find('.' + stylesClass);

            // close citation when clicking the X in window corner
            $citationContainer.find('#close-citation').click(function(){
                titleTrigger.trigger('click');
                return false;
            });
            
            // bind click function to header
            var titleTrigger = $(this).find('h4').click(function(){
                $inputField
                .text(citationText)
                .select()
                .focus()
                .addClass(stylesClass)
                .bind('click', function(){
                    $(this).select();
                });
            });
            
            if(window.console) 
                { console.log(citationText); }
        });
    }
}) (jQuery);

// Plugin to capture clicks on external links and send events to analytics
// Gabriel Hernandez
// 13 June 2011
(function($){
    
    var settings = {
        patheLinkID : 'pathe-link', 
        movietoneLinkID : 'movietone-link', 
        nfoLinkID : 'nfo-link',
        events : { 
            filmClipLink : 'External Film Clip click',
            externalLink : 'External Link click'
        }
    }
    
    var trackexternallink = function($linktotrack, options) {
        
        // checks if this is a film clip link
        function isFilmClipLink(currentLinkID) {
            return ( currentLinkID === options.patheLinkID ) || ( currentLinkID === options.movietoneLinkID ) || ( currentLinkID === options.nfoLinkID ); 
        }
        
        // add the click event to analytics 
        function addtrackcode (Category, Action, Label){
            _gaq.push(['_trackEvent', Category, Action, Label]);
            // debug, delete at some point
            if (window.console) { console.log('Category: ' + Category + ' | Event: ' + Action + ' | Label: ' + Label); }
        }
        
        var Obj = {
            init : function(){
                var linkURL = parseUri($linktotrack.attr('href')),
                    currentLinkID = $linktotrack.attr('id'),
                    currentEvent;

                $linktotrack.click(function(e){
                    if (!window._gaq) { throw ('Analytics has NOT been set up'); }
                    
                    // there is two links for one resource, (i.e Pathe) in the record 
                    // one is direct to the film click and the other is to the website so let's track them separately
                    if (isFilmClipLink(currentLinkID)) {
                        currentEvent = options.events.filmClipLink;
                    } else {
                        currentEvent = options.events.externalLink;
                    }
                    addtrackcode('External Links', currentEvent, linkURL.host);
                });
            }
        }
        return Obj;
    }
    
    $.fn.trackExternalLink = function(options) {
        // return instance if already one
        var tmp = this.data('trackexternallink');
        if(tmp) { return tmp; }
        
        options = $.extend({}, settings, options);
        
        this.each(function(){
            var tmp = trackexternallink($(this), options);
            tmp.init();
            $(this).data("trackexternallink", tmp);
        });
        return this;
    }
}(jQuery));

//function to reset date ranges to earliest through to latest dates available
all_dates_eq = function(datetype){
	var start_length, end_length, max_option, min_option, debug, option_val, empty_string=false;
	if(!datetype || datetype == 'year'){
		start_length = $('#date_start option').length - 1;
		val1 = $('#date_start option:eq(0)').val();
		if(!val1 || val1 == '' || isNaN(val1)){
			val1 = $('#date_start option:eq(0)').text();
			empty_string = true;
		}
		val2 = $('#date_start option:eq(' + start_length + ')').val();
		if(val1 < val2) min_option = val1; else min_option = val2;
		if(empty_string) min_option = '';
		$('#date_start').val(min_option);
		empty_string = false;
		end_length = $('#date_end option').length - 1;
		val1 = $('#date_end option:eq(0)').val();
		if(!val1 || val1 == '' || isNaN(val1)){
			val1 = $('#date_end option:eq(0)').text();
			empty_string = true;
		}
		val2 = $('#date_end option:eq(' + end_length + ')').val();
		if(val1 > val2) max_option = val1; else max_option = val2;
		if(empty_string) max_option = '';
		$('#date_end').val(max_option);
	}
	else {
		//loop for day, month, year selects in both start and end
		for(var i = 0; i < 3; i++){
			start_length = $('select[name="' + datetype + '_start[' + i +']"] option').length - 1;
			val1 = $('select[name="' + datetype + '_start[' + i +']"] option:eq(0)').val();
			if(!val1 || val1 == '' || isNaN(val1)){
				val1 = $('select[name="' + datetype + '_start[' + i +']"] option:eq(0)').text();
				empty_string = true;
			}
			val2 = $('select[name="' + datetype + '_start[' + i +']"] option:eq(' + start_length + ')').val();
			if(val1 < val2) min_option = val1; else min_option = val2;
			if(empty_string) min_option = '';
			$('select[name="' + datetype + '_start[' + i +']"]').val(min_option);
			empty_string = false;
			end_length = $('select[name="' + datetype + '_end[' + i +']"] option').length - 1;
			val1 = $('select[name="' + datetype + '_end[' + i +']"] option:eq(0)').val();
			if(!val1 || val1 == '' || isNaN(val1)){
				val1 = $('select[name="' + datetype + '_end[' + i +']"] option:eq(0)').text();
				empty_string = true;
			}
			val2 = $('select[name="' + datetype + '_end[' + i +']"] option:eq(' + end_length + ')').val();
			if(val1 > val2) max_option = val1; else max_option = val2;
			if(empty_string) max_option = '';
			$('select[name="' + datetype + '_end[' + i +']"]').val(max_option);
			empty_string = false;
		}
	}
};

function popup_help(url)
    {
    if (!window.focus)
        return true;
    if (typeof(url) != 'string')
       url = url.href;
    w = window.open(url, 'help', 'width=400,height=400,dependent=yes,status=no,location=no,scrollbars=yes');
    w.focus();
    return false;
   }

// Show or hide a window
function show_hide(id, show)
    {
    if (document.getElementById)
        {
        obj = document.getElementById(id);
        if (typeof(show) == 'undefined')
            show = obj.style.display == "none";
        
        if (show)
            obj.style.display = "";
        else
            obj.style.display = "none";
        }
    }

function clear_prompt(field, prompt)
    {
    if (field.value == prompt)
        {
        field.value = '';
        field.style.color = 'black';
        }
    }

function set_prompt(field, prompt)
    {
    if (field.value == '')
        {
        field.value = prompt;
        field.style.color = 'gray';
        }
    }

// Toggle a saved search as active or not using AJAX
function update_active_saved_search(obj, key, post_url)
    {
    // prepare post data
    var data = "key=" + escape(encodeURI(key));
    
    // active checkbox
    if (obj.checked == true)
        data += "&set_active=1";
    else
        data += "&remove_active=1";
    // add flag
    data += "&ajax=1";

    var xmlHttp = getRequester();
    
    // callback code
    xmlHttp.onreadystatechange=function()
        {
        // state 4: request complete
        if(xmlHttp.readyState==4)
            {
            if (xmlHttp.status != 200)
                return;
            
            // special case, empty response, some sort of unknown error
            if (xmlHttp.responseText == '')
                {
                document.getElementById("status_message").className='info-message';
                document.getElementById("status_message").innerHTML = '';
                return;
                }
            
            // set message and message class
            document.getElementById("status_message").className='info-message';
            document.getElementById("status_message").innerHTML = xmlHttp.responseText;
            }
        }
    
    xmlHttp.open('POST', post_url, true);
    xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
    xmlHttp.send(data);
    }

// Updated the saved search day using AJAX
function update_search_day(day, post_url)
    {
    // prepare post data
    var data = "day=" + escape(encodeURI(day));
    data += "&set_day=1";
    // add flag
    data += "&ajax=1";

    var xmlHttp = getRequester();
    
    // callback code
    xmlHttp.onreadystatechange=function()
        {
        // state 4: request complete
        if(xmlHttp.readyState==4)
            {
            if (xmlHttp.status != 200)
                return;
            
            // special case, empty response, some sort of unknown error
            if (xmlHttp.responseText == '')
                {
                document.getElementById("status_message").className='info-message';
                document.getElementById("status_message").innerHTML = '';
                return;
                }
            
            // set message and message class
            document.getElementById("status_message").className='info-message';
            document.getElementById("status_message").innerHTML = xmlHttp.responseText;
            }
        }
    
    xmlHttp.open('POST', post_url, true);
    xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");
    xmlHttp.send(data);
    }

// Creates the XMLHttpRequest object (AJAX)
function getRequester()
    {
    var xmlHttp;
    // this should work for all browsers except IE6 and older IE
    try
        {
        // try to create XMLHttpRequest object
        xmlHttp = new XMLHttpRequest();
        }
    catch(e)
        {
        // assume IE6 or older
        var XmlHttpVersions = new Array("MSXML2.XMLHTTP.6.0",
                                        "MSXML2.XMLHTTP.5.0",
                                        "MSXML2.XMLHTTP.4.0",
                                        "MSXML2.XMLHTTP.3.0",
                                        "MSXML2.XMLHTTP",
                                        "Microsoft.XMLHTTP");
        // try every prog id until one works
        for (var i=0; i < XmlHttpVersions.length && !xmlHttp; i++)
            {
            try
                {
                // try to create XMLHttpRequest object
                xmlHttp = new ActiveXObject(XmlHttpVersions[i]);
                }
            catch (e) {}
            }
        }
    // return the created object or display an error message
    if (!xmlHttp)
        alert("Error creating the XMLHttpRequest object.");
    else
        return xmlHttp;
    }

/*
Copyright (c) 2007 Christian yates
christianyates.com
chris [at] christianyates [dot] com
Licensed under the MIT License: 
http://www.opensource.org/licenses/mit-license.php

Inspired by work of Ingo Schommer
http://chillu.com/2007/9/30/jquery-columnizelist-plugin
*/
(function($){
  $.fn.columnizeList = function(settings){
    settings = $.extend({
      cols: 3,
      constrainWidth: 0
    }, settings);
    // var type=this.getNodeType();
    this.each(function() {
      var container = $(this);
      if (container.length == 0) { return; }
      var prevColNum = 10000; // Start high to avoid appending to the wrong column
      var size = $('li',this).size();
      var percol = Math.ceil(size/settings.cols);
      var tag = container[0].tagName.toLowerCase();
      var classN = container[0].className;
      var colwidth = Math.floor($(container).width()/settings.cols);
      var maxheight = 0;
      // Prevent stomping on existing ids with pseudo-random string
      var rand = Math.floor(Math.random().toPrecision(6)*10e6);
      $('<ul id="container'+rand+'" class="'+classN+'"></ul>').css({width:$(container).width()+'px'}).insertBefore(container);
      $('li', container).each(function(i) {
        var currentColNum = Math.floor(i/percol);
        if(prevColNum != currentColNum) {
          if ($("#col" + rand + "-" + prevColNum).height() > maxheight) { maxheight = $("#col" + rand + "-" + prevColNum).height(); }
          $("#container"+rand).append('<li class="list-column-processed"><'+tag+' id="col'+rand+'-'+currentColNum+'"></'+tag+'></li>');
        }
        $(this).attr("value",i+1).appendTo("#col"+rand+'-'+currentColNum);
        prevColNum = currentColNum;
      });
      $("li.list-column-processed").css({
        'float':'left',
        'list-style':'none',
        'margin':0,
        'padding-right':10
      });
      if (settings.constrainWidth) {
        $(".list-column-processed").css({'width':colwidth + "px"});
      };
      $("#container"+rand).after('<div style="clear: both;"></div>');
      $("#container"+rand+" "+tag).height(maxheight);
      // Add CSS to columns
      container.remove();
    });
    return this;
  };
})(jQuery);

/**
* hoverIntent r6 // 2011.02.26 // jQuery 1.5.1+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
* 
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne brian(at)cherne(dot)net
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev])}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev])};var handleHover=function(e){var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t)}if(e.type=="mouseenter"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob)},cfg.timeout)}}};return this.bind('mouseenter',handleHover).bind('mouseleave',handleHover)}})(jQuery);


	
// All plugins starters ...
jQuery(document).ready(function($){
    var searchForm = $("#search");
    var classToAdd = 'hide-offscreen';
    // add class to hide label of search field
    $('#q').each(function(){
        var label = $('label[for='+$(this).attr('id')+']');
        label.addClass(classToAdd);
    });
    // Add text class to all input type=text fields
    var classToAdd = 'text';
    $('#search input[type=text]').each(function(){
        $(this).addClass(classToAdd);
    });
	
	// apply only to external links - bufvc open in a new window link policy == external link
    $('a').filter(function(){
        return $(this).attr('target') === '_blank' || $(this).attr('id') === 'movietone-link';
    }).trackExternalLink();

	$('.checkbox-list').columnizeList();

	//reveal elements for JavaScript use
	$('.js-only').show();
    $('#tabs-container').Tabs();
	//listener for all dates buttons
	$('.all-dates').click(function(){
		all_dates_eq(this.id);
		return false;
	});
        
    // mark unmark records
    $(".results").parent().markUnmark();
    $("#sidebar #mark-individual-record").markUnmark();
    $(".remove-selected-facet").removeFacet();
    $(".lock-selected-facet").lockFacet();
    $('#sidebar .tip-help').titleHelp();
    $('#sidebar .help-icon').hoverClass()
        .tiphelp( {position:['top', 'center'], tipClass : 'tip-text', offset:[-5, -50] });
    $('#sidebar .tip-lock')
        .text("")
        .titleHelp( {inner : '<span class="lock-icon"></span>', titleClass : "tip-lock" });
    $('#sidebar .lock-icon').hoverClass()
        .tiphelp( {position:['top', 'center'], tipClass : 'tip-text', offset:[-5, -50] });
    $('#sidebar .tip-warning')
        .text("")
        .titleHelp( {inner : '<span class="warning-icon"></span>' });
    $('#sidebar .warning-icon').hoverClass()
        .tiphelp( {position:['top', 'center'], tipClass : 'tip-text', offset:[-5, -50] });
    $('#previous-search-menu').historySlider();
    $('div.record a').not('dd.nos-extras a').classLink();
    $('#sidebar h4.toggle-next').toggleNext();
    $('#record-citation').recordCitation();
    $('.results > li').enhanceResults();
    $('.collections-list li:nth-child(odd)').css('clear','left');
    $('.collections-list li:nth-child(even)').css('margin-right','0px');
    $('.resort').change(function(){
        selectElement = $(this);
        var initialSelectedIndex = selectElement[0].selectedIndex;
        var dataUrl = selectElement.attr('data-url');
        elem = selectElement.find('option').eq(initialSelectedIndex);
        elem.attr('selected', 'selected');
        
        if( dataUrl.indexOf("?") >= 0 )
            dataUrl += '&';
        else
            dataUrl += '?';
        window.location = dataUrl + 'sort=' + elem.val();
    });
});
