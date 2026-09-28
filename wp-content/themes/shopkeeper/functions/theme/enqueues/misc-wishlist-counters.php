<?php
	
function shopkeeper_enqueue_misc_wishlist_counters() {
	if ( ! SHOPKEEPER_WISHLIST_IS_ACTIVE ) {
		return;
	}

	wp_enqueue_script(
		'shopkeeper-misc-wishlist-counters',
		get_template_directory_uri() . '/js/public/misc-wishlist-counters.js',
		array( 'jquery' ),
		shopkeeper_theme_version(),
		true
	);

	wp_localize_script(
		'shopkeeper-misc-wishlist-counters',
		'shopkeeperWishlistCounters',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'action'   => 'shopkeeper_get_wishlist_count',
			'nonce'    => wp_create_nonce( 'shopkeeper_get_wishlist_count' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'shopkeeper_enqueue_misc_wishlist_counters' );

/**
 * AJAX handler to retrieve the wishlist items count.
 */
function shopkeeper_ajax_get_wishlist_count() {
	check_ajax_referer( 'shopkeeper_get_wishlist_count', 'nonce' );

	$count = function_exists( 'yith_wcwl_count_all_products' ) ? (int) yith_wcwl_count_all_products() : 0;

	wp_send_json_success( array( 'count' => $count ) );
}
add_action( 'wp_ajax_shopkeeper_get_wishlist_count', 'shopkeeper_ajax_get_wishlist_count' );
add_action( 'wp_ajax_nopriv_shopkeeper_get_wishlist_count', 'shopkeeper_ajax_get_wishlist_count' );
