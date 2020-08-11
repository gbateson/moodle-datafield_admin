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

    var fn = function(){
        if (window.require) {
            require(["jquery", "jqueryui", "core/str"], function($, JUI, STR) {

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
