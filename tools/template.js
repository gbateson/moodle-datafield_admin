(function() {
    var JS = {};

    JS.wwwroot = window.location.href.replace(new RegExp("/mod/data/.*"), "");
    JS.ajaxurl = JS.wwwroot + "/mod/data/field/report/field.ajax.php"

    JS.audiourl = new RegExp("\\w+\\.(aac|flac|mp3|m4a|oga|ogg|wav)\\b", "i");
    JS.imageurl = new RegExp("\\w+\\.(gif|jpe|jpeg|jpg|png|svg|svgz)\\b", "i");
    JS.videourl = new RegExp("\\w+\\.(mov|mp4|m4v|ogv|webm)\\b", "i");
    JS.htmlfragment = new RegExp("^<(\\w+)\\b[^>]*>.*</\\1>$");

    JS.audio_max_height = "64px";
    JS.audio_max_width = "480px";

    JS.image_max_height = "640px";
    JS.image_max_width = "640px";

    JS.video_max_height = "360px";
    JS.video_max_width = "640px";

    /**
     * Adds an event listener to an element.
     *
     * @param {HTMLElement} obj The element to which the event listener is attached.
     * @param {string} evt The event type (e.g., "click", "load").
     * @param {Function} fn The callback function to execute when the event occurs.
     * @param {boolean} [useCapture=false] Whether to use capture phase (default: false).
     */
    JS.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    };

    /**
     * AJAX utility for making HTTP requests.
     * https://stackoverflow.com/questions/8567114/how-to-make-an-JS.ajax-call-without-jquery
     */
    JS.ajax = {

        /**
         * Creates an XMLHttpRequest object.
         *
         * @returns {XMLHttpRequest|null} A new XMLHttpRequest object or null if not supported.
         */
        "x": function () {
            if (window.XMLHttpRequest) {
                return new XMLHttpRequest();
            }
            var versions = new Array(
                "MSXML2.XmlHttp.6.0", "MSXML2.XmlHttp.5.0", "MSXML2.XmlHttp.4.0",
                "MSXML2.XmlHttp.3.0", "MSXML2.XmlHttp.2.0", "Microsoft.XmlHttp");
            for (var i = 0; i < versions.length; i++) {
                try {
                    var x = new ActiveXObject(versions[i]);
                    return x;
                } catch (e) {}
            }
            return null;
        },

        /**
         * Sends an AJAX request.
         *
         * @param {string} url The URL to send the request to.
         * @param {string|null} data The request data (for POST requests).
         * @param {Function} callback The function to execute on success.
         * @param {string} method The HTTP method (GET or POST).
         */
        "send": function (url, data, callback, method) {
            var x = JS.ajax.x();
            if (x) {
                x.open(method, url, true); // Always asynchronous ;-)
                x.onreadystatechange = function () {
                    if (x.readyState == 4) {
                        callback(x.responseText);
                    }
                };
                if (method == "POST") {
                    x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                }
                x.send(data)
            }
        },

        /**
         * Sends a GET request.
         *
         * @param {string} url The URL to send the request to.
         * @param {Object} data An object containing query parameters.
         * @param {Function} callback The function to execute on success.
         */
        "get": function (url, data, callback) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url + (q.length ? '?' + q.join('&') : ''), null, callback, "GET")
        },

        /**
         * Sends a POST request.
         *
         * @param {string} url The URL to send the request to.
         * @param {Object} data An object containing form data.
         * @param {Function} callback The function to execute on success.
         */
        "post": function (url, data, callback) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url, q.join('&'), callback, "POST")
        }
    };

    /**
     * Retrieves the Moodle session key.
     *
     * @returns {string|null} The session key or null if not found.
     */
    JS.get_sesskey = function() {
        var elm = document.querySelector("input[name=sesskey]");
        if (elm) {
            return elm.getAttribute("value");
        }
        return null; // shouldn't happen !!
    };

    /**
     * Retrieves a URL parameter by name.
     *
     * @param {string} name The name of the URL parameter.
     * @returns {string} The value of the parameter or an empty string if not found.
     */
    JS.get_url_param = function(name) {
        if (window.URLSearchParams) {
            var s = new URLSearchParams(window.location.search);
            if (s.has(name)) {
                return s.get(name);
            } else {
                return "";
            }
        } else {
            // https://davidwalsh.name/query-string-javascript
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regexp = new RegExp("[\\?&]" + name + "=([^&#]*)");
            var r = regexp.exec(window.location.search);
            return (r === null ? "" : decodeURIComponent(r[1].replace(/\+/g, " ")));
        }
    };

    /**
     * Moves sidebar columns in the page layout.
     */
    JS.setup_columns = function() {
        ["left","right"].forEach(function(s) {
            let col = document.querySelector(".column" + s);
            if (col && col.parentNode.matches("#region-main-box")) {
                col.closest("#page-content").appendChild(col);
            }
        });
    }

    /**
     * Removes empty rows from a given selector.
     *
     * @param {string} rowtag The row tag name (optional).
     * @param {string} rowclass The row class name (optional).
     * @param {string} rowselectors The selector for targeting rows to check.
     */
    JS.remove_empty_rows = function(rowtag, rowclass, rowselectors) {
        document.querySelectorAll(rowselectors).forEach(function(row){
            if (rowtag == "" || row.matches(rowtag)) {
                if (rowclass == "" || row.matches(rowclass)) {
                    if (row.querySelector("dd:not(:empty)") == null) {
                        row.parentNode.removeChild(row);
                    }
                }
            }
        });
    };

    /**
     * Moves required field icons to the appropriate position.
     */
    JS.move_required_icons = function() {
        document.querySelectorAll("div.inline-req").forEach(function(req){
            var elm = req;
            // locate <dd> ancestor element
            while (elm && elm.matches("dd") == false) {
                elm = elm.parentNode;
            }
            // locate previous <dt> sibling element
            while (elm && elm.matches("dt") == false) {
                elm = elm.previousElementSibling;
            }
            if (elm) {
                // Locate ".icon" element (an <i> tag)
                var icon = req.querySelector(".icon");
                if (icon) {
                    // Override margin-right setting for ".icon" element.
                    icon.classList.add("mx-1");
                    var txt = icon.title;
                    if (txt) {
                        // Display "title" as text on wide screens.
                        var title = document.createElement("SMALL");
                        title.className = "d-none d-lg-inline text-muted";
                        title.appendChild(document.createTextNode(txt));
                        req.appendChild(title);
                        req.classList.add("text-nowrap");
                    }
                    req.classList.add("d-inline-block");
                    elm.appendChild(req);
                }
            }
        });
    };

    /**
     * Sets up media links for audio, video, and image elements.
     */
    JS.setup_media_links = function() {
        document.querySelectorAll(".row dd a").forEach(function(a){
            JS.setup_media_link(a);
        });
    };

    /**
     * Converts an anchor link to a media element (audio, video, image).
     *
     * @param {HTMLAnchorElement} a The anchor element to be transformed.
     */
    JS.setup_media_link = function(a) {
        var tag = "";
        switch (true) {
            case JS.videourl.test(a.href): tag = "VIDEO"; break;
            case JS.audiourl.test(a.href): tag = "AUDIO"; break;
            case JS.imageurl.test(a.href): tag = "IMG"; break;
        }
        if (tag) {
            var title = "";
            var img = a.previousElementSibling;
            if (img && img.matches("img")) {
                title = img.title;
                img.parentNode.removeChild(img);
            }

            // remove leading white space in <a>'s parent node
            while (a.parentNode.firstChild && 
                   a.parentNode.firstChild.nodeType == 3 && 
                   a.parentNode.firstChild.nodeValue.trim() == "") {
                a.parentNode.removeChild(a.parentNode.firstChild);
            }

            var elm = document.createElement(tag);
            elm.setAttribute("src", a.href);
            elm.setAttribute("title", title);
            if (tag == "AUDIO" || tag == "VIDEO") {
                elm.setAttribute("controls", "");
            }
            if (tag == "VIDEO") {
                elm.setAttribute("playsinline", "");
                elm.setAttribute("preload", "metadata");
            }
            a.parentNode.replaceChild(elm, a);
            JS.setup_media_player(elm);
        }
    };

    /**
     * Initializes media players for supported elements.
     */
    JS.setup_media_players = function() {
        var selectors = ".row dd video, " +
                        ".row dd audio, " +
                        ".row dd img";
        document.querySelectorAll(selectors).forEach(function(elm){
            JS.setup_media_player(elm);
        });
    };

    /**
     * Configures a media player element with appropriate settings.
     *
     * @param {HTMLElement} elm The media element (audio, video, or image).
     */
    JS.setup_media_player = function(elm) {

        var evt = "";
        var elm_height = "0px";
        var elm_width = "0px";
        var max_height = "0px";
        var max_width = "0px";
        var mediaready = false;

        switch (true) {
            case JS.videourl.test(elm.src):
                evt = "loadedmetadata";
                elm_height = (elm.videoHeight || 0);
                elm_width = (elm.videoWidth || 0);
                max_height = JS.video_max_height;
                max_width = JS.video_max_width;
                mediaready = (elm.readyState >= 1);
                break;

            case JS.audiourl.test(elm.src):
                evt = "canplay";
                elm_height = (elm.height || 0);
                elm_width = (elm.width || 0);
                max_height = JS.audio_max_height;
                max_width = JS.audio_max_width;
                mediaready = (elm.readyState >= 4);
                break;

            case JS.imageurl.test(elm.src):
                evt = "load";
                elm_height = (elm.height || 0);
                elm_width = (elm.width || 0);
                max_height = JS.image_max_height;
                max_width = JS.image_max_width;
                mediaready = (elm.complete ? true : false)
                break;
        }

        elm_height = parseInt(elm_height);
        elm_width = parseInt(elm_width);

        max_height = parseInt(max_height);
        max_width = parseInt(max_width);

        var parent_height = elm.parentNode.offsetHeight;
        var parent_width = elm.parentNode.offsetWidth;

        var cs = window.getComputedStyle(elm.parentNode);
        if (cs.getPropertyValue("box-sizing") == "content-box") {
            // content-box:
            //     width and height include the content, but 
            //     does not include the padding, border, or margin
            // border-box:
            //     width and height include the content, padding, 
            //     and border, but do not include the margin
            parent_height = parent_height
                            - parseInt(cs.getPropertyValue("padding-top"))
                            - parseInt(cs.getPropertyValue("padding-bottom"))
                            - parseInt(cs.getPropertyValue("border-top-width"))
                            - parseInt(cs.getPropertyValue("border-bottom-width"));
            parent_width = parent_width
                           - parseInt(cs.getPropertyValue("padding-left"))
                           - parseInt(cs.getPropertyValue("padding-right"))
                           - parseInt(cs.getPropertyValue("border-left-width"))
                           - parseInt(cs.getPropertyValue("border-right-width"));
        }

        // Ensure element's max dimensions do not exceed those of parent
        if (max_width == 0 || max_width > parent_width) {
            max_width = parent_width;
        }
        if (max_height == 0 || max_height > parent_height) {
            max_height = parent_height;
        }

       
        if (elm.dataset.setup == "complete") {
            // do nothing
        } else if (max_width && max_height) {

           if (mediaready == false) {
                elm.style.height = "auto";
                elm.style.width = "auto";
                elm.style.maxWidth = max_width + "px";
                JS.add_event_listener(elm, evt, function(e){
                    JS.setup_media_player(this);
                });
            } else {
                // Ensure we don't do this more than once
                elm.dataset.setup = "complete";

                if (elm_width <= max_width && elm_height > max_height) {
                    // Portrait video (height > width)
                    elm.style.height = "100%";
                    elm.style.width = "auto";
                    elm.style.maxHeight = max_height + "px";
                    elm.style.maxWidth = "unset";
                } else {
                    // Landscape video (height < width)
                    elm.style.height = "auto";
                    elm.style.width = "100%";
                    elm.style.maxHeight = "unset";
                    elm.style.maxWidth = max_width + "px";
                }
            }
        }
    }

    /**
     * Sets up a dependent field that updates based on a selection.
     *
     * @param {string} selectfieldname The name of the select field triggering the update.
     * @param {string} displayfieldname The name of the field being updated.
     * @param {string} displayparamname Additional parameters for fetching data.
     */
    JS.setup_dependant_field = function(selectfieldname,
                                        displayfieldname,
                                        displayparamname) {

        var selectselector = ".report." + selectfieldname + " dd select";
        var displayselector = ".report." + displayfieldname + " dd";

        var selectelement = document.querySelector(selectselector);
        if (selectelement) {
            JS.add_event_listener(selectelement, "change", function(e){
                var displayelement = document.querySelector(displayselector);
                if (displayelement) {
                    // Remove previous contents
                    while (displayelement.lastChild) {
                        displayelement.removeChild(displayelement.lastChild);
                    }
                    var uid = this.options[this.selectedIndex].value;
                    if (uid) {
                        var data = {"sesskey": JS.get_sesskey(),
                                    "d": JS.get_url_param("d"),
                                    "a": "displayvalue",
                                    "f": displayfieldname,
                                    "p": displayparamname,
                                    "uid": uid};
                        JS.ajax.post(JS.ajaxurl, data, function(responsetext){
                            if (responsetext.match(JS.htmlfragment)) {
                                var div = document.createElement('div');
                                div.innerHTML = responsetext;
                                var elm = div.firstChild;
                                displayelement.appendChild(elm);
                                JS.setup_media_player(elm);
                            } else if (typeof(responsetext) == "string" && responsetext.length > 0) {
                                // Probably an error :-(
                                displayelement.appendChild(document.createTextNode(responsetext));
                            }
                        });
                    }
                }
            });
        }
    };

    /**
     * Initializes page elements and settings.
     */
    JS.setup = function() {
        JS.setup_columns();
        JS.remove_empty_rows("dl", ".row", ".metafield.tags");
        //JS.setup_media_links();
        //JS.setup_media_players();
        JS.move_required_icons();
        //JS.setup_dependant_field("presenter", "presentation", "extra1");
    };

    /**
     * Adds an event listener to initialize the page setup on window load.
     */
    JS.add_event_listener(window, "load", JS.setup);
}());