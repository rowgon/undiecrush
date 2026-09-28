<?php
/**
 * Product Vendors block templates class.
 *
 * @package WooCommerce Product Vendors/Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Utils for compatibility with WooCommerce Full Site Editor Blocks.
 *
 * @version 2.4.5
 */
class WC_Product_Vendor_Block_Templates {

	/**
	 * Constructor.
	 *
	 * @since 2.4.5
	 */
	public function __construct() {
		add_action( 'get_block_templates', array( $this, 'get_block_templates' ), 10, 3 );
		add_filter( 'get_block_file_template', array( $this, 'get_block_file_template' ), 10, 3 );
		add_filter( 'woocommerce_has_block_template', array( $this, 'has_block_template' ), 10, 2 );

		add_shortcode( 'wcpv_vendor_details', array( $this, 'wcpv_vendor_details_shortcode' ) );
	}

	/**
	 * Fixes a bug regarding taxonomies and FSE.
	 * Without this, the system will always load archive-product.php version instead of taxonomy_wcpv_product_vendors.html
	 * it will show a deprecation error if that happens.
	 *
	 * @since 2.4.5
	 *
	 * @param bool   $has_template  True if the template is available.
	 * @param string $template_name The name of the template.
	 * @return bool True if the system is checking archive-product
	 */
	public function has_block_template( $has_template, $template_name ) {
		if ( 'archive-product' === $template_name || 'taxonomy-wcpv_product_vendors' === $template_name ) {
			$has_template = true;
		}

		return $has_template;
	}

	/**
	 * Get the block template for Taxonomy Product Vendors. First it attempts to load the last version from DB
	 * Otherwise it loads the file based template.
	 *
	 * @since 2.4.5
	 *
	 * @param string $template_type The post_type for the template. Normally wp_template or wp_template_part.
	 * @return WP_Block_Template The taxonomy-wcpv_product_vendors template.
	 */
	private function get_product_vendors_template( $template_type ) {
		$template_path = WC_Product_Vendor_Block_Template_Utils::should_use_blockified_product_grid_templates()
			? WC_PRODUCT_VENDORS_TEMPLATES_PATH . 'templates/blockified/taxonomy-wcpv_product_vendors.html'
			: WC_PRODUCT_VENDORS_TEMPLATES_PATH . 'templates/taxonomy-wcpv_product_vendors.html';

		$template_file = WC_Product_Vendor_Block_Template_Utils::create_new_block_template_object( $template_path, $template_type, 'taxonomy-wcpv_product_vendors', false );

		return WC_Product_Vendor_Block_Template_Utils::gutenberg_build_template_result_from_file( $template_file, $template_type );
	}

	/**
	 * Function to check if a template name is woocommerce/taxonomy-wcpv_product_vendors.
	 *
	 * @since 2.4.5
	 *
	 * @param  String $id The string to check if contains the template name.
	 * @return bool True if the template is woocommerce/taxonomy-wcpv_product_vendors
	 */
	private function is_taxonomy_product_vendors_template( $id ) {
		return strpos( $id, 'woocommerce//taxonomy-wcpv_product_vendors' ) !== false;
	}

	/**
	 * Get the block template for Taxonomy Product Vendors if requested.
	 * Triggered by get_block_file_template action
	 *
	 * @since 2.4.5
	 *
	 * @param WP_Block_Template|null $block_template The current Block Template loaded, if any.
	 * @param string                 $id The template id normally in the format theme-slug//template-slug.
	 * @param string                 $template_type The post_type for the template. Normally wp_template or wp_template_part.
	 * @return WP_Block_Template|null The taxonomy-wcpv_product_vendors template.
	 */
	public function get_block_file_template( $block_template, $id, $template_type ) {
		if ( $this->is_taxonomy_product_vendors_template( $id ) && is_null( $block_template ) ) {
			$block_template = $this->get_product_vendors_template( $template_type );
		}

		return $block_template;
	}

