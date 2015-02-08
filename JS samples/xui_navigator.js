/**
 * This history navigator is a file to manage our application's
 * navigation. It loads all content via ajax, and only uses two 
 * divs, so it doesn't clutter up the DOM. It allows you to go
 * backward in the navigation pattern as well as save the state
 * of the application and allow you to bookmark it
 * 
 * @TODO allow the bookmarking of the application
  *      08/16/2011 refactored code to be OOP
 */
var xui_history_navigator = {
    this_history        : new Array(),
    foreground          : null,
    background          : null,
    screen_width        : 0,
    screen_height       : 0,
    default_url         : '',
    last_scroll_pos     : 0,

    /**
     * initializes our history navigator and gets everything set
     * to enable our application
     * 
     * @returns void
     */
    init: function(this_default_url)
    {
        // subscribe to our global go_to_schedule_page event
        go_to_schedule_page.subscribe(this.handle_go_to_schedule_page);
        
        // subscribe to our global updated dom event
        updated_dom_event.subscribe(this.bind_events);

        // listen for an orientation change
        x$(window).on('orientationchange', function(event){
            xui_history_navigator.set_nav_sizes(event);
        });
        
        this.default_url = this_default_url;
        
        // set the height of the body to the height of the content that's being
        // placed in the foreground
        this.reset_height();
        
        // variables that will hold our content
        this.foreground = x$('div[data-role="nav1"]');
        this.background = x$('div[data-role="nav2"]');

        // set the initial size of our navigation holders
        this.set_nav_sizes();
        
        // load our first page (home/x)
        this.move_forward({url:'',scroll_to:0});
    },

    /**
     * changes all of our links from normal "page" links to "xhr" links
     * 
     * binds events to our navigation links
     * this should be called after every ajax call because XUI
     * doesn't provide a live bind like jQuery
     * 
     *      08/18/2011 made the function automatically create xhr links
     *  from page links
     * @returns void
     */
    bind_events: function()
    {
        // change all our normal links to xhr links
        x$('a').each(function(element){
            if ('' != x$(element).attr('href') && !x$(element).hasClass('xhr-ignore')){
                // move our href to our data-href attribute
                x$(element).attr('data-href', x$(element).attr('href'));
                
                // remove our href attribute alltogether because the iPhone
                // will always display a quick address bar with it there
                element.removeAttribute('href');
                
                var target = String(x$(element).attr('target'));
                
                switch (target){
                    case '_blank':
                        x$(element).attr('data-role', '_blank');
                        break;
                        
                    case '_self':
                        x$(element).attr('data-role', '_self');
                        break;
                        
                    default:
                        if (String(x$(element).attr('data-href')).search('page') < 0){
                            x$(element).attr('data-href', String(x$(element).attr('data-href')) + '/xhr');
                        } else {
                            x$(element).attr('data-href', String(x$(element).attr('data-href')).replace('page/', 'xhr/'));
                        }
                        x$(element).attr('data-role', 'xhr');
                        break;
                }
            }
        });
        
        // change our back links to xhr back links
        x$('a.lnk-back').each(function(element){
            element.removeAttribute('href');
            element.removeAttribute('onclick');
            x$(element).attr('data-role', 'back');
        });
        
        // attach to all our click events
        x$('a[data-role=""]').un('click').on('click', function(event){
            xui_history_navigator.handle_click(event);
        });
        x$('a[data-role="_blank"]').un('click').on('click', function(event){
            xui_history_navigator.handle_external_click(event);
        });
        x$('a[data-role="_self"]').un('click').on('click', function(event){
            xui_history_navigator.handle_click(event);
        });
        x$('a[data-role="xhr"]').un('click').on('click', function(event){
            xui_history_navigator.handle_xhr_click(event);
        });
        x$('a[data-role="back"]').un('click').on('click', function(event){
            xui_history_navigator.handle_back_click(event);
        });

        // need to fire a google analytics event
        xui_history_navigator.track_google();
    },
    
    /**
     * fires google analytics tracking
     * 
     */
    track_google: function()
    {
        //_gaq.push(['_trackPageview']);
        var full_url = String(xui_history_navigator.this_history[xui_history_navigator.this_history.length-1]);
        var full_url_array = full_url.split('/');
        //console.log(full_url_array);
        
        var page_url = '';
        if (full_url_array[0] == 'http:'){
        	for (var i=3;i<full_url_array.length;i++){
        		page_url += '/' + full_url_array[i];
        	}
        } else {
        	for (var i=0;i<full_url_array.length;i++){
        		page_url += '/' + full_url_array[i];
        	}
        }
        //console.log(page_url);
        _gaq.push(['_trackPageview'], page_url);
    },
    
    /**
     * listens to the go_to_schedule_page event and then redirects the
     * user to the schedule page
     * 
     * @return void
     */
    handle_go_to_schedule_page: function(e)
    {
        xui_history_navigator.move_forward({url:'listschedules/xhr',scroll_to:0});
    },
    
    /**
     * sets the size of our content holders to match the
     * height and width of the screen our application is
     * being viewed on
     * 
     * @returns void
     */
    set_nav_sizes: function ()
    {
        // figure out the screen height and width
        // depending on our orientation
        switch(window.orientation){
            default:
            case 0:
                this.screen_width = window.screen.width;
                this.screen_height = window.screen.height;
    
                var this_browser = new Browser();
                // if we're on a desktop browser, get the height and width
                // differently
                if (false == this_browser._is_mobile){
                    this.screen_width = document.body.offsetWidth;
                    this.screen_height = document.body.offsetHeight;
                }
    
                if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                    this.screen_width = 320;
                    this.screen_height = 569;
                }
                break;
            case -90:
            case 90:
                this.screen_width = window.screen.height;
                this.screen_height = window.screen.width;
                
                // if this is an android platform, we need to add an additional
                // 60 pixels when it goes horizontal because the address bar
                // disappears
                var this_browser = new Browser();
                if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                    this.screen_width = 569;
                    this.screen_height = 320;
                }
    
                // with the blackbery, it incorrectly reports the height and the
                // width and we need to swap them
                if (this_browser._platform == this_browser.PLATFORM_BLACKBERRY){
                    this.screen_width = window.screen.width;
                    this.screen_height = window.screen.height;
                }
                break;
        }
        //x$('#log').html('set nav size - width: '+screen_width+' height: '+screen_height+' orientation: '+window.orientation+' w: '+window.screen.width+' h: '+window.screen.height+' iw: '+window.innerWidth);
        //alert('set nav size - width: '+screen_width+' height: '+screen_height+' orientation: '+window.orientation);
    
        // scroll back up to the top
        setTimeout(function(){ window.scrollTo(0, 1); }, 0);
    
        // turn all animations off
        this.manage_animation(this.foreground, 'off');
        this.manage_animation(this.background, 'off');
        this.foreground.css({
            'min-height': this.screen_height + 'px',
            'width': this.screen_width + 'px',
            'left': '0px',
        });
        this.background.css({
            'min-height': this.screen_height + 'px',
            'width': this.screen_width + 'px',
            'left': this.screen_width + 'px',
        });
        x$('#wrapper').css({
            'width': this.screen_width + 'px',
            'min-height': this.screen_height + 'px'
        });
        x$('#overlay').css({
            'height': '100%',
            'width': '100%',
        });
        x$('#share-holder').css({
            'width': this.screen_width + 'px'
        });
        setTimeout(function(){ // pause to let our css changes take effect
            xui_history_navigator.manage_animation(xui_history_navigator.foreground, 'on');
            xui_history_navigator.manage_animation(xui_history_navigator.background, 'on');
        }, 100);
    },
    
    /**
     * handles the click on a default link
     * 
     */
    handle_click: function (e)
    {
        e.preventDefault();
        var url = x$(e.target).attr('data-href');
        window.location = url;
        return false;
    },
    
    /**
     * opens up link in an external window
     * 
     */
    handle_external_click: function (e)
    {
        e.preventDefault();
        var url = x$(e.target).attr('data-href');
        window.open(url);
        return false;
    },

    /**
     * handles what happens when a navigation link is clicked 
     * 
     * @param e the click event
     * @returns boolean
     */
    handle_xhr_click: function (e)
    {
        e.preventDefault();
        
        // get our url
        var url = x$(e.target).attr('data-href');
        if ('IMG' == e.target.nodeName
                || 'SPAN' == e.target.nodeName){
            url = x$(e.target.parentNode).attr('data-href');
        }
        this.move_forward({url:url,scroll_to:0});
        return false;
    },

    /**
     * Handles what happens when a link with data-role="back" is clicked
     * 
     * @returns boolean
     */
    handle_back_click: function(e)
    {
        e.preventDefault();
        // if there is no history, then we can't go back
        if (this.this_history.length <= 1) return false;
        
        // first thing to do is move our background holder all the way to the left
        // so we can do a reverse animation
        this.animate_background_to_left();
        
        // reset our height and scroll up to the top
        this.reset_height();
        
        setTimeout(function(){
            xui_history_navigator.move_back();
        }, 100);
        return false;
    },

    /**
     * this is the method that makes our xhr request
     * and then fires off the remaining methods to visually
     * handle the navigation
     * 
     */
    move_forward: function (url_object, historyObject)
    {
        var url = url_object.url;
        var scroll_to = url_object.scroll_to;
        this.last_scroll_pos = this.f_scroll_top();
        //console.log('scroll position: ' + this.last_scroll_pos);
        
        if (!url){
            url = this.default_url;
        }
        
        if (!historyObject || !historyObject.calledFromHistory){
            //dsHistory.setQueryVar('url', url);
            //dsHistory.bindQueryVars(load_page, this, url);
            dsHistory.addFunction(this.move_forward, this, {url:url,scroll_to:this.f_scroll_top()});
        }
        
        // add our url to our history
        this.this_history.push(url);
        
        this.show_overlay();
        
        // make our xhr
        this.background.xhr(url, function(){
            //alert(this.responseText)
            
            // put our contents in the background holder
            xui_history_navigator.background.html('inner', this.responseText);
            
            // reset our height and scroll up to the top
            xui_history_navigator.reset_height();

            // switch our holders
            xui_history_navigator.switch_holders();
            
            // move our background to the back
            xui_history_navigator.background.css({ 'z-index': '1'});
            xui_history_navigator.foreground.css({ 'z-index': '100'});

            setTimeout(function(){
                // move our foreground over to the front
                xui_history_navigator.animate_foreground_to_front_from_right()
                
                // after the transform is done, move the background over
                // to where it needs to be
                setTimeout(function(){
                    xui_history_navigator.hide_overlay();
                    
                    xui_history_navigator.animate_background_to_back()

                }, 600);
            }, 500);
        });
    },

    /**
     * This method contains the logic to actually move backward
     * in our ajax history
     * 
     * @returns void
     */
    move_back: function ()
    {
        setTimeout(function(){ window.scrollTo(0, 1); }, 10);
        
        // switch our holders
        this.switch_holders();
        
        this.background.css({'z-index':'1'});
        this.foreground.css({'z-index':'100'});

        // if there are only two items in the history, then we don't
        // have to do anything because our content already exists
        // or else get our content from three URLs ago to load into our background
        // so we have it waiting for another back button click
        var xhr_url = (this.this_history.length > 2) ? this.this_history[this.this_history.length - 3] : '';
        
        if (xhr_url != ''){

            setTimeout(function(){ // need a slight pause to allow css changes to take effect
                xui_history_navigator.animate_foreground_to_front_from_left()
                
                setTimeout(function(){ // pause to allow the transition to complete
                    xui_history_navigator.animate_background_to_back()
                    
                    //xui_history_navigator.show_overlay();
                    
                    // load that content into our new background
                    // so it can be ready if we do another back
                    xui_history_navigator.background.xhr(xhr_url, function(){
                        
                        // put our contents in the background holder
                        xui_history_navigator.background.html('inner', this.responseText);
                        
                        //xui_history_navigator.hide_overlay();
                    });
                    
                    if (xui_history_navigator.last_scroll_pos > 1){
                        setTimeout(function(){ window.scrollTo(0, xui_history_navigator.last_scroll_pos); }, 10);
                    } else {
                        setTimeout(function(){ window.scrollTo(0, 1); }, 10);
                    }
                }, 600);
            }, 100);
            
        } else {
            
            setTimeout(function(){ // need a slight pause to allow css changes to take effect
                xui_history_navigator.animate_foreground_to_front_from_left()
                
                setTimeout(function(){ // pause to allow the transition to complete
                    xui_history_navigator.animate_background_to_back()
                    xui_history_navigator.hide_overlay();
                    
                    if (xui_history_navigator.last_scroll_pos > 1){
                        setTimeout(function(){ window.scrollTo(0, xui_history_navigator.last_scroll_pos); }, 10);
                    } else {
                        setTimeout(function(){ window.scrollTo(0, 1); }, 10);
                    }
                }, 600);
            }, 100);

        }
        // remove the last element from our history
        this.this_history.pop();
    },

    /**
     * This method switches our content holders so that the foreground
     * always contains the latest content.
     * 
     * @returns void
     */
    switch_holders: function ()
    {
        // switch our holders
        if (this.background.attr('data-role') == 'nav2'){
            this.background = x$('div[data-role="nav1"]');
            this.foreground = x$('div[data-role="nav2"]');
        } else {
            this.background = x$('div[data-role="nav2"]');
            this.foreground = x$('div[data-role="nav1"]');
        }
    },

    /**
     * This method turns our hardware accelerated animations on and off
     * for a given element.
     * 
     * @param element the HTML element to animate
     * @param on_or_off string a flag 'on' or 'off'
     * @returns void
     */
    manage_animation: function (element, on_or_off)
    {
        switch (on_or_off){
            default:
            case 'on':

                var this_browser = new Browser();
                if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                    element.css({
                        '-webkit-transform':'translate(0px,0px)',
                        '-webkit-timing-animation-function':'linear',
                        '-webkit-transition-property':'transform',
                        '-webkit-transition-duration':'.5s',
                    });
                } else if (this_browser._platform == this_browser.PLATFORM_BLACKBERRY){
                    // do nothing
                } else {
                    element.css({
                        '-webkit-backface-visibility':'hidden',
                        '-webkit-perspective':'1000',
                        '-webkit-transform':'translate3d(0px,0px,0px)',
                        '-webkit-timing-animation-function':'linear',
                        '-webkit-transition-property':'transform3d',
                        '-webkit-transition-duration':'.5s',
                        '-moz-transition-property':'-moz-transform',
                        '-moz-transition-duration':'.5s',
                        '-o-transition-property':'-moz-transform',
                        '-o-transition-duration':'.5s',
                        '-ms-transition-property':'-moz-transform',
                        '-ms-transition-duration':'.5s'
                    });
                }
                break;
                
            case 'off':
                var this_browser = new Browser();
                if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                    element.css({
                        '-webkit-transition-duration':'0s',
                        '-webkit-transform':'translate(0px,0px)'
                    });
                } else if (this_browser._platform == this_browser.PLATFORM_BLACKBERRY){
                    // do nothing
                } else {
                    element.css({
                        '-webkit-transition-duration':'0s',
                        '-webkit-transform':'translate3d(0px,0px,0px)',
                        '-moz-transition-duration':'0s',
                        '-moz-transform':'translate(0px,0px)',
                        '-o-transition-duration':'0s',
                        '-o-transform':'translate(0px,0px)',
                        '-ms-transition-duration':'0s',
                        '-ms-transform':'translate(0px,0px)'
                    });
                }
                break;
        }
    },

    /**
     * moves the foreground to the front from the right side
     * 
     * @returns void
     */
    animate_foreground_to_front_from_right: function ()
    {
        this.manage_animation(this.foreground, 'on');
        setTimeout(function(){
            var this_browser = new Browser();
            if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                xui_history_navigator.foreground.css({
                    '-webkit-transform':'translate(-'+xui_history_navigator.screen_width+'px,0px)'
                });
            } else if (this_browser._platform == this_browser.PLATFORM_BLACKBERRY){
                xui_history_navigator.foreground.css({
                    'left':'0px'
                });
            } else {
                xui_history_navigator.foreground.css({
                    '-webkit-transform':'translate3d(-'+xui_history_navigator.screen_width+'px,0px,0px)',
                    '-moz-transform':'translate(-'+xui_history_navigator.screen_width+'px,0px)',
                    '-o-transform':'translate(-'+xui_history_navigator.screen_width+'px,0px)',
                    '-ms-transform':'translate(-'+xui_history_navigator.screen_width+'px,0px)'
                });
            }
            setTimeout(function(){ window.scrollTo(0, 1); }, 10);
            setTimeout(function(){
                // rebind all our events because XUI doesn't have a live() bind like jquery
                updated_dom_event.fire();
            }, 600);
        }, 100);
    },
    
    /**
     * moves the foreground to the front from the left side
     * 
     * @returns void
     */
    animate_foreground_to_front_from_left: function ()
    {
        this.manage_animation(this.foreground, 'on');
        setTimeout(function(){
            var this_browser = new Browser();
            if (this_browser._platform == this_browser.PLATFORM_ANDROID){
                xui_history_navigator.foreground.css({
                    '-webkit-transform':'translate('+xui_history_navigator.screen_width+'px,0px)'
                });
            } else if (this_browser._platform == this_browser.PLATFORM_BLACKBERRY){
                xui_history_navigator.foreground.css({
                    'left':'0px'
                });
            } else {
                xui_history_navigator.foreground.css({
                    '-webkit-transform':'translate3d('+xui_history_navigator.screen_width+'px,0px,0px)',
                    '-moz-transform':'translate('+xui_history_navigator.screen_width+'px,0px)',
                    '-o-transform':'translate('+xui_history_navigator.screen_width+'px,0px)',
                    '-ms-transform':'translate('+xui_history_navigator.screen_width+'px,0px)'
                });
            }
            
            setTimeout(function(){ window.scrollTo(0, 1); }, 10);
            setTimeout(function(){
                // rebind all our events because XUI doesn't have a live() bind like jquery
                updated_dom_event.fire();
            }, 600);
        }, 100);
    },

    /**
     * moves the background to the background and off the screen to the right
     * 
     * @returns void
     */
    animate_background_to_back: function ()
    {
        this.manage_animation(this.background, 'off');
        this.background.css({'left': this.screen_width+'px', 'z-index':'1'});
    },

    /**
     * moves the background off the screen to the left
     * 
     * @returns void
     */
    animate_background_to_left: function ()
    {
        this.manage_animation(this.background, 'off');
        this.background.css({'left':'-'+this.screen_width+'px', 'z-index':'1'});
    },

    /**
     * show the modal overlay
     * 
     */
    show_overlay: function ()
    {
        x$('#overlay').removeClass('hide').addClass('show');
        x$('.loading-holder').css({'top':this.f_scroll_top + 100 + 'px'});
    },
    
    /**
     * hide the modal overlay
     * 
     */
    hide_overlay: function()
    {
        x$('#overlay').removeClass('show').addClass('hide');
    },


    /**
     * This method resets the height of our document body based
     * on the content that was just loaded
     * 
     * @returns void
     */
    reset_height: function ()
    {
        if (this.background){
            // get the height of our foreground
            this.background.each(function(element){
                var content_height = element.offsetHeight;
                x$(document.body).setStyle('height', content_height + 'px');
                x$('#overlay').setStyle('height', content_height + 'px');
                
                x$('#everything-wrapper').setStyle('height', content_height + 'px');
                // console.log('content height: '+content_height+' document height: ' + document.body.offsetHeight);
            });
        }
    },
    
    /**
     *
     *
     */
    load_splash: function ()
    {
        this.hide_overlay();
        
        // only do this if the page is home
        image1 = new Image();
        image1.src = "/assets/images/mobi-splash.jpg";
        var window_height = x$('#wrapper').getStyle('height');
        var window_width = x$('#wrapper').getStyle('width');
        var back_size = 'auto '+window_height;
        x$('#splash-screen').css({backgroundSize:back_size,width:window_width,height:window_height});
        x$('#everything-wrapper').css({opacity:'0'});
        x$('#splash-screen').css({display:'block'});
        x$('#splash-screen').tween({opacity: '1',duration:700},function(){
            x$('#everything-wrapper').tween({duration:1000},function(){
                x$('#splash-screen').tween({opacity: '0',duration:700},function(){
                    x$('#splash-screen').css({display: 'none'});
                });
                x$('#everything-wrapper').tween({opacity:'1',duration:1000}); 
            });
        });
    },
    /**
     * gets the current scroll position of the browser
     * @returns integer
     */
    f_scroll_top: function()
    {
        return this.f_filter_results (
            window.pageYOffset ? window.pageYOffset : 0,
            document.documentElement ? document.documentElement.scrollTop : 0,
            document.body ? document.body.scrollTop : 0
        );
    },
    /**
     * used with the above function to filter the proper results
     * @param n_win
     * @param n_docel
     * @param n_body
     * @returns integer
     */
    f_filter_results: function(n_win, n_docel, n_body)
    {
        var n_result = n_win ? n_win : 0;
        if (n_docel && (!n_result || (n_result > n_docel)))
            n_result = n_docel;
        return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
    }
};
