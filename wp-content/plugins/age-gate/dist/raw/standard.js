(() => {
    "use strict";
    var fireEvent = function(name) {
        var event = new CustomEvent(name, {
            detail: arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}
        });
        window.dispatchEvent(event);
    };
    window.addEventListener("DOMContentLoaded", (function() {
        fireEvent("age_gate_shown"), fireEvent("agegateshown");
    }));
})();