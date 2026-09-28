<?php
/**
 * Vendor registration email to admin.
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p style="font-size: 1.17em;"><strong><?php esc_html_e( 'Hello! A vendor has requested to be registered.', 'woocommerce-product-vendors' ); ?></strong></p>

<h4><?php esc_html_e( 'Vendor Information', 'woocommerce-product-vendors' ); ?></h4>
<ul>
	<li><b><?php printf( esc_html__( 'Vendor Name', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( stripslashes( $vendor_name ) ) ); ?></li>
	<li><b><?php printf( esc_html__( 'Business Overview', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( stripslashes( $vendor_desc ) ) ); ?></li>
	<li><b><?php printf( esc_html__( 'Public Vendor Profile', 'woocommerce-product-vendors' ) . ':</b> %s', wp_kses_post( stripslashes( $vendor_profile ) ) ); ?></li>
</ul>

<h4><?php esc_html_e( 'Contact Information', 'woocommerce-product-vendors' ); ?></h4>
<ul>
	<li><b><?php printf( esc_html__( 'First Name', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( $first_name ) ); ?></li>
	<li><b><?php printf( esc_html__( 'Last Name', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( $last_name ) ); ?></li>
	<li><b><?php printf( esc_html__( 'Username', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( $user_login ) ); ?></li>
	<li><b><?php printf( esc_html__( 'Email', 'woocommerce-product-vendors' ) . ':</b> %s', esc_html( $user_email ) ); ?></li>
</ul>

<?php if ( isset( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) { ?>

<p><i>
	<?php
	$remote_addr = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	/* translators: 1: registration date and time, 2: IP address */
	printf(
		esc_html__( 'Registration form submitted on %1$s from IP address %2$s', 'woocommerce-product-vendors' ),
		'<b>' . esc_html( current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</b>',
		'<b>' . esc_html( $remote_addr ) . '</b>'
	);
	?>
</i></p>

<?php } ?>

<?php /* translators: %1$s is the pending vendors list url. */ ?>
<p><?php printf( wp_kses_post( __( 'You can approve this vendor at <a href="%1$s">%1$s</a>.', 'woocommerce-product-vendors' ) ), esc_url( admin_url( 'users.php?role=wc_product_vendors_pending_vendor' ) ) ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
