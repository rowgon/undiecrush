/**
 * EA Advanced Slider — editor handler
 * Wrapped in an IIFE so AdvancedSliderHandler is module-local and cannot collide
 * with the same identifier in src/js/view/advanced-slider.js.
 */
import {
    ADVANCED_SLIDER_EASING_CURVES,
    toAlphabet,
    toRoman,
    escapeHtml,
    getPaginationType,
    getPaginationPosition,
    buildPaginationConfig,
    mountPaginationHost,
    swiperLoader,
} from '../_shared/advanced-slider.js';

(function () {

let AdvancedSliderHandler = function ($scope, $) {
    const editorState = window.__eaelAdvancedSliderEditorState || (window.__eaelAdvancedSliderEditorState = {
        queued: false,
        inProgress: false,
        lastRunAt: 0,
        changeListenerBound: false,
    });
    editorState.sliderSettings = editorState.sliderSettings || {};
    // Per-slider state — keyed by Elementor element ID. Tracks the active
    // Swiper instance (Phase 5.1) and any in-flight requestAnimationFrame
    // tokens (Phase 5.2) so teardownSlider() can shut both down before
    // the next init pass rebuilds the DOM (Phase 5.3).
    editorState.sliders = editorState.sliders || new Map();

    /* Phase 5.3 — clean up the previous slider before re-init.

       Before this commit, every signature-changing control flip
       (direction, effect, items-per-slide…) caused initAdvancedSlider to
       container.empty() and rebuild — but the OLD Swiper instance kept
       its autoplay timer, transitionEnd listeners, and rAF loops
       running against the now-detached DOM. After ~3-4 control changes
       the editor preview accumulated enough stale handlers to feel
       sluggish and intermittently render wrong slide widths or skip
       slides on the first interaction. Cowork's earlier attempt at
       this teardown went wrong by ALSO invalidating the settings
       cache + clearing the Map entry, which caused the next init
       to read partial state. This version is deliberately minimal:
       destroy + cancelAnimationFrame, then null the swiper field.
       The Map key + rafTokens Set are preserved so Phase 5.1/5.2's
       stash logic writes back into the same slot on next init. */
    const teardownSlider = (elementId) => {
        if (!elementId) return;
        const entry = editorState.sliders.get(elementId);
        if (!entry) return;
        if (entry.swiper && !entry.swiper.destroyed && typeof entry.swiper.destroy === 'function') {
            try {
                /* (deleteInstance=true, cleanStyles=false) — kill the
                   instance reference, the autoplay timer, and all event
                   listeners, but SKIP Swiper's per-slide inline-style
                   stripping. That scrub walks every slide and removes
                   transform/width/transition styles + swiper-slide-active
                   / -duplicate / -prev / -next classes; on an 18-slide
                   looped slider it's ~50ms of layout-thrashing work.

                   The follow-up items.detach() + container.empty() in
                   initAdvancedSlider discards the entire subtree anyway,
                   so cleanStyles=true is double work. Flipping to false
                   was specifically what cleared the items-per-slide /
                   item-gap drag lag in QA. */
                entry.swiper.destroy(true, false);
            } catch (e) {
                /* destroy() can throw on partially-initialized instances
                   (e.g. a previous init promise hadn't resolved yet when
                   the next control change fired). Best-effort only —
                   swallow and continue so the rebuild isn't blocked. */
            }
        }
        if (entry.rafTokens && typeof entry.rafTokens.forEach === 'function') {
            entry.rafTokens.forEach((token) => cancelAnimationFrame(token));
            entry.rafTokens.clear();
        }
        /* Disconnect the viewport-autoplay observer (see initSwiperSlider's
           autoplay block) before the DOM is rebuilt, so observers don't leak
           across re-inits and keep referencing detached slider wrappers. */
        if (entry.autoplayObserver && typeof entry.autoplayObserver.disconnect === 'function') {
            entry.autoplayObserver.disconnect();
            entry.autoplayObserver = null;
        }
        entry.swiper = null;
    };
    // 250ms debounce — gives Elementor's CSS injection time to settle and
    // batches multiple control changes (e.g. dragging a slider) into one
    // re-init pass instead of running every frame.
    const INIT_DEBOUNCE_MS = 250;

    /* toAlphabet, toRoman imported from ../_shared/advanced-slider.js (Phase 6.1). */

    const buildSettingsSignature = (settings) => {
        return JSON.stringify({
            effect: settings?.eael_advanced_slider_effect,
            direction: settings?.eael_advanced_slider_direction,
            manualScrolling: settings?.eael_advanced_slider_manual_scrolling,
            marquee: settings?.eael_advanced_slider_effect_marquee,
            indicator: settings?.eael_advanced_slider_indicator,
            indicatorType: settings?.eael_advanced_slider_indicator_type,
            navigationArrows: settings?.eael_advanced_slider_navigation_arrows,
            // New pagination contract — re-init editor when any of these change.
            paginationType: settings?.eael_advanced_slider_pagination_type,
            paginationPosition: settings?.eael_advanced_slider_pagination_position,
            centeredSlides: settings?.eael_advanced_slider_centered_slides,
            indexFormat: settings?.eael_advanced_slider_index_format,
            autoHeight: settings?.eael_advanced_slider_auto_height,
            height3d: settings?.eael_advanced_slider_3d_height,
            showNavigation: settings?.eael_advanced_slider_enable_navigation,
            loop: settings?.eael_advanced_slider_loop,
            speed: settings?.eael_advanced_slider_speed,
            speedMarquee: settings?.eael_advanced_slider_speed_marquee,
            transitionEasing: settings?.eael_advanced_slider_transition_easing,
            autoplay: settings?.eael_advanced_slider_autoplay,
            autoplaySpeed: settings?.eael_advanced_slider_autoplay_speed,
            items: settings?.eael_advanced_slider_per_view,
            gap: settings?.eael_advanced_slider_item_gap,
            gapMarquee: settings?.eael_advanced_slider_item_gap_marquee,
            enableBackground: settings?.eael_advanced_slider_enable_background,
            coverflowRotation: settings?.eael_advanced_slider_coverflow_rotation,
            pauseOnHover: settings?.eael_advanced_slider_pause_on_hover,
            pauseOnHoverMarquee: settings?.eael_advanced_slider_pause_on_hover_marquee,
            height: settings?.eael_advanced_slider_height,
            navigationIconLeft: settings?.eael_advanced_slider_navigation_icon_left,
            navigationIconRight: settings?.eael_advanced_slider_navigation_icon_right,
        });
    }

    /* Pagination helpers (getPaginationType, getPaginationPosition,
       escapeHtml, buildPaginationConfig) and the editor's mountPaginationHost
       are imported from ../_shared/advanced-slider.js (Phase 6.1). */
    /* Detect a slide's representative image (img > bg-image url > null). */
    const detectSlideImage = ($slide) => {
        let imgSrc = $slide.find('img').first().attr('src') || '';
        if (!imgSrc) {
            const $bgEl = $slide.find('[style*="background-image"]').first();
            if ($bgEl.length) {
                const m = ($bgEl.attr('style') || '').match(/background-image\s*:\s*url\(['"]?([^'"\)]+)/);
                if (m) imgSrc = m[1];
            }
        }
        return imgSrc;
    };

    /* Thumbnails sub-Swiper builder (auto-detects per-slide image).
       In the EDITOR, Elementor renders slide content asynchronously, so
       images often aren't in the DOM when this builder first runs.
       We schedule retry passes to swap numbered placeholders for actual
       images as they appear. */
    const buildThumbnailsNav = ($scope, $slides, options) => {
        const position = (options?.pagination_position || 'bottom').replace(/_/g, '-');
        const $host = $('<div class="eael-as-thumbnails eael-as-thumbnails--position-' + position + '"></div>');
        const $thumbsSwiper = $('<div class="swiper eael-as-thumbnails-swiper"></div>');
        const $thumbsWrapper = $('<div class="swiper-wrapper"></div>');
        const slideElements = $slides.toArray();

        slideElements.forEach((slideEl, i) => {
            const imgSrc = detectSlideImage($(slideEl));
            const inner = imgSrc
                ? '<img src="' + escapeHtml(imgSrc) + '" alt="" data-eael-as-thumb-detected="1">'
                : '<span class="eael-as-thumb-num" data-idx="' + i + '">' + (i + 1) + '</span>';
            $thumbsWrapper.append('<div class="swiper-slide eael-as-thumb">' + inner + '</div>');
        });
        $thumbsSwiper.append($thumbsWrapper);
        $host.append($thumbsSwiper);
        $scope.append($host);

        // Retry up to 6 times over ~3 seconds to upgrade number placeholders
        // to actual images as Elementor renders slide content async.
        let retries = 6;
        const retryTimer = setInterval(() => {
            const $needs = $host.find('.eael-as-thumb-num');
            if (!$needs.length) { clearInterval(retryTimer); return; }
            $needs.each(function () {
                const idx = parseInt(this.getAttribute('data-idx'), 10);
                if (isNaN(idx) || !slideElements[idx]) return;
                const imgSrc = detectSlideImage($(slideElements[idx]));
                if (imgSrc) {
                    $(this).replaceWith('<img src="' + escapeHtml(imgSrc) + '" alt="" data-eael-as-thumb-detected="1">');
                }
            });
            retries--;
            if (retries <= 0) clearInterval(retryTimer);
        }, 500);

        return {
            $host,
            getThumbsSwiper: () => new Swiper($thumbsSwiper[0], {
                slidesPerView: 'auto',
                spaceBetween: 8,
                centerInsufficientSlides: true,
                watchSlidesProgress: true,
                slideToClickedSlide: true,
            })
        };
    };

    /* Auto-fit height for 3D effects (cards / flip / coverflow). */
    const autoFitHeight = ($scope, swiper) => {
        const $swiperEl = $scope.children('.swiper').first();
        if (!$swiperEl.length) return;
        const $slides = $swiperEl.find('> .swiper-wrapper > .swiper-slide');
        if (!$slides.length) return;
        const measure = () => {
            const slidesPrev = [];
            $slides.each(function () { slidesPrev.push(this.style.height); });
            $slides.css('height', 'auto');
            $swiperEl.css('height', 'auto');
            $swiperEl[0].offsetHeight; // reflow
            let maxH = 0;
            $slides.each(function () { const h = this.scrollHeight; if (h > maxH) maxH = h; });
            $slides.each(function (i) { this.style.height = slidesPrev[i] || ''; });
            if (maxH > 0) $swiperEl.css('height', maxH + 'px');
            if (swiper && typeof swiper.update === 'function') swiper.update();
        };
        measure();
        let imgTimer;
        const debouncedMeasure = () => {
            clearTimeout(imgTimer);
            imgTimer = setTimeout(measure, 150);
        };
        $scope.find('img').each(function () {
            if (!this.complete) this.addEventListener('load', debouncedMeasure, { once: true });
        });
    };

    /* Time-based Autoplay Progress Bar (matches demo V3). */
    const buildProgressBarNav = ($scope, options, trackRaf) => {
        const position = (options?.pagination_position || 'bottom').replace(/_/g, '-');
        /* re-centered pause/play SVGs — see view JS for the full rationale.
           Pause bars now sit symmetrically inside the 24-unit viewBox; play
           triangle is shifted right of geometric center so the optical center
           lands in the middle of the round button. SVG sizing comes from CSS. */
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
                const $fill = $host.find('.eael-as-pb-fill');
                const $current = $host.find('.eael-as-pb-current');
                const $total = $host.find('.eael-as-pb-total');
                const $toggle = $host.find('.eael-as-pb-toggle');
                const $iconPause = $host.find('.eael-as-pb-pause');
                const $iconPlay = $host.find('.eael-as-pb-play');
                const totalSlides = swiper.slides.length - (swiper.params.loop ? 2 * swiper.loopedSlides : 0);
                const safeTotal = Math.max(1, totalSlides);
                $total.text(String(safeTotal).padStart(2, '0'));
                $current.text(String((swiper.realIndex || 0) + 1).padStart(2, '0'));
                /* Fill animation works for ANY effect, not just fade.
                   Mirror of view JS — see that file for the rationale. */
                const autoplayEnabled = !!(swiper.params.autoplay && (swiper.params.autoplay.delay || swiper.params.autoplay === true));
                const delay = (options?.autoplay_speed || (swiper.params.autoplay && swiper.params.autoplay.delay) || 3000);
                /* A slide is on screen for `delay` + `speed` (Swiper autoplay
                   waitForTransition:true). Span the full cycle so the fill
                   completes as the next slide settles, not at `delay`. Mirror
                   of view JS. */
                const transitionSpeed = swiper.params.speed || 0;
                const cycle = delay + transitionSpeed;
                let startTime = performance.now();
                let paused = !autoplayEnabled;
                if (autoplayEnabled && swiper.autoplay && !swiper.autoplay.running) {
                    try { swiper.autoplay.start(); } catch (e) {}
                }
                const setPausedUI = (isPaused) => {
                    paused = isPaused;
                    $iconPause.css('display', isPaused ? 'none' : '');
                    $iconPlay.css('display', isPaused ? '' : 'none');
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
                    $fill.css('width', '0%');
                });
                swiper.on('autoplayStart', () => setPausedUI(false));
                swiper.on('autoplayStop', () => setPausedUI(true));
                swiper.on('autoplayPause', () => setPausedUI(true));
                swiper.on('autoplayResume', () => setPausedUI(false));
                const tick = () => {
                    if (!paused) {
                        const elapsed = performance.now() - startTime;
                        const pct = Math.min(100, (elapsed / cycle) * 100);
                        $fill.css('width', pct + '%');
                    }
                    const token = requestAnimationFrame(tick);
                    if (trackRaf) trackRaf(token);
                };
                tick();
            }
        };
    };

    /* Numbered/Alphabet/Roman index rail builder. */
    const buildNumberedIndexNav = ($scope, $slides, options) => {
        const format = options?.index_format || 'number';
        const cls = 'eael-as-pagination eael-as-pagination--type-numbered-index eael-as-pagination--position-' + (options?.pagination_position || 'bottom').replace(/_/g, '-');
        const $host = $('<nav class="' + cls + '" aria-label="Slider navigation"></nav>');
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
    const getIconTag = (icon) => {
        if (!icon) return '';
        if (typeof icon === 'string') {
            return `<i class="${icon}"></i>`;
        }
        if (icon.library === 'svg') {
            if (icon.value && icon.value.url) {
                return `<img src="${icon.value.url}" />`;
            }
        } else {
            if (icon.value) {
                return `<i class="${icon.value}"></i>`;
            }
        }
        return '';
    }

    /* swiperLoader imported from ../_shared/advanced-slider.js (Phase 6.1). */

    const initVerticalSlider = function ($scope, options, $, settings) {
        /* Manual-scroll vertical uses native CSS scroll-snap (NOT Swiper).
           initAdvancedSlider built a mode-agnostic .swiper > .swiper-wrapper
           hull around the items; in manual-scroll mode that hull is dead
           weight AND breaks the SCSS rules — the scroll-snap selectors all
           target .eael-advanced-slider-inner, which until this commit never
           got attached because the original code looked at $scope > .swiper-
           wrapper (one level too shallow) and silently no-op'd.

           Flatten: lift each slide out of .swiper > .swiper-wrapper into a
           fresh .eael-advanced-slider-inner that lives directly under $scope,
           strip .swiper-slide (irrelevant outside Swiper), tag with
           .eael-advanced-slider-slide so the scroll-snap-align rule binds. */
        const $swiperEl = $scope.children('.swiper').first();
        const $swiperWrap = $swiperEl.children('.swiper-wrapper').first();
        let $inner;
        let $slides;
        if ($swiperWrap.length) {
            $inner = $('<div class="eael-advanced-slider-inner"></div>');
            $slides = $swiperWrap.children('.swiper-slide');
            $slides.each(function () {
                $(this).removeClass('swiper-slide').addClass('eael-advanced-slider-slide');
                $inner.append(this);
            });
            $swiperEl.remove();
            $scope.append($inner);
        } else {
            // Legacy / re-init path: structure may already be flattened.
            $inner = $scope.children('.eael-advanced-slider-inner').first();
            if (!$inner.length) {
                $inner = $('<div class="eael-advanced-slider-inner"></div>');
                $scope.append($inner);
            }
            $slides = $inner.children();
            $slides.addClass('eael-advanced-slider-slide');
        }

        if ('yes' === options?.indicator) {
            const $indicator = $('<ul id="eael-advanced-slider-' + $scope.attr('id') + '" class="eael-as-indicator"></ul>');
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
        $slides.each(function () {
            detect(this);
        });
    }

    const initMarqueeSlider = function ($scope, options, $) {
        let $track = $scope.find('> .eael-as-track');
        if (!$track.length) {
            $track = $scope.find('> .swiper-wrapper');
            $track.addClass('eael-as-track');
            $track.removeClass('swiper-wrapper');
        }

        if (options?.gap) {
            $track.css('gap', options?.gap + 'px');
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

        setTimeout(duplicateTrackContent, 500);
        // duplicateTrackContent();

        const SPEED_PX_PER_SEC = options?.speed || 50;
        function setDuration() {
            // let totalWidth = $track[0].scrollWidth / 2;
            const duration = SPEED_PX_PER_SEC / 20;
            $track.css('--duration', duration + 's');
        }
        setDuration();
        $(window).on('resize', setDuration);

        if ('yes' === options?.pauseOnHover) {
            $track.on('mouseenter', function () {
                $(this).css('animation-play-state', 'paused');
            });
            $track.on('mouseleave', function () {
                $(this).css('animation-play-state', 'running');
            });
        }

        function syncHeight() {
            const childHeights = $track.children().map(function () {
                return $(this).outerHeight(true);
            }).get();

            if (childHeights.length) {
                const maxHeight = Math.max(...childHeights);
                if (maxHeight) {
                    $scope.css('height', maxHeight);
                }
            }
        }

        syncHeight();
        setTimeout(syncHeight, 50);
        $(window).on('resize', syncHeight);
    }

    const initSwiperSlider = function ($scope, options, $) {
        // After the editor restructure, $scope is the outer .eael-advanced-slider
        // and the actual Swiper container is a .swiper child of it. Fall back to
        // $scope itself if the structure is legacy (no inner .swiper found).
        let $inner = $scope.children('.swiper').first();
        if (!$inner.length) $inner = $scope;
        // Phase 5.2 — rAF token capture (passive). Tokens accumulate into the
        // per-slider stash so Phase 5.3 can cancelAnimationFrame() on them
        // during teardown. Lazy-init the Set so tokens written before the
        // Swiper promise resolves still land in the right entry.
        /* Key the per-slider stash by the RAW Elementor element id so it
           matches the key teardownSlider() and the soft-update fast-path use
           (model.attributes.id, e.g. "aaaa111"). $scope here is the built
           .eael-advanced-slider whose DOM id is "eael-advanced-slider-<id>";
           stashing under that prefixed id meant teardownSlider("<id>") never
           found the instance — old Swipers (autoplay timers, listeners) were
           never destroyed on rebuild, and the soft-update path couldn't locate
           the live Swiper. Strip the prefix so all three agree on one key. */
        const elementId = $scope.attr('data-id')
            || ( $scope.attr('id') || '' ).replace('eael-advanced-slider-', '')
            || null;
        const trackRaf = (token) => {
            if (!elementId || !token) return token;
            let entry = editorState.sliders.get(elementId);
            if (!entry) {
                entry = {};
                editorState.sliders.set(elementId, entry);
            }
            entry.rafTokens = entry.rafTokens || new Set();
            entry.rafTokens.add(token);
            return token;
        };
        const sliderDirection = options?.direction || 'horizontal';
        const isCardsEffect = options?.effect === 'cards';
        const isFadeEffect = options?.effect === 'fade';
        const isFlipEffect = options?.effect === 'flip';
        /* Fade & Flip are single-slide effects — Swiper shows ONE slide at a
           time and sizes it to the FULL container width. Mirror the frontend
           (view JS) and force slidesPerView:1 / spaceBetween:0 for them.

           Without this the editor fell through to "Items Per Slide" (default 3),
           so each flip/fade slide was sized to ~1/3 of the container. With
           Swiper's flip effect (virtualTranslate) the active slide then rendered
           blank at 1/3 width, and the auto-fit pass measured a near-zero slide
           height → the whole slider collapsed the moment the effect was changed.
           The frontend never hit this because it already forces perView 1, which
           is why the breakage was editor-only (and most visible in Firefox). */
        const isSingleSlideEffect = isFadeEffect || isFlipEffect;
        const isNavigationEnabled = options?.showNavigation === 'yes'
            || (typeof options?.showNavigation === 'undefined'
                && 'yes' === options?.navigation_arrows);
        const slidesPerViewValue = isCardsEffect ? 'auto' : (isSingleSlideEffect ? 1 : (options?.items || 3));
        const spaceBetweenValue = isCardsEffect ? (options?.gap || 0) : (isSingleSlideEffect ? 0 : (options?.gap || 10));

        if ('yes' === options?.navigation_arrows && 'fade' !== options?.effect && isNavigationEnabled) {
            let leftIconHtml = options.navigation_icon_left || '';
            let rightIconHtml = options.navigation_icon_right || '';

            if ($inner.find('.swiper-button-next').length === 0) {
                let content = rightIconHtml ? rightIconHtml : '';
                let className = rightIconHtml ? 'swiper-button-next eael-as-nav-icon' : 'swiper-button-next';
                $inner.append(`<div class="${className}" aria-label="Next">${content}</div>`);
            }
            if ($inner.find('.swiper-button-prev').length === 0) {
                let content = leftIconHtml ? leftIconHtml : '';
                let className = leftIconHtml ? 'swiper-button-prev eael-as-nav-icon' : 'swiper-button-prev';
                $inner.append(`<div class="${className}" aria-label="Previous">${content}</div>`);
            }
        }

        /* ──────── Pagination (new contract) ──────── */
        const paginationType = getPaginationType(options);
        const paginationPosition = getPaginationPosition(options);
        let $paginationEl = null;
        let thumbnailsNav = null;
        let numberedIndexNav = null;
        let progressBarNav = null;

        $scope
            .addClass('eael-as-pag-' + paginationType.replace(/_/g, '-'))
            .addClass('eael-as-pos-' + paginationPosition.replace(/_/g, '-'));

        const $slidesForNav = $inner.find('.swiper-wrapper > .swiper-slide');
        // Note: progress_bar is allowed on fade effect (matches demo V3 — fade hero with progress).
        if (paginationType === 'progress_bar') {
            progressBarNav = buildProgressBarNav($scope, options, trackRaf);
        } else if ('fade' !== options?.effect) {
            if (paginationType === 'thumbnails') {
                thumbnailsNav = buildThumbnailsNav($scope, $slidesForNav, options);
            } else if (paginationType === 'numbered_index') {
                numberedIndexNav = buildNumberedIndexNav($scope, $slidesForNav, options);
            } else if (paginationType !== 'none' && isNavigationEnabled) {
                // Gate on isNavigationEnabled (master Enable toggle), NOT
                // canShowNavigation. canShowNavigation also excludes vertical
                // direction — correct for left/right ARROWS, but dots / tick
                // bars / fraction render fine on a vertical slider. Using the
                // arrow gate here hid the navigation style for vertical slide
                // in the editor while the frontend still showed it.
                $paginationEl = mountPaginationHost($scope, $inner, paginationType, paginationPosition, $);
            }
        }

        // "Inline with arrows": co-locate prev-arrow + pagination + next-arrow
        // in a single flex row. Arrows mount in $inner and the pagination host
        // in $scope, so without this they live in different parents and the
        // position collapses to look identical to "bottom". Mirrors view JS.
        if (paginationPosition === 'inline_with_arrows' && 'yes' === options?.navigation_arrows) {
            const $pagHost = $scope.find('.eael-as-pagination--position-inline-with-arrows').first();
            const $prevArrow = $inner.find('> .swiper-button-prev').first();
            const $nextArrow = $inner.find('> .swiper-button-next').first();
            if ($pagHost.length && $prevArrow.length && $nextArrow.length) {
                // Tag the row with the pagination type so CSS can give wide-rail
                // types (progress bar) a definite width instead of shrink-wrapping.
                const $inlineRow = $('<div class="eael-as-inline-row eael-as-inline-row--' + paginationType.replace(/_/g, '-') + '"></div>');
                $inlineRow.append($prevArrow).append($pagHost).append($nextArrow);
                // Append to $scope (outer, overflow:visible) — NOT $inner
                // (.swiper, overflow:hidden for slide/fade), which clips the row.
                $scope.append($inlineRow);
            }
        }

        /* Autoplay in the editor preview mirrors the frontend so the user can
           see the configured autoplay/loop behaviour while building — earlier
           builds held the editor static, but QA flagged "autoplay not working
           in editor" as a regression vs user expectation. Read the same flags
           the view JS reads. pauseOnHover is honoured here too so hovering the
           preview to edit a slide doesn't fight the auto-advance. */
        const isAutoplayEnabled = options?.autoplay === 'yes' || options?.autoplay === true;
        const shouldPauseOnHover = isAutoplayEnabled && options?.pauseOnHover === 'yes';

        // Honor centered_slides toggle on horizontal slide effect.
        const isCenteredSlides = isCardsEffect
            || ('yes' === options?.centered_slides
                && options?.effect === 'slide'
                && sliderDirection === 'horizontal');

        // Slide-count guard for loop — see view JS comment for the full
        // rationale. Loop is only safe with MORE real slides than are visible
        // at once; below that, Swiper's clones run out and it snaps backward
        // to the first slide (reverse jump) or shows a blank slide — the
        // latter especially visible in the editor where `observer` re-clones
        // on Elementor DOM mutations. The old `>= 2 * slidesPerView` guard was
        // too strict and silently dropped loop on common configs.
        const realSlideCount = $inner.find('.swiper-wrapper > .swiper-slide').length;
        const numericSPV = (slidesPerViewValue === 'auto') ? 1 : (parseInt(slidesPerViewValue, 10) || 1);
        const wantsLoop = !!options?.loop;
        const safeLoop = wantsLoop && realSlideCount > numericSPV;

        const swiperOptions = {
            effect: options?.effect || 'slide',
            slidesPerView: slidesPerViewValue,
            slidesPerGroup: 1,
            spaceBetween: spaceBetweenValue,
            grabCursor: false,
            loop: safeLoop,
            // Clone the FULL slide set on each side — documented cure for
            // Swiper 8's loop artifacts (reverse-jump teleport + blank
            // trailing slide). With a full buffer the cycle never reaches an
            // un-cloned edge. See view JS comment for the full rationale.
            loopedSlides: safeLoop ? realSlideCount : undefined,
            speed: options?.speed || 300,
            direction: sliderDirection,
            centeredSlides: isCenteredSlides,
            autoHeight: isCardsEffect && sliderDirection === 'vertical',
            // Keep false — watchOverflow:true makes Swiper add
            // swiper-button-lock / swiper-pagination-lock and hide nav +
            // pagination when slidesPerView >= slide count. Slide effect's
            // perView comes from "Items Per Slide" (default 2), so few-slide
            // sliders lost their navigation style in the editor preview.
            watchOverflow: false,
            observer: true,
            observeParents: true,
            navigation: {
                // Search $scope, not $inner (.swiper): the inline_with_arrows
                // reparent moves the arrows into the row under $scope, so a
                // .swiper-scoped lookup would resolve to undefined.
                nextEl: $scope.find('.swiper-button-next')[0],
                prevEl: $scope.find('.swiper-button-prev')[0],
            },
        };

        const paginationConfig = $paginationEl
            ? buildPaginationConfig(paginationType, $paginationEl[0])
            : null;
        if (paginationConfig) {
            swiperOptions.pagination = paginationConfig;
        }

        if (thumbnailsNav) {
            try {
                swiperOptions.thumbs = { swiper: thumbnailsNav.getThumbsSwiper() };
            } catch (e) { /* swiper modules not ready */ }
        }
        if (isCardsEffect && sliderDirection === 'vertical') {
            swiperOptions.slidesPerView = 1;
            swiperOptions.slidesPerGroup = 1;
            swiperOptions.centeredSlides = true;
            swiperOptions.spaceBetween = 0;
            swiperOptions.autoHeight = true;
            swiperOptions.watchOverflow = false;
            swiperOptions.resizeObserver = true;
            swiperOptions.observer = true;
            swiperOptions.observeParents = true;
        }

        if (isAutoplayEnabled) {
            swiperOptions.autoplay = {
                delay: options?.autoplay_speed || 3000,
                disableOnInteraction: false,
                ...(shouldPauseOnHover ? { pauseOnMouseEnter: true } : {}),
            };
        }

        if ('coverflow' === options?.effect) {
            // V4 reference defaults: rotation 35°, depth 200, stretch 0, modifier 1.
            swiperOptions.coverflowEffect = {
                rotate:      (typeof options?.coverflow_rotation === 'number') ? options.coverflow_rotation : 35,
                depth:       (typeof options?.coverflow_depth    === 'number') ? options.coverflow_depth    : 200,
                stretch:     (typeof options?.coverflow_stretch  === 'number') ? options.coverflow_stretch  : 0,
                modifier:    1,
                slideShadows: options?.enable_background !== false,
            };
        }
        if ('cards' === options?.effect) {
            swiperOptions.cardsEffect = {
                slideShadows: options?.enable_background !== false,
                perSlideOffset: 9,    // matches reference demo (V5 stacked cards)
                perSlideRotate: 2.4,
            };
            // Cards overflow:visible so rotated edges show; let CSS handle clipping per slide.
            $inner.css('overflow', 'visible');
        } else if ('flip' === options?.effect) {
            swiperOptions.flipEffect = {
                slideShadows: options?.enable_background !== false,
                limitRotation: true,
            };
            $inner.css('overflow', 'visible');
        }

        const swiperInstancePromise = swiperLoader($inner[0], swiperOptions);

        // Phase 5.1 — stash the Swiper instance per-slider so future
        // sub-commits can tear it down before re-init. Write-only at this
        // commit (no reads, no destroy). Keyed by the Elementor element ID
        // so multiple sliders on a page don't collide.
        swiperInstancePromise.then((swiper) => {
            if (!swiper || !elementId) return;
            const prevEntry = editorState.sliders.get(elementId) || {};
            editorState.sliders.set(elementId, Object.assign(prevEntry, { swiper }));
        });

        /**
         * slidesPerView visual override — CSS-variable approach (mirror of view JS).
         * Writes Swiper's calculated slide size to a CSS custom property on the
         * wrapper once per Swiper resize/breakpoint event. The companion rule in
         * advanced-slider.scss applies the variable to every slide with !important
         * and specificity high enough to beat Elementor's per-widget max-width
         * injection. ~1 DOM op per event regardless of slide count.
         */
        const applySlideSizeVariable = (swiper) => {
            if (!swiper || !swiper.slidesSizesGrid || !swiper.slidesSizesGrid[0]) return;
            const effect = swiper.params.effect;
            if (effect === 'cards' || effect === 'flip') return;
            const size = swiper.slidesSizesGrid[0];
            // Feedback-loop guard — see view JS for full rationale.
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
            /* vertical mode needs an extra update() pulse after Elementor's
               CSS selectors have been injected. On the FIRST direction-toggle to
               vertical, .swiper height jumps from "auto" to "70vh" via CSS but
               Swiper has already cached slide heights against the previous
               (auto, near-zero) container size. Without this, the editor renders
               a blank slider until the next resize event. The two-tick approach
               (rAF + setTimeout) covers both the immediate CSS settle and any
               late paint. */
            if (swiper.params.direction === 'vertical') {
                const refresh = () => {
                    if (!swiper.destroyed) {
                        if (typeof swiper.updateSize === 'function') swiper.updateSize();
                        if (typeof swiper.updateSlides === 'function') swiper.updateSlides();
                        if (typeof swiper.update === 'function') swiper.update();
                        applySlideSizeVariable(swiper);
                    }
                };
                trackRaf(requestAnimationFrame(refresh));
                setTimeout(refresh, 120);
            }
        });

        /**
         * Transition Easing — inline timing-function write on every transition.
         * Mirror of view JS. See that file for the full rationale on why CSS
         * alone wasn't reliable.
         */
        /* ADVANCED_SLIDER_EASING_CURVES is imported from
           ../_shared/advanced-slider.js (Phase 6.0 — first extraction). */
        const applyTransitionEasing = (swiper) => {
            const preset = $scope.attr('data-easing') || 'spring';
            const curve = ADVANCED_SLIDER_EASING_CURVES[preset] || ADVANCED_SLIDER_EASING_CURVES.spring;
            // setProperty with 'important' flag — see view JS comment for rationale.
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
            applyTransitionEasing(swiper);
            swiper.on('setTransition', () => applyTransitionEasing(swiper));
        });

        if (['cards', 'flip', 'coverflow'].indexOf(options?.effect) !== -1
            && options?.auto_height === 'yes') {
            swiperInstancePromise.then((swiper) => {
                if (!swiper) return;
                setTimeout(() => autoFitHeight($scope, swiper), 50);
            });
        }
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

        if (isAutoplayEnabled) {
            swiperInstancePromise.then((swiper) => {
                if (!swiper?.autoplay) return;

                /* PERF (editor lag, worst in Firefox) — the editor renders every
                   slider on the page at once. Swiper autoplay advances on a
                   setTimeout, which browsers do NOT throttle for off-screen
                   elements, so each autoplaying slider keeps firing transitions
                   even while scrolled out of view. Every transition runs the
                   setTransition easing pass (an inline write to each slide, loop
                   clones included) plus a style/layout recalc, so 3-4 stacked
                   sliders animating in the background churn the main thread
                   continuously while the user edits a panel. Firefox feels this
                   hardest because it composites the 3D card/coverflow decks far
                   less cheaply than Chrome.

                   Fix: only autoplay a slider while it is actually inside the
                   editor viewport. Visible sliders still animate (preserves the
                   "autoplay works in the editor preview" expectation); off-screen
                   ones stay paused until scrolled into view, so at most the one
                   or two sliders the user is looking at ever run. Front-end
                   behaviour is untouched — this lives only in the editor handler.
                   pauseOnHover (below) and the progress-bar UI both listen to
                   Swiper's autoplayStart/Stop events, so they stay in sync when
                   the observer toggles autoplay. The observer is stashed on the
                   per-slider entry so teardownSlider() disconnects it on rebuild. */
                const startAutoplay = () => {
                    if (!swiper.destroyed && swiper.autoplay && !swiper.autoplay.running) {
                        try { swiper.autoplay.start(); } catch (e) {}
                    }
                };
                const stopAutoplay = () => {
                    if (!swiper.destroyed && swiper.autoplay && swiper.autoplay.running) {
                        try { swiper.autoplay.stop(); } catch (e) {}
                    }
                };
                const targetEl = $scope[0];
                if (typeof IntersectionObserver === 'function' && targetEl) {
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach((e) => {
                            if (e.isIntersecting) { startAutoplay(); } else { stopAutoplay(); }
                        });
                    }, { threshold: 0 });
                    io.observe(targetEl);
                    if (elementId) {
                        let entry = editorState.sliders.get(elementId);
                        if (!entry) { entry = {}; editorState.sliders.set(elementId, entry); }
                        entry.autoplayObserver = io;
                    }
                } else {
                    // No IntersectionObserver support — keep the previous always-on behaviour.
                    startAutoplay();
                }
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
        if (isCardsEffect && sliderDirection === 'vertical') {
            swiperInstancePromise.then((swiper) => {
                if (!swiper) {
                    return;
                }
                $inner.find('.swiper-slide').css('width', '100%');
                $inner.find('.swiper-wrapper').css('align-items', 'stretch');
                const syncHeight = () => {
                    if (typeof swiper.updateAutoHeight === 'function') {
                        swiper.updateAutoHeight(0);
                    }
                    if (typeof swiper.update === 'function') {
                        swiper.update();
                    }
                };
                syncHeight();
                setTimeout(syncHeight, 50);
            });
        }
    }

    function initAdvancedSlider(model) {
        let settings = model?.attributes?.settings?.attributes;
        if ('yes' !== settings?.eael_enable_advanced_slider) {
            return;
        }

        const elementId = model?.attributes?.id;
        if (!elementId) {
            return;
        }

        const element = $(`.elementor-element-${elementId}`);
        const sliderWrapperExists = element.hasClass('eael-advanced-slider-wrapper');
        /* DOM-intact check. Elementor rebuilds a container's .e-con-inner from
           the model whenever a child widget renders asynchronously — video
           oembed resolving, text-editor TinyMCE boot, icon library swap, etc.
           That rebuild wipes the .swiper structure we injected and drops the
           slides back into .e-con-inner, so the slider silently un-inits and
           renders stacked. Image widgets render synchronously and never trigger
           this, which is why image sliders looked fine while video/text/icon
           ones didn't. When our injected DOM is gone we MUST rebuild even
           though the settings signature is unchanged — so the cache skip below
           is gated on the slider DOM still being present, not just on the
           wrapper class (which survives Elementor's inner rebuild). */
        const sliderDomIntact = element.find('.eael-advanced-slider, .eael-advanced-slider-inner, .eael-as-track').length > 0;
        const signature = buildSettingsSignature(settings);
        const cached = editorState.sliderSettings[elementId];

        if (sliderWrapperExists && sliderDomIntact && cached?.signature === signature) {
            return;
        }

        editorState.sliderSettings[elementId] = {
            signature,
        };

        const isMarquee = settings?.eael_advanced_slider_effect_marquee === 'yes' && 'slide' === settings.eael_advanced_slider_effect;

        // Vertical direction is only supported for the Slide effect — see
        // PHP register_main_settings_controls and before_render. Normalize
        // here so editor preview matches frontend for any legacy/saved
        // sliders that have vertical+non-slide combinations.
        const effectForOptions = settings.eael_advanced_slider_effect || 'slide';
        const rawDirection = settings.eael_advanced_slider_direction || 'horizontal';
        const normalizedDirection = ( 'slide' === effectForOptions ) ? rawDirection : 'horizontal';

        const options = {
            effect: effectForOptions,
            direction: normalizedDirection,
            manualScrolling: settings.eael_advanced_slider_manual_scrolling || 'no',
            marquee: settings.eael_advanced_slider_effect_marquee || 'no',
            indicator: settings.eael_advanced_slider_indicator || 'no',
            indicator_type: settings.eael_advanced_slider_indicator_type || 'number',
            navigation_arrows: settings.eael_advanced_slider_navigation_arrows || 'no',
            loop: settings.eael_advanced_slider_loop === 'yes',
            speed: isMarquee ? (settings.eael_advanced_slider_speed_marquee?.size || 50) : (settings.eael_advanced_slider_speed?.size || 300),
            autoplay: settings.eael_advanced_slider_autoplay === 'yes',
            autoplay_speed: settings.eael_advanced_slider_autoplay_speed?.size || 3000,
            items: settings.eael_advanced_slider_per_view?.size || 3,
            gap: isMarquee ? (settings.eael_advanced_slider_item_gap_marquee?.size || 10) : (settings.eael_advanced_slider_item_gap?.size || 10),
            enable_background: settings.eael_advanced_slider_enable_background === 'yes',
            coverflow_rotation: settings.eael_advanced_slider_coverflow_rotation?.size || 100,
            pauseOnHover: isMarquee
                ? (settings.eael_advanced_slider_pause_on_hover_marquee === 'yes' ? 'yes' : 'no')
                : (settings.eael_advanced_slider_pause_on_hover === 'yes' ? 'yes' : 'no'),
            navigation_icon_left: getIconTag(settings.eael_advanced_slider_navigation_icon_left),
            navigation_icon_right: getIconTag(settings.eael_advanced_slider_navigation_icon_right),
            showNavigation: settings?.eael_advanced_slider_enable_navigation,
            // New pagination contract — read from settings, fall through to default.
            pagination_type: settings.eael_advanced_slider_pagination_type || 'tick_bars',
            pagination_position: settings.eael_advanced_slider_pagination_position || 'bottom',
            centered_slides: settings.eael_advanced_slider_centered_slides || 'no',
            index_format: settings.eael_advanced_slider_index_format || 'number',
            // Switcher returns 'yes' when on, '' when off. Default is 'yes'.
            auto_height: (settings.eael_advanced_slider_auto_height === '' || settings.eael_advanced_slider_auto_height === 'no') ? 'no' : 'yes',
        };
        const sliderDirection = options.direction;
        const isCardsEffect = options.effect === 'cards';
        const isEditorMarquee = options.effect === 'slide' && options.direction === 'horizontal' && options.marquee === 'yes';

        if (element.hasClass('e-con--column')) {
            element.css('--flex-direction', 'row')
        }
        let container = element.find(' > .e-con-inner');
        if (!container.length) {
            container = element.find(' > .elementor-container, > .elementor-column-wrap > .elementor-widget-wrap, > .elementor-widget-wrap');
        }

        if (!container.length) {
            container = element;
        }

        // Search for items. If already wrapped, look inside the wrapper.
        // `.e-con` catches nested Elementor flex containers used as slides
        // (4.x renders them with this class instead of `.e-child`).
        let items = container.find(' > .e-child, > .elementor-column, > .elementor-widget, > .e-con');
        if (!items.length && container.find('.eael-advanced-slider').length) {
            // Re-init path: real slides now live nested in .swiper-wrapper, so
            // the direct-child selector above finds nothing and we fall back to
            // collecting the already-built slides. This MUST stay scoped to the
            // MAIN slider wrapper only, via child combinators:
            //   .eael-advanced-slider > .swiper > .swiper-wrapper > .swiper-slide
            //   .eael-advanced-slider > .eael-as-track > *   (marquee)
            // A loose descendant selector (`.eael-advanced-slider .swiper-slide`)
            // also swept in the THUMBNAILS pagination rail — each thumb is a
            // `<div class="swiper-slide eael-as-thumb">` nested under
            // .eael-as-thumbnails — AND Swiper's loop clones. Those got
            // re-collected as real content slides, the rebuilt slider re-cloned
            // the larger set, and the thumbnail rail regenerated from it, so the
            // slide + thumbnail count compounded (~×2–×3) on every control change
            // (3 → 12 → 30 …). The child-combinator scope reaches only the real
            // content slides; `:not(.swiper-slide-duplicate)` drops the loop clones.
            // Editor-only: re-init only fires on control changes.
            items = container.find('.eael-advanced-slider > .swiper > .swiper-wrapper > .swiper-slide:not(.swiper-slide-duplicate), .eael-advanced-slider > .eael-as-track > *');
        }

        if (!items?.length) return;

        /* Phase 5.3 — tear down the previous Swiper instance (and cancel
           any rAFs from the progress-bar tick or vertical refresh pulse)
           BEFORE the DOM is rebuilt. Order matters: destroy first while
           the old DOM is still attached so Swiper's cleanup of inline
           styles / event listeners has something to bind against; THEN
           detach + empty + rebuild. */
        teardownSlider(elementId);

        /* Detach BEFORE remove(). Previously we let .remove() destroy the
           old .eael-advanced-slider wrapper, which silently stripped jQuery
           data and event handlers from every descendant — including the slide
           elements we still hold references to in `items`. The slides came
           back attached but with their state wiped, which manifested as
           "sometimes the slider goes blank or reverts to the default" the
           moment any signature-affecting control changed (direction, effect,
           items per slide, etc.) AFTER the first init. detach() preserves
           data + events on the items so the new init can re-mount them. */
        items.detach();

        // Clean up previous slider after items have been safely detached.
        container.find('.eael-advanced-slider, .eael-as-indicator').remove();

        element.addClass('eael-advanced-slider-wrapper');
        let shapeTop = container.find(' > .elementor-shape.elementor-shape-top');
        let shapeBottom = container.find(' > .elementor-shape.elementor-shape-bottom');
        let overlay = container.find(' > .elementor-element-overlay');

        let sliderHTML = $('<div id="eael-advanced-slider-' + elementId + '" class="eael-advanced-slider"></div>');
        sliderHTML.addClass('eael-advanced-slider-' + options.direction);
        sliderHTML.addClass('eael-advanced-slider-' + options.effect);
        /* Auto-fit marker. On 3D effects (cards/flip/coverflow) the SCSS card
           chrome carries a 360px min-height floor for the demo "card" presence.
           When Auto-fit Slide Height is ON (default) that floor fights the
           auto-fit measure pass — every slide is forced ≥360px so the
           container never hugs shorter content, leaving a tall empty box. This
           class lets the SCSS drop the floor when auto-fit is on so the slider
           sizes to content; the floor still applies when auto-fit is off. */
        if ( ['cards', 'flip', 'coverflow'].indexOf(options.effect) !== -1 && options.auto_height === 'yes' ) {
            sliderHTML.addClass('eael-as-auto-height');
        }
        /* data-easing drives the CSS variable --eael-as-easing for slide
           transition timing. PHP selectors target [data-easing="..."] and set
           the variable; .swiper-wrapper's transition-timing-function reads it. */
        sliderHTML.attr('data-easing', settings.eael_advanced_slider_transition_easing || 'spring');

        /* The .eael-advanced-slider-vertical class drives the vertical SCSS
           block (height:70vh, overflow:hidden, scroll-snap). It must ONLY be
           added when the slider is actually a vertical manual-scroll slider —
           the same gate used for initVerticalSlider() below. Previously this
           keyed on manualScrolling alone, so a HORIZONTAL slider whose saved
           data still carried a leftover `manual_scrolling = yes` (e.g. after
           switching direction back to horizontal) got the vertical class while
           a normal Swiper was initialized. The 70vh vertical height then
           applied to a short horizontal slider, producing a tall blank area
           below it — the "blank screen at the end of the slider section". */
        if (options.effect === 'slide' && options.direction === 'vertical' && options.manualScrolling === 'yes') {
            sliderHTML.addClass('eael-advanced-slider-vertical');
        }

        let swiperWrapper;
        let $sliderInner;  // The .swiper element that Swiper actually inits on
        if (isEditorMarquee) {
            sliderHTML.addClass('eael-advanced-slider-marquee');
            swiperWrapper = $('<div class="eael-as-track"></div>');
            sliderHTML.append(swiperWrapper);
            $sliderInner = sliderHTML; // marquee has no separate inner
        } else {
            // Wrap the .swiper element as a CHILD of .eael-advanced-slider so the outer
            // wrapper isn't itself a Swiper container. This is critical for 3D effects
            // (cards / flip / coverflow): Swiper applies preserve-3d on its own container,
            // and any sibling DOM (like our pagination) needs to live OUTSIDE that 3D
            // context — i.e. as a child of the outer .eael-advanced-slider, not inside .swiper.
            $sliderInner = $('<div class="swiper"></div>');
            swiperWrapper = $('<div class="swiper-wrapper"></div>');
            $sliderInner.append(swiperWrapper);
            sliderHTML.append($sliderInner);
        }

        items.each(function () {
            if (isEditorMarquee) {
                $(this).removeClass('swiper-slide');
            } else {
                $(this).addClass('swiper-slide');
            }
            swiperWrapper.append(this);
        });

        container.empty();

        if (overlay.length) {
            container.append(overlay);
        }

        if (shapeTop.length) {
            container.append(shapeTop);
        }

        if (shapeBottom.length) {
            container.append(shapeBottom);
        }

        container.append(sliderHTML);

        container.css('height', 'auto');
        if (options.effect === 'slide' && options.direction === 'vertical' && options.manualScrolling === 'yes') {
            initVerticalSlider(sliderHTML, options, $, settings);
        } else if (isEditorMarquee) {
            initMarqueeSlider(sliderHTML, options, $);
        } else {
            initSwiperSlider(sliderHTML, options, $);
        }

        if (sliderDirection === 'vertical') {
            if (isCardsEffect) {
                $(sliderHTML).css('height', 'auto');
                container.css('height', 'auto');
            } else {
                /* stop hardcoding inline height here. The PHP control
                   eael_advanced_slider_height now applies via correctly-scoped
                   {{WRAPPER}} .eael-advanced-slider-vertical selectors and the
                   user's chosen value (default 70vh) is the single source of
                   truth — both in editor and on frontend. We only nudge the
                   container off any leftover explicit height from a previous
                   horizontal init so the CSS rule wins. */
                $(sliderHTML).css('height', '');
                container.css('height', '');
            }
        }
    }
    /**
     * Walk the editor's model tree and init any container that is a Section
     * Slider. Recurses into ALL non-widget containers (sections, columns,
     * containers) so deeply-nested sliders are picked up. Widgets are leaves
     * and never need walking.
     *
     * Performance notes:
     *  - Iterative DFS with an explicit stack avoids deep recursion overhead
     *    on pages with many nested containers.
     *  - For each slider model, initAdvancedSlider's signature check is the
     *    main perf gate — it skips work when nothing relevant changed.
     */
    function getAdvancedSliderSettings(models) {
        if (!models || !Array.isArray(models) || !models.length) return;

        const stack = [models];
        while (stack.length) {
            const current = stack.pop();
            if (!Array.isArray(current)) continue;
            for (let i = 0; i < current.length; i++) {
                const model = current[i];
                if (!model || !model.attributes) continue;
                if (model.attributes.elType === 'widget') continue;

                if ('yes' === model?.attributes?.settings?.attributes?.eael_enable_advanced_slider) {
                    initAdvancedSlider(model);
                }
                const children = model.attributes?.elements?.models;
                if (children && children.length) stack.push(children);
            }
        }
    }
    const runInit = () => {
        if (editorState.inProgress) {
            return;
        }

        editorState.inProgress = true;
        editorState.lastRunAt = Date.now();
        try {
            getAdvancedSliderSettings(window.elementor?.elements?.models);
        } finally {
            editorState.inProgress = false;
        }
    };

    if (editorState.queued) {
        return;
    }

    const elapsed = Date.now() - editorState.lastRunAt;
    const delay = elapsed >= INIT_DEBOUNCE_MS ? 0 : (INIT_DEBOUNCE_MS - elapsed);
    editorState.queued = true;

    window.setTimeout(() => {
        editorState.queued = false;
        runInit();
    }, delay);
}

jQuery(window).on("elementor/frontend/init", function () {
    if (eael.elementStatusCheck('eaelAdvancedSliderEditor')) {
        return false;
    }
    elementorFrontend.hooks.addAction("frontend/element_ready/section", AdvancedSliderHandler);
    elementorFrontend.hooks.addAction("frontend/element_ready/container", AdvancedSliderHandler);
    elementorFrontend.hooks.addAction("frontend/element_ready/column", AdvancedSliderHandler);

    /* Re-init when a child widget (re)renders inside a slider. Elementor fires
       this for the INITIAL render of widgets dropped into a slider container
       AND for every async re-render afterwards (video oembed resolving,
       text-editor TinyMCE boot, icon swaps). Without it the slider only
       (re)builds on a slider control change, so a container whose slides are
       async-rendering widgets stays stacked after Elementor rebuilds the
       container DOM. AdvancedSliderHandler ignores its $scope arg here and just
       schedules a single debounced tree walk; the per-slider signature +
       DOM-intact guard in initAdvancedSlider keeps already-built, still-intact
       sliders from rebuilding, so these extra calls coalesce into cheap
       no-ops. Moving slides into .swiper-wrapper doesn't re-render the widget
       views, so this can't feed back into an init loop. */
    elementorFrontend.hooks.addAction("frontend/element_ready/widget", AdvancedSliderHandler);

    const editorState = window.__eaelAdvancedSliderEditorState || (window.__eaelAdvancedSliderEditorState = {
        queued: false,
        inProgress: false,
        lastRunAt: 0,
        changeListenerBound: false,
    });

    if (!editorState.changeListenerBound && window.elementor?.channels?.editor) {
        editorState.changeListenerBound = true;

        // PERF: Pre-built skip list. These controls drive only CSS via
        // Elementor's selector-based injection — they never need to trigger
        // a slider re-init. Skipping them at the change-listener level
        // avoids even calling AdvancedSliderHandler / scheduling a debounced
        // runInit, which is the cheapest possible no-op.
        const CSS_ONLY_CONTROLS = new Set([
            'eael_advanced_slider_swiper_arrow_size',
            'eael_advanced_slider_swiper_arrow_color',
            'eael_advanced_slider_swiper_arrow_color_hover',
            'eael_advanced_slider_swiper_dot_size',
            'eael_advanced_slider_swiper_dot_color',
            'eael_advanced_slider_swiper_dot_active_color',
            'eael_advanced_slider_dots_border_radius',
            'eael_advanced_slider_dots_gap',
            'eael_advanced_slider_pagination_color',
            'eael_advanced_slider_pagination_active_color',
            'eael_advanced_slider_tickbars_height',
            'eael_advanced_slider_tickbars_active_height',
            'eael_advanced_slider_tickbars_gap',
            'eael_advanced_slider_progressbar_height',
            'eael_advanced_slider_progressbar_max_width',
            'eael_advanced_slider_indicator_color',
            'eael_advanced_slider_indicator_bar_width',
            'eael_advanced_slider_indicator_bar_color',
            'eael_advanced_slider_3d_height',
            'eael_advanced_slider_height',
            'eael_advanced_slider_background_color',
        ]);
        // Strip Elementor's responsive device suffix to find the base control name.
        // CRITICAL: do NOT skip every name ending in _tablet / _mobile / etc. —
        // some responsive controls (Items Per Slide, Item Gap) DO need to
        // trigger a re-render. Only skip if the BASE name is in CSS_ONLY.
        const DEVICE_SUFFIX_RE = /^(.+?)(_tablet|_mobile|_widescreen|_laptop|_mobile_extra|_tablet_extra)$/;
        const isCssOnlyControl = (name) => {
            if (CSS_ONLY_CONTROLS.has(name)) return true;
            if (name.indexOf('_typography') !== -1) return true;
            const m = name.match(DEVICE_SUFFIX_RE);
            return !!(m && CSS_ONLY_CONTROLS.has(m[1]));
        };

        elementor.channels.editor.on('change', function (controlView) {
            const controlName = controlView?.model?.get('name');
            if (!controlName) return;

            if ('eael_enable_advanced_slider' !== controlName
                && !controlName.startsWith('eael_advanced_slider_')
                && 'content_width' !== controlName
                && 'direction' !== controlName
                && 'flex_direction' !== controlName) {
                return;
            }

            // Skip CSS-only controls — Elementor handles them via selectors.
            if (isCssOnlyControl(controlName)) return;

            // PERF: Don't clear the cache. The signature check inside
            // initAdvancedSlider detects which sliders actually changed.
            // CSS-only fields aren't in the signature so they never trigger
            // expensive DOM rebuilds anyway — but the early-skip above
            // saves the entire AdvancedSliderHandler call too.
            AdvancedSliderHandler(null, jQuery);
        });
    }

    // Sync button — explicit "Refresh Preview" trigger. Forces a full
    // re-render by invalidating the cache and running the handler again.
    // This is the EA Parallax-style escape hatch for cases where the
    // slider DOM gets out of sync (e.g. thumbnails not picking up images
    // because the slide DOM rendered after our init pass).
    jQuery(document).on('click', '.eael-as-sync-btn', function (e) {
        e.preventDefault();
        const editorState = window.__eaelAdvancedSliderEditorState;
        if (editorState) editorState.sliderSettings = {};
        AdvancedSliderHandler(null, jQuery);
    });
});

})();
