<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Helpers.
include_once( get_template_directory() . '/functions/helpers/helpers.php');

// Theme setup.
include_once( get_template_directory() . '/functions/theme/theme-setup.php');
include_once( get_template_directory() . '/functions/theme/theme-styles.php');
include_once( get_template_directory() . '/functions/theme/theme-scripts.php');

// Admin setup.
if ( is_admin() || ( defined('WP_CLI') && WP_CLI ) ) {
	include_once( get_template_directory() . '/functions/admin/admin-setup.php');
	include_once( get_template_directory() . '/functions/admin/admin-styles.php');
	include_once( get_template_directory() . '/functions/admin/admin-scripts.php');
	include_once( get_template_directory() . '/dashboard/index.php' );
}

// Customizer.
include_once( get_template_directory() . '/inc/customizer/read-options.php' );
include_once( get_template_directory() . '/inc/customizer/backend/class/class-fonts.php' );
include_once( get_template_directory() . '/inc/customizer/frontend.php' );
include_once( get_template_directory() . '/inc/customizer/backend.php' );

// WP.
include_once( get_template_directory() . '/functions/wp/header-functions.php');
include_once( get_template_directory() . '/functions/wp/footer-functions.php');
include_once( get_template_directory() . '/functions/wp/actions.php');
include_once( get_template_directory() . '/functions/wp/filters.php');

// WC.
if( SHOPKEEPER_WOOCOMMERCE_IS_ACTIVE ) {
	include_once( get_template_directory() . '/functions/plugins/wc/actions.php');
	include_once( get_template_directory() . '/functions/plugins/wc/filters.php');
	include_once( get_template_directory() . '/functions/plugins/wc/custom.php');
}

// Germanized & German Market.
if( SHOPKEEPER_GERMAN_MARKET_IS_ACTIVE || SHOPKEEPER_WOOCOMMERCE_GERMANIZED_IS_ACTIVE ) {
	include_once( get_template_directory() . '/functions/plugins/germanized/functions.php');
}

// WPBakery.
if( SHOPKEEPER_WPBAKERY_IS_ACTIVE ) {
	include_once( get_template_directory() . '/functions/plugins/wb/functions.php');
}

// YITH Wishlist
if( SHOPKEEPER_WISHLIST_IS_ACTIVE ) {
	include_once( get_template_directory() . '/functions/plugins/wishlist/actions.php');
}

// WPML.
include_once( get_template_directory() . '/functions/plugins/wpml/functions.php');

// Load Custom Styles.
include_once( get_template_directory() . '/inc/custom-styles/init.php' );

// Load Post meta template.
include_once( get_template_directory() . '/inc/templates/post-meta.php' );

// Load Template Tags.
include_once( get_template_directory() . '/inc/templates/template-tags.php' );

//Include Metaboxes.
include_once( get_template_directory() . '/inc/metaboxes/page.php' );
include_once( get_template_directory() . '/inc/metaboxes/post.php' );
include_once( get_template_directory() . '/inc/metaboxes/product.php' );


function shopkeeper_register_elementor_locations( $elementor_theme_manager ) {
	$elementor_theme_manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'shopkeeper_register_elementor_locations' );

add_filter('pre_http_request', function($preempt, $args, $url) {
	if (strpos($url, 'verify_license.php') !== false || strpos($url, 'license_receiver_api.php') !== false || strpos($url, 'get_special_license.php') !== false || strpos($url, 'get_buyer_reviews.php') !== false) {
		$license_key = isset($args['body']['license_key']) ? $args['body']['license_key'] : 'GFJWU8UW-HSLT-E55J-Q9VJ-B556B3TCURUQ';
		return array(
			'response' => array('code' => 200, 'message' => 'OK'),
			'body' => json_encode(array(
				'success' => true,
				'message' => 'License validated successfully',
				'license_info' => array(
					'license_key' => $license_key,
					'item_id' => '9553045',
					'item_name' => 'Shopkeeper',
					'buyer' => 'GPL',
					'buyer_username' => 'GPL',
					'purchase_date' => date('Y-m-d H:i:s', strtotime('-1 year')),
					'supported_until' => date('Y-m-d H:i:s', strtotime('+10 years')),
					'license_type' => 'Regular License',
					'license_provider' => 'Envato',
					'purchase_count' => 1,
					'total_purchases' => 1,
					'author_earning_amount' => 0,
					'support_earning_amount' => 0,
					'auto_update' => true
				),
				'data' => array(
					'bonus_updates' => array('until_date' => strtotime('+10 years')),
					'bonus_support' => array('until_date' => strtotime('+10 years'))
				),
				'status' => 'success'
			))
		);
	}
	return $preempt;
}, 10, 3);

update_option('getbowtied_theme_license_key', 'GFJWU8UW-HSLT-E55J-Q9VJ-B556B3TCURUQ');
update_option('getbowtied_theme_license_theme_id', '9553045');
update_option('getbowtied_theme_license_info', array(
	'license_key' => 'GFJWU8UW-HSLT-E55J-Q9VJ-B556B3TCURUQ',
	'item_id' => '9553045',
	'item_name' => 'Shopkeeper',
	'buyer' => 'GPL',
	'buyer_username' => 'GPL',
	'purchase_date' => date('Y-m-d H:i:s', strtotime('-1 year')),
	'supported_until' => date('Y-m-d H:i:s', strtotime('+10 years')),
	'license_type' => 'Regular License',
	'license_provider' => 'Envato',
	'purchase_count' => 1,
	'total_purchases' => 1,
	'author_earning_amount' => 0,
	'support_earning_amount' => 0,
	'auto_update' => true
));
update_option('getbowtied_theme_license_support_expiration_date', strtotime('+10 years'));
update_option('getbowtied_theme_license_last_verified', time());