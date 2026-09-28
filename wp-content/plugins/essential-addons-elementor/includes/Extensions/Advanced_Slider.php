<?php
namespace Essential_Addons_Elementor\Pro\Extensions;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Essential_Addons_Elementor\Classes\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Advanced_Slider {
    public function __construct() {
		// Sections that may contain inner containers
        add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_controls' ] );

        // Columns that may contain inner containers
        add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this, 'register_controls' ] );

        // Containers (Flex containers in Elementor)
        add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ] );

		add_action( 'elementor/frontend/before_render', [ $this, 'before_render' ], 100, 1 );
    }

    private function navigation_conditions( $common_params = [] ) {
        $terms = [
            // 1. Horizontal Slide
            [
                'relation' => 'and',
                'terms' => [
                    [ 'name' => 'eael_enable_advanced_slider', 'operator' => '==', 'value' => 'yes' ],
                    [ 'name' => 'eael_advanced_slider_effect', 'operator' => '==', 'value' => 'slide' ],
                    [ 'name' => 'eael_advanced_slider_direction', 'operator' => '==', 'value' => 'horizontal' ],
                    [ 'name' => 'eael_advanced_slider_effect_marquee', 'operator' => '!=', 'value' => 'yes' ],
                ],
            ],
            // 2. Vertical Slide
            [
                'relation' => 'and',
                'terms' => [
                    [ 'name' => 'eael_enable_advanced_slider', 'operator' => '==', 'value' => 'yes' ],
                    [ 'name' => 'eael_advanced_slider_effect', 'operator' => '==', 'value' => 'slide' ],
                    [ 'name' => 'eael_advanced_slider_direction', 'operator' => '==', 'value' => 'vertical' ],
                    [ 'name' => 'eael_advanced_slider_manual_scrolling', 'operator' => '!=', 'value' => 'yes' ],
                ],
            ],
            // 3. Coverflow/Cards/Flip
            [
                'relation' => 'and',
                'terms' => [
                    [ 'name' => 'eael_enable_advanced_slider', 'operator' => '==', 'value' => 'yes' ],
                    [ 'name' => 'eael_advanced_slider_effect', 'operator' => 'in', 'value' => [ 'coverflow', 'cards', 'flip' ] ],
                    [ 'name' => 'eael_advanced_slider_direction', 'operator' => '==', 'value' => 'horizontal' ],
                    [ 'name' => 'eael_advanced_slider_effect_marquee', 'operator' => '!=', 'value' => 'yes' ],
                ],
            ],
        ];

        // Inject $common_params into every single group (repetitive but flat)
        if ( ! empty( $common_params ) ) {
            foreach ( $terms as &$group ) {
                $group['terms'] = array_merge( $common_params, $group['terms'] );
            }
        }

        return [
            'relation' => 'or',
            'terms' => $terms,
        ];
    }

    private function items_conditions( $conditions = [], $effect_not_in = [] ) {
        $items_conditions = [
            'relation' => 'or',
            'terms' => [
                [
                    'relation' => 'and',
                    'terms' => [
                        [
                            'name' => 'eael_enable_advanced_slider',
                            'operator' => '===',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'eael_advanced_slider_effect',
                            'operator' => '===',
                            'value' => 'slide',
                        ],
                        [
                            'name' => 'eael_advanced_slider_effect_marquee',
                            'operator' => '!==',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'eael_advanced_slider_direction',
                            'operator' => '===',
                            'value' => 'horizontal',
                        ],
                    ],
                ],
                [
                    'relation' => 'and',
                    'terms' => [
                        [
                            'name' => 'eael_enable_advanced_slider',
                            'operator' => '===',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'eael_advanced_slider_effect',
                            'operator' => '===',
                            'value' => 'slide',
                        ],
                        [
                            'name' => 'eael_advanced_slider_manual_scrolling',
                            'operator' => '!==',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'eael_advanced_slider_direction',
                            'operator' => '===',
                            'value' => 'vertical',
                        ],
                    ],
                ],
                [
                    'relation' => 'and',
                    'terms' => [
                        [
                            'name' => 'eael_enable_advanced_slider',
                            'operator' => '===',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'eael_advanced_slider_effect',
                            'operator' => '!in',
                            'value' => array_merge( [ 'slide' ], $effect_not_in ),
                        ],
                    ],
                ],
            ]
        ];

        if ( ! empty( $conditions ) ) {
            $items_conditions['terms'][] = $conditions;
        }

        return $items_conditions;
    }
    public function register_controls( $element ) {
        // Only add controls if element is a section, column or container
        $inner_types = [ 'section', 'column', 'container' ];

        if ( ! in_array( $element->get_name(), $inner_types ) ) {
            return; // skip widgets
        }

        $element->start_controls_section(
            'eael_advanced_slider_section',
            [
                'label' => __( '<i class="eaicon-logo"></i> Advanced Slider', 'essential-addons-elementor' ),
                'tab'   => Controls_Manager::TAB_ADVANCED
            ]
        );

        $this->register_main_settings_controls( $element );
        $this->register_3d_dimensions_controls( $element );
        $this->register_speed_controls( $element );
        $this->register_items_gap_controls( $element );
        $this->register_background_controls( $element );
        $this->register_autoplay_controls( $element );
        $this->register_indicator_controls( $element );
        $this->register_navigation_controls( $element );

        $element->end_controls_section();
    }

    private function register_main_settings_controls( $element ) {
        $element->add_control(
            'eael_enable_advanced_slider',
            [
                'label'        => __( 'Enable Advanced Slider', 'essential-addons-elementor' ),
                'description'  => __( 'Turn this section, column or container into a slider.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes'
            ]
        );

        /* Sync Slider Preview button commented out per user request.
           v17–v21 hardened the auto-init enough that the manual refresh
           is no longer pulling its weight. Keeping the underlying handler
           in edit JS (cheap, useful as an internal escape hatch and as a
           one-liner reactivation if we re-introduce the button) — only
           the visible control is removed. To restore: uncomment this block
           and ship; no JS changes needed because the click delegate at the
           bottom of edit JS still listens for `.eael-as-sync-btn`.

        $element->add_control(
            'eael_advanced_slider_sync_button',
            [
                'label'       => esc_html__( 'Sync Slider Preview', 'essential-addons-elementor' ),
                'type'        => Controls_Manager::RAW_HTML,
                'raw'         => '<button type="button" class="eael-as-sync-btn elementor-button elementor-button-default" data-action="eael-advanced-slider-sync" style="display:flex;align-items:center;gap:6px;justify-content:center;width:100%;padding:9px 12px;cursor:pointer;"><i class="eicon-sync" aria-hidden="true"></i> ' . esc_html__( 'Refresh Preview', 'essential-addons-elementor' ) . '</button>',
                'content_classes' => 'eael-as-sync-button-wrap',
                'description' => esc_html__( 'Click if the slider in the preview iframe looks stale (thumbnails showing numbers, layout off-center, etc.). Hard-refresh the slider DOM.', 'essential-addons-elementor' ),
                'condition'   => [
                    'eael_enable_advanced_slider' => 'yes',
                ],
            ]
        );
        */

        /**
         * Slider Effect — visual skin-style selector. CHOOSE renders each option
         * as a labeled icon button (matches the EA Post Grid skin selection UI).
         */
        $element->add_control(
            'eael_advanced_slider_effect',
            [
                'label'   => esc_html__( 'Slider Effect', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'slide',
                'toggle'  => false,
                'options' => [
                    'slide' => [
                        'title' => esc_html__( 'Slide — horizontal/vertical scrolling', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-slider-push',
                    ],
                    'fade' => [
                        'title' => esc_html__( 'Fade — cross-dissolve between slides', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-eye',
                    ],
                    'coverflow' => [
                        'title' => esc_html__( 'Coverflow — 3D carousel', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-carousel-loop',
                    ],
                    'cards' => [
                        'title' => esc_html__( 'Cards — stacked card deck', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-flip-box',
                    ],
                    'flip' => [
                        'title' => esc_html__( 'Flip — 3D flip transition', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-image-rollover',
                    ],
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_direction',
            [
                'label'   => esc_html__( 'Slide Direction', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'horizontal' => [
                        'title' => esc_html__( 'Horizontal', 'essential-addons-elementor' ),
                        'icon' => 'eicon-slides',
                    ],
                    'vertical' => [
                        'title' => esc_html__( 'Vertical', 'essential-addons-elementor' ),
                        'icon' => 'eicon-slider-vertical',
                    ],
                ],
                'default'   => 'horizontal',
                'toggle'    => false,
                /* Vertical direction is only supported for the Slide effect.
                   3D effects (coverflow, cards, flip) are designed for
                   horizontal motion — Swiper does not lay them out cleanly
                   in vertical direction. Hiding the control for non-slide
                   effects prevents users from picking a broken combination. */
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_manual_scrolling',
            [
                'label'        => esc_html__( 'Enable Manual Scrolling', 'essential-addons-elementor' ),
                'description'  => esc_html__( 'Native scroll-snap with optional indicator. No Swiper.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_effect' => 'slide',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_effect_marquee',
            [
                'label'        => esc_html__( 'Enable Marquee', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_direction' => 'horizontal',
                    'eael_advanced_slider_effect' => 'slide',
                ],
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_height',
            [
                'label' => esc_html__( 'Height', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 1000,
                        'step' => 1,
                    ],
                    'vh' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'vh',
                    'size' => 70,
                ],
                'selectors' => [
                    /* The Height control has to bind on TWO completely
                       different DOM shapes because Pro's editor preview
                       rebuilds the slider from scratch while the frontend
                       reuses Elementor's existing wrapper:

                         Editor preview ({{WRAPPER}} is the outer
                         .elementor-element-XYZ; the slider div is a CHILD
                         carrying the eael-advanced-slider classes):

                             .elementor-element-XYZ
                               > .eael-advanced-slider.eael-advanced-slider-vertical
                                 > .swiper > .swiper-wrapper        (Swiper modes)
                                 OR
                                 > .eael-advanced-slider-inner       (manual-scroll)

                         Frontend ({{WRAPPER}} IS the section/container,
                         add_render_attribute pushes the classes onto the
                         same element — no nested .eael-advanced-slider div):

                             .elementor-element-XYZ.eael-advanced-slider
                               .eael-advanced-slider-vertical
                                 > .e-con-inner.swiper > .swiper-wrapper  (Swiper)
                                 OR
                                 > .e-con-inner.eael-advanced-slider-inner (manual-scroll)

                       The descendant-only selectors below (with a space
                       after {{WRAPPER}}) only match the editor shape; the
                       same-element selectors (no space — {{WRAPPER}}.class)
                       are what catches the frontend. Both forms are listed
                       so the rule fires regardless of which DOM is live. */

                    // Editor shape — .eael-advanced-slider-vertical is a child of {{WRAPPER}}.
                    '{{WRAPPER}} .eael-advanced-slider.eael-advanced-slider-vertical' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider.eael-advanced-slider-vertical > .swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider.eael-advanced-slider-vertical.swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider.eael-advanced-slider-vertical .eael-advanced-slider-inner' => 'height: {{SIZE}}{{UNIT}} !important;',

                    // Frontend shape — .eael-advanced-slider-vertical IS {{WRAPPER}}.
                    '{{WRAPPER}}.eael-advanced-slider.eael-advanced-slider-vertical' => 'height: {{SIZE}}{{UNIT}} !important;',
                    /* Direct child `> .swiper` (mirrors the editor selector on
                       line 371). A bare descendant `.swiper` here also matched
                       the nested thumbnails sub-swiper
                       (`.eael-as-thumbnails-swiper.swiper`), forcing the thumb
                       strip to the full slider height and collapsing the main
                       swiper to ~0 — vertical + thumbnails-below showed no
                       slides on the frontend. */
                    '{{WRAPPER}}.eael-advanced-slider.eael-advanced-slider-vertical > .swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}}.eael-advanced-slider.eael-advanced-slider-vertical .eael-advanced-slider-inner' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    /* dropped the `effect != fade` exclusion. Vertical fade
                       sliders need a height too (without one, Swiper's fade math
                       collapses since the .swiper container has no fixed height
                       to measure slides against). */
                    'eael_enable_advanced_slider'    => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                ],
            ]
        );
    }

    private function register_3d_dimensions_controls( $element ) {
        /**
         * Auto-fit Slider Height — Default ON. The slider container
         * automatically takes the MAX content height across all slides, so the
         * tallest slide isn't clipped and the stack visually fits all cards.
         * Disable this to get a manual Slider Height control instead.
         */
        $element->add_control(
            'eael_advanced_slider_auto_height',
            [
                'label'        => esc_html__( 'Auto-fit Slide Height', 'essential-addons-elementor' ),
                'description'  => esc_html__( 'Container takes the tallest slide\'s height so nothing is clipped. Turn off to set a fixed height manually.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'cards', 'flip', 'coverflow' ],
                ],
            ]
        );

        /**
         * Manual 3D Slider Height — only when auto-fit is off.
         */
        $element->add_responsive_control(
            'eael_advanced_slider_3d_height',
            [
                'label' => esc_html__( 'Manual Slider Height', 'essential-addons-elementor' ),
                'description' => esc_html__( 'Fixed height for cards/flip/coverflow. Slides clip content above this height.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range' => [
                    'px' => [ 'min' => 200, 'max' => 1000, 'step' => 10 ],
                    'vh' => [ 'min' => 30,  'max' => 100,  'step' => 1  ],
                ],
                'default' => [ 'unit' => 'px', 'size' => 580 ],
                'selectors' => [
                    /* `.swiper` is qualified with :not(.eael-as-thumbnails-swiper)
                       on every descendant form — the thumbnails pagination rail
                       mounts a nested `.eael-as-thumbnails-swiper.swiper` inside
                       the same effect wrapper, and a bare `.swiper` descendant
                       selector forced the thumb strip to the full slider height
                       (collapsing/overriding the Thumbnail Size control). The
                       same-element forms (`.effect.swiper`, no space) are the
                       wrapper itself and never match a thumb, so they're left bare. */
                    '{{WRAPPER}} .eael-advanced-slider-cards .swiper:not(.eael-as-thumbnails-swiper), {{WRAPPER}} .eael-advanced-slider-cards.swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider-flip .swiper:not(.eael-as-thumbnails-swiper), {{WRAPPER}} .eael-advanced-slider-flip.swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider-coverflow .swiper:not(.eael-as-thumbnails-swiper), {{WRAPPER}} .eael-advanced-slider-coverflow.swiper' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider-cards' => 'min-height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .eael-advanced-slider-flip' => 'min-height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .eael-advanced-slider-coverflow' => 'min-height: {{SIZE}}{{UNIT}};',

                    // Frontend shape — effect class IS {{WRAPPER}}; .swiper is the child.
                    '{{WRAPPER}}.eael-advanced-slider-cards .swiper:not(.eael-as-thumbnails-swiper)' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}}.eael-advanced-slider-flip .swiper:not(.eael-as-thumbnails-swiper)' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}}.eael-advanced-slider-coverflow .swiper:not(.eael-as-thumbnails-swiper)' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}}.eael-advanced-slider-cards' => 'min-height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.eael-advanced-slider-flip' => 'min-height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.eael-advanced-slider-coverflow' => 'min-height: {{SIZE}}{{UNIT}};',

                    /* Per-slide min-height must skip the thumbnail tiles
                       (.eael-as-thumb) on ALL three effects and BOTH DOM shapes —
                       otherwise the 3D "Manual Slider Height" min-height wins over
                       the Thumbnail Size control's height (min-height beats height)
                       and the thumbs balloon to the full slider height. */
                    '{{WRAPPER}} .eael-advanced-slider-cards .swiper-slide:not(.eael-as-thumb), {{WRAPPER}}.eael-advanced-slider-cards .swiper-slide:not(.eael-as-thumb)' => 'min-height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider-flip .swiper-slide:not(.eael-as-thumb), {{WRAPPER}}.eael-advanced-slider-flip .swiper-slide:not(.eael-as-thumb)' => 'min-height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .eael-advanced-slider-coverflow .swiper-slide:not(.eael-as-thumb), {{WRAPPER}}.eael-advanced-slider-coverflow .swiper-slide:not(.eael-as-thumb)' => 'min-height: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'eael_enable_advanced_slider'      => 'yes',
                    'eael_advanced_slider_effect'      => [ 'cards', 'flip', 'coverflow' ],
                    'eael_advanced_slider_auto_height!' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_coverflow_rotation',
            [
                'label' => esc_html__( 'Rotation', 'essential-addons-elementor' ),
                'description' => esc_html__( 'How much the side slides rotate. 35° gives a readable peek; 60–80° is dramatic; 100°+ pushes side slides edge-on.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'deg' ],
                'range' => [
                    'deg' => [
                        'min' => 0,
                        'max' => 90,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'deg',
                    'size' => 35,
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'coverflow',
                ]
            ]
        );

        /**
         * Coverflow Depth — how far back the side slides recede in 3D space.
         * Higher = more dramatic perspective. 100 (Swiper default) is conservative;
         * 200–250 matches the V4 "Photographer portfolio" reference design.
         */
        $element->add_control(
            'eael_advanced_slider_coverflow_depth',
            [
                'label' => esc_html__( 'Depth', 'essential-addons-elementor' ),
                'description' => esc_html__( '3D recession of side slides. Larger values push them further back.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 500, 'step' => 5 ],
                ],
                'default' => [ 'unit' => 'px', 'size' => 200 ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'coverflow',
                ]
            ]
        );

        /**
         * Coverflow Stretch — horizontal pull/push of side slides toward the
         * center. Negative values pull them in (overlap with active), positive
         * push them out. 0 (default) = natural spacing. Useful for tight stacks.
         */
        $element->add_control(
            'eael_advanced_slider_coverflow_stretch',
            [
                'label' => esc_html__( 'Stretch', 'essential-addons-elementor' ),
                'description' => esc_html__( 'Horizontal spacing of side slides. Negative = overlap, positive = pull apart.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [ 'min' => -200, 'max' => 200, 'step' => 5 ],
                ],
                'default' => [ 'unit' => 'px', 'size' => 0 ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'coverflow',
                ]
            ]
        );
        
    }

    private function register_speed_controls( $element ) {
        /* Default Transition Speed bumped 400 → 800ms. At 400ms the
           difference between the four easing presets (smooth/snappy/spring/
           soft) is mathematically present in the cubic-bezier curve but
           visually subtle to the human eye — most of the curve is still
           sampling the constant-velocity middle. 800ms gives the curve
           enough wall-clock time for its acceleration/deceleration
           character to actually land. The demo HTML's V3 fade hero uses
           800ms speed, V5 cards 500ms, V7 flip 700ms — 800ms is a
           reasonable median that flatters every effect. */
        $element->add_control(
            'eael_advanced_slider_speed',
            [
                'label' => esc_html__( 'Transition Speed', 'essential-addons-elementor' ),
                'description' => esc_html__( 'Milliseconds for the slide-to-slide animation. 600-1000ms makes the easing curve visibly distinct; under 400ms most curves blur together.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 10000,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 800,
                ],
                'conditions' => $this->items_conditions(),
            ]
        );

        /* Transition Easing — picks the cubic-bezier curve used for slide
           transitions across all effects. Mirrors the smooth animation feel of
           the demo HTML (cubic-bezier(.22, 1, .36, 1) — "spring"). Applied via
           the --eael-as-easing CSS variable on .eael-advanced-slider, then
           consumed by .swiper-wrapper { transition-timing-function }. The
           variable is picked up regardless of effect (slide, fade, coverflow,
           cards, flip), giving users a coherent feel across the deck. */
        $element->add_control(
            'eael_advanced_slider_transition_easing',
            [
                'label' => esc_html__( 'Transition Easing', 'essential-addons-elementor' ),
                'type' => Controls_Manager::CHOOSE,
                /* eicon names corrected. Probed live in the editor —
                   eicon-ease, eicon-linear, eicon-ease-out, eicon-ease-in-out
                   do NOT exist in the eicons font (rendered glyphs were 0px
                   wide). Replaced with verified-rendering icons whose
                   metaphors map well to easing curves. */
                'options' => [
                    'smooth' => [
                        'title' => esc_html__( 'Smooth', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-skill-bar',
                    ],
                    'snappy' => [
                        'title' => esc_html__( 'Snappy', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-flash',
                    ],
                    'spring' => [
                        'title' => esc_html__( 'Spring', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-spinner',
                    ],
                    'soft' => [
                        'title' => esc_html__( 'Soft', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-time-line',
                    ],
                ],
                'default' => 'spring',
                'toggle' => false,
                'selectors' => [
                    /* The fallback `ease` is what browsers use when no transition-timing
                       is specified. Each preset overrides --eael-as-easing on the slider
                       wrapper; the .swiper-wrapper rule (in advanced-slider.scss) reads it. */
                    '{{WRAPPER}} .eael-advanced-slider[data-easing="smooth"]'  => '--eael-as-easing: cubic-bezier(0.4, 0, 0.2, 1);',
                    '{{WRAPPER}} .eael-advanced-slider[data-easing="snappy"]'  => '--eael-as-easing: cubic-bezier(0.7, 0, 0.3, 1);',
                    '{{WRAPPER}} .eael-advanced-slider[data-easing="spring"]'  => '--eael-as-easing: cubic-bezier(0.22, 1, 0.36, 1);',
                    '{{WRAPPER}} .eael-advanced-slider[data-easing="soft"]'    => '--eael-as-easing: cubic-bezier(0.25, 0.46, 0.45, 0.94);',
                ],
                'conditions' => $this->items_conditions(),
            ]
        );

        /* Per-option easing descriptions. A CHOOSE control renders one static
           description for all values, so instead show the explanation for the
           currently-selected curve only. Each note is AND-gated against
           items_conditions() (so it hides whenever the easing control itself is
           hidden) plus the matching easing value. */
        $easing_descriptions = [
            'smooth' => esc_html__( 'Smooth — Material standard, balanced ease-in-out. Set Transition Speed to 600-1000ms to feel it clearly.', 'essential-addons-elementor' ),
            'snappy' => esc_html__( 'Snappy — Fast in, fast out. Set Transition Speed to 600-1000ms to feel it clearly.', 'essential-addons-elementor' ),
            'spring' => esc_html__( 'Spring — Overshoot at the end. Set Transition Speed to 600-1000ms to feel it clearly.', 'essential-addons-elementor' ),
            'soft'   => esc_html__( 'Soft — Gentle ease-out. Set Transition Speed to 600-1000ms to feel it clearly.', 'essential-addons-elementor' ),
        ];

        foreach ( $easing_descriptions as $easing => $description ) {
            $element->add_control(
                'eael_advanced_slider_transition_easing_desc_' . $easing,
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw'  => $description,
                    'content_classes' => 'elementor-descriptor',
                    'conditions' => [
                        'relation' => 'and',
                        'terms' => [
                            $this->items_conditions(),
                            [
                                'name' => 'eael_advanced_slider_transition_easing',
                                'operator' => '===',
                                'value' => $easing,
                            ],
                        ],
                    ],
                ]
            );
        }

        $element->add_control(
            'eael_advanced_slider_speed_marquee',
            [
                'label' => esc_html__( 'Speed', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 10000,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_direction' => 'horizontal',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_effect_marquee' => 'yes',
                ],
            ]
        );
    }

    private function register_items_gap_controls( $element ) {
        $element->add_responsive_control(
            'eael_advanced_slider_per_view',
            [
                'label'      => esc_html__( 'Items Per Slide', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 2,
                ],
                'description' => __( 'The responsive controller will affect the frontend only.', 'essential-addons-elementor' ),
                'conditions' => $this->items_conditions([], ['cards', 'fade', 'flip']),
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_item_gap',
            [
                'label'      => esc_html__( 'Items Gap', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'description' => __( 'The responsive controller will affect the frontend only.', 'essential-addons-elementor' ),
                /* render_type:none — see Items Per Slide above. Swiper
                   spaceBetween is JS-driven; skipping Elementor's re-render
                   keeps the slider DOM intact so the editor handler can do an
                   in-place update instead of a full rebuild on every drag. */
                'render_type' => 'none',
                'conditions' => $this->items_conditions([], ['cards', 'fade', 'flip']),
            ]
        );

        $element->add_control(
            'eael_advanced_slider_item_gap_marquee',
            [
                'label'      => esc_html__( 'Items Gap', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_direction' => 'horizontal',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_effect_marquee' => 'yes',
                ],
            ]
        );

        // Pause on Hover for Marquee
        $element->add_control(
            'eael_advanced_slider_pause_on_hover_marquee',
            [
                'label'        => esc_html__( 'Pause on Hover', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect_marquee' => 'yes',
                    'eael_advanced_slider_direction' => 'horizontal',
                ],
            ]
        );
    }

    private function register_background_controls( $element ) {
        $element->add_control(
            'eael_advanced_slider_background_heading',
            [
                'label' => esc_html__( 'Background Overlay', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'coverflow', 'cards', 'flip' ],
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_enable_background',
            [
                'label' => esc_html__( 'Enable', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'coverflow', 'cards', 'flip' ],
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_background_color',
            [
                'label' => esc_html__( 'Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}.eael-advanced-slider .swiper .swiper-slide-shadow-top' => 'background:{{VALUE}};',
                    '{{WRAPPER}}.eael-advanced-slider .swiper .swiper-slide-shadow-bottom' => 'background:{{VALUE}};',
                    '{{WRAPPER}}.eael-advanced-slider .swiper .swiper-slide-shadow' => 'background:{{VALUE}};',
                    '{{WRAPPER}} .swiper .swiper-slide-shadow' => 'background:{{VALUE}};',
                    '{{WRAPPER}} .swiper-wrapper .swiper-slide-shadow-left' => 'background:{{VALUE}}; z-index: -1;',
                    '{{WRAPPER}} .swiper-wrapper .swiper-slide-shadow-right' => 'background:{{VALUE}}; z-index: -1;',
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'coverflow', 'cards', 'flip' ],
                    'eael_advanced_slider_enable_background' => 'yes',
                ]
            ]
        );

        /* Active-slide overlay. Swiper's slide shadows (the "Color" control
           above) only paint the NON-active side/back slides — the front
           (active) slide gets progress 0 and therefore no shadow, so users
           had no way to tint it. This separate control paints a pseudo
           overlay (.swiper-slide-active::after, defined in advanced-slider.scss)
           that sits ABOVE the card's own #fff background, so it is actually
           visible on the active slide. Independent of the Enable switch above
           so it works even with the side-slide shadows turned off. */
        $element->add_control(
            'eael_advanced_slider_active_overlay_color',
            [
                'label' => esc_html__( 'Active Slide Overlay', 'essential-addons-elementor' ),
                'description' => esc_html__( 'Tint painted on top of the active (front) slide. Use a semi-transparent color so the content stays readable.', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    /* On the frontend the slider wrapper IS {{WRAPPER}} and also
                       carries the .swiper class (no nested .swiper child), so the
                       active slide is a direct descendant — no intermediate
                       .swiper in the selector. The editor shape nests
                       .eael-advanced-slider as a child of {{WRAPPER}}. Both forms
                       listed so the tint fires regardless of which DOM is live. */
                    // Frontend shape — .eael-advanced-slider IS {{WRAPPER}}.
                    '{{WRAPPER}}.eael-advanced-slider .swiper-slide-active:not(.eael-as-thumb)::after' => 'background:{{VALUE}};',
                    // Editor shape — .eael-advanced-slider is a child of {{WRAPPER}}.
                    '{{WRAPPER}} .eael-advanced-slider .swiper-slide-active:not(.eael-as-thumb)::after' => 'background:{{VALUE}};',
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'coverflow', 'cards', 'flip' ],
                ]
            ]
        );

        /* Card box-shadow. The 3D card chrome shipped a hardcoded shadow
           (0 30px 60px -20px rgba(15,15,50,0.18)) in advanced-slider.scss with
           no way to tune it. This group control now OWNS the slide shadow — the
           SCSS rule was removed so the control is the single source of truth,
           and the fields_options default reproduces the original look so
           existing sliders render identically out of the box (and users can
           soften/recolor/remove it). :not(.eael-as-thumb) keeps the thumbnail
           rail tiles out. Both DOM shapes covered (frontend: wrapper IS
           {{WRAPPER}}; editor: .eael-advanced-slider is a child). */
        $element->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'eael_advanced_slider_card_box_shadow',
                'selector' => '{{WRAPPER}}.eael-advanced-slider .swiper-slide:not(.eael-as-thumb), {{WRAPPER}} .eael-advanced-slider .swiper-slide:not(.eael-as-thumb)',
                'fields_options' => [
                    'box_shadow_type' => [ 'default' => 'yes' ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical'   => 30,
                            'blur'       => 60,
                            'spread'     => -20,
                            'color'      => 'rgba(15, 15, 50, 0.18)',
                        ],
                    ],
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => [ 'coverflow', 'cards', 'flip' ],
                ],
            ]
        );
    }

    private function register_autoplay_controls( $element ) {
        $element->start_controls_tabs( 'eael_advanced_slider_autoplay_tabs', [
            'conditions' => $this->items_conditions(),
        ] );
        
        $element->start_controls_tab(
            'eael_advanced_slider_autoplay_tab',
            [
                'label' => '',
                'conditions' => $this->items_conditions(),
            ]
        );

        $element->add_control(
            'eael_advanced_slider_autoplay_tab_heading',
            [
                'label' => esc_html__( 'Autoplay', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                ],
            ]
        );
 
        $element->add_control(
            'eael_autoplay_panel_notice',
            [
                'type' => Controls_Manager::NOTICE,
                'notice_type' => 'info',
                'dismissible' => false,
                'heading' => '',
                'content' => esc_html__( 'Autoplay is recommended for Fade, Flip & Coverflow effects.', 'essential-addons-elementor' ),
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => ['fade', 'coverflow', 'flip'],
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_autoplay',
            [
                'label'        => esc_html__( 'Enable Autoplay', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_autoplay_speed',
            [
                'label'      => esc_html__( 'Autoplay Delay', 'essential-addons-elementor' ),
                'description' => esc_html__( 'How long each slide stays before the next.', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 10,
                        'max' => 10000,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 2000,
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_autoplay' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_pause_on_hover',
            [
                'label'        => esc_html__( 'Pause on Hover', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_autoplay' => 'yes',
                    'eael_advanced_slider_effect_marquee!' => 'yes',
                    'eael_advanced_slider_direction' => 'horizontal',
                    'eael_advanced_slider_effect!' => 'flip'
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_loop',
            [
                'label'        => esc_html__( 'Loop Slides', 'essential-addons-elementor' ),
                'description'  => esc_html__( 'Cycle from the last slide back to the first.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'eael_enable_advanced_slider'            => 'yes',
                    'eael_advanced_slider_effect!'           => [ 'fade', 'flip' ],
                    'eael_advanced_slider_manual_scrolling!' => 'yes',
                    'eael_advanced_slider_effect_marquee!'   => 'yes',
                ],
            ]
        );

        /**
         * Centered Active Slide — when items-per-slide > 1 on horizontal slide effect,
         * highlight the centered slide. Mirrors the "Carousel Slider" pattern from
         * theme.co/modern-sliders. One Swiper option flag (centeredSlides) does the work.
         */
        $element->add_control(
            'eael_advanced_slider_centered_slides',
            [
                'label'        => esc_html__( 'Center active slide', 'essential-addons-elementor' ),
                'description'  => esc_html__( 'Keep the active slide centered. Best with 2+ items per slide on horizontal Slide effect.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'eael_enable_advanced_slider'          => 'yes',
                    'eael_advanced_slider_effect'          => 'slide',
                    'eael_advanced_slider_direction'       => 'horizontal',
                    'eael_advanced_slider_effect_marquee!' => 'yes',
                ],
            ]
        );

        $element->end_controls_tab();
        
        $element->end_controls_tabs();
    }

    private function register_indicator_controls( $element ) {
        /**
         * Advanced Slider indicator 
         * this will show when for mannual scrolling
         */

        $element->add_control(
            'eael_advanced_slider_indicator_heading',
            [
                'label' => esc_html__( 'Indicator', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_indicator',
            [
                'label'        => esc_html__( 'Show', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_indicator_type',
            [
                'label'   => esc_html__( 'Type', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'number',
                'options' => [
                    'number'   => esc_html__( 'Number', 'essential-addons-elementor' ), // 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20
                    'alphabet' => esc_html__( 'Alphabet', 'essential-addons-elementor' ), // A, B, C, D, E, F, G, H, I, J, K, L, M, N, O, P, Q, R, S, T, U, V, W, X, Y, Z,
                    'roman'    => esc_html__( 'Roman', 'essential-addons-elementor' ), // I, II, III, IV, V, VI, VII, VIII, IX, X, XI, XII, XIII, XIV, XV, XVI, XVII, XVIII, XIX, XX
                ],
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                    'eael_advanced_slider_indicator' => 'yes',
                ]
            ]
        );

        $element->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'eael_advanced_slider_indicator_typography',
                'selector' => '{{WRAPPER}} .eael-as-indicator-index',
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                    'eael_advanced_slider_indicator' => 'yes',
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_indicator_color',
            [
                'label' => esc_html__( 'Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#ccc',
                'selectors' => [
                    '{{WRAPPER}} .eael-as-indicator-index' => 'color: {{VALUE}}',
                ],
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                    'eael_advanced_slider_indicator' => 'yes',
                ]
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_indicator_bar_width',
            [
                'label' => esc_html__( 'Bar Width', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'rem' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}.eael-advanced-slider .eael-as-indicator-index::before, {{WRAPPER}}.eael-advanced-slider .eael-as-indicator-index::after' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition'    => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                    'eael_advanced_slider_indicator' => 'yes',
                ]
            ]
        );
        
        $element->add_control(
            'eael_advanced_slider_indicator_bar_color',
            [
                'label' => esc_html__( 'Bar Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#878484ff',
                'selectors' => [
                    '{{WRAPPER}}.eael-advanced-slider .eael-as-indicator-index::before, {{WRAPPER}}.eael-advanced-slider .eael-as-indicator-index::after' => 'background-color: {{VALUE}} !important;',
                ],
                'condition' => [
                    'eael_enable_advanced_slider' => 'yes',
                    'eael_advanced_slider_effect' => 'slide',
                    'eael_advanced_slider_direction' => 'vertical',
                    'eael_advanced_slider_manual_scrolling' => 'yes',
                    'eael_advanced_slider_indicator' => 'yes',
                ],
            ]
        );

        // end the indicator controls
    }

    private function register_navigation_controls( $element ) {
        $element->add_control(
            'eael_advanced_slider_heading_navigation',
            [
                'label' => esc_html__( 'Navigation', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'conditions' => $this->navigation_conditions(),
            ]
        );

        $element->add_control(
            'eael_advanced_slider_enable_navigation',
            [
                'label' => esc_html__( 'Enable', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'conditions' => $this->navigation_conditions(),
            ]
        );

        $element->start_controls_tabs( 'eael_advanced_slider_navigation_tabs', [
            'conditions' => $this->navigation_conditions( [
                [
                    'name' => 'eael_advanced_slider_enable_navigation',
                    'operator' => '===',
                    'value' => 'yes',
                ]
            ] )
            ] 
        );
        
        $element->start_controls_tab( 
            'eael_advanced_slider_navigation_content_tab', 
            [ 
                'label' => esc_html__( 'Content', 'essential-addons-elementor' ),
            ] 
        );

        $element->add_control(
            'eael_advanced_slider_navigation_arrows',
            [
                'label'        => esc_html__( 'Show Arrows', 'essential-addons-elementor' ),
                'description'  => esc_html__( 'Disabled by default — pagination alone is enough for most layouts.', 'essential-addons-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
                'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
                'return_value' => 'yes',
                'default'      => '', // Off by default
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_navigation_icon_position_left',
            [
                'label' => esc_html__( 'Left Icon Position', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} > .e-con-inner .swiper-button-prev.eael-as-nav-icon' => 'left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.eael-advanced-slider > .swiper-button-prev.eael-as-nav-icon' => 'left: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .swiper-button-prev.eael-as-nav-icon' => 'left: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_navigation_icon_left',
            [
                'label' => esc_html__( 'Icon Left', 'essential-addons-elementor' ),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-angle-left',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_navigation_icon_position_right',
            [
                'label' => esc_html__( 'Right Icon Position', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} > .e-con-inner .swiper-button-next.eael-as-nav-icon' => 'right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.eael-advanced-slider > .swiper-button-next.eael-as-nav-icon' => 'right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .swiper-button-next.eael-as-nav-icon' => 'right: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_navigation_icon_right',
            [
                'label' => esc_html__( 'Icon Right', 'essential-addons-elementor' ),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-angle-right',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        /**
         * Pagination Style — visual skin picker. CHOOSE renders icon-buttons
         * in a row (each with a hover tooltip showing the full style name).
         * Icons are eicon-* (Elementor's built-in icon font, ensures they
         * render in the editor panel without a separate CSS enqueue).
         */
        $element->add_control(
            'eael_advanced_slider_pagination_type',
            [
                'label'       => esc_html__( 'Navigation Style', 'essential-addons-elementor' ),
                'description' => esc_html__( 'Hover an icon for its name. Tick Bars (default) are clickable and need no autoplay.', 'essential-addons-elementor' ),
                'type'        => Controls_Manager::CHOOSE,
                'default'     => 'tick_bars',
                'toggle'      => false,
                'label_block' => true,
                'options'     => [
                    'none' => [
                        'title' => esc_html__( 'None — no pagination', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-ban',
                    ],
                    'tick_bars' => [
                        'title' => esc_html__( 'Tick Bars (default) — vertical bar markers', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-meta-data',
                    ],
                    'dots' => [
                        'title' => esc_html__( 'Dots — classic horizontal bullets', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-section',
                    ],
                    'dots_vertical' => [
                        'title' => esc_html__( 'Dots Vertical — small dot rail on the right edge', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-handle',
                    ],
                    'fraction' => [
                        'title' => esc_html__( 'Fraction — "01 / 05" counter', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-typography',
                    ],
                    'progress_bar' => [
                        'title' => esc_html__( 'Progress Bar — autoplay timer with play / pause + counter', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-loading',
                    ],
                    'numbered_index' => [
                        'title' => esc_html__( 'Numbered Index Rail — clickable 01/02 or A/B or I/II buttons', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-number-field',
                    ],
                    'thumbnails' => [
                        'title' => esc_html__( 'Thumbnails strip — auto-detected slide images', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-thumbnails-down',
                    ],
                ],
            ]
        );

        /**
         * Index format — only used when pagination_type === 'numbered_index'.
         * Drives the label rendered inside each rail item.
         */
        $element->add_control(
            'eael_advanced_slider_index_format',
            [
                'label'   => esc_html__( 'Index Format', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'number',
                'options' => [
                    'number'   => esc_html__( '01, 02, 03 …', 'essential-addons-elementor' ),
                    'alphabet' => esc_html__( 'A, B, C …', 'essential-addons-elementor' ),
                    'roman'    => esc_html__( 'I, II, III …', 'essential-addons-elementor' ),
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => 'numbered_index',
                ],
            ]
        );

        /**
         * Pagination Position. Some positions are conditionally relevant.
         */
        /**
         * Pagination Position — visual skin selector with directional eicons,
         * mirroring the EA Post Grid skin selection UX.
         */
        $element->add_control(
            'eael_advanced_slider_pagination_position',
            [
                'label'   => esc_html__( 'Navigation Position', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'bottom',
                'toggle'  => false,
                'options' => [
                    'bottom' => [
                        'title' => esc_html__( 'Below the slider', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                    'inside_top_center' => [
                        'title' => esc_html__( 'Inside · Top center (overlay)', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'inside_bottom_center' => [
                        'title' => esc_html__( 'Inside · Bottom center (overlay)', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-v-align-middle',
                    ],
                    'inline_with_arrows' => [
                        'title' => esc_html__( 'Inline with arrows', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-h-align-stretch',
                    ],
                    'right_center' => [
                        'title' => esc_html__( 'Right edge (vertical column)', 'essential-addons-elementor' ),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type!' => [ 'none', 'dots_vertical' ],
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_pagination_position_inline_notice',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__( '“Inline with arrows” groups the dots between the prev/next arrows. Enable “Show Arrows” above — without arrows it renders the same as “Below the slider”.', 'essential-addons-elementor' ),
                'content_classes' => 'elementor-control-field-description',
                'condition'       => [
                    'eael_advanced_slider_pagination_position'  => 'inline_with_arrows',
                    'eael_advanced_slider_navigation_arrows!'   => 'yes',
                    'eael_advanced_slider_pagination_type!'     => [ 'none', 'dots_vertical' ],
                ],
            ]
        );

        $element->end_controls_tab();

        $element->start_controls_tab( 
            'eael_advanced_slider_navigation_style_tab', 
            [ 
                'label' => esc_html__( 'Style', 'essential-addons-elementor' ),
            ] 
        );

        $element->add_control(
            'eael_advanced_slider_swiper_arrow_style_heading',
            [
                'label' => esc_html__( 'Arrows', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_swiper_arrow_size',
            [
                'label' => esc_html__( 'Size', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} > .e-con-inner > .swiper-button-next:after, {{WRAPPER}} > .e-con-inner > .swiper-button-prev:after' => 'font-size: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}}.eael-advanced-slider > .swiper-button-next:after, {{WRAPPER}}.eael-advanced-slider > .swiper-button-prev:after' => 'font-size: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon' => 'font-size: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon' => 'font-size: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};line-height: {{SIZE}}{{UNIT}}',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .eael-as-nav-icon' => 'font-size: {{SIZE}}{{UNIT}};',

                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_swiper_arrow_color',
            [
                'label' => esc_html__( 'Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} > .e-con-inner > .swiper-button-next:after, {{WRAPPER}} > .e-con-inner > .swiper-button-prev:after' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .swiper-button-next:after, {{WRAPPER}}.eael-advanced-slider > .swiper-button-prev:after' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon' => 'color: {{VALUE}}',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .eael-as-nav-icon' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_swiper_arrow_color_hover',
            [
                'label' => esc_html__( 'Hover Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} > .e-con-inner > .swiper-button-next:after:hover, {{WRAPPER}} > .e-con-inner > .swiper-button-prev:after:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .swiper-button-next:after:hover, {{WRAPPER}}.eael-advanced-slider > .swiper-button-prev:after:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}}.eael-advanced-slider > .eael-as-nav-icon:hover svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}} > .e-con-inner > .eael-as-nav-icon:hover svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .eael-as-nav-icon:hover' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}}.elementor-element-edit-mode .eael-advanced-slider .eael-as-nav-icon:hover svg' => 'fill: {{VALUE}} !important;',
                ],
                'condition' => [
                    'eael_advanced_slider_navigation_arrows' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_swiper_dot_style_heading',
            [
                'label' => esc_html__( 'Dots', 'essential-addons-elementor' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );
        
        $element->add_responsive_control(
            'eael_advanced_slider_swiper_dot_size',
            [
                'label' => esc_html__( 'Size', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination .eael-as-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_swiper_dot_color',
            [
                'label' => esc_html__( 'Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#ccc',
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination .eael-as-bullet' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );

        $element->add_control(
            'eael_advanced_slider_swiper_dot_active_color',
            [
                'label' => esc_html__( 'Active Color', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#000',
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination .eael-as-bullet-active' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_dots_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'default' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination .eael-as-bullet' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );

        $element->add_responsive_control(
            'eael_advanced_slider_dots_gap',
            [
                'label' => esc_html__( 'Gap', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'default' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination .eael-as-bullet' => 'margin: 0 {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'eael_advanced_slider_pagination_type' => [ 'dots', 'dots_vertical' ]
                ]
            ]
        );

        /* ────────── Tick Bars styling ────────── */
        $element->add_control(
            'eael_advanced_slider_tickbars_heading',
            [
                'label'     => esc_html__( 'Tick Bars', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'tick_bars' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_tickbars_height',
            [
                'label'      => esc_html__( 'Bar Height', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 4, 'max' => 60, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 14 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-tick-bars .eael-as-tick-bar' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'tick_bars' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_tickbars_active_height',
            [
                'label'      => esc_html__( 'Active Bar Height', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 4, 'max' => 80, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 18 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-tick-bars .eael-as-tick-bar.is-active' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'tick_bars' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_tickbars_gap',
            [
                'label'      => esc_html__( 'Gap', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 4 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-tick-bars' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'tick_bars' ],
            ]
        );

        /* ────────── Fraction styling ────────── */
        $element->add_control(
            'eael_advanced_slider_fraction_heading',
            [
                'label'     => esc_html__( 'Fraction', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'fraction' ],
            ]
        );
        $element->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'eael_advanced_slider_fraction_typography',
                'selector'  => '{{WRAPPER}} .eael-as-pagination--type-fraction',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'fraction' ],
            ]
        );

        /* ────────── Progress Bar styling ────────── */
        $element->add_control(
            'eael_advanced_slider_progressbar_heading',
            [
                'label'     => esc_html__( 'Progress Bar', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'progress_bar' ],
            ]
        );
        /* Track-height selector fix. Was targeting the OUTER pagination
           container `{{WRAPPER}} .eael-as-pagination--type-progress-bar`,
           which has display:flex with toggle (38px) | track (2px) | counter
           (~20px) children. Setting height: 2px on the container collapsed
           the whole row to 2px tall, hiding the play/pause button and the
           counter (the track itself stayed visible because its `position:
           relative` and `flex: 1` made it the dominant child). The user
           reported the progress bar "doesn't show on frontend" — actually
           it WAS rendering but only the 2px track was visible, the toggle
           and counter were geometrically inside a 2-px-tall flex line and
           not visually findable. Fix: scope the height control to the
           track child where it actually belongs. */
        $element->add_responsive_control(
            'eael_advanced_slider_progressbar_height',
            [
                'label'      => esc_html__( 'Track Height', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 1, 'max' => 20, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 2 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar .eael-as-pb-track' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'progress_bar' ],
            ]
        );
        /* Progress bar Max Width — default raised from 240px to 80% so the
           bar reads as the wide editorial control from the demo HTML's V3 layout
           ("Travel magazine · Fade hero with timing"). The previous 240px default
           collapsed it into a chip-sized strip in the middle of the slider, which
           didn't match the demo and made the play/pause button feel detached.
           Range now allows up to 100% with px ceiling raised to 1200 so users can
           pin it to a fixed width if they want. */
        $element->add_responsive_control(
            'eael_advanced_slider_progressbar_max_width',
            [
                'label'      => esc_html__( 'Width', 'essential-addons-elementor' ),
                'description'=> esc_html__( 'Width of the progress-bar control. Use % for fluid (default 80%) or px for a fixed strip.', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ '%', 'px' ],
                'range'      => [ 'px' => [ 'min' => 120, 'max' => 1200, 'step' => 5 ], '%' => [ 'min' => 20, 'max' => 100 ] ],
                'default'    => [ 'unit' => '%', 'size' => 80 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar' => 'max-width: {{SIZE}}{{UNIT}} !important; width: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'progress_bar' ],
            ]
        );

        /* Progress Bar Fill Color — controls the .eael-as-pb-fill background
           independently from the active-pagination color. The track itself stays
           on --eael-as-pag-color (existing inactive token). The fill defaults to
           the active-pagination color so users who haven't picked a fill color
           still see something coherent. Selector also covers the play/pause
           toggle background so the round button colour stays in sync with the
           fill on dark/light hero slides. */
        $element->add_control(
            'eael_advanced_slider_progressbar_fill_color',
            [
                'label'     => esc_html__( 'Fill Color', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar .eael-as-pb-fill' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar .eael-as-pb-toggle' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar .eael-as-pb-counter strong' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'progress_bar' ],
            ]
        );

        $element->add_control(
            'eael_advanced_slider_progressbar_track_color',
            [
                'label'     => esc_html__( 'Track Color', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination--type-progress-bar .eael-as-pb-track' => 'background: {{VALUE}};',
                ],
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'progress_bar' ],
            ]
        );

        /* ────────── Numbered Index Rail styling ────────── */
        $element->add_control(
            'eael_advanced_slider_numbered_index_heading',
            [
                'label'     => esc_html__( 'Numbered Index Rail', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'numbered_index' ],
            ]
        );
        $element->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'eael_advanced_slider_numbered_index_typography',
                'selector'  => '{{WRAPPER}} .eael-as-pagination--type-numbered-index .eael-as-index-label',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'numbered_index' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_numbered_index_gap',
            [
                'label'      => esc_html__( 'Gap', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 60, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 8 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-pagination--type-numbered-index' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'numbered_index' ],
            ]
        );

        /* ────────── Thumbnails strip styling ────────── */
        $element->add_control(
            'eael_advanced_slider_thumbnails_heading',
            [
                'label'     => esc_html__( 'Thumbnails', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => 'thumbnails' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_thumbnails_size',
            [
                'label'      => esc_html__( 'Thumbnail Size', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 40, 'max' => 200, 'step' => 1 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 20 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-thumbnails .swiper-slide' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'thumbnails' ],
            ]
        );
        $element->add_responsive_control(
            'eael_advanced_slider_thumbnails_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ], '%' => [ 'min' => 0, 'max' => 50 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 8 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-as-thumbnails .swiper-slide' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [ 'eael_advanced_slider_pagination_type' => 'thumbnails' ],
            ]
        );

        /* ────────── Unified Pagination color tokens ──────────
           These two controls feed CSS custom properties on the pagination
           wrapper so they apply consistently to every pagination type
           (tick bars, fraction text, progress bar, dots, numbered index,
           thumbnail number fallback). */
        $element->add_control(
            'eael_advanced_slider_pagination_tokens_heading',
            [
                'label'     => esc_html__( 'Color Tokens', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_advanced_slider_pagination_type' => [ 'tick_bars', 'fraction', 'progress_bar', 'dots_vertical', 'numbered_index', 'thumbnails' ] ],
            ]
        );
        $element->add_control(
            'eael_advanced_slider_pagination_color',
            [
                'label'     => esc_html__( 'Inactive Color', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination' => '--eael-as-pag-color: {{VALUE}};',
                    '{{WRAPPER}} .eael-as-thumbnails .eael-as-thumb-num' => '--eael-as-pag-color: {{VALUE}};',
                ],
                'condition' => [ 'eael_advanced_slider_pagination_type' => [ 'tick_bars', 'fraction', 'progress_bar', 'dots_vertical', 'numbered_index', 'thumbnails' ] ],
            ]
        );
        $element->add_control(
            'eael_advanced_slider_pagination_active_color',
            [
                'label'     => esc_html__( 'Active Color', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-as-pagination' => '--eael-as-pag-active-color: {{VALUE}}; --eael-as-pag-text-color: {{VALUE}};',
                    '{{WRAPPER}} .eael-as-thumbnails .eael-as-thumb-num' => '--eael-as-pag-active-color: {{VALUE}};',
                ],
                'condition' => [ 'eael_advanced_slider_pagination_type' => [ 'tick_bars', 'fraction', 'progress_bar', 'dots_vertical', 'numbered_index', 'thumbnails' ] ],
            ]
        );

        $element->end_controls_tab();

        $element->end_controls_tabs();
    }

    public function before_render( $element ) {
        
        if( 'yes' !== $element->get_settings( 'eael_enable_advanced_slider' ) ) {
            return;
        }
            
        $settings = $element->get_settings_for_display();

        /* Defensive normalize — vertical direction is only supported for the
           Slide effect. Any saved slider that has vertical+coverflow/cards/
           flip/fade is coerced back to horizontal at render time so it
           doesn't render the broken combination. New sliders can't reach
           this state — the Slide Direction control is hidden for non-slide
           effects (see register_main_settings_controls). */
        if ( 'slide' !== $settings['eael_advanced_slider_effect'] ) {
            $settings['eael_advanced_slider_direction'] = 'horizontal';
        }

        $element->add_render_attribute( '_wrapper', 'class', 'eael-advanced-slider' );
        $element->add_render_attribute( '_wrapper', 'class', 'eael-advanced-slider-' . $settings['eael_advanced_slider_direction'] );
        $slider_options = [];
        if( isset( $settings['eael_advanced_slider_effect'] ) && ! empty( $settings['eael_advanced_slider_effect'] ) ) {
            $slider_options['effect'] = $settings['eael_advanced_slider_effect'];
            $element->add_render_attribute( '_wrapper', 'class', 'eael-advanced-slider-' . $settings['eael_advanced_slider_effect'] );
        }

        if( 'yes' !== $settings['eael_advanced_slider_manual_scrolling'] && 'yes' !== $settings['eael_advanced_slider_effect_marquee'] ) {
            wp_enqueue_style( 'e-swiper' );
            wp_enqueue_script( 'e-swiper' );
        }
        $slider_options['direction'] = $settings['eael_advanced_slider_direction'];
        $slider_options['manualScrolling'] = isset( $settings['eael_advanced_slider_manual_scrolling'] ) ? $settings['eael_advanced_slider_manual_scrolling'] : 'no';
        $slider_options['marquee'] = isset( $settings['eael_advanced_slider_effect_marquee'] ) ? $settings['eael_advanced_slider_effect_marquee'] : 'no';
        
        if( !empty( $settings['eael_advanced_slider_effect_marquee'] ) ) {
            $slider_options['marquee'] = $settings['eael_advanced_slider_effect_marquee'];
            $element->add_render_attribute( '_wrapper', 'class', 'eael-advanced-slider-marquee' );
        }

        if ( isset( $settings['eael_advanced_slider_autoplay'] ) && ! empty( $settings['eael_advanced_slider_autoplay'] ) ) {
            $slider_options['autoplay'] = $settings['eael_advanced_slider_autoplay'];
            $slider_options['autoplay_speed'] = $settings['eael_advanced_slider_autoplay_speed']['size'];
        }

        if( in_array( $settings['eael_advanced_slider_effect'], [ 'coverflow', 'cards', 'flip' ] ) ) {
            $slider_options['enable_background'] = isset( $settings['eael_advanced_slider_enable_background'] ) && 'yes' === $settings['eael_advanced_slider_enable_background'];
        }

        if( 'coverflow' === $settings['eael_advanced_slider_effect'] ) {
            $slider_options['coverflow_rotation'] = $settings['eael_advanced_slider_coverflow_rotation']['size'];
            $slider_options['coverflow_depth']    = isset( $settings['eael_advanced_slider_coverflow_depth']['size'] )
                ? $settings['eael_advanced_slider_coverflow_depth']['size']
                : 200;
            $slider_options['coverflow_stretch']  = isset( $settings['eael_advanced_slider_coverflow_stretch']['size'] )
                ? $settings['eael_advanced_slider_coverflow_stretch']['size']
                : 0;
        }

        if ( isset( $settings['eael_advanced_slider_pause_on_hover'] ) && ! empty( $settings['eael_advanced_slider_pause_on_hover'] ) ) {
            $slider_options['pauseOnHover'] = $settings['eael_advanced_slider_pause_on_hover'];
        } else if( isset( $settings['eael_advanced_slider_effect_marquee'] ) && ! empty( $settings['eael_advanced_slider_effect_marquee'] ) ) {
            $slider_options['pauseOnHover'] = $settings['eael_advanced_slider_pause_on_hover_marquee'];
        }

        if( isset( $settings['eael_advanced_slider_loop'] ) && ! empty( $settings['eael_advanced_slider_loop'] ) ) {
            $slider_options['loop'] = $settings['eael_advanced_slider_loop'];
        } else if( in_array( $settings['eael_advanced_slider_effect'], [ 'fade', 'flip' ] ) ) {
            $slider_options['loop'] = 'yes';
        }

        if ( isset( $settings['eael_advanced_slider_speed'] ) && ! empty( $settings['eael_advanced_slider_speed'] ) ) {
            $slider_options['speed'] = $settings['eael_advanced_slider_speed']['size'];
        } else if( isset( $settings['eael_advanced_slider_effect_marquee'] ) && ! empty( $settings['eael_advanced_slider_effect_marquee'] ) ) {
            $slider_options['speed'] = $settings['eael_advanced_slider_speed_marquee']['size'];
        }

        if ( isset( $settings['eael_advanced_slider_navigation_arrows'] ) && ! empty( $settings['eael_advanced_slider_navigation_arrows'] ) ) {
            $slider_options['navigation_arrows'] = $settings['eael_advanced_slider_navigation_arrows'];
            if( isset( $settings['eael_advanced_slider_navigation_icon_left'] ) && ! empty( $settings['eael_advanced_slider_navigation_icon_left'] ) ) {
                ob_start();
                Icons_Manager::render_icon( $settings['eael_advanced_slider_navigation_icon_left'], [ 'aria-hidden' => 'true' ] );
                $left_icon_html = ob_get_clean();
                $left_icon_html = wp_kses( $left_icon_html, Helper::eael_allowed_icon_tags() );
                $slider_options['navigation_icon_left'] = base64_encode( $left_icon_html );
            }
            if( isset( $settings['eael_advanced_slider_navigation_icon_right'] ) && ! empty( $settings['eael_advanced_slider_navigation_icon_right'] ) ) {
                ob_start();
                Icons_Manager::render_icon( $settings['eael_advanced_slider_navigation_icon_right'], [ 'aria-hidden' => 'true' ] );
                $right_icon_html = ob_get_clean();
                $right_icon_html = wp_kses( $right_icon_html, Helper::eael_allowed_icon_tags() );
                $slider_options['navigation_icon_right'] = base64_encode( $right_icon_html );
            }
        }

        // New pagination contract — type / position / centered slides.
        $pagination_type = isset( $settings['eael_advanced_slider_pagination_type'] ) && '' !== $settings['eael_advanced_slider_pagination_type']
            ? $settings['eael_advanced_slider_pagination_type']
            : 'tick_bars';

        $slider_options['pagination_type']     = $pagination_type;
        $slider_options['pagination_position'] = isset( $settings['eael_advanced_slider_pagination_position'] )
            ? $settings['eael_advanced_slider_pagination_position']
            : 'bottom';

        if ( 'numbered_index' === $pagination_type ) {
            $slider_options['index_format'] = isset( $settings['eael_advanced_slider_index_format'] )
                ? $settings['eael_advanced_slider_index_format']
                : 'number';
        }

        if ( isset( $settings['eael_advanced_slider_centered_slides'] ) && 'yes' === $settings['eael_advanced_slider_centered_slides'] ) {
            $slider_options['centered_slides'] = 'yes';
        }

        // Auto-fit slide height for cards/flip/coverflow (default 'yes').
        $slider_options['auto_height'] = isset( $settings['eael_advanced_slider_auto_height'] )
            ? $settings['eael_advanced_slider_auto_height']
            : 'yes';

        /* Auto-fit marker class — mirrors the editor (see edit JS). When
           auto-fit is on for a 3D effect, the SCSS drops the 360px min-height
           floor so the slider hugs its content instead of forcing a tall
           empty card box. */
        if ( in_array( $settings['eael_advanced_slider_effect'], [ 'cards', 'flip', 'coverflow' ], true )
            && 'no' !== $slider_options['auto_height'] && '' !== $slider_options['auto_height'] ) {
            $element->add_render_attribute( '_wrapper', 'class', 'eael-as-auto-height' );
        }

        if ( isset( $settings['eael_advanced_slider_indicator'] ) && ! empty( $settings['eael_advanced_slider_indicator'] ) ) {
            $slider_options['indicator'] = $settings['eael_advanced_slider_indicator'];

            if ( isset( $settings['eael_advanced_slider_indicator_type'] ) && ! empty( $settings['eael_advanced_slider_indicator_type'] ) ) {
                $slider_options['indicator_type'] = $settings['eael_advanced_slider_indicator_type'];
            }
        }

        if ( isset( $settings['eael_advanced_slider_per_view'] ) && ! empty( $settings['eael_advanced_slider_per_view'] ) ) {
            $slider_options['items'] = $settings['eael_advanced_slider_per_view']['size'];
        }

        if ( isset( $settings['eael_advanced_slider_item_gap'] ) && ! empty( $settings['eael_advanced_slider_item_gap'] ) ) {
            $slider_options['gap'] = $settings['eael_advanced_slider_item_gap']['size'];
        } else if( isset( $settings['eael_advanced_slider_item_gap_marquee'] ) && ! empty( $settings['eael_advanced_slider_item_gap_marquee'] ) ) {
            $slider_options['gap'] = $settings['eael_advanced_slider_item_gap_marquee']['size'];
        }
        
        if ( method_exists( Plugin::$instance->breakpoints, 'get_breakpoints_config' ) && ! empty( $breakpoints = Plugin::$instance->breakpoints->get_breakpoints_config() ) ) {
            foreach ( $breakpoints as $key => $breakpoint ){
                if ($breakpoint['is_enabled']) {
                    if (!empty($settings['eael_advanced_slider_per_view_'.$key]['size'])) {
                        $slider_options['breakpoints'][$key] = $settings['eael_advanced_slider_per_view_'.$key]['size'];
                    }
                    if (!empty($settings['eael_advanced_slider_item_gap_'.$key]['size'])) {
                        $slider_options['margins'][$key] = $settings['eael_advanced_slider_item_gap_'.$key]['size'];
                    }
                }
            }
        }
        $element->add_render_attribute( '_wrapper', 'data-options', esc_attr( wp_json_encode( $slider_options ) ) );

        /* data-easing → CSS variable --eael-as-easing.
           Front-end + editor preview use the same selector contract; the JS
           handler also reads $scope.data('easing') to populate the CSS var
           inline as a belt-and-suspenders fallback if Elementor's selector
           injection happens after init. */
        $easing = isset( $settings['eael_advanced_slider_transition_easing'] ) && ! empty( $settings['eael_advanced_slider_transition_easing'] )
            ? $settings['eael_advanced_slider_transition_easing']
            : 'spring';
        $element->add_render_attribute( '_wrapper', 'data-easing', esc_attr( $easing ) );
    }
}
