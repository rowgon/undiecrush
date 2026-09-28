(() => {
    "use strict";
    var deferred, __webpack_modules__ = {
        6227: () => {
            String.format || (String.format = function(format) {
                var args = Array.prototype.slice.call(arguments, 1);
                return format.replace(/{(\d+)}/g, (function(match, number) {
                    return void 0 !== args[number] ? args[number] : match;
                }));
            }), window.addEventListener("DOMContentLoaded", (function() {
                var title = document.querySelector(".ag-post-metabox__title"), icon = title ? title.querySelector("i") : document.createElement("i"), text = title ? title.querySelector("span") : document.createElement("span"), button = title ? title.querySelector("button") : document.createElement("button");
                document.querySelectorAll(".ag-post-metabox__age-toggle").forEach((function(button) {
                    button.addEventListener("click", (function() {
                        return document.body.classList.toggle("ag-show-age");
                    }));
                })), document.querySelectorAll(".ag-post-metabox__set").forEach((function(button) {
                    button.addEventListener("click", (function(e) {
                        if (title) {
                            title.dataset.age = e.currentTarget.parentNode.querySelector("input").value;
                            var _title$dataset = title.dataset, age = _title$dataset.age, textRestrict = _title$dataset.textRestrict;
                            text.textContent = String.format(textRestrict, age), function(name) {
                                var event = new CustomEvent(name, {
                                    detail: arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}
                                });
                                window.dispatchEvent(event);
                            }("AgeGateSetAge", age);
                        }
                        document.body.classList.toggle("ag-show-age");
                    }));
                })), document.querySelectorAll(".ag_settings_switch").forEach((function(toggle) {
                    toggle.addEventListener("change", (function(e) {
                        if (title) {
                            var checked = e.target.checked, _title$dataset2 = title.dataset, age = _title$dataset2.age, textRestrict = _title$dataset2.textRestrict, textUnrestrict = _title$dataset2.textUnrestrict;
                            e.target.name.match(/bypass/) ? checked ? (button && (button.style.display = "none"), 
                            document.body.classList.remove("ag-show-age"), icon.className = "dashicons dashicons-unlock", 
                            text.textContent = textUnrestrict) : (button && (button.style.display = "inline-block"), 
                            icon.className = "dashicons dashicons-lock", text.textContent = String.format(textRestrict, age)) : checked ? (button && (button.style.display = "inline-block"), 
                            icon.className = "dashicons dashicons-lock", text.textContent = String.format(textRestrict, age)) : (button && (button.style.display = "none"), 
                            document.body.classList.remove("ag-show-age"), icon.className = "dashicons dashicons-unlock", 
                            text.textContent = textUnrestrict);
                        }
                    }));
                }));
                var languageSelector = document.querySelector('[name="post_lang_choice"], [name="icl_post_language"], [name="term_lang_choice"], [name="icl_tax_category_language"]');
                if (window.addEventListener("AgeGateSetAge", (function(e) {})), languageSelector) {
                    var agagemap = window.agagemap, initial = languageSelector.value, input = document.querySelector('[name="ag_settings[age]"]');
                    if (input) {
                        var isCustom = agagemap[initial] != input.value;
                        languageSelector.addEventListener("change", (function(e) {
                            if (!isCustom) {
                                var bypass = document.querySelector('.ag-post-metabox [name="ag_settings[bypass]"]'), restrict = document.querySelector('.ag-post-metabox [name="ag_settings[restrict]"]');
                                input.value = agagemap[e.target.value], title.dataset.age = agagemap[e.target.value];
                                var _title$dataset3 = title.dataset, age = _title$dataset3.age, textRestrict = _title$dataset3.textRestrict;
                                restrict && !restrict.checked || bypass && bypass.checked || (text.textContent = String.format(textRestrict, age));
                            }
                        }));
                    }
                }
            }));
        },
        7498: () => {},
        7741: () => {},
        8520: () => {},
        5881: () => {}
    }, __webpack_module_cache__ = {};
    function __webpack_require__(moduleId) {
        var cachedModule = __webpack_module_cache__[moduleId];
        if (void 0 !== cachedModule) return cachedModule.exports;
        var module = __webpack_module_cache__[moduleId] = {
            exports: {}
        };
        return __webpack_modules__[moduleId](module, module.exports, __webpack_require__), 
        module.exports;
    }
    __webpack_require__.m = __webpack_modules__, deferred = [], __webpack_require__.O = (result, chunkIds, fn, priority) => {
        if (!chunkIds) {
            var notFulfilled = 1 / 0;
            for (i = 0; i < deferred.length; i++) {
                for (var [chunkIds, fn, priority] = deferred[i], fulfilled = !0, j = 0; j < chunkIds.length; j++) (!1 & priority || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key => __webpack_require__.O[key](chunkIds[j]))) ? chunkIds.splice(j--, 1) : (fulfilled = !1, 
                priority < notFulfilled && (notFulfilled = priority));
                if (fulfilled) {
                    deferred.splice(i--, 1);
                    var r = fn();
                    void 0 !== r && (result = r);
                }
            }
            return result;
        }
        priority = priority || 0;
        for (var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
        deferred[i] = [ chunkIds, fn, priority ];
    }, __webpack_require__.o = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop), 
    (() => {
        var installedChunks = {
            268: 0,
            928: 0,
            84: 0,
            608: 0,
            116: 0
        };
        __webpack_require__.O.j = chunkId => 0 === installedChunks[chunkId];
        var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
            var moduleId, chunkId, [chunkIds, moreModules, runtime] = data, i = 0;
            if (chunkIds.some((id => 0 !== installedChunks[id]))) {
                for (moduleId in moreModules) __webpack_require__.o(moreModules, moduleId) && (__webpack_require__.m[moduleId] = moreModules[moduleId]);
                if (runtime) var result = runtime(__webpack_require__);
            }
            for (parentChunkLoadingFunction && parentChunkLoadingFunction(data); i < chunkIds.length; i++) chunkId = chunkIds[i], 
            __webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId] && installedChunks[chunkId][0](), 
            installedChunks[chunkId] = 0;
            return __webpack_require__.O(result);
        }, chunkLoadingGlobal = self.webpackChunkage_gate = self.webpackChunkage_gate || [];
        chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0)), chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
    })(), __webpack_require__.O(void 0, [ 928, 84, 608, 116 ], (() => __webpack_require__(6227))), 
    __webpack_require__.O(void 0, [ 928, 84, 608, 116 ], (() => __webpack_require__(7498))), 
    __webpack_require__.O(void 0, [ 928, 84, 608, 116 ], (() => __webpack_require__(7741))), 
    __webpack_require__.O(void 0, [ 928, 84, 608, 116 ], (() => __webpack_require__(8520)));
    var __webpack_exports__ = __webpack_require__.O(void 0, [ 928, 84, 608, 116 ], (() => __webpack_require__(5881)));
    __webpack_exports__ = __webpack_require__.O(__webpack_exports__);
})();