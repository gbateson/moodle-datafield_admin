(function() {
    var TMP = {};

    TMP.audiourl = new RegExp("\\w+\\.(aac|flac|mp3|m4a|oga|ogg|wav)\\b", "i");
    TMP.imageurl = new RegExp("\\w+\\.(gif|jpe|jpeg|jpg|png|svg|svgz)\\b", "i");
    TMP.videourl = new RegExp("\\w+\\.(mov|mp4|m4v|ogv|webm)\\b", "i");

    TMP.audio_max_width = "480px";
    TMP.image_max_width = "640px";
    TMP.video_max_width = "640px";

    TMP.add_event_listener = function(elm, evt, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evt, fn, (useCapture || false));
        } else if (elm.attachEvent) {
            elm.attachEvent("on" + evt, fn);
        }
    };

    TMP.setup_row_hover = function() {
        var elms = document.querySelectorAll("div.defaulttemplate dt, " +
                                             "div.defaulttemplate dd");
        elms.forEach(function(elm){
            var sibling = "";
            if (elm.matches("dt")) {
                sibling = "nextElementSibling";
            } else if (elm.matches("dd")) {
                sibling = "previousElementSibling";
            }
            if (sibling) {
                TMP.add_event_listener(elm, "mouseover", function(){
                    this.classList.add("hover");
                    this[sibling].classList.add("hover");
                });
                TMP.add_event_listener(elm, "mouseout", function(){
                    this.classList.remove("hover");
                    this[sibling].classList.remove("hover");
                });
            }
        });
    };

    TMP.hide_empty_rows = function() {
        // Define selectors of rows that are to be removed if they are empty.
        var selectors = new Array(".metafield.tags");
        selectors.forEach(function(selector){
            document.querySelectorAll("dt" + selector).forEach(function(dt){
                var dd = dt.nextElementSibling;
                if (dd) {
                    if (dd.matches("dd:empty")) {
                        dt.parentNode.removeChild(dd);
                        dt.parentNode.removeChild(dt);
                    }
                }
            });
        });
    };

    TMP.move_required_icons = function() {
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
                // override margin-right setting for ".icon" element
                var icon = req.querySelector(".icon");
                if (icon) {
                    icon.classList.add("mx-1");
                    var txt = icon.title;
                    if (txt) {
                        txt = document.createTextNode(txt);
                        var title = document.createElement("SMALL");
                        title.classList.add("d-none");
                        title.classList.add("d-lg-inline");
                        title.classList.add("text-muted");
                        title.appendChild(txt);
                        req.appendChild(title);
                        req.classList.add("text-nowrap");
                    }
                    req.classList.add("d-inline-block");
                    elm.appendChild(req);
                }
            }
        });
    };

    TMP.setup_dependant_field = function(selectfieldname,
                                         displayfieldname,
                                         displayparamname) {

        var selectselector = "dd.report." + selectfieldname + " select";
        var displayselector = "dd.report." + displayfieldname;

        var selectelement = document.querySelector(selectselector);
        if (selectelement) {
            TMP.add_event_listener(selectelement, "change", function(){
                var displayelement = document.querySelector(displayselector);
                if (displayelement) {
                    // Remove previous contents
                    while (displayelement.lastChild) {
                        displayelement.removeChild(displayelement.lastChild);
                    }
                    var uid = this.options[this.selectedIndex].value;
                    if (uid) {
                        var data = {"sesskey": TMP.get_sesskey(),
                                    "d": TMP.get_url_param("d"),
                                    "a": "displayvalue",
                                    "f": displayfieldname,
                                    "p": displayparamname,
                                    "uid": uid};
                        TMP.ajax.post(TMP.ajaxurl, data, function(responsetext){
                            if (responsetext.match(TMP.htmlfragment)) {
                                var div = document.createElement('div');
                                div.innerHTML = responsetext;
                                var elm = div.firstChild;
                                displayelement.appendChild(elm);
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

    TMP.setup_media_links = function() {
        document.querySelectorAll("dd a").forEach(function(a){        
            switch (true) {
                case TMP.videourl.test(a.href):
                    TMP.setup_media_link(a, "VIDEO", TMP.video_max_width);
                    break;
                case TMP.audiourl.test(a.href):
                    TMP.setup_media_link(a, "AUDIO", TMP.audio_max_width);
                    break;
                case TMP.imageurl.test(a.href):
                    TMP.setup_media_link(a, "IMG", TMP.image_max_width);
                    break;
            }
        });
    };

    TMP.setup_media_link = function(a, tag, max_width) {
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
        elm.style.width = "100%";
        elm.style.height = "auto";
        if (max_width) {
            elm.style.maxWidth = max_width;
        }

        a.parentNode.replaceChild(elm, a);
    };

    TMP.setup = function() {
        TMP.setup_row_hover();
        TMP.hide_empty_rows();
        TMP.move_required_icons();
        TMP.setup_media_links();
    };

    TMP.add_event_listener(window, "load", TMP.setup);
}());