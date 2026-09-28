<?php

namespace Essential_Addons_Elementor\Pro\Elements;

use \Elementor\Controls_Manager;
use \Elementor\Group_Control_Background;
use Elementor\Repeater;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Box_Shadow;
use \Elementor\Group_Control_Typography;
use \Elementor\Widget_Base;
use Essential_Addons_Elementor\Classes\Helper;
use Essential_Addons_Elementor\Pro\Classes\Helper as Pro_Helper;

if (!defined('ABSPATH')) exit; // If this file is called directly, abort.

class Google_Map extends Widget_Base
{

	public function get_name()
	{
		return 'eael-google-map';
	}

	public function get_title()
	{
		return esc_html__('Advanced Google Map', 'essential-addons-elementor');
	}

	public function get_icon()
	{
		return 'eaicon-advanced-google-maps';
	}

	public function get_categories()
	{
		return ['essential-addons-elementor'];
	}

	public function get_keywords()
	{
		return [
			'advanced google map',
			'ea advanced google map',
			'ea google map',
			'ea map',
			'ea advanced map',
			'map',
			'google map',
			'navigation map',
			'mutiple marker',
			'dynamic map',
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
		return 'https://essential-addons.com/elementor/docs/advanced-google-map/';
	}

    /**
     * ACF Registers Controls
     */
    protected function eael_register_acf_controls() {
        $this->start_controls_section(
            'eael_gmap_acf_data_source_section',
            [
                'label' => esc_html__('Data Source', 'essential-addons-elementor'),
            ]
        );

        $this->add_control(
            'eael_gmap_acf_data_source',
            [
                'label'   => esc_html__('Source', 'essential-addons-elementor'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    'custom' => esc_html__('Custom (Manual)', 'essential-addons-elementor'),
                    'acf_repeater'    => esc_html__('ACF Repeater Field', 'essential-addons-elementor'),
                ],
            ]
        );

        $this->add_control(
            'eael_gmap_acf_repeater_field',
            [
                'label'     => esc_html__( 'Repeater Field', 'essential-addons-elementor' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => '',
                'options'   => Pro_Helper::eael_get_acf_repeater_options(),
                'condition' => [ 'eael_gmap_acf_data_source' => 'acf_repeater' ],
            ]
        );

        // ACF Notice
        Pro_Helper::eael_acf_notice_controls( $this, ['eael_gmap_acf_data_source' => 'acf_repeater'] );

        // ACF Sub Field Mapping
        $acf_sub_fields_by_repeater = Pro_Helper::eael_get_acf_repeater_sub_fields();

        foreach ( $acf_sub_fields_by_repeater as $repeater_name => $sub_field_options ) {
            $base_condition = [
                'eael_gmap_acf_data_source'    => 'acf_repeater',
                'eael_gmap_acf_repeater_field' => $repeater_name,
            ];

            $this->add_control(
                'eael_gmap_acf_fields_heading_' . $repeater_name,
                [
                    'label'     => esc_html__( 'Field Mapping', 'essential-addons-elementor' ),
                    'type'      => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => $base_condition,
                ]
            );

            $mapping = [
                'lat'     => esc_html__( 'Latitude', 'essential-addons-elementor' ),
                'lng'     => esc_html__( 'Longitude', 'essential-addons-elementor' ),
                'title'   => esc_html__( 'Title', 'essential-addons-elementor' ),
                'content' => esc_html__( 'Content', 'essential-addons-elementor' ),
                'icon'    => esc_html__( 'Marker (Image)', 'essential-addons-elementor' ),
            ];

            foreach ( $mapping as $key => $label ) {
                $this->add_control(
                    'eael_gmap_acf_' . $key . '_' . $repeater_name,
                    [
                        'label'     => $label,
                        'type'      => Controls_Manager::SELECT,
                        'default'   => '',
                        'options'   => $sub_field_options,
                        'condition' => $base_condition,
                    ]
                );
            }

            // Extra sub-fields appended to each marker's info window content.
            $extra_options = $sub_field_options;
            unset( $extra_options[''] );

            $this->add_control(
                'eael_gmap_acf_extras_' . $repeater_name,
                [
                    'label'       => esc_html__( 'Additional Fields', 'essential-addons-elementor' ),
                    'type'        => Controls_Manager::SELECT2,
                    'multiple'    => true,
                    'label_block' => true,
                    'default'     => [],
                    'options'     => $extra_options,
                    'description' => esc_html__( 'Selected additional data to display below the description.', 'essential-addons-elementor' ),
                    'condition'   => $base_condition,
                ]
            );
        }

        $this->end_controls_section();
    }

	protected function register_controls()
	{
        // ACF Data Source
        $this->eael_register_acf_controls();
		/**
		 * Google Map General Settings
		 */
		$this->start_controls_section(
			'eael_section_google_map_settings',
			[
				'label'     => esc_html__('General Settings', 'essential-addons-elementor'),
				// Hidden for ACF Repeater source — markers come from the repeater and the map is forced to Multiple Marker.
				'condition' => [ 'eael_gmap_acf_data_source' => 'custom' ],
			]
		);
		$this->add_control(
			'eael_google_map_type',
			[
				'label'       	=> esc_html__('Google Map Type', 'essential-addons-elementor'),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'basic',
				'label_block' 	=> false,
				'options' 		=> [
					'basic'  	=> esc_html__('Basic', 'essential-addons-elementor'),
					'marker'  	=> esc_html__('Multiple Marker', 'essential-addons-elementor'),
					'static'  	=> esc_html__('Static', 'essential-addons-elementor'),
					'polyline'  => esc_html__('Polyline', 'essential-addons-elementor'),
					'polygon'  	=> esc_html__('Polygon', 'essential-addons-elementor'),
					'overlay'  	=> esc_html__('Overlay', 'essential-addons-elementor'),
					'routes'  	=> esc_html__('With Routes', 'essential-addons-elementor'),
					'panorama'  => esc_html__('Panorama', 'essential-addons-elementor'),
				]
			]
		);

		$this->add_control(
			'eael_google_map_address_type',
			[
				'label' => __('Address Type', 'essential-addons-elementor'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'address' => [
						'title' => __('Address', 'essential-addons-elementor'),
						'icon' => 'fa fa-map',
					],
					'coordinates' => [
						'title' => __('Coordinates', 'essential-addons-elementor'),
						'icon' => 'fa fa-map-marker',
					],
				],
				'default' => 'address',
				'condition' => [
					'eael_google_map_type' => ['basic']
				]
			]
		);
		$this->add_control(
			'eael_google_map_addr',
			[
				'label' => esc_html__('Geo Address', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
                'dynamic' => [ 'active' => true ],
				'label_block' => true,
				'default' => esc_html__('Marina Bay, Singapore', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_address_type' => ['address'],
					'eael_google_map_type' => ['basic']
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('28.948790', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type!' => ['routes'],
					'eael_google_map_address_type' => ['coordinates']
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-81.298843', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type!' => ['routes'],
					'eael_google_map_address_type' => ['coordinates']
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		// Only for static
		$this->add_control(
			'eael_google_map_static_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('28.948790', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['static'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_static_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-81.298843', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['static'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_resolution_title',
			[
				'label' => __('Map Image Resolution', 'essential-addons-elementor'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'eael_google_map_type' => 'static'
				]
			]
		);
		$this->add_control(
			'eael_google_map_static_width',
			[
				'label' => esc_html__('Static Image Width', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 610
				],
				'range' => [
					'px' => [
						'max' => 1400,
					],
				],
				'condition' => [
					'eael_google_map_type' => 'static'
				]
			]
		);
		$this->add_control(
			'eael_google_map_static_height',
			[
				'label' => esc_html__('Static Image Height', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 300
				],
				'range' => [
					'px' => [
						'max' => 700,
					],
				],
				'condition' => [
					'eael_google_map_type' => 'static'
				]
			]
		);
		// Only for Overlay
		$this->add_control(
			'eael_google_map_overlay_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('28.948790', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['overlay'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_overlay_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-81.298843', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['overlay'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		// Only for panorama
		$this->add_control(
			'eael_google_map_panorama_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('28.948790', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['panorama'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_panorama_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-81.298843', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['panorama'],
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_overlay_content',
			[
				'label' => esc_html__('Overlay Content', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXTAREA,
				'label_block' => True,
				'default' => esc_html__('Add your content here', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => 'overlay'
				]
			]
		);
		$this->end_controls_section();
		/**
		 * Map Settings (With Marker only for Basic)
		 */
		$this->start_controls_section(
			'eael_section_google_map_basic_marker_settings',
			[
				'label' => esc_html__('Map Marker Settings', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['basic'],
                    'condition' => ['eael_gmap_acf_data_source' => 'custom'],
				]
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_title',
			[
				'label' => esc_html__('Title', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
				'label_block' => true,
				'default' => esc_html__('Google Map Title', 'essential-addons-elementor'),
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_content',
			[
				'label' => esc_html__('Content', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXTAREA,
                'dynamic' => [
                    'active' => true,
                ],
				'label_block' => true,
				'default' => esc_html__('Google map content', 'essential-addons-elementor')
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_icon_enable',
			[
				'label' => __('Custom Marker Icon', 'essential-addons-elementor'),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'no',
				'label_on' => __('Yes', 'essential-addons-elementor'),
				'label_off' => __('No', 'essential-addons-elementor'),
				'return_value' => 'yes',
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_icon',
			[
				'label' => esc_html__('Marker Icon', 'essential-addons-elementor'),
				'type' => Controls_Manager::MEDIA,
				'condition' => [
					'eael_google_map_basic_marker_icon_enable' => 'yes'
				],
				'ai' => [
					'active' => false,
				],
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_icon_width',
			[
				'label' => esc_html__('Marker Width', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 32
				],
				'range' => [
					'px' => [
						'max' => 150,
					],
				],
				'condition' => [
					'eael_google_map_basic_marker_icon_enable' => 'yes'
				]
			]
		);
		$this->add_control(
			'eael_google_map_basic_marker_icon_height',
			[
				'label' => esc_html__('Marker Height', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 32
				],
				'range' => [
					'px' => [
						'max' => 150,
					],
				],
				'condition' => [
					'eael_google_map_basic_marker_icon_enable' => 'yes'
				]
			]
		);
		$this->end_controls_section();

  		/**
  		 * Map Settings (With Marker)
  		 */
  		$this->start_controls_section(
  			'eael_section_google_map_marker_settings',
  			[
  				'label' => esc_html__( 'Map Marker Settings', 'essential-addons-elementor' ),
  				'condition' => [
  					'eael_google_map_type' => ['marker', 'polyline', 'routes', 'static']
  				]
  			]
  		);

        $repeater = new Repeater();

        $repeater->add_control(
            'eael_google_map_marker_lat',
            [
                'label' => esc_html__( 'Latitude', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__( '28.948790', 'essential-addons-elementor' ),
				'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_lng',
            [
                'label' => esc_html__( 'Longitude', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__( '-81.298843', 'essential-addons-elementor' ),
				'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_title',
            [
                'label' => esc_html__( 'Title', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__( 'Marker Title', 'essential-addons-elementor' ),
				'ai' => [
					'active' => true,
				],
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_content',
            [
                'label' => esc_html__( 'Content', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXTAREA,
                'label_block' => true,
                'default' => esc_html__( 'Marker Content. You can put html here.', 'essential-addons-elementor' ),
				'ai' => [
					'active' => false,
				],
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_icon_color',
            [
                'label' => esc_html__( 'Default Icon Color', 'essential-addons-elementor' ),
                'description' => esc_html__( '(Works only on Static mode)', 'essential-addons-elementor' ),
                'type' => Controls_Manager::COLOR,
                'default' => '#e23a47',
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_icon_enable',
            [
                'label' => __( 'Use Custom Icon', 'essential-addons-elementor' ),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'label_on' => __( 'Yes', 'essential-addons-elementor' ),
                'label_off' => __( 'No', 'essential-addons-elementor' ),
                'return_value' => 'yes',
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_icon',
            [
                'label' => esc_html__( 'Custom Icon', 'essential-addons-elementor' ),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'eael_google_map_marker_icon_enable' => 'yes'
				],
				'ai' => [
					'active' => false,
				],
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_icon_width',
            [
                'label' => esc_html__( 'Icon Width', 'essential-addons-elementor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => esc_html__( '32', 'essential-addons-elementor' ),
                'condition' => [
                    'eael_google_map_marker_icon_enable' => 'yes'
                ]
            ]
        );

        $repeater->add_control(
            'eael_google_map_marker_icon_height',
            [
                'label' => esc_html__( 'Icon Height', 'essential-addons-elementor' ),
                'type' => Controls_Manager::NUMBER,
                'default' => esc_html__( '32', 'essential-addons-elementor' ),
                'condition' => [
                    'eael_google_map_marker_icon_enable' => 'yes'
                ]
            ]
        );

		$this->add_control(
			'eael_google_map_markers',
			[
				'type' => Controls_Manager::REPEATER,
				'seperator' => 'before',
				'default' => [
					['eael_google_map_marker_title' => esc_html__('Map Marker 1', 'essential-addons-elementor')],
				],
				'fields' => $repeater->get_controls(),
				'title_field' => '{{eael_google_map_marker_title}}',
			]
		);

		$this->add_control(
			'enable_marker_search',
			[
				'label'        => esc_html__( 'Enable Marker Search', 'essential-addons-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'essential-addons-elementor' ),
				'label_off'    => esc_html__( 'No', 'essential-addons-elementor' ),
				'description'  => esc_html__( 'Search among listed markers', 'essential-addons-elementor' ),
				'return_value' => 'yes',
				'condition' => [
					'eael_google_map_type' => 'marker'
				]
			]
		);

		$this->add_control(
			'marker_search_text',
			[
				'label'       => esc_html__('Placeholder Text', 'essential-addons-elementor'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__('Search Marker...', 'essential-addons-elementor'),
				'condition'   => [
					'enable_marker_search' => 'yes',
					'eael_google_map_type' => 'marker'
				],
				'ai' 	  => [ 'active' => true ],
                'dynamic' => [ 'active' => true ],
			]
		);

		$this->end_controls_section();


  		/**
  		 * Polyline Coordinates Settings (Polyline)
  		 */
  		$this->start_controls_section(
  			'eael_section_google_map_polyline_settings',
  			[
  				'label' => esc_html__( 'Coordinate Settings', 'essential-addons-elementor' ),
  				'condition' => [
  					'eael_google_map_type' => ['polyline', 'polygon']
  				]
  			]
  		);

        $polylines_repeater = new Repeater();

        $polylines_repeater->add_control(
            'eael_google_map_polyline_title',
            [
                'label' => esc_html__( 'Title', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__( '#', 'essential-addons-elementor' ),
				'ai' => [
					'active' => true,
				],
            ]
        );

        $polylines_repeater->add_control(
            'eael_google_map_polyline_lat',
            [
                'label' => esc_html__( 'Latitude', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'ai' => [
					'active' => true,
				],
            ]
        );

        $polylines_repeater->add_control(
            'eael_google_map_polyline_lng',
            [
                'label' => esc_html__( 'Longitude', 'essential-addons-elementor' ),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
				'ai' => [
					'active' => true,
				],
            ]
        );

		$this->add_control(
			'eael_google_map_polylines',
			[
				'type'        => Controls_Manager::REPEATER,
				'seperator'   => 'before',
				'default'     => [
					[
						'eael_google_map_polyline_title' => esc_html__( '#1', 'essential-addons-elementor' ),
						'eael_google_map_polyline_lat'   => '-12.040397656836609',
						'eael_google_map_polyline_lng'   => '-77.03373871559225'
					],
					[
						'eael_google_map_polyline_title' => esc_html__( '#2', 'essential-addons-elementor' ),
						'eael_google_map_polyline_lat'   => '-12.040248585302038',
						'eael_google_map_polyline_lng'   => '-77.03993927003302'
					],
					[
						'eael_google_map_polyline_title' => esc_html__( '#3', 'essential-addons-elementor' ),
						'eael_google_map_polyline_lat'   => '-12.050047116528843',
						'eael_google_map_polyline_lng'   => '-77.02448169303511'
					],
					[
						'eael_google_map_polyline_title' => esc_html__( '#4', 'essential-addons-elementor' ),
						'eael_google_map_polyline_lat'   => '-12.044804866577001',
						'eael_google_map_polyline_lng'   => '-77.02154422636042'
					],
				],
				'fields'      => $polylines_repeater->get_controls(),
				'title_field' => '{{eael_google_map_polyline_title}}',
			]
		);
		$this->end_controls_section();

		/**
		 * Routes Coordinates Settings (Routes)
		 */
		$this->start_controls_section(
			'eael_section_google_map_routes_settings',
			[
				'label' => esc_html__('Routes Coordinate Settings', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_type' => ['routes']
				]
			]
		);
		$this->add_control(
			'eael_google_map_routes_origin',
			[
				'label' => esc_html__('Origin', 'essential-addons-elementor'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'after',
			]
		);
		$this->add_control(
			'eael_google_map_routes_origin_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-12.044012922866312', 'essential-addons-elementor'),
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_routes_origin_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-77.02470665341184', 'essential-addons-elementor'),
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_routes_dest',
			[
				'label' => esc_html__('Destination', 'essential-addons-elementor'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'after',
			]
		);
		$this->add_control(
			'eael_google_map_routes_dest_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-12.090814532191756', 'essential-addons-elementor'),
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_routes_dest_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('-77.02271108990476', 'essential-addons-elementor'),
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_routes_travel_mode',
			[
				'label'       	=> esc_html__('Travel Mode', 'essential-addons-elementor'),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> 'walking',
				'label_block' 	=> false,
				'options' 		=> [
					'walking'  	=> esc_html__('Walking', 'essential-addons-elementor'),
					'bicycling' => esc_html__('Bicycling', 'essential-addons-elementor'),
					'driving' 	=> esc_html__('Driving', 'essential-addons-elementor'),
				]
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_map_controls',
			[
				'label'	=> esc_html__('Map Controls', 'essential-addons-elementor')
			]
		);
		$this->add_control(
			'eael_google_map_zoom',
			[
				'label' => esc_html__('Zoom Level', 'essential-addons-elementor'),
				'type' => Controls_Manager::NUMBER,
				'label_block' => false,
				'default' => esc_html__('14', 'essential-addons-elementor'),
			]
		);
		$this->add_control(
			'eael_map_streeview_control',
			[
				'label'                 => esc_html__('Street View Controls', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SWITCHER,
				'default'               => 'true',
				'label_on'              => __('On', 'essential-addons-elementor'),
				'label_off'             => __('Off', 'essential-addons-elementor'),
				'return_value'          => 'true',
			]
		);
		$this->add_control(
			'eael_map_type_control',
			[
				'label'                 => esc_html__('Map Type Control', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SWITCHER,
				'default'               => 'yes',
				'label_on'              => __('On', 'essential-addons-elementor'),
				'label_off'             => __('Off', 'essential-addons-elementor'),
				'return_value'          => 'yes',
			]
		);
		$this->add_control(
			'eael_map_zoom_control',
			[
				'label'                 => esc_html__('Zoom Control', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SWITCHER,
				'default'               => 'yes',
				'label_on'              => __('On', 'essential-addons-elementor'),
				'label_off'             => __('Off', 'essential-addons-elementor'),
				'return_value'          => 'yes',
			]
		);
		$this->add_control(
			'eael_map_fullscreen_control',
			[
				'label'                 => esc_html__('Fullscreen Control', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SWITCHER,
				'default'               => 'yes',
				'label_on'              => __('On', 'essential-addons-elementor'),
				'label_off'             => __('Off', 'essential-addons-elementor'),
				'return_value'          => 'yes',
			]
		);
		$this->add_control(
			'eael_map_scroll_zoom',
			[
				'label'                 => esc_html__('Scroll Wheel Zoom', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SWITCHER,
				'default'               => 'yes',
				'label_on'              => __('On', 'essential-addons-elementor'),
				'label_off'             => __('Off', 'essential-addons-elementor'),
				'return_value'          => 'yes',
			]
		);
		$this->add_control(
			'eael_google_map_center_point',
			[
				'label'     => esc_html__( 'Center Point', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''       => __( 'Default', 'essential-addons-elementor' ),
					'auto'   => __( 'Auto Center', 'essential-addons-elementor' ),
					'custom' => __( 'Custom Point', 'essential-addons-elementor' ),
				],
				'condition' => [
					'eael_google_map_type' => [ 'marker', 'polyline', 'polygon' ]
				]
			]
		);
		$this->add_control(
			'eael_google_map_center_point_lat',
			[
				'label' => esc_html__('Latitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('28.948790', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_center_point' => 'custom',
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->add_control(
			'eael_google_map_center_point_lng',
			[
				'label' => esc_html__('Longitude', 'essential-addons-elementor'),
				'type' => Controls_Manager::TEXT,
				'label_block' => false,
				'default' => esc_html__('90.403947', 'essential-addons-elementor'),
				'condition' => [
					'eael_google_map_center_point' => 'custom',
				],
				'ai' => [
					'active' => true,
				],
			]
		);
		$this->end_controls_section();

		/**
		 * Map Theme Settings
		 */
		$this->start_controls_section(
			'eael_section_google_map_theme_settings',
			[
				'label'		=> esc_html__('Map Theme', 'essential-addons-elementor'),
				'condition' => [
                    'eael_google_map_type!'	=> ['static', 'panorama', 'polygon', 'routes']
				]
			]
		);
		$this->add_control(
			'eael_google_map_theme_source',
			[
				'label'		=> __('Theme Source', 'essential-addons-elementor'),
				'type'		=> Controls_Manager::CHOOSE,
				'options' => [
					'gstandard' => [
						'title' => __('Google Standard', 'essential-addons-elementor'),
						'icon' => 'fa fa-map',
					],
					'snazzymaps' => [
						'title' => __('Snazzy Maps', 'essential-addons-elementor'),
						'icon' => 'fa fa-map-marker',
					],
					'custom' => [
						'title' => __('Custom', 'essential-addons-elementor'),
						'icon' => 'fa fa-edit',
					],
				],
				'default'	=> 'gstandard'
			]
		);
		$this->add_control(
			'eael_google_map_gstandards',
			[
				'label'                 => esc_html__('Google Themes', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SELECT,
				'default'               => 'standard',
				'options'               => [
					'standard'     => __('Standard', 'essential-addons-elementor'),
					'silver'       => __('Silver', 'essential-addons-elementor'),
					'retro'        => __('Retro', 'essential-addons-elementor'),
					'dark'         => __('Dark', 'essential-addons-elementor'),
					'night'        => __('Night', 'essential-addons-elementor'),
					'aubergine'    => __('Aubergine', 'essential-addons-elementor')
				],
				'description'           => sprintf('<a href="https://mapstyle.withgoogle.com/" target="_blank">%1$s</a> %2$s', __('Click here', 'essential-addons-elementor'), __('to generate your own theme and use JSON within Custom style field.', 'essential-addons-elementor')),
				'condition'	=> [
					'eael_google_map_theme_source'	=> 'gstandard'
				]
			]
		);
		$this->add_control(
			'eael_google_map_snazzymaps',
			[
				'label'                 => esc_html__('SnazzyMaps Themes', 'essential-addons-elementor'),
				'type'                  => Controls_Manager::SELECT,
				'label_block'			=> true,
				'default'               => 'colorful',
				'options'               => [
					'default'		=> __('Default', 'essential-addons-elementor'),
					'simple'		=> __('Simple', 'essential-addons-elementor'),
					'colorful'		=> __('Colorful', 'essential-addons-elementor'),
					'complex'		=> __('Complex', 'essential-addons-elementor'),
					'dark'			=> __('Dark', 'essential-addons-elementor'),
					'greyscale'		=> __('Greyscale', 'essential-addons-elementor'),
					'light'			=> __('Light', 'essential-addons-elementor'),
					'monochrome'	=> __('Monochrome', 'essential-addons-elementor'),
					'nolabels'		=> __('No Labels', 'essential-addons-elementor'),
					'twotone'		=> __('Two Tone', 'essential-addons-elementor')
				],
				'description'           => sprintf('<a href="https://snazzymaps.com/explore" target="_blank">%1$s</a> %2$s', __('Click here', 'essential-addons-elementor'), __('to explore more themes and use JSON within custom style field.', 'essential-addons-elementor')),
				'condition'	=> [
					'eael_google_map_theme_source'	=> 'snazzymaps'
				]
			]
		);
		$this->add_control(
			'eael_google_map_custom_style',
			[
				'label'                 => __('Custom Style', 'essential-addons-elementor'),
				'description'           => sprintf('<a href="https://mapstyle.withgoogle.com/" target="_blank">%1$s</a> %2$s', __('Click here', 'essential-addons-elementor'), __('to get JSON style code to style your map', 'essential-addons-elementor')),
				'type'                  => Controls_Manager::TEXTAREA,
				'condition'             => [
					'eael_google_map_theme_source'     => 'custom',
				],
			]
		);
		$this->end_controls_section();
		/**
		 * -------------------------------------------
		 * Tab Style Google Map Style
		 * -------------------------------------------
		 */
		$this->start_controls_section(
			'eael_section_google_map_style_settings',
			[
				'label' => esc_html__('General Style', 'essential-addons-elementor'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'eael_google_map_max_width',
			[
				'label' => __('Max Width', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 1140,
					'unit' => 'px',
				],
				'size_units' => ['px', '%'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1400,
						'step' => 10,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map' => 'max-width: {{SIZE}}{{UNIT}};',
				]
			]
		);
		$this->add_responsive_control(
			'eael_google_map_max_height',
			[
				'label' => __('Max Height', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 400,
					'unit' => 'px',
				],
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1400,
						'step' => 10,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map' => 'height: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_responsive_control(
			'eael_google_map_margin',
			[
				'label' => esc_html__('Margin', 'essential-addons-elementor'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();


		/**
		 * -------------------------------------------
		 * Tab Style Google Map Marker search Style
		 * -------------------------------------------
		 */
		$this->start_controls_section(
			'eael_section_google_map_maker_search_style_settings',
			[
				'label'     => esc_html__('Search Input', 'essential-addons-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'enable_marker_search' => 'yes',
					'eael_google_map_type' => 'marker'
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'eael_google_map_maker_search_typography',
				'selector' => '{{WRAPPER}} .eael-google-map-marker-search input, {{WRAPPER}} .eael-google-map-marker-search input::placeholder, {{WRAPPER}} .eael-google-map-marker-search ul li',
			]
		);

		$this->add_control(
			'eael_google_map_maker_search_text_color',
			[
				'label'     => esc_html__( 'Text Color', 'essential-addons-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .eael-google-map-marker-search input' => 'color: {{VALUE}}',
					'{{WRAPPER}} .eael-google-map-marker-search input::placeholder' => 'color: {{VALUE}}',
					'{{WRAPPER}} .eael-google-map-marker-search ul li' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'eael_google_map_maker_search_background',
				'types'    => [ 'classic', 'gradient' ],
				'exclude'  => [ 'image' ],
				'selector' => '{{WRAPPER}} .eael-google-map-marker-search input, {{WRAPPER}} .eael-google-map-marker-search ul li',
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_box_position_x',
			[
				'label'      => __('Position X', 'essential-addons-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'separator'  => 'before',
				'default'    => [
					'unit' => 'px',
					'size' => 10,
				],
				'range'  => [
					'px' => [
						'min'  => 0,
						'max'  => 1400,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map-marker-search' => 'left: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_box_position_Y',
			[
				'label'      => __('Position Y', 'essential-addons-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'separator'  => 'after',
				'default'    => [
					'unit' => '%',
					'size' => 40,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1400,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map-marker-search' => 'top: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_input_width',
			[
				'label'   => __('Width', 'essential-addons-elementor'),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 300,
					'unit' => 'px',
				],
				'size_units' => ['px', '%'],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1400,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map-marker-search' => 'width: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_input_height',
			[
				'label'      => __('Height', 'essential-addons-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'separator'  => 'after',
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1400,
						'step' => 10,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-google-map-marker-search input' => 'height: {{SIZE}}{{UNIT}};',
				]
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'eael_google_map_maker_search_input_border',
				'selector' => '{{WRAPPER}} .eael-google-map-marker-search input',
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_input_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'essential-addons-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .eael-google-map-marker-search input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_input_margin',
			[
				'label'      => esc_html__('Margin', 'essential-addons-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .eael-google-map-marker-search input' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'eael_google_map_maker_search_input_padding',
			[
				'label'      => esc_html__('Padding', 'essential-addons-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .eael-google-map-marker-search input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .eael-google-map-marker-search ul li' => 'padding-left: {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		/**
		 * -------------------------------------------
		 * Tab Style Google Map Style
		 * -------------------------------------------
		 */
		$this->start_controls_section(
			'eael_section_google_map_overlay_style_settings',
			[
				'label' => esc_html__('Overlay Style', 'essential-addons-elementor'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'eael_google_map_type' => ['overlay']
				]
			]
		);
		$this->add_responsive_control(
			'eael_google_map_overlay_width',
			[
				'label' => __('Width', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 200,
					'unit' => 'px',
				],
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1100,
						'step' => 10,
					]
				],
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'width: {{SIZE}}{{UNIT}};',
				]
			]
		);
		$this->add_control(
			'eael_google_map_overlay_bg_color',
			[
				'label' => esc_html__('Background Color', 'essential-addons-elementor'),
				'type' => Controls_Manager::COLOR,
				'default' => '#fff',
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'eael_google_mapoverlay_padding',
			[
				'label' => esc_html__('Padding', 'essential-addons-elementor'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'eael_google_map_overlay_margin',
			[
				'label' => esc_html__('Margin', 'essential-addons-elementor'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'eael_google_map_overlay_border',
				'label' => esc_html__('Border', 'essential-addons-elementor'),
				'selector' => '{{WRAPPER}} .eael-gmap-overlay',
			]
		);
		$this->add_responsive_control(
			'eael_google_map_overlay_border_radius',
			[
				'label' => esc_html__('Border Radius', 'essential-addons-elementor'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'eael_google_map_overlay_box_shadow',
				'selector' => '{{WRAPPER}} .eael-gmap-overlay',
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'eael_google_map_overlay_typography',
				'selector' => '{{WRAPPER}} .eael-gmap-overlay',
			]
		);
		$this->add_control(
			'eael_google_map_overlay_color',
			[
				'label' => esc_html__('Color', 'essential-addons-elementor'),
				'type' => Controls_Manager::COLOR,
				'default' => '#222',
				'selectors' => [
					'{{WRAPPER}} .eael-gmap-overlay' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();

		/**
		 * -------------------------------------------
		 * Tab Style Google Map Stroke Style
		 * -------------------------------------------
		 */
		$this->start_controls_section(
			'eael_section_google_map_stroke_style_settings',
			[
				'label' => esc_html__('Stroke Style', 'essential-addons-elementor'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'eael_google_map_type' => ['polyline', 'polygon', 'routes']
				]
			]
		);
		$this->add_control(
			'eael_google_map_stroke_color',
			[
				'label' => esc_html__('Color', 'essential-addons-elementor'),
				'type' => Controls_Manager::COLOR,
				'default' => '#e23a47',
			]
		);
		$this->add_responsive_control(
			'eael_google_map_stroke_opacity',
			[
				'label' => __('Opacity', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0.8,
				],
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0.2,
						'max' => 1,
						'step' => 0.1,
					]
				],
			]
		);
		$this->add_responsive_control(
			'eael_google_map_stroke_weight',
			[
				'label' => __('Weight', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 4,
				],
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 10,
						'step' => 1,
					]
				],
			]
		);
		$this->add_control(
			'eael_google_map_stroke_fill_color',
			[
				'label' => esc_html__('Fill Color', 'essential-addons-elementor'),
				'type' => Controls_Manager::COLOR,
				'default' => '#e23a47',
				'condition' => [
					'eael_google_map_type' => ['polygon']
				]
			]
		);
		$this->add_responsive_control(
			'eael_google_map_stroke_fill_opacity',
			[
				'label' => __('Fill Opacity', 'essential-addons-elementor'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0.4,
				],
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0.2,
						'max' => 1,
						'step' => 0.1,
					]
				],
				'condition' => [
					'eael_google_map_type' => ['polygon']
				]
			]
		);



		$this->end_controls_section();
	}

	protected function get_map_theme($settings)
	{

		if ($settings['eael_google_map_theme_source'] == 'custom') {
			return wp_strip_all_tags($settings['eael_google_map_custom_style']);
		} else {
			$themes = include('advance-gmap-themes.php');
			if (isset($themes[$settings['eael_google_map_theme_source']][$settings['eael_google_map_gstandards']])) {
				return $themes[$settings['eael_google_map_theme_source']][$settings['eael_google_map_gstandards']];
			} elseif (isset($themes[$settings['eael_google_map_theme_source']][$settings['eael_google_map_snazzymaps']])) {
				return $themes[$settings['eael_google_map_theme_source']][$settings['eael_google_map_snazzymaps']];
			} else {
				return '';
			}
		}
	}

    /**
     * Create Google Map data map for ACF
     */
	protected function get_gmap_acf_data_settings( $settings ) {
		if ( 'acf_repeater' !== ( $settings['eael_gmap_acf_data_source'] ?? 'custom' ) ) {
			return $settings['eael_google_map_markers'] ?? [];
		}

		$field_name = sanitize_text_field( $settings['eael_gmap_acf_repeater_field'] ?? '' );

		if ( empty( $field_name ) || ! function_exists( 'get_field' ) ) {
			return [];
		}

		$lat_key     = sanitize_text_field( $settings[ 'eael_gmap_acf_lat_' . $field_name ] ?? '' );
		$lng_key     = sanitize_text_field( $settings[ 'eael_gmap_acf_lng_' . $field_name ] ?? '' );
		$title_key   = sanitize_text_field( $settings[ 'eael_gmap_acf_title_' . $field_name ] ?? '' );
		$content_key = sanitize_text_field( $settings[ 'eael_gmap_acf_content_' . $field_name ] ?? '' );
		$icon_key    = sanitize_text_field( $settings[ 'eael_gmap_acf_icon_' . $field_name ] ?? '' );
		$extra_keys  = $settings[ 'eael_gmap_acf_extras_' . $field_name ] ?? [];
		$extra_keys  = is_array( $extra_keys ) ? array_map( 'sanitize_text_field', $extra_keys ) : [];

		// Resolve sub-field labels for the selected extra fields.
		$extra_labels = [];
		if ( ! empty( $extra_keys ) && function_exists( 'acf_get_field' ) ) {
			$repeater_field = acf_get_field( $field_name );
			if ( ! empty( $repeater_field['sub_fields'] ) ) {
				foreach ( $repeater_field['sub_fields'] as $sf ) {
					$extra_labels[ $sf['name'] ] = $sf['label'];
				}
			}
		}

		$object_id = get_the_ID() ?: get_queried_object_id();
		$rows      = get_field( $field_name, $object_id );

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return [];
		}

		$markers = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$lat = $lat_key && isset( $row[ $lat_key ] ) && is_scalar( $row[ $lat_key ] ) ? (string) $row[ $lat_key ] : '';
			$lng = $lng_key && isset( $row[ $lng_key ] ) && is_scalar( $row[ $lng_key ] ) ? (string) $row[ $lng_key ] : '';

			if ( '' === $lat || '' === $lng ) {
				continue;
			}

			// ACF image field may return an array, a URL string, or an attachment ID.
			$icon = $icon_key ? ( $row[ $icon_key ] ?? '' ) : '';
			if ( is_array( $icon ) ) {
				$icon_url = $icon['url'] ?? '';
				$icon_id  = $icon['ID'] ?? 0;
			} elseif ( is_numeric( $icon ) ) {
				$icon_id  = (int) $icon;
				$icon_url = wp_get_attachment_image_url( $icon_id, 'full' ) ?: '';
			} else {
				$icon_url = is_string( $icon ) ? $icon : '';
				$icon_id  = 0;
			}

			$content = $content_key && isset( $row[ $content_key ] ) && is_scalar( $row[ $content_key ] ) ? wp_kses_post( (string) $row[ $content_key ] ) : '';

			// Append selected additional fields to the marker info window.
			$extras_html = '';
			foreach ( $extra_keys as $key ) {
				if ( ! isset( $row[ $key ] ) || ! is_scalar( $row[ $key ] ) || '' === $row[ $key ] ) {
					continue;
				}
				$extras_html .= '<li><strong>' . esc_html( $extra_labels[ $key ] ?? $key ) . ':</strong> ' . wp_kses_post( (string) $row[ $key ] ) . '</li>';
			}
			if ( '' !== $extras_html ) {
				$content .= '<ul class="eael-gmap-marker-extras">' . $extras_html . '</ul>';
			}

			$markers[] = [
				'eael_google_map_marker_lat'         => $lat,
				'eael_google_map_marker_lng'         => $lng,
				'eael_google_map_marker_title'       => $title_key && isset( $row[ $title_key ] ) && is_scalar( $row[ $title_key ] ) ? wp_kses_post( (string) $row[ $title_key ] ) : '',
				'eael_google_map_marker_content'     => $content,
				'eael_google_map_marker_icon_enable' => $icon_url ? 'yes' : 'no',
				'eael_google_map_marker_icon'        => [ 'url' => esc_url_raw( $icon_url ), 'id' => $icon_id ],
				'eael_google_map_marker_icon_width'  => 32,
				'eael_google_map_marker_icon_height' => 32,
				'_id'                                => uniqid( 'gmap_', false ),
			];
		}

		return $markers;
	}

	protected function map_render_data_attributes( $settings ) {
        $new_settings = $this->get_settings();
        // WPML Media Translation compatibility
        if ( ! empty( $new_settings['eael_google_map_basic_marker_icon']['id'] ) ) {
            $new_settings['eael_google_map_basic_marker_icon']['id']  = apply_filters( 'wpml_object_id', $new_settings['eael_google_map_basic_marker_icon']['id'], 'attachment', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            $new_settings['eael_google_map_basic_marker_icon']['url'] = wp_get_attachment_url( $new_settings['eael_google_map_basic_marker_icon']['id'] );
        }
		return [
			'data-map_type'				=> esc_attr($settings['eael_google_map_type']),
			'data-map_address_type'		=> esc_attr($settings['eael_google_map_address_type']),
			'data-map_lat'				=> esc_attr($this->get_settings('eael_google_map_lat')),
			'data-map_lng'				=> esc_attr($this->get_settings('eael_google_map_lng')),
			'data-map_addr'				=> esc_attr($settings['eael_google_map_addr']),
			'data-map_basic_marker_title'		=> esc_attr($settings['eael_google_map_basic_marker_title']),
			'data-map_basic_marker_content'		=> esc_attr($settings['eael_google_map_basic_marker_content']),
			'data-map_basic_marker_icon_enable'	=> esc_attr($new_settings['eael_google_map_basic_marker_icon_enable']),
			'data-map_basic_marker_icon'		=> esc_attr($new_settings['eael_google_map_basic_marker_icon']['url']),
			'data-map_basic_marker_icon_width'	=> esc_attr($new_settings['eael_google_map_basic_marker_icon_width']['size']),
			'data-map_basic_marker_icon_height'	=> esc_attr($new_settings['eael_google_map_basic_marker_icon_height']['size']),
			'data-map_zoom'				=> esc_attr($settings['eael_google_map_zoom']),
			'data-map_marker_content'	=> isset($settings['eael_google_map_marker_content']) ? esc_attr($settings['eael_google_map_marker_content']) : '',
			'data-map_markers'				=> urlencode(wp_json_encode($this->get_gmap_acf_data_settings($new_settings))),
			'data-map_static_width'			=> esc_attr($new_settings['eael_google_map_static_width']['size']),
			'data-map_static_height'		=> esc_attr($new_settings['eael_google_map_static_height']['size']),
			'data-map_static_lat'			=> esc_attr($settings['eael_google_map_static_lat']),
			'data-map_static_lng'			=> esc_attr($settings['eael_google_map_static_lng']),
			'data-map_polylines'			=> urlencode(wp_json_encode($settings['eael_google_map_polylines'])),
			'data-map_stroke_color'			=> esc_attr($new_settings['eael_google_map_stroke_color']),
			'data-map_stroke_opacity'		=> esc_attr($new_settings['eael_google_map_stroke_opacity']['size']),
			'data-map_stroke_weight'		=> esc_attr($new_settings['eael_google_map_stroke_weight']['size']),
			'data-map_stroke_fill_color'	=> esc_attr($new_settings['eael_google_map_stroke_fill_color']),
			'data-map_stroke_fill_opacity'	=> esc_attr($new_settings['eael_google_map_stroke_fill_opacity']['size']),
			'data-map_overlay_content'		=> esc_attr($settings['eael_google_map_overlay_content']),
			'data-map_routes_origin_lat'	=> esc_attr($settings['eael_google_map_routes_origin_lat']),
			'data-map_routes_origin_lng'	=> esc_attr($settings['eael_google_map_routes_origin_lng']),
			'data-map_routes_dest_lat'		=> esc_attr($settings['eael_google_map_routes_dest_lat']),
			'data-map_routes_dest_lng'		=> esc_attr($settings['eael_google_map_routes_dest_lng']),
			'data-map_routes_travel_mode'	=> esc_attr($settings['eael_google_map_routes_travel_mode']),
			'data-map_panorama_lat'			=> esc_attr($settings['eael_google_map_panorama_lat']),
			'data-map_panorama_lng'			=> esc_attr($settings['eael_google_map_panorama_lng']),
			'data-map_theme'				=> urlencode(wp_json_encode($this->get_map_theme($settings))),
			'data-map_streeview_control'	=> ($settings['eael_map_streeview_control'] ? 'true' : 'false'),
			'data-map_type_control'			=> ($settings['eael_map_type_control'] ? 'true' : 'false'),
			'data-map_zoom_control'			=> ($settings['eael_map_zoom_control'] ? 'true' : 'false'),
			'data-map_fullscreen_control'	=> ($settings['eael_map_fullscreen_control'] ? 'true' : 'false'),
			'data-map_scroll_zoom'			=> ($settings['eael_map_scroll_zoom'] ? 'true' : 'false')
		];
	}

	protected function print_map_render_data_attribute_string($settings)
	{
		$data_attributes = $this->map_render_data_attributes($settings);

		foreach ($data_attributes as $key => $value) {
			if (isset($key) && !empty($value)) {
				echo ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
	}

	protected function get_polyline_center_point( $obj ) {
		$center_type = $obj->get_settings_for_display( 'eael_google_map_center_point' );

		if ( $center_type === 'auto' ) {
			$poly_lines = $obj->get_settings_for_display( 'eael_google_map_polylines' );

			if ( ! empty( $poly_lines ) ) {
				$lat_array = wp_list_pluck( $poly_lines, 'eael_google_map_polyline_lat' );
				$lng_array = wp_list_pluck( $poly_lines, 'eael_google_map_polyline_lng' );
			} else {
				$multy_lines = $obj->get_gmap_acf_data_settings( $obj->get_settings() );
				$lat_array   = wp_list_pluck( $multy_lines, 'eael_google_map_marker_lat' );
				$lng_array   = wp_list_pluck( $multy_lines, 'eael_google_map_marker_lng' );
			}

			if ( empty( $lat_array ) || empty( $lng_array ) ) {
				return [ 'center_lat' => '', 'center_lng' => '' ];
			}

			$center_lat = ( max( $lat_array ) + min( $lat_array ) ) / 2;
			$center_lng = ( max( $lng_array ) + min( $lng_array ) ) / 2;
		} elseif ( $center_type === 'custom' ) {
			$center_lat = $obj->get_settings_for_display( 'eael_google_map_center_point_lat' );
			$center_lng = $obj->get_settings_for_display( 'eael_google_map_center_point_lng' );
		} else {
			$center_lat = $center_lng = '';
		}

		return [ 'center_lat' => $center_lat, 'center_lng' => $center_lng ];
	}

	protected function render()
	{

        $settings = $this->get_settings_for_display();

        // ACF Repeater source always renders as a Multiple Marker map (General Settings / map type are hidden in this mode).
        if ( 'acf_repeater' === ( $settings['eael_gmap_acf_data_source'] ?? 'custom' ) ) {
            $settings['eael_google_map_type'] = 'marker';
        }

        $poly_line_center = $this->get_polyline_center_point($this);
		$this->add_render_attribute( 'eael_google_map_wrap', [
			'class'               => [ 'eael-google-map', 'eael-google-map-' . $settings['eael_google_map_type'] ],
			'id'                  => 'eael-google-map-' . esc_attr( $this->get_id() ),
			'data-id'             => esc_attr( $this->get_id() ),
			'data-map-center-lat' => $poly_line_center['center_lat'],
			'data-map-center-lng' => $poly_line_center['center_lng'],
		] );
?>

		<?php if (!empty($settings['eael_google_map_type'])) : ?>
		
			<?php if( isset( $settings['enable_marker_search'] ) && 'yes' === $settings['enable_marker_search'] ){
				$section_id = $this->get_id();
				echo "<div class='eael-google-map-marker-search' id='eael-google-map-" . esc_attr( $section_id ) ."-search'>";
				echo "<input type='text' id='eael-google-map-" . esc_attr( $section_id ) ."-markers-input' placeholder='". esc_attr( $settings['marker_search_text'] ) ."'>";
				echo "<ul id='eael-google-map-" . esc_attr( $section_id ) ."-markers'></ul>";
				echo "</div>";
			} ?>

			<div <?php 
				$this->print_render_attribute_string('eael_google_map_wrap'); 
				$this->print_map_render_data_attribute_string($settings); // Already Escaped
				?>></div>
		<?php endif; ?>
		<div class="google-map-notice"></div>
<?php
	}

	protected function content_template()
	{
	}
}
