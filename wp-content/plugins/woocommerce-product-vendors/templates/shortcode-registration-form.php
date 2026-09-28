<?php
/**
 * Vendor Registration Form Template
 *
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Verify nonce earlier.
$is_valid_nonce = false;
if ( isset( $_POST['wcpv_vendor_registration_nonce'] ) ) {
	$is_valid_nonce = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcpv_vendor_registration_nonce'] ) ), 'wcpv_vendor_registration' ); // nosemgrep:scanner.php.wp.security.csrf.verify-nonce-not-in-statement -- already checked.
}

$is_editor     = is_admin() || wp_is_serving_rest_request();
$disabled_attr = $is_editor ? 'disabled' : '';

?>

<div class="wcpv-registration-wrapper">
	<div class="wcpv-messages" role="alert" aria-live="polite"></div>

	<header class="wcpv-registration-header">
		<h1 id="registration-title"><?php esc_html_e( 'Apply to Become a Vendor', 'woocommerce-product-vendors' ); ?></h1>

		<div class="wcpv-registration-info" id="registration-info">
			<p><?php esc_html_e( 'Join our marketplace and start selling your products. Please fill out the form below to begin your vendor application.', 'woocommerce-product-vendors' ); ?></p>
		</div>
	</header>

	<main>
		<form class="wcpv-shortcode-registration-form" novalidate aria-labelledby="registration-title" aria-describedby="registration-info" role="form">
			<?php wp_nonce_field( 'wcpv_vendor_registration', 'wcpv_vendor_registration_nonce' ); ?>
			<?php
			/**
			 * Hook: wcpv_registration_form_start.
			 *
			 * Allows adding custom fields, notices, or any HTML content at the start of the vendor registration form.
			 *
			 * @since 2.3.5
			 */
			do_action( 'wcpv_registration_form_start' );
			?>

			<fieldset class="wcpv-form-section vendor-info" aria-labelledby="vendor-info-heading" aria-describedby="vendor-info-desc" <?php echo esc_attr( $disabled_attr ); ?>>
				<legend id="vendor-info-heading"><?php esc_html_e( 'Vendor Information', 'woocommerce-product-vendors' ); ?></legend>
				<p id="vendor-info-desc" class="screen-reader-text"><?php esc_html_e( 'Please provide your business details below. Required fields are marked with an asterisk (*).', 'woocommerce-product-vendors' ); ?></p>

				<p class="form-row form-row-wide">
					<label for="wcpv-vendor-name"><?php esc_html_e( 'Vendor Name', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
					<input class="input-text" type="text" name="vendor_name" id="wcpv-vendor-name" value="<?php
					if ( $is_valid_nonce && ! empty( $_POST['vendor_name'] ) ) {
						echo esc_attr( sanitize_text_field( wp_unslash( $_POST['vendor_name'] ) ) );
					}
					?>" required aria-required="true" aria-describedby="vendor-name-desc" autocomplete="organization" />
					<span class="description" id="vendor-name-desc"><?php esc_html_e( 'This name will appear as "Sold by: Vendor Name" throughout the marketplace. Choose a professional name that customers will recognize and trust.', 'woocommerce-product-vendors' ); ?></span>
				</p>

				<p class="form-row form-row-wide">
					<label for="wcpv-vendor-description"><?php esc_html_e( 'Business Overview (not visible to customers)', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
					<textarea class="input-text" name="vendor_description" id="wcpv-vendor-description" rows="3" required aria-required="true" aria-describedby="vendor-desc-help" aria-label="<?php esc_attr_e( 'Business Overview (not visible to customers)', 'woocommerce-product-vendors' ); ?>" autocomplete="off"><?php
					if ( $is_valid_nonce && ! empty( $_POST['vendor_description'] ) ) {
						echo esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['vendor_description'] ) ) );
					}
					?></textarea>
					<span class="description" id="vendor-desc-help"><?php esc_html_e( 'Help us understand your business better. Include details about your company history, product range, business experience, areas of expertise, target market, and what makes your business unique. This information is for internal review only.', 'woocommerce-product-vendors' ); ?></span>
				</p>

				<p class="form-row form-row-wide vendor-profile-label">
					<label for="wcpv-vendor-profile"><?php esc_html_e( 'Public Vendor Profile (publicly visible)', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
					<?php
					/* translators: %s: Vendor profile help text */
					$profile_help = __( 'Create an engaging profile that showcases your brand. Highlight your unique value proposition, product quality, customer service standards, and what sets you apart from other vendors. This description will be visible to all customers on your public profile page.', 'woocommerce-product-vendors' );

					$editor_settings = array(
						'textarea_name'    => 'vendor_profile',
						'textarea_rows'    => 10,
						'media_buttons'    => true,
						'teeny'            => false,
						'dfw'              => true,
						'editor_height'    => 100,
						'editor_class'     => 'required',
						'aria_label'       => __( 'Public Vendor Profile (publicly visible)', 'woocommerce-product-vendors' ),
						'editor_css'       => '<style>#wp-wcpv-vendor-profile-wrap { margin-top: 5px; }</style>',
						'aria_describedby' => 'vendor-profile-help',
					);

					$content = '';
					if ( $is_valid_nonce && ! empty( $_POST['vendor_profile'] ) ) {
						$content = wp_kses_post( wp_unslash( $_POST['vendor_profile'] ) );
					}
					?>
					<a href="#" 
						role="button" 
						class="wcpv-tinymce-keyboard-help-button" 
						aria-haspopup="dialog" 
						aria-label="<?php esc_attr_e( 'Keyboard Shortcuts', 'woocommerce-product-vendors' ); ?>"
					>
						<?php esc_html_e( 'Keyboard Shortcuts', 'woocommerce-product-vendors' ); ?>
					</a>
					<?php
					wp_editor( $content, 'wcpv-vendor-profile', $editor_settings );
					?>
					<span class="description" id="vendor-profile-help"><?php echo esc_html( $profile_help ); ?></span>
				</p>
			</fieldset>

			<?php if ( ! is_user_logged_in() || $is_editor ) : ?>
				<fieldset class="wcpv-form-section contact-info" aria-labelledby="contact-info-heading" aria-describedby="contact-info-desc" <?php echo esc_attr( $disabled_attr ); ?>>
					<legend id="contact-info-heading"><?php esc_html_e( 'Contact Information', 'woocommerce-product-vendors' ); ?></legend>
					<p id="contact-info-desc" class="screen-reader-text"><?php esc_html_e( 'Please provide your contact details below. Required fields are marked with an asterisk (*).', 'woocommerce-product-vendors' ); ?></p>

					<p class="form-row form-row-first">
						<label for="wcpv-firstname"><?php esc_html_e( 'First Name', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
						<input type="text" class="input-text" name="firstname" id="wcpv-firstname" value="<?php
						if ( $is_valid_nonce && ! empty( $_POST['firstname'] ) ) {
							echo esc_attr( sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) );
						}
						?>" required aria-required="true" autocomplete="given-name" />
					</p>

					<p class="form-row form-row-last">
						<label for="wcpv-lastname"><?php esc_html_e( 'Last Name', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
						<input type="text" class="input-text" name="lastname" id="wcpv-lastname" value="<?php
						if ( $is_valid_nonce && ! empty( $_POST['lastname'] ) ) {
							echo esc_attr( sanitize_text_field( wp_unslash( $_POST['lastname'] ) ) );
						}
						?>" required aria-required="true" autocomplete="family-name" />
					</p>

					<div class="clear"></div>

					<p class="form-row form-row-wide">
						<label for="wcpv-username"><?php esc_html_e( 'Username', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
						<input type="text" class="input-text" name="username" id="wcpv-username" value="<?php
						if ( $is_valid_nonce && ! empty( $_POST['username'] ) ) {
							echo esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) );
						}
						?>" required aria-required="true" aria-describedby="username-desc" autocomplete="username" />
						<span class="description" id="username-desc"><?php esc_html_e( 'This will be your login username', 'woocommerce-product-vendors' ); ?></span>
					</p>

					<p class="form-row form-row-first">
						<label for="wcpv-email"><?php esc_html_e( 'Email Address', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
						<input type="email" class="input-text" name="email" id="wcpv-email" value="<?php
						if ( $is_valid_nonce && ! empty( $_POST['email'] ) ) {
							echo esc_attr( sanitize_email( wp_unslash( $_POST['email'] ) ) );
						}
						?>" required aria-required="true" autocomplete="email" />
					</p>

					<p class="form-row form-row-last">
						<label for="wcpv-confirm-email"><?php esc_html_e( 'Confirm Email', 'woocommerce-product-vendors' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'required', 'woocommerce-product-vendors' ); ?>">*</span></label>
						<input type="email" class="input-text" name="confirm_email" id="wcpv-confirm-email" value="<?php
						if ( $is_valid_nonce && ! empty( $_POST['confirm_email'] ) ) {
							echo esc_attr( sanitize_email( wp_unslash( $_POST['confirm_email'] ) ) );
						}
						?>" required aria-required="true" autocomplete="email" />
					</p>
				</fieldset>
			<?php endif; ?>

			<?php
			/**
			 * Hook: wcpv_registration_form.
			 *
			 * Allows adding custom fields, notices, or any HTML content at the end of the vendor registration form.
			 *
			 * @since 2.0.0
			 */
			do_action( 'wcpv_registration_form' );
			?>

			<p class="form-row submit-button">
				<button type="submit" class="button button-primary" name="register" value="register" <?php echo esc_attr( $disabled_attr ); ?>>
					<?php esc_html_e( 'Submit Application', 'woocommerce-product-vendors' ); ?>
				</button>
			</p>

			<?php
			/**
			 * Hook: wcpv_registration_form_end.
			 *
			 * Allows adding custom fields, notices, or any HTML content after the submit button.
			 *
			 * @since 2.0.0
			 */
			do_action( 'wcpv_registration_form_end' );
			?>
		</form>
	</main>
</div>
