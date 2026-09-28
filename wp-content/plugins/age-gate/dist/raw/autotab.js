window.addEventListener("age_gate_shown", (function() {
    !function() {
        var inputs = Array.from(document.querySelectorAll(".age-gate-form-elements input, .age-gate__form-elements input")), current = 0;
        if (inputs) {
            var region = document.querySelector(".age-gate__region");
            region && region.addEventListener("change", (function() {
                region.value && document.querySelector(".age-gate__button, .age-gate-button").focus();
            })), inputs.forEach((function(input, idx) {
                input.addEventListener("keyup", (function(e) {
                    e.target.value.length >= e.target.maxLength && (idx !== inputs.length - 1 ? (inputs[current + 1].focus(), 
                    current += 1) : document.querySelector(".age-gate__region") ? document.querySelector(".age-gate__region").focus() : document.querySelector(".age-gate__button, .age-gate-button").focus());
                }));
            }));
        }
    }();
}));