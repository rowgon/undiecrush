<?php
/**
 * Vendor registration email to admin (plain text).
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Hello! A vendor has requested to be registered.', 'woocommerce-product-vendors' ) . "\n\n";

echo esc_html__( 'Vendor Information', 'woocommerce-product-vendors' ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo esc_html( sprintf( __( 'Vendor Name', 'woocommerce-product-vendors' ) . ': %s', $vendor_name ) ) . "\n\n";
echo esc_html( sprintf( __( 'Business Overview', 'woocommerce-product-vendors' ) . ': %s', wp_strip_all_tags( $vendor_desc ) ) ) . "\n\n";
echo esc_html( sprintf( __( 'Public Vendor Profile', 'woocommerce-product-vendors' ) . ': %s', wp_strip_all_tags( $vendor_profile ) ) ) . "\n\n";

echo esc_html__( 'Contact Information', 'woocommerce-product-vendors' ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo esc_html( sprintf( __( 'First Name', 'woocommerce-product-vendors' ) . ': %s', $first_name ) ) . "\n\n";
echo esc_html( sprintf( __( 'Last Name', 'woocommerce-product-vendors' ) . ': %s', $last_name ) ) . "\n\n";
echo esc_html( sprintf( __( 'Username', 'woocommerce-product-vendors' ) . ': %s', $user_login ) ) . "\n\n";
echo esc_html( sprintf( __( 'Email', 'woocommerce-product-vendors' ) . ': %s', $user_email ) ) . "\n\n";

if ( isset( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
	$remote_addr = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

	/* translators: 1: registration date and time, 2: IP address */
	printf(
		esc_html__( 'Registration form submitted on %1$s from IP address %2$s', 'woocommerce-product-vendors' ) . "\n\n",
		esc_html( current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
		esc_html( $remote_addr )
	);
}

/* translators: %s is the pending vendors list url. */
printf( esc_html__( 'You can approve this vendor at %s.', 'woocommerce-product-vendors' ), esc_url( admin_url( 'users.php?role=wc_product_vendors_pending_vendor' ) ) );

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
