<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

switch ( getbowtied_product_layout(get_the_ID()) )
{        
    case "default":
        get_template_part('woocommerce/single-product/product-image-default');
        break;
    case "style_2":
    case "style_3":
    case "cascade":
        get_template_part('woocommerce/single-product/product-image-cascade');
        break;
    case "style_4":
    case "scattered":
        get_template_part('woocommerce/single-product/product-image-scattered');
        break;
    default:
        get_template_part('woocommerce/single-product/product-image-default');
        break;
}