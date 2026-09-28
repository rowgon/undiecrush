<?php
namespace Essential_Addons_Elementor\Pro\Elements;

use \Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use \Elementor\Widget_Base;
use \Elementor\Plugin;
use \Elementor\Group_Control_Css_Filter;

use \Essential_Addons_Elementor\Pro\Classes\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pricing_Slider extends Widget_Base {
    public function get_name() {
        return 'eael-pricing-slider';
    }

    public function get_title() {
        return esc_html__('Pricing Slider', 'essential-addons-elementor');
    }

    public function get_icon() {
        return 'eaicon-pricing-slider';
    }

    public function get_categories() {
        return ['essential-addons-elementor'];
    }

    public function get_keywords() {
        return [
            'pricing',
            'price',
            'pricing slider',
            'slider',
            'ea pricing table',
            'pricing plan',
            'ea',
            'essential addons',
        ];
    }

    public function get_custom_help_url() {
		return 'https://essential-addons.com/docs/ea-pricing-slider/';
	}

    /**
	 * Register widget controls.
	 */
	protected function register_controls() {

        //General Controll
        $this->eael_pricing_slider_general_controls();

        //Slider Controll
        $this->eael_pricing_slider_controls();

        //Pricing Plan
        $this->eael_pricing_plan_controls();
        
        //Style Controls
        $this->eael_pricing_slider_style_controls();

	}

    /**
     * Pricing Slider General Controls
     */
    protected function eael_pricing_slider_general_controls() {
        $this->start_controls_section(
			'eael_pricing_slider_general_controls',
			[
				'label' => esc_html__( 'General', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

        $image_dir_url = EAEL_PRO_PLUGIN_URL . 'assets/admin/images/layout-previews/pricing-slider-';
        $this->add_control(
			'eael_pricing_slider_style',
			[
				'label'   => esc_html__( 'Select Style', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::CHOOSE,
				'default' => 'style_1',
                'options'     => [
					'style_1' => [
						'title' => esc_html__( 'Default', 'essential-addons-elementor' ),
						'image'  => $image_dir_url . 'preset-1.png',
					],
					'style_2' => [
						'title' => esc_html__( 'Preset 2', 'essential-addons-elementor' ),
						'image'  => $image_dir_url . 'preset-2.png',
					],
				],
                'label_block' => true,
                'toggle'      => false,
                'image_choose'=> true,
			]
		);

        $this->add_responsive_control(
            'eael_pricing_slider_columns',
            [
                'label'          => esc_html__('Columns', 'essential-addons-elementor'),
                'type'           => Controls_Manager::CHOOSE,
                'default'        => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options'        => [
                    '1' => [
                        'title' => '1',
                        'text'  => '1',
                    ],
                    '2' => [
                        'title' => '2',
                        'text'  => '2',
                    ],
                    '3' => [
                        'title' => '3',
                        'text'  => '3',
                    ],
                    '4' => [
                        'title' => '4',
                        'text'  => '4',
                    ],
                    '5' => [
                        'title' => '5',
                        'text'  => '5',
                    ],
                    '6' => [
                        'title' => '6',
                        'text'  => '6',
                    ],
                ],
                'prefix_class'       => 'elementor-grid%s-',
                'frontend_available' => true,
            ]
        );

        $this->add_control(
			'eael_pricing_slider_show_description',
			[
				'label'        => esc_html__( 'Show Description', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

        $this->add_control(
			'eael_pricing_slider_description',
			[
				'label'       => esc_html__( 'Description', 'essential-addons-elementor' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => esc_html__( 'Pro plans run on dedicated high-speed nodes with AES-256 encryption, RAM-only servers, and zero-log infrastructure — built for maximum privacy. Standard plans offer reliable, encrypted tunneling at an affordable rate, ideal for everyday secure browsing.', 'essential-addons-elementor' ),
                'condition'   => [
                    'eael_pricing_slider_show_description' => 'yes'
                ],
			]
		);

		$this->end_controls_section();
    }

    /**
     * Pricing Slider Controls
     */
    protected function eael_pricing_slider_controls() {
        $this->start_controls_section(
			'eael_pricing_slider_controls',
			[
				'label' => esc_html__( 'Slider Controls', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

        $repeater = new Repeater();
        $repeater->add_control(
			'eael_pricing_slider_title',
			[
				'label'     => esc_html__( 'Title', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( '1GB' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_slider_custom_id',
			[
				'label' => esc_html__( 'Unique Filter ID', 'essential-addons-elementor' ),
				'type'  => Controls_Manager::TEXT,
                'ai'    => [ 'active' => false ],
			]
		);

        $repeater->add_control(
			'eael_pricing_slider_custom_id_alert',
			[
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading'    => esc_html__( 'Unique Filter ID', 'essential-addons-elementor' ),
				'content'    => esc_html__( 'It\'ll function as an unique identifier for each slider you add in your plan. No two sliders can share the same Filter ID.', 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_slider_active_as_default',
			[
				'label'        => esc_html__( 'Active As Default', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'No', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

        $repeater->add_control(
			'eael_pricing_slider_active_as_default_alert',
			[
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading'    => esc_html__( 'Active As Default', 'essential-addons-elementor' ),
				'content'    => esc_html__( 'Only the last selected item will be Active as Default. Previous selections will be overridden.', 'essential-addons-elementor' ),
                'condition'  => [
                    'eael_pricing_slider_active_as_default' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_slider_tooltip_active',
			[
				'label'        => esc_html__( 'Active Tooltip', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);


        $repeater->add_control(
			'eael_pricing_slider_tooltip_text',
			[
				'label' => esc_html__( 'Tooltip', 'essential-addons-elementor' ),
				'type'  => Controls_Manager::TEXT,
                'condition'   => [
                    'eael_pricing_slider_tooltip_active' => 'yes',
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_title_list',
			[
				'label'   => esc_html__( 'Slider Lists', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [
					[
						'eael_pricing_slider_title' => esc_html__( 'All', 'essential-addons-elementor' ),
                        'eael_pricing_slider_custom_id' => 0,
					],
					[
						'eael_pricing_slider_title' => esc_html__( 'Starter', 'essential-addons-elementor' ),
                        'eael_pricing_slider_custom_id' => 1,
                        'eael_pricing_slider_tooltip_text' => esc_html__( 'Perfect for beginners', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_slider_title' => esc_html__( 'Basic', 'essential-addons-elementor' ),
                        'eael_pricing_slider_custom_id' => 2,
                        'eael_pricing_slider_active_as_default' => 'yes',
                        'eael_pricing_slider_tooltip_text' => esc_html__( 'Add more posts & campaigns', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_slider_title' => esc_html__( 'Growth', 'essential-addons-elementor' ),
                        'eael_pricing_slider_custom_id' => 3,
                        'eael_pricing_slider_tooltip_text' => esc_html__( 'Boost SEO & ads', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_slider_title' => esc_html__( 'Advanced', 'essential-addons-elementor' ),
                        'eael_pricing_slider_custom_id' => 4,
                        'eael_pricing_slider_tooltip_text' => esc_html__( 'Full-service marketing', 'essential-addons-elementor' ),
					],
				],
				'title_field' => '{{{ eael_pricing_slider_title }}}',
			]
		);

		$this->end_controls_section();
    }

    /**
     * Summary of eael_pricing_plan_controls
     * @return void
     */
    protected function eael_pricing_plan_controls() {
        $this->start_controls_section(
			'eael_pricing_plan_controls',
			[
				'label' => esc_html__( 'Pricing Plans', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

        $repeater = new Repeater();

        $repeater->add_control(
			'eael_pricing_panel_control_id',
			[
				'label'     => esc_html__( 'Filter ID', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'ai'        => [ 'active' => false ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_control_id_alert',
			[
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading'    => esc_html__( 'Filter ID', 'essential-addons-elementor' ),
				'content'    => esc_html__( 'Specify the Unique Filter ID of the slider where this list should be displayed. For multiple sliders, enter each Unique Filter ID separated by a comma (e.g. 1,2, pen)', 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_title',
			[
				'label'     => esc_html__( 'Title', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( 'Newcomer' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_show_subtitle',
			[
				'label'        => esc_html__( 'Show Badge', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_subtitle',
			[
				'label'     => esc_html__( 'Badge Title', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( 'Pro' , 'essential-addons-elementor' ),
                'condition' => [
                    'eael_pricing_panel_show_subtitle' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_show_badge_icon',
			[
				'label'        => esc_html__( 'Show Icon', 'essential-addons-elementor' ),
                'separator'    => 'before',
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_badge_icon',
			[
				'label'     => esc_html__( 'Icon', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-cubes',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'eael_pricing_panel_show_badge_icon' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_badge_icon_alert',
			[
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading'    => esc_html__( 'Icon', 'essential-addons-elementor' ),
				'content'    => esc_html__( 'This icon show only for Style 2', 'essential-addons-elementor' ),
                'condition' => [
                    'eael_pricing_panel_show_badge_icon' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_status',
			[
				'label'     => esc_html__( 'Status', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_status_show',
			[
				'label'        => esc_html__( 'Show Status', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_status_title',
			[
				'label'       => esc_html__( 'Status Title', 'essential-addons-elementor' ),
				'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__( '1 Website' , 'essential-addons-elementor' ),
                'ai'          => [ 'active' => false ],
                'condition'   => [
                    'eael_pricing_panel_status_show' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_tooptip',
			[
				'label'        => esc_html__( 'Tooltip', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
                'condition'   => [
                    'eael_pricing_panel_status_show' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_tooptip_text',
			[
				'label'         => esc_html__( 'Tooltip Text', 'essential-addons-elementor' ),
				'type'          => Controls_Manager::TEXTAREA,
				'rows'          => 5,
				'default'       => esc_html__( 'Default description', 'essential-addons-elementor' ),
                'ai'            => [ 'active' => false ],
                'condition'     => [
                    'eael_pricing_panel_tooptip'     => 'yes',
                    'eael_pricing_panel_status_show' => 'yes',
                ],
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_feature_heading',
			[
				'label'     => esc_html__( 'Feature List', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $feature_limit = 20;

        $repeater->add_control(
			'eael_pricing_panel_feature_list_number',
			[
				'label'   => esc_html__( 'Number of Features', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [
					'px' => [
						'min' 	=> 1,
						'max' 	=> $feature_limit,
						'step'	=> 1,
					],
				],
				'default' => [
					'size' => 4,
				],
			]
		);

		for ( $i = 1; $i <= $feature_limit; $i++ ) {
			$repeater->add_control(
				'eael_pricing_panel_feature_text_' . $i,
				[
					'label'     => sprintf( esc_html__( 'Feature %d', 'essential-addons-elementor' ), $i ),
					'type'      => Controls_Manager::TEXTAREA,
					'default'   => esc_html__( 'Feature Text ' . $i , 'essential-addons-elementor' ),
                    'separator' => 'before',
					'condition' => [
						'eael_pricing_panel_feature_list_number[size]!' => range( 0, $i - 1 ),
					],
				]
			);

            $repeater->add_control(
			'eael_pricing_panel_feature_text_icon_' . $i,
			[
                    'label'     => esc_html__( 'Icon', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::ICONS,
                    'default'   => [
                        'value'   => 'far fa-check-circle',
                        'library' => 'fa-solid',
                    ],
                    'condition' => [
						'eael_pricing_panel_feature_list_number[size]!' => range( 0, $i - 1 ),
					],
                ]
            );

            $repeater->add_control(
			'eael_pricing_panel_feature_tooltip_' . $i,
			[
                    'label'        => esc_html__( 'Tooltip', 'essential-addons-elementor' ),
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
                    'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
                    'return_value' => 'yes',
                    'condition' => [
                        'eael_pricing_panel_feature_list_number[size]!' => range( 0, $i - 1 ),
                    ],
                ]
            );

            $repeater->add_control(
			'eael_pricing_panel_feature_tooltip_text_' . $i,
			[
                    'label'   => esc_html__( 'Tooltip Text', 'essential-addons-elementor' ),
                    'type'    => Controls_Manager::TEXTAREA,
                    'rows'    => 5,
                    'default' => esc_html__( 'Default description', 'essential-addons-elementor' ),
                    'ai'      => [ 'active' => false ],
                    'condition' => [
                        'eael_pricing_panel_feature_tooltip_' . $i => 'yes',
                        'eael_pricing_panel_feature_list_number[size]!' => range( 0, $i - 1 ),
                    ],
                ]
            );
		}

        $repeater->add_control(
			'eael_pricing_panel_price',
			[
				'label'     => esc_html__( 'Price', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_amount',
			[
				'label'     => esc_html__( 'Amount', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( '12' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_currency',
			[
				'label'     => esc_html__( 'Currency', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( '$' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_period',
			[
				'label'     => esc_html__( 'Period (per)', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( '/mo' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_sale_price_on',
			[
				'label'        => esc_html__( 'Sale Price', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Off', 'essential-addons-elementor' ),
				'return_value' => 'yes',
                'separator'    => 'before',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_sale_price_amount',
			[
				'label'     => esc_html__( 'Original Price', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( '4.99' , 'essential-addons-elementor' ),
                'condition' => [
                    'eael_pricing_panel_sale_price_on' => 'yes'
                ]
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_button',
			[
				'label'     => esc_html__( 'Button', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_button_text',
			[
				'label'     => esc_html__( 'Button Text', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__( 'Buy Now' , 'essential-addons-elementor' ),
			]
		);

        $repeater->add_control(
			'eael_pricing_panel_price_button_link',
			[
				'label'   => esc_html__( 'Link', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::URL,
				'options' => [ 'url', 'is_external', 'nofollow' ],
				'default' => [
					'url'         => '#',
					'is_external' => true,
					'nofollow'    =>  true,
				],
				'label_block' => true,
			]
		);

        $this->add_control(
			'eael_pricing_panels_list',
			[
				'label'   => esc_html__( 'Pricing Panel Lists', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [
					[
						'eael_pricing_panel_title' => esc_html__( 'MICRO START', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '1',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '3 Social Media Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Start simple: Basic setup for beginners', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Basic Profile Optimization', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( '1 Platform Management', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Monthly Insights', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Email Support', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '29', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'STARTER PLUS', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '1',
                        'eael_pricing_panel_show_subtitle' => 'yes',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( ' 7 Social Media Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Grow steadily: More posts + basic campaigns', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Basic SEO + Hashtag Strategy', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( '1 Campaign + Boosting', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Bi-weekly Report', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Email Support', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '59', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'BASIC PLUS', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '2',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '12 Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Boost visibility: SEO + structured content', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'SEO Optimization + Keywords', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( '2 Campaigns', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Content Calendar + Captions', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Priority Email Support', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '99', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'SMART MARKETING', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '2',
                        'eael_pricing_panel_show_subtitle' => 'yes',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '18 Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Smarter growth: Ads + insights', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Ads Optimization', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( 'Competitor Tracking', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Bi-weekly Reports', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Strategy Suggestions', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '140', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'GROWTH PLUS', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '3',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '25 Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Scale faster: SEO + ads + optimization', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Advanced SEO + Blog Suggestions', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( '3 Campaigns + Retargeting', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Weekly Reports', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Landing Page Optimization', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '210', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'SCALE', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '3',
                        'eael_pricing_panel_show_subtitle' => 'yes',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '35 Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Expand big: Funnels + multi-channel ads', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Multi-Channel Ads', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( 'Conversion Tracking', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Funnel Optimization', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Weekly Strategy Calls', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '280', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'ADVANCED PLUS', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '4',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '45 Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Full control: Automation + expert support', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Full Funnel Strategy', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( 'Automation Setup', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Dedicated Account Manager', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'A/B Testing', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '420', 'essential-addons-elementor' ),
					],
					[
						'eael_pricing_panel_title' => esc_html__( 'BUSINESS ELITE', 'essential-addons-elementor' ),
                        'eael_pricing_panel_control_id' => '4',
                        'eael_pricing_panel_show_subtitle' => 'yes',
                        'eael_pricing_panel_status_show' => 'yes',
						'eael_pricing_panel_status_title' => esc_html__( '50+ Posts', 'essential-addons-elementor' ),
                        'eael_pricing_panel_tooptip' => 'yes',
						'eael_pricing_panel_tooptip_text' => esc_html__( 'Max performance: Analytics + priority help', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_1' => esc_html__( 'Multi-Platform Management', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_2' => esc_html__( 'Advanced Analytics Dashboard', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_3' => esc_html__( 'Weekly Strategy + Reporting', 'essential-addons-elementor' ),
						'eael_pricing_panel_feature_text_4' => esc_html__( 'Priority Support', 'essential-addons-elementor' ),
						'eael_pricing_panel_price_amount' => esc_html__( '500', 'essential-addons-elementor' ),
					],
				],
				'title_field' => '{{{ eael_pricing_panel_title }}}',
			]
		);

		$this->end_controls_section();
    }

    /**
     * Summary of eael_pricing_slider_bg_color
     * @param $id: Uniqe control id
     * @param $selectors: CSS selector name
     */
    public function eael_pricing_slider_bg_color( $id, $selectors ) {
        $this->add_responsive_control(
			$id,
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} '.$selectors => 'background-color: {{VALUE}}',
				],
			]
		);
    }

    public function eael_pricing_slider_text_color( $id, $selectors ) {
        $this->add_control(
			$id,
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} '.$selectors => 'color: {{VALUE}}',
				],
			]
		);
    }

    /**
     * Summary of Pricing Header Controllers
     * @return void
     */
    protected function eael_pricing_slider_pricing_header() {
        $this->start_controls_section(
			'eael_pricing_slider_pricing_header_section',
			[
				'label' => esc_html__( 'Header', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_header_icon',
			[
				'label'     => esc_html__( 'Icon', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_header_icon_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon svg' => 'fill: {{VALUE}}',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_header_icon_size',
			[
				'label'      => esc_html__( 'Size', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
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
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'eael_pricing_slider_plan_header_icon_size_border',
				'selector'  => '{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon',
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_header_icon_size_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_header_icon_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_header_icon_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon i,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge-icon svg' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ]
			]
		);


        $this->add_control(
			'eael_pricing_slider_plan_header',
			[
				'label'     => esc_html__( 'Title', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        // Text Color
        $this->eael_pricing_slider_text_color( 'eael_pricing_slider_plan_header_text_color', '.eael-pricing-plan .eael-pricing-plan-header .title' );

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_header_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .eael-pricing-plan-header .title',
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_subtitle',
			[
				'label'     => esc_html__( 'Badge', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_subtitle_bg_color',
				'types'    => [ 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title,
                                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge',
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_subtitle_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge',
			]
		);

        // Text Color
        $this->eael_pricing_slider_text_color( 'eael_pricing_slider_plan_subtitle_text_color', '.eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title, .eael-pricing-slider-style-2 .eael-pricing-plan-badge' );

        $this->add_responsive_control(
			'eael_pricing_slider_plan_subtitle_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_subtitle_border',
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title, 
                                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_subtitle_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header .sub-title,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_subtitle_gap',
			[
				'label'      => esc_html__( 'Gap', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan .eael-pricing-plan-header' => 'gap: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_1'
                ]
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_status',
			[
				'label'     => esc_html__( 'Status', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        // Text Color
        $this->eael_pricing_slider_text_color( 'eael_pricing_slider_plan_status_text_color', '.eael-pricing-plan .eael-pricing-plan-status .status-title' );

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_status_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .eael-pricing-plan-status .status-title',
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_status_color',
			[
				'label'     => esc_html__( 'Icon Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-status .info-icon svg path,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-status .info-icon svg path' => 'fill: {{VALUE}}',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_tooltip',
			[
				'label'     => esc_html__( 'Tooltip', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'texteael_pricing_slider_plan_tooltip_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip::after' => 'border-color: rgba(0,0,0,0) {{VALUE}} rgba(0,0,0,0) rgba(0,0,0,0)',
                    '{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip::after' => 'border-right-color: {{VALUE}}',
				],
			]
		);

        // Text Color
        $this->eael_pricing_slider_text_color( 'eael_pricing_slider_plan_tooltip_text_color', '.eael-pricing-plan-status .eael-pricing-plan-status-tootlip span' );

        $this->add_responsive_control(
			'eael_pricing_slider_plan_tooltip_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_tooltip_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip',
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_tooltip_width',
			[
				'label'      => esc_html__( 'Width', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_tooltip_border',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_tooltip_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_tooltip_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-status .eael-pricing-plan-status-tootlip',
			]
		);

        $this->end_controls_section();
    }

    /**
     * Summary of Pricing Features Controllers
     * @return void
     */
    protected function eael_pricing_slider_pricing_features() {
        $this->start_controls_section(
			'eael_pricing_slider_feature',
			[
				'label' => esc_html__( 'Features', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_feature_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .pricing-content .feature-value',
			]
		);

        //Text Color
        $this->eael_pricing_slider_text_color('eael_pricing_slider_feature_text_color', '.eael-pricing-plan .pricing-content .feature-value');

        $this->add_responsive_control(
			'eael_pricing_slider_feature_text_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content .feature-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_feature_text_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content .feature-value' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_icon',
			[
				'label'     => esc_html__( 'Icon', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_icon_size',
			[
				'label'      => esc_html__( 'Size', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_icon_gap',
			[
				'label'      => esc_html__( 'Gap', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_icon_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon svg' => 'fill: {{VALUE}}',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_feature_icon_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_feature_icon_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .feature-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_plan_tooltip',
			[
				'label'     => esc_html__( 'Tooltip', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_plan_tooltip_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip::after' => 'border-color:rgba(0,0,0,0) {{VALUE}} rgba(0,0,0,0) rgba(0,0,0,0)',
				],
			]
		);

        // Text Color
        $this->eael_pricing_slider_text_color( 'eael_pricing_slider_feature_plan_tooltip_text_color', '.eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip span' );

        $this->add_responsive_control(
			'eael_pricing_slider_feature_plan_tooltip_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_feature_plan_tooltip_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip span',
			]
		);

        $this->add_control(
			'eael_pricing_slider_feature_plan_tooltip_width',
			[
				'label'      => esc_html__( 'Width', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_feature_plan_tooltip_border',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_feature_plan_tooltip_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_feature_plan_tooltip_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-plan .pricing-content li .eael-pricing-plan-status-tootlip',
			]
		);

        $this->end_controls_section();
    }

    /**
     * Summary of Pricing
     */
    protected function eael_pricing_slider_pricing() {
        $this->start_controls_section(
			'eael_pricing_slider_price_section',
			[
				'label' => esc_html__( 'Price', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'eael_pricing_slider_price_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .highlight-pirce,
                    {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .highlight-pirce,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-amount,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-currency' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_price_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .highlight-pirce,
                {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .highlight-pirce,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-amount,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-currency',
			]
		);

        $this->add_control(
			'eael_pricing_slider_price_currency_size',
			[
				'label'      => esc_html__( 'Currency Size', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .highlight-pirce .pirce-currency,
                    {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .highlight-pirce .pirce-currency,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-currency' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_price_currency_color',
			[
				'label'     => esc_html__( 'Currency Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .highlight-pirce .pirce-currency,
                    {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .highlight-pirce .pirce-currency,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-currency' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_sale_price',
			[
				'label'     => esc_html__( 'Sale Price', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'eael_pricing_slider_sale_price_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
                    '{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .sale-price-block .sale-price,
					{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .sale-price-block .sale-pirce-period,
					{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .original-price,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .original-price .original-price-currency' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_sale_price_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .sale-price-block .sale-price,
                {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .sale-price-block .sale-pirce-period,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .original-price,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .original-price .original-price-currency',
			]
		);

        $this->add_control(
			'eael_pricing_slider_price_period',
			[
				'label'     => esc_html__( 'Period', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'eael_pricing_slider_price_period_text_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .pirce-period,
					{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .pirce-period,
                    {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-period' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_price_period_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-plan-signup .pricinging .pirce-period,
                {{WRAPPER}} .eael-pricing-slider-style-1 .eael-pricing-sale-price .original-sale-price .pirce-period,
                {{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-plan-price .price-period',
			]
		);

        $this->end_controls_section();
    }

    /**
     * Summary of Pricing Footer Controllers
     * @return void
     */
    protected function eael_pricing_slider_pricing_footer() {
        $this->start_controls_section(
			'eael_pricing_slider_footer_section',
			[
				'label' => esc_html__( 'Footer', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'eael_pricing_slider_button_align',
			[
				'label'   => esc_html__( 'Alignment', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'flex-start',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan .eael-pricing-plan-signup' => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .eael-pricing-plan .eael-pricing-sale-price' => 'text-align: {{VALUE}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_button_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_button_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_button_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->start_controls_tabs(
			'eael_pricing_slider_button_style_tabs'
		);

        //Normal Section
        $this->start_controls_tab(
			'eael_pricing_slider_button_style_normal',
			[
				'label' => esc_html__( 'Normal', 'essential-addons-elementor' ),
			]
		);

        //Text Color
        $this->add_control(
			'eael_pricing_slider_button_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_button_bg_gradient_color',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn',
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_button_border',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn',
			]
		);

        $this->end_controls_tab();

        //Hover Section
        $this->start_controls_tab(
			'style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'essential-addons-elementor' ),
			]
		);

        //Text Color
        $this->add_control(
			'eael_pricing_slider_button_text_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-plan-signup .buy-btn:hover, {{WRAPPER}} .eael-pricing-sale-price .buy-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_button_bg_gradient_color_hover',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-plan-signup .buy-btn:hover, {{WRAPPER}} .eael-pricing-sale-price .buy-btn:hover',
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_button_border_hover',
				'selector' => '{{WRAPPER}} .eael-pricing-plan-signup .buy-btn:hover, {{WRAPPER}} .eael-pricing-sale-price .buy-btn:hover',
			]
		);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
			'eael_pricing_slider_button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plan-signup .buy-btn, {{WRAPPER}} .eael-pricing-sale-price .buy-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_section();
    }

    /**
     * Summary of Slider Controls
     */
    protected function eael_pricing_slider_style_slider_controls() {
        $this->start_controls_section(
			'eael_pricing_slider_slider_section',
			[
				'label' => esc_html__( 'Slider', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        // Background Color
        $this->eael_pricing_slider_bg_color( 'eael_pricing_slider_slider_bg', '.eael-pricing-slider .eael-pricing-slider-area' );

        $this->add_responsive_control(
			'eael_pricing_slider_slider_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider .eael-pricing-slider-area' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider .eael-pricing-slider-area' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider .eael-pricing-slider-area',
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-slider .eael-pricing-slider-area',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider .eael-pricing-slider-area' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar',
			[
				'label'     => esc_html__( 'Slider Bar', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_height',
			[
				'label'      => esc_html__( 'Height', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 24,
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-track' => 'height: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_1'
                ],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_height_style_2',
			[
				'label'      => esc_html__( 'Height', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-slider-curve path' => 'stroke-width: {{SIZE}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_top_style_2',
			[
				'label'      => esc_html__( 'Top', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min'  => -200,
						'max'  => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-style-2 .eael-pricing-slider-curve' => 'top: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_background',
				'types'    => [ 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-track',
                'condition' => [
                    'eael_pricing_slider_style' => 'style_1'
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_curve_heading',
			[
				'label'     => esc_html__( 'Curve Track Colors', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        // $this->add_control(
		// 	'eael_pricing_slider_curve_edge_color',
		// 	[
		// 		'label'     => esc_html__( 'Edge Color', 'essential-addons-elementor' ),
		// 		'type'      => Controls_Manager::COLOR,
		// 		'default'   => '#FFFFFF',
		// 		'selectors' => [
		// 			'{{WRAPPER}}' => '--eael-curve-edge-color: {{VALUE}}',
		// 		],
        //         'condition' => [
        //             'eael_pricing_slider_style' => 'style_2'
        //         ],
		// 	]
		// );

        $this->add_control(
			'eael_pricing_slider_curve_dark_color',
			[
				'label'     => esc_html__( 'Dark Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3B3B3B',
				'selectors' => [
					'{{WRAPPER}}' => '--eael-curve-dark-color: {{VALUE}}',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_curve_mid_color',
			[
				'label'     => esc_html__( 'Mid Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#828282',
				'selectors' => [
					'{{WRAPPER}}' => '--eael-curve-mid-color: {{VALUE}}',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_top',
			[
				'label'      => esc_html__( 'Top', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 50,
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-track' => 'top: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_1'
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_handel',
			[
				'label'     => esc_html__( 'Slider Handle', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_handel_background',
				'types'    => [ 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-handle',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_handel_size',
			[
				'label'      => esc_html__( 'Size', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-handle' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_handel_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-handle',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_handel_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'default'    => [
					'top'      => 50,
					'right'    => 50,
					'bottom'   => 50,
					'left'     => 50,
					'unit'     => '%',
					'isLinked' => true,
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-handle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_handel_icon',
			[
				'label'     => esc_html__( 'Handle Icon', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_handel_icon_width',
			[
				'label'      => esc_html__( 'Size', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
                'default' => [
					'unit' => 'px',
					'size' => 24,
				],
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-handle .eael-pricing-slider-drag-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_handel_icon_color',
			[
				'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-handle .eael-pricing-slider-drag-icon svg path' => 'fill: {{VALUE}}',
				],
                'condition' => [
                    'eael_pricing_slider_style' => 'style_2'
                ],
			]
		);

        $this->end_controls_section();
    }

    protected function eael_pricing_slider_style_controls() {
        // General Section
        $this->start_controls_section(
			'eael_pricing_slider_general_section',
			[
				'label' => esc_html__( 'General', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        // Background Color
        $this->eael_pricing_slider_bg_color( 'eael_pricing_slider_general_bg', '.eael-pricing-slider' );

        $this->add_responsive_control(
			'eael_pricing_slider_general_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_general_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_general_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider',
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_general_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-slider',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_general_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_general_desc',
			[
				'label'     => esc_html__( 'Description', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
			'eael_pricing_slider_general_desc_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-description, {{WRAPPER}} .eael-pricing-slider-description p' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_general_desc_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-description, {{WRAPPER}} .eael-pricing-slider-description p',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_general_desc_align',
			[
				'label'   => esc_html__( 'Alignment', 'essential-addons-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-description, {{WRAPPER}} .eael-pricing-slider-description p' => 'text-align: {{VALUE}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_general_desc_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-description, {{WRAPPER}} .eael-pricing-slider-description p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_general_desc_margin',
			[
				'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-description, {{WRAPPER}} .eael-pricing-slider-description p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_section();

        // Slider Section
        $this->eael_pricing_slider_style_slider_controls();

        // Slider Tooltip
        $this->start_controls_section(
			'eael_pricing_slider_tooltip_section',
			[
				'label' => esc_html__( 'Slider Tooltip', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_tooltip_bg',
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .eael-pricing-slider-tooltip::after' => 'border-color: {{VALUE}} rgba(0,0,0,0) rgba(0,0,0,0) rgba(0,0,0,0)',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_tooltip_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip',
			]
		);

        $this->add_control(
			'eael_pricing_slider_tooltip_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_tooltip_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_tooltip_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_tooltip_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_tooltip_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-area .eael-pricing-slider-tooltip',
			]
		);

        $this->end_controls_section();

        // Slider Label
        $this->start_controls_section(
			'eael_pricing_slider_lebel_section',
			[
				'label' => esc_html__( 'Slider Label', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->start_controls_tabs(
			'eael_pricing_slider_lebel_tabs'
		);

		$this->start_controls_tab(
			'eael_pricing_slider_lebel_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'essential-addons-elementor' ),
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_bg',
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label' => 'background-color: {{VALUE}}',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_label_connect_color',
			[
				'label'     => esc_html__( 'Connect Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-dot' => 'background-color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_label_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_label_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_active_border',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control .slider-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_tab();

        $this->start_controls_tab(
			'eael_pricing_slider_lebel_active_tab',
			[
				'label' => esc_html__( 'Active', 'essential-addons-elementor' ),
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_active_bg',
			[
				'label'     => esc_html__( 'Background Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-label' => 'background-color: {{VALUE}}',
				],
			]
		);

        $this->add_control(
			'eael_pricing_slider_slider_bar_label_connect_active_color',
			[
				'label'     => esc_html__( 'Connect Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-dot' => 'background-color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_label_active_typography',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-label',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_active_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-label' => 'color: {{VALUE}}',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_slider_bar_label_active_border',
				'selector' => '{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-label',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_slider_bar_label_active_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-slider-controls .slider-control.active .slider-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_tab();

		$this->end_controls_tabs();

        $this->end_controls_section();

        // Pricing Section
        $this->start_controls_section(
			'eael_pricing_slider_pricing_section',
			[
				'label' => esc_html__( 'Plans', 'essential-addons-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_bg',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan',
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_padding',
			[
				'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_border',
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan',
			]
		);

        $this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'eael_pricing_slider_plan_box_shadow',
				'selector' => '{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan',
			]
		);

        $this->add_control(
			'eael_pricing_slider_show_adjust_height',
			[
				'label'        => esc_html__( 'Adjust Height', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'essential-addons-elementor' ),
				'return_value' => 'yes',
			]
		);

        $this->add_control(
			'eael_pricing_slider_plan_adjust_height',
			[
				'label'      => esc_html__( 'Height', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
                'condition'   => [
                    'eael_pricing_slider_show_adjust_height' => 'yes',
                ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'eael_pricing_slider_plan_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'essential-addons-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .eael-pricing-plans-container .eael-pricing-plan' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_section();

        // Pricing Header
        $this->eael_pricing_slider_pricing_header();

        // Pricing Features
        $this->eael_pricing_slider_pricing_features();

        // Price Amount
        $this->eael_pricing_slider_pricing();
        
        // Pricing Footer
        $this->eael_pricing_slider_pricing_footer();
    }

    protected function eael_pricing_slider_settings( $settings ) {
        $pricing_slider_settings = [];
        $pricing_slider_settings['pricing_controls'] = !empty( $settings['eael_pricing_slider_title_list'] ) ? $settings['eael_pricing_slider_title_list'] : '';
        $pricing_slider_settings['pricing_panels'] = !empty( $settings['eael_pricing_panels_list'] ) ? $settings['eael_pricing_panels_list'] : '';

        return $pricing_slider_settings;
    }
    /**
     * Render widget output on the frontend
     * @return void
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $pricing_settings = $this->eael_pricing_slider_settings( $settings );
        $widget_id = $this->get_id();

        if ( 'style_1' === $settings['eael_pricing_slider_style'] ) {
        ?>
            <div class="eael-pricing-slider eael-pricing-slider-style-1" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <div class="eael-pricing-slider-wrapper">
                    <div class="eael-pricing-slider-inner-wrapper">
                        <div class="eael-pricing-slider-area">
                            <div class="eael-pricing-slider-container">
                                <div class="eael-pricing-slider-bar" id="eael-pricing-slider-bar-<?php echo esc_attr($widget_id); ?>">
                                    <div class="eael-pricing-slider-track"></div>
                                    <div class="eael-pricing-slider-fill" id="eael-pricing-slider-fill-<?php echo esc_attr($widget_id); ?>"></div>
                                    <div class="eael-pricing-slider-handle" id="eael-pricing-slider-handle-<?php echo esc_attr($widget_id); ?>">
                                        <div class="eael-pricing-slider-tooltip" id="eael-pricing-slider-tooltip-<?php echo esc_attr($widget_id); ?>"></div>
                                    </div>
                                </div>

                                <div class="slider-markers-container">
                                    <div class="eael-pricing-slider-controls" id="eael-pricing-slider-controls-<?php echo esc_attr($widget_id); ?>">
                                    <?php
                                    foreach ($pricing_settings['pricing_controls'] as $index => $item) {
                                        ?>
                                            <div
                                                class="slider-control"
                                                data-value="<?php echo esc_attr($item['eael_pricing_slider_custom_id']); ?>"
                                                data-active="<?php echo esc_attr($item['eael_pricing_slider_active_as_default']); ?>"
                                                data-tooltip-active="<?php echo esc_attr($item['eael_pricing_slider_tooltip_active'] ?? 'yes'); ?>"
                                                data-tooltip-text="<?php echo esc_attr($item['eael_pricing_slider_tooltip_text'] ?? ''); ?>"
                                            >
                                                <div class="slider-dot"></div>
                                                <p class="slider-label"><?php echo esc_html($item['eael_pricing_slider_title']); ?></p>
                                            </div>
                                            <?php
                                    }
                                    ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ('yes' === $settings['eael_pricing_slider_show_description'] && !empty($settings['eael_pricing_slider_description'])) {
                            ?>
                            <div class="eael-pricing-slider-description">
                                <?php echo wp_kses($settings['eael_pricing_slider_description'], Helper::eael_allowed_tags()); ?>
                            </div>
                            <?php
                        } ?>

                        <!-- Pricing Tables Container - All plans in HTML -->
                        <div class="eael-pricing-plans-container" id="plans-container-<?php echo esc_attr($widget_id); ?>">
                        <?php
                        foreach ($pricing_settings['pricing_panels'] as $index => $item) {
                            $feature_count = isset($item['eael_pricing_panel_feature_list_number']['size']) ? $item['eael_pricing_panel_feature_list_number']['size'] : 5;
                            $this->add_link_attributes('button_link_' . $index, $item['eael_pricing_panel_price_button_link']);
                            ?>
                        <div class="eael-pricing-plan general" data-filter="<?php echo esc_attr($item['eael_pricing_panel_control_id']); ?>">
                            <div class="eael-pricing-plan-header">
                                <h3 class="title"><?php echo esc_html($item['eael_pricing_panel_title']); ?></h3>
                                <?php if ('yes' === $item['eael_pricing_panel_show_subtitle']) { ?>
                                    <p class="sub-title"><?php echo esc_html($item['eael_pricing_panel_subtitle']); ?></p>
                                <?php } ?>
                            </div>

                            <?php if ('yes' === $item['eael_pricing_panel_status_show']) { ?>
                                <div class="eael-pricing-plan-status">
                                    <p class="status-title"><?php echo esc_html($item['eael_pricing_panel_status_title']); ?></p>
                                    <?php if ('yes' === $item['eael_pricing_panel_tooptip']) { ?>
                                        <span class="info-icon">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7 14C3.14008 14 0 10.8599 0 7C0 3.14008 3.14008 0 7 0C10.8599 0 14 3.14008 14 7C14 10.8599 10.8599 14 7 14ZM7 1.16667C3.7835 1.16667 1.16667 3.7835 1.16667 7C1.16667 10.2165 3.7835 12.8333 7 12.8333C10.2165 12.8333 12.8333 10.2165 12.8333 7C12.8333 3.7835 10.2165 1.16667 7 1.16667Z" fill="#7C7695"></path>
                                                <path d="M6.75 4.5C7.16421 4.5 7.5 4.16421 7.5 3.75C7.5 3.33579 7.16421 3 6.75 3C6.33579 3 6 3.33579 6 3.75C6 4.16421 6.33579 4.5 6.75 4.5Z" fill="#7C7695"></path>
                                                <path d="M7.6 10.1055H7.4166V6.44723C7.4166 6.20036 7.2374 6 7.0166 6H6.4C6.1792 6 6 6.20036 6 6.44723C6 6.6941 6.1792 6.89445 6.4 6.89445H6.6166V10.1055H6.4C6.1792 10.1055 6 10.3059 6 10.5528C6 10.7996 6.1792 11 6.4 11H7.6C7.8208 11 8 10.7996 8 10.5528C8 10.3059 7.8208 10.1055 7.6 10.1055Z" fill="#7C7695"></path>
                                            </svg>
                                            <div class="eael-pricing-plan-status-tootlip-wrapper">
                                                <div class="eael-pricing-plan-status-tootlip">
                                                    <span><?php echo esc_html($item['eael_pricing_panel_tooptip_text']); ?></span>
                                                </div>
                                            </div>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="pricing-content">
                                <ul>
                                    <?php for ($i = 1; $i <= $feature_count; $i++) {
                                        $feature_key = 'eael_pricing_panel_feature_text_' . $i;
                                        $feature_icon = 'eael_pricing_panel_feature_text_icon_' . $i;
                                        $feature_tooltip = 'eael_pricing_panel_feature_tooltip_' . $i;
                                        $feature_tooltip_text = 'eael_pricing_panel_feature_tooltip_text_' . $i;
                                        if (!empty($item[$feature_key]) || !empty($item[$feature_icon])) { ?>
                                        <li>
                                            <?php
                                            if (!empty($item[$feature_icon])) {
                                                ?>
                                                <span class="feature-icon">
                                                    <?php
                                                    Icons_Manager::render_icon($item[$feature_icon], ['aria-hidden' => 'true']);
                                                    ?>
                                                </span>
                                                <?php
                                            }
                                            ?>

                                            <?php
                                            $text_key = $this->get_repeater_setting_key('eael_pricing_panel_feature_text_' . $i, 'eael_pricing_panels_list', $index);
                                            $this->add_render_attribute($text_key, [
                                                'class' => 'feature-value',
                                            ]);

                                            $this->add_inline_editing_attributes($text_key, 'basic');
                                            ?>
                                            <span <?php $this->print_render_attribute_string($text_key); ?>><?php echo wp_kses($item[$feature_key], Helper::eael_allowed_tags()); ?></span>

                                            <?php if ('yes' === $item[$feature_tooltip]) { ?>
                                                <span class="info-icon">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M7 14C3.14008 14 0 10.8599 0 7C0 3.14008 3.14008 0 7 0C10.8599 0 14 3.14008 14 7C14 10.8599 10.8599 14 7 14ZM7 1.16667C3.7835 1.16667 1.16667 3.7835 1.16667 7C1.16667 10.2165 3.7835 12.8333 7 12.8333C10.2165 12.8333 12.8333 10.2165 12.8333 7C12.8333 3.7835 10.2165 1.16667 7 1.16667Z" fill="#7C7695"></path>
                                                        <path d="M6.75 4.5C7.16421 4.5 7.5 4.16421 7.5 3.75C7.5 3.33579 7.16421 3 6.75 3C6.33579 3 6 3.33579 6 3.75C6 4.16421 6.33579 4.5 6.75 4.5Z" fill="#7C7695"></path>
                                                        <path d="M7.6 10.1055H7.4166V6.44723C7.4166 6.20036 7.2374 6 7.0166 6H6.4C6.1792 6 6 6.20036 6 6.44723C6 6.6941 6.1792 6.89445 6.4 6.89445H6.6166V10.1055H6.4C6.1792 10.1055 6 10.3059 6 10.5528C6 10.7996 6.1792 11 6.4 11H7.6C7.8208 11 8 10.7996 8 10.5528C8 10.3059 7.8208 10.1055 7.6 10.1055Z" fill="#7C7695"></path>
                                                    </svg>
                                                    <div class="eael-pricing-plan-status-tootlip-wrapper">
                                                        <div class="eael-pricing-plan-status-tootlip">
                                                            <span><?php echo wp_kses($item[$feature_tooltip_text], Helper::eael_allowed_tags()); ?></span>
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php } ?>
                                        </li>
                                        <?php }
                                    } ?>
                                </ul>
                            </div>

                            <?php if ( 'yes' === $item['eael_pricing_panel_sale_price_on'] ) {
                                ?>
                                <div class="eael-pricing-sale-price">
                                    <div class="original-sale-price">
                                        <div class="pricinging">
                                            <span class="highlight-pirce">
                                                <span class="pirce-currency">
                                                    <?php echo esc_html($item['eael_pricing_panel_price_currency']); ?>
                                                </span>
                                                <span class="pirce-amount">
                                                    <?php echo esc_html($item['eael_pricing_panel_price_amount']); ?>
                                                </span>
                                            </span>
                                            <span class="pirce-period">
                                                <?php echo esc_html($item['eael_pricing_panel_price_period']); ?>
                                            </span>
                                        </div>
                                        <div class="sale-price-block">
                                            <div class="sale-price">
                                                <span class="sale-price-currency"><?php echo esc_html($item['eael_pricing_panel_price_currency']); ?></span>
                                                <?php echo esc_html( $item['eael_pricing_panel_sale_price_amount'] ); ?>
                                            </div>
                                            <span class="sale-pirce-period">
                                                <?php echo esc_html($item['eael_pricing_panel_price_period']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a <?php echo $this->get_render_attribute_string('button_link_' . $index); ?> class="buy-btn">
                                        <?php echo esc_html($item['eael_pricing_panel_price_button_text']); ?>
                                    </a>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="eael-pricing-plan-signup">
                                    <a <?php echo $this->get_render_attribute_string('button_link_' . $index); ?> class="buy-btn">
                                        <?php echo esc_html($item['eael_pricing_panel_price_button_text']); ?>
                                    </a>
                                    <span class="pricinging">
                                        <span class="highlight-pirce">
                                            <span class="pirce-currency">
                                                <?php echo esc_html($item['eael_pricing_panel_price_currency']); ?>
                                            </span>
                                            <span class="pirce-amount">
                                                <?php echo esc_html($item['eael_pricing_panel_price_amount']); ?>
                                            </span>
                                        </span>
                                        <span class="pirce-period">
                                            <?php echo esc_html($item['eael_pricing_panel_price_period']); ?>
                                        </span>
                                    </span>
                                </div>
                                <?php
                            } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            </div>
        <?php
        }
        elseif ( 'style_2' === $settings['eael_pricing_slider_style'] ) {
        ?>
            <div class="eael-pricing-slider eael-pricing-slider-style-2" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                <div class="eael-pricing-slider-wrapper">
                    <div class="eael-pricing-slider-inner-wrapper">
                        <div class="eael-pricing-slider-area">
                            <div class="eael-pricing-slider-container">
                                <div class="eael-pricing-slider-bar" id="eael-pricing-slider-bar-<?php echo esc_attr($widget_id); ?>">
                                    <div class="eael-pricing-slider-track"></div>
                                    <div class="eael-pricing-slider-fill" id="eael-pricing-slider-fill-<?php echo esc_attr($widget_id); ?>"></div>

                                    <!-- SVG Curve Track -->
                                    <svg class="eael-pricing-slider-curve" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="eael-slider-gradient-<?php echo esc_attr($widget_id); ?>" x1="3" y1="27.5" x2="1243" y2="27.5" gradientUnits="userSpaceOnUse">
                                                <stop style="stop-color: var(--eael-curve-edge-color, #ffffff00)"></stop>
                                                <stop offset="0.173486" style="stop-color: var(--eael-curve-dark-color, #3B3B3B)"></stop>
                                                <stop offset="0.450625" style="stop-color: var(--eael-curve-mid-color, #828282)"></stop>
                                                <stop offset="0.526888" style="stop-color: var(--eael-curve-dark-color, #3B3B3B)"></stop>
                                                <stop offset="0.726888" style="stop-color: var(--eael-curve-mid-color, #828282)"></stop>
                                                <stop offset="0.82" style="stop-color: var(--eael-curve-edge-color, #ffffff00)"></stop>
                                            </linearGradient>
                                        </defs>
                                        <path class="eael-pricing-slider-curve-path" d="M 0 5 L 293.3984375 5 C 333.3984375 5, 368.3984375 45, 393.3984375 45 C 418.3984375 45, 453.3984375 5, 493.3984375 5 L 1040 5" stroke="url(#eael-slider-gradient-<?php echo esc_attr($widget_id); ?>)" stroke-width="4" fill="none" stroke-linecap="round" />
                                    </svg>

                                    <div class="eael-pricing-slider-handle" id="eael-pricing-slider-handle-<?php echo esc_attr($widget_id); ?>">
                                        <div class="eael-pricing-slider-tooltip" id="eael-pricing-slider-tooltip-<?php echo esc_attr($widget_id); ?>"></div>
                                        <span class="eael-pricing-slider-drag-icon">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.875 5.87645V18.1234C14.875 18.8757 15.7603 19.2763 16.3263 18.7821L23.3245 12.6586C23.4185 12.5765 23.4938 12.4753 23.5454 12.3616C23.597 12.248 23.6237 12.1247 23.6237 11.9999C23.6237 11.8751 23.597 11.7518 23.5454 11.6382C23.4938 11.5246 23.4185 11.4233 23.3245 11.3412L16.3263 5.21774C15.7603 4.72349 14.875 5.12501 14.875 5.87645Z" fill="black"/>
                                            <path d="M9.125 5.87645V18.1234C9.125 18.8757 8.23972 19.2763 7.67374 18.7821L0.675492 12.6586C0.581532 12.5765 0.506227 12.4753 0.454629 12.3616C0.403031 12.248 0.376335 12.1247 0.376335 11.9999C0.376335 11.8751 0.403031 11.7518 0.454629 11.6382C0.506227 11.5246 0.581532 11.4233 0.675492 11.3412L7.67374 5.21774C8.23972 4.72349 9.125 5.12501 9.125 5.87645Z" fill="black"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>

                                <div class="slider-markers-container">
                                    <div class="eael-pricing-slider-controls" id="eael-pricing-slider-controls-<?php echo esc_attr($widget_id); ?>">
                                    <?php
                                    foreach ($pricing_settings['pricing_controls'] as $index => $item) {
                                        ?>
                                            <div
                                                class="slider-control"
                                                data-value="<?php echo esc_attr($item['eael_pricing_slider_custom_id']); ?>"
                                                data-active="<?php echo esc_attr($item['eael_pricing_slider_active_as_default']); ?>"
                                                data-tooltip-active="<?php echo esc_attr($item['eael_pricing_slider_tooltip_active'] ?? 'yes'); ?>"
                                                data-tooltip-text="<?php echo esc_attr($item['eael_pricing_slider_tooltip_text'] ?? ''); ?>"
                                            >
                                                <div class="slider-dot"></div>
                                                <p class="slider-label"><?php echo esc_html($item['eael_pricing_slider_title']); ?></p>
                                            </div>
                                            <?php
                                    }
                                    ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ('yes' === $settings['eael_pricing_slider_show_description'] && !empty($settings['eael_pricing_slider_description'])) {
                            ?>
                            <div class="eael-pricing-slider-description">
                                <?php echo wp_kses($settings['eael_pricing_slider_description'], Helper::eael_allowed_tags()); ?>
                            </div>
                            <?php
                        } ?>

                        <!-- Pricing Tables Container -->
                        <div class="eael-pricing-plans-container" id="plans-container-<?php echo esc_attr($widget_id); ?>">
                        <?php
                        foreach ($pricing_settings['pricing_panels'] as $index => $item) {
                            $feature_count = isset($item['eael_pricing_panel_feature_list_number']['size']) ? $item['eael_pricing_panel_feature_list_number']['size'] : 5;
                            $this->add_link_attributes('button_link_' . $index, $item['eael_pricing_panel_price_button_link']);
                            $is_featured = ('yes' === $item['eael_pricing_panel_show_subtitle']);
                            $plan_classes = 'eael-pricing-plan';
                            if ($is_featured) {
                                $plan_classes .= ' featured';
                            }
                            ?>
                        <div class="<?php echo esc_attr($plan_classes); ?>" data-filter="<?php echo esc_attr($item['eael_pricing_panel_control_id']); ?>">
                            <div class="eael-pricing-plan-top-badge">
                                <?php if ( 'yes' === $item['eael_pricing_panel_show_badge_icon'] && !empty($item['eael_pricing_panel_badge_icon']) ) { ?>
                                    <span class="eael-pricing-plan-badge-icon">
                                        <?php Icons_Manager::render_icon($item['eael_pricing_panel_badge_icon'], ['aria-hidden' => 'true']); ?>
                                    </span>
                                <?php } if ( $is_featured ) { ?>
                                    <span class="eael-pricing-plan-badge"><?php echo esc_html($item['eael_pricing_panel_subtitle']); ?></span>
                                <?php } ?>
                            </div>

                            <div class="eael-pricing-plan-header">
                                <h3 class="title"><?php echo esc_html($item['eael_pricing_panel_title']); ?></h3>
                            </div>

                            <div class="eael-pricing-plan-price">
                                <div class="price-main">
                                    <span class="price-currency"><?php echo esc_html($item['eael_pricing_panel_price_currency']); ?></span>
                                    <span class="price-amount"><?php echo esc_html($item['eael_pricing_panel_price_amount']); ?></span>
                                </div>
                                <?php if ( 'yes' === $item['eael_pricing_panel_sale_price_on'] ) { ?>
                                    <div class="original-price">
                                        <span class="original-price-currency"><?php echo esc_html($item['eael_pricing_panel_price_currency']); ?></span>
                                        <?php echo esc_html( $item['eael_pricing_panel_sale_price_amount'] ); ?>
                                    </div>
                                <?php } ?>
                                <p class="price-period"><?php echo esc_html($item['eael_pricing_panel_price_period']); ?></p>
                            </div>

                            <?php if ('yes' === $item['eael_pricing_panel_status_show']) { ?>
                                <div class="eael-pricing-plan-status">
                                    <p class="status-title"><?php echo esc_html($item['eael_pricing_panel_status_title']); ?></p>
                                    <?php if ('yes' === $item['eael_pricing_panel_tooptip']) { ?>
                                        <span class="info-icon">
                                            <svg viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7 14C3.14008 14 0 10.8599 0 7C0 3.14008 3.14008 0 7 0C10.8599 0 14 3.14008 14 7C14 10.8599 10.8599 14 7 14ZM7 1.16667C3.7835 1.16667 1.16667 3.7835 1.16667 7C1.16667 10.2165 3.7835 12.8333 7 12.8333C10.2165 12.8333 12.8333 10.2165 12.8333 7C12.8333 3.7835 10.2165 1.16667 7 1.16667Z" fill="#7C7695"></path>
                                                <path d="M6.75 4.5C7.16421 4.5 7.5 4.16421 7.5 3.75C7.5 3.33579 7.16421 3 6.75 3C6.33579 3 6 3.33579 6 3.75C6 4.16421 6.33579 4.5 6.75 4.5Z" fill="#7C7695"></path>
                                                <path d="M7.6 10.1055H7.4166V6.44723C7.4166 6.20036 7.2374 6 7.0166 6H6.4C6.1792 6 6 6.20036 6 6.44723C6 6.6941 6.1792 6.89445 6.4 6.89445H6.6166V10.1055H6.4C6.1792 10.1055 6 10.3059 6 10.5528C6 10.7996 6.1792 11 6.4 11H7.6C7.8208 11 8 10.7996 8 10.5528C8 10.3059 7.8208 10.1055 7.6 10.1055Z" fill="#7C7695"></path>
                                            </svg>
                                            <div class="eael-pricing-plan-status-tootlip-wrapper">
                                                <div class="eael-pricing-plan-status-tootlip">
                                                    <span><?php echo esc_html($item['eael_pricing_panel_tooptip_text']); ?></span>
                                                </div>
                                            </div>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="pricing-content">
                                <ul>
                                    <?php for ($i = 1; $i <= $feature_count; $i++) {
                                        $feature_key = 'eael_pricing_panel_feature_text_' . $i;
                                        $feature_icon = 'eael_pricing_panel_feature_text_icon_' . $i;
                                        $feature_tooltip = 'eael_pricing_panel_feature_tooltip_' . $i;
                                        $feature_tooltip_text = 'eael_pricing_panel_feature_tooltip_text_' . $i;
                                        if (!empty($item[$feature_key]) || !empty($item[$feature_icon])) { ?>
                                        <li>
                                            <?php
                                            if (!empty($item[$feature_icon])) {
                                                ?>
                                                <span class="feature-icon">
                                                    <?php
                                                    Icons_Manager::render_icon($item[$feature_icon], ['aria-hidden' => 'true']);
                                                    ?>
                                                </span>
                                                <?php
                                            }
                                            ?>

                                            <?php
                                            $text_key = $this->get_repeater_setting_key('eael_pricing_panel_feature_text_' . $i, 'eael_pricing_panels_list', $index);
                                            $this->add_render_attribute($text_key, [
                                                'class' => 'feature-value',
                                            ]);

                                            $this->add_inline_editing_attributes($text_key, 'basic');
                                            ?>
                                            <span <?php $this->print_render_attribute_string($text_key); ?>><?php echo wp_kses($item[$feature_key], Helper::eael_allowed_tags()); ?></span>

                                            <?php if ('yes' === $item[$feature_tooltip]) { ?>
                                                <span class="info-icon">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M7 14C3.14008 14 0 10.8599 0 7C0 3.14008 3.14008 0 7 0C10.8599 0 14 3.14008 14 7C14 10.8599 10.8599 14 7 14ZM7 1.16667C3.7835 1.16667 1.16667 3.7835 1.16667 7C1.16667 10.2165 3.7835 12.8333 7 12.8333C10.2165 12.8333 12.8333 10.2165 12.8333 7C12.8333 3.7835 10.2165 1.16667 7 1.16667Z" fill="#7C7695"></path>
                                                        <path d="M6.75 4.5C7.16421 4.5 7.5 4.16421 7.5 3.75C7.5 3.33579 7.16421 3 6.75 3C6.33579 3 6 3.33579 6 3.75C6 4.16421 6.33579 4.5 6.75 4.5Z" fill="#7C7695"></path>
                                                        <path d="M7.6 10.1055H7.4166V6.44723C7.4166 6.20036 7.2374 6 7.0166 6H6.4C6.1792 6 6 6.20036 6 6.44723C6 6.6941 6.1792 6.89445 6.4 6.89445H6.6166V10.1055H6.4C6.1792 10.1055 6 10.3059 6 10.5528C6 10.7996 6.1792 11 6.4 11H7.6C7.8208 11 8 10.7996 8 10.5528C8 10.3059 7.8208 10.1055 7.6 10.1055Z" fill="#7C7695"></path>
                                                    </svg>
                                                    <div class="eael-pricing-plan-status-tootlip-wrapper">
                                                        <div class="eael-pricing-plan-status-tootlip">
                                                            <span><?php echo wp_kses($item[$feature_tooltip_text], Helper::eael_allowed_tags()); ?></span>
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php } ?>
                                        </li>
                                        <?php }
                                    } ?>
                                </ul>
                            </div>

                            <div class="eael-pricing-plan-signup">
                                <a <?php echo $this->get_render_attribute_string('button_link_' . $index); ?> class="buy-btn">
                                    <?php echo esc_html($item['eael_pricing_panel_price_button_text']); ?>
                                </a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            </div>
        <?php
        }
	}

}