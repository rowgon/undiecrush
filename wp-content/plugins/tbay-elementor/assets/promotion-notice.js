( function( $ ) {
	'use strict';
	$( document ).on( 'click', '.tbay-elementor-promo-notice__dismiss', function() {
		var $notice = $( this ).closest( '.tbay-elementor-promo-notice' );
		$notice.fadeTo( 150, 0, function() {
			$notice.slideUp( 150, function() { $notice.remove(); } );
		} );
		$.post( tbayElementorPromotionNotice.ajaxUrl, {
			action: 'tbay_elementor_dismiss_promotion_notice',
			nonce: tbayElementorPromotionNotice.nonce,
			promotion_id: tbayElementorPromotionNotice.promotionId
		} );
	} );
}( jQuery ) );