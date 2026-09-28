<?php
/**
 * Report class responsible for adding reports.
 *
 * @since      2.0.0
 *
 * @package    WooCommerce Product Vendors
 * @subpackage WooCommerce Product Vendors/Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Product_Vendors_Store_Reports' ) ) :

	/**
	 * WC_Product_Vendors_Store_Reports Class
	 */
	class WC_Product_Vendors_Store_Reports {
		/**
		 * Constructor
		 *
		 * @since 2.0.0
		 * @version 2.0.0
		 */
		public function __construct() {
			// Add reports.
			add_filter( 'woocommerce_admin_reports', array( $this, 'add_report' ), 11 );
		}

		/**
		 * Get a report from our reports subfolder
		 *
		 * @param string $name Report name.
		 * @throws Exception If the file could not be read.
		 */
		public static function get_report( $name ) {
			$name = sanitize_title( str_replace( '_', '-', $name ) );

			$class = 'WC_Product_Vendors_Store_Report_' . str_replace( '-', '_', $name );

			/**
			 * Filter the path to the store reports.
			 *
			 * @since 2.0.0
			 * @param string $path Path to the store reports.
			 * @param string $name Report name.
			 * @param string $class Class name.
			 */
			include_once apply_filters( 'wcpv_store_reports_path', 'class-wc-product-vendors-store-report-' . $name . '.php', $name, $class ); // nosemgrep:audit.php.lang.security.file.inclusion-arg -- already checked.

			if ( ! class_exists( $class ) ) {
				return;
			}

			$report = new $class();
			$report->output_report();
		}

		/**
		 * Add vendor report
		 *
		 * @since 2.0.0
		 * @version 2.0.0
		 * @param array $reports Reports.
		 * @return array $reports Updated reports.
		 */
		public function add_report( $reports ) {
			$reports['vendors'] = array(
				'title'   => __( 'Vendors', 'woocommerce-product-vendors' ),
				'reports' => array(
					'sales_by_date' => array(
						'title'       => __( 'Sales by date', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
			);

			return $reports;
		}
	}

	new WC_Product_Vendors_Store_Reports();
endif;
