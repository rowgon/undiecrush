<?php

namespace Essential_Addons_Elementor\Pro\Elements;

use \Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use Elementor\Plugin;
use \Elementor\Widget_Base;
use Essential_Addons_Elementor\Classes\Helper;

if (!defined('ABSPATH')) {
    exit;
} // If this file is called directly, abort.

class Pinterest_Feed extends Widget_Base {
    use \Essential_Addons_Elementor\Pro\Traits\Pinterest_Feed;

    public function get_name() {
        return 'eael-pinterest-feed';
    }

    public function get_title() {
        return esc_html__('Pinterest Feed', 'essential-addons-elementor');
    }

    public function get_icon() {
        return 'eaicon-pinterest-feed';
    }

    public function get_categories() {
        return ['essential-addons-elementor'];
    }

    public function get_keywords() {
        return [
            'pinterest',
            'pinterest feed',
            'ea pinterest feed',
            'pinterest gallery',
            'ea pinterest gallery',
            'social media',
            'social feed',
            'ea social feed',
            'pinterest embed',
            'pinterest board',
            'pinterest pins',
            'ea',
            'essential addons'
        ];
    }

    public function has_widget_inner_wrapper(): bool {
        return ! Helper::eael_e_optimized_markup();
    }

    public function get_custom_help_url() {
        return 'https://essential-addons.com/docs/ea-pinterest-feed';
    }

    public function get_style_depends() {
        return [
            'swiper',
            'font-awesome-5-all',
            'font-awesome-4-shim',
        ];
    }

