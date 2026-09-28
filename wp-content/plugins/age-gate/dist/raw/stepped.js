(() => {
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
    function _defineProperty(e, r, t) {
        return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
            value: t,
            enumerable: !0,
            configurable: !0,
            writable: !0
        }) : e[r] = t, e;
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
    var age = window.ag_stepped.age, AgeGateSteps = _createClass((function AgeGateSteps() {
        var _this = this;
        !function(a, n) {
            if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
        }(this, AgeGateSteps), _defineProperty(this, "init", (function() {
            _this.form.setAttribute("novalidate", !0), _this.inputSubmit.style.display = "none", 
            _this.wrapper.classList.add("age-gate--stepped"), _this.inputYear.addEventListener("keyup", _this.handleYear), 
            _this.inputMonth.addEventListener("keyup", _this.handleMonth);
        })), _defineProperty(this, "handleYear", (function(e) {
            _this.inputYear.value.length === _this.inputYear.maxLength && (parseInt(_this.inputYear.value) !== _this.minYear ? _this.passed || (_this.passed = !0, 
            _this.inputDay.value = "01", _this.inputMonth.value = "01", _this.form.requestSubmit()) : (_this.inputMonth.value = "", 
            _this.inputDay.value = "", _this.inputMonth.focus()));
        })), _defineProperty(this, "handleMonth", (function(e) {
            _this.inputMonth.value.length === _this.inputMonth.maxLength && (_this.inputMonth.value < _this.minMonth ? (_this.inputDay.value = "01", 
            _this.passed || _this.form.requestSubmit()) : (_this.inputSubmit.style.display = "", 
            _this.wrapper.classList.remove("age-gate--stepped"), _this.inputYear.removeEventListener("keyup", _this.handleYear), 
            _this.inputMonth.removeEventListener("keyup", _this.handleMonth), _this.inputDay.value = "", 
            _this.inputDay.focus()));
        }));
        var date = new Date;
        date.setFullYear(date.getFullYear() - age);
        var dateTime = date.toISOString().split("T").slice(0, 1)[0].split("-");
        this.form = document.querySelector(".age-gate-form, .age-gate__form"), "requestSubmit" in this.form != !1 && (this.passed = !1, 
        this.inputDay = this.form.querySelector('[name="age_gate[d]"]'), this.inputMonth = this.form.querySelector('[name="age_gate[m]"]'), 
        this.inputYear = this.form.querySelector('[name="age_gate[y]"]'), this.inputSubmit = this.form.querySelector(".age-gate-submit, .age-gate__submit"), 
        this.elements = this.form.querySelector(".age-gate-form-elements, .age-gate__form-elements"), 
        this.wrapper = this.form.parentNode, this.inputDay.tabIndex = "-1", this.inputMonth.tabIndex = "-1", 
        this.current = "y", this.userYear = 0, this.userMonth = 0, this.userDay = 0, this.minYear = parseInt(dateTime[0]), 
        this.minMonth = parseInt(dateTime[1]), this.minDay = parseInt(dateTime[2]), this.init());
    }));
    window.addEventListener("age_gate_shown", (function() {
        new AgeGateSteps;
    }));
})();