(function() {
    var TOOL = {};

    TOOL.str = {};
    TOOL.plugin = "datafield_admin";

    TOOL.commands = new Array("viewhtml", "copyhtml", "stripes");

    TOOL.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    TOOL.img = {
        "viewhtml" : TOOL.wwwroot + "/pix/i/preview.svg",
        "copyhtml" : TOOL.wwwroot + "/pix/t/download.svg",
        "stripes"  : TOOL.wwwroot + "/pix/a/view_list_active.svg"
    };

    TOOL.get_text_content = function(elm) {
        var txt = new Array();
        elm.childNodes.forEach(function(node){
            if (node.nodeType == 3) { // Node.TEXT_NODE
                txt.push(node.nodeValue);
            } else {
                txt.push(node.textContent || node.innerText);
                // txt.push(TOOL.get_text_content(node));
            }
        });
        return txt.join("\n");
    };

    TOOL.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    }

    TOOL.get_related_element = function(elm, ancestor, target) {
        var a = elm;
        while (a = a.parentElement) {
            if (a.matches(ancestor)) {
                return a.querySelector(target);
            }
        }
        return null;
    };

    TOOL.get_defaulttemplate = function(elm) {
        return TOOL.get_related_element(elm, "fieldset", ".defaulttemplate");
    }

    TOOL.onclick_viewhtml = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                var pre = document.createElement("pre");
                pre.classList.add("defaulttemplate");
                pre.classList.add("p-2");
                pre.contentEditable = true;
                pre.appendChild(document.createTextNode(elm.outerHTML));
                elm.parentNode.replaceChild(pre, elm);
            } else if (elm.matches("pre")) {
                elm.outerHTML = TOOL.get_text_content(elm);
                if (elm = TOOL.get_defaulttemplate(this)) {
                    TOOL.setup_row_hover(elm);
                }
            }
        }
    };

    TOOL.onclick_copyhtml = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            var html = "";
            if (elm.matches("div")) {
                html = elm.outerHTML;
            } else if (elm.matches("pre")) {
                html = TOOL.get_text_content(elm);
            }

            var container = document.createElement("pre");
            container.style.position = "fixed";
            container.style.pointerEvents = "none"
            container.style.opacity = 0;
            container.appendChild(document.createTextNode(html));
            document.body.appendChild(container)

            if (window.getSelection) {
                // All browsers, except IE before version 9
                var selection = window.getSelection();
                selection.selectAllChildren(container);
            } else {
                // Internet Explorer before version 9
                var range = document.body.createTextRange();
                range.moveToElementText(container);
                range.select();
            }

            document.execCommand("Copy");
            alert(TOOL.str.copiedhtml);

            document.body.removeChild(container);
        }
    };

    TOOL.onclick_stripes = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                var dl = elm.querySelector("dl.row");
                if (dl) {
                    dl.classList.toggle("stripes");
                }
            } else if (elm.matches("pre")) {
                var txt = TOOL.get_text_content(elm);
                var stripeson = '<dl class="row stripes">';
                var stripesoff = '<dl class="row">';
                if (txt.indexOf(stripesoff) >= 0) {
                    txt = txt.replace(stripesoff, stripeson);
                } else {
                    txt = txt.replace(stripeson, stripesoff);
                }
                while (elm.firstChild) {
                    elm.removeChild(elm.firstChild);
                }
                elm.appendChild(document.createTextNode(txt));
            }
        }
    };

    TOOL.setup_strings = function() {
        return new Promise(function(resolve, reject){
            if (window.require) {
                require(["core/str"], function(STR) {

                    var strings = new Array();
                    TOOL.commands.forEach(function(command){
                        strings.push({"key": command, "component": TOOL.plugin});
                    });
                    strings.push({"key": "copiedhtml", "component": TOOL.plugin});

                    STR.get_strings(strings).done(function(s) {
                        TOOL.commands.forEach(function(command, i){
                            TOOL.str[command] = s[i];
                        });
                        var i = TOOL.commands.length;
                        TOOL.str.copiedhtml = s[i++];
                        resolve();
                    });
                });
            } else {
                // use English defaults
                TOOL.str.viewhtml = "View HTML";
                TOOL.str.copyhtml = "Copy HTML";
                TOOL.str.copiedhtml = "HTML was copied to clipboard";
                resolve();
            }
        });
    };

    TOOL.setup_commands = function() {
        document.querySelectorAll("fieldset.template:not(.csstemplate):not(.jstemplate) legend").forEach(function(legend){

            var icons = document.createElement("div");
            icons.className = "icons "
                            + "border border-light rounded "
                            + "bg-light text-dark "
                            + "px-2 py-0 mx-2 my-0";
            // The classes could be added individually
            // e.g. icons.classList.add("border");

            TOOL.commands.forEach(function(command){
                var title = TOOL.str[command];
                var icon = document.createElement("img");
                icon.src = TOOL.img[command];

                var span = document.createElement("span");
                span.title = title;
                span.classList.add("mx-sm-2");
                span.appendChild(icon);
                span.appendChild(document.createTextNode(" " + title));
                TOOL.add_event_listener(span, "click", TOOL["onclick_" + command]);
                icons.appendChild(span);
            });

            legend.appendChild(icons);
        });
    };

    TOOL.setup_row_hover = function(div) {
        if (div) {
            var elms = div.querySelectorAll("dt, dd");
        } else {
            var elms = document.querySelectorAll("div.defaulttemplate dt, " +
                                                 "div.defaulttemplate dd");
        }
        elms.forEach(function(elm){
            if (elm.matches("dt")) {
                TOOL.add_event_listener(elm, "mouseover", function(){
                    this.classList.add("hover");
                    this.nextElementSibling.classList.add("hover");
                });
                TOOL.add_event_listener(elm, "mouseout", function(){
                    this.classList.remove("hover");
                    this.nextElementSibling.classList.remove("hover");
                });
            } else if (elm.matches("dd")) {
                TOOL.add_event_listener(elm, "mouseover", function(){
                    this.classList.add("hover");
                    this.previousElementSibling.classList.add("hover");
                });
                TOOL.add_event_listener(elm, "mouseout", function(){
                    this.classList.remove("hover");
                    this.previousElementSibling.classList.remove("hover");
                });
            }
        });
    };

    TOOL.setup_nav_links = function(){
        var elm = document.querySelector("a.nav-link[href*='/mod/data/field.php']");
        if (elm) {
            elm.classList.add("active");
        }
    };

    TOOL.setup = function() {
        var p = TOOL.setup_strings();
        p.then(TOOL.setup_commands);
        p.then(TOOL.setup_row_hover);
        p.then(TOOL.setup_nav_links);
    };

    TOOL.add_event_listener(window, "load", TOOL.setup);
}());