    public function get_script_depends() {
        return [
            'font-awesome-4-shim'
        ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'eael_section_pinterest_feed_settings_account',
            [
                'label' => esc_html__('Account Settings', 'essential-addons-elementor'),
            ]
        );

        // Site-wide OAuth connection lives in EA Dashboard; one connection serves all widgets.
        $eael_pf_token = get_option( 'eael_pinterest_access_token', '' );
        $eael_pf_user  = get_option( 'eael_pinterest_user_info', [] );
        if ( is_string( $eael_pf_user ) ) {
            $eael_pf_user = json_decode( $eael_pf_user, true );
        }
        if ( ! is_array( $eael_pf_user ) ) {
            $eael_pf_user = [];
        }
        $eael_pf_status       = get_option( 'eael_pinterest_connection_status', '' );
        $eael_pf_settings_url = esc_url( admin_url( 'admin.php?page=eael-settings' ) );

        if ( ! empty( $eael_pf_token ) && 'revoked' !== $eael_pf_status ) {
            $eael_pf_username = ! empty( $eael_pf_user['username'] ) ? $eael_pf_user['username'] : '';
            $eael_pf_raw = $eael_pf_username
                ? sprintf(
                    /* translators: %1$s: Pinterest username, %2$s: dashboard URL */
                    __( 'Connected as <strong>@%1$s</strong>. Manage from <a href="%2$s" target="_blank">EA Dashboard</a>.', 'essential-addons-elementor' ),
                    esc_html( $eael_pf_username ),
                    $eael_pf_settings_url
                )
                : sprintf(
                    __( 'Pinterest connected. Manage from <a href="%s" target="_blank">EA Dashboard</a>.', 'essential-addons-elementor' ),
                    $eael_pf_settings_url
                );
            $eael_pf_class = 'elementor-descriptor';
        } else {
            $eael_pf_raw = sprintf(
                'revoked' === $eael_pf_status
                    ? __( 'Pinterest access was revoked. Reconnect from <a href="%s" target="_blank">EA Dashboard</a>.', 'essential-addons-elementor' )
                    : __( 'Please connect your Pinterest account from <a href="%s" target="_blank">EA Dashboard » Elements » Pinterest Feed</a>.', 'essential-addons-elementor' ),
                $eael_pf_settings_url
            );
            $eael_pf_class = 'eael-warning';
        }

        $this->add_control(
            'eael_pinterest_feed_connection_status',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => $eael_pf_raw,
                'content_classes' => $eael_pf_class,
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_data_cache_limit',
            [
                'label' => __('Data Cache Time', 'essential-addons-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '60',
                'options' => [
                    '1' => __('1 Minute', 'essential-addons-elementor'),
                    '5' => __('5 Minutes', 'essential-addons-elementor'),
                    '15' => __('15 Minutes', 'essential-addons-elementor'),
                    '30' => __('30 Minutes', 'essential-addons-elementor'),
                    '60' => __('1 Hour', 'essential-addons-elementor'),
                    '180' => __('3 Hours', 'essential-addons-elementor'),
                    '360' => __('6 Hours', 'essential-addons-elementor'),
                    '720' => __('12 Hours', 'essential-addons-elementor'),
                    '1440' => __('1 Day', 'essential-addons-elementor'),
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_type',
            [
                'label' => esc_html__('Feed Type', 'essential-addons-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'user_pins',
                'options' => [
                    'user_pins'  => esc_html__('User Pins', 'essential-addons-elementor'),
                    'board_pins' => esc_html__('Board Pins', 'essential-addons-elementor'),
                    'boards'     => esc_html__('Boards', 'essential-addons-elementor'),
                ],
            ]
        );

        // Site-wide OAuth token drives the boards dropdown prefetch.
        $board_options       = [];
        $pf_token_for_boards = $this->get_current_pinterest_access_token();
        if ( ! empty( $pf_token_for_boards ) ) {
            $board_options = $this->get_boards_for_dropdown( $pf_token_for_boards, 60 ) ?: [];
        }

        $this->add_control(
            'eael_pinterest_feed_board_id',
            [
                'label'       => esc_html__('Select Board', 'essential-addons-elementor'),
                'type'        => ! empty( $board_options ) ? Controls_Manager::SELECT2 : Controls_Manager::TEXT,
                'options'     => $board_options,
                'label_block' => true,
                'condition'   => [
                    'eael_pinterest_feed_type' => 'board_pins',
                ],
                'ai'          => [ 'active' => false ],
                'description' => empty( $board_options )
                    ? esc_html__('Enter Board ID or slug. After first load, a dropdown will appear here.', 'essential-addons-elementor')
                    : '',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_cover_style',
            [
                'label'       => esc_html__('Board Cover Style', 'essential-addons-elementor'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'mosaic-3',
                'options'     => [
                    'single-image' => esc_html__('Single Cover', 'essential-addons-elementor'),
                    'split-2'      => esc_html__('Split (2 images)', 'essential-addons-elementor'),
                    'mosaic-3'     => esc_html__('Mosaic (1 large + 2 small)', 'essential-addons-elementor'),
                    'grid-4'       => esc_html__('Grid (4 images)', 'essential-addons-elementor'),
                    'multi-image'  => esc_html__('Multi-Image Wall (6 images)', 'essential-addons-elementor'),
                ],
                'description' => esc_html__('How many cover images each board card displays. Boards with fewer pins fall back gracefully.', 'essential-addons-elementor'),
                'condition'   => [ 'eael_pinterest_feed_type' => 'boards' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_board_name',
            [
                'label'        => esc_html__('Show Board Name', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [ 'eael_pinterest_feed_type' => 'boards' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_pin_count',
            [
                'label'        => esc_html__('Show Pin Count', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [ 'eael_pinterest_feed_type' => 'boards' ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_settings',
            [
                'label' => esc_html__('Feed Settings', 'essential-addons-elementor'),
            ]
        );

        $image_dir_url = EAEL_PRO_PLUGIN_URL . 'assets/admin/images/layout-previews/pinterest-';
        $this->add_control(
            'eael_pinterest_feed_layout',
            [
                'label'   => esc_html__('Layout', 'essential-addons-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'layout-1',
                // Layout IDs kept stable for backward-compatibility with saved widgets.
                'options'     => [
                    'layout-1' => [
                        'title' => esc_html__('Grid Overlay', 'essential-addons-elementor'),
                        'image'  => $image_dir_url . 'layout-1.png',
                    ],
                    'layout-2' => [
                        'title' => esc_html__('Masonry Overlay', 'essential-addons-elementor'),
                        'image'  => $image_dir_url . 'layout-2.png',
                    ],
                    'layout-3' => [
                        'title' => esc_html__('Polaroid Wall', 'essential-addons-elementor'),
                        'image'  => $image_dir_url . 'layout-3.png',
                    ],
                    'layout-4' => [
                        'title' => esc_html__('Card Slider', 'essential-addons-elementor'),
                        'image'  => $image_dir_url . 'layout-4.png',
                    ],
                    'layout-5' => [
                        'title' => esc_html__('Featured Slider', 'essential-addons-elementor'),
                        'image'  => $image_dir_url . 'layout-5.png',
                    ],
                ],
                'label_block' => true,
                'toggle'      => false,
                'image_choose' => true,
                'condition' => [
                    'eael_pinterest_feed_type!' => 'boards',
                ],
            ]
        );

        // Boards feed only supports Grid/Slider — pin-specific skins would render as duplicates.
        $image_dir_board_url = EAEL_PRO_PLUGIN_URL . 'assets/admin/images/layout-previews/pinterest-board-';
        $this->add_control(
            'eael_pinterest_feed_layout_boards',
            [
                'label'   => esc_html__('Layout', 'essential-addons-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'layout-1',
                'options' => [
                    'layout-1' => [
                        'title' => esc_html__('Grid', 'essential-addons-elementor'),
                        'image' => $image_dir_board_url . 'layout-1.png',
                    ],
                    'layout-4' => [
                        'title' => esc_html__('Slider', 'essential-addons-elementor'),
                        'image' => $image_dir_board_url . 'layout-2.png',
                    ],
                ],
                'label_block' => true,
                'toggle'      => false,
                'image_choose' => true,
                'condition' => [
                    'eael_pinterest_feed_type' => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_mode',
            [
                'label'   => esc_html__('Overlay Mode', 'essential-addons-elementor'),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'always',
                'toggle'  => false,
                'options' => [
                    'always' => [
                        'title' => esc_html__('Always Visible', 'essential-addons-elementor'),
                        'icon'  => 'eicon-preview-medium',
                    ],
                    'hover' => [
                        'title' => esc_html__('On Hover', 'essential-addons-elementor'),
                        'icon'  => 'eicon-click',
                    ],
                ],
                'condition' => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_columns',
            [
                'label'          => esc_html__('Columns', 'essential-addons-elementor'),
                'type'           => Controls_Manager::SELECT,
                'default'        => '4',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options'        => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-mode-masonry'                        => 'column-count: {{VALUE}};',
                    '{{WRAPPER}} .eael-pinterest-feed-mode-grid .eael-pinterest-feed-item' => 'width: calc(100% / {{VALUE}});',
                ],
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => '!in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'operator' => '!=', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slides_desktop',
            [
                'label'      => esc_html__('Slides Per View (Desktop)', 'essential-addons-elementor'),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 3,
                'min'        => 1,
                'max'        => 6,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'value' => 'layout-4' ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slides_tablet',
            [
                'label'      => esc_html__('Slides Per View (Tablet)', 'essential-addons-elementor'),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 2,
                'min'        => 1,
                'max'        => 4,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'value' => 'layout-4' ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slides_mobile',
            [
                'label'      => esc_html__('Slides Per View (Mobile)', 'essential-addons-elementor'),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 1,
                'min'        => 1,
                'max'        => 3,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'value' => 'layout-4' ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_gap',
            [
                'label'     => esc_html__('Space Between Slides', 'essential-addons-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 20,
                'min'       => 0,
                'max'       => 100,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_effect',
            [
                'label'     => esc_html__('Slide Effect', 'essential-addons-elementor'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'slide',
                'options'   => [
                    'slide'      => esc_html__('Slide', 'essential-addons-elementor'),
                    'coverflow'  => esc_html__('Coverflow', 'essential-addons-elementor'),
                ],
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_speed',
            [
                'label'     => esc_html__('Transition Speed (ms)', 'essential-addons-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 600,
                'min'       => 100,
                'max'       => 5000,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_loop',
            [
                'label'        => esc_html__('Loop', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_autoplay',
            [
                'label'        => esc_html__('Autoplay', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_autoplay_delay',
            [
                'label'      => esc_html__('Autoplay Delay (ms)', 'essential-addons-elementor'),
                'type'       => Controls_Manager::NUMBER,
                'default'    => 3000,
                'min'        => 500,
                'max'        => 10000,
                'conditions' => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'relation' => 'and',
                                    'terms'    => [
                                        [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                        [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                                    ],
                                ],
                                [
                                    'relation' => 'and',
                                    'terms'    => [
                                        [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                        [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                                    ],
                                ],
                            ],
                        ],
                        [ 'name' => 'eael_pinterest_feed_slider_autoplay', 'value' => 'yes' ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_pause_on_hover',
            [
                'label'        => esc_html__('Pause on Hover', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'relation' => 'and',
                                    'terms'    => [
                                        [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                        [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                                    ],
                                ],
                                [
                                    'relation' => 'and',
                                    'terms'    => [
                                        [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                        [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                                    ],
                                ],
                            ],
                        ],
                        [ 'name' => 'eael_pinterest_feed_slider_autoplay', 'value' => 'yes' ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_navigation',
            [
                'label'        => esc_html__('Show Navigation Arrows', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_dots',
            [
                'label'        => esc_html__('Show Dots (Pagination)', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_grab_cursor',
            [
                'label'        => esc_html__('Grab Cursor', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_image_count',
            [
                'label'     => esc_html__('Number of Pins', 'essential-addons-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 12,
                'min'       => 1,
                'max'       => 100,
                'condition' => [ 'eael_pinterest_feed_type!' => 'boards' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_count',
            [
                'label'     => esc_html__('Number of Boards', 'essential-addons-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 12,
                'min'       => 1,
                'max'       => 100,
                'condition' => [ 'eael_pinterest_feed_type' => 'boards' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_force_square',
            [
                'label'        => esc_html__('Square Image', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Crops every pin to a 1:1 square (object-fit: cover) for an even editorial grid.', 'essential-addons-elementor'),
                'conditions'   => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'value' => 'layout-1' ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-1' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_caption_style',
            [
                'label'   => esc_html__('Caption Style', 'essential-addons-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default'     => esc_html__('Bottom', 'essential-addons-elementor'),
                    'caption_top' => esc_html__('Top', 'essential-addons-elementor'),
                ],
                'condition' => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                ],
            ]
        );


        $this->add_control(
            'eael_pinterest_feed_show_caption',
            [
                'label' => esc_html__('Show Caption', 'essential-addons-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off' => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition' => [ 'eael_pinterest_feed_type!' => 'boards' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_show_board',
            [
                'label'        => esc_html__('Show Board Name', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_show_domain',
            [
                'label'       => esc_html__('Show Source Domain', 'essential-addons-elementor'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'hover',
                'options'     => [
                    ''       => esc_html__( 'Off',                          'essential-addons-elementor' ),
                    'hover'  => esc_html__( 'Hover only (top-right pill)',  'essential-addons-elementor' ),
                    'always' => esc_html__( 'Always (inline in meta row)',  'essential-addons-elementor' ),
                ],
                'description' => esc_html__('Outbound pins only — pinterest.com fallback links are skipped.', 'essential-addons-elementor'),
                'condition'   => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );


        $this->add_control(
            'eael_pinterest_feed_show_date',
            [
                'label' => esc_html__('Show Date', 'essential-addons-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'label_on' => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off' => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition' => [
                    'eael_pinterest_feed_type!' => 'boards',
                    'eael_pinterest_feed_layout' => 'layout-4',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_show_chip',
            [
                'label'        => esc_html__('Show Board Chip', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Show the source board name as a small badge straddling the image/content seam. Hidden when no board name is available.', 'essential-addons-elementor'),
                'condition'    => [
                    'eael_pinterest_feed_layout' => 'layout-4',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_description',
            [
                'label'        => esc_html__('Show Description', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_layout' => [ 'layout-4', 'layout-5' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_description_length',
            [
                'label'     => esc_html__('Description Length', 'essential-addons-elementor'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 60,
                'min'       => 10,
                'max'       => 500,
                'condition' => [
                    'eael_pinterest_feed_layout'           => [ 'layout-4', 'layout-5' ],
                    'eael_pinterest_feed_show_description' => 'yes',
                    'eael_pinterest_feed_type!'            => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_editorial_show_url',
            [
                'label'        => esc_html__('Show Source URL', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_layout' => [ 'layout-5' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_read_more',
            [
                'label'        => esc_html__('Show Read More', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_layout' => 'layout-4',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_read_more_text',
            [
                'label'     => esc_html__('Read More Text', 'essential-addons-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Read More', 'essential-addons-elementor'),
                'condition' => [
                    'eael_pinterest_feed_layout'          => 'layout-4',
                    'eael_pinterest_feed_show_read_more'  => 'yes',
                    'eael_pinterest_feed_type!'           => 'boards',
                ],
            ]
        );

        // Featured Slider (layout-5) has its own controls so layout-4 defaults stay intact.
        $this->add_control(
            'eael_pinterest_feed_featured_show_board',
            [
                'label'        => esc_html__('Show Board Name', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Show the source board name as a small accent above the title. Hidden when no board name is available.', 'essential-addons-elementor'),
                'condition'    => [
                    'eael_pinterest_feed_layout' => 'layout-5',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_show_date',
            [
                'label'        => esc_html__('Show Date', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_type!'  => 'boards',
                    'eael_pinterest_feed_layout' => 'layout-5',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_show_read_more',
            [
                'label'        => esc_html__('Show Read More', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_layout' => 'layout-5',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_read_more_text',
            [
                'label'     => esc_html__('Read More Text', 'essential-addons-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Read More', 'essential-addons-elementor'),
                'condition' => [
                    'eael_pinterest_feed_layout'                  => 'layout-5',
                    'eael_pinterest_feed_featured_show_read_more' => 'yes',
                    'eael_pinterest_feed_type!'                   => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_modern_show_board',
            [
                'label'        => esc_html__('Show Board Name', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Display the board name with a folder icon — neutral wording (no "Saved in" prefix).', 'essential-addons-elementor'),
                'condition'    => [
                    'eael_pinterest_feed_layout'  => [ 'layout-3' ],
                    'eael_pinterest_feed_type!'   => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_modern_show_domain',
            [
                'label'        => esc_html__('Show Source Domain', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Show the destination domain (e.g. example.com) on outbound pins.', 'essential-addons-elementor'),
                'condition'    => [
                    'eael_pinterest_feed_layout' => [ 'layout-3' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_modern_description_lines',
            [
                'label'     => esc_html__('Title Line Clamp', 'essential-addons-elementor'),
                'type'      => Controls_Manager::SELECT,
                'default'   => '2',
                'options'   => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                ],
                'condition' => [
                    'eael_pinterest_feed_layout'        => [ 'layout-3' ],
                    'eael_pinterest_feed_show_caption!' => '',
                    'eael_pinterest_feed_type!'         => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_polaroid_always_tilt',
            [
                'label'        => esc_html__('Always Tilt', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Apply the alternating left/right tilt as the default state, not just on hover.', 'essential-addons-elementor'),
                'condition'    => [
                    'eael_pinterest_feed_layout' => 'layout-3',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_modern_show_credit',
            [
                'label'        => esc_html__('Show "Powered by Pinterest"', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'description'  => esc_html__('Small wordmark credit below the feed. Replaces per-card Pinterest icons for cleaner cards.', 'essential-addons-elementor'),
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_pagination',
            [
                'label'        => esc_html__('Load More', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'conditions'   => [
                    'relation' => 'and',
                    'terms'    => [
                        [ 'name' => 'eael_pinterest_feed_layout',        'operator' => '!in', 'value' => [ 'layout-4', 'layout-5' ] ],
                        [ 'name' => 'eael_pinterest_feed_layout_boards', 'operator' => '!=',  'value' => 'layout-4' ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'loadmore_text',
            [
                'label'     => esc_html__('Load More Text', 'essential-addons-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Load More', 'essential-addons-elementor'),
                'condition' => [
                    'eael_pinterest_feed_pagination' => 'yes',
                    'eael_pinterest_feed_layout!'    => 'layout-4',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_profile_header',
            [
                'label' => esc_html__('Profile Header', 'essential-addons-elementor'),
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_header',
            [
                'label'        => esc_html__('Show Profile Header', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_header_style',
            [
                'label'   => esc_html__('Header Style', 'essential-addons-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline'  => esc_html__('Inline', 'essential-addons-elementor'),
                    'stacked' => esc_html__('Stacked', 'essential-addons-elementor'),
                ],
                'condition' => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_header_meta_order',
            [
                'label'   => esc_html__('Meta Order', 'essential-addons-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'stats-first',
                'options' => [
                    'stats-first'  => esc_html__('Stats → Follow Button', 'essential-addons-elementor'),
                    'button-first' => esc_html__('Follow Button → Stats', 'essential-addons-elementor'),
                ],
                'condition' => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_custom_avatar',
            [
                'label'     => esc_html__('Custom Avatar', 'essential-addons-elementor'),
                'type'      => Controls_Manager::MEDIA,
                'condition' => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_pinterest_url',
            [
                'label'       => esc_html__('Pinterest Profile URL', 'essential-addons-elementor'),
                'type'        => Controls_Manager::URL,
                'ai'          => [ 'active' => false ],
                'placeholder' => 'https://www.pinterest.com/username/',
                'condition'   => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_follow_btn',
            [
                'label'        => esc_html__('Show Follow Button', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_follow_btn_pinterest_icon',
            [
                'label'        => esc_html__('Show Pinterest Icon', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [
                    'eael_pinterest_feed_show_header' => 'yes',
                    'eael_pinterest_feed_show_follow_btn' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_follow_btn_text',
            [
                'label'     => esc_html__('Follow Button Text', 'essential-addons-elementor'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Follow', 'essential-addons-elementor'),
                'condition' => [
                    'eael_pinterest_feed_show_header'     => 'yes',
                    'eael_pinterest_feed_show_follow_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_show_stats',
            [
                'label'        => esc_html__('Show Followers / Following', 'essential-addons-elementor'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'label_on'     => esc_html__('Yes', 'essential-addons-elementor'),
                'label_off'    => esc_html__('No', 'essential-addons-elementor'),
                'return_value' => 'yes',
                'condition'    => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        $this->register_style_controls();
    }

    protected function register_style_controls() {
        $this->start_controls_section(
            'eael_section_pinterest_feed_item_styles',
            [
                'label' => esc_html__('Pin Item', 'essential-addons-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_item_spacing',
            [
                'label' => esc_html__('Item Spacing', 'essential-addons-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '5',
                    'right' => '5',
                    'bottom' => '5',
                    'left' => '5',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_image_ratio',
            [
                'label'   => esc_html__('Image Aspect Ratio', 'essential-addons-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''       => esc_html__('Default', 'essential-addons-elementor'),
                    '1 / 1'  => esc_html__('1:1 Square', 'essential-addons-elementor'),
                    '4 / 5'  => esc_html__('4:5 Portrait', 'essential-addons-elementor'),
                    '3 / 4'  => esc_html__('3:4 Portrait', 'essential-addons-elementor'),
                    '2 / 3'  => esc_html__('2:3 Tall Portrait', 'essential-addons-elementor'),
                    '16 / 9' => esc_html__('16:9 Landscape', 'essential-addons-elementor'),
                    '4 / 3'  => esc_html__('4:3 Landscape', 'essential-addons-elementor'),
                    'custom' => esc_html__('Custom Height (px)', 'essential-addons-elementor'),
                ],
                'condition' => [
                    'eael_pinterest_feed_layout!' => 'layout-2',
                ],
                'description' => esc_html__('Layout 1 default: 2:3, Layout 3 default: 3:4. Masonry uses natural image heights.', 'essential-addons-elementor'),
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_image_height',
            [
                'label' => esc_html__('Image Height', 'essential-addons-elementor'),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [ 'min' => 100, 'max' => 800 ],
                    'vh' => [ 'min' => 10, 'max' => 100 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-img' => 'aspect-ratio: unset; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'eael_pinterest_feed_image_ratio' => 'custom',
                    'eael_pinterest_feed_layout!'     => 'layout-2',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_item_style_separator',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'eael_pinterest_feed_item_border',
                'label' => esc_html__('Border', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pinterest-feed-item-inner',
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'essential-addons-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-item-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    //Todo needs a seperate controller
                    // '{{WRAPPER}} .eael-pinterest-feed-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'eael_pinterest_feed_item_shadow',
                'label' => esc_html__('Box Shadow', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pinterest-feed-item-inner',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_caption_styles',
            [
                'label'     => esc_html__('Caption', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_caption_bg_color',
            [
                'label' => esc_html__('Background Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-caption' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'eael_pinterest_feed_caption_typography',
                'label' => esc_html__('Typography', 'essential-addons-elementor'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_TEXT,
                ],
                'selector' => '{{WRAPPER}} .eael-pinterest-feed-caption-text',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_caption_color',
            [
                'label' => esc_html__('Text Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-caption-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_caption_padding',
            [
                'label' => esc_html__('Padding', 'essential-addons-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_overlay_styles',
            [
                'label' => esc_html__('Overlay', 'essential-addons-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_gradient_color',
            [
                'label' => esc_html__('Overlay Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-overlay:not(.eael-pinterest-feed-caption-top) .eael-pinterest-feed-caption' =>
                        'background: linear-gradient(to top, {{VALUE}} 0%, transparent 100%);',
                    '{{WRAPPER}} .eael-pinterest-feed-overlay.eael-pinterest-feed-caption-top .eael-pinterest-feed-caption' =>
                        'background: linear-gradient(to bottom, {{VALUE}} 0%, transparent 100%);',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_title_color',
            [
                'label' => esc_html__('Title Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-overlay .eael-pinterest-feed-caption-text' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_board_color',
            [
                'label'     => esc_html__('Board Name Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-overlay .eael-pinterest-feed-board' =>
                        'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_overlay_show_board' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_overlay_domain_color',
            [
                'label'     => esc_html__('Source Domain Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'description' => esc_html__('Applied to both the hover-pill and inline domain styles.', 'essential-addons-elementor'),
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-domain,
                     {{WRAPPER}} .eael-pinterest-feed-domain-inline' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_card_styles',
            [
                'label' => esc_html__('Card Style', 'essential-addons-elementor'),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => 'layout-4',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_bg',
            [
                'label' => esc_html__('Card Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-4 .eael-pinterest-feed-item-inner' =>
                        'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_card_chip_heading',
            [
                'label'     => esc_html__('Board Chip', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_card_show_chip' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_chip_bg',
            [
                'label' => esc_html__('Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-chip' => 'background-color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_card_show_chip' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_chip_color',
            [
                'label' => esc_html__('Text Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-chip' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_card_show_chip' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_chip_icon_color',
            [
                'label' => esc_html__('Icon Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-chip i' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_card_show_chip' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pf_card_title_heading',
            [
                'label'     => esc_html__('Title', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_card_title_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_TEXT ],
                'selector' => '{{WRAPPER}} .eael-pf-card-title',
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_title_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-title' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pf_card_date_heading',
            [
                'label'     => esc_html__('Date', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_date' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_date_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-date' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_date' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pf_card_desc_heading',
            [
                'label'     => esc_html__('Description', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_description' => 'yes' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_card_desc_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_TEXT ],
                'selector' => '{{WRAPPER}} .eael-pf-card-description',
                'condition' => [ 'eael_pinterest_feed_show_description' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_card_desc_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-description' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_description' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pf_card_read_more_heading',
            [
                'label'     => esc_html__('Read More', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_read_more' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_read_more_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-read-more' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_read_more' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_read_more_hover_color',
            [
                'label' => esc_html__('Hover Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-card-link:hover .eael-pf-card-read-more' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_read_more' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_featured_styles',
            [
                'label'     => esc_html__('Featured Card', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => 'layout-5',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_card_bg',
            [
                'label'     => esc_html__('Card Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pinterest-feed-item-inner' =>
                        'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_board_color',
            [
                'label'     => esc_html__('Board Name Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-board' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_title_color',
            [
                'label'     => esc_html__('Title Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-title' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_text_color',
            [
                'label'     => esc_html__('Text Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'description' => esc_html__('Applied to date, description, and source URL.', 'essential-addons-elementor'),
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-date,
                     {{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-description,
                     {{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-url' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_button_bg',
            [
                'label'     => esc_html__('Read More Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-button' =>
                        'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_featured_button_color',
            [
                'label'     => esc_html__('Read More Text Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-layout-layout-5 .eael-pf-editorial-button' =>
                        'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_polaroid_styles',
            [
                'label'     => esc_html__('Polaroid', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => 'layout-3',
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_polaroid_frame_bg',
            [
                'label'     => esc_html__('Frame Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'description' => esc_html__('White paper frame around the photo and caption.', 'essential-addons-elementor'),
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-polaroid-frame' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_polaroid_frame_radius',
            [
                'label'      => esc_html__('Frame Border Radius', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-polaroid-frame' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'eael_pinterest_feed_polaroid_frame_shadow',
                'label'    => esc_html__('Frame Shadow', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-polaroid-frame',
            ]
        );

        $this->add_control(
            'eael_pf_polaroid_title_heading',
            [
                'label'     => esc_html__('Title', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_polaroid_title_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'global'   => [ 'default' => Global_Typography::TYPOGRAPHY_TEXT ],
                'selector' => '{{WRAPPER}} .eael-pf-polaroid-title',
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_polaroid_title_color',
            [
                'label'     => esc_html__('Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-polaroid-title' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_caption' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pf_polaroid_meta_heading',
            [
                'label'     => esc_html__('Meta (Board / Domain)', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_polaroid_meta_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-polaroid-meta',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_polaroid_board_color',
            [
                'label'     => esc_html__('Board Name Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-polaroid-board' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_modern_show_board' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_polaroid_domain_color',
            [
                'label'     => esc_html__('Domain Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-polaroid-domain' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_modern_show_domain' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_board_styles',
            [
                'label'     => esc_html__('Board Card', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_type' => 'boards',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_card_bg',
            [
                'label'     => esc_html__('Card Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-board-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_board_card_radius',
            [
                'label'      => esc_html__('Card Border Radius', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-board-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'eael_pinterest_feed_board_card_border',
                'label'    => esc_html__('Card Border', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-board-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'eael_pinterest_feed_board_card_shadow',
                'label'    => esc_html__('Card Shadow', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-board-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'eael_pinterest_feed_board_card_shadow_hover',
                'label'    => esc_html__('Card Shadow (Hover)', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-board-card:hover',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_cover_gap',
            [
                'label'      => esc_html__('Cover Image Gap', 'essential-addons-elementor'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 12 ] ],
                'default'    => [ 'unit' => 'px', 'size' => 2 ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-board-cover' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_cover_bg',
            [
                'label'     => esc_html__('Cover Placeholder Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'description' => esc_html__('Shown briefly before cover images load, and behind the gap between images.', 'essential-addons-elementor'),
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-board-cover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_info_heading',
            [
                'label' => esc_html__('Info Footer', 'essential-addons-elementor'),
                'type'  => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_info_bg',
            [
                'label'     => esc_html__('Info Background', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-board-info' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_board_info_padding',
            [
                'label'      => esc_html__('Info Padding', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-board-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_name_heading',
            [
                'label' => esc_html__('Board Name', 'essential-addons-elementor'),
                'type'  => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_board_name' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_name_color',
            [
                'label'     => esc_html__('Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-board-name' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_board_name' => 'yes' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_board_name_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-board-name',
                'condition' => [ 'eael_pinterest_feed_show_board_name' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_pin_count_heading',
            [
                'label' => esc_html__('Pin Count', 'essential-addons-elementor'),
                'type'  => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_show_pin_count' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_board_pin_count_color',
            [
                'label'     => esc_html__('Color', 'essential-addons-elementor'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-board-pin-count' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_show_pin_count' => 'yes' ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_board_pin_count_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-board-pin-count',
                'condition' => [ 'eael_pinterest_feed_show_pin_count' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_meta_styles',
            [
                'label'     => esc_html__('Meta', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_layout' => [ 'layout-1', 'layout-2' ],
                    'eael_pinterest_feed_type!'  => 'boards',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'eael_pinterest_feed_meta_typography',
                'label' => esc_html__('Typography', 'essential-addons-elementor'),
                'description' => esc_html__('Applied to the board name and inline source domain row under the title.', 'essential-addons-elementor'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_TEXT,
                ],
                'selector' => '{{WRAPPER}} .eael-pinterest-feed-meta span',
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_meta_spacing',
            [
                'label' => esc_html__('Spacing', 'essential-addons-elementor'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-meta span' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_header_styles',
            [
                'label'     => esc_html__('Profile Header', 'essential-addons-elementor'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [ 'eael_pinterest_feed_show_header' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_header_bg',
            [
                'label' => esc_html__('Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed-profile-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_header_padding',
            [
                'label'      => esc_html__('Padding', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pinterest-feed-profile-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'eael_pinterest_feed_header_border',
                'label'    => esc_html__('Border', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pinterest-feed-profile-header',
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_header_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pinterest-feed-profile-header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_header_avatar_heading',
            [
                'label'     => esc_html__('Avatar', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_avatar_size',
            [
                'label' => esc_html__('Size', 'essential-addons-elementor'),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [ 'px' => [ 'min' => 30, 'max' => 120 ] ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-avatar' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_header_name_heading',
            [
                'label'     => esc_html__('Username', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pinterest_feed_header_name_typography',
                'label'    => esc_html__('Typography', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-pf-header-username',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_header_name_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-header-username' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_header_stats_heading',
            [
                'label'     => esc_html__('Stats', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_header_stats_color',
            [
                'label' => esc_html__('Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-stat' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pf_header_follow_heading',
            [
                'label'     => esc_html__('Follow Button', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_follow_bg',
            [
                'label' => esc_html__('Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-follow-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_follow_color',
            [
                'label' => esc_html__('Text Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-follow-btn, {{WRAPPER}} .eael-pf-follow-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_follow_padding',
            [
                'label'      => esc_html__('Padding', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-follow-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_follow_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'essential-addons-elementor'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .eael-pf-follow-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_slider_styles',
            [
                'label'      => esc_html__('Slider', 'essential-addons-elementor'),
                'tab'        => Controls_Manager::TAB_STYLE,
                'conditions' => [
                    'relation' => 'or',
                    'terms'    => [
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',   'operator' => '!=', 'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout', 'operator' => 'in', 'value' => [ 'layout-4', 'layout-5' ] ],
                            ],
                        ],
                        [
                            'relation' => 'and',
                            'terms'    => [
                                [ 'name' => 'eael_pinterest_feed_type',           'value' => 'boards' ],
                                [ 'name' => 'eael_pinterest_feed_layout_boards', 'value' => 'layout-4' ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_slider_container_bg',
            [
                'label' => esc_html__('Container Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed.eael-pinterest-feed-mode-slider' =>
                        'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_dots_color',
            [
                'label' => esc_html__('Dots Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-pagination .swiper-pagination-bullet' =>
                        'background: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_dots' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_dots_active_color',
            [
                'label' => esc_html__('Active Dot Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-pagination .swiper-pagination-bullet-active' =>
                        'background: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_dots' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_container_border_radius',
            [
                'label' => esc_html__('Container Border Radius', 'essential-addons-elementor'),
                'type'  => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .eael-pinterest-feed.eael-pinterest-feed-mode-slider' =>
                        'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'eael_pf_slider_arrow_heading',
            [
                'label'     => esc_html__('Navigation Arrows', 'essential-addons-elementor'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [ 'eael_pinterest_feed_slider_navigation' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_arrow_bg_color',
            [
                'label' => esc_html__('Arrow Background', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-arrow' => 'background-color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_navigation' => 'yes' ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_arrow_color',
            [
                'label' => esc_html__('Arrow Icon Color', 'essential-addons-elementor'),
                'type'  => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-arrow i' => 'color: {{VALUE}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_navigation' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_arrow_size',
            [
                'label' => esc_html__('Arrow Size', 'essential-addons-elementor'),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [ 'px' => [ 'min' => 20, 'max' => 60 ] ],
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_navigation' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_arrow_border_radius',
            [
                'label' => esc_html__('Arrow Border Radius', 'essential-addons-elementor'),
                'type'  => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .eael-pf-arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [ 'eael_pinterest_feed_slider_navigation' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'eael_section_pinterest_feed_load_more_styles',
            [
                'label' => esc_html__('Load More Button', 'essential-addons-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pinterest_feed_pagination' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'eael_pinterest_feed_load_more_typography',
                'label' => esc_html__('Typography', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-load-more-button',
            ]
        );

        $this->start_controls_tabs('eael_pinterest_feed_load_more_tabs');

        $this->start_controls_tab(
            'eael_pinterest_feed_load_more_normal',
            [
                'label' => esc_html__('Normal', 'essential-addons-elementor'),
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_load_more_color',
            [
                'label' => esc_html__('Text Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_load_more_bg_color',
            [
                'label' => esc_html__('Background Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'eael_pinterest_feed_load_more_hover',
            [
                'label' => esc_html__('Hover', 'essential-addons-elementor'),
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_load_more_hover_color',
            [
                'label' => esc_html__('Text Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pinterest_feed_load_more_hover_bg_color',
            [
                'label' => esc_html__('Background Color', 'essential-addons-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'eael_pinterest_feed_load_more_padding',
            [
                'label' => esc_html__('Padding', 'essential-addons-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '12',
                    'right' => '24',
                    'bottom' => '12',
                    'left' => '24',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'eael_pinterest_feed_load_more_border',
                'label' => esc_html__('Border', 'essential-addons-elementor'),
                'selector' => '{{WRAPPER}} .eael-load-more-button',
            ]
        );

        $this->add_responsive_control(
            'eael_pinterest_feed_load_more_border_radius',
            [
                'label' => esc_html__('Border Radius', 'essential-addons-elementor'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .eael-load-more-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Skip render entirely when Pinterest isn't connected; otherwise wrappers render around an empty feed.
        $pf_token = $this->get_current_pinterest_access_token( $settings );
        if ( empty( $pf_token ) ) {
            return;
        }

        $layout       = $this->get_active_layout( $settings );
        $overlay_mode = $settings['eael_pinterest_feed_overlay_mode'] ?? 'always';
        $post_id      = 0;
        if ( Plugin::$instance->documents->get_current() ) {
            $post_id = Plugin::$instance->documents->get_current()->get_main_id();
        }

        switch ( $layout ) {
            case 'layout-1':
                $display_mode = 'grid';
                $visual       = 'overlay';
                break;
            case 'layout-2':
                $display_mode = 'masonry';
                $visual       = 'overlay';
                break;
            case 'layout-5':
                $display_mode = 'slider';
                $visual       = 'editorial';
                break;
            case 'layout-3':
                $display_mode = 'masonry';
                $visual       = 'polaroid';
                break;
            case 'layout-4':
            default:
                $display_mode = 'slider';
                $visual       = 'card';
                break;
        }
        $is_slider = ( $display_mode === 'slider' );

        $wrap_classes = [
            'eael-pinterest-feed',
            'eael-pinterest-feed-mode-' . $display_mode,
            'eael-pf-layout-' . $layout,
        ];

        if ( $visual === 'overlay' ) {
            $wrap_classes[] = 'eael-pinterest-feed-overlay';
            if ( $overlay_mode === 'always' ) {
                $wrap_classes[] = 'eael-pinterest-feed-overlay-always';
            }
            if ( ( $settings['eael_pinterest_feed_caption_style'] ?? 'default' ) === 'caption_top' ) {
                $wrap_classes[] = 'eael-pinterest-feed-caption-top';
            }
        }

        if ( $is_slider ) {
            $wrap_classes[] = 'swiper';
        }

        $this->add_render_attribute('pinterest-wrap', [
            'class' => $wrap_classes,
            'id'    => 'eael-pinterest-feed-' . $this->get_id(),
        ]);

        // Skip masonry layouts — natural image height drives the column flow.
        $img_ratio = $settings['eael_pinterest_feed_image_ratio'] ?? '';
        if ( ! empty( $img_ratio ) && $img_ratio !== 'custom' && ! in_array( $layout, [ 'layout-2', 'layout-3' ], true ) ) {
            $this->add_render_attribute('pinterest-wrap', 'style', '--eael-pf-img-ratio: ' . esc_attr( $img_ratio ) . ';');
        }

        if ( $is_slider ) {
            $wid       = $this->get_id();
            $autoplay  = ( $settings['eael_pinterest_feed_slider_autoplay']       ?? 'yes' ) === 'yes' ? 1 : 0;
            $loop      = ( $settings['eael_pinterest_feed_slider_loop']            ?? 'yes' ) === 'yes' ? 1 : 0;
            $nav       = ( $settings['eael_pinterest_feed_slider_navigation']      ?? 'yes' ) === 'yes' ? 1 : 0;
            $dots      = ( $settings['eael_pinterest_feed_slider_dots']            ?? 'yes' ) === 'yes' ? 1 : 0;
            $pause     = ( $settings['eael_pinterest_feed_slider_pause_on_hover']  ?? 'yes' ) === 'yes' ? 1 : 0;
            $grab      = ( $settings['eael_pinterest_feed_slider_grab_cursor']     ?? '' )   === 'yes' ? 1 : 0;

            // Featured Slider is always one pin per slide regardless of stale layout-4 values.
            $is_featured     = ( $layout === 'layout-5' );
            $slides_desktop  = $is_featured ? 1 : absint( $settings['eael_pinterest_feed_slides_desktop'] ?? 3 );
            $slides_tablet   = $is_featured ? 1 : absint( $settings['eael_pinterest_feed_slides_tablet']  ?? 2 );
            $slides_mobile   = $is_featured ? 1 : absint( $settings['eael_pinterest_feed_slides_mobile']  ?? 1 );

            $this->add_render_attribute('pinterest-wrap', [
                'data-slides-desktop'  => $slides_desktop,
                'data-slides-tablet'   => $slides_tablet,
                'data-slides-mobile'   => $slides_mobile,
                'data-gap'             => absint( $settings['eael_pinterest_feed_slider_gap']              ?? 20 ),
                'data-effect'          => esc_attr( $settings['eael_pinterest_feed_slider_effect']         ?? 'slide' ),
                'data-speed'           => absint( $settings['eael_pinterest_feed_slider_speed']            ?? 600 ),
                'data-loop'            => $loop,
                'data-autoplay'        => $autoplay,
                'data-autoplay-delay'  => absint( $settings['eael_pinterest_feed_slider_autoplay_delay']   ?? 3000 ),
                'data-pause-on-hover'  => $pause,
                'data-grab-cursor'     => $grab,
                'data-navigation'      => $nav,
                'data-dots'            => $dots,
                'data-pagination'      => '.eael-pf-pagination-' . esc_attr( $wid ),
                'data-arrow-next'      => '.eael-pf-arrow-next-' . esc_attr( $wid ),
                'data-arrow-prev'      => '.eael-pf-arrow-prev-' . esc_attr( $wid ),
            ]);
        }

        if ( ! $is_slider && $visual !== 'card' && ( $settings['eael_pinterest_feed_force_square'] ?? '' ) === 'yes' ) {
            $this->add_render_attribute('pinterest-wrap', 'class', 'eael-pinterest-feed-square-img');
        }

        if ( $visual === 'polaroid' && ( $settings['eael_pinterest_feed_polaroid_always_tilt'] ?? '' ) === 'yes' ) {
            $this->add_render_attribute('pinterest-wrap', 'class', 'eael-pf-polaroid-always-tilt');
        }

        $show_load_more = ( ! $is_slider && ( $settings['eael_pinterest_feed_pagination'] ?? '' ) === 'yes' );
        if ( $show_load_more ) {
            $this->add_render_attribute('load-more', [
                'type'           => 'button',
                'class' => [
                    'eael-load-more-button',
                    'eael-pinterest-feed-load-more',
                ],
                'data-widget-id' => $this->get_id(),
                'data-post-id'   => $post_id,
                'data-page'      => 1,
                'data-per-page'  => ( ( $settings['eael_pinterest_feed_type'] ?? '' ) === 'boards' )
                    ? absint( $settings['eael_pinterest_feed_board_count'] ?? 12 )
                    : absint( $settings['eael_pinterest_feed_image_count'] ?? 12 ),
            ]);

            if ( Plugin::instance()->editor->is_edit_mode() ) {
                $this->add_render_attribute('load-more', [
                    'data-settings' => http_build_query([
                        'eael_pinterest_feed_type'               => $settings['eael_pinterest_feed_type'],
                        'eael_pinterest_feed_board_id'           => $settings['eael_pinterest_feed_board_id'] ?? '',
                        'eael_pinterest_feed_image_count'        => $settings['eael_pinterest_feed_image_count'],
                        'eael_pinterest_feed_board_count'        => $settings['eael_pinterest_feed_board_count'] ?? 12,
                        'eael_pinterest_feed_layout'             => $settings['eael_pinterest_feed_layout'] ?? 'layout-1',
                        'eael_pinterest_feed_layout_boards'      => $settings['eael_pinterest_feed_layout_boards'] ?? 'layout-1',
                        'eael_pinterest_feed_overlay_mode'       => $settings['eael_pinterest_feed_overlay_mode'] ?? 'always',
                        'eael_pinterest_feed_show_caption'       => $settings['eael_pinterest_feed_show_caption'] ?? 'yes',
                        'eael_pinterest_feed_show_date'          => $settings['eael_pinterest_feed_show_date'] ?? 'yes',
                        'eael_pinterest_feed_show_description'   => $settings['eael_pinterest_feed_show_description'] ?? 'yes',
                        'eael_pinterest_feed_description_length' => $settings['eael_pinterest_feed_description_length'] ?? 100,
                        'eael_pinterest_feed_show_read_more'     => $settings['eael_pinterest_feed_show_read_more'] ?? 'yes',
                        'eael_pinterest_feed_read_more_text'     => $settings['eael_pinterest_feed_read_more_text'] ?? 'Read More',
                        'eael_pinterest_feed_data_cache_limit'   => $settings['eael_pinterest_feed_data_cache_limit'],
                        'eael_pinterest_feed_board_cover_style'  => $settings['eael_pinterest_feed_board_cover_style'] ?? 'multi-image',
                        'eael_pinterest_feed_show_board_name'    => $settings['eael_pinterest_feed_show_board_name'] ?? 'yes',
                        'eael_pinterest_feed_show_pin_count'     => $settings['eael_pinterest_feed_show_pin_count'] ?? 'yes',
                        'eael_pinterest_feed_modern_show_board'        => $settings['eael_pinterest_feed_modern_show_board']        ?? 'yes',
                        'eael_pinterest_feed_modern_show_domain'       => $settings['eael_pinterest_feed_modern_show_domain']       ?? 'yes',
                        'eael_pinterest_feed_modern_description_lines' => $settings['eael_pinterest_feed_modern_description_lines'] ?? 2,
                        'eael_pinterest_feed_modern_show_credit'       => $settings['eael_pinterest_feed_modern_show_credit']       ?? 'yes',
                        'eael_pinterest_feed_editorial_show_url'       => $settings['eael_pinterest_feed_editorial_show_url']       ?? 'yes',
                        'eael_pinterest_feed_overlay_show_board'       => $settings['eael_pinterest_feed_overlay_show_board']       ?? 'yes',
                        'eael_pinterest_feed_overlay_show_domain'      => $settings['eael_pinterest_feed_overlay_show_domain']      ?? 'hover',
                        'eael_pinterest_feed_polaroid_always_tilt'     => $settings['eael_pinterest_feed_polaroid_always_tilt']     ?? '',
                        'eael_pinterest_feed_card_show_chip'           => $settings['eael_pinterest_feed_card_show_chip']           ?? 'yes',
                        'eael_pinterest_feed_featured_show_board'      => $settings['eael_pinterest_feed_featured_show_board']      ?? 'yes',
                        'eael_pinterest_feed_featured_show_date'       => $settings['eael_pinterest_feed_featured_show_date']       ?? 'yes',
                        'eael_pinterest_feed_featured_show_read_more'  => $settings['eael_pinterest_feed_featured_show_read_more']  ?? 'yes',
                        'eael_pinterest_feed_featured_read_more_text'  => $settings['eael_pinterest_feed_featured_read_more_text']  ?? 'Read More',
                    ]),
                ]);
            }
        }
        ?>
        <?php
            // Pre-populate boards dropdown cache so SELECT2 has data on next editor load.
            $pf_cache_duration = max( 1, absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 ) );
            $this->get_boards_for_dropdown( $pf_token, $pf_cache_duration );

            $profile_header = $this->render_profile_header( $settings );
            if ( ! empty( $profile_header ) ) {
                echo wp_kses( $profile_header, Helper::eael_allowed_tags( Helper::eael_allowed_icon_tags() ) );
            }
        ?>
        <div <?php $this->print_render_attribute_string('pinterest-wrap'); ?>>
            <?php if ( $is_slider ) : ?>
            <div class="swiper-wrapper">
            <?php endif; ?>
            <?php
                $pinterest_content = $this->pinterest_feed_load_more();
                echo $pinterest_content ? wp_kses($pinterest_content, Helper::eael_allowed_tags( Helper::eael_allowed_icon_tags() )) : '';
            ?>
            <?php if ( $is_slider ) :
                $wid_r  = $this->get_id();
                $dots_r = ( $settings['eael_pinterest_feed_slider_dots']       ?? 'yes' ) === 'yes';
                $nav_r  = ( $settings['eael_pinterest_feed_slider_navigation'] ?? 'yes' ) === 'yes';
            ?>
            </div>
            <?php if ( $dots_r ) : ?>
            <div class="eael-pf-pagination swiper-pagination eael-pf-pagination-<?php echo esc_attr( $wid_r ); ?>"></div>
            <?php endif; ?>
            <?php if ( $nav_r ) : ?>
            <div class="eael-pf-arrow eael-pf-arrow-next swiper-button-next eael-pf-arrow-next-<?php echo esc_attr( $wid_r ); ?>">
                <i class="fas fa-angle-right" aria-hidden="true"></i>
            </div>
            <div class="eael-pf-arrow eael-pf-arrow-prev swiper-button-prev eael-pf-arrow-prev-<?php echo esc_attr( $wid_r ); ?>">
                <i class="fas fa-angle-left" aria-hidden="true"></i>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>

        <?php
        if ( ( $settings['eael_pinterest_feed_modern_show_credit'] ?? 'yes' ) === 'yes' ) : ?>
            <div class="eael-pf-credit">
                <span><?php esc_html_e( 'Powered by', 'essential-addons-elementor' ); ?></span>
                <span class="eael-pf-credit-mark">
                    <i class="fab fa-pinterest eael-pf-credit-icon" aria-hidden="true"></i>
                </span>
            </div>
        <?php endif; ?>

        <?php
        // $eael_pf_has_more is set by pinterest_feed_load_more() during the initial render above.
        if ( $show_load_more && ! empty( $this->eael_pf_has_more ) ) { ?>
            <div class="eael-load-more-button-wrap eael-pf-mode-<?php echo esc_attr( $display_mode ); ?>">
                <button <?php $this->print_render_attribute_string('load-more'); ?>>
                    <div class="eael-btn-loader button__loader"></div>
                    <span><?php echo esc_html($settings['loadmore_text']); ?></span>
                </button>
            </div>
            <?php
        }
    }
}
