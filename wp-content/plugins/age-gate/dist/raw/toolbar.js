/*! For license information please see toolbar.js.LICENSE.txt */
(() => {
    "use strict";
    function js_cookie_assign(target) {
        for (var i = 1; i < arguments.length; i++) {
            var source = arguments[i];
            for (var key in source) target[key] = source[key];
        }
        return target;
    }
    var api = function init(converter, defaultAttributes) {
        function set(name, value, attributes) {
            if ("undefined" != typeof document) {
                "number" == typeof (attributes = js_cookie_assign({}, defaultAttributes, attributes)).expires && (attributes.expires = new Date(Date.now() + 864e5 * attributes.expires)), 
                attributes.expires && (attributes.expires = attributes.expires.toUTCString()), name = encodeURIComponent(name).replace(/%(2[346B]|5E|60|7C)/g, decodeURIComponent).replace(/[()]/g, escape);
                var stringifiedAttributes = "";
                for (var attributeName in attributes) attributes[attributeName] && (stringifiedAttributes += "; " + attributeName, 
                !0 !== attributes[attributeName] && (stringifiedAttributes += "=" + attributes[attributeName].split(";")[0]));
                return document.cookie = name + "=" + converter.write(value, name) + stringifiedAttributes;
            }
        }
        return Object.create({
            set,
            get: function(name) {
                if ("undefined" != typeof document && (!arguments.length || name)) {
                    for (var cookies = document.cookie ? document.cookie.split("; ") : [], jar = {}, i = 0; i < cookies.length; i++) {
                        var parts = cookies[i].split("="), value = parts.slice(1).join("=");
                        try {
                            var found = decodeURIComponent(parts[0]);
                            if (jar[found] = converter.read(value, found), name === found) break;
                        } catch (e) {}
                    }
                    return name ? jar[name] : jar;
                }
            },
            remove: function(name, attributes) {
                set(name, "", js_cookie_assign({}, attributes, {
                    expires: -1
                }));
            },
            withAttributes: function(attributes) {
                return init(this.converter, js_cookie_assign({}, this.attributes, attributes));
            },
            withConverter: function(converter) {
                return init(js_cookie_assign({}, this.converter, converter), this.attributes);
            }
        }, {
            attributes: {
                value: Object.freeze(defaultAttributes)
            },
            converter: {
                value: Object.freeze(converter)
            }
        });
    }({
        read: function(value) {
            return '"' === value[0] && (value = value.slice(1, -1)), value.replace(/(%[\dA-F]{2})+/gi, decodeURIComponent);
        },
        write: function(value) {
            return encodeURIComponent(value).replace(/%(2[346BF]|3[AC-F]|40|5[BDE]|60|7[BCD])/g, decodeURIComponent);
        }
    }, {
        path: "/"
    });
    window.addEventListener("DOMContentLoaded", (function() {
        var selector, eventType, childSelector, eventHandler;
        selector = "#wp-admin-bar-age-gate-toggle", eventType = "click", childSelector = ".ab-item", 
        eventHandler = function(e) {
            e.preventDefault();
            var ls = new URLSearchParams(e.target.href).get("ls"), _window = window, ag_cookie_domain = _window.ag_cookie_domain, ag_cookie_name = _window.ag_cookie_name;
            if (ls) if (localStorage.getItem(ag_cookie_name)) localStorage.removeItem(ag_cookie_name); else {
                var currentTime = (new Date).getTime(), item = {
                    value: 99,
                    expires: new Date(currentTime + 36e5).getTime()
                };
                localStorage.setItem(ag_cookie_name, JSON.stringify(item));
            } else api.get(ag_cookie_name) ? ((new FormData).append("action", "ag_clear_cookie"), 
            api.set(ag_cookie_name, 1, {
                path: "/",
                domain: ag_cookie_domain,
                expires: -1,
                secure: !!window.location.protocol.match(/https/),
                sameSite: !!window.location.protocol.match(/https/) && "None"
            })) : api.set(ag_cookie_name, "99", {
                path: "/",
                domain: ag_cookie_domain,
                secure: !!window.location.protocol.match(/https/),
                sameSite: !!window.location.protocol.match(/https/) && "None"
            });
            window.location.reload();
        }, Array.from(document.querySelectorAll(selector)).forEach((function(element) {
            element.addEventListener(eventType, (function(eventOnElement) {
                eventOnElement.target.matches(childSelector) && eventHandler(eventOnElement);
            }));
        }));
    }));
})();