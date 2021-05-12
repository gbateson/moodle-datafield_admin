(function() {
    var JS = {};

    JS.str = {};
    JS.plugin = "datafield_admin";

    JS.add_event_listener = function(elm, evt, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evt, fn, (useCapture || false));
        } else if (elm.attachEvent) {
            elm.attachEvent("on" + evt, fn);
        }
    };

    /**
     * Simulate a mouse event based on a corresponding touch event
     * @param {Object} event A touch event
     * @param {String} simulatedType The corresponding mouse event
     */
    JS.simulateMouseEvent = function(event, simulatedType){

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
    JS.touchstart = function (event) {
        var self = this;

        // Ignore the event if another widget is already being handled
        if (JS.touchHandled || !self._mouseCapture(event.originalEvent.changedTouches[0])) {
            return;
        }

        // Set the flag to prevent other widgets from inheriting the touch event
        JS.touchHandled = true;

        // Track movement to determine if interaction was a click
        self._touchMoved = false;

        // Simulate the mouseover event
        JS.simulateMouseEvent(event, 'mouseover');

        // Simulate the mousemove event
        JS.simulateMouseEvent(event, 'mousemove');

        // Simulate the mousedown event
        JS.simulateMouseEvent(event, 'mousedown');
    };

    /**
     * Handle the jQuery UI widget's touchmove events
     * @param {Object} event The document's touchmove event
     */
    JS.touchMove = function (event) {

        // Ignore event if not handled
        if (!JS.touchHandled) {
            return;
        }

        // Interaction was not a click
        this._touchMoved = true;

        // Simulate the mousemove event
        JS.simulateMouseEvent(event, 'mousemove');
    };

    /**
     * Handle the jQuery UI widget's touchend events
     * @param {Object} event The document's touchend event
     */
    JS.touchEnd = function (event) {

        // Ignore event if not handled
        if (!JS.touchHandled) {
            return;
        }

        // Simulate the mouseup event
        JS.simulateMouseEvent(event, 'mouseup');

        // Simulate the mouseout event
        JS.simulateMouseEvent(event, 'mouseout');

        // If the touch interaction did not move, it should trigger a click
        if (!this._touchMoved) {

            // Simulate the click event
            JS.simulateMouseEvent(event, 'click');
        }

        // Unset the flag to allow other widgets to inherit the touch event
        JS.touchHandled = false;
    };

    JS.touchHandled = null;;

    var fn = function(){
        if (window.require) {
            require(["jquery", "jqueryui", "core/str"], function($, JUI, STR) {

                // Setup browsers with touch support
                if ($.support.touch = ('ontouchend' in document)) {

                    var mouseProto = $.ui.mouse.prototype;
                    var _mouseInit = mouseProto._mouseInit;
                    var _mouseDestroy = mouseProto._mouseDestroy;

                    mouseProto._touchStart = JS.touchstart;
                    mouseProto._touchMove = JS.touchMove;
                    mouseProto._touchEnd = JS.touchEnd;

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

                JS.onclick_viewdescriptions = function(){
                    var counttext = 0;
                    var counthidden = 0;
                    var nonalphachars = new RegExp("[^a-z0-9]+", "g");
                    document.querySelectorAll("input[name^=desc]").forEach(function(elm){
                        var txt = "";
                        var classname = elm.name.replace(nonalphachars, "_");
                        if (elm.type == "text") {
                            elm.type = "hidden";
                            counthidden++;
                            var span = document.createElement("SPAN");
                            span.className = classname;
                            span.innerHTML = elm.value;
                            elm.parentNode.appendChild(span);
                        } else if (elm.type == "hidden") {
                            var span = elm.parentNode.querySelector("span." + classname);
                            if (span) {
                                span.parentNode.removeChild(span);
                            }
                            elm.type = "text";
                            counttext++;
                        }
                    });
                    if (counttext < counthidden) {
                        this.innerText = JS.str.editdescriptions;
                    } else {
                        this.innerText = JS.str.viewdescriptions;
                    }
                };

                JS.setup_strings = function() {
                    return new Promise(function(resolve, reject){
                        var strings = new Array(
                            {"key": "editdescriptions", "component": JS.plugin},
                            {"key": "viewdescriptions", "component": JS.plugin}
                        );
                        STR.get_strings(strings).done(function(s) {
                            var i = 0;
                            JS.str.editdescriptions = s[i++];
                            JS.str.viewdescriptions = s[i++];
                            resolve();
                        });
                    });
                };

                JS.setup_buttons = function() {

                    var h3 = document.querySelector("form.reorderfields");
                    while (h3 && h3.matches("h3") == false) {
                        h3 = h3.previousElementSibling;
                    }

                    if (h3) {
                        var name = "viewdescriptions";
                        var btn = document.createElement("BUTTON");
                        btn.className = "btn btn-secondary bg-light rounded " + name;
                        btn.setAttribute("type", "button");
                        btn.setAttribute("name", name);
                        btn.appendChild(document.createTextNode(JS.str[name]));

                        var div = document.createElement("DIV");
                        div.className ="singlebutton ml-4";
                        div.appendChild(btn);

                        h3.appendChild(div);

                        JS.add_event_listener(btn, "click", JS["onclick_" + name]);
                        btn.dispatchEvent(new Event("click"))
                    }
                };

                JS.setup_nav_links = function(){
                    document.querySelectorAll("a.nav-link[href*='/mod/data/field.php']").forEach(function(elm){
                        elm.classList.add("active");
                    });
                };

                JS.setup_admin_menus = function(){
                    document.querySelectorAll("select[name^='admin']").forEach(function(elm){
                         JS.add_event_listener(elm, "change", function(){
                            if (this.options[this.selectedIndex].value == "1") {
                                this.classList.add("admin");
                            } else {
                                this.classList.remove("admin");
                            }
                         }, false);
                         elm.dispatchEvent(new Event("change"));
                    });
                };

                JS.setup_row_hover = function(){
                    document.querySelectorAll(".fieldlist li").forEach(function(elm){
                        JS.add_event_listener(elm, "mouseover", function(){
                            this.classList.add("hover");
                        });
                        JS.add_event_listener(elm, "mouseout", function(){
                            this.classList.remove("hover");
                        });
                    });
                };

                JS.setup_row_drag = function(){
                    $(".fieldlist").sortable({
                        "axis": "y",
                        "containment": $(".fieldlist"),
                        "opacity" : 0.6,
                        "update"  : function(){
                            $(this).find("li").each(function(i){
                                $(this).find("input[type=text][name^=sort]").attr("value", i + 1);
                            });
                        }
                    });
                };

                JS.setup_bootstrap_v3 = function() {
                    var bootstrap_v3 = false;
                    for (var s in document.styleSheets) {
                        for (var r in document.styleSheets[s].rules) {
                            var txt = document.styleSheets[s].rules[r].selectorText;
                            if (txt && txt.indexOf("dl-horizontal") >= 0) {
                                bootstrap_v3 = true;
                                break;
                            }
                        }
                        if (bootstrap_v3) {
                            break;
                        }
                    }
                    if (bootstrap_v3) {
                        // Bootstrap version 3.x styles available in Moodle <= 3.6
                        // see "/theme/bootstrapbase/style/moodle.css"
                        document.querySelectorAll(".singlebutton").forEach(function(elm){
                            elm.classList.add("d-inline");
                            elm.querySelectorAll("button").forEach(function(btn){
                                btn.style.padding = "0.375em 0.75em";
                                btn.style.fontSize = "0.6em";
                            });
                        });
                        document.querySelectorAll("dl.row").forEach(function(elm){
                            elm.className = "dl-horizontal " + elm.className;
                            elm.classList.add("m-0"); // override ".row" margins.
                            elm.querySelector("dt").style.textAlign = "left";
                        });

                        document.querySelectorAll("select, input[type=text]").forEach(function(elm){
                            elm.style.marginBottom = "initial";
                        });
                        document.querySelectorAll(".py-1").forEach(function(elm){
                            elm.classList.remove("py-1");
                            elm.classList.add("pt-1");
                            if (elm.matches("dd:last-child")) {
                                elm.classList.add("pb-1");
                            }
                        });
                        document.querySelectorAll("ol.fieldlist").forEach(function(elm){
                            elm.classList.add("pb-2");
                        });
                    }
                };

                JS.setup = function() {
                    var p = JS.setup_strings();
                    p.then(JS.setup_buttons);
                    p.then(JS.setup_nav_links);
                    p.then(JS.setup_admin_menus);
                    p.then(JS.setup_bootstrap_v3)
                    p.then(JS.setup_row_hover);
                    p.then(JS.setup_row_drag);
                };

                JS.setup();
            });
        }
    };

    JS.add_event_listener(window, "load", fn, false);
}());
