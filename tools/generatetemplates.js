(function() {
    var TOOL = {};

    TOOL.str = {};
    TOOL.plugin = "datafield_admin";

    TOOL.commands = new Array("viewhtml", "copyhtml");

    TOOL.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    TOOL.img = {
        "viewhtml" : TOOL.wwwroot + "/pix/i/preview.svg",
        "copyhtml" : TOOL.wwwroot + "/pix/t/download.svg"
    };

    TOOL.get_text_content = function(elm){
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

    TOOL.onclick_viewhtml = function(){
        var p = this;
        while (p) {
            p = p.parentElement;
            if (p && p.matches("fieldset")) {
                p.querySelectorAll(".defaulttemplate").forEach(function(elm){
                    if (elm.matches("div")) {
                        var pre = document.createElement("pre");
                        pre.classList.add("defaulttemplate");
                        pre.classList.add("p-2");
                        pre.contentEditable = true;
                        pre.appendChild(document.createTextNode(elm.outerHTML));
                        elm.parentNode.replaceChild(pre, elm);
                    } else if (elm.matches("pre")) {
                        elm.outerHTML = TOOL.get_text_content(elm);
                    }
                });
                p = null; // stop looping
            }
        }
    };

    TOOL.onclick_copyhtml = function(){
        var p = this;
        while (p) {
            p = p.parentElement;
            if (p && p.matches("fieldset")) {
                p.querySelectorAll(".defaulttemplate").forEach(function(elm){

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
                });
                p = null; // stop looping
            }
        }
    };

    TOOL.get_strings = function() {
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
        document.querySelectorAll("fieldset.template legend").forEach(function(legend){

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

    TOOL.init = function() {
        TOOL.get_strings().then(TOOL.setup_commands);
    };

    TOOL.add_event_listener(window, "load", TOOL.init);
}());