	/**
	 * Add the Block template in the template query results needed by FSE
	 * Triggered by get_block_templates action
	 *
	 * @since 2.4.5
	 *
	 * @param array  $query_result The list of templates to render in the query.
	 * @param array  $query The current query parameters.
	 * @param string $template_type The post_type for the template. Normally wp_template or wp_template_part.
	 * @return WP_Block_Template[] Array of the matched Block Templates to render.
	 */
	public function get_block_templates( $query_result, $query, $template_type ) {
		// We don't want to run this if we are looking for template-parts. Like the header.
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		$post_id = isset( $_REQUEST['postId'] ) ? wc_clean( wp_unslash( $_REQUEST['postId'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slugs   = $query['slug__in'] ?? array();

		// Only add the template if  asking for Product Vendors.
		if (
			in_array( 'taxonomy-wcpv_product_vendors', $slugs, true ) ||
			( ! $post_id && ! count( $slugs ) ) ||
			( ! count( $slugs ) && $this->is_taxonomy_product_vendors_template( $post_id ) )
		) {
			$query_result[] = $this->get_product_vendors_template( $template_type );
		}

		return $query_result;
	}

	/**
	 * Displays a current vendors details based on the specified attributes.
	 *
	 * @since 2.4.5
	 *
	 * @param array $atts {
	 *     Optional. An array of vendor-specific attributes to customize the output.
	 *     Default empty array.
	 *
	 *     @type bool $show_logo    Whether to display the vendor's logo. Default true.
	 *     @type bool $show_ratings Whether to display the vendor's ratings. Default true.
	 *     @type bool $show_profile Whether to display the vendor's profile. Default true.
	 * }
	 * @return string The generated HTML for the vendor.
	 */
	public function wcpv_vendor_details_shortcode( $atts ) {
		global $wp_query;

		$term = $wp_query->queried_object;

		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'show_logo'    => true,
				'show_ratings' => true,
				'show_profile' => true,
			),
			$atts,
			'wcpv_vendor_details'
		);

		$show_logo    = filter_var( $atts['show_logo'], FILTER_VALIDATE_BOOLEAN );
		$show_ratings = filter_var( $atts['show_ratings'], FILTER_VALIDATE_BOOLEAN );
		$show_profile = filter_var( $atts['show_profile'], FILTER_VALIDATE_BOOLEAN );

		$output = '';
		if ( is_tax( WC_PRODUCT_VENDORS_TAXONOMY, $term->term_id ) ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $term->term_id );

			// Vendor logo.
			if ( $show_logo && ! empty( $vendor_data['logo'] ) && 'yes' === get_option( 'wcpv_vendor_settings_vendor_display_logo', 'yes' ) ) {
				$output .= '<p class="wcpv-vendor-logo">' . wp_get_attachment_image( absint( $vendor_data['logo'] ), 'full' ) . '</p>';
			}

			// Vendor ratings.
			$show_ratings_option = get_option( 'wcpv_vendor_settings_vendor_review', 'yes' );

			if ( $show_ratings && 'yes' === $show_ratings_option ) {
				$output .= WC_Product_Vendors_Utils::get_vendor_rating_html( $term->term_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// Vendor profile.
			if ( $show_profile && ! empty( $vendor_data['profile'] ) && 'yes' === get_option( 'wcpv_vendor_settings_vendor_display_profile', 'yes' ) ) {
				$vendor_frontend_instance = new WC_Product_Vendors_Vendor_Frontend();
				add_filter( 'wp_kses_allowed_html', array( $vendor_frontend_instance, 'kses_allowed_html' ), 10, 2 );
				$output .= wpautop( wp_kses_post( do_shortcode( $vendor_data['profile'] ) ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				remove_filter( 'wp_kses_allowed_html', array( $vendor_frontend_instance, 'kses_allowed_html' ) );
			}
		}
		return $output;
	}
}

new WC_Product_Vendor_Block_Templates();
