/**
 * EA Advanced Slider — view (frontend + editor preview iframe)
 * Wrapped in an IIFE so AdvancedSliderHandler is module-local and cannot collide
 * with the same identifier in src/js/edit/advanced-slider.js.
 */
import {
    ADVANCED_SLIDER_EASING_CURVES,
    toAlphabet,
    toRoman,
    escapeHtml as eaelEscapeHtml,
    getPaginationType as eaelGetPaginationType,
    getPaginationPosition as eaelGetPaginationPosition,
    buildPaginationConfig as eaelBuildPaginationConfig,
    mountPaginationHost as eaelMountPaginationHost,
    swiperLoader,
} from '../_shared/advanced-slider.js';

(function () {

var AdvancedSliderHandler = function ($scope, $) {
    if (!$scope.hasClass('eael-advanced-slider')) return;

    const options = $scope.data('options');

    if (options?.effect === 'slide' && options?.direction === 'vertical' && options?.manualScrolling === 'yes') {
        initVerticalSlider($scope, options, $);
    } else if (options?.effect === 'slide' && options?.direction === 'horizontal' && options?.marquee === 'yes') {
        initMarqueeSlider($scope, options, $);
    } else {
        initSwiperSlider($scope, options, $);
    }
};

const initMarqueeSlider = function ($scope, options, $) {
    const $inner = $scope.find('> .e-con-inner');
    $inner.addClass('eael-as-track');

    let $html = $scope.html();
    if ($scope.hasClass('e-con-full')) {
        $scope.html('');
        $scope.append('<div class="eael-as-track"></div>');
    }
    let $track = $scope.find('.eael-as-track');

    if (options?.gap) {
        $track.css('gap', options?.gap + 'px');
    }

    if ($scope.hasClass('e-con-full')) {
        $track.html($html);
    }

    const duplicateTrackContent = () => {
        const $originalChildren = $track.children().clone(true);
        if (!$originalChildren.length) {
            return;
        }

        for (let i = 0; i < 4; i++) {
            $track.append($originalChildren.clone(true));
        }
    };

    duplicateTrackContent();

    // Speed (px/sec)
    const SPEED_PX_PER_SEC = options?.speed || 50;

    function setDuration() {
        // const totalWidth = $track[0].scrollWidth / 2;
        const duration = SPEED_PX_PER_SEC / 20;
        $track.css('--duration', duration + 's');
        $track.css('--flex-wrap', 'nowrap');
    }

    setDuration();
    $(window).on('resize', setDuration);

    if ('yes' === options?.pauseOnHover) {
        // Pause on hover
        $track.on('mouseenter', function () {
            $(this).css('animation-play-state', 'paused');
        });

        $track.on('mouseleave', function () {
            $(this).css('animation-play-state', 'running');
        });
    }
}

/* Pagination helpers (eaelGetPaginationType, eaelGetPaginationPosition,
   eaelEscapeHtml, eaelBuildPaginationConfig, eaelMountPaginationHost) are
   imported from ../_shared/advanced-slider.js with `as eael*` aliases so
   the existing callsites in this file don't need renaming. See Phase 6.1
   commit message for the rationale on the dedup. */

/**
 * Auto-fit slide height for 3D effects (cards / flip / coverflow).
 * Measures the natural content height of every slide, picks the max, and
 * sets the swiper container to that height so all slides fit without clipping.
 * Re-runs on window resize and after images load.
 */
const eaelAutoFitHeight = ($scope, swiper, $) => {
    const $swiperEl = $scope.children('.swiper').first();
    if (!$swiperEl.length) return;
    const $slides = $swiperEl.find('> .swiper-wrapper > .swiper-slide');
    if (!$slides.length) return;

    const measure = () => {
        // Snapshot the existing inline styles so we can restore them.
        const slidesPrevHeight = [];
        $slides.each(function () {
            slidesPrevHeight.push(this.style.height);
        });
        const swiperPrevHeight = $swiperEl[0].style.height;

        // Temporarily release height constraints so each slide reports its
        // natural content height.
        $slides.css('height', 'auto');
        $swiperEl.css('height', 'auto');
        // Force a synchronous reflow before measuring.
        // eslint-disable-next-line no-unused-expressions
        $swiperEl[0].offsetHeight;

        let maxH = 0;
        $slides.each(function () {
            const h = this.scrollHeight;
            if (h > maxH) maxH = h;
        });

        // Restore slide heights to fill the container, set container to max.
        $slides.each(function (i) {
            this.style.height = slidesPrevHeight[i] || '';
        });
        if (maxH > 0) {
            $swiperEl.css('height', maxH + 'px');
        } else {
            $swiperEl[0].style.height = swiperPrevHeight;
        }
        if (swiper && typeof swiper.update === 'function') swiper.update();
    };

    measure();
    // Re-measure when images inside slides finish loading. Debounce so a
    // burst of image loads triggers exactly one measurement, not N.
    let imgTimer;
    const debouncedMeasure = () => {
        clearTimeout(imgTimer);
        imgTimer = setTimeout(measure, 150);
    };
    $scope.find('img').each(function () {
        if (!this.complete) {
            this.addEventListener('load', debouncedMeasure, { once: true });
        }
    });
    // Re-measure on resize (debounced).
    let resizeTimer;
    $(window).off('resize.eaelAutoFit-' + ($scope.attr('id') || ''))
        .on('resize.eaelAutoFit-' + ($scope.attr('id') || ''), () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(measure, 150);
        });
};

/**
 * Time-based Autoplay Progress Bar (replicates demo V3 — Travel magazine
 * "Fade hero with timing"). Includes play/pause toggle, animated linear bar
 * that fills with autoplay countdown (resets on slide change), and a
 * "01 / 04" fraction counter. Lives outside Swiper's pagination module.
 */
const eaelBuildProgressBarNav = ($scope, options, $) => {
    const position = (options?.pagination_position || 'bottom').replace(/_/g, '-');
    /* pause/play SVG paths re-centered.
       Pause: two 4-wide bars from x=8/x=14 (was 6/14). Optical center now at
       x=12.5 instead of x=11, matching the geometric center of the 24-unit
       viewBox.
       Play: triangle from x=7, y=5 to x=18,y=12 to x=7,y=19 — the visual
       weight (centroid) of a triangle sits ~1/3 from the base, so we shift
       the path right of geometric center to make the rendered triangle
       *appear* centered in the round button.
       SVG sizing now lives in CSS (.eael-as-pb-toggle svg) so style changes
       don't require a JS re-build. */
    const $host = $(
        '<div class="eael-as-pagination eael-as-pagination--type-progress-bar eael-as-pagination--position-' + position + '">' +
            '<button type="button" class="eael-as-pb-toggle" aria-label="Pause autoplay">' +
                '<svg class="eael-as-pb-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="7" y="5" width="3.5" height="14" rx="1"/><rect x="13.5" y="5" width="3.5" height="14" rx="1"/></svg>' +
                '<svg class="eael-as-pb-play" viewBox="0 0 24 24" fill="currentColor" style="display:none" aria-hidden="true"><path d="M7.5 5.2v13.6c0 .8.9 1.3 1.6.9l11-6.8c.6-.4.6-1.4 0-1.8l-11-6.8c-.7-.4-1.6 0-1.6.9z"/></svg>' +
            '</button>' +
            '<div class="eael-as-pb-track"><div class="eael-as-pb-fill"></div></div>' +
            '<div class="eael-as-pb-counter"><strong class="eael-as-pb-current">01</strong><span class="eael-as-pb-sep">/</span><span class="eael-as-pb-total">01</span></div>' +
        '</div>'
    );
    $scope.append($host);
    return {
        $host,
        attach: (swiper) => {
            if (!swiper) return;
            const $fill    = $host.find('.eael-as-pb-fill');
            const $current = $host.find('.eael-as-pb-current');
            const $total   = $host.find('.eael-as-pb-total');
            const $toggle  = $host.find('.eael-as-pb-toggle');
            const $iconPause = $host.find('.eael-as-pb-pause');
            const $iconPlay  = $host.find('.eael-as-pb-play');

            const totalSlides = swiper.slides.length - (swiper.params.loop ? 2 * swiper.loopedSlides : 0);
            const safeTotal = Math.max(1, totalSlides);
            $total.text(String(safeTotal).padStart(2, '0'));
            $current.text(String((swiper.realIndex || 0) + 1).padStart(2, '0'));

            /* Make the fill animation work for ANY effect — not just
               fade. Previously `paused = !(swiper.autoplay && swiper.autoplay.running)`
               could latch true at attach time (e.g. coverflow/cards where
               autoplay hasn't started its first tick yet) and the fill never
               moved until the user clicked the toggle. Pin paused to the
               actual autoplay-enabled flag. If autoplay is enabled at all,
               the fill should animate; we'll start the swiper's autoplay if
               it's not already running. */
            const autoplayEnabled = !!(swiper.params.autoplay && (swiper.params.autoplay.delay || swiper.params.autoplay === true));
            const delay = (options?.autoplay_speed || (swiper.params.autoplay && swiper.params.autoplay.delay) || 3000);
            /* A slide is on screen for `delay` (autoplay dwell) PLUS `speed`
               (the slide transition) because Swiper autoplay defaults to
               waitForTransition:true. With slow/panning transitions `speed`
               can dwarf `delay` (e.g. delay 2000ms, speed 4881ms), so a fill
               that spans only `delay` finishes ~5s before the next slide
               arrives. Span the FULL visible cycle so the bar completes exactly
               as the next slide settles. */
            const transitionSpeed = swiper.params.speed || 0;
            const cycle = delay + transitionSpeed;
            let startTime = performance.now();
            let paused = !autoplayEnabled;
            // If autoplay is enabled but not yet running, kick it off so the
            // fill has a real ticking baseline.
            if (autoplayEnabled && swiper.autoplay && !swiper.autoplay.running) {
                try { swiper.autoplay.start(); } catch (e) {}
            }

            const setPausedUI = (isPaused) => {
                paused = isPaused;
                $iconPause.css('display', isPaused ? 'none' : '');
                $iconPlay.css('display',  isPaused ? '' : 'none');
                $toggle.attr('aria-label', isPaused ? 'Resume autoplay' : 'Pause autoplay');
            };
            setPausedUI(paused);

            $toggle.on('click', () => {
                if (paused) {
                    startTime = performance.now();
                    if (swiper.autoplay) swiper.autoplay.start();
                    setPausedUI(false);
                } else {
                    if (swiper.autoplay) swiper.autoplay.stop();
                    setPausedUI(true);
                }
            });

            swiper.on('slideChange', () => {
                startTime = performance.now();
                $current.text(String((swiper.realIndex || 0) + 1).padStart(2, '0'));
                // ensure the fill snaps back to 0 immediately so the
                // next cycle starts visibly from empty even if the rAF tick
                // happens to land mid-frame.
                $fill.css('width', '0%');
            });
            swiper.on('autoplayStart', () => setPausedUI(false));
            swiper.on('autoplayStop',  () => setPausedUI(true));
            swiper.on('autoplayPause', () => setPausedUI(true));
            swiper.on('autoplayResume', () => setPausedUI(false));

            /* rAF drives the fill across the full `cycle` (dwell + transition),
               not Swiper's `autoplayTimeLeft` — that event only reports the
               `delay` portion, so with long transitions the bar would finish
               while the slide is still panning. startTime resets on slideChange
               (above), so the bar empties when a slide starts moving and reaches
               100% exactly as the next slide starts moving — staying locked to
               the slider regardless of how large `speed` is. */
            const tick = () => {
                if (!paused) {
                    const elapsed = performance.now() - startTime;
                    const pct = Math.min(100, (elapsed / cycle) * 100);
                    $fill.css('width', pct + '%');
                }
                requestAnimationFrame(tick);
            };
            tick();
        }
    };
};

/**
 * Build the Thumbnails sub-Swiper. Auto-detects images per slide:
 *   1. first <img> inside the slide
 *   2. first inline background-image url(...)
 *   3. fallback to a numbered placeholder
 *
 * IMPORTANT: returns a Promise-producing factory (`getThumbsSwiperAsync`),
 * not a synchronous `new Swiper()`. On the frontend, Elementor loads Swiper
 * asynchronously via `elementorFrontend.utils.swiper`, and `window.Swiper`
 * may not be the canonical Swiper class until the loader resolves. Using
 * `swiperLoader` ensures the thumbs sub-swiper uses the SAME Swiper class
 * as the main slider, so the `mainSwiper.thumbs.swiper` reference is valid.
 */
const eaelBuildThumbnailsNav = ($scope, $slides, options, $) => {
    const position = (options?.pagination_position || 'bottom').replace(/_/g, '-');
    const $host = $('<div class="eael-as-thumbnails eael-as-thumbnails--position-' + position + '"></div>');
    const $thumbsSwiper = $('<div class="swiper eael-as-thumbnails-swiper"></div>');
    const $thumbsWrapper = $('<div class="swiper-wrapper"></div>');

    $slides.each(function (i) {
        const $slide = $(this);
        let imgSrc = $slide.find('img').first().attr('src') || '';
        if (!imgSrc) {
            const $bgEl = $slide.find('[style*="background-image"]').first();
            if ($bgEl.length) {
                const m = ($bgEl.attr('style') || '').match(/background-image\s*:\s*url\(['"]?([^'"\)]+)/);
                if (m) imgSrc = m[1];
            }
        }
        const inner = imgSrc
            ? '<img src="' + eaelEscapeHtml(imgSrc) + '" alt="">'
            : '<span class="eael-as-thumb-num">' + (i + 1) + '</span>';
        $thumbsWrapper.append('<div class="swiper-slide eael-as-thumb">' + inner + '</div>');
    });
    $thumbsSwiper.append($thumbsWrapper);
    $host.append($thumbsSwiper);
    $scope.append($host);

    return {
        $host,
        $thumbsSwiperEl: $thumbsSwiper,
        // Returns a Promise that resolves to the thumbs Swiper instance.
        getThumbsSwiperAsync: () => swiperLoader($thumbsSwiper[0], {
            slidesPerView: 'auto',
            spaceBetween: 8,
            centerInsufficientSlides: true,
            watchSlidesProgress: true,
            slideToClickedSlide: true,
        })
    };
};

/**
 * Numbered Index navigation rail. Generates 01/02/03 (number),
 * A/B/C (alphabet), or I/II/III (roman) buttons that scrub the slider.
 */
const eaelBuildNumberedIndexNav = ($scope, $slides, options, $) => {
    const format = options?.index_format || 'number';
    const $host = $('<nav class="eael-as-pagination eael-as-pagination--type-numbered-index eael-as-pagination--position-' + (options?.pagination_position || 'bottom').replace(/_/g, '-') + '" aria-label="Slider navigation"></nav>');
    $slides.each(function (i) {
        let label;
        if (format === 'alphabet') label = String.fromCharCode(65 + i);
        else if (format === 'roman') {
            const map = [[10,'X'],[9,'IX'],[5,'V'],[4,'IV'],[1,'I']];
            let n = i + 1, s = '';
            map.forEach(([v, sym]) => { while (n >= v) { s += sym; n -= v; } });
            label = s;
        }
        else label = String(i + 1).padStart(2, '0');
        $host.append('<button type="button" class="eael-as-index-item' + (i === 0 ? ' is-active' : '') + '" data-index="' + i + '">' +
            '<span class="eael-as-index-label">' + label + '</span>' +
            '<span class="eael-as-index-bar" aria-hidden="true"></span>' +
            '</button>');
    });
    $scope.append($host);
    return {
        $host,
        onSlideChange: (idx) => {
            $host.find('.eael-as-index-item').removeClass('is-active');
            $host.find('.eael-as-index-item[data-index="' + idx + '"]').addClass('is-active');
        },
        bindClicks: (swiper) => {
            $host.on('click', '.eael-as-index-item', function () {
                const idx = parseInt($(this).attr('data-index'), 10);
                if (!isNaN(idx)) swiper.slideTo(idx);
            });
        }
    };
};

/* toAlphabet, toRoman imported from ../_shared/advanced-slider.js (Phase 6.1). */

const initVerticalSlider = function ($scope, options, $) {
    let $inner = $scope.find('> .e-con-inner');
    // `.e-con` covers nested Elementor flex containers as direct children.
    // Pre-Elementor-4.x layouts used `.e-child` / `.elementor-widget`; the
    // newer flex containers render as `.e-con` and were silently missed,
    // producing zero-slide Swiper init and a raw stacked render.
    let $slides = $inner.find('> .e-child, > .elementor-column, > .elementor-widget, > .e-con');
    let $is_grid = $scope.hasClass('e-grid') || $scope.hasClass('e-con-full');

    if ($is_grid && !$slides.length) {
        $slides = $scope.find('> .e-child, > .elementor-column, > .elementor-widget, > .e-con');
        $inner = $('<div class="eael-advanced-slider-inner"></div>');
        $slides.appendTo($inner);
        $scope.append($inner);
        $scope.css('display', 'block');
    }

    $slides.addClass('eael-advanced-slider-slide');
    $inner.addClass('eael-advanced-slider-inner');

    if ('yes' === options?.indicator) {
        // Append indicator dynamically if not exist or indicator_type is not empty
        const $indicator = $('<ul id="eael-advanced-slider-' + $scope.data('id') + '" class="eael-as-indicator"></ul>');
        const mode = options?.indicator_type || '';
        $slides.each(function (i) {
            $(this).attr('data-index', i);

            let label = (mode === 'alphabet') ? toAlphabet(i)
                : (mode === 'roman') ? toRoman(i + 1)
                    : (i + 1);

            $indicator.append(
                `<li class="eael-as-indicator-index" data-index="${i}">${label}</li>`
            );
        });
        $scope.append($indicator);
    }


    const $indicators = $scope.find('.eael-as-indicator-index');

    function update(entries) {
        entries.forEach(entry => {
            const i = $(entry.target).data('index');
            $indicators.eq(i).toggleClass('expand', entry.isIntersecting);
        });
    }

    function detect(slide) {
        const options = { threshold: 0.2 };
        const io = new IntersectionObserver(update, options);
        io.observe(slide);
    }

    function init() {
        $slides.each(function () {
            detect(this);
        });
    }

    $(window).on('load', init);
}

const initSwiperSlider = function ($scope, options, $) {

    let $inner = $scope.find('> .e-con-inner');
    if (!$inner.length) {
        $inner = $scope.find('> .elementor-container, > .elementor-column-wrap > .elementor-widget-wrap, > .elementor-widget-wrap');
    }
    if (!$inner.length) {
        $inner = $scope;
    }
    const $is_grid = $scope.hasClass('e-grid') || $scope.hasClass('e-con-full');
    // `.e-con` catches nested Elementor flex containers (4.x) as direct
    // children. Without it, sliders whose children are containers rather
    // than widgets render with zero slides on the frontend.
    var $slides = $inner.find('> .e-child, > .elementor-column, > .elementor-widget, > .e-con');
    var $wrapper = $inner.find('> .swiper-wrapper');

    if ($is_grid && !$slides.length) {
        $slides = $scope.find('> .e-child, > .elementor-column, > .elementor-widget, > .e-con');
        // Wrap slides in a new swiper-wrapper
        $wrapper = $scope.find('> .swiper-wrapper');
        $scope.css('display', 'block');
    }

    if ($wrapper.length === 0) {
        $wrapper = $('<div class="swiper-wrapper"></div>');
        $slides.appendTo($wrapper);

        if ($is_grid && !$inner.length) {
            $inner = $scope;
        }

        $inner.append($wrapper);
    }

    // Add swiper container class
    $inner.addClass('swiper');

    // Make each slide valid. `.e-con` covers Elementor flex containers used as slides.
    $wrapper.find('> .e-child, > .elementor-column, > .elementor-widget, > .e-con').addClass('swiper-slide');

    const $navHost = $scope.find('.swiper-wrapper').parent();
    const sliderDirection = options?.direction || 'horizontal';
    const isCardsEffect = options?.effect === 'cards';
    const isFadeEffect = options?.effect === 'fade';
    const isFlipEffect = options?.effect === 'flip';
    const isSingleSlideEffect = isFadeEffect || isFlipEffect;
    const slidesPerViewValue = isCardsEffect ? 'auto' : (isSingleSlideEffect ? 1 : (options?.items || 3));
    const spaceBetweenValue = isCardsEffect ? (options?.gap || 0) : (isSingleSlideEffect ? 0 : (options?.gap || 10));

    // Arrows mount whenever the Arrows toggle is on — including vertical
    // direction (up/down nav). Previously gated on a non-vertical check
    // which silently dropped arrows on vertical sliders in the frontend.
    if ('yes' === options?.navigation_arrows) {
        let $left_icon = '';
        let $right_icon = '';

        if (options?.navigation_icon_left) {
            $left_icon = atob(options?.navigation_icon_left);
        }
        if (options?.navigation_icon_right) {
            $right_icon = atob(options?.navigation_icon_right);
        }

        // Append navigation & pagination dynamically if not exist
        if ($navHost.find('.swiper-button-next').length === 0) {
            if ($right_icon) {
                $navHost.append('<div class="swiper-button-next eael-as-nav-icon" aria-label="Next">' + $right_icon + '</div>');
            } else {
                $navHost.append('<div class="swiper-button-next" aria-label="Next"></div>');
            }
        }
        if ($navHost.find('.swiper-button-prev').length === 0) {
            if ($left_icon) {
                $navHost.append('<div class="swiper-button-prev eael-as-nav-icon" aria-label="Previous">' + $left_icon + '</div>');
            } else {
                $navHost.append('<div class="swiper-button-prev" aria-label="Previous"></div>');
            }
        }
    }

    /* ──────────────── Pagination (new contract) ──────────────── */
    const paginationType = eaelGetPaginationType(options);
    const paginationPosition = eaelGetPaginationPosition(options);
    let $paginationEl = null;
    let thumbnailsNav = null;
    let numberedIndexNav = null;
    let progressBarNav = null;

    // Mark the wrapper so CSS can scope styles per type/position.
    $scope
        .addClass('eael-as-pag-' + paginationType.replace(/_/g, '-'))
        .addClass('eael-as-pos-' + paginationPosition.replace(/_/g, '-'));

    const $slidesForNav = $wrapper.find('> .swiper-slide');
    if (paginationType === 'thumbnails') {
        thumbnailsNav = eaelBuildThumbnailsNav($scope, $slidesForNav, options, $);
    } else if (paginationType === 'numbered_index') {
        numberedIndexNav = eaelBuildNumberedIndexNav($scope, $slidesForNav, options, $);
    } else if (paginationType === 'progress_bar') {
        progressBarNav = eaelBuildProgressBarNav($scope, options, $);
    } else if (paginationType !== 'none') {
        $paginationEl = eaelMountPaginationHost($scope, $inner, paginationType, paginationPosition, $);
    }

    // "Inline with arrows": co-locate prev-arrow + pagination + next-arrow in a
    // single flex row. Arrows mount in $inner and the pagination host in $scope,
    // so without this they live in different parents and the position collapses
    // to look identical to "bottom". Reparent all three into one row.
    //
    // The row MUST be appended to $scope (the outer .eael-advanced-slider,
    // overflow:visible), NOT $inner (.swiper). For slide/fade effects .swiper is
    // overflow:hidden and sized to the slide height, so a row appended there
    // falls below the clip box and the arrows + pagination vanish. 3D effects
    // (.swiper overflow:visible) hid the bug. $scope is the same non-clipped
    // parent every other pagination position already mounts into.
    if (paginationPosition === 'inline_with_arrows' && 'yes' === options?.navigation_arrows) {
        const $pagHost = $scope.find('.eael-as-pagination--position-inline-with-arrows').first();
        const $prevArrow = $navHost.find('> .swiper-button-prev').first();
        const $nextArrow = $navHost.find('> .swiper-button-next').first();
        if ($pagHost.length && $prevArrow.length && $nextArrow.length) {
            // Tag the row with the pagination type so CSS can give wide-rail
            // types (progress bar) a definite width instead of shrink-wrapping.
            const $inlineRow = $('<div class="eael-as-inline-row eael-as-inline-row--' + paginationType.replace(/_/g, '-') + '"></div>');
            $inlineRow.append($prevArrow).append($pagHost).append($nextArrow);
            $scope.append($inlineRow);
        }
    }

    // Build responsive breakpoints for Swiper
    let breakpoints = {};

    if (!isCardsEffect && !isSingleSlideEffect) {
        // Check if we have breakpoints data and responsive settings
        if (typeof localize !== 'undefined' && localize.el_breakpoints && typeof localize.el_breakpoints === 'object') {
            let el_breakpoints = {}, bp_index = 0;
            const desktopBreakPoint = localize.el_breakpoints.widescreen?.is_enabled ? localize.el_breakpoints.widescreen.value - 1 : 4800;

            // Initialize base breakpoint
            el_breakpoints[bp_index] = {
                breakpoint: 0,
                slidesPerView: 0,
                spaceBetween: 0
            };
            bp_index++;

            // Add desktop breakpoint
            localize.el_breakpoints.desktop = {
                is_enabled: true,
                value: desktopBreakPoint
            };

            // Process each device breakpoint
            ['mobile', 'mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'desktop', 'widescreen'].forEach(function (device) {
                const breakpoint = localize.el_breakpoints[device];
                if (breakpoint && breakpoint.is_enabled) {
                    // Get device-specific items and margin from options
                    const deviceItems = options?.breakpoints?.[device] || (device === 'desktop' ? (options?.items || 3) : 3);
                    const deviceMargin = options?.margins?.[device] || (device === 'desktop' ? (options?.gap || 10) : 10);

                    el_breakpoints[bp_index] = {
                        breakpoint: breakpoint.value,
                        slidesPerView: deviceItems,
                        spaceBetween: deviceMargin
                    };
                    bp_index++;
                }
            });

            // Convert el_breakpoints to Swiper breakpoints format
            Object.keys(el_breakpoints).forEach(function (index) {
                const _index = parseInt(index);
                if (typeof el_breakpoints[_index + 1] !== 'undefined') {
                    breakpoints[el_breakpoints[index].breakpoint] = {
                        slidesPerView: el_breakpoints[_index + 1].slidesPerView,
                        spaceBetween: el_breakpoints[_index + 1].spaceBetween
                    };
                }
            });
        }
    }

    const isAutoplayEnabled = options?.autoplay === 'yes' || options?.autoplay === true;
    const shouldPauseOnHover = isAutoplayEnabled && options?.pauseOnHover === 'yes';

    // Cards effect already centers; otherwise honor the user's centered_slides toggle
    // (only meaningful for horizontal slide effect with > 1 items per view).
    const isCenteredSlides = isCardsEffect
        || ('yes' === options?.centered_slides
            && options?.effect === 'slide'
            && sliderDirection === 'horizontal');

    // Swiper builds its infinite loop by cloning slides at both ends. The
    // loop is only safe once there are MORE real slides than are visible at
    // once — with as many (or fewer) slides than slidesPerView the clones run
    // out mid-cycle and Swiper either snaps backward to the first slide (the
    // "reverse jump") or shows a blank gap.
    //
    // The previous guard (>= 2 × slidesPerView) was too strict: a 5-slide,
    // 3-per-view slider failed 5 >= 6, so loop was silently dropped and
    // autoplay rewound all the way back at the end. `> numericSPV` is the
    // real minimum for a clean Swiper loop.
    const realSlideCount = $wrapper.find('> .swiper-slide').length;
    const numericSPV = (slidesPerViewValue === 'auto') ? 1 : (parseInt(slidesPerViewValue, 10) || 1);
    const wantsLoop = 'yes' === options?.loop;
    const safeLoop = wantsLoop && realSlideCount > numericSPV;

    // Swiper options
    const swiperOptions = {
        effect: options?.effect || 'slide',
        slidesPerView: slidesPerViewValue,
        slidesPerGroup: 1, // advance one slide at a time even when slidesPerView > 1
        spaceBetween: spaceBetweenValue,
        grabCursor: true,
        loop: safeLoop,
        // Clone the FULL slide set on each side. This is the documented cure
        // for Swiper 8's loop artifacts: with a full buffer the user can never
        // reach an un-cloned edge, so there's no visible teleport (reverse
        // jump) and no blank trailing slide. Cheap at realistic slide counts.
        loopedSlides: safeLoop ? realSlideCount : undefined,
        speed: options?.speed || 300,
        direction: sliderDirection,
        centeredSlides: isCenteredSlides,
        autoHeight: isCardsEffect && sliderDirection === 'vertical',
        // watchOverflow MUST stay false. When true, Swiper adds
        // swiper-button-lock / swiper-pagination-lock and hides nav +
        // pagination whenever slidesPerView >= slide count. Slide effect
        // derives slidesPerView from "Items Per Slide" (default 2), so a
        // 1–2 slide slider silently lost its navigation style on BOTH
        // editor and frontend. fade/flip force perView 1 and cards/coverflow
        // use 'auto', so only slide effect was bitten — matching the report.
        watchOverflow: false,
        observer: true,             // react to DOM mutations (editor friendly)
        observeParents: true,
        navigation: {
            // Search $scope, not $navHost (.swiper): the inline_with_arrows
            // reparent moves the arrows out of .swiper into the row under
            // $scope, so a .swiper-scoped lookup would resolve to undefined and
            // the arrows would no longer drive the slider.
            nextEl: $scope.find('.swiper-button-next')[0],
            prevEl: $scope.find('.swiper-button-prev')[0],
        },
    };

    // Pagination — only attach if a pagination element exists for a Swiper-native type.
    const paginationConfig = $paginationEl
        ? eaelBuildPaginationConfig(paginationType, $paginationEl[0])
        : null;
    if (paginationConfig) {
        swiperOptions.pagination = paginationConfig;
    }

    // Thumbnails: do NOT pre-create the sub-Swiper here. On the frontend
    // Elementor's swiper loader is async, and a synchronously-created sub-Swiper
    // wouldn't share the same Swiper class. We create both swipers via the same
    // async loader and connect them after both promises resolve (below).

    if (isAutoplayEnabled) {
        swiperOptions.autoplay = {
            delay: options?.autoplay_speed || 3000,
            disableOnInteraction: false,
            ...(shouldPauseOnHover ? { pauseOnMouseEnter: true } : {}),
        };
    }

    if (!isCardsEffect) {
        swiperOptions.breakpoints = breakpoints;
    }

    if ('coverflow' === options?.effect) {
        // Tuned to match the V4 "Photographer portfolio · 3D coverflow" reference:
        // moderate rotation, generous depth, no stretch — gives readable side
        // slides instead of nearly-edge-on strips.
        swiperOptions.coverflowEffect = {
            rotate:      (typeof options?.coverflow_rotation === 'number') ? options.coverflow_rotation : 35,
            depth:       (typeof options?.coverflow_depth    === 'number') ? options.coverflow_depth    : 200,
            stretch:     (typeof options?.coverflow_stretch  === 'number') ? options.coverflow_stretch  : 0,
            modifier:    1,
            slideShadows: options?.enable_background !== false,
        };
    } else if ('cards' === options?.effect) {
        swiperOptions.cardsEffect = {
            slideShadows: options?.enable_background !== false,
            perSlideOffset: 9,    // matches reference demo (V5 stacked cards)
            perSlideRotate: 2.4,
        };
    } else if ('flip' === options?.effect) {
        swiperOptions.flipEffect = {
            slideShadows: options?.enable_background !== false,
            limitRotation: true,
        };
    }

    // Initialize Swiper
    const swiperInstancePromise = swiperLoader($inner[0], swiperOptions);

    /**
     * slidesPerView visual override — CSS-variable approach.
     *
     * Background: Elementor injects per-widget CSS for column-sized widgets
     *   `width: var(--container-widget-width, 30%); max-width: 30%`
     * Swiper writes its calculated slide width to `style.width` (no !important),
     * and Elementor's `max-width: 30%` clamps it. Items Per Slide ends up with
     * no visible effect — slides keep the column width.
     *
     * Fix: write Swiper's calculated slide size to a CSS custom property on
     * the wrapper once per resize/breakpoint event. The companion rule in
     * advanced-slider.scss (`.eael-advanced-slider:not(.cards):not(.flip)...`)
     * applies the variable to every slide with `!important` and specificity
     * high enough to beat Elementor's `.e-flex.e-con-row` injection.
     *
     * Performance: 1 setProperty call per event regardless of slide count.
     * Previous implementation looped every slide and wrote 4-5 inline styles
     * — for an 18-slide looped slider that was ~90 DOM writes per event.
     */
    const applySlideSizeVariable = (swiper) => {
        if (!swiper || !swiper.slidesSizesGrid || !swiper.slidesSizesGrid[0]) return;
        const effect = swiper.params.effect;
        if (effect === 'cards' || effect === 'flip') return;

        // slidesSizesGrid[0]: for slidesPerView = integer (our case for non-cards/flip),
        // every entry has the same value. For horizontal it's width, for vertical it's height.
        const size = swiper.slidesSizesGrid[0];

        // Feedback-loop guard. If Swiper reports an absurdly large slide size
        // (>5000px), the container is mismeasuring — probably has no fixed height
        // and is collapsing to content size. Writing that value back via the CSS
        // variable inflates slides → wrapper grows → next layout pass measures
        // even bigger → exponential runaway. Real-world report: 8.9M px.
        // Skip the write; CSS fallback `auto` (horizontal) / SCSS baseline 70vh
        // (vertical) keep the slider from rendering as nothing.
        if (size > 5000) return;

        const isVertical = swiper.params.direction === 'vertical';
        const wrapper = swiper.el && swiper.el.closest
            ? (swiper.el.closest('.eael-advanced-slider') || swiper.el)
            : swiper.el;
        if (!wrapper) return;

        if (isVertical) {
            wrapper.style.setProperty('--eael-as-slide-h', size + 'px');
        } else {
            wrapper.style.setProperty('--eael-as-slide-w', size + 'px');
        }
    };
    swiperInstancePromise.then((swiper) => {
        if (!swiper) return;
        applySlideSizeVariable(swiper);
        swiper.on('resize', () => applySlideSizeVariable(swiper));
        swiper.on('slidesUpdated', () => applySlideSizeVariable(swiper));
        swiper.on('breakpoint', () => applySlideSizeVariable(swiper));
    });

    /**
     * Transition Easing — write transition-timing-function inline directly on
     * the wrapper and every slide, on every Swiper transition.
     *
     * Why JS instead of relying on CSS:
     *   Swiper's effects modules write `style.transition = "transform Xms"`
     *   (shorthand without timing-function) on slides for fade/cards/flip/
     *   coverflow. Browser handling of the resulting longhand transitionTimingFunction
     *   is inconsistent — sometimes empty (falls through to our `!important`
     *   CSS rule), sometimes explicit `ease` (competes with our author-CSS
     *   important against inline-without-important). The CSS approach was
     *   intermittently winning; users reported all four presets felt the same.
     *   Writing the timing-function inline on every transition guarantees
     *   the inline declaration matches the user's selection — bypasses the
     *   cascade entirely.
     *
     * Cost: ~(1 + slide_count) DOM writes per slide change. For a typical
     *   6-slide-with-loop slider that's ~19 writes per transition. Not per
     *   resize event like the old forceSwiperSlideWidths — only when the
     *   user actually changes slides.
     *
     * The SCSS rule `transition-timing-function: var(--eael-as-easing) !important`
     * stays as a defensive fallback for the initial paint before this hook
     * fires.
     */
    /* ADVANCED_SLIDER_EASING_CURVES is imported from ../_shared/advanced-slider.js
       (Phase 6.0 — first extraction to the shared partial). */
    const applyTransitionEasing = (swiper) => {
        const preset = $scope.attr('data-easing') || 'spring';
        const curve = ADVANCED_SLIDER_EASING_CURVES[preset] || ADVANCED_SLIDER_EASING_CURVES.spring;
        // setProperty with 'important' flag is required: the advanced-slider.scss
        // rule `transition-timing-function: var(--eael-as-easing) !important`
        // beats plain inline. We need inline-WITH-important to win the cascade.
        if (swiper.wrapperEl) {
            swiper.wrapperEl.style.setProperty('transition-timing-function', curve, 'important');
        }
        if (swiper.slides && swiper.slides.length) {
            for (let i = 0; i < swiper.slides.length; i++) {
                if (swiper.slides[i]) {
                    swiper.slides[i].style.setProperty('transition-timing-function', curve, 'important');
                }
            }
        }
    };
    swiperInstancePromise.then((swiper) => {
        if (!swiper) return;
        applyTransitionEasing(swiper);                                  // initial paint
        swiper.on('setTransition', () => applyTransitionEasing(swiper)); // every slide change
    });

    // Auto-fit height for cards/flip/coverflow (default ON).
    // Treat any value other than '' / 'no' as ON (handles 'yes' explicit + missing default).
    if (['cards', 'flip', 'coverflow'].indexOf(options?.effect) !== -1
        && options?.auto_height !== '' && options?.auto_height !== 'no') {
        swiperInstancePromise.then((swiper) => {
            if (!swiper) return;
            // Defer one tick so Swiper has finished its own size pass.
            setTimeout(() => eaelAutoFitHeight($scope, swiper, $), 50);
        });
    }

    // Thumbnails — connect the sub-Swiper to the main Swiper after BOTH async
    // creates resolve. This is the recommended Swiper pattern for async loaders.
    if (thumbnailsNav) {
        Promise.all([
            swiperInstancePromise,
            thumbnailsNav.getThumbsSwiperAsync()
        ]).then(([mainSwiper, thumbsSwiper]) => {
            if (!mainSwiper || !thumbsSwiper) return;
            // Wire thumbs reference into main swiper and let it sync.
            if (!mainSwiper.thumbs) {
                mainSwiper.thumbs = { swiper: thumbsSwiper };
            } else {
                mainSwiper.thumbs.swiper = thumbsSwiper;
            }
            if (typeof mainSwiper.thumbs.init === 'function') {
                try { mainSwiper.thumbs.init(); } catch (e) { /* no-op */ }
            }
            if (typeof mainSwiper.thumbs.update === 'function') {
                try { mainSwiper.thumbs.update(true); } catch (e) { /* no-op */ }
            }
            // Manually link clickable thumbs + active-state class.
            // BEST UX: highlight ALL currently-visible slides as active in the
            // thumb strip, not just the leftmost one. With slidesPerView=2 and
            // items 3+4 visible in the slider, thumbs 3 and 4 both light up.
            // Matches what the editor preview does and what users expect from
            // a thumbnail rail (it represents "what's in view right now").
            const totalThumbs = thumbsSwiper.slides.length;
            const numericSPV = () => {
                const v = mainSwiper.params.slidesPerView;
                return (v === 'auto') ? 1 : (parseInt(v, 10) || 1);
            };
            const setActiveThumbs = () => {
                const start = mainSwiper.realIndex || 0;
                const count = Math.min(numericSPV(), totalThumbs);
                for (let i = 0; i < totalThumbs; i++) {
                    thumbsSwiper.slides[i].classList.remove('swiper-slide-thumb-active');
                }
                for (let i = 0; i < count; i++) {
                    const idx = (start + i) % totalThumbs;
                    if (thumbsSwiper.slides[idx]) {
                        thumbsSwiper.slides[idx].classList.add('swiper-slide-thumb-active');
                    }
                }
                if (typeof thumbsSwiper.slideTo === 'function') {
                    thumbsSwiper.slideTo(start);
                }
            };

            thumbsSwiper.on('click', () => {
                const i = thumbsSwiper.clickedIndex;
                if (typeof i === 'number' && mainSwiper.slideTo) {
                    mainSwiper.slideTo(i);
                }
            });
            mainSwiper.on('slideChange', setActiveThumbs);
            mainSwiper.on('breakpoint', setActiveThumbs);  // re-sync if slidesPerView changes responsively
            setActiveThumbs();  // initial state
        }).catch(() => { /* ignore errors */ });
    }

    // Wire numbered_index nav (lives outside Swiper's pagination module).
    if (numberedIndexNav) {
        swiperInstancePromise.then((swiper) => {
            if (!swiper) return;
            numberedIndexNav.bindClicks(swiper);
            swiper.on('slideChange', () => numberedIndexNav.onSlideChange(swiper.realIndex));
        });
    }
    if (progressBarNav) {
        swiperInstancePromise.then((swiper) => {
            if (!swiper) return;
            progressBarNav.attach(swiper);
        });
    }

    if (shouldPauseOnHover) {
        swiperInstancePromise.then((swiper) => {
            if (!swiper?.autoplay) {
                return;
            }
            const stopAutoplay = () => swiper.autoplay.stop();
            const startAutoplay = () => swiper.autoplay.start();
            $inner.on('mouseenter.eael-advanced-slider', stopAutoplay);
            $inner.on('mouseleave.eael-advanced-slider', startAutoplay);
        });
    }

    if ('cards' === options?.effect) {
        if ($scope.hasClass('eael-advanced-slider-cards') || $scope.find(' > .eael-advanced-slider-cards').length) {
            $scope.css('display', 'flex');
        }
    }
}

/* swiperLoader imported from ../_shared/advanced-slider.js (Phase 6.1). */

jQuery(window).on("elementor/frontend/init", function () {
    if (eael.elementStatusCheck('eaelAdvancedSlider') || window.isEditMode) {
        return false;
    }
    elementorFrontend.hooks.addAction("frontend/element_ready/container", AdvancedSliderHandler);
    elementorFrontend.hooks.addAction("frontend/element_ready/section", AdvancedSliderHandler);
});

})();
