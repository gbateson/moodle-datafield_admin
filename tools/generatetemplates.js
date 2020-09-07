(function() {
    var TOOL = {};

    TOOL.str = {};
    TOOL.plugin = "datafield_admin";

    TOOL.htmlcommands = new Array("viewhtml", "copyhtml", "stripes", "savehtml");
    TOOL.textcommands = new Array("viewtext", "copytext", "savetext");

    TOOL.stringnames = TOOL.htmlcommands.concat(TOOL.textcommands)
                       .concat(new Array("copiedhtml", "savedhtml",
                                         "copiedtext", "savedtext", "hidetext",
                                         "stripesall", "saveall", "savedall", "labelseparators",
                                         "confirmaction", "confirmsave", "confirmsaveall"));

    TOOL.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    TOOL.img = {
        "viewhtml" : TOOL.wwwroot + "/pix/i/preview.svg",
        "copyhtml" : TOOL.wwwroot + "/pix/t/download.svg",
        "savehtml" : TOOL.wwwroot + "/pix/e/save.svg",
        "stripes"  : TOOL.wwwroot + "/pix/a/view_list_active.svg",
        "viewtext" : TOOL.wwwroot + "/pix/i/preview.svg",
        "copytext" : TOOL.wwwroot + "/pix/t/download.svg",
        "savetext" : TOOL.wwwroot + "/pix/e/save.svg"
    };
    TOOL.toolurl = TOOL.wwwroot + "/mod/data/field/admin/tools/generatetemplates.php"

    TOOL.match_valid_templatenames = new RegExp("^(list|single|asearch|add|css|js)template$");
    TOOL.match_stripe_templatenames = new RegExp("^(list|single|asearch|add)template$");

    TOOL.match_stripeson = new RegExp('<div class="container([^"]*) stripes([^"]*)">');
    TOOL.match_stripesoff = new RegExp('<div class="container([^"]*)">');

    TOOL.replace_stripesoff = '<div class="container$1$2">';
    TOOL.replace_stripeson = '<div class="container stripes$1">';

    TOOL.match_labelson = new RegExp('<div class="container([^"]*) label-separators([^"]*)">');
    TOOL.match_labelsoff = new RegExp('<div class="container([^"]*)">');

    TOOL.replace_labelsoff = '<div class="container$1$2">';
    TOOL.replace_labelson = '<div class="container label-separators$1">';

    TOOL.add_event_listener = function(elm, evt, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evt, fn, (useCapture || false));
        } else if (elm.attachEvent) {
            elm.attachEvent("on" + evt, fn);
        }
    };

    // https://stackoverflow.com/questions/8567114/how-to-make-an-TOOL.ajax-call-without-jquery
    TOOL.ajax = {
        "x": function () {
            if (window.XMLHttpRequest) {
                return new XMLHttpRequest();
            }
            var versions = new Array("MSXML2.XmlHttp.6.0",
                                     "MSXML2.XmlHttp.5.0",
                                     "MSXML2.XmlHttp.4.0",
                                     "MSXML2.XmlHttp.3.0",
                                     "MSXML2.XmlHttp.2.0",
                                     "Microsoft.XmlHttp");
            var x;
            for (var i = 0; i < versions.length; i++) {
                try {
                    x = new ActiveXObject(versions[i]);
                    break;
                } catch (e) {}
            }
            return x;
        },

        "send": function (url, data, callback, method) {
            var x = TOOL.ajax.x();
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
            TOOL.ajax.send(url + (q.length ? '?' + q.join('&') : ''), null, callback, "GET")
        },

        "post": function (url, data, callback) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            TOOL.ajax.send(url, q.join('&'), callback, "POST")
        }
    };

    TOOL.get_fieldset = function(elm) {
        return TOOL.get_related_element(elm, "fieldset.template");
    };

    TOOL.get_legend = function(elm) {
        return TOOL.get_related_element(elm, "fieldset", "legend");
    };

    TOOL.get_defaulttemplate = function(elm) {
        return TOOL.get_related_element(elm, "fieldset", ".defaulttemplate");
    };

    TOOL.get_related_element = function(elm, ancestor, target) {
        var a = elm;
        while (a = a.parentElement) {
            if (a.matches(ancestor)) {
                if (target) {
                    return a.querySelector(target);
                } else {
                    return a;
                }
            }
        }
        return null;
    };

    TOOL.get_sesskey = function() {
        var elm = document.querySelector("input[name=sesskey]");
        if (elm) {
            return elm.getAttribute("value");
        }
        return null; // shouldn't happen !!
    };

    TOOL.get_url_param = function(name) {
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

    TOOL.get_string = function(strname, a) {
        if (typeof(TOOL.str[strname]) == "undefined") {
            return "Unknown string: " + strname;
        }
        var str = TOOL.str[strname];
        if (typeof(a) == "object") {
            a.forEach(function(value, key){
                var r = new RegExp("\\{\\$a->" + key + "\\}", "g");
                str = str.replace(r, value);
            });
        } else {
            var r = new RegExp("\\{\\$a\\}", "g");
            str = str.replace(r, a);
        }
        return str;
    };

    TOOL.get_template_description = function(elm) {
        return TOOL.get_legend(elm).firstChild.nodeValue;
    };

    TOOL.confirm_action = function(elm, msg) {
        if (elm) {
            var a = TOOL.get_template_description(elm);
            msg = TOOL.get_string(msg, a);
        } else {
            msg = TOOL.get_string(msg);
        }
        return confirm(TOOL.str.confirmaction + "\n\n" + msg);
    };

    TOOL.onclick_viewhtml = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                var pre = document.createElement("pre");
                pre.className = "defaulttemplate p-2";
                pre.contentEditable = true;
                pre.appendChild(document.createTextNode(elm.outerHTML));
                elm.parentNode.replaceChild(pre, elm);
            } else if (elm.matches("pre")) {
                elm.outerHTML = TOOL.get_text_content(elm);
            }
        }
    };

    TOOL.onclick_copyhtml = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            var content = "";
            if (elm.matches("div")) {
                content = elm.outerHTML;
            } else if (elm.matches("pre")) {
                content = TOOL.get_text_content(elm);
            }
            TOOL.copy_to_clipboard(content, TOOL.str.copiedhtml);
        }
    };

    TOOL.onclick_stripes = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                elm.classList.toggle("stripes");
            } else if (elm.matches("pre")) {
                var txt = TOOL.get_text_content(elm);
                if (txt.match(TOOL.match_stripeson)) {
                    txt = txt.replace(TOOL.match_stripeson, TOOL.replace_stripesoff);
                } else {
                    txt = txt.replace(TOOL.match_stripesoff, TOOL.replace_stripeson);
                }
                while (elm.firstChild) {
                    elm.removeChild(elm.firstChild);
                }
                elm.appendChild(document.createTextNode(txt));
            }
        }
    };

    TOOL.onclick_savehtml = function() {
        var elm = TOOL.get_defaulttemplate(this);
        if (elm && TOOL.confirm_action(this, "confirmsave")) {
            var content = "";
            if (elm.matches("div")) {
                content = elm.outerHTML;
            } else if (elm.matches("pre")) {
                content = TOOL.get_text_content(elm);
            }
            TOOL.save_to_template(this, "savedhtml", content);
        }
    };

    TOOL.onclick_viewtext = function() {
        var elm = TOOL.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm) {
            this.childNodes.forEach(function(node){
                if (node.nodeType == 3) {
                    node.parentNode.removeChild(node);
                }
            });
            if (elm.style.display == "none") {
                elm.style.display = "";
                this.appendChild(document.createTextNode(" " + TOOL.str.hidetext));
            } else {
                elm.style.display = "none";
                this.appendChild(document.createTextNode(" " + TOOL.str.viewtext));
            };
        }
    };

    TOOL.onclick_copytext = function() {
        var elm = TOOL.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm) {
            var content = TOOL.get_text_content(elm);
            TOOL.copy_to_clipboard(content, TOOL.str.copiedtext);
        }
    };

    TOOL.onclick_savetext = function() {
        var elm = TOOL.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm && TOOL.confirm_action(this, "confirmsave")) {
            var content = TOOL.get_text_content(elm);
            TOOL.save_to_template(this, "savedtext", content);
        }
    };

    TOOL.copy_to_clipboard = function(txt, msg) {

        var container = document.createElement("pre");
        container.style.position = "fixed";
        container.style.pointerEvents = "none"
        container.style.opacity = 0;
        container.appendChild(document.createTextNode(txt));
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
        alert(msg);

        document.body.removeChild(container);
    };

    TOOL.save_to_template = function(elm, strname, content) {

        var data = {"id": TOOL.get_url_param("id"),
                    "sesskey": TOOL.get_sesskey(),
                    "action": "savetemplates"};

        TOOL.get_fieldset(elm).classList.forEach(function(name){
            if (name.match(TOOL.match_valid_templatenames)) {
                data["templates[" + name + "]"] = content;
            }
        });

        TOOL.ajax.post(TOOL.toolurl, data, function(responsetext){
            if (responsetext == 'OK') {
                var a = TOOL.get_template_description(elm);
                alert(TOOL.get_string(strname, a));
            } else {
                // Probably an error :-(
                alert(responsetext);
            }
        });
    };

    TOOL.onclick_saveall = function() {

        // Confirm that user really wants to overwrite ALL templates.
        if (TOOL.confirm_action(null, "confirmsaveall")) {

            var data = {"id": TOOL.get_url_param("id"),
                        "sesskey": TOOL.get_sesskey(),
                        "action": "savetemplates"};

            document.querySelectorAll("fieldset.template").forEach(function(fieldset){
                fieldset.classList.forEach(function(name){
                    if (name.match(TOOL.match_valid_templatenames)) {
                        var content = "";
                        var elm = fieldset.querySelector(".defaulttemplate");
                        if (name == "csstemplate" || name == "jstemplate") {
                            elm = elm.querySelector("pre");
                        }
                        if (elm) {
                            if (elm.matches("div")) {
                                content = elm.outerHTML;
                            } else if (elm.matches("pre")) {
                                content = TOOL.get_text_content(elm);
                            }
                        }
                        if (content) {
                            data["templates[" + name + "]"] = content;
                        }
                    }
                });
            });

            TOOL.ajax.post(TOOL.toolurl, data, function(responsetext){
                if (responsetext == 'OK') {
                    alert(TOOL.str.savedall);
                } else {
                    // Probably an error :-(
                    alert(responsetext);
                }
            });
        }
    };

    TOOL.onclick_stripesall = function() {

        var fieldsets = document.querySelectorAll("fieldset.template");

        // Count the number of templates using stripes.
        var count = 0;
        fieldsets.forEach(function(fieldset){
            fieldset.classList.forEach(function(name){
                if (name.match(TOOL.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (elm.matches(".stripes")) {
                            count++;
                        } else {
                            count--;
                        }
                    } else if (elm.matches("pre")) {
                        var txt = TOOL.get_text_content(elm);
                        if (txt.match(TOOL.match_stripeson)) {
                            count++;
                        } else {
                            count--;
                        }
                    }
                }
            });
        });

        // Determine whether to add (true) or remove (false) stripes.
        var stripes = (count < 0);

        fieldsets.forEach(function(fieldset){
            fieldset.classList.forEach(function(name){
                if (name.match(TOOL.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (stripes) {
                            elm.classList.add("stripes");
                        } else {
                            elm.classList.remove("stripes");
                        }
                    } else if (elm.matches("pre")) {
                        var txt = TOOL.get_text_content(elm);
                        var newtxt = "";
                        if (txt.match(TOOL.match_stripeson)) {
                            if (stripes == false) {
                                newtxt = txt.replace(TOOL.match_stripeson, TOOL.replace_stripesoff);
                            }
                        } else {
                            if (stripes) {
                                newtxt = txt.replace(TOOL.match_stripesoff, TOOL.replace_stripeson);
                            }
                        }
                        if (newtxt) {
                            while (elm.firstChild) {
                                elm.removeChild(elm.firstChild);
                            }
                            elm.appendChild(document.createTextNode(newtxt));
                        }
                    }
                }
            });
        });
    };

    TOOL.onclick_labelseparators = function() {
        document.querySelectorAll("fieldset.template").forEach(function(fieldset){
            fieldset.classList.forEach(function(name){
                if (name.match(TOOL.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (elm.classList.contains("label-separators")) {
                            elm.classList.remove("label-separators");
                        } else {
                            elm.classList.add("label-separators");
                        }
                    } else if (elm.matches("pre")) {
                        var txt = TOOL.get_text_content(elm);
                        var newtxt = "";
                        if (txt.match(TOOL.match_labelson)) {
                            newtxt = txt.replace(TOOL.match_labelson, TOOL.replace_labelsoff);
                        } else {
                            newtxt = txt.replace(TOOL.match_labelsoff, TOOL.replace_labelson);
                        }
                        if (newtxt) {
                            while (elm.firstChild) {
                                elm.removeChild(elm.firstChild);
                            }
                            elm.appendChild(document.createTextNode(newtxt));
                        }
                    }
                }
            });
        });
    };

    TOOL.setup_strings = function() {
        return new Promise(function(resolve, reject){
            if (window.require) {
                require(["core/str"], function(STR) {
                    var strings = new Array();
                    TOOL.stringnames.forEach(function(name, i){
                        strings.push({"key": name, "component": TOOL.plugin});
                    });
                    STR.get_strings(strings).done(function(s) {
                        TOOL.stringnames.forEach(function(name, i){
                            TOOL.str[name] = s[i];
                        });
                        resolve();
                    });
                });
            } else {
                // use English equivalents
                var htmltext = new RegExp("(^.*)(html|text)$");
                TOOL.stringnames.forEach(function(name, i){
                    var s = name.charAt(0).toUpperCase()
                          + name.substr(1).toLowerCase();
                    TOOL.str[name] = s.replace(htmltext, "$1 $2");
                });
                resolve();
            }
        });
    };

    TOOL.setup_buttons = function() {

        var h3 = document.querySelector("fieldset.template:first-of-type").previousElementSibling;

        var names = new Array("labelseparators", "stripesall", "saveall");
        names.forEach(function(name){
            var btn = document.createElement("BUTTON");
            btn.className = "btn btn-secondary bg-light rounded " + name;
            btn.setAttribute("type", "button");
            btn.setAttribute("name", name);
            btn.appendChild(document.createTextNode(TOOL.str[name]));

            var div = document.createElement("DIV");
            div.className ="singlebutton ml-4";
            div.appendChild(btn);

            h3.appendChild(div);

            TOOL.add_event_listener(btn, "click", TOOL["onclick_" + name]);
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

            if (legend.parentElement.matches(".csstemplate, .jstemplate")) {
                var commands = TOOL.textcommands;
            } else {
                var commands = TOOL.htmlcommands;
            }

            commands.forEach(function(command){
                var title = TOOL.str[command];
                var icon = document.createElement("img");
                icon.src = TOOL.img[command];
                icon.title = title;

                var span = document.createElement("span");
                span.className = command + " mx-sm-2";
                span.appendChild(icon);
                span.appendChild(document.createTextNode(" " + title));
                TOOL.add_event_listener(span, "click", TOOL["onclick_" + command]);
                icons.appendChild(span);
            });

            legend.appendChild(icons);

            var span = icons.querySelector(".viewtext");
            if (span) {
                span.click();
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
        p.then(TOOL.setup_buttons);
        p.then(TOOL.setup_commands);
        p.then(TOOL.setup_nav_links);
    };

    TOOL.add_event_listener(window, "load", TOOL.setup);
}());
