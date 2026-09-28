<?php
namespace Essential_Addons_Elementor\Pro\Elements;

use \Elementor\Controls_Manager;
use Elementor\Repeater;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Widget_Base;
use Elementor\Group_Control_Image_Size;
use Essential_Addons_Elementor\Pro\Classes\Helper;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Price_Menu extends Widget_Base {
    
    public function get_name() {
        return 'eael-price-menu';
    }

    public function get_title() {
        return __( 'Price Menu', 'essential-addons-elementor' );
    }

    public function get_categories() {
        return [ 'essential-addons-elementor' ];
    }

    public function get_keywords()
    {
        return [
            'ea price menu',
            'ea pricing menu',
            'ea restaurant menu',
            'restaurant menu',
            'cafe menu',
            'food menu',
            'pricing menu',
            'ea',
            'essential addons'
        ];
    }

    protected function is_dynamic_content():bool {
        return false;
    }

    public function has_widget_inner_wrapper(): bool {
        return ! Helper::eael_e_optimized_markup();
    }

    public function get_custom_help_url()
    {
		return 'https://essential-addons.com/elementor/docs/price-menu/';
	}

    public function get_icon() {
        return 'eaicon-price-menu';
    }

    // ACF
    protected function eael_register_acf_controls() {
        $this->start_controls_section(
            'eael_section_price_menu_data_source',
            [
                'label' => esc_html__( 'Data Source', 'essential-addons-elementor' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'eael_pm_data_source',
            [
                'label'   => esc_html__( 'Source', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    'custom'       => esc_html__( 'Custom (Manual)', 'essential-addons-elementor' ),
                    'acf_repeater' => esc_html__( 'ACF Repeater Field', 'essential-addons-elementor' ),
                ],
            ]
        );

        $this->add_control(
            'eael_pm_acf_repeater_field',
            [
                'label'     => esc_html__( 'Repeater Field', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => '',
                'options'   => Helper::eael_get_acf_repeater_options(),
                'condition' => [ 'eael_pm_data_source' => 'acf_repeater' ],
            ]
        );

        Helper::eael_acf_notice_controls( $this, [ 'eael_pm_data_source' => 'acf_repeater' ] );

        $acf_sub_fields = Helper::eael_get_acf_repeater_sub_fields();
        foreach ( $acf_sub_fields as $repeater_name => $sub_field_options ) {
            $this->add_control(
                'eael_pm_acf_fields_heading_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Sub Fields', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_title_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Title', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => '',
                    'options'   => $sub_field_options,
                    'condition' => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_description_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Description', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => '',
                    'options'   => $sub_field_options,
                    'condition' => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_price_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Price', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => '',
                    'options'   => $sub_field_options,
                    'condition' => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_original_price_' . $repeater_name,
                [
                    'label'       => esc_html__( 'Original Price', 'essential-addons-elementor' ),
                    'description' => esc_html__( 'The item renders as discounted.', 'essential-addons-elementor' ),
                    'type'        => Controls_Manager::SELECT,
                    'default'     => '',
                    'options'     => $sub_field_options,
                    'condition'   => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_image_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Image', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => '',
                    'options'   => $sub_field_options,
                    'condition' => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $this->add_control(
                'eael_pm_acf_field_link_' . $repeater_name,
                [
                    'label'       => esc_html__( 'Link', 'essential-addons-elementor' ),
                    'description' => esc_html__('Select the sub-field that holds the URL for each menu item.', 'essential-addons-elementor' ),
                    'type'        => Controls_Manager::SELECT,
                    'default'     => '',
                    'options'     => $sub_field_options,
                    'condition'   => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            $extra_field_options = $sub_field_options;
            unset( $extra_field_options[''] );
            $this->add_control(
                'eael_pm_acf_repeater_extras_' . $repeater_name,
                [
                    'label'       => esc_html__('Additional Fields', 'essential-addons-elementor' ),
                    'description' => esc_html__( 'Select additional data to display below the description.', 'essential-addons-elementor' ),
                    'type'        => Controls_Manager::SELECT2,
                    'multiple'    => true,
                    'default'     => [],
                    'options'     => $extra_field_options,
                    'label_block' => true,
                    'condition'   => [
                        'eael_pm_data_source'        => 'acf_repeater',
                        'eael_pm_acf_repeater_field' => $repeater_name,
                    ],
                ]
            );

            // Variation Prices — only register if outer has at least one nested repeater sub-field.
            $nested_repeaters = $this->get_nested_repeaters_for_outer( $repeater_name );
            if ( ! empty( $nested_repeaters ) ) {
                $variation_repeater_options = [ '' => esc_html__( 'None', 'essential-addons-elementor' ) ];
                foreach ( $nested_repeaters as $nested_name => $nested_def ) {
                    $variation_repeater_options[ $nested_name ] = $nested_def['label'];
                }

                $this->add_control(
                    'eael_pm_acf_variation_heading_' . $repeater_name,
                    [
                        'label'     => esc_html__( 'Price Variations', 'essential-addons-elementor' ),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                        'condition' => [
                            'eael_pm_data_source'        => 'acf_repeater',
                            'eael_pm_acf_repeater_field' => $repeater_name,
                        ],
                    ]
                );

                $this->add_control(
                    'eael_pm_acf_field_variation_repeater_' . $repeater_name,
                    [
                        'label'       => esc_html__( 'Variations Repeater', 'essential-addons-elementor' ),
                        'description' => esc_html__( 'Pick the nested ACF Repeater sub-field that holds price variations.', 'essential-addons-elementor' ),
                        'type'        => Controls_Manager::SELECT,
                        'default'     => '',
                        'options'     => $variation_repeater_options,
                        'condition'   => [
                            'eael_pm_data_source'        => 'acf_repeater',
                            'eael_pm_acf_repeater_field' => $repeater_name,
                        ],
                    ]
                );

                foreach ( $nested_repeaters as $nested_name => $nested_def ) {
                    $this->add_control(
                        'eael_pm_acf_field_variation_label_' . $repeater_name . '_' . $nested_name,
                        [
                            'label'     => esc_html__( 'Variation Label', 'essential-addons-elementor' ),
                            'type'      => Controls_Manager::SELECT,
                            'default'   => '',
                            'options'   => $nested_def['options'],
                            'condition' => [
                                'eael_pm_data_source'                                    => 'acf_repeater',
                                'eael_pm_acf_repeater_field'                             => $repeater_name,
                                'eael_pm_acf_field_variation_repeater_' . $repeater_name => $nested_name,
                            ],
                        ]
                    );

                    $this->add_control(
                        'eael_pm_acf_field_variation_original_' . $repeater_name . '_' . $nested_name,
                        [
                            'label'       => esc_html__( 'Variation Original Price', 'essential-addons-elementor' ),
                            'description' => esc_html__( 'Optional — strike-through price.', 'essential-addons-elementor' ),
                            'type'        => Controls_Manager::SELECT,
                            'default'     => '',
                            'options'     => $nested_def['options'],
                            'condition'   => [
                                'eael_pm_data_source'                                    => 'acf_repeater',
                                'eael_pm_acf_repeater_field'                             => $repeater_name,
                                'eael_pm_acf_field_variation_repeater_' . $repeater_name => $nested_name,
                            ],
                        ]
                    );

                    $this->add_control(
                        'eael_pm_acf_field_variation_price_' . $repeater_name . '_' . $nested_name,
                        [
                            'label'     => esc_html__( 'Variation Price', 'essential-addons-elementor' ),
                            'type'      => Controls_Manager::SELECT,
                            'default'   => '',
                            'options'   => $nested_def['options'],
                            'condition' => [
                                'eael_pm_data_source'                                    => 'acf_repeater',
                                'eael_pm_acf_repeater_field'                             => $repeater_name,
                                'eael_pm_acf_field_variation_repeater_' . $repeater_name => $nested_name,
                            ],
                        ]
                    );
                }
            }
        }

        $this->end_controls_section();
    }

    // Additional Fields Style From ACF
    protected function eael_acf_extra_data_controls_style() {
        $this->start_controls_section(
            'eael_pm_section_extra_data_style',
            [
                'label'     => esc_html__( 'Additional Fields', 'essential-addons-elementor' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'eael_pm_data_source' => 'acf_repeater',
                ],
            ]
        );

        $this->add_control(
            'eael_pm_section_extra_data_text_color',
            [
                'label'     => esc_html__( 'Color', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .eael-restaurant-menu-extras .eael-restaurant-menu-extra-value' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'eael_pm_section_extra_data_text_typography',
                'selector' => '{{WRAPPER}} .eael-restaurant-menu-extras .eael-restaurant-menu-extra-value',
            ]
        );

        $this->add_control(
            'eael_pm_section_extra_data_text_margin',
            [
                'label'      => esc_html__( 'Margin', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-restaurant-menu-extras .eael-restaurant-menu-extra' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'eael_pm_section_extra_data_text_padding',
            [
                'label'      => esc_html__( 'Padding', 'essential-addons-elementor' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors'  => [
                    '{{WRAPPER}} .eael-restaurant-menu-extras .eael-restaurant-menu-extra' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_controls() {
        // ACF Data Source Controls
        $this->eael_register_acf_controls();

        /*-----------------------------------------------------------------------------------*/
        /*	Content Tab
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'section_price_menu',
            [
                'label'                 => __( 'Price Menu', 'essential-addons-elementor' ),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'menu_title',
            [
                'name'          => 'menu_title',
                'label'         => __( 'Title', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::TEXT,
                'dynamic'       => [
                    'active'    => true,
                ],
                'label_block'   => true,
                'placeholder'   => __( 'Title', 'essential-addons-elementor' ),
                'default'       => __( 'Title', 'essential-addons-elementor' ),
                'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
            'menu_description',
            [
                'name'          => 'menu_description',
                'label'         => __( 'Description', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::TEXTAREA,
                'dynamic'       => [
                    'active'    => true,
                ],
                'label_block'   => true,
                'placeholder'   => __( 'Description', 'essential-addons-elementor' ),
                'default'       => __( 'Description', 'essential-addons-elementor' ),
            ]
        );

        $repeater->add_control(
            'menu_price',
            [
                'name'          => 'menu_price',
                'label'         => __( 'Price', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::TEXT,
                'dynamic'       => [
                    'active'    => true,
                ],
                'default'       => '$49',
                'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
            'discount',
            [
                'name'          => 'discount',
                'label'         => __( 'Discount', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::SWITCHER,
                'default'       => 'no',
                'label_on'      => __( 'On', 'essential-addons-elementor' ),
                'label_off'     => __( 'Off', 'essential-addons-elementor' ),
                'return_value'  => 'yes',
            ]
        );

        $repeater->add_control(
            'original_price',
            [
                'name'          => 'original_price',
                'label'         => __( 'Original Price', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::TEXT,
                'dynamic'       => [
                    'active'    => true,
                ],
                'default'       => '$69',
                'condition'     => [
                    'discount' => 'yes',
                ],
                'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
			'variation_price',
			[
				'label'        => __( 'Variation Price', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'essential-addons-elementor' ),
				'label_off'    => __( 'Off', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

        $variation_repeater = new Repeater();

		$variation_repeater->add_control(
			'variation_label',
			[
				'label'       => __( 'Label', 'essential-addons-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Small', 'essential-addons-elementor' ),
				'ai' => [
					'active' => false,
				],
			]
		);

		$variation_repeater->add_control(
			'variation_original_price',
			[
				'label' => __( 'Original Price', 'essential-addons-elementor' ),
				'type'  => Controls_Manager::TEXT,
				'placeholder' => '$15',
				'ai' => [
					'active' => false,
				],
			]
		);

		$variation_repeater->add_control(
			'variation_price',
			[
				'label' => __( 'Price', 'essential-addons-elementor' ),
				'type'  => Controls_Manager::TEXT,
				'placeholder' => '$10',
				'ai' => [
					'active' => false,
				],
			]
		);

		$repeater->add_control(
			'variation_price_list',
			[
				'label'       => __( 'Price Variations', 'essential-addons-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $variation_repeater->get_controls(),
				'title_field' => '{{{ variation_label }}} {{{ variation_price }}}',
				'condition'   => [
					'variation_price' => 'yes',
				],
				'default'     => [
					[
						'variation_label' => 'Small',
						'variation_price' => '$10',
					],
				],
			]
		);

		$repeater->add_control(
			'variation_price_list_note',
			[
				'type'      => Controls_Manager::RAW_HTML,
				'raw'       => '<style>.elementor-control-variation_price_list .elementor-repeater-add { display: none !important; }</style>',
				'condition' => [
					'variation_price' => 'yes',
				],
			]
		);

        $repeater->add_control(
            'image_switch',
            [
                'name'          => 'image_switch',
                'label'         => __( 'Show Image', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::SWITCHER,
                'default'       => '',
                'label_on'      => __( 'On', 'essential-addons-elementor' ),
                'label_off'     => __( 'Off', 'essential-addons-elementor' ),
                'return_value'  => 'yes',
            ]
        );

        $repeater->add_control(
            'image',
            [
                'name'          => 'image',
                'label'         => __( 'Image', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::MEDIA,
                'dynamic'       => [
                    'active'    => true,
                ],
                'condition'     => [
                    'image_switch' => 'yes',
                ],
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $repeater->add_control(
            'link',
            [
                'name'          => 'link',
                'label'         => __( 'Link', 'essential-addons-elementor' ),
                'type'          => Controls_Manager::URL,
                'dynamic'       => [
                    'active'    => true,
                ],
                'placeholder'   => 'https://www.your-link.com',
            ]
        );



		$this->add_control(
			'menu_items',
			[
				'label'                 => '',
				'type'                  => Controls_Manager::REPEATER,
				'condition'             => [ 'eael_pm_data_source' => 'custom' ],
				'default'               => [
					[
						'menu_title' => __( 'Menu Item #1', 'essential-addons-elementor' ),
						'menu_price' => '$49',
					],
					[
						'menu_title' => __( 'Menu Item #2', 'essential-addons-elementor' ),
						'menu_price' => '$49',
					],
					[
						'menu_title' => __( 'Menu Item #3', 'essential-addons-elementor' ),
						'menu_price' => '$49',
					],
				],
				'fields'                => $repeater->get_controls(),
				'title_field'       => '{{{ menu_title }}}',
			]
		);
        
        $this->add_control(
          'menu_style',
          [
             'label'                => __( 'Menu Style', 'essential-addons-elementor' ),
             'type'                 => Controls_Manager::SELECT,
             'default'              => 'style-1',
             'options'              => [
                'style-eael'   => __( 'EA Style', 'essential-addons-elementor' ),
                'style-1'           => __( 'Style 1', 'essential-addons-elementor' ),
                'style-2'           => __( 'Style 2', 'essential-addons-elementor' ),
                'style-3'           => __( 'Style 3', 'essential-addons-elementor' ),
                'style-4'           => __( 'Style 4', 'essential-addons-elementor' ),
             ],
          ]
        );
        
        $this->add_control(
            'link_apply_on',
            [
                'label'   => __( 'Link apply on', 'essential-addons-elementor' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title'     => __( 'Title', 'essential-addons-elementor' ),
                    'image'     => __( 'Image', 'essential-addons-elementor' ),
                    'full_item' => __( 'Full Item', 'essential-addons-elementor' ),
                ],
            ]
        );

        $this->add_responsive_control(
			'menu_align',
			[
				'label'                 => __( 'Alignment', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::CHOOSE,
				'options'               => [
					'left'      => [
						'title' => __( 'Left', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'    => [
						'title' => __( 'Center', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'     => [
						'title' => __( 'Right', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify'   => [
						'title' => __( 'Justified', 'essential-addons-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'               => '',
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-style-4'   => 'text-align: {{VALUE}};',
				],
                'condition'             => [
                    'menu_style' => 'style-4',
                ],
			]
		);
        
        $this->add_control(
            'title_price_connector',
            [
                'label'                 => __( 'Title-Price Connector', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SWITCHER,
                'default'               => 'no',
                'label_on'              => __( 'Yes', 'essential-addons-elementor' ),
                'label_off'             => __( 'No', 'essential-addons-elementor' ),
                'return_value'          => 'yes',
                'condition'             => [
                    'menu_style' => 'style-1',
                ],
            ]
        );
        
        $this->add_control(
            'title_separator',
            [
                'label'                 => __( 'Title Separator', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SWITCHER,
                'default'               => 'no',
                'label_on'              => __( 'Yes', 'essential-addons-elementor' ),
                'label_off'             => __( 'No', 'essential-addons-elementor' ),
                'return_value'          => 'yes',
            ]
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*	Style Tab
        /*-----------------------------------------------------------------------------------*/

        /**
         * Style Tab: Menu Items Section
         */
        $this->start_controls_section(
            'section_items_style',
            [
                'label'                 => __( 'Menu Items', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'items_bg_color',
            [
                'label'                 => __( 'Background Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-item' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'items_spacing',
            [
                'label'                 => __( 'Items Spacing', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'range'                 => [
                    '%' => [
                        'min'   => 0,
                        'max'   => 100,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-item-wrap' => 'margin-bottom: calc(({{SIZE}}{{UNIT}})/2); padding-bottom: calc(({{SIZE}}{{UNIT}})/2)',
                ],
            ]
        );

		$this->add_responsive_control(
			'items_padding',
			[
				'label'                 => __( 'Padding', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'                  => 'items_border',
				'label'                 => __( 'Border', 'essential-addons-elementor' ),
				'placeholder'           => '1px',
				'default'               => '1px',
				'selector'              => '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-item',
			]
		);

		$this->add_control(
			'items_border_radius',
			[
				'label'                 => __( 'Border Radius', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'                  => 'pricing_table_shadow',
				'selector'              => '{{WRAPPER}} .eael-restaurant-menu-item',
				'separator'             => 'before',
			]
		);
        
        $this->end_controls_section();
        
        /**
         * Style Tab: Menu Items Section
         */
        $this->start_controls_section(
            'section_content_style',
            [
                'label'                 => __( 'Content', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
                'condition'             => [
                    'menu_style' => 'style-eael',
                ],
            ]
        );

		$this->add_responsive_control(
			'content_padding',
			[
				'label'                 => __( 'Padding', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition'             => [
                    'menu_style' => 'style-eael',
                ],
			]
		);
        
        $this->end_controls_section();

        /**
         * Style Tab: Title Section
         */
        $this->start_controls_section(
            'section_title_style',
            [
                'label'                 => __( 'Title', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'                 => __( 'Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-title' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'                  => 'title_typography',
                'label'                 => __( 'Typography', 'essential-addons-elementor' ),
                'global' => [
	                'default' => Global_Typography::TYPOGRAPHY_ACCENT
                ],
                'selector'              => '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-title, {{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-title a',
            ]
        );
        
        $this->add_responsive_control(
            'title_margin',
            [
                'label'                 => __( 'Margin Bottom', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'range'                 => [
                    '%' => [
                        'min'   => 0,
                        'max'   => 40,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-header' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->end_controls_section();

        $this->start_controls_section(
            'section_title_separator_style',
            [
                'label'                 => __( 'Title Separator', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
                'condition'             => [
                    'title_separator' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'divider_title_border_type',
            [
                'label'                 => __( 'Border Type', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SELECT,
                'default'               => 'dotted',
                'options'               => [
                    'none'      => __( 'None', 'essential-addons-elementor' ),
                    'solid'     => __( 'Solid', 'essential-addons-elementor' ),
                    'double'    => __( 'Double', 'essential-addons-elementor' ),
                    'dotted'    => __( 'Dotted', 'essential-addons-elementor' ),
                    'dashed'    => __( 'Dashed', 'essential-addons-elementor' ),
                ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-menu-divider' => 'border-bottom-style: {{VALUE}}',
                ],
                'condition'             => [
                    'title_separator' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'divider_title_border_weight',
            [
                'label'                 => __( 'Border Height', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'default'               => [
                    'size'      => 1,
                ],
                'range'                 => [
                    'px' => [
                        'min'   => 1,
                        'max'   => 20,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-menu-divider' => 'border-bottom-width: {{SIZE}}{{UNIT}}',
                ],
                'condition'             => [
                    'title_separator' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'divider_title_border_width',
            [
                'label'                 => __( 'Border Width', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'default'               => [
                    'size'      => 100,
                    'unit'      => '%',
                ],
                'range'                 => [
                    'px' => [
                        'min'   => 1,
                        'max'   => 20,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-menu-divider' => 'width: {{SIZE}}{{UNIT}}',
                ],
                'condition'             => [
                    'title_separator' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'divider_title_border_color',
            [
                'label'                 => __( 'Border Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-menu-divider' => 'border-bottom-color: {{VALUE}}',
                ],
                'condition'             => [
                    'title_separator' => 'yes',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'divider_title_spacing',
            [
                'label'                 => __( 'Margin Bottom', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'range'                 => [
                    '%' => [
                        'min'   => 0,
                        'max'   => 100,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-menu-divider' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();

        $this->start_controls_section(
            'section_price_style',
            [
                'label'                 => __( 'Price', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'price_badge_heading',
            [
                'label'                 => __( 'Price Badge', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::HEADING,
                'separator'             => 'before',
                'condition'             => [
                    'menu_style' => 'style-eael',
                ],
            ]
        );

        $this->add_control(
            'badge_text_color',
            [
                'label'                 => __( 'Text Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-style-eael .eael-restaurant-menu-price' => 'color: {{VALUE}}',
                ],
                'condition'             => [
                    'menu_style' => 'style-eael',
                ],
            ]
        );

        $this->add_control(
            'badge_bg_color',
            [
                'label'                 => __( 'Background Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-style-eael .eael-restaurant-menu-price:after' => 'border-right-color: {{VALUE}}',
                ],
                'condition'             => [
                    'menu_style' => 'style-eael',
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'                 => __( 'Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-price-discount' => 'color: {{VALUE}}',
                ],
                'condition'             => [
                    'menu_style!' => 'style-eael',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'                  => 'price_typography',
                'label'                 => __( 'Typography', 'essential-addons-elementor' ),
                'global' => [
	                'default' => Global_Typography::TYPOGRAPHY_ACCENT
                ],
                'selector'              => '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-price-discount',
            ]
        );
        
        $this->add_control(
            'original_price_heading',
            [
                'label'                 => __( 'Original Price', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::HEADING,
                'separator'             => 'before',
            ]
        );
        
        $this->add_control(
            'original_price_strike',
            [
                'label'                 => __( 'Strikethrough', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SWITCHER,
                'default'               => 'yes',
                'label_on'              => __( 'On', 'essential-addons-elementor' ),
                'label_off'             => __( 'Off', 'essential-addons-elementor' ),
                'return_value'          => 'yes',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-price-original' => 'text-decoration: line-through;',
                ],
            ]
        );

        $this->add_control(
            'original_price_color',
            [
                'label'                 => __( 'Original Price Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '#a3a3a3',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-price-original' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'                  => 'original_price_typography',
                'label'                 => __( 'Original Price Typography', 'essential-addons-elementor' ),
                'global' => [
	                'default' => Global_Typography::TYPOGRAPHY_ACCENT
                ],
                'selector'              => '{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-price-original',
            ]
        );

        $this->add_control(
			'variation_price_heading',
			[
				'label'     => __( 'Price Variation', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_control(
            'variation_price_original_price_strike',
            [
                'label'                 => __( 'Strikethrough', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SWITCHER,
                'default'               => 'yes',
                'label_on'              => __( 'On', 'essential-addons-elementor' ),
                'label_off'             => __( 'Off', 'essential-addons-elementor' ),
                'return_value'          => 'yes',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-original' => 'text-decoration: line-through;',
                ],
            ]
        );

		$this->add_control(
			'variation_label_color',
			[
				'label'     => __( 'Label Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'variation_label_typography',
				'label'    => __( 'Label Typography', 'essential-addons-elementor' ),
				'selector' => '{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-label',
			]
		);

		$this->add_control(
			'variation_price_color_style',
			[
				'label'     => __( 'Price Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-amount' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'variation_original_price_color_style',
			[
				'label'     => __( 'Original Price Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
                'default'   => '#A3A3A3',
				'selectors' => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-original' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'variation_original_price_typography',
				'label'    => __( 'Original Price Typography', 'essential-addons-elementor' ),
				'selector' => '{{WRAPPER}} .eael-restaurant-menu .eael-price-variation-original',
			]
		);

        $this->end_controls_section();

        $this->start_controls_section(
            'section_description_style',
            [
                'label'                 => __( 'Description', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label'                 => __( 'Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-description' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'                  => 'description_typography',
                'label'                 => __( 'Typography', 'essential-addons-elementor' ),
                'global' => [
	                'default' => Global_Typography::TYPOGRAPHY_ACCENT
                ],
                'selector'              => '{{WRAPPER}} .eael-restaurant-menu-description',
            ]
        );
        
        $this->add_responsive_control(
            'description_spacing',
            [
                'label'                 => __( 'Margin Bottom', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'range'                 => [
                    '%' => [
                        'min'   => 0,
                        'max'   => 100,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();

        /**
         * Style Tab: Image Section
         */
        $this->start_controls_section(
            'section_image_style',
            [
                'label'                 => __( 'Image', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
            ]
        );
		
        $this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'                  => 'image_size',
				'label'                 => __( 'Image Size', 'essential-addons-elementor' ),
				'default'               => 'thumbnail',
			]
		);

        $this->add_control(
            'image_bg_color',
            [
                'label'                 => __( 'Background Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-image img' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'image_width',
            [
                'label'                 => __( 'Width', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'range'                 => [
                    'px' => [
                        'min'   => 20,
                        'max'   => 300,
                        'step'  => 1,
                    ],
                    '%' => [
                        'min'   => 5,
                        'max'   => 50,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px', '%' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-image img' => 'width: {{SIZE}}{{UNIT}}',
                ],
            ]
        );

		$this->add_responsive_control(
			'image_margin',
			[
				'label'                 => __( 'Margin', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_padding',
			[
				'label'                 => __( 'Padding', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-image img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'                  => 'image_border',
				'label'                 => __( 'Border', 'essential-addons-elementor' ),
				'placeholder'           => '1px',
				'default'               => '1px',
				'selector'              => '{{WRAPPER}} .eael-restaurant-menu-image img',
			]
		);

		$this->add_control(
			'image_border_radius',
			[
				'label'                 => __( 'Border Radius', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::DIMENSIONS,
				'size_units'            => [ 'px', '%' ],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'image_vertical_position',
			[
				'label'                 => __( 'Vertical Position', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::CHOOSE,
				'label_block'           => false,
				'options'               => [
					'top'       => [
						'title' => __( 'Top', 'essential-addons-elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'middle'    => [
						'title' => __( 'Middle', 'essential-addons-elementor' ),
						'icon'  => 'eicon-v-align-middle',
					],
					'bottom'    => [
						'title' => __( 'Bottom', 'essential-addons-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu .eael-restaurant-menu-image' => 'align-self: {{VALUE}}',
				],
				'selectors_dictionary'  => [
					'top'      => 'flex-start',
                    'middle'   => 'center',
					'bottom'   => 'flex-end',
				],
			]
		);

        $this->end_controls_section();

        /**
         * Style Tab: Items Divider Section
         */
        $this->start_controls_section(
            'section_table_title_connector_style',
            [
                'label'                 => __( 'Title-Price Connector', 'essential-addons-elementor' ),
                'tab'                   => Controls_Manager::TAB_STYLE,
                'condition'             => [
                    'title_price_connector' => 'yes',
                    'menu_style' => 'style-1',
                ],
            ]
        );
        
        $this->add_control(
			'title_connector_vertical_align',
			[
				'label'                 => __( 'Vertical Alignment', 'essential-addons-elementor' ),
				'type'                  => Controls_Manager::CHOOSE,
				'default'               => 'middle',
				'options'               => [
					'top'          => [
						'title'    => __( 'Top', 'essential-addons-elementor' ),
						'icon'     => 'eicon-v-align-top',
					],
					'middle'       => [
						'title'    => __( 'Center', 'essential-addons-elementor' ),
						'icon'     => 'eicon-v-align-middle',
					],
					'bottom'       => [
						'title'    => __( 'Bottom', 'essential-addons-elementor' ),
						'icon'     => 'eicon-v-align-bottom',
					],
				],
				'selectors'             => [
					'{{WRAPPER}} .eael-restaurant-menu-style-1 .eael-price-title-connector'   => 'align-self: {{VALUE}};',
				],
				'selectors_dictionary'  => [
					'top'          => 'flex-start',
					'middle'       => 'center',
					'bottom'       => 'flex-end',
				],
                'condition'             => [
                    'title_price_connector' => 'yes',
                    'menu_style' => 'style-1',
                ],
			]
		);
        
        $this->add_control(
            'items_divider_style',
            [
                'label'                 => __( 'Style', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SELECT,
                'default'               => 'dashed',
                'options'              => [
                    'solid'     => __( 'Solid', 'essential-addons-elementor' ),
                    'dashed'    => __( 'Dashed', 'essential-addons-elementor' ),
                    'dotted'    => __( 'Dotted', 'essential-addons-elementor' ),
                    'double'    => __( 'Double', 'essential-addons-elementor' ),
                ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-style-1 .eael-price-title-connector' => 'border-bottom-style: {{VALUE}}',
                ],
                'condition'             => [
                    'title_price_connector' => 'yes',
                    'menu_style' => 'style-1',
                ],
            ]
        );

        $this->add_control(
            'items_divider_color',
            [
                'label'                 => __( 'Color', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::COLOR,
                'default'               => '#000',
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-style-1 .eael-price-title-connector' => 'border-bottom-color: {{VALUE}}',
                ],
                'condition'             => [
                    'title_price_connector' => 'yes',
                    'menu_style' => 'style-1',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'items_divider_weight',
            [
                'label'                 => __( 'Divider Weight', 'essential-addons-elementor' ),
                'type'                  => Controls_Manager::SLIDER,
                'default'               => [ 'size' => '1'],
                'range'                 => [
                    'px' => [
                        'min'   => 0,
                        'max'   => 30,
                        'step'  => 1,
                    ],
                ],
                'size_units'            => [ 'px' ],
                'selectors'             => [
                    '{{WRAPPER}} .eael-restaurant-menu-style-1 .eael-price-title-connector' => 'border-bottom-width: {{SIZE}}{{UNIT}}; bottom: calc((-{{SIZE}}{{UNIT}})/2)',
                ],
                'condition'             => [
                    'title_price_connector' => 'yes',
                    'menu_style' => 'style-1',
                ],
            ]
        );

        $this->end_controls_section();

        // Additional Fields Style From ACF
        $this->eael_acf_extra_data_controls_style();
    }

    /**
     * Discover ACF nested-repeater sub-fields inside a parent repeater.
     * Returns: [ nested_repeater_name => [ 'label' => string, 'options' => [sub_field_name => sub_field_label] ] ]
     */
    private function get_nested_repeaters_for_outer( $outer_repeater_name ) {
        if ( '' === $outer_repeater_name || ! function_exists( 'acf_get_field' ) ) {
            return [];
        }

        $outer = acf_get_field( $outer_repeater_name );
        if ( empty( $outer['sub_fields'] ) || ! is_array( $outer['sub_fields'] ) ) {
            return [];
        }

        $nested = [];
        foreach ( $outer['sub_fields'] as $sf ) {
            if ( 'repeater' !== ( $sf['type'] ?? '' ) || empty( $sf['sub_fields'] ) ) {
                continue;
            }
            $opts = [ '' => esc_html__( 'Select', 'essential-addons-elementor' ) ];
            foreach ( $sf['sub_fields'] as $nsf ) {
                if ( empty( $nsf['name'] ) ) {
                    continue;
                }
                $opts[ $nsf['name'] ] = $nsf['label'] ?? $nsf['name'];
            }
            $nested[ $sf['name'] ] = [
                'label'   => $sf['label'] ?? $sf['name'],
                'options' => $opts,
            ];
        }
        return $nested;
    }

    protected function eael_get_price_menu_items( $settings ) {
        if ( 'acf_repeater' !== ( $settings['eael_pm_data_source'] ?? 'custom' ) ) {
            return $settings['menu_items'];
        }

        $field_name         = sanitize_text_field( $settings['eael_pm_acf_repeater_field'] ?? '' );
        $title_key          = sanitize_text_field( $settings[ 'eael_pm_acf_field_title_'          . $field_name ] ?? '' );
        $description_key    = sanitize_text_field( $settings[ 'eael_pm_acf_field_description_'    . $field_name ] ?? '' );
        $price_key          = sanitize_text_field( $settings[ 'eael_pm_acf_field_price_'          . $field_name ] ?? '' );
        $original_price_key = sanitize_text_field( $settings[ 'eael_pm_acf_field_original_price_' . $field_name ] ?? '' );
        $image_key          = sanitize_text_field( $settings[ 'eael_pm_acf_field_image_'          . $field_name ] ?? '' );
        $link_key           = sanitize_text_field( $settings[ 'eael_pm_acf_field_link_'           . $field_name ] ?? '' );
        $extra_keys         = (array) ( $settings[ 'eael_pm_acf_repeater_extras_' . $field_name ] ?? [] );
        $variation_repeater_key = sanitize_text_field( $settings[ 'eael_pm_acf_field_variation_repeater_' . $field_name ] ?? '' );
        $variation_label_key    = '' !== $variation_repeater_key ? sanitize_text_field( $settings[ 'eael_pm_acf_field_variation_label_'    . $field_name . '_' . $variation_repeater_key ] ?? '' ) : '';
        $variation_original_key = '' !== $variation_repeater_key ? sanitize_text_field( $settings[ 'eael_pm_acf_field_variation_original_' . $field_name . '_' . $variation_repeater_key ] ?? '' ) : '';
        $variation_price_key    = '' !== $variation_repeater_key ? sanitize_text_field( $settings[ 'eael_pm_acf_field_variation_price_'    . $field_name . '_' . $variation_repeater_key ] ?? '' ) : '';

        if ( empty( $field_name ) || ! function_exists( 'get_field' ) ) {
            return [];
        }

        $object_id = get_the_ID() ?: get_queried_object_id();
        $rows      = get_field( $field_name, $object_id );
        if ( empty( $rows ) || ! is_array( $rows ) ) {
            return [];
        }

        $sub_field_labels = [];
        if ( ! empty( $extra_keys ) && function_exists( 'acf_get_field' ) ) {
            $repeater_field = acf_get_field( $field_name );
            if ( ! empty( $repeater_field['sub_fields'] ) ) {
                foreach ( $repeater_field['sub_fields'] as $sf ) {
                    $sub_field_labels[ $sf['name'] ] = $sf['label'];
                }
            }
        }

        // Coerce sub-field reads to string. Non-scalar (e.g. mis-mapped image array) → empty string,
        $str = static function ( $value ) {
            return is_scalar( $value ) ? (string) $value : '';
        };

        $items = [];
        foreach ( $rows as $row ) {
            $image          = $row[ $image_key ] ?? [];
            $link_value     = $str( $row[ $link_key ]           ?? '' );
            $original_price = $str( $row[ $original_price_key ] ?? '' );

            $extras = [];
            foreach ( $extra_keys as $key ) {
                $val = $row[ $key ] ?? '';
                if ( '' === $val || null === $val || [] === $val ) continue;
                if ( ! is_scalar( $val ) ) continue;
                $extras[] = [
                    'label' => $sub_field_labels[ $key ] ?? $key,
                    'value' => (string) $val,
                ];
            }

            // Build variation_price_list from nested ACF repeater rows.
            $variation_list   = [];
            $variation_toggle = '';
            if ( '' !== $variation_repeater_key ) {
                $variation_rows = $row[ $variation_repeater_key ] ?? [];
                if ( is_array( $variation_rows ) && ! empty( $variation_rows ) ) {
                    foreach ( $variation_rows as $vr ) {
                        if ( ! is_array( $vr ) ) {
                            continue;
                        }
                        $v_label    = '' !== $variation_label_key    ? $str( $vr[ $variation_label_key ]    ?? '' ) : '';
                        $v_original = '' !== $variation_original_key ? $str( $vr[ $variation_original_key ] ?? '' ) : '';
                        $v_price    = '' !== $variation_price_key    ? $str( $vr[ $variation_price_key ]    ?? '' ) : '';
                        if ( '' === $v_label && '' === $v_price ) {
                            continue;
                        }
                        $variation_list[] = [
                            'variation_label'          => sanitize_text_field( $v_label ),
                            'variation_original_price' => sanitize_text_field( $v_original ),
                            'variation_price'          => sanitize_text_field( $v_price ),
                        ];
                    }
                    if ( ! empty( $variation_list ) ) {
                        $variation_toggle = 'yes';
                    }
                }
            }

            $items[] = [
                '_id'                  => uniqid( 'pm_', false ),
                'menu_title'           => wp_kses_post( $str( $row[ $title_key ]       ?? '' ) ),
                'menu_description'     => wp_kses_post( $str( $row[ $description_key ] ?? '' ) ),
                'menu_price'           => wp_kses_post( $str( $row[ $price_key ]       ?? '' ) ),
                'discount'             => '' !== $original_price ? 'yes' : 'no',
                'original_price'       => wp_kses_post( $original_price ),
                'variation_price'      => $variation_toggle,
                'variation_price_list' => $variation_list,
                'image_switch'         => ! empty( $image ) ? 'yes' : '',
                'image'                => [
                    'url' => is_array( $image ) ? ( $image['url'] ?? '' ) : (string) $image,
                    'id'  => is_array( $image ) ? ( $image['ID']  ?? 0 )  : 0,
                ],
                'link'                 => [
                    'url'         => '' !== $link_value ? esc_url_raw( $link_value ) : '',
                    'is_external' => '',
                    'nofollow'    => '',
                ],
                'menu_extras'          => $extras,
            ];
        }

        return $items;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $i = 1;
        $this->add_render_attribute( 'price-menu', 'class', 'eael-restaurant-menu' );

		if ( $settings['menu_style'] ) {
			$this->add_render_attribute( 'price-menu', 'class', 'eael-restaurant-menu-' . $settings['menu_style'] );
		}

        $menu_items = $this->eael_get_price_menu_items( $settings );
        ?>
        <div <?php $this->print_render_attribute_string( 'price-menu' ); ?>>
            <div class="eael-restaurant-menu-items">
                <?php foreach ( $menu_items as $index => $item ) : ?>
                    <?php
                        $title_key = $this->get_repeater_setting_key( 'menu_title', 'menu_items', $index );
                        $this->add_render_attribute( $title_key, 'class', 'eael-restaurant-menu-title-text' );
                        $this->add_inline_editing_attributes( $title_key, 'none' );

                        $description_key = $this->get_repeater_setting_key( 'menu_description', 'menu_items', $index );
                        $this->add_render_attribute( $description_key, 'class', 'eael-restaurant-menu-description' );
                        $this->add_inline_editing_attributes( $description_key, 'basic' );

                        $discount_price_key = $this->get_repeater_setting_key( 'menu_price', 'menu_items', $index );
                        $this->add_render_attribute( $discount_price_key, 'class', 'eael-restaurant-menu-price-discount' );
                        $this->add_inline_editing_attributes( $discount_price_key, 'none' );

                        $original_price_key = $this->get_repeater_setting_key( 'original_price', 'menu_items', $index );
                        $this->add_render_attribute( $original_price_key, 'class', 'eael-restaurant-menu-price-original' );
                        $this->add_inline_editing_attributes( $original_price_key, 'none' );

                        $has_link = false;
                        if ( ! empty( $item['link']['url'] ) ) {
                            $has_link = true;
                            $this->add_link_attributes( 'price_menu_link_' . $i, $item['link'] );
                        }
                    ?>
                    <div class="eael-restaurant-menu-item-wrap">
                        <?php if( $has_link && 'full_item' === $settings['link_apply_on'] ){
                            echo '<a '; $this->print_render_attribute_string( 'price_menu_link_' . $i ); echo '>';
                        } ?>
                        <div class="eael-restaurant-menu-item">
                            <?php if ( $item['image_switch'] == 'yes' ) { ?>
                                <div class="eael-restaurant-menu-image">
                                    <?php
                                        if ( ! empty( $item['image']['url'] ) ) :
                                            $image = $item['image'];
                                            // WPML Media Translation compatibility
                                            if ( ! empty( $image['id'] ) ) {
                                                $image['id']  = apply_filters( 'wpml_object_id', $image['id'], 'attachment', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                                                $image['url'] = wp_get_attachment_url( $image['id'] );
                                            }
                                            $image_url = Group_Control_Image_Size::get_attachment_image_src( $image['id'], 'image_size', $settings );
                                            if( $has_link && 'image' === $settings['link_apply_on'] ){
                                                echo '<a '; $this->print_render_attribute_string( 'price_menu_link_' . $i ); echo '>';
                                            }
                                        ?>
                                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr(get_post_meta($image['id'], '_wp_attachment_image_alt', true)); ?>">	
                                    <?php 
                                            if( $has_link && 'image' === $settings['link_apply_on'] ) { echo '</a>'; }
                                        endif; 
                                    ?>
                                </div>
                            <?php } ?>

                            <div class="eael-restaurant-menu-content">
                                <div class="eael-restaurant-menu-header">
                                    <?php if ( ! empty( $item['menu_title'] ) ) { ?>
                                        <h4 class="eael-restaurant-menu-title">
                                            <?php if( $has_link && 'title' === $settings['link_apply_on'] ){
                                                echo '<a '; $this->print_render_attribute_string( 'price_menu_link_' . $i ); echo '>';
                                            } ?>
                                            <span <?php $this->print_render_attribute_string( $title_key ); ?>>
                                                <?php echo wp_kses( $item['menu_title'], Helper::eael_allowed_tags() ); ?>
                                            </span>
                                            <?php if( $has_link && 'title' === $settings['link_apply_on'] ) { echo '</a>'; } ?>
                                        </h4>
                                    <?php } ?>
                                    
                                    <?php if ( $settings['title_price_connector'] == 'yes' ) { ?>
                                        <span class="eael-price-title-connector"></span>
                                    <?php } ?>
                                    
                                    <?php if ( $settings['menu_style'] == 'style-1' ) { ?>
                                         <span class="eael-restaurant-menu-price">
                                             <?php if ( ! empty( $item['menu_price'] ) && 'yes' !== $item['variation_price'] ) { ?>
                                                 <?php if ( $item['discount'] == 'yes' ) { ?>
                                                     <span <?php $this->print_render_attribute_string( $original_price_key ); ?>>
                                                         <?php echo esc_attr( $item['original_price'] ); ?>
                                                     </span>
                                                 <?php } ?>
                                                 <span <?php $this->print_render_attribute_string( $discount_price_key ); ?>>
                                                     <?php echo wp_kses( $item['menu_price'], Helper::eael_allowed_tags() ); ?>
                                                 </span>
                                             <?php } ?>

                                             <?php if ( 'yes' === $item['variation_price'] && ! empty( $item['variation_price_list'] ) ) : ?>
                                                 <div class="eael-price-variations">
                                                     <?php foreach ( $item['variation_price_list'] as $variation ) : ?>
                                                         <span class="eael-price-variation-item">
                                                             <?php if ( ! empty( $variation['variation_label'] ) ) : ?>
                                                                 <span class="eael-price-variation-label"><?php echo esc_html( $variation['variation_label'] ); ?></span>
                                                             <?php endif; ?>
                                                             <?php if ( ! empty( $variation['variation_original_price'] ) ) : ?>
                                                                 <span class="eael-price-variation-original"><?php echo esc_html( $variation['variation_original_price'] ); ?></span>
                                                             <?php endif; ?>
                                                             <?php if ( ! empty( $variation['variation_price'] ) ) : ?>
                                                                 <span class="eael-price-variation-amount"><?php echo esc_html( $variation['variation_price'] ); ?></span>
                                                             <?php endif; ?>
                                                         </span>
                                                     <?php endforeach; ?>
                                                 </div>
                                             <?php endif; ?>
                                         </span>
                                     <?php } ?>
                                 </div>
                                 
                                 <?php if ( $settings['title_separator'] == 'yes' ) { ?>
                                     <div class="eael-price-menu-divider-wrap">
                                         <div class="eael-price-menu-divider"></div>
                                     </div>
                                 <?php } ?>

                                 <?php
                                     if ( ! empty( $item['menu_description'] ) ) {
                                         echo '<div '; $this->print_render_attribute_string( $description_key ) ; echo '>';
                                         echo wp_kses( $item['menu_description'], Helper::eael_allowed_tags() );
                                         echo '</div>';
                                     }

                                     if ( ! empty( $item['menu_extras'] ) ) {
                                         echo '<ul class="eael-restaurant-menu-extras">';
                                         foreach ( $item['menu_extras'] as $extra ) {
                                             printf(
                                                 '<li class="eael-restaurant-menu-extra"><span class="eael-restaurant-menu-extra-value">%s</span></li>',
                                                 wp_kses_post( $extra['value'] )
                                             );
                                         }
                                         echo '</ul>';
                                     }
                                 ?>

                                 <?php if ( $settings['menu_style'] != 'style-1' ) { ?>
                                     <span class="eael-restaurant-menu-price">
                                         <?php if ( ! empty( $item['menu_price'] ) && 'yes' !== $item['variation_price'] ) { ?>
                                             <?php if ( $item['discount'] == 'yes' ) { ?>
                                                 <span <?php $this->print_render_attribute_string( $original_price_key ); ?>>
                                                     <?php echo esc_attr( $item['original_price'] ); ?>
                                                 </span>
                                             <?php } ?>
                                             <span <?php $this->print_render_attribute_string( $discount_price_key ); ?>>
                                                 <?php echo wp_kses( $item['menu_price'], Helper::eael_allowed_tags() ); ?>
                                             </span>
                                         <?php } ?>

                                         <?php if ( 'yes' === $item['variation_price'] && ! empty( $item['variation_price_list'] ) ) : ?>
                                             <div class="eael-price-variations">
                                                 <?php foreach ( $item['variation_price_list'] as $variation ) : ?>
                                                     <span class="eael-price-variation-item">
                                                         <?php if ( ! empty( $variation['variation_label'] ) ) : ?>
                                                             <span class="eael-price-variation-label"><?php echo esc_html( $variation['variation_label'] ); ?></span>
                                                         <?php endif; ?>
                                                         <?php if ( ! empty( $variation['variation_original_price'] ) ) : ?>
                                                             <span class="eael-price-variation-original"><?php echo esc_html( $variation['variation_original_price'] ); ?></span>
                                                         <?php endif; ?>
                                                         <?php if ( ! empty( $variation['variation_price'] ) ) : ?>
                                                             <span class="eael-price-variation-amount"><?php echo esc_html( $variation['variation_price'] ); ?></span>
                                                         <?php endif; ?>
                                                     </span>
                                                 <?php endforeach; ?>
                                             </div>
                                         <?php endif; ?>
                                     </span>
                                 <?php } ?>
                            </div>
                        </div>
                        <?php if( $has_link && 'full_item' === $settings['link_apply_on'] ){ echo '</a>'; } ?>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
        <?php
    }

    protected function content_template() { }
}
