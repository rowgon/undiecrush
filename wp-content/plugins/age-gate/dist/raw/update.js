(() => {
    "use strict";
    window.addEventListener("DOMContentLoaded", (function() {
        var selector, eventType, childSelector, eventHandler;
        selector = "#the-list", eventType = "click", childSelector = ".age-gate-enable-update", 
        eventHandler = function(e) {
            if (confirm("I understand this is a breaking change and have backed up my settings")) {
                var link = document.querySelector("#age-gate-update .update-link");
                link && (link.style.pointerEvents = "initial", link.style.opacity = 1, link.style.cursor = "pointer");
            }
        }, Array.from(document.querySelectorAll(selector)).forEach((function(element) {
            element.addEventListener(eventType, (function(eventOnElement) {
                eventOnElement.target.matches(childSelector) && eventHandler(eventOnElement);
            }));
        }));
    }));
})();