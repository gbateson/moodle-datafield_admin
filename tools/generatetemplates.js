(function() {
    var JS = {};

    JS.str = {};
    JS.plugin = "datafield_admin";

    JS.htmlcommands = new Array("viewhtml", "copyhtml", "stripes", "loadhtml", "savehtml");
    JS.textcommands = new Array("viewtext", "copytext", "loadtext", "savetext");

    JS.stringnames = JS.htmlcommands.concat(JS.textcommands)
                     .concat(new Array("copiedhtml", "loadedhtml", "savedhtml",
                                       "copiedtext", "loadedtext", "savedtext", "hidetext",
                                       "labelseparators", "stripesall",
                                       "loadall", "loadedall",
                                       "saveall", "savedall",
                                       "confirmaction",
                                       "confirmsave", "confirmsaveall",
                                       "confirmload", "confirmloadall"));

    JS.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    JS.img = {
        "viewhtml" : JS.wwwroot + "/pix/i/preview.svg",
        "copyhtml" : JS.wwwroot + "/pix/t/download.svg",
        "loadhtml" : JS.wwwroot + "/pix/t/portfolioadd.svg",
        "savehtml" : JS.wwwroot + "/pix/e/save.svg",
        "stripes"  : JS.wwwroot + "/pix/a/view_list_active.svg",
        "viewtext" : JS.wwwroot + "/pix/i/preview.svg",
        "copytext" : JS.wwwroot + "/pix/t/download.svg",
        "loadtext" : JS.wwwroot + "/pix/t/portfolioadd.svg",
        "savetext" : JS.wwwroot + "/pix/e/save.svg"
    };
    JS.toolurl = JS.wwwroot + "/mod/data/field/admin/tools/generatetemplates.php"

    JS.match_valid_templatenames = new RegExp("^(list|single|asearch|add|css|js)template$");
    JS.match_stripe_templatenames = new RegExp("^(list|single|asearch|add)template$");

    JS.match_stripeson = new RegExp('<div class="container([^"]*) stripes([^"]*)">');
    JS.match_stripesoff = new RegExp('<div class="container([^"]*)">');

    JS.replace_stripesoff = '<div class="container$1$2">';
    JS.replace_stripeson = '<div class="container stripes$1">';

    JS.match_labelson = new RegExp('<div class="container([^"]*) label-separators([^"]*)">');
    JS.match_labelsoff = new RegExp('<div class="container([^"]*)">');

    JS.replace_labelsoff = '<div class="container$1$2">';
    JS.replace_labelson = '<div class="container label-separators$1">';

    JS.match_html_error = new RegExp("^<br[^>]*>\\s*((.|\\s)*?)<br[^>]*>\\s*\\{");

    JS.add_event_listener = function(elm, evt, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evt, fn, (useCapture || false));
        } else if (elm.attachEvent) {
            elm.attachEvent("on" + evt, fn);
        }
    };

    // https://stackoverflow.com/questions/8567114/how-to-make-an-ajax-call-without-jquery
    // based on above page (Thanks!). Reformatting and addition of "responseType" by GB.
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

        "send": function (url, data, callback, method, type) {
            var x = JS.ajax.x();
            if (x) {
                x.open(method, url, true); // Always asynchronous ;-);
                if (type) {
                    x.responseType = type; // e.g. "blob"
                }
                x.onreadystatechange = function () {
                    if (x.readyState == 4) {
                        if (x.responseType == "" || x.responseType == "text") {
                            callback(x.responseText);
                        } else {
                            callback(x.response); // e.g. "blob"
                        }
                    }
                };
                if (method == "POST") {
                    x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                }
                x.send(data);
            }
        },

        "get": function (url, data, callback, type) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url + (q.length ? '?' + q.join('&') : ''), null, callback, "GET", type);
        },

        "post": function (url, data, callback, type) {
            var q = [];
            for (var key in data) {
                q.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            JS.ajax.send(url, q.join('&'), callback, "POST", type);
        }
    };

    JS.get_fieldset = function(elm) {
        return JS.get_related_element(elm, "fieldset.template");
    };

    JS.get_legend = function(elm) {
        return JS.get_related_element(elm, "fieldset", "legend");
    };

    JS.get_defaulttemplate = function(elm) {
        return JS.get_related_element(elm, "fieldset", ".defaulttemplate");
    };

    JS.get_related_element = function(elm, ancestor, target) {
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

    JS.get_text_content = function(elm) {
        var txt = new Array();
        elm.childNodes.forEach(function(node){
            if (node.nodeType == 3) { // Node.TEXT_NODE
                txt.push(node.nodeValue);
            } else {
                txt.push(node.textContent || node.innerText);
                // txt.push(JS.get_text_content(node));
            }
        });
        return txt.join("\n");
    };

    JS.get_string = function(strname, a) {
        if (typeof(JS.str[strname]) == "undefined") {
            return "Unknown string: " + strname;
        }
        var str = JS.str[strname];
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

    JS.get_template_description = function(elm) {
        return JS.get_legend(elm).firstChild.nodeValue;
    };

    JS.confirm_action = function(elm, msg) {
        if (elm) {
            var a = JS.get_template_description(elm);
            msg = JS.get_string(msg, a);
        } else {
            msg = JS.get_string(msg);
        }
        return confirm(JS.str.confirmaction + "\n\n" + msg);
    };

    JS.onclick_viewhtml = function() {
        var elm = JS.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                var pre = document.createElement("pre");
                pre.className = "defaulttemplate p-2";
                pre.contentEditable = true;
                pre.appendChild(document.createTextNode(elm.outerHTML));
                elm.parentNode.replaceChild(pre, elm);
            } else if (elm.matches("pre")) {
                elm.outerHTML = JS.get_text_content(elm);
            }
        }
    };

    JS.onclick_copyhtml = function() {
        var elm = JS.get_defaulttemplate(this);
        if (elm) {
            var content = "";
            if (elm.matches("div")) {
                content = elm.outerHTML;
            } else if (elm.matches("pre")) {
                content = JS.get_text_content(elm);
            }
            JS.copy_to_clipboard(content, JS.str.copiedhtml);
        }
    };

    JS.onclick_stripes = function() {
        var elm = JS.get_defaulttemplate(this);
        if (elm) {
            if (elm.matches("div")) {
                elm.classList.toggle("stripes");
            } else if (elm.matches("pre")) {
                var txt = JS.get_text_content(elm);
                if (txt.match(JS.match_stripeson)) {
                    txt = txt.replace(JS.match_stripeson, JS.replace_stripesoff);
                } else {
                    txt = txt.replace(JS.match_stripesoff, JS.replace_stripeson);
                }
                while (elm.firstChild) {
                    elm.removeChild(elm.firstChild);
                }
                elm.appendChild(document.createTextNode(txt));
            }
        }
    };

    JS.onclick_loadhtml = function() {
        var elm = JS.get_defaulttemplate(this);
        if (elm && JS.confirm_action(this, "confirmload")) {
            JS.load_template(this, "loadedhtml");
        }
    };

    JS.onclick_savehtml = function() {
        var elm = JS.get_defaulttemplate(this);
        if (elm && JS.confirm_action(this, "confirmsave")) {
            var content = "";
            if (elm.matches("div")) {
                content = elm.outerHTML;
            } else if (elm.matches("pre")) {
                content = JS.get_text_content(elm);
            }
            JS.save_template(this, "savedhtml", content);
        }
    };

    JS.onclick_viewtext = function() {
        var elm = JS.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm) {
            this.childNodes.forEach(function(node){
                if (node.nodeType == 3) {
                    node.parentNode.removeChild(node);
                }
            });
            if (elm.style.display == "none") {
                elm.style.display = "";
                this.appendChild(document.createTextNode(" " + JS.str.hidetext));
            } else {
                elm.style.display = "none";
                this.appendChild(document.createTextNode(" " + JS.str.viewtext));
            };
        }
    };

    JS.onclick_copytext = function() {
        var elm = JS.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm) {
            var content = JS.get_text_content(elm);
            JS.copy_to_clipboard(content, JS.str.copiedtext);
        }
    };

    JS.onclick_loadtext = function() {
        var elm = JS.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm && JS.confirm_action(this, "confirmload")) {
            JS.load_template(this, "loadedtext");
        }
    };

    JS.onclick_savetext = function() {
        var elm = JS.get_defaulttemplate(this);
        elm = elm.querySelector("pre");
        if (elm && JS.confirm_action(this, "confirmsave")) {
            var content = JS.get_text_content(elm);
            JS.save_template(this, "savedtext", content);
        }
    };

    JS.copy_to_clipboard = function(txt, msg) {

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
        JS.show_message(msg);

        document.body.removeChild(container);
    };

    JS.load_template = function(elm, strname, content) {

        var data = {"id": JS.get_url_param("id"),
                    "sesskey": JS.get_sesskey(),
                    "action": "loadtemplates"};

        JS.get_fieldset(elm).classList.forEach(function(name){
            if (name.match(JS.match_valid_templatenames)) {
                data["templates[" + name + "]"] = content;
            }
        });

        var a = JS.get_template_description(elm);
        var msg = JS.get_string(strname, a);

        JS.load_templates(data, msg);
    };

    JS.load_templates = function(data, msg) {
        JS.ajax.post(JS.toolurl, data, function(responsetext){
            var m = responsetext.match(JS.match_html_error);
            if (m && m[1]) {
                JS.show_message(m[1], "alert");
                responsetext = responsetext.substr(m[0].length - 1);
            }
            if (JS.is_json(responsetext)) {
                var templates = JSON.parse(responsetext);
                for (var name in templates) {
                    if (name.match(JS.match_valid_templatenames)) {
                        var selector = ".template." + name + " .defaulttemplate";
                        var elm = document.querySelector(selector);
                        if (name == "csstemplate" || name == "jstemplate") {
                            elm = elm.querySelector("pre");
                        }
                        if (elm) {
                            if (elm.matches("div")) {
                                elm.outerHTML = templates[name];
                            } else if (elm.matches("pre")) {
                                elm.innerText = templates[name];
                            }
                        }
                    }
                }
                JS.show_message(msg);
            } else {
                JS.show_message(responsetext, "error");
            }
        });
    };

    JS.is_json = function(str) {
        if (typeof(str) == "string" && str.length > 0) {
            return (str.charAt(0) == '{' && str.charAt(str.length - 1) == '}');
        } else {
            return false;
        }
    }

    JS.show_message = function(msg, type) {
        var elm = document.getElementById("user-notifications");
        if (type == "alert") {
            elm = null;
        }
        if (elm) {
            if (elm.dataset.setup == null) {
                elm.dataset.setup = 1;
                elm.style.borderRadius = "6px";
                elm.style.padding = "6px 12px";
                JS.add_event_listener(elm, "click", JS.hide_message);
            }
            if (type == "error") {
                elm.style.backgroundColor = "#fee"; // light red
                elm.style.border = "2px solid #c99"; // dark red
            } else {
                elm.style.backgroundColor = "#efe"; // light green
                elm.style.border = "2px solid #9c9"; // dark green
            }
            elm.innerHTML = msg;
            elm.style.display = "block";
        } else {
            // "user-notifications" element was not found so ...
            alert(msg.replace(new RegExp("<[^>]*>", "g"), ""));
        }
    }

    JS.hide_message = function() {
        var elm = document.getElementById("user-notifications");
        if (elm) {
            elm.innerHTML = "";
            elm.style.display = "none";
        }
    }

    JS.save_template = function(elm, strname, content) {

        var data = {"id": JS.get_url_param("id"),
                    "sesskey": JS.get_sesskey(),
                    "action": "savetemplates"};

        JS.get_fieldset(elm).classList.forEach(function(name){
            if (name.match(JS.match_valid_templatenames)) {
                data["templates[" + name + "]"] = content;
            }
        });

        JS.ajax.post(JS.toolurl, data, function(responsetext){
            if (responsetext == 'OK') {
                var a = JS.get_template_description(elm);
                JS.show_message(JS.get_string(strname, a));
            } else {
                // Probably an error :-(
                JS.show_message(responsetext);
            }
        });
    };

    JS.onclick_loadall = function() {
        // Confirm that user really wants to load ALL templates.
        if (JS.confirm_action(null, "confirmloadall")) {

            var data = {"id": JS.get_url_param("id"),
                        "sesskey": JS.get_sesskey(),
                        "action": "loadtemplates"};

            document.querySelectorAll("fieldset.template").forEach(function(fieldset){
                fieldset.classList.forEach(function(name){
                    if (name.match(JS.match_valid_templatenames)) {
                        data["templates[" + name + "]"] = true;
                    }
                });
            });

            JS.load_templates(data, JS.str.loadedall);
        }
    };

    JS.onclick_saveall = function() {

        // Confirm that user really wants to overwrite ALL templates.
        if (JS.confirm_action(null, "confirmsaveall")) {

            var data = {"id": JS.get_url_param("id"),
                        "sesskey": JS.get_sesskey(),
                        "action": "savetemplates"};

            document.querySelectorAll("fieldset.template").forEach(function(fieldset){
                fieldset.classList.forEach(function(name){
                    if (name.match(JS.match_valid_templatenames)) {
                        var content = "";
                        var elm = fieldset.querySelector(".defaulttemplate");
                        if (name == "csstemplate" || name == "jstemplate") {
                            elm = elm.querySelector("pre");
                        }
                        if (elm) {
                            if (elm.matches("div")) {
                                content = elm.outerHTML;
                            } else if (elm.matches("pre")) {
                                content = JS.get_text_content(elm);
                            }
                        }
                        if (content) {
                            data["templates[" + name + "]"] = content;
                        }
                    }
                });
            });

            JS.ajax.post(JS.toolurl, data, function(responsetext){
                if (responsetext == 'OK') {
                    JS.show_message(JS.str.savedall);
                } else {
                    // Probably an error :-(
                    JS.show_message(responsetext);
                }
            });
        }
    };

    JS.onclick_stripesall = function() {

        var fieldsets = document.querySelectorAll("fieldset.template");

        // Count the number of templates using stripes.
        var count = 0;
        fieldsets.forEach(function(fieldset){
            fieldset.classList.forEach(function(name){
                if (name.match(JS.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (elm.matches(".stripes")) {
                            count++;
                        } else {
                            count--;
                        }
                    } else if (elm.matches("pre")) {
                        var txt = JS.get_text_content(elm);
                        if (txt.match(JS.match_stripeson)) {
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
                if (name.match(JS.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (stripes) {
                            elm.classList.add("stripes");
                        } else {
                            elm.classList.remove("stripes");
                        }
                    } else if (elm.matches("pre")) {
                        var txt = JS.get_text_content(elm);
                        var newtxt = "";
                        if (txt.match(JS.match_stripeson)) {
                            if (stripes == false) {
                                newtxt = txt.replace(JS.match_stripeson, JS.replace_stripesoff);
                            }
                        } else {
                            if (stripes) {
                                newtxt = txt.replace(JS.match_stripesoff, JS.replace_stripeson);
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

    JS.onclick_labelseparators = function() {
        document.querySelectorAll("fieldset.template").forEach(function(fieldset){
            fieldset.classList.forEach(function(name){
                if (name.match(JS.match_stripe_templatenames)) {
                    var elm = fieldset.querySelector(".defaulttemplate");
                    if (elm.matches("div")) {
                        if (elm.classList.contains("label-separators")) {
                            elm.classList.remove("label-separators");
                        } else {
                            elm.classList.add("label-separators");
                        }
                    } else if (elm.matches("pre")) {
                        var txt = JS.get_text_content(elm);
                        var newtxt = "";
                        if (txt.match(JS.match_labelson)) {
                            newtxt = txt.replace(JS.match_labelson, JS.replace_labelsoff);
                        } else {
                            newtxt = txt.replace(JS.match_labelsoff, JS.replace_labelson);
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

    JS.setup_strings = function() {
        return new Promise(function(resolve, reject){
            if (window.require) {
                require(["core/str"], function(STR) {
                    var strings = new Array();
                    JS.stringnames.forEach(function(name, i){
                        strings.push({"key": name, "component": JS.plugin});
                    });
                    STR.get_strings(strings).done(function(s) {
                        JS.stringnames.forEach(function(name, i){
                            JS.str[name] = s[i];
                        });
                        resolve();
                    });
                });
            } else {
                // use English equivalents
                var htmltext = new RegExp("(^.*)(html|text)$");
                JS.stringnames.forEach(function(name, i){
                    var s = name.charAt(0).toUpperCase()
                          + name.substr(1).toLowerCase();
                    JS.str[name] = s.replace(htmltext, "$1 $2");
                });
                resolve();
            }
        });
    };

    JS.setup_buttons = function() {

        var h3 = document.querySelector("fieldset.template:first-of-type").previousElementSibling;

        var names = new Array("labelseparators", "stripesall", "loadall", "saveall");
        names.forEach(function(name){
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
        });
    };

    JS.setup_commands = function() {
        document.querySelectorAll("fieldset.template legend").forEach(function(legend){

            var icons = document.createElement("div");
            icons.className = "icons "
                            + "border border-light rounded "
                            + "bg-light text-dark "
                            + "px-2 py-0 mx-2 my-0";
            // The classes could be added individually
            // e.g. icons.classList.add("border");

            if (legend.parentElement.matches(".csstemplate, .jstemplate")) {
                var commands = JS.textcommands;
            } else {
                var commands = JS.htmlcommands;
            }

            commands.forEach(function(command){
                var title = JS.str[command];
                var icon = document.createElement("img");
                icon.src = JS.img[command];
                icon.title = title;

                var span = document.createElement("span");
                span.className = command + " mx-sm-2";
                span.appendChild(icon);
                span.appendChild(document.createTextNode(" " + title));
                JS.add_event_listener(span, "click", JS["onclick_" + command]);
                icons.appendChild(span);
            });

            legend.appendChild(icons);

            var span = icons.querySelector(".viewtext");
            if (span) {
                span.click();
            }
        });
    };

    JS.setup_nav_links = function(){
        var elm = document.querySelector("a.nav-link[href*='/mod/data/field.php']");
        if (elm) {
            elm.classList.add("active");
        }
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
            document.querySelectorAll(".border").forEach(function(elm){
                elm.classList.remove("border");
                elm.classList.remove("border-dark");
                elm.classList.remove("border-light");
                elm.classList.add("border-bottom");
                elm.classList.add("border-left");
                elm.classList.add("border-right");
                elm.classList.add("border-top");
            });
            document.querySelectorAll("legend.text-light").forEach(function(elm){
                elm.classList.remove("text-light");
                elm.classList.add("text-white");
            });
            document.querySelectorAll(".icons.text-dark").forEach(function(elm){
                elm.classList.remove("text-dark");
                elm.classList.add("text-info");
            });
            document.querySelectorAll(".container .my-2").forEach(function(elm){
                elm.classList.remove("my-2");
                elm.classList.add("m-b-1");
            });
            document.querySelectorAll(".icons .mx-sm-2").forEach(function(elm){
                elm.classList.remove("mx-sm-2");
                if (elm.matches(":not(:first-child)")) {
                    elm.classList.add("m-l-1"); // 14px
                }
            });
            document.querySelectorAll("dl.row").forEach(function(elm){
                elm.className = "dl-horizontal " + elm.className;
                elm.classList.add("m-0"); // override ".row" margins.
            });
            document.querySelectorAll("dl.h3").forEach(function(elm){
                elm.classList.remove("h3");
                elm.classList.remove("text-dark");
                elm.classList.add("lead"); // increase font-size
            });
            var r = new RegExp("^[bmp][blrtxy]?(-(xs|sm|md|lg|xl))-[0-9]+$");
            document.querySelectorAll("fieldset, legend, div.container, dl, dt, dd").forEach(function(elm){
                var names = new Array();
                elm.classList.forEach(function(name){
                    if (name.substr(0,3) == "col" || name.substr(0, 8) == "rounded-" || r.test(name)) {
                        names.push(name);
                    }
                });
                names.forEach(function(name){
                    elm.classList.remove(name);
                });
            });
        }
    };

    JS.setup = function() {
        var p = JS.setup_strings();
        p.then(JS.setup_buttons);
        p.then(JS.setup_commands);
        p.then(JS.setup_nav_links);
        p.then(JS.setup_bootstrap_v3);
    };

    JS.add_event_listener(window, "load", JS.setup);
}());
