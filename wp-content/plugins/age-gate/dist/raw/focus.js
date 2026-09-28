/*! For license information please see focus.js.LICENSE.txt */
(() => {
    "use strict";
    var candidateSelectors = [ "input", "select", "textarea", "a[href]", "button", "[tabindex]:not(slot)", "audio[controls]", "video[controls]", '[contenteditable]:not([contenteditable="false"])', "details>summary:first-of-type", "details" ], candidateSelector = candidateSelectors.join(","), NoElement = "undefined" == typeof Element, matches = NoElement ? function() {} : Element.prototype.matches || Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector, getRootNode = !NoElement && Element.prototype.getRootNode ? function(element) {
        return element.getRootNode();
    } : function(element) {
        return element.ownerDocument;
    }, getCandidates = function(el, includeContainer, filter) {
        var candidates = Array.prototype.slice.apply(el.querySelectorAll(candidateSelector));
        return includeContainer && matches.call(el, candidateSelector) && candidates.unshift(el), 
        candidates = candidates.filter(filter);
    }, getCandidatesIteratively = function getCandidatesIteratively(elements, includeContainer, options) {
        for (var candidates = [], elementsToCheck = Array.from(elements); elementsToCheck.length; ) {
            var element = elementsToCheck.shift();
            if ("SLOT" === element.tagName) {
                var assigned = element.assignedElements(), nestedCandidates = getCandidatesIteratively(assigned.length ? assigned : element.children, !0, options);
                options.flatten ? candidates.push.apply(candidates, nestedCandidates) : candidates.push({
                    scope: element,
                    candidates: nestedCandidates
                });
            } else {
                matches.call(element, candidateSelector) && options.filter(element) && (includeContainer || !elements.includes(element)) && candidates.push(element);
                var shadowRoot = element.shadowRoot || "function" == typeof options.getShadowRoot && options.getShadowRoot(element), validShadowRoot = !options.shadowRootFilter || options.shadowRootFilter(element);
                if (shadowRoot && validShadowRoot) {
                    var _nestedCandidates = getCandidatesIteratively(!0 === shadowRoot ? element.children : shadowRoot.children, !0, options);
                    options.flatten ? candidates.push.apply(candidates, _nestedCandidates) : candidates.push({
                        scope: element,
                        candidates: _nestedCandidates
                    });
                } else elementsToCheck.unshift.apply(elementsToCheck, element.children);
            }
        }
        return candidates;
    }, getTabindex = function(node, isScope) {
        return node.tabIndex < 0 && (isScope || /^(AUDIO|VIDEO|DETAILS)$/.test(node.tagName) || node.isContentEditable) && isNaN(parseInt(node.getAttribute("tabindex"), 10)) ? 0 : node.tabIndex;
    }, sortOrderedTabbables = function(a, b) {
        return a.tabIndex === b.tabIndex ? a.documentOrder - b.documentOrder : a.tabIndex - b.tabIndex;
    }, isInput = function(node) {
        return "INPUT" === node.tagName;
    }, isNonTabbableRadio = function(node) {
        return function(node) {
            return isInput(node) && "radio" === node.type;
        }(node) && !function(node) {
            if (!node.name) return !0;
            var radioSet, radioScope = node.form || getRootNode(node), queryRadios = function(name) {
                return radioScope.querySelectorAll('input[type="radio"][name="' + name + '"]');
            };
            if ("undefined" != typeof window && void 0 !== window.CSS && "function" == typeof window.CSS.escape) radioSet = queryRadios(window.CSS.escape(node.name)); else try {
                radioSet = queryRadios(node.name);
            } catch (err) {
                return !1;
            }
            var checked = function(nodes, form) {
                for (var i = 0; i < nodes.length; i++) if (nodes[i].checked && nodes[i].form === form) return nodes[i];
            }(radioSet, node.form);
            return !checked || checked === node;
        }(node);
    }, isZeroArea = function(node) {
        var _node$getBoundingClie = node.getBoundingClientRect(), width = _node$getBoundingClie.width, height = _node$getBoundingClie.height;
        return 0 === width && 0 === height;
    }, isNodeMatchingSelectorFocusable = function(options, node) {
        return !(node.disabled || function(node) {
            return isInput(node) && "hidden" === node.type;
        }(node) || function(node, _ref) {
            var displayCheck = _ref.displayCheck, getShadowRoot = _ref.getShadowRoot;
            if ("hidden" === getComputedStyle(node).visibility) return !0;
            var nodeUnderDetails = matches.call(node, "details>summary:first-of-type") ? node.parentElement : node;
            if (matches.call(nodeUnderDetails, "details:not([open]) *")) return !0;
            var nodeRootHost = getRootNode(node).host, nodeIsAttached = (null == nodeRootHost ? void 0 : nodeRootHost.ownerDocument.contains(nodeRootHost)) || node.ownerDocument.contains(node);
            if (displayCheck && "full" !== displayCheck) {
                if ("non-zero-area" === displayCheck) return isZeroArea(node);
            } else {
                if ("function" == typeof getShadowRoot) {
                    for (var originalNode = node; node; ) {
                        var parentElement = node.parentElement, rootNode = getRootNode(node);
                        if (parentElement && !parentElement.shadowRoot && !0 === getShadowRoot(parentElement)) return isZeroArea(node);
                        node = node.assignedSlot ? node.assignedSlot : parentElement || rootNode === node.ownerDocument ? parentElement : rootNode.host;
                    }
                    node = originalNode;
                }
                if (nodeIsAttached) return !node.getClientRects().length;
            }
            return !1;
        }(node, options) || function(node) {
            return "DETAILS" === node.tagName && Array.prototype.slice.apply(node.children).some((function(child) {
                return "SUMMARY" === child.tagName;
            }));
        }(node) || function(node) {
            if (/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(node.tagName)) for (var parentNode = node.parentElement; parentNode; ) {
                if ("FIELDSET" === parentNode.tagName && parentNode.disabled) {
                    for (var i = 0; i < parentNode.children.length; i++) {
                        var child = parentNode.children.item(i);
                        if ("LEGEND" === child.tagName) return !!matches.call(parentNode, "fieldset[disabled] *") || !child.contains(node);
                    }
                    return !0;
                }
                parentNode = parentNode.parentElement;
            }
            return !1;
        }(node));
    }, isNodeMatchingSelectorTabbable = function(options, node) {
        return !(isNonTabbableRadio(node) || getTabindex(node) < 0 || !isNodeMatchingSelectorFocusable(options, node));
    }, isValidShadowRootTabbable = function(shadowHostNode) {
        var tabIndex = parseInt(shadowHostNode.getAttribute("tabindex"), 10);
        return !!(isNaN(tabIndex) || tabIndex >= 0);
    }, sortByOrder = function sortByOrder(candidates) {
        var regularTabbables = [], orderedTabbables = [];
        return candidates.forEach((function(item, i) {
            var isScope = !!item.scope, element = isScope ? item.scope : item, candidateTabindex = getTabindex(element, isScope), elements = isScope ? sortByOrder(item.candidates) : element;
            0 === candidateTabindex ? isScope ? regularTabbables.push.apply(regularTabbables, elements) : regularTabbables.push(element) : orderedTabbables.push({
                documentOrder: i,
                tabIndex: candidateTabindex,
                item,
                isScope,
                content: elements
            });
        })), orderedTabbables.sort(sortOrderedTabbables).reduce((function(acc, sortable) {
            return sortable.isScope ? acc.push.apply(acc, sortable.content) : acc.push(sortable.content), 
            acc;
        }), []).concat(regularTabbables);
    }, tabbable = function(el, options) {
        var candidates;
        return candidates = (options = options || {}).getShadowRoot ? getCandidatesIteratively([ el ], options.includeContainer, {
            filter: isNodeMatchingSelectorTabbable.bind(null, options),
            flatten: !1,
            getShadowRoot: options.getShadowRoot,
            shadowRootFilter: isValidShadowRootTabbable
        }) : getCandidates(el, options.includeContainer, isNodeMatchingSelectorTabbable.bind(null, options)), 
        sortByOrder(candidates);
    }, isTabbable = function(node, options) {
        if (options = options || {}, !node) throw new Error("No node provided");
        return !1 !== matches.call(node, candidateSelector) && isNodeMatchingSelectorTabbable(options, node);
    }, focusableCandidateSelector = candidateSelectors.concat("iframe").join(","), isFocusable = function(node, options) {
        if (options = options || {}, !node) throw new Error("No node provided");
        return !1 !== matches.call(node, focusableCandidateSelector) && isNodeMatchingSelectorFocusable(options, node);
    };
    function ownKeys(object, enumerableOnly) {
        var keys = Object.keys(object);
        if (Object.getOwnPropertySymbols) {
            var symbols = Object.getOwnPropertySymbols(object);
            enumerableOnly && (symbols = symbols.filter((function(sym) {
                return Object.getOwnPropertyDescriptor(object, sym).enumerable;
            }))), keys.push.apply(keys, symbols);
        }
        return keys;
    }
    function _objectSpread2(target) {
        for (var i = 1; i < arguments.length; i++) {
            var source = null != arguments[i] ? arguments[i] : {};
            i % 2 ? ownKeys(Object(source), !0).forEach((function(key) {
                _defineProperty(target, key, source[key]);
            })) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach((function(key) {
                Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
            }));
        }
        return target;
    }
    function _defineProperty(obj, key, value) {
        return key in obj ? Object.defineProperty(obj, key, {
            value,
            enumerable: !0,
            configurable: !0,
            writable: !0
        }) : obj[key] = value, obj;
    }
    var trapQueue, activeFocusTraps = (trapQueue = [], {
        activateTrap: function(trap) {
            if (trapQueue.length > 0) {
                var activeTrap = trapQueue[trapQueue.length - 1];
                activeTrap !== trap && activeTrap.pause();
            }
            var trapIndex = trapQueue.indexOf(trap);
            -1 === trapIndex || trapQueue.splice(trapIndex, 1), trapQueue.push(trap);
        },
        deactivateTrap: function(trap) {
            var trapIndex = trapQueue.indexOf(trap);
            -1 !== trapIndex && trapQueue.splice(trapIndex, 1), trapQueue.length > 0 && trapQueue[trapQueue.length - 1].unpause();
        }
    }), delay = function(fn) {
        return setTimeout(fn, 0);
    }, findIndex = function(arr, fn) {
        var idx = -1;
        return arr.every((function(value, i) {
            return !fn(value) || (idx = i, !1);
        })), idx;
    }, valueOrHandler = function(value) {
        for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) params[_key - 1] = arguments[_key];
        return "function" == typeof value ? value.apply(void 0, params) : value;
    }, getActualTarget = function(event) {
        return event.target.shadowRoot && "function" == typeof event.composedPath ? event.composedPath()[0] : event.target;
    }, createFocusTrap = function(elements, userOptions) {
        var trap, doc = (null == userOptions ? void 0 : userOptions.document) || document, config = _objectSpread2({
            returnFocusOnDeactivate: !0,
            escapeDeactivates: !0,
            delayInitialFocus: !0
        }, userOptions), state = {
            containers: [],
            containerGroups: [],
            tabbableGroups: [],
            nodeFocusedBeforeActivation: null,
            mostRecentlyFocusedNode: null,
            active: !1,
            paused: !1,
            delayInitialFocusTimer: void 0
        }, getOption = function(configOverrideOptions, optionName, configOptionName) {
            return configOverrideOptions && void 0 !== configOverrideOptions[optionName] ? configOverrideOptions[optionName] : config[configOptionName || optionName];
        }, findContainerIndex = function(element) {
            return state.containerGroups.findIndex((function(_ref) {
                var container = _ref.container, tabbableNodes = _ref.tabbableNodes;
                return container.contains(element) || tabbableNodes.find((function(node) {
                    return node === element;
                }));
            }));
        }, getNodeForOption = function(optionName) {
            var optionValue = config[optionName];
            if ("function" == typeof optionValue) {
                for (var _len2 = arguments.length, params = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) params[_key2 - 1] = arguments[_key2];
                optionValue = optionValue.apply(void 0, params);
            }
            if (!0 === optionValue && (optionValue = void 0), !optionValue) {
                if (void 0 === optionValue || !1 === optionValue) return optionValue;
                throw new Error("`".concat(optionName, "` was specified but was not a node, or did not return a node"));
            }
            var node = optionValue;
            if ("string" == typeof optionValue && !(node = doc.querySelector(optionValue))) throw new Error("`".concat(optionName, "` as selector refers to no known node"));
            return node;
        }, getInitialFocusNode = function() {
            var node = getNodeForOption("initialFocus");
            if (!1 === node) return !1;
            if (void 0 === node) if (findContainerIndex(doc.activeElement) >= 0) node = doc.activeElement; else {
                var firstTabbableGroup = state.tabbableGroups[0];
                node = firstTabbableGroup && firstTabbableGroup.firstTabbableNode || getNodeForOption("fallbackFocus");
            }
            if (!node) throw new Error("Your focus-trap needs to have at least one focusable element");
            return node;
        }, updateTabbableNodes = function() {
            if (state.containerGroups = state.containers.map((function(container) {
                var el, options, tabbableNodes = tabbable(container, config.tabbableOptions), focusableNodes = (el = container, 
                (options = (options = config.tabbableOptions) || {}).getShadowRoot ? getCandidatesIteratively([ el ], options.includeContainer, {
                    filter: isNodeMatchingSelectorFocusable.bind(null, options),
                    flatten: !0,
                    getShadowRoot: options.getShadowRoot
                }) : getCandidates(el, options.includeContainer, isNodeMatchingSelectorFocusable.bind(null, options)));
                return {
                    container,
                    tabbableNodes,
                    focusableNodes,
                    firstTabbableNode: tabbableNodes.length > 0 ? tabbableNodes[0] : null,
                    lastTabbableNode: tabbableNodes.length > 0 ? tabbableNodes[tabbableNodes.length - 1] : null,
                    nextTabbableNode: function(node) {
                        var forward = !(arguments.length > 1 && void 0 !== arguments[1]) || arguments[1], nodeIdx = focusableNodes.findIndex((function(n) {
                            return n === node;
                        }));
                        if (!(nodeIdx < 0)) return forward ? focusableNodes.slice(nodeIdx + 1).find((function(n) {
                            return isTabbable(n, config.tabbableOptions);
                        })) : focusableNodes.slice(0, nodeIdx).reverse().find((function(n) {
                            return isTabbable(n, config.tabbableOptions);
                        }));
                    }
                };
            })), state.tabbableGroups = state.containerGroups.filter((function(group) {
                return group.tabbableNodes.length > 0;
            })), state.tabbableGroups.length <= 0 && !getNodeForOption("fallbackFocus")) throw new Error("Your focus-trap must have at least one container with at least one tabbable node in it at all times");
        }, tryFocus = function tryFocus(node) {
            !1 !== node && node !== doc.activeElement && (node && node.focus ? (node.focus({
                preventScroll: !!config.preventScroll
            }), state.mostRecentlyFocusedNode = node, function(node) {
                return node.tagName && "input" === node.tagName.toLowerCase() && "function" == typeof node.select;
            }(node) && node.select()) : tryFocus(getInitialFocusNode()));
        }, getReturnFocusNode = function(previousActiveElement) {
            var node = getNodeForOption("setReturnFocus", previousActiveElement);
            return node || !1 !== node && previousActiveElement;
        }, checkPointerDown = function(e) {
            var target = getActualTarget(e);
            findContainerIndex(target) >= 0 || (valueOrHandler(config.clickOutsideDeactivates, e) ? trap.deactivate({
                returnFocus: config.returnFocusOnDeactivate && !isFocusable(target, config.tabbableOptions)
            }) : valueOrHandler(config.allowOutsideClick, e) || e.preventDefault());
        }, checkFocusIn = function(e) {
            var target = getActualTarget(e), targetContained = findContainerIndex(target) >= 0;
            targetContained || target instanceof Document ? targetContained && (state.mostRecentlyFocusedNode = target) : (e.stopImmediatePropagation(), 
            tryFocus(state.mostRecentlyFocusedNode || getInitialFocusNode()));
        }, checkKey = function(e) {
            if (function(e) {
                return "Escape" === e.key || "Esc" === e.key || 27 === e.keyCode;
            }(e) && !1 !== valueOrHandler(config.escapeDeactivates, e)) return e.preventDefault(), 
            void trap.deactivate();
            (function(e) {
                return "Tab" === e.key || 9 === e.keyCode;
            })(e) && function(e) {
                var target = getActualTarget(e);
                updateTabbableNodes();
                var destinationNode = null;
                if (state.tabbableGroups.length > 0) {
                    var containerIndex = findContainerIndex(target), containerGroup = containerIndex >= 0 ? state.containerGroups[containerIndex] : void 0;
                    if (containerIndex < 0) destinationNode = e.shiftKey ? state.tabbableGroups[state.tabbableGroups.length - 1].lastTabbableNode : state.tabbableGroups[0].firstTabbableNode; else if (e.shiftKey) {
                        var startOfGroupIndex = findIndex(state.tabbableGroups, (function(_ref2) {
                            var firstTabbableNode = _ref2.firstTabbableNode;
                            return target === firstTabbableNode;
                        }));
                        if (startOfGroupIndex < 0 && (containerGroup.container === target || isFocusable(target, config.tabbableOptions) && !isTabbable(target, config.tabbableOptions) && !containerGroup.nextTabbableNode(target, !1)) && (startOfGroupIndex = containerIndex), 
                        startOfGroupIndex >= 0) {
                            var destinationGroupIndex = 0 === startOfGroupIndex ? state.tabbableGroups.length - 1 : startOfGroupIndex - 1;
                            destinationNode = state.tabbableGroups[destinationGroupIndex].lastTabbableNode;
                        }
                    } else {
                        var lastOfGroupIndex = findIndex(state.tabbableGroups, (function(_ref3) {
                            var lastTabbableNode = _ref3.lastTabbableNode;
                            return target === lastTabbableNode;
                        }));
                        if (lastOfGroupIndex < 0 && (containerGroup.container === target || isFocusable(target, config.tabbableOptions) && !isTabbable(target, config.tabbableOptions) && !containerGroup.nextTabbableNode(target)) && (lastOfGroupIndex = containerIndex), 
                        lastOfGroupIndex >= 0) {
                            var _destinationGroupIndex = lastOfGroupIndex === state.tabbableGroups.length - 1 ? 0 : lastOfGroupIndex + 1;
                            destinationNode = state.tabbableGroups[_destinationGroupIndex].firstTabbableNode;
                        }
                    }
                } else destinationNode = getNodeForOption("fallbackFocus");
                destinationNode && (e.preventDefault(), tryFocus(destinationNode));
            }(e);
        }, checkClick = function(e) {
            var target = getActualTarget(e);
            findContainerIndex(target) >= 0 || valueOrHandler(config.clickOutsideDeactivates, e) || valueOrHandler(config.allowOutsideClick, e) || (e.preventDefault(), 
            e.stopImmediatePropagation());
        }, addListeners = function() {
            if (state.active) return activeFocusTraps.activateTrap(trap), state.delayInitialFocusTimer = config.delayInitialFocus ? delay((function() {
                tryFocus(getInitialFocusNode());
            })) : tryFocus(getInitialFocusNode()), doc.addEventListener("focusin", checkFocusIn, !0), 
            doc.addEventListener("mousedown", checkPointerDown, {
                capture: !0,
                passive: !1
            }), doc.addEventListener("touchstart", checkPointerDown, {
                capture: !0,
                passive: !1
            }), doc.addEventListener("click", checkClick, {
                capture: !0,
                passive: !1
            }), doc.addEventListener("keydown", checkKey, {
                capture: !0,
                passive: !1
            }), trap;
        }, removeListeners = function() {
            if (state.active) return doc.removeEventListener("focusin", checkFocusIn, !0), doc.removeEventListener("mousedown", checkPointerDown, !0), 
            doc.removeEventListener("touchstart", checkPointerDown, !0), doc.removeEventListener("click", checkClick, !0), 
            doc.removeEventListener("keydown", checkKey, !0), trap;
        };
        return (trap = {
            get active() {
                return state.active;
            },
            get paused() {
                return state.paused;
            },
            activate: function(activateOptions) {
                if (state.active) return this;
                var onActivate = getOption(activateOptions, "onActivate"), onPostActivate = getOption(activateOptions, "onPostActivate"), checkCanFocusTrap = getOption(activateOptions, "checkCanFocusTrap");
                checkCanFocusTrap || updateTabbableNodes(), state.active = !0, state.paused = !1, 
                state.nodeFocusedBeforeActivation = doc.activeElement, onActivate && onActivate();
                var finishActivation = function() {
                    checkCanFocusTrap && updateTabbableNodes(), addListeners(), onPostActivate && onPostActivate();
                };
                return checkCanFocusTrap ? (checkCanFocusTrap(state.containers.concat()).then(finishActivation, finishActivation), 
                this) : (finishActivation(), this);
            },
            deactivate: function(deactivateOptions) {
                if (!state.active) return this;
                var options = _objectSpread2({
                    onDeactivate: config.onDeactivate,
                    onPostDeactivate: config.onPostDeactivate,
                    checkCanReturnFocus: config.checkCanReturnFocus
                }, deactivateOptions);
                clearTimeout(state.delayInitialFocusTimer), state.delayInitialFocusTimer = void 0, 
                removeListeners(), state.active = !1, state.paused = !1, activeFocusTraps.deactivateTrap(trap);
                var onDeactivate = getOption(options, "onDeactivate"), onPostDeactivate = getOption(options, "onPostDeactivate"), checkCanReturnFocus = getOption(options, "checkCanReturnFocus"), returnFocus = getOption(options, "returnFocus", "returnFocusOnDeactivate");
                onDeactivate && onDeactivate();
                var finishDeactivation = function() {
                    delay((function() {
                        returnFocus && tryFocus(getReturnFocusNode(state.nodeFocusedBeforeActivation)), 
                        onPostDeactivate && onPostDeactivate();
                    }));
                };
                return returnFocus && checkCanReturnFocus ? (checkCanReturnFocus(getReturnFocusNode(state.nodeFocusedBeforeActivation)).then(finishDeactivation, finishDeactivation), 
                this) : (finishDeactivation(), this);
            },
            pause: function() {
                return state.paused || !state.active || (state.paused = !0, removeListeners()), 
                this;
            },
            unpause: function() {
                return state.paused && state.active ? (state.paused = !1, updateTabbableNodes(), 
                addListeners(), this) : this;
            },
            updateContainerElements: function(containerElements) {
                var elementsAsArray = [].concat(containerElements).filter(Boolean);
                return state.containers = elementsAsArray.map((function(element) {
                    return "string" == typeof element ? doc.querySelector(element) : element;
                })), state.active && updateTabbableNodes(), this;
            }
        }).updateContainerElements(elements), trap;
    };
    function _typeof(o) {
        return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o) {
            return typeof o;
        } : function(o) {
            return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
        }, _typeof(o);
    }
    function _defineProperties(e, r) {
        for (var t = 0; t < r.length; t++) {
            var o = r[t];
            o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), 
            Object.defineProperty(e, _toPropertyKey(o.key), o);
        }
    }
    function _createClass(e, r, t) {
        return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", {
            writable: !1
        }), e;
    }
    function _toPropertyKey(t) {
        var i = function(t, r) {
            if ("object" != _typeof(t) || !t) return t;
            var e = t[Symbol.toPrimitive];
            if (void 0 !== e) {
                var i = e.call(t, r || "default");
                if ("object" != _typeof(i)) return i;
                throw new TypeError("@@toPrimitive must return a primitive value.");
            }
            return ("string" === r ? String : Number)(t);
        }(t, "string");
        return "symbol" == _typeof(i) ? i : i + "";
    }
    new (_createClass((function AgeGateTrap() {
        var e, r, t, _this = this;
        !function(a, n) {
            if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
        }(this, AgeGateTrap), e = this, t = function() {
            _this.elements.length && (window.addEventListener("age_gate_shown", (function() {
                var options = {
                    escapeDeactivates: !1
                };
                _this.focus && Object.assign(options, {
                    initialFocus: '[name="'.concat(_this.focus, '"]')
                }), _this.trap = createFocusTrap(_this.elements, options), _this.trap.activate();
            })), window.addEventListener("age_gate_passed", (function() {
                _this.trap.deactivate();
            })));
        }, (r = _toPropertyKey(r = "init")) in e ? Object.defineProperty(e, r, {
            value: t,
            enumerable: !0,
            configurable: !0,
            writable: !0
        }) : e[r] = t;
        var _agfocus = agfocus, elements = _agfocus.elements, focus = _agfocus.focus;
        this.elements = elements, this.focus = focus, this.init();
    })));
})();