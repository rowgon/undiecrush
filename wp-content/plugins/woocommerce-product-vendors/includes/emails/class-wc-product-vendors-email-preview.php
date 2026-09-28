<?php
/**
 * Product Vendors Email Preview Class
 *
 * @package WooCommerce Product Vendors/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Vendors Email Preview Class
 */
class WC_Product_Vendors_Email_Preview {
	/**
	 * The email being previewed
	 *
	 * @var string
	 */
	private $email_type;

	/**
	 * Initialize the class
	 */
	public function init() {
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );
	}

	/**
	 * Prepare email for preview
	 *
	 * @param WC_Email $email Email object.
	 * @return WC_Email
	 */
	public function prepare_email_for_preview( $email ) {
		$this->email_type = get_class( $email );

		switch ( $this->email_type ) {
			case 'WC_Product_Vendors_Registration_Email_To_Admin':
				$vendor_data        = $this->get_dummy_vendor_data();
				$email->user_email  = $vendor_data['user_email'];
				$email->first_name  = $vendor_data['first_name'];
				$email->last_name   = $vendor_data['last_name'];
				$email->vendor_name = $vendor_data['vendor_name'];
				$email->vendor_desc = $vendor_data['vendor_desc'];
				break;

			case 'WC_Product_Vendors_Registration_Email_To_Vendor':
				$vendor_data               = $this->get_dummy_vendor_data();
				$email->user_login         = $vendor_data['user_login'];
				$email->password_reset_key = 'dummy_password_reset_key';
				break;

			case 'WC_Product_Vendors_Product_Added_Notice':
				$vendor_data         = $this->get_dummy_vendor_data();
				$email->vendor_name  = $vendor_data['vendor_name'];
				$email->product_name = $this->get_dummy_product()->get_name();
				$email->product_link = admin_url( 'post.php?post=' . absint( $this->get_dummy_product()->get_id() ) . '&action=edit' );
				break;
			case 'WC_Product_Vendors_Order_Note_To_Customer':
				$email->note = esc_attr__( 'This is a dummy note.', 'woocommerce-product-vendors' );
				break;

			case 'WC_Product_Vendors_Order_Fulfill_Status_To_Admin':
				$email->order           = $this->get_dummy_order();
				$email->vendor_name     = esc_attr__( 'Dummy Vendor Name', 'woocommerce-product-vendors' );
				$email->fulfill_status  = esc_attr__( 'fulfilled', 'woocommerce-product-vendors' );
				$email->order_item_name = $this->get_dummy_product()->get_name();
				break;
		}

		return $email;
	}

	/**
	 * Get a dummy order object without the need to create in the database.
	 *
	 * @return WC_Order
	 */
	private function get_dummy_order() {
		$product   = $this->get_dummy_product();
		$variation = $this->get_dummy_product_variation();

		$order = new WC_Order();
		if ( $product ) {
			$order->add_product( $product, 2 );
		}
		if ( $variation ) {
			$order->add_product( $variation );
		}
		$order->set_id( 12345 );
		$order->set_date_created( time() );
		$order->set_currency( 'USD' );
		$order->set_discount_total( 10 );
		$order->set_shipping_total( 5 );
		$order->set_total( 65 );
		$order->set_payment_method_title( __( 'Direct bank transfer', 'woocommerce-product-vendors' ) );
		$order->set_customer_note( __( "This is a customer note. Customers can add a note to their order on checkout.\n\nIt can be multiple lines. If there’s no note, this section is hidden.", 'woocommerce-product-vendors' ) );

		$address = $this->get_dummy_address();
		$order->set_billing_address( $address );
		$order->set_shipping_address( $address );

		/**
		 * A dummy WC_Order used in email preview.
		 *
		 * @param WC_Order $order      The dummy order object.
		 * @param string   $email_type The email type to preview.
		 *
		 * @since 2.4.0
		 */
		return apply_filters( 'wcpv_email_preview_dummy_order', $order, $this->email_type );
	}

	/**
	 * Get a dummy product.
	 *
	 * @return WC_Product
	 */
	private function get_dummy_product() {
		$product = new WC_Product();
		$product->set_name( __( 'Dummy Product', 'woocommerce-product-vendors' ) );
		$product->set_price( 25 );

		/**
		 * A dummy WC_Product used in email preview.
		 *
		 * @param WC_Product $product    The dummy product object.
		 * @param string     $email_type The email type to preview.
		 *
		 * @since 2.4.0
		 */
		return apply_filters( 'wcpv_email_preview_dummy_product', $product, $this->email_type );
	}

	/**
	 * Get a dummy product variation.
	 *
	 * @return WC_Product_Variation
	 */
	private function get_dummy_product_variation() {
		$variation = new WC_Product_Variation();
		$variation->set_name( __( 'Dummy Product Variation', 'woocommerce-product-vendors' ) );
		$variation->set_price( 20 );
		$variation->set_attributes(
			array(
				__( 'Color', 'woocommerce-product-vendors' ) => __( 'Red', 'woocommerce-product-vendors' ),
				__( 'Size', 'woocommerce-product-vendors' )  => __( 'Small', 'woocommerce-product-vendors' ),
			)
		);

		/**
		 * A dummy WC_Product_Variation used in email preview.
		 *
		 * @param WC_Product_Variation $variation  The dummy product variation object.
		 * @param string               $email_type The email type to preview.
		 *
		 * @since 2.4.0
		 */
		return apply_filters( 'wcpv_email_preview_dummy_product_variation', $variation, $this->email_type );
	}

	/**
	 * Get a dummy address.
	 *
	 * @return array
	 */
	private function get_dummy_address() {
		$address = array(
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'company'    => 'Company',
			'email'      => 'john@company.com',
			'phone'      => '555-555-5555',
			'address_1'  => '123 Fake Street',
			'city'       => 'Faketown',
			'postcode'   => '12345',
			'country'    => 'US',
			'state'      => 'CA',
		);

		/**
		 * A dummy address used in email preview as billing and shipping one.
		 *
		 * @param array  $address    The dummy address.
		 * @param string $email_type The email type to preview.
		 *
		 * @since 2.4.0
		 */
		return apply_filters( 'wcpv_email_preview_dummy_address', $address, $this->email_type );
	}

	/**
	 * Get a dummy vendor data.
	 *
	 * @return array
	 */
	private function get_dummy_vendor_data() {
		$vendor_data = array(
			'user_id'     => 0,
			'user_email'  => 'john@company.com',
			'first_name'  => 'John',
			'last_name'   => 'Doe',
			'user_login'  => __( 'Same as your account login', 'woocommerce-product-vendors' ),
			'user_pass'   => __( 'Same as your account password', 'woocommerce-product-vendors' ),
			'vendor_name' => 'Dummy Vendor name',
			'vendor_desc' => 'Dummy Vendor description',
		);

		/**
		 * A dummy vendor data used in email preview.
		 *
		 * @param array  $vendor_data The dummy vendor object.
		 * @param string $email_type  The email type to preview.
		 *
		 * @since 2.4.0
		 */
		return apply_filters( 'wcpv_email_preview_dummy_vendor_data', $vendor_data, $this->email_type );
	}
}
