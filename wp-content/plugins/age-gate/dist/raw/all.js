/*! For license information please see all.js.LICENSE.txt */
(() => {
    var __webpack_modules__ = {
        8712: function(module) {
            module.exports = function() {
                "use strict";
                var __assign = function() {
                    return __assign = Object.assign || function(t) {
                        for (var s, i = 1, n = arguments.length; i < n; i++) for (var p in s = arguments[i]) Object.prototype.hasOwnProperty.call(s, p) && (t[p] = s[p]);
                        return t;
                    }, __assign.apply(this, arguments);
                };
                function createTestElement() {
                    var testElement = document.createElement("div");
                    return testElement.style.cssText = "position: fixed; top: 0; height: 100vh; pointer-events: none;", 
                    document.documentElement.insertBefore(testElement, document.documentElement.firstChild), 
                    testElement;
                }
                function removeTestElement(element) {
                    document.documentElement.removeChild(element);
                }
                function checkSizes() {
                    var vhTest = createTestElement(), windowHeight = window.innerHeight, vh = vhTest.offsetHeight, offset = vh - windowHeight;
                    return removeTestElement(vhTest), {
                        vh,
                        windowHeight,
                        offset,
                        isNeeded: 0 !== offset,
                        value: 0
                    };
                }
                function noop() {}
                function computeDifference() {
                    var sizes = checkSizes();
                    return sizes.value = sizes.offset, sizes;
                }
                function redefineVhUnit() {
                    var sizes = checkSizes();
                    return sizes.value = .01 * sizes.windowHeight, sizes;
                }
                var methods = Object.freeze({
                    noop,
                    computeDifference,
                    redefineVhUnit
                });
                function isString(text) {
                    return "string" == typeof text && text.length > 0;
                }
                function isFunction(f) {
                    return "function" == typeof f;
                }
                var defaultOptions = Object.freeze({
                    cssVarName: "vh-offset",
                    redefineVh: !1,
                    method: computeDifference,
                    force: !1,
                    bind: !0,
                    updateOnTouch: !1,
                    onUpdate: noop
                });
                function getOptions(options) {
                    if (isString(options)) return __assign({}, defaultOptions, {
                        cssVarName: options
                    });
                    if ("object" != typeof options) return defaultOptions;
                    var finalOptions = {
                        force: !0 === options.force,
                        bind: !1 !== options.bind,
                        updateOnTouch: !0 === options.updateOnTouch,
                        onUpdate: isFunction(options.onUpdate) ? options.onUpdate : noop
                    }, redefineVh = !0 === options.redefineVh;
                    return finalOptions.method = methods[redefineVh ? "redefineVhUnit" : "computeDifference"], 
                    finalOptions.cssVarName = isString(options.cssVarName) ? options.cssVarName : redefineVh ? "vh" : defaultOptions.cssVarName, 
                    finalOptions;
                }
                var passiveSupported = !1, eventListeners = [];
                try {
                    var options = Object.defineProperty({}, "passive", {
                        get: function() {
                            passiveSupported = !0;
                        }
                    });
                    window.addEventListener("test", options, options), window.removeEventListener("test", options, options);
                } catch (err) {
                    passiveSupported = !1;
                }
                function addListener(eventName, callback) {
                    eventListeners.push({
                        eventName,
                        callback
                    }), window.addEventListener(eventName, callback, !!passiveSupported && {
                        passive: !0
                    });
                }
                function removeAll() {
                    eventListeners.forEach((function(config) {
                        window.removeEventListener(config.eventName, config.callback);
                    })), eventListeners = [];
                }
                function updateCssVar(cssVarName, result) {
                    document.documentElement.style.setProperty("--" + cssVarName, result.value + "px");
                }
                function formatResult(sizes, options) {
                    return __assign({}, sizes, {
                        unbind: removeAll,
                        recompute: options.method
                    });
                }
                function vhCheck(options) {
                    var config = Object.freeze(getOptions(options)), result = formatResult(config.method(), config);
                    if (!result.isNeeded && !config.force) return result;
                    if (updateCssVar(config.cssVarName, result), config.onUpdate(result), !config.bind) return result;
                    function onWindowChange() {
                        window.requestAnimationFrame((function() {
                            var sizes = config.method();
                            updateCssVar(config.cssVarName, sizes), config.onUpdate(formatResult(sizes, config));
                        }));
                    }
                    return result.unbind(), addListener("orientationchange", onWindowChange), config.updateOnTouch && addListener("touchmove", onWindowChange), 
                    result;
                }
                return vhCheck;
            }();
        }
    }, __webpack_module_cache__ = {};
    function __webpack_require__(moduleId) {
        var cachedModule = __webpack_module_cache__[moduleId];
        if (void 0 !== cachedModule) return cachedModule.exports;
        var module = __webpack_module_cache__[moduleId] = {
            exports: {}
        };
        return __webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__), 
        module.exports;
    }
    __webpack_require__.n = module => {
        var getter = module && module.__esModule ? () => module.default : () => module;
        return __webpack_require__.d(getter, {
            a: getter
        }), getter;
    }, __webpack_require__.d = (exports, definition) => {
        for (var key in definition) __webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key) && Object.defineProperty(exports, key, {
            enumerable: !0,
            get: definition[key]
        });
    }, __webpack_require__.o = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop), 
    (() => {
        "use strict";
        var vh_check__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8712);
        __webpack_require__.n(vh_check__WEBPACK_IMPORTED_MODULE_0__)()({
            cssVarName: "ag-vh-offset"
        });
        window.addEventListener("age_gate_shown", (function() {
            if (!navigator.cookieEnabled) {
                var cookies = age_gate_common.cookies;
                document.querySelector(".age-gate-form, .age-gate__form").insertAdjacentHTML("afterbegin", '<p class="age-gate__error">'.concat(cookies, "</p>"));
            }
        }));
    })();
})();