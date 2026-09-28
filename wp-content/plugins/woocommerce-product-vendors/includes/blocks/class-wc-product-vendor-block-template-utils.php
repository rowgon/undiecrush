<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1);

use Automattic\WooCommerce\Blocks\Options;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch

/**
 * BlockTemplateUtils class used for serving block templates from Woo Blocks.
 * IMPORTANT: These methods have been duplicated from Gutenberg/lib/full-site-editing/block-templates.php as those functions are not for public usage.
 *
 * For internal use only.
 *
 * Based on https://github.com/woocommerce/woocommerce/blob/release/10.0/plugins/woocommerce/includes/blocks/class-wc-brands-block-template-utils-duplicated.php.
 */
class WC_Product_Vendor_Block_Template_Utils {

	/**
	 * WooCommerce plugin slug
	 *
	 * This is used to save templates to the DB which are stored against this value in the wp_terms table.
	 *
	 * @var string
	 */
	protected const PLUGIN_SLUG = 'woocommerce/woocommerce';

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function gutenberg_flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}
		$queue_count = count( $queue );

		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}

			$queue_count = count( $queue );
		}

		return $all_blocks;
	}

	/**
	 * Parses wp_template content and injects the current theme's
	 * stylesheet as a theme attribute into each wp_template_part
	 *
	 * @param string $template_content serialized wp_template content.
	 *
	 * @return string Updated wp_template content.
	 */
	public static function gutenberg_inject_theme_attribute_in_content( $template_content ) {
		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $template_content );

		$blocks = self::gutenberg_flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			if (
				'core/template-part' === $block['blockName'] &&
				! isset( $block['attrs']['theme'] )
			) {
				$block['attrs']['theme'] = wp_get_theme()->get_stylesheet();
				$has_updated_content     = true;
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $template_content;
	}

	/**
	 * Build a unified template object based on a theme file.
	 *
	 * @param array|object $template_file Theme file.
	 * @param string       $template_type wp_template or wp_template_part.
	 *
	 * @return \WP_Block_Template Template.
	 */
	public static function gutenberg_build_template_result_from_file( $template_file, $template_type ) {
		$template_file = (object) $template_file;

		// If the theme has an archive-products.html template but does not have product taxonomy templates
		// then we will load in the archive-product.html template from the theme to use for product taxonomies on the frontend.
		$template_is_from_theme = 'theme' === $template_file->source;
		$theme_name             = wp_get_theme()->get( 'TextDomain' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template_content  = file_get_contents( $template_file->path );
		$template          = new \WP_Block_Template();
		$template->id      = $template_is_from_theme ? $theme_name . '//' . $template_file->slug : self::PLUGIN_SLUG . '//' . $template_file->slug;
		$template->theme   = $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG;
		$template->content = self::gutenberg_inject_theme_attribute_in_content( $template_content );
		// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
		$template->source         = $template_file->source ? $template_file->source : 'plugin';
		$template->slug           = $template_file->slug;
		$template->type           = $template_type;
		$template->title          = ! empty( $template_file->title ) ? $template_file->title : self::convert_slug_to_title( $template_file->slug );
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = $template_file->source;
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = 'uncategorized';
		return $template;
	}


	/**
	 * Converts template slugs into readable titles.
	 *
	 * @param string $template_slug The templates slug (e.g. single-product).
	 * @return string Human friendly title converted from the slug.
	 */
	public static function convert_slug_to_title( $template_slug ) {
		switch ( $template_slug ) {
			case 'single-product':
				return __( 'Single Product', 'woocommerce-product-vendors' );
			case 'archive-product':
				return __( 'Product Archive', 'woocommerce-product-vendors' );
			case 'taxonomy-product_cat':
				return __( 'Product Category', 'woocommerce-product-vendors' );
			case 'taxonomy-product_tag':
				return __( 'Product Tag', 'woocommerce-product-vendors' );
			case 'taxonomy-product_brand':
				return __( 'Products by Brand', 'woocommerce-product-vendors' );
			default:
				// Replace all hyphens and underscores with spaces.
				return ucwords( preg_replace( '/[\-_]/', ' ', $template_slug ) );
		}
	}


	/**
	 * Build a new template object so that we can make Woo Blocks default templates available in the current theme should they not have any.
	 *
	 * @param string $template_file Block template file path.
	 * @param string $template_type wp_template or wp_template_part.
	 * @param string $template_slug Block template slug e.g. single-product.
	 * @param bool   $template_is_from_theme If the block template file is being loaded from the current theme instead of Woo Blocks.
	 *
	 * @return object Block template object.
	 */
	public static function create_new_block_template_object( $template_file, $template_type, $template_slug, $template_is_from_theme = false ) {
		$theme_name = wp_get_theme()->get( 'TextDomain' );

		$new_template_item = array(
			'slug'        => $template_slug,
			'id'          => $template_is_from_theme ? $theme_name . '//' . $template_slug : self::PLUGIN_SLUG . '//' . $template_slug,
			'path'        => $template_file,
			'type'        => $template_type,
			'theme'       => $template_is_from_theme ? $theme_name : self::PLUGIN_SLUG,
			// Plugin was agreed as a valid source value despite existing inline docs at the time of creating: https://github.com/WordPress/gutenberg/issues/36597#issuecomment-976232909.
			'source'      => $template_is_from_theme ? 'theme' : 'plugin',
			'title'       => self::convert_slug_to_title( $template_slug ),
			'description' => '',
			'post_types'  => array(), // Don't appear in any Edit Post template selector dropdown.
		);

		return (object) $new_template_item;
	}

	/**
	 * Returns whether the blockified templates should be used or not.
	 *
	 * If the option is not stored on the db, we need to check if the current theme is a block one or not.
	 *
	 * @return boolean
	 */
	public static function should_use_blockified_product_grid_templates() {
		$use_blockified_templates = wc_string_to_bool( get_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE ) );

		if ( false === $use_blockified_templates ) {
			return wp_is_block_theme();
		}

		return $use_blockified_templates;
	}
}
