(function() {
    var JS = {};

    JS.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    };

    JS.fix_page_id = function() {
        var body = document.querySelector("body#page-mod-data-field-");
        if (body) {
            var input = document.querySelector("form#editfield input[type=hidden][name=type]");
            if (input) {
                body.id += input.value;
            }
        }
    };

    JS.init_textareas = function() {
        document.querySelectorAll("textarea").forEach(function(textarea){
            // Add event listener that adjusts height to accommodate content.
            JS.add_event_listener(textarea, 'input', function(){
                this.style.height = 'auto'; // '1px' also works
                this.style.height = (this.scrollHeight + 6) + 'px';
            });
            textarea.dispatchEvent(new Event('input'));
        });
    };

    JS.setup = function() {
        JS.fix_page_id();
        JS.init_textareas();
    };

    JS.add_event_listener(window, "load", JS.setup);
}());