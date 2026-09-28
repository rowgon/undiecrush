<?php
/**
 * Edit vendor fields page template ( store admin )
 *
 * @version 2.0.16
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-description"><?php esc_html_e( 'Registration Description', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'This is the business overview provided by the vendor during registration. It contains details about their company history, products, and business experience.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<div class="registration-description">
			<i><?php echo esc_html( $description ); ?></i>
			<input type="hidden" name="vendor_data[description]" value="<?php echo esc_attr( $description ); ?>" />
		</div>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-logo"><?php esc_html_e( 'Vendor Logo', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'The vendor\'s logo that will be displayed on their public page and products. Recommended size: 400x100px.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<input type="hidden" name="vendor_data[logo]" value="<?php echo esc_attr( $logo ); ?>" />
		<button class="wcpv-upload-logo button" aria-haspopup="true"><?php esc_html_e( 'Upload Logo', 'woocommerce-product-vendors' ); ?></button>
		<br />
		<div class="wcpv-logo-preview-wrapper">
			<?php if ( is_array( $logo_image_url ) && ! empty( $logo_image_url ) ) { ?>
					<img src="<?php echo esc_url( $logo_image_url[0] ); ?>" class="wcpv-logo-preview-image" alt="<?php esc_attr_e( 'Vendor logo preview', 'woocommerce-product-vendors' ); ?>" />
			<?php } else { ?>
					<img src="" class="wcpv-logo-preview-image hide" alt="" />
			<?php } ?>
			<a href="#" class="wcpv-remove-image dashicons dashicons-no" style="<?php echo esc_attr( $hide_remove_image_link ); ?>" title="<?php esc_attr_e( 'Remove logo image', 'woocommerce-product-vendors' ); ?>"></a>
		</div>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-profile"><?php esc_html_e( 'Public Vendor Profile', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'This is the public profile that customers will see on the vendor\'s page. It should highlight their brand, products, and what makes them unique.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<?php
			$args = array(
				'textarea_name' => 'vendor_data[profile]',
				'textarea_rows' => 5,
			);

			wp_editor( htmlspecialchars_decode( $profile, ENT_COMPAT ), 'wcpv_vendor_info', $args );
		?>
		<p class="description"><?php esc_html_e( 'This profile will be displayed to customers on the vendor\'s public page.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-email"><?php esc_html_e( 'Vendor Email', 'woocommerce-product-vendors' ); ?> <span class="required">*</span> <?php echo wc_help_tip( esc_html__( 'The primary email address for vendor communications. Multiple addresses can be added for order notifications and customer inquiries.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<input type="email" multiple name="vendor_data[email]" required aria-required="true" value="<?php echo esc_attr( $email ); ?>" />
		<p class="description"><?php esc_html_e( 'Vendor notifications including orders and customer inquiries will be sent to this email address. For multiple recipients, separate email addresses with commas.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-admins"><?php esc_html_e( 'Vendor Administrators', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Users who can manage this vendor\'s products, orders, and settings. Each admin can have different access levels.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<select id="wcpv-vendor-admins" style="width: 95%; max-width: 25em;" class="wc-customer-search" name="vendor_data[admins][]" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Search for Users', 'woocommerce-product-vendors' ); ?>">
			<?php
				foreach ( $selected_admins as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $value ) . '</option>';
				}
			?>
		</select>
		<p class="description"><?php esc_html_e( 'Select users who will have administrative access to manage this vendor.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr>
	<th colspan="2">
		<h2><?php esc_html_e( 'Commission Settings', 'woocommerce-product-vendors' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Choose how commissions are calculated for this vendor. Product level commissions that have already been set will only be updated for commission (if no specific value was set on the product), commission type, and tax handling. Changing passing shipping on this page will only be applied to products created after updating.', 'woocommerce-product-vendors' ); ?>
		</p>
	</th>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-commission-type"><?php esc_html_e( 'Commission Type', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Choose how the commission will be calculated - either as a percentage of the product price or a fixed amount per product.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<select id="wcpv-vendor-commission-type" name="vendor_data[commission_type]" style="width:20%">
			<option value="percentage" <?php selected( 'percentage', $commission_type ); ?>><?php esc_html_e( 'Percentage', 'woocommerce-product-vendors' ); ?></option>
			<option value="fixed" <?php selected( 'fixed', $commission_type ); ?>><?php esc_html_e( 'Fixed', 'woocommerce-product-vendors' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Select whether the commission will be a percentage of product or a fixed amount per product.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-commission"><?php esc_html_e( 'Commission Amount', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'The commission amount the vendor will receive for each sale. This can be overridden at the product level.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<input type="number" id="wcpv-vendor-commission" name="vendor_data[commission]" value="<?php echo esc_attr( $commission ); ?>" step="any" min="0" style="width:20%" />
		<p class="description"><?php esc_html_e( 'Enter the commission amount. For percentage, enter a number between 0-100. For fixed amount, enter the monetary value.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-pass-shipping"><?php esc_html_e( 'Shipping Charges', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Determine if shipping charges should be passed to the vendor or kept by the marketplace.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>
	<td>
		<label for="wcpv-pass-shipping">
			<input type="checkbox" id="wcpv-pass-shipping" name="vendor_data[pass_shipping]" <?php checked( 'yes', $pass_shipping ); ?> />
			<?php esc_html_e( 'Pass shipping charges to vendor', 'woocommerce-product-vendors' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, the vendor will receive the shipping charges for their products.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-tax-handling"><?php esc_html_e( 'Tax Handling', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Choose how taxes should be handled when calculating vendor commissions.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<fieldset id="wcpv-tax-handling">
			<div class="tax-option">
				<label for="wcpv-tax-keep">
					<input type="radio" id="wcpv-tax-keep" value="keep-tax" name="vendor_data[taxes]" <?php checked( 'keep-tax', $taxes ); ?> />
					<span class="tax-option-title"><?php esc_html_e( 'Keep Taxes', 'woocommerce-product-vendors' ); ?></span>
					<span class="description"><?php esc_html_e( 'Calculate commission based on product price only.', 'woocommerce-product-vendors' ); ?></span>
				</label>
			</div>

			<div class="tax-option">
				<label for="wcpv-tax-pass">
					<input type="radio" id="wcpv-tax-pass" value="pass-tax" name="vendor_data[taxes]" <?php checked( 'pass-tax', $taxes ); ?> />
					<span class="tax-option-title"><?php esc_html_e( 'Pass Taxes', 'woocommerce-product-vendors' ); ?></span>
					<span class="description"><?php esc_html_e( 'Include all tax charges in the vendor\'s commission.', 'woocommerce-product-vendors' ); ?></span>
				</label>
			</div>

			<div class="tax-option">
				<label for="wcpv-tax-split">
					<input type="radio" id="wcpv-tax-split" value="split-tax" name="vendor_data[taxes]" <?php checked( 'split-tax', $taxes ); ?> />
					<span class="tax-option-title"><?php esc_html_e( 'Split Taxes', 'woocommerce-product-vendors' ); ?></span>
					<span class="description"><?php esc_html_e( 'Calculate commission using the full price including taxes.', 'woocommerce-product-vendors' ); ?></span>
				</label>
			</div>
		</fieldset>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-instant-payout"><?php esc_html_e( 'Payment Method', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Choose whether to pay commissions instantly via PayPal or through scheduled payouts.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>
	<td>
		<label for="wcpv-vendor-instant-payout">
			<input type="checkbox" id="wcpv-vendor-instant-payout" name="vendor_data[instant_payout]" <?php checked( 'yes', $instant_payout ); ?> />
			<?php esc_html_e( 'Enable instant PayPal payouts', 'woocommerce-product-vendors' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, commissions will be paid instantly through PayPal when the order is paid.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-paypal"><?php esc_html_e( 'PayPal Email', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'The PayPal email address where commission payments will be sent.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<input type="email" id="wcpv-vendor-paypal" name="vendor_data[paypal]" value="<?php echo esc_attr( $paypal ); ?>" />
		<p class="description"><?php esc_html_e( 'Enter the PayPal email address for receiving commission payments.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<tr>
	<th colspan="2">
		<h2><?php esc_html_e( 'Additional Settings', 'woocommerce-product-vendors' ); ?></h2>
	</th>
</tr>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-vendor-timezone"><?php esc_html_e( 'Vendor Timezone', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Set the local timezone for the vendor to ensure accurate order processing times and reports.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>

	<td>
		<select id="wcpv-vendor-timezone" name="vendor_data[timezone]" aria-describedby="timezone-description" class="wc-enhanced-select">
			<?php echo wp_timezone_choice( $tzstring ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the timezone for vendor operations and reporting.', 'woocommerce-product-vendors' ); ?></p>
	</td>
</tr>

<?php if ( WC_Product_Vendors_Utils::is_wcpv_per_product_shipping_enabled() ) { ?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="wcpv-per-product-shipping"><?php esc_html_e( 'Product Shipping Rules', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Allow vendor to set custom shipping rules for individual products.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
		</th>
		<td>
			<label for="wcpv-per-product-shipping">
				<input type="checkbox" id="wcpv-per-product-shipping" name="vendor_data[per_product_shipping]" <?php checked( 'yes', $per_product_shipping ); ?> />
				<?php esc_html_e( 'Enable per-product shipping rules', 'woocommerce-product-vendors' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'When enabled, the vendor can set specific shipping costs for individual products.', 'woocommerce-product-vendors' ); ?></p>
		</td>
	</tr>
<?php } ?>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wcpv-enable-bookings"><?php esc_html_e( 'Bookable Products', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Enable this to allow the vendor to create and manage bookable products (requires WooCommerce Bookings).', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
	</th>
	<td>
		<label for="wcpv-enable-bookings">
			<input type="checkbox" id="wcpv-enable-bookings" name="vendor_data[enable_bookings]" <?php checked( 'yes', $enable_bookings ); ?> />
			<?php esc_html_e( 'Enable bookings feature', 'woocommerce-product-vendors' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Allow vendor to create bookable products like classes, appointments, or rentals. Requires WooCommerce Bookings plugin.', 'woocommerce-product-vendors' ); ?> <a href="https://woocommerce.com/products/woocommerce-bookings/" target="_blank"><?php esc_html_e( 'Learn more', 'woocommerce-product-vendors' ); ?></a></p>
	</td>
</tr>
