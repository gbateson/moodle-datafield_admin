(function() {
    var TOOL = {};

    TOOL.str = {};
    TOOL.plugin = "datafield_admin";

    TOOL.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    }

    /**
     * Simulate a mouse event based on a corresponding touch event
     * @param {Object} event A touch event
     * @param {String} simulatedType The corresponding mouse event
     */
    TOOL.simulateMouseEvent = function(event, simulatedType){

        // Ignore multi-touch events
        if (event.originalEvent.touches.length > 1) {
            return;
        }

        event.preventDefault();

        var touch = event.originalEvent.changedTouches[0];
        var simulatedEvent = document.createEvent('MouseEvents');

        // Initialize the simulated mouse event using the touch event's coordinates
        simulatedEvent.initMouseEvent(
            simulatedType,    // type
            true,             // bubbles                                        
            true,             // cancelable                                 
            window,           // view                                             
            1,                // detail                                         
            touch.screenX,    // screenX                                        
            touch.screenY,    // screenY                                        
            touch.clientX,    // clientX                                        
            touch.clientY,    // clientY                                        
            false,            // ctrlKey                                        
            false,            // altKey                                         
            false,            // shiftKey                                     
            false,            // metaKey                                        
            0,                // button                                         
            null              // relatedTarget                            
        );

        // Dispatch the simulated event to the target element
        event.target.dispatchEvent(simulatedEvent);
    };

    /**
     * Handle the jQuery UI widget's touchstart events
     * @param {Object} event The widget element's touchstart event
     */
    TOOL.touchstart = function (event) {
        var self = this;

        // Ignore the event if another widget is already being handled
        if (TOOL.touchHandled || !self._mouseCapture(event.originalEvent.changedTouches[0])) {
            return;
        }

        // Set the flag to prevent other widgets from inheriting the touch event
        TOOL.touchHandled = true;

        // Track movement to determine if interaction was a click
        self._touchMoved = false;

        // Simulate the mouseover event
        TOOL.simulateMouseEvent(event, 'mouseover');

        // Simulate the mousemove event
        TOOL.simulateMouseEvent(event, 'mousemove');

        // Simulate the mousedown event
        TOOL.simulateMouseEvent(event, 'mousedown');
    };

    /**
     * Handle the jQuery UI widget's touchmove events
     * @param {Object} event The document's touchmove event
     */
    TOOL.touchMove = function (event) {

        // Ignore event if not handled
        if (!TOOL.touchHandled) {
            return;
        }

        // Interaction was not a click
        this._touchMoved = true;

        // Simulate the mousemove event
        TOOL.simulateMouseEvent(event, 'mousemove');
    };

    /**
     * Handle the jQuery UI widget's touchend events
     * @param {Object} event The document's touchend event
     */
    TOOL.touchEnd = function (event) {

        // Ignore event if not handled
        if (!TOOL.touchHandled) {
            return;
        }

        // Simulate the mouseup event
        TOOL.simulateMouseEvent(event, 'mouseup');

        // Simulate the mouseout event
        TOOL.simulateMouseEvent(event, 'mouseout');

        // If the touch interaction did not move, it should trigger a click
        if (!this._touchMoved) {

            // Simulate the click event
            TOOL.simulateMouseEvent(event, 'click');
        }

        // Unset the flag to allow other widgets to inherit the touch event
        TOOL.touchHandled = false;
    };

    TOOL.touchHandled = null;;

    var fn = function(){
        if (window.require) {
            require(["jquery", "jqueryui", "core/str"], function($, JUI, STR) {

                // Setup browsers with touch support
                if ($.support.touch = ('ontouchend' in document)) {

                    var mouseProto = $.ui.mouse.prototype;
                    var _mouseInit = mouseProto._mouseInit;
                    var _mouseDestroy = mouseProto._mouseDestroy;

                    mouseProto._touchStart = TOOL.touchstart;
                    mouseProto._touchMove = TOOL.touchMove;
                    mouseProto._touchEnd = TOOL.touchEnd;

                    /**
                     * Overload the $.ui.mouse _mouseInit method to support touch events.
                     * This method extends the widget with bound touch event handlers that
                     * translate touch events to mouse events and pass them to the widget's
                     * original mouse event handling methods.
                     */
                    mouseProto._mouseInit = function () {
    
                        var self = this;

                        // Delegate the touch handlers to the widget's element
                        self.element.bind({
                            touchstart: $.proxy(self, '_touchStart'),
                            touchmove: $.proxy(self, '_touchMove'),
                            touchend: $.proxy(self, '_touchEnd')
                        });

                        // Call the original $.ui.mouse init method
                        _mouseInit.call(self);
                    };

                    /**
                     * Remove the touch event handlers
                     */
                    mouseProto._mouseDestroy = function () {
    
                        var self = this;

                        // Delegate the touch handlers to the widget's element
                        self.element.unbind({
                            touchstart: $.proxy(self, '_touchStart'),
                            touchmove: $.proxy(self, '_touchMove'),
                            touchend: $.proxy(self, '_touchEnd')
                        });

                        // Call the original $.ui.mouse destroy method
                        _mouseDestroy.call(self);
                    };
                }


                TOOL.setup_strings = function() {
                    return new Promise(function(resolve, reject){
                        var strings = new Array(
                            {"key": "copiedhtml", "component": TOOL.plugin}
                        );
                        STR.get_strings(strings).done(function(s) {
                            var i = 0;
                            TOOL.str.copiedhtml = s[i++];
                            resolve();
                        });
                    });
                };

                TOOL.setup_nav_links = function(){
                    document.querySelectorAll("a.nav-link[href*='/mod/data/field.php']").forEach(function(elm){
                        elm.classList.add("active");
                    });
                };

                TOOL.setup_admin_menus = function(){
                    document.querySelectorAll("select[name^='admin']").forEach(function(elm){
                         TOOL.add_event_listener(elm, "change", function(){
                            if (this.options[this.selectedIndex].value == "1") {
                                this.classList.add("admin");
                            } else {
                                this.classList.remove("admin");
                            }
                         }, false);
                         elm.dispatchEvent(new Event("change"));
                    });
                };

                TOOL.setup_row_hover = function(){
                    document.querySelectorAll(".fieldlist li").forEach(function(elm){
                        TOOL.add_event_listener(elm, "mouseover", function(){
                            this.classList.add("hover");
                        });
                        TOOL.add_event_listener(elm, "mouseout", function(){
                            this.classList.remove("hover");
                        });
                    });
                };

                TOOL.setup_row_drag = function(){
                    $(".fieldlist").sortable({
                        "axis": "y",
                        "containment": $(".fieldlist"),
                        "opacity" : 0.6,
                        "update"  : function(){
                            $(this).find("li").each(function(i){
                                $(this).find("input[type=text]").attr("value", i + 1);
                            });
                        }
                    });
                };

                TOOL.setup = function() {
                    var p = TOOL.setup_strings();
                    p.then(TOOL.setup_nav_links);
                    p.then(TOOL.setup_admin_menus);
                    p.then(TOOL.setup_row_hover);
                    p.then(TOOL.setup_row_drag);
                };

                TOOL.setup();
            });
        }
    };

    TOOL.add_event_listener(window, "load", fn, false);
}());
