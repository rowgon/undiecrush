const eaelPfSwiperLoader = (swiperElement, swiperConfig) => {
	if ("undefined" === typeof Swiper || "function" === typeof Swiper) {
		const asyncSwiper = elementorFrontend.utils.swiper;
		return new asyncSwiper(swiperElement, swiperConfig).then((instance) => instance);
	}
	return new Promise((resolve) => resolve(new Swiper(swiperElement, swiperConfig)));
};

jQuery(window).on("elementor/frontend/init", function () {
	let PinterestFeed = function ($scope, $) {
		let isEditMode = elementorFrontend.isEditMode();

		let force_square = function () {
			let $item = $(".eael-pinterest-feed-square-img .eael-pinterest-feed-item", $scope);
			let itemWidth = $item.width();
			if (itemWidth > 0) {
				$(".eael-pinterest-feed-item-inner", $scope).css("max-height", itemWidth);
			}
		};

		if (isEditMode) {
			let pinterestEl = document.querySelector(
				"#" + $scope.attr("id") + " .eael-pinterest-feed-square-img .eael-pinterest-feed-item"
			);
			if (pinterestEl) {
				new ResizeObserver(function () {
					force_square();
				}).observe(pinterestEl);
			}
		}

		let $feed = $(".eael-pinterest-feed", $scope);

		if ($feed.hasClass("eael-pinterest-feed-mode-slider")) {
			let slidesDesktop = parseInt($feed.data("slides-desktop")) || 3;
			let slidesTablet = parseInt($feed.data("slides-tablet")) || 2;
			let slidesMobile = parseInt($feed.data("slides-mobile")) || 1;
			let gap = parseInt($feed.data("gap")) || 20;
			let effect = $feed.data("effect") || "slide";
			let speed = parseInt($feed.data("speed")) || 600;
			let loop = parseInt($feed.data("loop")) === 1;
			let autoplay = parseInt($feed.data("autoplay")) === 1;
			let autoplayDelay = parseInt($feed.data("autoplay-delay")) || 3000;
			let pauseOnHover = parseInt($feed.data("pause-on-hover")) === 1;
			let grabCursor = parseInt($feed.data("grab-cursor")) === 1;
			let paginationEl = $feed.data("pagination") || null;
			let arrowNext = $feed.data("arrow-next") || null;
			let arrowPrev = $feed.data("arrow-prev") || null;

			// Swiper needs slidesPerView * 2 slides to loop without visual gaps.
			let slideCount = $feed.find(".swiper-slide").length;
			if (loop && slideCount < slidesDesktop * 2) {
				loop = false;
			}

			let swiperConfig = {
				effect: effect,
				speed: speed,
				loop: loop,
				grabCursor: grabCursor,
				spaceBetween: gap,
				autoplay: autoplay
					? {
							delay: autoplayDelay,
							disableOnInteraction: false,
						}
					: false,
				pagination: paginationEl
					? {
							el: paginationEl,
							clickable: true,
						}
					: false,
				navigation:
					arrowNext && arrowPrev
						? {
								nextEl: arrowNext,
								prevEl: arrowPrev,
							}
						: false,
			};

			if (effect === "slide" || effect === "coverflow") {
				swiperConfig.slidesPerView = slidesDesktop;
				swiperConfig.slidesPerGroup = slidesDesktop;
				swiperConfig.breakpoints = {
					1024: { slidesPerView: slidesDesktop, slidesPerGroup: slidesDesktop, spaceBetween: gap },
					768: { slidesPerView: slidesTablet, slidesPerGroup: slidesTablet, spaceBetween: gap },
					320: { slidesPerView: slidesMobile, slidesPerGroup: slidesMobile, spaceBetween: gap },
				};
			} else {
				swiperConfig.slidesPerView = 1;
				swiperConfig.slidesPerGroup = 1;
			}

			eaelPfSwiperLoader($feed[0], swiperConfig).then(function (swiper) {
				if (pauseOnHover && autoplay) {
					$feed[0].addEventListener("mouseenter", function () {
						swiper.autoplay.stop();
					});
					$feed[0].addEventListener("mouseleave", function () {
						swiper.autoplay.start();
					});
				}
				swiper.update();
			});

			return;
		}

		force_square();
		$(window).on("resize.pf-" + $scope.attr("id"), force_square);

		// Namespaced + unbind-before-bind so Elementor's editor-mode re-init can't stack handlers.
		$(".eael-load-more-button", $scope)
			.off("click.eaelPf")
			.on("click.eaelPf", function (e) {
				e.preventDefault();

				let $btn = $(this),
					$span = $("span", $btn),
					$origText = $span.html(),
					widget_id = $btn.data("widget-id"),
					post_id = $btn.data("post-id"),
					settings = $btn.data("settings"),
					perPage = parseInt($btn.attr("data-per-page"), 10) || 12,
					$feed = $(".eael-pinterest-feed", $scope),
					// Derive page from DOM count — jQuery .data() state doesn't survive editor re-renders.
					visible = $feed.children(".eael-pinterest-feed-item").length,
					page = Math.ceil(visible / perPage);

				$btn.addClass("button--loading");
				$span.html(localize.i18n.loading);

				$.ajax({
					url: localize.ajaxurl,
					type: "post",
					data: {
						action: "pinterest_feed_load_more",
						security: localize.nonce,
						page: page,
						post_id: post_id,
						widget_id: widget_id,
						settings: settings,
					},
					success: function (response) {
						if (!response.html && !response.num_pages) {
							$btn.removeClass("button--loading").prop("disabled", false).removeAttr("disabled");
							$span.html($origText);
							return;
						}

						let $html = $(response.html);
						let $liveBtn = $(".eael-load-more-button", $scope);
						$feed.append($html);
						force_square();

						if (response.num_pages > page) {
							// Elementor editor sets a native `disabled` attr on buttons; clear both refs in case DOM was swapped.
							$btn.removeClass("button--loading").prop("disabled", false).removeAttr("disabled");
							$liveBtn.removeClass("button--loading").prop("disabled", false).removeAttr("disabled");
							$span.html($origText);
							$("span", $liveBtn).html($origText);
						} else {
							$btn.remove();
						}
					},
					error: function () {
						$btn.removeClass("button--loading").prop("disabled", false).removeAttr("disabled");
						$span.html($origText);
					},
				});
			});

		let refreshLayout = function () {
			force_square();
		};
		eael.hooks.addAction("ea-lightbox-triggered", "ea", refreshLayout);
		eael.hooks.addAction("ea-advanced-tabs-triggered", "ea", refreshLayout);
		eael.hooks.addAction("ea-advanced-accordion-triggered", "ea", refreshLayout);
		eael.hooks.addAction("ea-toggle-triggered", "ea", refreshLayout);
	};

	elementorFrontend.hooks.addAction("frontend/element_ready/eael-pinterest-feed.default", PinterestFeed);
});
