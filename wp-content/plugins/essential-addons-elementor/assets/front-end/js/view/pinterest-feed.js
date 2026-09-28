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
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/view/pinterest-feed.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/js/view/pinterest-feed.js":
/*!***************************************!*\
  !*** ./src/js/view/pinterest-feed.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("var eaelPfSwiperLoader = function eaelPfSwiperLoader(swiperElement, swiperConfig) {\n  if (\"undefined\" === typeof Swiper || \"function\" === typeof Swiper) {\n    var asyncSwiper = elementorFrontend.utils.swiper;\n    return new asyncSwiper(swiperElement, swiperConfig).then(function (instance) {\n      return instance;\n    });\n  }\n  return new Promise(function (resolve) {\n    return resolve(new Swiper(swiperElement, swiperConfig));\n  });\n};\njQuery(window).on(\"elementor/frontend/init\", function () {\n  var PinterestFeed = function PinterestFeed($scope, $) {\n    var isEditMode = elementorFrontend.isEditMode();\n    var force_square = function force_square() {\n      var $item = $(\".eael-pinterest-feed-square-img .eael-pinterest-feed-item\", $scope);\n      var itemWidth = $item.width();\n      if (itemWidth > 0) {\n        $(\".eael-pinterest-feed-item-inner\", $scope).css(\"max-height\", itemWidth);\n      }\n    };\n    if (isEditMode) {\n      var pinterestEl = document.querySelector(\"#\" + $scope.attr(\"id\") + \" .eael-pinterest-feed-square-img .eael-pinterest-feed-item\");\n      if (pinterestEl) {\n        new ResizeObserver(function () {\n          force_square();\n        }).observe(pinterestEl);\n      }\n    }\n    var $feed = $(\".eael-pinterest-feed\", $scope);\n    if ($feed.hasClass(\"eael-pinterest-feed-mode-slider\")) {\n      var slidesDesktop = parseInt($feed.data(\"slides-desktop\")) || 3;\n      var slidesTablet = parseInt($feed.data(\"slides-tablet\")) || 2;\n      var slidesMobile = parseInt($feed.data(\"slides-mobile\")) || 1;\n      var gap = parseInt($feed.data(\"gap\")) || 20;\n      var effect = $feed.data(\"effect\") || \"slide\";\n      var speed = parseInt($feed.data(\"speed\")) || 600;\n      var loop = parseInt($feed.data(\"loop\")) === 1;\n      var autoplay = parseInt($feed.data(\"autoplay\")) === 1;\n      var autoplayDelay = parseInt($feed.data(\"autoplay-delay\")) || 3000;\n      var pauseOnHover = parseInt($feed.data(\"pause-on-hover\")) === 1;\n      var grabCursor = parseInt($feed.data(\"grab-cursor\")) === 1;\n      var paginationEl = $feed.data(\"pagination\") || null;\n      var arrowNext = $feed.data(\"arrow-next\") || null;\n      var arrowPrev = $feed.data(\"arrow-prev\") || null;\n\n      // Swiper needs slidesPerView * 2 slides to loop without visual gaps.\n      var slideCount = $feed.find(\".swiper-slide\").length;\n      if (loop && slideCount < slidesDesktop * 2) {\n        loop = false;\n      }\n      var swiperConfig = {\n        effect: effect,\n        speed: speed,\n        loop: loop,\n        grabCursor: grabCursor,\n        spaceBetween: gap,\n        autoplay: autoplay ? {\n          delay: autoplayDelay,\n          disableOnInteraction: false\n        } : false,\n        pagination: paginationEl ? {\n          el: paginationEl,\n          clickable: true\n        } : false,\n        navigation: arrowNext && arrowPrev ? {\n          nextEl: arrowNext,\n          prevEl: arrowPrev\n        } : false\n      };\n      if (effect === \"slide\" || effect === \"coverflow\") {\n        swiperConfig.slidesPerView = slidesDesktop;\n        swiperConfig.slidesPerGroup = slidesDesktop;\n        swiperConfig.breakpoints = {\n          1024: {\n            slidesPerView: slidesDesktop,\n            slidesPerGroup: slidesDesktop,\n            spaceBetween: gap\n          },\n          768: {\n            slidesPerView: slidesTablet,\n            slidesPerGroup: slidesTablet,\n            spaceBetween: gap\n          },\n          320: {\n            slidesPerView: slidesMobile,\n            slidesPerGroup: slidesMobile,\n            spaceBetween: gap\n          }\n        };\n      } else {\n        swiperConfig.slidesPerView = 1;\n        swiperConfig.slidesPerGroup = 1;\n      }\n      eaelPfSwiperLoader($feed[0], swiperConfig).then(function (swiper) {\n        if (pauseOnHover && autoplay) {\n          $feed[0].addEventListener(\"mouseenter\", function () {\n            swiper.autoplay.stop();\n          });\n          $feed[0].addEventListener(\"mouseleave\", function () {\n            swiper.autoplay.start();\n          });\n        }\n        swiper.update();\n      });\n      return;\n    }\n    force_square();\n    $(window).on(\"resize.pf-\" + $scope.attr(\"id\"), force_square);\n\n    // Namespaced + unbind-before-bind so Elementor's editor-mode re-init can't stack handlers.\n    $(\".eael-load-more-button\", $scope).off(\"click.eaelPf\").on(\"click.eaelPf\", function (e) {\n      e.preventDefault();\n      var $btn = $(this),\n        $span = $(\"span\", $btn),\n        $origText = $span.html(),\n        widget_id = $btn.data(\"widget-id\"),\n        post_id = $btn.data(\"post-id\"),\n        settings = $btn.data(\"settings\"),\n        perPage = parseInt($btn.attr(\"data-per-page\"), 10) || 12,\n        $feed = $(\".eael-pinterest-feed\", $scope),\n        // Derive page from DOM count — jQuery .data() state doesn't survive editor re-renders.\n        visible = $feed.children(\".eael-pinterest-feed-item\").length,\n        page = Math.ceil(visible / perPage);\n      $btn.addClass(\"button--loading\");\n      $span.html(localize.i18n.loading);\n      $.ajax({\n        url: localize.ajaxurl,\n        type: \"post\",\n        data: {\n          action: \"pinterest_feed_load_more\",\n          security: localize.nonce,\n          page: page,\n          post_id: post_id,\n          widget_id: widget_id,\n          settings: settings\n        },\n        success: function success(response) {\n          if (!response.html && !response.num_pages) {\n            $btn.removeClass(\"button--loading\").prop(\"disabled\", false).removeAttr(\"disabled\");\n            $span.html($origText);\n            return;\n          }\n          var $html = $(response.html);\n          var $liveBtn = $(\".eael-load-more-button\", $scope);\n          $feed.append($html);\n          force_square();\n          if (response.num_pages > page) {\n            // Elementor editor sets a native `disabled` attr on buttons; clear both refs in case DOM was swapped.\n            $btn.removeClass(\"button--loading\").prop(\"disabled\", false).removeAttr(\"disabled\");\n            $liveBtn.removeClass(\"button--loading\").prop(\"disabled\", false).removeAttr(\"disabled\");\n            $span.html($origText);\n            $(\"span\", $liveBtn).html($origText);\n          } else {\n            $btn.remove();\n          }\n        },\n        error: function error() {\n          $btn.removeClass(\"button--loading\").prop(\"disabled\", false).removeAttr(\"disabled\");\n          $span.html($origText);\n        }\n      });\n    });\n    var refreshLayout = function refreshLayout() {\n      force_square();\n    };\n    eael.hooks.addAction(\"ea-lightbox-triggered\", \"ea\", refreshLayout);\n    eael.hooks.addAction(\"ea-advanced-tabs-triggered\", \"ea\", refreshLayout);\n    eael.hooks.addAction(\"ea-advanced-accordion-triggered\", \"ea\", refreshLayout);\n    eael.hooks.addAction(\"ea-toggle-triggered\", \"ea\", refreshLayout);\n  };\n  elementorFrontend.hooks.addAction(\"frontend/element_ready/eael-pinterest-feed.default\", PinterestFeed);\n});\n\n//# sourceURL=webpack:///./src/js/view/pinterest-feed.js?");

/***/ })

/******/ });