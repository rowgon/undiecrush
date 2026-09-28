/**
 * EA Advanced Slider — shared helpers
 *
 * Imported by BOTH `src/js/view/advanced-slider.js` (frontend + editor
 * preview iframe) and `src/js/edit/advanced-slider.js` (Elementor editor
 * control-change handler). Phase 6 is gradually moving duplicated helpers
 * here; until that migration is complete, the view and edit entries will
 * still each contain their own copies of some helpers — extract one piece
 * at a time and verify the editor + frontend both still work before
 * lifting the next one.
 *
 * Webpack 4's entry globber only scans `src/js/view/*` and `src/js/edit/*`
 * (see webpack.config.js — `outputEntry()`), so files under `src/js/_shared/`
 * are never treated as entry points. They're inlined into any bundle that
 * imports them. Net cost: one copy each in view + edit's minified output,
 * but a SINGLE source of truth in this directory.
 *
 * Conventions:
 *   - Export only pure functions / constants with no closure dependencies
 *     on either bundle's runtime state.
 *   - Helpers that need jQuery should accept `$` as a parameter instead
 *     of relying on a closure (view JS has helpers at IIFE-top, edit JS
 *     re-creates them per call — neither contract carries here).
 *   - Keep editor-only behaviour (rAF token tracking, swiper.destroy
 *     teardown) in `edit/advanced-slider.js`. This file is shared, not
 *     editor-flavoured.
 */

/* ─────────── Constants ─────────── */

/**
 * Easing presets for the Advanced Slider's slide-transition timing.
 *
 * Both bundles read `$scope.attr('data-easing')` (set by PHP from the
 * `eael_advanced_slider_transition_easing` control) and look the preset
 * up in this map. The selected curve is written via
 * `setProperty('transition-timing-function', curve, 'important')` so it
 * beats the SCSS rule `transition-timing-function: var(--eael-as-easing)
 * !important` in the cascade. See applyTransitionEasing in either entry.
 */
export const ADVANCED_SLIDER_EASING_CURVES = {
    smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
    snappy: 'cubic-bezier(0.7, 0, 0.3, 1)',
    spring: 'cubic-bezier(0.22, 1, 0.36, 1)',
    soft:   'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
};

/**
 * Valid pagination types the user can select in the
 * `eael_advanced_slider_pagination_type` control. Anything else (including
 * an unrecognised legacy value) is coerced to 'tick_bars' by
 * getPaginationType() so the slider never renders without pagination.
 *
 * Order: discrete (Swiper-pagination-module) types first, custom-DOM
 * types last. Not user-facing — getPaginationType() is the only consumer.
 */
const PAGINATION_TYPES = ['dots', 'dots_vertical', 'fraction', 'tick_bars', 'progress_bar', 'thumbnails', 'numbered_index'];

/* ─────────── Pure helpers (no jQuery, no closure deps) ─────────── */

/**
 * 0 → 'A', 1 → 'B', 2 → 'C', ...  — index-label generator for the
 * 'numbered_index' pagination type's 'alphabet' format.
 * Wraps at 25 → 'Z'; consumers should cap slide count or fall through to
 * the default 'number' format for sliders with > 26 slides.
 */
export const toAlphabet = (num) => {
    return String.fromCharCode(65 + num); // 0 → A, 1 → B, 2 → C ...
};

/**
 * 1 → 'I', 4 → 'IV', 5 → 'V', 9 → 'IX', 10 → 'X', etc. — Roman numeral
 * generator for the 'numbered_index' pagination type's 'roman' format.
 * Slide indices are 1-based by convention for this format.
 */
export const toRoman = (num) => {
    const map = [
        { v: 1000, s: 'M' }, { v: 900, s: 'CM' }, { v: 500, s: 'D' }, { v: 400, s: 'CD' },
        { v: 100, s: 'C' }, { v: 90, s: 'XC' }, { v: 50, s: 'L' }, { v: 40, s: 'XL' },
        { v: 10, s: 'X' }, { v: 9, s: 'IX' }, { v: 5, s: 'V' }, { v: 4, s: 'IV' }, { v: 1, s: 'I' }
    ];
    let result = '';
    map.forEach(o => {
        while (num >= o.v) {
            result += o.s;
            num -= o.v;
        }
    });
    return result;
};

/**
 * Minimal HTML-entity escape. Used when injecting user-controlled strings
 * (slide image URLs, alt text) into innerHTML / attribute values.
 *
 * NOT a substitute for proper PHP escaping at render time — this is a
 * client-side defence-in-depth for strings that came from Elementor's
 * model (already kses'd at save time) and need to survive a JS round-trip.
 */
export const escapeHtml = (str) => {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
};

/**
 * Resolve the active pagination type from saved settings.
 *
 * - 'none' → renders no pagination
 * - Any value in PAGINATION_TYPES → that exact value
 * - Anything else (including undefined) → fall through to 'tick_bars'
 *   (the default for fresh sliders so the user sees pagination immediately
 *   and can switch it off via the dropdown).
 */
export const getPaginationType = (options) => {
    if (options?.pagination_type === 'none') return 'none';
    if (options?.pagination_type && PAGINATION_TYPES.indexOf(options.pagination_type) !== -1) {
        return options.pagination_type;
    }
    return 'tick_bars';
};

