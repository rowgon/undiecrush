<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}


if ( isset( woof()->settings['by_sku'] ) and woof()->settings['by_sku']['show'] ) {
	if ( isset( woof()->settings['by_sku']['title'] ) and ! empty( woof()->settings['by_sku']['title'] ) ) {
		?>
		<!-- <<?php echo esc_attr( apply_filters( 'woof_title_tag', 'h4' ) ); ?>><?php echo esc_html( woof()->settings['by_sku']['title'] ); ?></<?php echo esc_attr( apply_filters( 'woof_title_tag', 'h4' ) ); ?>> -->
		<?php
	}
	echo do_shortcode( '[woof_sku_filter]' );
}

