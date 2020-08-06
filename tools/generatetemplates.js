//<![CDATA[
(function() {
    var fn = function() {
        if (window.require) {
            //require(["jquery"], function($) {
            //});
        }

        var wwwroot =  document.location.href.replace(new RegExp("/mod/.*$"), "");
        var showicon = wwwroot + "/pix/i/preview.svg";
        var copyicon = wwwroot + "/pix/t/download.svg";

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

            icons.style.display = "inline-block";
            icons.style.lineHeight = "24px";
            icons.style.fontSize = "0.5em";
            icons.style.position = "relative";
            icons.style.bottom = "5px";
            icons.style.left = "10px";

            var title = "View";

            var icon = document.createElement("img");
            icon.src = showicon;

            var span = document.createElement("span");
            span.title = title;
            span.classList.add("mx-sm-2");
            span.appendChild(icon);
            span.appendChild(document.createTextNode(" " +  title));
            span.onclick = function(){
                var p = this;
                while (p) {
                    p = p.parentElement;
                    if (p && p.matches("fieldset")) {
                        p.querySelectorAll(".defaulttemplate").forEach(function(elm){
                            if (elm.matches("div")) {
                                var pre = document.createElement("pre");
                                pre.classList.add("defaulttemplate");
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
            icons.appendChild(span);

            var title = "Copy";

            var icon = document.createElement("img");
            icon.src = copyicon;

            var span = document.createElement("span");
            span.title = title;
            span.classList.add("mx-sm-2");
            span.appendChild(icon);
            span.appendChild(document.createTextNode(" " +  title));
            span.onclick = function(){
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

                            if (document.body.createTextRange) {
                                // Internet Explorer
                                var range = document.body.createTextRange();
                                range.moveToElementText(container);
                                range.select();
                            } else if (window.getSelection) {
                                // other browsers
                                var selection = window.getSelection();
                                var range = document.createRange();
                                range.selectNode(container);
                                selection.removeAllRanges();
                                selection.addRange(range);
                            }

                            document.execCommand("Copy");
                            alert("Copied content to clipboard");

                            document.body.removeChild(container);
                        });
                        p = null; // stop looping
                    }
                }
            };
            icons.appendChild(span);

            legend.appendChild(icons);
        });
    };
    if (window.addEventListener) {
        window.addEventListener("load", fn, false);
    } else if (window.attachEvent) {
        window.attachEvent("onload", fn);
    }
}());
//]]>
