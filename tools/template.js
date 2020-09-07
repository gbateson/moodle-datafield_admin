(function() {
    var JS = {};

    JS.wwwroot = window.location.href.replace(new RegExp("/mod/data/.*"), "");
    JS.ajaxurl = JS.wwwroot + "/mod/data/field/report/field.ajax.php"

    JS.audiourl = new RegExp("\\w+\\.(aac|flac|mp3|m4a|oga|ogg|wav)\\b", "i");
    JS.imageurl = new RegExp("\\w+\\.(gif|jpe|jpeg|jpg|png|svg|svgz)\\b", "i");
    JS.videourl = new RegExp("\\w+\\.(mov|mp4|m4v|ogv|webm)\\b", "i");
    JS.htmlfragment = new RegExp("^<(\\w+)\\b[^>]*>.*</\\1>$");

    JS.audio_max_width = "480px";
    JS.audio_max_height = "64px";

    JS.image_max_width = "640px";
    JS.image_max_height = "640px";

    JS.video_max_width = "640px";
    JS.video_max_height = "360px";

    JS.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    };

    // https://stackoverflow.com/questions/8567114/how-to-make-an-JS.ajax-call-without-jquery
    JS.ajax = {
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

        "get": function (url, data, callback) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url + (q.length ? '?' + q.join('&') : ''), null, callback, "GET")
        },

        "post": function (url, data, callback) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url, q.join('&'), callback, "POST")
        }
    };

    JS.get_sesskey = function() {
        var elm = document.querySelector("input[name=sesskey]");
        if (elm) {
            return elm.getAttribute("value");
        }
        return null; // shouldn't happen !!
    };

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

    JS.remove_empty_rows = function(rows) {
        document.querySelectorAll(rows).forEach(function(row){
            if (row.querySelector("dd:not(:empty)") == null) {
                row.parentNode.removeChild(row);
            }
        });
    };

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

    JS.setup_media_links = function() {
        document.querySelectorAll(".row dd a").forEach(function(a){
            JS.setup_media_link(a);
        });
    };

    JS.setup_media_link = function(a, max_width, max_height) {
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

            var elm = document.createElement(tag);
            elm.src = a.href;
            elm.title = title;
            if (tag == "AUDIO" || tag == "VIDEO") {
                elm.controls = true;
            }
            if (tag == "VIDEO") {
                elm.playsinline = true;
            }
            a.parentNode.replaceChild(elm, a);
            JS.setup_media_player(elm, max_width, max_height);
        }
    };

    JS.setup_media_players = function() {
        var selectors = ".row dd video, " +
                        ".row dd audio, " +
                        ".row dd img";
        document.querySelectorAll(selectors).forEach(function(elm){
            JS.setup_media_player(elm);
        });
    };

    JS.setup_media_player = function(elm, max_width, max_height) {
        var evt = "";
        var mediaready = false;
        if (elm.dataset.setup !== "complete") {
            elm.dataset.setup = "complete";
            if (elm.tagName == "AUDIO" || elm.tagName == "VIDEO") {
                evt = "canplay";
                mediaready = (elm.readyState >= 4);
            } else if (elm.tagName == "IMG") {
                evt = "load";
                mediaready = (elm.complete ? true : false)
            }
        }
        if (mediaready) {
            JS.check_media_height(elm, max_width, max_height);
        } else if (evt) {
            JS.add_event_listener(elm, evt, function(){
                JS.check_media_height(this, max_width, max_height);
            });
        }
    };

    JS.check_media_height = function(elm, max_width, max_height) {
        if (typeof(max_width) == "undefined" ||
            typeof(max_height) == "undefined") {
            switch (true) {
                case JS.videourl.test(elm.src):
                    max_width = JS.video_max_width;
                    max_height = JS.video_max_height;
                    break;
                case JS.audiourl.test(elm.src):
                    max_width = JS.audio_max_width;
                    max_height = JS.audio_max_height;
                    break;
                case JS.imageurl.test(elm.src):
                    max_width = JS.image_max_width;
                    max_height = JS.image_max_height;
                    break;
            }
        }
        if (elm.offsetHeight > parseInt(max_height)) {
            elm.style.width = "auto";
            elm.style.height = "100%";
            elm.style.maxWidth = "initial";
            elm.style.maxHeight = max_height;
        } else {
            elm.style.width = "100%";
            elm.style.height = "auto";
            elm.style.maxWidth = max_width;
            elm.style.maxHeight = "initial";
        }
    }

    JS.setup_dependant_field = function(selectfieldname,
                                         displayfieldname,
                                         displayparamname) {

        var selectselector = ".report." + selectfieldname + " dd select";
        var displayselector = ".report." + displayfieldname + " dd";

        var selectelement = document.querySelector(selectselector);
        if (selectelement) {
            JS.add_event_listener(selectelement, "change", function(){
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

    // Find any "##user##" text on the add/edit screen anduser
    // replace it with the formatted name of the current user.
    JS.setup_user = function() {
        var user = document.querySelector(".user dd");
        if (user && user.innerText == "##user##") {
            var usertext = document.querySelector(".userbutton .usertext");
            if (usertext) {
                var viewingas = usertext.querySelector(".viewingas span");
                if (viewingas) {
                    user.innerText = viewingas.innerText
                } else {
                    user.innerText = usertext.innerText
                }
            }
        }
    };

    JS.setup = function() {
        JS.remove_empty_rows(".metafield.tags");
        //JS.setup_user();
        //JS.setup_media_links();
        //JS.setup_media_players();
        JS.move_required_icons();
        //JS.setup_dependant_field("presenter", "presentation", "extra1");
    };

    JS.add_event_listener(window, "load", JS.setup);
}());