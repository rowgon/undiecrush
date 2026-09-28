(() => {
    "use strict";
    var __webpack_require__ = {};
    __webpack_require__.g = function() {
        if ("object" == typeof globalThis) return globalThis;
        try {
            return this || new Function("return this")();
        } catch (e) {
            if ("object" == typeof window) return window;
        }
    }();
    const build_module_validateNamespace = function(namespace) {
        return "string" == typeof namespace && "" !== namespace && !!/^[a-zA-Z][a-zA-Z0-9_.\-\/]*$/.test(namespace);
    };
    const build_module_validateHookName = function(hookName) {
        return "string" == typeof hookName && "" !== hookName && (!/^__/.test(hookName) && !!/^[a-zA-Z][a-zA-Z0-9_.-]*$/.test(hookName));
    };
    const build_module_createAddHook = function(hooks, storeKey) {
        return function(hookName, namespace, callback, priority = 10) {
            const hooksStore = hooks[storeKey];
            if (!build_module_validateHookName(hookName)) return;
            if (!build_module_validateNamespace(namespace)) return;
            if ("function" != typeof callback) return;
            if ("number" != typeof priority) return;
            const handler = {
                callback,
                priority,
                namespace
            };
            if (hooksStore[hookName]) {
                const handlers = hooksStore[hookName].handlers;
                let i;
                for (i = handlers.length; i > 0 && !(priority >= handlers[i - 1].priority); i--) ;
                i === handlers.length ? handlers[i] = handler : handlers.splice(i, 0, handler), 
                hooksStore.__current.forEach((hookInfo => {
                    hookInfo.name === hookName && hookInfo.currentIndex >= i && hookInfo.currentIndex++;
                }));
            } else hooksStore[hookName] = {
                handlers: [ handler ],
                runs: 0
            };
            "hookAdded" !== hookName && hooks.doAction("hookAdded", hookName, namespace, callback, priority);
        };
    };
    const build_module_createRemoveHook = function(hooks, storeKey, removeAll = !1) {
        return function(hookName, namespace) {
            const hooksStore = hooks[storeKey];
            if (!build_module_validateHookName(hookName)) return;
            if (!removeAll && !build_module_validateNamespace(namespace)) return;
            if (!hooksStore[hookName]) return 0;
            let handlersRemoved = 0;
            if (removeAll) handlersRemoved = hooksStore[hookName].handlers.length, hooksStore[hookName] = {
                runs: hooksStore[hookName].runs,
                handlers: []
            }; else {
                const handlers = hooksStore[hookName].handlers;
                for (let i = handlers.length - 1; i >= 0; i--) handlers[i].namespace === namespace && (handlers.splice(i, 1), 
                handlersRemoved++, hooksStore.__current.forEach((hookInfo => {
                    hookInfo.name === hookName && hookInfo.currentIndex >= i && hookInfo.currentIndex--;
                })));
            }
            return "hookRemoved" !== hookName && hooks.doAction("hookRemoved", hookName, namespace), 
            handlersRemoved;
        };
    };
    const build_module_createHasHook = function(hooks, storeKey) {
        return function(hookName, namespace) {
            const hooksStore = hooks[storeKey];
            return void 0 !== namespace ? hookName in hooksStore && hooksStore[hookName].handlers.some((hook => hook.namespace === namespace)) : hookName in hooksStore;
        };
    };
    const build_module_createRunHook = function(hooks, storeKey, returnFirstArg = !1) {
        return function(hookName, ...args) {
            const hooksStore = hooks[storeKey];
            hooksStore[hookName] || (hooksStore[hookName] = {
                handlers: [],
                runs: 0
            }), hooksStore[hookName].runs++;
            const handlers = hooksStore[hookName].handlers;
            if (!handlers || !handlers.length) return returnFirstArg ? args[0] : void 0;
            const hookInfo = {
                name: hookName,
                currentIndex: 0
            };
            for (hooksStore.__current.push(hookInfo); hookInfo.currentIndex < handlers.length; ) {
                const result = handlers[hookInfo.currentIndex].callback.apply(null, args);
                returnFirstArg && (args[0] = result), hookInfo.currentIndex++;
            }
            return hooksStore.__current.pop(), returnFirstArg ? args[0] : void 0;
        };
    };
    const build_module_createCurrentHook = function(hooks, storeKey) {
        return function() {
            var _hooksStore$__current;
            const hooksStore = hooks[storeKey];
            return null !== (_hooksStore$__current = hooksStore.__current[hooksStore.__current.length - 1]?.name) && void 0 !== _hooksStore$__current ? _hooksStore$__current : null;
        };
    };
    const build_module_createDoingHook = function(hooks, storeKey) {
        return function(hookName) {
            const hooksStore = hooks[storeKey];
            return void 0 === hookName ? void 0 !== hooksStore.__current[0] : !!hooksStore.__current[0] && hookName === hooksStore.__current[0].name;
        };
    };
    const build_module_createDidHook = function(hooks, storeKey) {
        return function(hookName) {
            const hooksStore = hooks[storeKey];
            if (build_module_validateHookName(hookName)) return hooksStore[hookName] && hooksStore[hookName].runs ? hooksStore[hookName].runs : 0;
        };
    };
    class _Hooks {
        constructor() {
            this.actions = Object.create(null), this.actions.__current = [], this.filters = Object.create(null), 
            this.filters.__current = [], this.addAction = build_module_createAddHook(this, "actions"), 
            this.addFilter = build_module_createAddHook(this, "filters"), this.removeAction = build_module_createRemoveHook(this, "actions"), 
            this.removeFilter = build_module_createRemoveHook(this, "filters"), this.hasAction = build_module_createHasHook(this, "actions"), 
            this.hasFilter = build_module_createHasHook(this, "filters"), this.removeAllActions = build_module_createRemoveHook(this, "actions", !0), 
            this.removeAllFilters = build_module_createRemoveHook(this, "filters", !0), this.doAction = build_module_createRunHook(this, "actions"), 
            this.applyFilters = build_module_createRunHook(this, "filters", !0), this.currentAction = build_module_createCurrentHook(this, "actions"), 
            this.currentFilter = build_module_createCurrentHook(this, "filters"), this.doingAction = build_module_createDoingHook(this, "actions"), 
            this.doingFilter = build_module_createDoingHook(this, "filters"), this.didAction = build_module_createDidHook(this, "actions"), 
            this.didFilter = build_module_createDidHook(this, "filters");
        }
    }
    const build_module_createHooks = function() {
        return new _Hooks;
    }, defaultHooks = build_module_createHooks(), {addAction, addFilter, removeAction, removeFilter, hasAction, hasFilter, removeAllActions, removeAllFilters, doAction, applyFilters, currentAction, currentFilter, doingAction, doingFilter, didAction, didFilter, actions, filters} = defaultHooks;
    __webpack_require__.g.AgeGateHooks = build_module_createHooks();
})();