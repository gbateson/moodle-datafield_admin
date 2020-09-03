(function() {
    var ADMIN = {};

    ADMIN.add_event_listener = function(obj, evt, fn, useCapture) {
        if (obj.addEventListener) {
            obj.addEventListener(evt, fn, (useCapture || false));
        } else if (obj.attachEvent) {
            obj.attachEvent("on" + evt, fn);
        }
    };

    ADMIN.fix_page_id = function() {
        var body = document.querySelector("body#page-mod-data-field-");
        if (body) {
            var input = document.querySelector("form#editfield input[type=hidden][name=type]");
            if (input) {
                body.id += input.value;
            }
        }
    };

    ADMIN.setup = function() {
        ADMIN.fix_page_id();
    };

    ADMIN.add_event_listener(window, "load", ADMIN.setup);
}());