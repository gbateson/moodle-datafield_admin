//<![CDATA[
(function() {
    var TOOL = {};

    TOOL.str = {};
    TOOL.plugin = "datafield_admin";

    if (window.require) {
        //
        // mod/englishcentral/amd/src/report.js
        //
        //require(["jquery", "jqueryui", "core/str"], function($, JUI, STR) {
        //    STR.get_strings([
        //        {"key": "viewhtml", "component": TOOL.plugin},
        //        {"key": "copyhtml", "component": TOOL.plugin}
        //    ]).done(function(s) {
        //        var i = 0;
        //        TOOL.str.viewhtml = s[i++];
        //        TOOL.str.copyhtml = s[i++];
        //    });
        //});
    } else {
        // use English defaults
    }

    TOOL.wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
    TOOL.showicon = TOOL.wwwroot + "/pix/i/preview.svg";
    TOOL.copyicon = TOOL.wwwroot + "/pix/t/download.svg";

    TOOL.onclick_viewhtml = function(){
        var p = this;
        while (p) {
            p = p.parentElement;
            if (p && p.matches("fieldset")) {
                p.querySelectorAll(".defaulttemplate").forEach(function(elm){
                    if (elm.matches("div")) {
                        var pre = document.createElement("pre");
                        pre.classList.add("defaulttemplate");
                        pre.contentEditable = true;
                        pre.appendChild(document.createTextNode(elm.outerHTML));
                        elm.parentNode.replaceChild(pre, elm);
                    } else if (elm.matches("pre")) {
                        elm.outerHTML = elm.childNodes[0].nodeValue;
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
                        html = elm.childNodes[0].nodeValue;
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
                    alert("Copied content to clipboard");

                    document.body.removeChild(container);
                });
                p = null; // stop looping
            }
        }
    };

    TOOL.init = function() {

        document.querySelectorAll("fieldset.template legend").forEach(function(legend){

            var icons = document.createElement("div");
            icons.classList.add("border");
            icons.classList.add("border-light");
            icons.classList.add("rounded");
            icons.classList.add("bg-light");
            icons.classList.add("text-dark");
            icons.classList.add("px-2");
            icons.classList.add("py-0");
            icons.classList.add("mx-2");
            icons.classList.add("my-0");
            // NOTE: the above classes could be set all at once using the following:
            // icons.className = "border border-light rounded "
            //                 + "bg-light text-dark "
            //                 + "px-2 py-0 mx-2 my-0";

            icons.style.display = "inline-block";
            icons.style.lineHeight = "24px";
            icons.style.fontSize = "0.5em";
            icons.style.position = "relative";
            icons.style.bottom = "5px";
            icons.style.left = "10px";
            // NOTE; the above styles could be set all at once using the following:
            // icons.style.cssText = "display: inline-block; "
            //                     + "line-height: 24px; font-size: 0.5em; "
            //                     + "position: relative; bottom: 5px; left: 10px";

            var title = "View";
            var icon = document.createElement("img");
            icon.src = TOOL.showicon;

            var span = document.createElement("span");
            span.title = title;
            span.classList.add("mx-sm-2");
            span.appendChild(icon);
            span.appendChild(document.createTextNode(" " +  title));
            span.onclick = TOOL.onclick_viewhtml;
            icons.appendChild(span);

            var title = "Copy";
            var icon = document.createElement("img");
            icon.src = TOOL.copyicon;

            var span = document.createElement("span");
            span.title = title;
            span.classList.add("mx-sm-2");
            span.appendChild(icon);
            span.appendChild(document.createTextNode(" " +  title));
            span.onclick = TOOL.onclick_copyhtml;
            icons.appendChild(span);
            legend.appendChild(icons);
        });
    };

    TOOL.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    }

    TOOL.add_event_listener(window, "load", TOOL.init);
}());
//]]>
