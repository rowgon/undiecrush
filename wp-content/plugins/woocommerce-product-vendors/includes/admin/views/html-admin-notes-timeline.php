<?php
/**
 * Admin notes timeline view (shared by the product meta box and the vendor edit screen).
 *
 * @var string                              $object_type One of the OBJECT_TYPE_* constants.
 * @var int                                 $object_id   Vendor term id or product post id.
 * @var array                               $notes       Array of formatted note objects.
 * @var WC_Product_Vendors_Admin_Notes_UI   $ui          UI instance (for note markup).
 *
 * @package WooCommerce Product Vendors/Admin Notes
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_id = 'wcpv-admin-note-' . $object_type . '-' . $object_id;
// The product meta box is narrow, so collapse the add form behind a button there.
$collapsible = ( WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_PRODUCT === $object_type );
?>
<div class="wcpv-admin-notes" data-object-type="<?php echo esc_attr( $object_type ); ?>" data-object-id="<?php echo esc_attr( $object_id ); ?>">
	<?php wp_nonce_field( 'wcpv_admin_note_' . $object_type . '_' . $object_id, 'wcpv_admin_note_nonce', false ); ?>

	<div class="wcpv-admin-notes__status" role="status" aria-live="polite"></div>

	<div class="add_note wcpv-admin-notes__add<?php echo $collapsible ? ' is-collapsed' : ''; ?>">
		<?php if ( $collapsible ) : ?>
			<button type="button" class="button wcpv-admin-notes__add-toggle" aria-expanded="false"><?php esc_html_e( 'Add private note', 'woocommerce-product-vendors' ); ?></button>
		<?php endif; ?>
		<div class="wcpv-admin-notes__add-fields">
			<p>
				<label for="<?php echo esc_attr( $field_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Add private note', 'woocommerce-product-vendors' ); ?></label>
				<textarea id="<?php echo esc_attr( $field_id ); ?>" class="wcpv-admin-notes__input input-text" cols="20" rows="3" placeholder="<?php esc_attr_e( 'Add a note…', 'woocommerce-product-vendors' ); ?>"></textarea>
			</p>
			<?php
			$note_types = array_filter(
				WC_Product_Vendors_Admin_Notes::get_registered_note_types(),
				static function ( $meta ) {
					return ! empty( $meta['selectable'] );
				}
			);

			if ( count( $note_types ) > 1 ) :
				?>
				<p class="wcpv-admin-notes__type-field">
					<label for="<?php echo esc_attr( $field_id . '-type' ); ?>"><?php esc_html_e( 'Type', 'woocommerce-product-vendors' ); ?></label>
					<select id="<?php echo esc_attr( $field_id . '-type' ); ?>" class="wcpv-admin-notes__type">
						<?php foreach ( $note_types as $slug => $meta ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $meta['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endif; ?>
			<p>
				<button type="button" class="button wcpv-admin-notes__add-button"><?php esc_html_e( 'Add private note', 'woocommerce-product-vendors' ); ?></button>
			</p>
		</div>
	</div>

	<ul class="order_notes wcpv-admin-notes__list">
		<?php if ( ! empty( $notes ) ) : ?>
			<?php foreach ( $notes as $note ) : ?>
				<?php echo $ui->note_li_html( $note ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in note_li_html(). ?>
			<?php endforeach; ?>
		<?php else : ?>
			<li class="no-items"><?php esc_html_e( 'There are no notes yet.', 'woocommerce-product-vendors' ); ?></li>
		<?php endif; ?>
	</ul>

	<?php echo $ui->vendor_other_notes_footer( $object_type, $object_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in vendor_other_notes_footer(). ?>
</div>
