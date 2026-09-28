<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Vendor Registration.
 *
 * Handles the vendor form registration process .
 *
 * @category Registration
 * @package  WooCommerce Product Vendors/Registration
 * @version  2.0.0
 */
class WC_Product_Vendors_Registration {
	/**
	 * Stores registration form errors
	 *
	 * @var array
	 */
	private $form_errors = array();

	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_wc_product_vendors_registration', array( $this, 'registration_ajax' ) );
			add_action( 'wp_ajax_nopriv_wc_product_vendors_registration', array( $this, 'registration_ajax' ) );

			// Create user if vendor creating from admin dashboard.
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'add-tag'  ) {
				add_action( 'created_' . WC_PRODUCT_VENDORS_TAXONOMY, array( $this, 'create_user_on_vendor_term_creation' ), 100 );
			}
		}

		add_action( 'init', array( $this, 'register_vendor_registration_block' ) );
		add_action( 'after_setup_theme', array( $this, 'enqueue_block_style' ) );
	}

	/**
	 * Add scripts
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_scripts() {
		// Get the current global post object.
		$post = get_post();

		$is_admin = is_admin();
		$has_post = ! empty( $post );

		$has_registration_shortcode = $has_post && has_shortcode( $post->post_content, 'wcpv_registration' );
		$has_registration_block     = $has_post && has_block( 'woocommerce-product-vendors/vendor-registration-form' );

		if ( ! ( $is_admin || $has_registration_shortcode || $has_registration_block ) ) {
			// Do nothing if not admin and there is no global post or the shortcode is not present.
			return;
		}

		wp_enqueue_script( 'wcpv-frontend-scripts' );

		$localized_vars = array(
			'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
			'ajaxRegistrationNonce'      => wp_create_nonce( '_wc_product_vendors_registration_nonce' ),
			'success'                    => __( 'Your request has been submitted.  You will be contacted shortly.', 'woocommerce-product-vendors' ),
			/* translators: %s is the text format */
			'text_format_dropdown_label' => __( 'Text format: %s', 'woocommerce-product-vendors' ),
		);

		wp_localize_script( 'wcpv-frontend-scripts', 'wcpv_registration_local', $localized_vars );

		return true;
	}

	/**
	 * Handles the registration via AJAX
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function registration_ajax() {
		if ( ! isset( $_POST['form_items'] ) ) {
			return false;
		}
		if ( ! is_array( $_POST['form_items'] ) ) {
			parse_str( $_POST['form_items'], $form_items ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- POST data will be sanitized and unslashed below.
		} else {
			$form_items = $_POST['form_items']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- POST data will be sanitized and unslashed below.
		}

		// Save vendor profile separately to preserve HTML.
		$vendor_profile = isset( $form_items['vendor_profile'] ) ? wp_kses_post( wp_unslash( $form_items['vendor_profile'] ) ) : '';

		// Sanitize all other fields.
		$form_items = array_map(
			function( $item ) {
				return sanitize_text_field( wp_unslash( $item ) );
			},
			$form_items // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- POST data is both sanitized and unslashed above.
		);

		// Restore vendor profile with HTML preserved.
		$form_items['vendor_profile'] = $vendor_profile;

		$this->registration_form_validation( $form_items );

		return true;
	}

	/**
	 * Enqueue assets to register Vendor Registration Block.
	 */
	public function register_vendor_registration_block() {
		register_block_type( WC_PRODUCT_VENDORS_PATH . '/build/blocks/vendor-registration-form' );
	}

	/**
	 * Loads the vendor registration styles inside the editor.
	 */
	public function enqueue_block_style() {
		add_editor_style( includes_url( 'js/tinymce/skins/lightgray/skin.min.css' ) );
		add_editor_style( WC_PRODUCT_VENDORS_PLUGIN_URL . '/build/frontend/wcpv-frontend-scripts.css' );
	}

	/**
	 * Includes the registration form
	 *
	 * Also allows for the form template to be overwritten.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.1.5
	 */
	public function include_form() {
		wc_get_template( 'shortcode-registration-form.php', array(), 'woocommerce-product-vendors', WC_PRODUCT_VENDORS_TEMPLATES_PATH );
	}

	/**
	 * Validates the registration form
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_items forms items to validate
	 * @return bool
	 */
	public function registration_form_validation( $form_items = array() ) {
		if ( ! isset( $form_items ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( ! isset( $_POST['ajaxRegistrationNonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajaxRegistrationNonce'] ) ), '_wc_product_vendors_registration_nonce' ) ) {
			wp_die( esc_html__( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		// handle form submission/validation.
		if ( ! empty( $form_items ) ) {
			$this->form_errors = array();

			$is_username_exist = ! empty( $form_items['username'] ) && username_exists( $form_items['username'] );
			$is_email_exist    = ! empty( $form_items['email'] ) && false !== email_exists( $form_items['email'] );

			// Vendor fields validation.
			if ( empty( $form_items['vendor_name'] ) ) {
				$this->form_errors['vendor_name'] = __( 'Vendor Name is a required field.', 'woocommerce-product-vendors' );
			} elseif ( ! empty( $form_items['vendor_name'] ) && term_exists( $form_items['vendor_name'], WC_PRODUCT_VENDORS_TAXONOMY ) ) {
				$this->form_errors['vendor_name'] = __( 'Sorry that vendor name already exists. Please enter a different one.', 'woocommerce-product-vendors' );
			}

			if ( empty( $form_items['vendor_description'] ) ) {
				$this->form_errors['vendor_description'] = __( 'Business Overview is a required field.', 'woocommerce-product-vendors' );
			}

			if ( empty( $form_items['vendor_profile'] ) ) {
				$this->form_errors['vendor_profile'] = __( 'Public Vendor Profile is a required field.', 'woocommerce-product-vendors' );
			}

			// Contact Information validation.
			if ( ! is_user_logged_in() ) {
				if ( empty( $form_items['firstname'] ) ) {
					$this->form_errors['firstname'] = __( 'First Name is a required field.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['lastname'] ) ) {
					$this->form_errors['lastname'] = __( 'Last Name is a required field.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['username'] ) ) {
					$this->form_errors['username'] = __( 'Username is a required field.', 'woocommerce-product-vendors' );
				} elseif ( ! validate_username( $form_items['username'] ) ) {
					$this->form_errors['username'] = __( 'Please enter a valid username.', 'woocommerce-product-vendors' );
				} elseif ( $is_username_exist ) {
					$this->form_errors['username'] = __( 'Please choose a different username.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['email'] ) ) {
					$this->form_errors['email'] = __( 'Email is a required field.', 'woocommerce-product-vendors' );
				} elseif ( ! filter_var( $form_items['email'], FILTER_VALIDATE_EMAIL ) ) {
					$this->form_errors['email'] = __( 'Email is not valid.', 'woocommerce-product-vendors' );
				} elseif ( $is_email_exist ) {
					$this->form_errors['email'] = __( 'Email already exists in our system.', 'woocommerce-product-vendors' );
				}

				if ( empty( $form_items['confirm_email'] ) ) {
					$this->form_errors['confirm_email'] = __( 'Confirm email is a required field.', 'woocommerce-product-vendors' );
				} elseif ( $form_items['confirm_email'] !== $form_items['email'] ) {
					$this->form_errors['confirm_email'] = __( 'Emails must match.', 'woocommerce-product-vendors' );
				}

				// Add login message if user exists.
				if ( $is_username_exist || $is_email_exist ) {
					$this->form_errors['wp_user_exists'] = sprintf(
						__(
							'If you are already an existing user on the site, please %1$slog in%2$s before registering as a Vendor.',
							'woocommerce-product-vendors'
						),
						'<a href="' . esc_url( wp_login_url( wp_get_referer() ) ) . '">',
						'</a>'
					);
				}
			}

			/**
			 * This hook allows adding custom validation rules for the vendor registration form.
			 * Any validation errors should be added to the $form_errors array.
			 *
			 * @since 2.3.5
			 *
			 * @param array $form_errors Array of current validation errors.
			 * @param array $form_items  Sanitized form items from the registration form.
			 */
			do_action( 'wcpv_shortcode_registration_form_validation', $this->form_errors, $form_items );

			/**
			 * Filter the registration form validation errors.
			 *
			 * @since 2.3.5
			 *
			 * @param array $form_errors Array of validation errors.
			 * @param array $form_items  Sanitized form items from the registration form.
			 */
			$this->form_errors = apply_filters( 'wcpv_shortcode_registration_form_validation_errors', $this->form_errors, $form_items );

			// no errors, lets process the form
			if ( empty( $this->form_errors ) ) {
				if ( is_user_logged_in() ) {
					$this->vendor_registration_form_process( $form_items );
				} else {
					$this->vendor_user_registration_form_process( $form_items );
				}
			} else {
				wp_send_json( array( 'errors' => $this->form_errors ) );
			}
		}
	}

	/**
	 * Process the registration for a vendor.
	 *
	 * @since 2.0.41
	 * @version 2.0.41
	 * @param array   $form_items Sanitized form items
	 * @param WP_User $user       WP User
	 * @param array   $args
	 * @return bool
	 */
	protected function register_vendor( $form_items, $user, $args = array() ) {
		$term_args = apply_filters( 'wcpv_registration_term_args', $args, $form_items );

		// add vendor name to taxonomy.
		$term = wp_insert_term( $form_items['vendor_name'], WC_PRODUCT_VENDORS_TAXONOMY, $term_args );

		// no errors, term added, continue.
		if ( ! is_wp_error( $term ) && ! empty( $user ) ) {
			// add user to term meta
			$vendor_data = array();

			// Set vendor data.
			$vendor_data['email']                = $user->user_email;
			$vendor_data['admins']               = $user->ID;
			$vendor_data['profile']              = isset( $form_items['vendor_profile'] ) ? wp_kses_post( $form_items['vendor_profile'] ) : '';
			$vendor_data['description']          = isset( $form_items['vendor_description'] ) ? wp_kses_post( $form_items['vendor_description'] ) : '';
			$vendor_data['commission_type']      = 'percentage';
			$vendor_data['per_product_shipping'] = 'yes';

			WC_Product_Vendors_Utils::set_vendor_data(
				$term['term_id'],
				apply_filters( 'wcpv_registration_default_vendor_data', $vendor_data )
			);

			// change this user's role to pending vendor.
			wp_update_user( apply_filters( 'wcpv_registration_default_user_data', array(
				'ID'   => $user->ID,
				'role' => 'wc_product_vendors_pending_vendor',
			) ) );

			// Add new pending vendor to list.
			WC_Product_Vendors_Utils::set_new_pending_vendor( $user->ID );

			$default_args = array(
				'user_id'        => $user->ID,
				'user_email'     => $user->user_email,
				'first_name'     => $user->user_firstname,
				'last_name'      => $user->user_lastname,
				'user_login'     => $user->user_login,
				'user_pass'      => __( 'Same as your account password', 'woocommerce-product-vendors' ),
				'vendor_name'    => $form_items['vendor_name'],
				'vendor_desc'    => isset( $form_items['vendor_description'] ) ? wp_kses_post( $form_items['vendor_description'] ) : '',
				'vendor_profile' => isset( $form_items['vendor_profile'] ) ? wp_kses_post( $form_items['vendor_profile'] ) : '',
			);

			$args = apply_filters( 'wcpv_registration_args', wp_parse_args( $args, $default_args ), $args, $default_args );

			do_action( 'wcpv_shortcode_registration_form_process', $args, $form_items );

			echo 'success';
			exit;
		} else {
			if ( is_wp_error( $user ) ) {
				$this->form_errors[] = $user->get_error_message();
			}

			if ( is_wp_error( $term ) ) {
				$this->form_errors[] = $term->get_error_message();
			}

			wp_send_json( array( 'errors' => $this->form_errors ) );
		}

		return true;
	}

	/**
	 * Process the registration form for just vendor.
	 * As in they already have a user account on the site.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.41
	 * @param array $form_items sanitized form items
	 * @return bool
	 */
	public function vendor_registration_form_process( $form_items ) {
		return $this->register_vendor( $form_items, wp_get_current_user() );
	}

	/**
	 * Process the registration form for vendor and user
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.41
	 * @param array $form_items sanitized form items
	 * @return bool
	 */
	public function vendor_user_registration_form_process( $form_items ) {
		$username    = $form_items['username'];
		$email       = $form_items['email'];
		$firstname   = $form_items['firstname'];
		$lastname    = $form_items['lastname'];

		$password = wp_generate_password();

		$args = apply_filters( 'wcpv_shortcode_register_vendor_args', array(
			'user_login'      => $username,
			'user_email'      => $email,
			'user_pass'       => $password,
			'first_name'      => $firstname,
			'last_name'       => $lastname,
			'display_name'    => $firstname,
			'role'            => 'wc_product_vendors_pending_vendor',
		) );

		$user_id            = wp_insert_user( $args );
		$user               = get_user_by( 'id', $user_id );
		$password_reset_key = get_password_reset_key( $user );

		$args['password_reset_key'] = $password_reset_key;

		return $this->register_vendor( $form_items, $user, $args );
	}

	/**
	 * Create user on vendor creation through admin dashboard
	 *
	 * @access public
	 * @param int $term_id newly created term id
	 * @return void
	 */
	public function create_user_on_vendor_term_creation( $term_id ) {
		// Get term and vendor data.
		$term         = get_term( $term_id );
		$vendor_data  = WC_Product_Vendors_Utils::get_vendor_data_by_id( $term_id );
		$vendor_data  = ! is_array( $vendor_data ) ? array() : $vendor_data;
		$vendor_email = empty( $vendor_data['email'] ) ? null : $vendor_data['email'];

		// No need to create user if the email is already associated with any account.
		if ( ! $vendor_email || email_exists( $vendor_email ) ) {
			return;
		}

		$args = apply_filters( 'wcpv_admin_register_vendor_args', array(
			'user_login'    =>  $term->slug,
			'user_email'    =>  $vendor_email,
			'user_pass'     =>  wp_generate_password(),
			'first_name'    =>  $term->name,
			'display_name'  =>  $term->name,
			'role'          =>  'wc_product_vendors_admin_vendor',
		) );

		// Create.
		$user_id = wp_insert_user( $args );

		// Make the user admin of the vendor.
		if ( $user_id ) {
			$admins                = ( empty( $vendor_data['admins'] ) || ! is_array( $vendor_data['admins'] ) ) ? array() : $vendor_data['admins'];
			$admins[]              = $user_id;
			$vendor_data['admins'] = array_unique( $admins );

			WC_Product_Vendors_Utils::set_vendor_data( $term_id, $vendor_data );
		}
	}
}
