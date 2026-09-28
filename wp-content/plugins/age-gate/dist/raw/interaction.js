(() => {
    var preventKey = function(event) {
        void 0 !== event.key ? ("f12" === event.key.toLowerCase() && event.preventDefault(), 
        event.metaKey && event.altKey && "dead" === event.key.toLowerCase() && event.preventDefault(), 
        event.ctrlKey && event.shiftKey && "dead" === event.key.toLowerCase() && event.preventDefault()) : void 0 !== event.keyCode && (123 === event.keyCode && event.preventDefault(), 
        event.metaKey && event.altKey && 73 === event.keyCode && event.preventDefault(), 
        event.ctrlKey && event.shiftKey && 73 === event.keyCode && event.preventDefault());
    }, preventContext = function(event) {
        event.preventDefault();
    };
    window.addEventListener("age_gate_shown", (function() {
        document.addEventListener("keydown", preventKey), document.addEventListener("contextmenu", preventContext);
    })), window.addEventListener("age_gate_passed", (function() {
        document.removeEventListener("keydown", preventKey), document.removeEventListener("contextmenu", preventContext);
    }));
})();