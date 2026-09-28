/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/edit/pinterest-feed.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/edit/pinterest-feed.js":
/*!***************************************!*\
  !*** ./src/js/edit/pinterest-feed.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("(function ($) {\n  \"use strict\";\n\n  $(window).on(\"elementor:init\", function () {\n    elementor.channels.editor.on(\"eaelPinterestFetchBoards\", function (view) {\n      var model = view.model || view.options.model;\n      var settings = model.get(\"settings\");\n      var token = settings.get(\"eael_pinterest_feed_access_token\");\n      if (!token) {\n        elementor.notifications.showToast({\n          message: \"Please enter an Access Token first.\",\n          type: \"warning\"\n        });\n        return;\n      }\n      elementor.notifications.showToast({\n        message: \"Fetching boards...\",\n        type: \"info\"\n      });\n      $.ajax({\n        url: localize.ajaxurl,\n        type: \"POST\",\n        data: {\n          action: \"eael_pinterest_fetch_boards\",\n          security: localize.nonce,\n          access_token: token\n        },\n        success: function success(response) {\n          if (response.success && response.data) {\n            var control = model.controls.eael_pinterest_feed_board_id;\n            if (control) {\n              control.options = response.data;\n              view.renderUI();\n            }\n            elementor.notifications.showToast({\n              message: Object.keys(response.data).length + \" boards loaded!\",\n              type: \"success\"\n            });\n          } else {\n            elementor.notifications.showToast({\n              message: response.data || \"No boards found. Check your token.\",\n              type: \"error\"\n            });\n          }\n        },\n        error: function error() {\n          elementor.notifications.showToast({\n            message: \"Failed to fetch boards. Please try again.\",\n            type: \"error\"\n          });\n        }\n      });\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack:///./src/js/edit/pinterest-feed.js?");

/***/ })

/******/ });