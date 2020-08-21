(function() {
    var TMP = {};

    TMP.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    };

    TMP.setup_row_hover = function(div) {
        if (div) {
            var elms = div.querySelectorAll("dt, dd");
        } else {
            var elms = document.querySelectorAll("div.defaulttemplate dt, " +
                                                 "div.defaulttemplate dd");
        }
        elms.forEach(function(elm){
            if (elm.matches("dt")) {
                TMP.add_event_listener(elm, "mouseover", function(){
                    this.classList.add("hover");
                    this.nextElementSibling.classList.add("hover");
                });
                TMP.add_event_listener(elm, "mouseout", function(){
                    this.classList.remove("hover");
                    this.nextElementSibling.classList.remove("hover");
                });
            } else if (elm.matches("dd")) {
                TMP.add_event_listener(elm, "mouseover", function(){
                    this.classList.add("hover");
                    this.previousElementSibling.classList.add("hover");
                });
                TMP.add_event_listener(elm, "mouseout", function(){
                    this.classList.remove("hover");
                    this.previousElementSibling.classList.remove("hover");
                });
            }
        });
    };

    TMP.hide_empty_rows = function() {
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

    TMP.setup = function() {
        TMP.setup_row_hover();
        TMP.hide_empty_rows();
    };

    TMP.add_event_listener(window, "load", TMP.setup);
}());