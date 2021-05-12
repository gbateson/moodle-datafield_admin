(function() {
    var JS = {};

    JS.str = {};
    JS.plugin = "datafield_admin";

    JS.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    JS.img = {
        "add" : JS.wwwroot + "/pix/t/add.svg",
        "edit" : JS.wwwroot + "/pix/t/edit.svg",
        "delete" : JS.wwwroot + "/pix/t/delete.svg"
    };

    JS.add_event_listener = function(elm, evt, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evt, fn, (useCapture || false));
        } else if (elm.attachEvent) {
            elm.attachEvent("on" + evt, fn);
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

    JS.onclick_add = function(){
        var li = this;
        while (li && li.matches("li") == false) {
            li = li.parentElement;
        }

        var m = li.parentElement.className.match(new RegExp("field_([0-9]+)"));
        if (m && m[1]) {
            var fid = m[1];
            var ul = document.querySelector("ul.currentvalues.field_" + fid);
            var i = ul.querySelectorAll("li").length + 1;
            li.querySelectorAll("input[name^=fields][name*=missing]").forEach(function(elm){
                elm.name = elm.name.replace("missing", "current");
                elm.name = elm.name.replace(new RegExp("\\[\\d+\\]$"), "[" + i + "]");
            });
            ul.appendChild(li);
            this.parentNode.removeChild(this);
        }
        JS.setup_duplicates();
    };

    JS.onclick_edit = function(){
        var parent = null; // parentNode
        var input = null; // INPUT
        var txt = null; // .text
        if (parent = this.parentElement.parentElement) {
            input = parent.querySelector("input");
            txt = parent.querySelector(".text");
        }
        if (input) {
            if (input.type == "hidden") {
                input.type = "text";
                txt.style.display = "none";
            } else {
                input.type = "hidden";
                txt.innerHTML = input.value;
                txt.style.display = "block";
            }
        }
        JS.setup_duplicates();
    };

    JS.onclick_delete = function(){
        var parent = null; // parentNode
        var input = null; // INPUT
        var txt = null; // .text
        if (parent = this.parentElement.parentElement) {
            input = parent.querySelector("input");
            txt = parent.querySelector(".text");
        }
        if (input) {
            if (txt.querySelector("del")) {
                txt.innerHTML = txt.innerHTML.replace(new RegExp("^<del>(.*)</del>$"), "$1");
                input.value = txt.innerText;
            } else {
                txt.innerHTML = "<del>" + txt.innerText + "</del>";
                input.value = "";
            }
        }
        JS.setup_duplicates();
    };

    JS.setup_nav_links = function(){
        var elm = document.querySelector("a.nav-link[href*='/mod/data/field.php']");
        if (elm) {
            elm.classList.add("active");
        }
    };

    JS.setup_icons = function(){

        var selector = "input[type=text][name^=fields][name*=new]";
        document.querySelectorAll(selector).forEach(function(elm){

            // Hide this element.
            elm.type = "hidden";

            // Locate ".count" element
            var count = elm;
            while (count && count.matches(".count") == false) {
                count = count.previousElementSibling;
            }
            
            // Create ".icons" element
            var icons = document.createElement("DIV");
            icons.className = "icons position-absolute h-100";

            // Determine the names of required icons.
            var names = new Array();
            if (elm.matches("[name*=current]")) {
                names = new Array("edit", "delete");
            } else if (elm.matches("[name*=missing]")) {
                names = new Array("add", "edit", "delete");
            }

            // Add icons.
            names.forEach(function(name){
                var icon = document.createElement("IMG");
                icon.className = "icon " + name + " mr-2";
                icon.src = JS.img[name];
                JS.add_event_listener(icon, "click", JS["onclick_" + name]);
                icons.appendChild(icon);
            });

            // Append count of occurences.
            if (count) {
                JS.add_event_listener(count, "click", function(){
                    alert(this.title);
                });
                icons.appendChild(count);
            }

            var text = document.createElement("DIV");
            text.className = "text";
            text.innerHTML = elm.value;

            var item = document.createElement("DIV");
            item.className = "item position-relative h-auto";
            // prevent zero height when element contains no text
            item.style.minHeight = "1.0em";
            item.appendChild(icons);
            item.appendChild(text);
            
            elm.parentNode.insertBefore(item, elm);
            item.appendChild(elm);
        });
    };

    JS.setup_duplicates = function(){
        var selector = ".currentvalues, .missingvalues";
        document.querySelectorAll(selector).forEach(function(li){
            var values = new Array();
            li.querySelectorAll("li .text").forEach(function(txt){
                if (values.indexOf(txt.innerText) >= 0) {
                    txt.classList.add("duplicate");
                } else {
                    txt.classList.remove("duplicate");
                    values.push(txt.innerText);
                }
            });
        });
    };

    JS.setup = function() {
        JS.setup_nav_links();
        JS.setup_icons();
        JS.setup_duplicates();
    };

    JS.add_event_listener(window, "load", JS.setup);
}());
