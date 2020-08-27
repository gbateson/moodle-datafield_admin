(function() {
    var TMP = {};

    TMP.audiourl = new RegExp("\\w+\\.(aac|flac|mp3|m4a|oga|ogg|wav)\\b", "i");
    TMP.imageurl = new RegExp("\\w+\\.(gif|jpe|jpeg|jpg|png|svg|svgz)\\b", "i");
    TMP.videourl = new RegExp("\\w+\\.(mov|mp4|m4v|ogv|webm)\\b", "i");

    TMP.audio_max_width = "480px";
    TMP.image_max_width = "640px";
    TMP.video_max_width = "640px";

    TMP.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
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
        TMP.setup_media_links();
    };

    TMP.add_event_listener(window, "load", TMP.setup);
}());