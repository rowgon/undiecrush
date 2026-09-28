<?php
/**
 * Output for the `Vendor Registration Block`.
 *
 * @package WooCommerce Product Vendors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shortcode = new WC_Product_Vendors_Shortcodes();
echo $shortcode->render_registration_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