/**
 * Resolve the active pagination position from saved settings.
 *
 * Dots Vertical is the one type whose position is structurally fixed
 * (right rail) — the position control is hidden in the editor for that
 * type but a saved slider could still carry a stale position value, so
 * we force-override here. Everything else falls through to the saved
 * value or 'bottom' as default.
 */
export const getPaginationPosition = (options) => {
    if (getPaginationType(options) === 'dots_vertical') return 'right_center';
    return options?.pagination_position || 'bottom';
};

/**
 * Build the Swiper pagination config object for a given type.
 *
 * Returns null for types that don't use Swiper's pagination module —
 * 'progress_bar', 'thumbnails', 'numbered_index' all render via custom
 * DOM in their own build*Nav() helpers (those stay in the view/edit
 * entries because they wire in Swiper-instance event listeners and
 * editor-only state).
 *
 * NOTE: do NOT pass `verticalClass: ''` here. Modern Swiper calls
 * `classList.add(params.verticalClass)` which throws DOMTokenList.add:
 * "The empty string is not a valid token", aborting the entire
 * swiperInstancePromise chain. We let Swiper apply its default
 * `.swiper-pagination-vertical` class and override the visual position
 * via the SCSS rule
 *   `.eael-advanced-slider-vertical .swiper-pagination.swiper-pagination-vertical`.
 */
export const buildPaginationConfig = (type, el) => {
    if (!el || type === 'none' || type === 'progress_bar' || type === 'thumbnails' || type === 'numbered_index') return null;
    const base = { el: el, clickable: true };
    if (type === 'fraction') {
        return Object.assign({}, base, {
            type: 'fraction',
            renderFraction: (currentClass, totalClass) =>
                '<span class="' + currentClass + ' eael-as-fraction-current"></span>' +
                '<span class="eael-as-fraction-sep">/</span>' +
                '<span class="' + totalClass + ' eael-as-fraction-total"></span>'
        });
    }
    if (type === 'tick_bars') {
        // Bullets (not custom) so each tick is a clickable button.
        // CSS styles `.eael-as-tick-bar` as a vertical bar.
        return Object.assign({}, base, {
            type: 'bullets',
            bulletClass: 'eael-as-tick-bar',
            bulletActiveClass: 'is-active',
            bulletElement: 'button'
        });
    }
    // 'dots' and 'dots_vertical'
    return Object.assign({}, base, {
        type: 'bullets',
        bulletClass: 'eael-as-bullet',
        bulletActiveClass: 'eael-as-bullet-active',
        bulletElement: 'button'
    });
};

/* ─────────── jQuery-aware helpers ($ passed as parameter) ─────────── */

/**
 * Create the pagination host element and append it to $scope (the outer
 * .eael-advanced-slider). Always mount OUTSIDE the .swiper child element so
 * 3D effects (cards / flip / coverflow) — which apply preserve-3d to the
 * .swiper container — don't drag pagination into their 3D context (where
 * it would inherit perspective transforms and render at unintended depth).
 *
 * Signature: ($scope, $inner, type, position, $) — note `$inner` is
 * currently unused inside the helper but kept on the signature for
 * symmetry with future variants that may need it (e.g. mounting inside
 * .swiper for specific types).
 */
export const mountPaginationHost = ($scope, $inner, type, position, $) => {
    if (type === 'none') return null;
    const typeClass = 'eael-as-pagination--type-' + type.replace(/_/g, '-');
    const posClass  = 'eael-as-pagination--position-' + position.replace(/_/g, '-');
    const $host = $('<div class="eael-as-pagination ' + typeClass + ' ' + posClass + '"></div>');
    $scope.append($host);
    return $host;
};

/* ─────────── Swiper loader ─────────── */

/**
 * Internal: wrap synchronous `new Swiper(...)` in a Promise so the loader
 * always returns the same shape.
 */
const swiperPromise = (swiperElement, swiperConfig) => {
    return new Promise((resolve) => {
        const swiperInstance = new Swiper(swiperElement, swiperConfig);
        resolve(swiperInstance);
    });
};

/**
 * Async Swiper bootstrapper. Returns a Promise<Swiper>.
 *
 * Two paths because Elementor lazy-loads Swiper:
 *   - If global `Swiper` (constructor) is already on window, use it
 *     directly via swiperPromise.
 *   - Otherwise use elementorFrontend.utils.swiper, which kicks off the
 *     async fetch and resolves once Swiper's bundle has loaded.
 *
 * `typeof Swiper === 'function'` covers the loaded-but-not-yet-instantiated
 * case; `typeof Swiper === 'undefined'` is the not-yet-loaded case (also
 * routed through the async loader, falling through here would throw a
 * ReferenceError).
 */
export const swiperLoader = (swiperElement, swiperConfig) => {
    if ('undefined' === typeof Swiper || 'function' === typeof Swiper) {
        const asyncSwiper = elementorFrontend.utils.swiper;
        return new asyncSwiper(swiperElement, swiperConfig).then((newSwiperInstance) => {
            return newSwiperInstance;
        });
    }
    return swiperPromise(swiperElement, swiperConfig);
};
