<?php
/**
 * Admin Notes UI.
 *
 * @package WooCommerce Product Vendors/Admin Notes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Notes UI.
 *
 * Renders the admin-only notes timeline on the product edit screen (as a sidebar meta
 * box) and on the vendor term edit screen, and handles the add/edit/delete AJAX actions.
 * Every render and AJAX path is gated to store admins via the data API.
 *
 * @package WooCommerce Product Vendors/Admin Notes
 * @version 2.6.0
 */
class WC_Product_Vendors_Admin_Notes_UI {
	/**
	 * Max products listed in the vendor "Products with notes" rollup.
	 *
	 * @var int
	 */
	const ROLLUP_PRODUCT_LIMIT = 20;

	/**
	 * Registers hooks.
	 *
	 * @since 2.6.0
	 * @return WC_Product_Vendors_Admin_Notes_UI
	 */
	public static function init() {
		$self = new self();

		// Product edit screen: sidebar meta box.
		add_action( 'add_meta_boxes', array( $self, 'add_product_notes_meta_box' ) );

		// Vendor term edit screen: timeline section.
		add_action( WC_PRODUCT_VENDORS_TAXONOMY . '_edit_form_fields', array( $self, 'render_vendor_notes' ) );

		// AJAX: add / edit / delete / resolve.
		add_action( 'wp_ajax_wcpv_admin_note_add', array( $self, 'ajax_add_note' ) );
		add_action( 'wp_ajax_wcpv_admin_note_edit', array( $self, 'ajax_edit_note' ) );
		add_action( 'wp_ajax_wcpv_admin_note_delete', array( $self, 'ajax_delete_note' ) );
		add_action( 'wp_ajax_wcpv_admin_note_resolve', array( $self, 'ajax_resolve_note' ) );

		return $self;
	}

	/**
	 * Registers the product notes meta box (store admins only).
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function add_product_notes_meta_box() {
		if ( ! WC_Product_Vendors_Admin_Notes::current_user_can_manage() ) {
			return;
		}

		$title = __( 'Private notes', 'woocommerce-product-vendors' ) . wc_help_tip( __( 'Private, admin-only notes about this product. Not visible to vendors or customers.', 'woocommerce-product-vendors' ) );

		add_meta_box(
			'wcpv-admin-notes',
			$title,
			array( $this, 'render_product_notes_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Renders the product notes meta box.
	 *
	 * @since 2.6.0
	 * @param WP_Post $post Current product post.
	 * @return void
	 */
	public function render_product_notes_meta_box( $post ) {
		if ( ! WC_Product_Vendors_Admin_Notes::current_user_can_manage() ) {
			return;
		}

		$this->render_timeline( WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_PRODUCT, (int) $post->ID );
	}

	/**
	 * Renders the vendor notes timeline on the term edit screen.
	 *
	 * @since 2.6.0
	 * @param WP_Term $term Current vendor term.
	 * @return void
	 */
	public function render_vendor_notes( $term ) {
		if ( ! WC_Product_Vendors_Admin_Notes::current_user_can_manage() ) {
			return;
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Private notes', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Private, admin-only notes about this vendor. Not visible to vendors or customers.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td>
				<?php $this->render_timeline( WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_VENDOR, (int) $term->term_id ); ?>
			</td>
		</tr>
		<?php
		$this->render_vendor_product_rollup( (int) $term->term_id );
	}

	/**
	 * Renders the shared timeline (list + add form) for an object.
	 *
	 * @since 2.6.0
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @return void
	 */
	private function render_timeline( $object_type, $object_id ) {
		$notes = WC_Product_Vendors_Admin_Notes::get_notes( $object_type, $object_id );
		$ui    = $this;

		/**
		 * Filters the admin notes timeline template path.
		 *
		 * @since 2.6.0
		 *
		 * @param string $template    Default template path.
		 * @param string $object_type Object type being rendered.
		 */
		$template = apply_filters( 'wcpv_admin_notes_timeline_template', WC_PRODUCT_VENDORS_PATH . '/includes/admin/views/html-admin-notes-timeline.php', $object_type );

		include $template; // nosemgrep:audit.php.lang.security.file.inclusion-arg -- Trusted filter.
	}

	/**
	 * Renders the "Products with notes" rollup on the vendor term edit screen.
	 *
	 * Instead of interleaving product notes into the vendor timeline, this lists the
	 * vendor's products that have notes (name, count, most-recent date) with a link to
	 * each product's edit screen. Renders nothing when no product has notes.
	 *
	 * @since 2.6.0
	 * @param int $vendor_id Vendor term id.
	 * @return void
	 */
	private function render_vendor_product_rollup( $vendor_id ) {
		// Fetch one extra row to detect (and flag) that the list was truncated.
		$summary = WC_Product_Vendors_Admin_Notes::get_vendor_product_note_summary( $vendor_id, self::ROLLUP_PRODUCT_LIMIT + 1 );

		if ( empty( $summary ) ) {
			return;
		}

		$truncated = count( $summary ) > self::ROLLUP_PRODUCT_LIMIT;
		$summary   = array_slice( $summary, 0, self::ROLLUP_PRODUCT_LIMIT );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Products with notes', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( esc_html__( 'Products belonging to this vendor that have private notes. Open a product to view or edit its notes.', 'woocommerce-product-vendors' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
			</th>
			<td>
				<ul class="wcpv-admin-notes__rollup">
					<?php foreach ( $summary as $row ) : ?>
						<?php
						$edit_link = get_edit_post_link( $row->object_id );
						$title     = get_the_title( $row->object_id );
						$title     = '' !== $title ? $title : sprintf( '#%d', (int) $row->object_id );
						?>
						<li>
							<?php if ( $edit_link ) : ?>
								<a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $title ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $title ); ?>
							<?php endif; ?>
							<span class="wcpv-admin-notes__rollup-stats">
								<?php echo self::stats_html( (int) $row->note_count, (int) $row->redflag_open, (int) $row->redflag_solved ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in stat_chip(). ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $truncated ) : ?>
					<p class="description">
						<?php
						/* translators: %s: number of products shown */
						echo esc_html( sprintf( __( 'Showing the %s most recently updated products.', 'woocommerce-product-vendors' ), number_format_i18n( self::ROLLUP_PRODUCT_LIMIT ) ) );
						?>
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Footer link under the product timeline pointing to the vendor's other notes.
	 *
	 * Surfaces notes/flags the product's vendor has elsewhere (its profile and its
	 * other products) and links to the vendor edit screen. Returns '' unless this is a
	 * product that has a vendor with notes elsewhere.
	 *
	 * @since 2.6.0
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Object id.
	 * @return string
	 */
	public function vendor_other_notes_footer( $object_type, $object_id ) {
		if ( WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_PRODUCT !== $object_type ) {
			return '';
		}

		$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( (int) $object_id );

		if ( ! $vendor_id ) {
			return '';
		}

		$counts = WC_Product_Vendors_Admin_Notes::get_object_note_counts( WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_VENDOR, $vendor_id );
		$notes  = $counts['note_count'];
		$open   = $counts['redflag_open'];
		$solved = $counts['redflag_solved'];

		foreach ( WC_Product_Vendors_Admin_Notes::get_vendor_product_note_summary( $vendor_id ) as $row ) {
			if ( (int) $row->object_id === (int) $object_id ) {
				continue; // Exclude the product we're already viewing.
			}
			$notes  += (int) $row->note_count;
			$open   += (int) $row->redflag_open;
			$solved += (int) $row->redflag_solved;
		}

		if ( 0 === $notes + $open + $solved ) {
			return '';
		}

		$link = get_edit_term_link( $vendor_id, WC_PRODUCT_VENDORS_TAXONOMY, 'product' );

		ob_start();
		?>
		<div class="wcpv-admin-notes__vendor-link">
			<span class="wcpv-admin-notes__vendor-link-label"><?php esc_html_e( 'This vendor has notes elsewhere:', 'woocommerce-product-vendors' ); ?></span>
			<?php echo self::stats_html( $notes, $open, $solved ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in stat_chip(). ?>
			<?php if ( $link ) : ?>
				<a class="wcpv-admin-notes__vendor-link-more" href="<?php echo esc_url( $link ); ?>" aria-label="<?php esc_attr_e( 'View all notes on this vendor', 'woocommerce-product-vendors' ); ?>"><?php esc_html_e( 'View all', 'woocommerce-product-vendors' ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the HTML for an inline "icon + count" stat chip.
	 *
	 * Shared by the product rollup and the Vendors list Notes column so both render
	 * identically. The glyph and visible count are decorative (aria-hidden); the label
	 * is exposed to screen readers and as a hover title.
	 *
	 * @since 2.6.0
	 * @param int    $count    Visible count.
	 * @param string $glyph    Dashicon glyph class (e.g. dashicons-flag).
	 * @param string $modifier Colour modifier (is-note|is-flag|is-solved).
	 * @param string $label    Accessible / hover label (e.g. "2 red flags").
	 * @return string
	 */
	public static function stat_chip( $count, $glyph, $modifier, $label ) {
		return sprintf(
			'<span class="wcpv-admin-notes__stat" title="%1$s"><span class="dashicons %2$s wcpv-admin-notes__stat-icon %3$s" aria-hidden="true"></span><span aria-hidden="true">%4$s</span><span class="screen-reader-text">%1$s</span></span>',
			esc_attr( $label ),
			esc_attr( $glyph ),
			esc_attr( $modifier ),
			esc_html( number_format_i18n( $count ) )
		);
	}

	/**
	 * Builds the note / open-flag / solved-flag stat chips for a set of counts.
	 *
	 * Owns the glyph, colour, and label mapping in one place so the product rollup and
	 * the Vendors list Notes column render identical chips. Each chip is omitted when
	 * its count is zero.
	 *
	 * @since 2.6.0
	 * @param int $notes  Number of regular notes.
	 * @param int $open   Number of open red flags.
	 * @param int $solved Number of solved red flags.
	 * @return string Concatenated chip HTML (already escaped).
	 */
	public static function stats_html( $notes, $open, $solved ) {
		$html = '';

		if ( $notes > 0 ) {
			/* translators: %s: number of notes */
			$html .= self::stat_chip( $notes, 'dashicons-format-aside', 'is-note', sprintf( _n( '%s note', '%s notes', $notes, 'woocommerce-product-vendors' ), number_format_i18n( $notes ) ) );
		}

		if ( $open > 0 ) {
			/* translators: %s: number of red flags */
			$html .= self::stat_chip( $open, 'dashicons-flag', 'is-flag', sprintf( _n( '%s red flag', '%s red flags', $open, 'woocommerce-product-vendors' ), number_format_i18n( $open ) ) );
		}

		if ( $solved > 0 ) {
			/* translators: %s: number of solved red flags */
			$html .= self::stat_chip( $solved, 'dashicons-flag', 'is-solved', sprintf( _n( '%s solved red flag', '%s solved red flags', $solved, 'woocommerce-product-vendors' ), number_format_i18n( $solved ) ) );
		}

		return $html;
	}

	/**
	 * Returns the HTML for a single timeline note (list item).
	 *
	 * Mirrors the WooCommerce order-notes markup so existing styles apply.
	 *
	 * @since 2.6.0
	 * @param object $note Formatted note object.
	 * @return string
	 */
	public function note_li_html( $note ) {
		if ( ! $note ) {
			return '';
		}

		$classes = array( 'note', 'wcpv-admin-note' );

		if ( WC_Product_Vendors_Admin_Notes::SOURCE_MANUAL !== $note->source ) {
			$classes[] = 'system-note';
		}

		if ( WC_Product_Vendors_Admin_Notes::is_flag_type( $note->type ) ) {
			$classes[] = $note->is_resolved ? 'wcpv-admin-note--resolved' : 'wcpv-admin-note--flag';
		}

		/**
		 * Filters the CSS classes for a rendered admin note.
		 *
		 * @since 2.6.0
		 *
		 * @param array  $classes Note classes.
		 * @param object $note    The note object.
		 */
		$classes = apply_filters( 'wcpv_admin_note_class', $classes, $note );

		// translators: 1: date, 2: time.
		$meta_text = __( '%1$s at %2$s', 'woocommerce-product-vendors' );
		$meta_text = sprintf(
			$meta_text,
			date_i18n( wc_date_format(), strtotime( $note->created_at ) ),
			date_i18n( wc_time_format(), strtotime( $note->created_at ) )
		);

		ob_start();
		?>
		<li rel="<?php echo esc_attr( $note->id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-note-id="<?php echo esc_attr( $note->id ); ?>" data-note-raw="<?php echo esc_attr( $note->note ); ?>">
			<div class="note_content">
				<?php
				// Only custom non-flag types get a labelled badge; flags rely on the inline icon.
				$badge_label = '';
				if ( WC_Product_Vendors_Admin_Notes::TYPE_NOTE !== $note->type && ! WC_Product_Vendors_Admin_Notes::is_flag_type( $note->type ) ) {
					$types       = WC_Product_Vendors_Admin_Notes::get_registered_note_types();
					$badge_label = isset( $types[ $note->type ]['label'] ) ? $types[ $note->type ]['label'] : $note->type;
				}
				?>
				<?php if ( '' !== $badge_label ) : ?>
					<div class="wcpv-admin-note__header">
						<span class="wcpv-admin-note__type"><?php echo esc_html( $badge_label ); ?></span>
					</div>
				<?php endif; ?>
				<div class="note_body">
					<?php
					$body_html = wpautop( wptexturize( wp_kses_post( $note->note ) ) );

					// Prefix the first paragraph with an inline icon (flag for flag types, aside for notes).
					$icon_html = '';
					if ( WC_Product_Vendors_Admin_Notes::is_flag_type( $note->type ) ) {
						$flag_class = 'dashicons dashicons-flag wcpv-admin-note__flag-icon' . ( $note->is_resolved ? ' is-solved' : '' );
						$icon_html  = '<span class="' . esc_attr( $flag_class ) . '" aria-hidden="true"></span>';
					} elseif ( WC_Product_Vendors_Admin_Notes::TYPE_NOTE === $note->type ) {
						$icon_html = '<span class="dashicons dashicons-format-aside wcpv-admin-note__note-icon" aria-hidden="true"></span>';
					}

					if ( '' !== $icon_html ) {
						$body_html = preg_replace( '/<p\b[^>]*>/', '$0' . $icon_html, $body_html, 1, $replaced );

						if ( empty( $replaced ) ) {
							$body_html = $icon_html . $body_html;
						}
					}

					echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from wp_kses_post() output plus a static icon span.
					?>
				</div>
				<?php if ( $note->is_resolved ) : ?>
					<div class="wcpv-admin-note__resolution">
						<span class="dashicons dashicons-yes wcpv-admin-note__resolution-icon" aria-hidden="true"></span>
						<?php
						/* translators: 1: date the flag was resolved, 2: admin who resolved it */
						echo esc_html( sprintf( __( 'Solved on %1$s by %2$s', 'woocommerce-product-vendors' ), date_i18n( wc_date_format(), strtotime( $note->resolved_at ) ), $note->resolved_by_name ? $note->resolved_by_name : __( 'an admin', 'woocommerce-product-vendors' ) ) );
						?>
						<?php if ( '' !== $note->resolution ) : ?>
							<div class="wcpv-admin-note__resolution-reason"><?php echo esc_html( $note->resolution ); ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="wcpv-admin-note__footer">
			<p class="meta">
				<abbr class="exact-date" title="<?php echo esc_attr( $note->created_at ); ?>"><?php echo esc_html( $meta_text ); ?></abbr>
				<?php
				if ( $note->author_id ) {
					/* translators: %s: author display name */
					echo esc_html( sprintf( ' ' . __( 'by %s', 'woocommerce-product-vendors' ), $note->author_name ) );
				}

				if ( WC_Product_Vendors_Admin_Notes::SOURCE_MANUAL !== $note->source ) {
					$sources      = WC_Product_Vendors_Admin_Notes::get_registered_sources();
					$source_label = isset( $sources[ $note->source ] ) ? $sources[ $note->source ] : '';

					if ( $source_label ) {
						echo ' <span class="wcpv-admin-note__source">' . esc_html( $source_label ) . '</span>';
					}
				}

				?>
			</p>
				<?php
				$can_resolve = WC_Product_Vendors_Admin_Notes::is_flag_type( $note->type ) && ! $note->is_resolved;
				$can_edit    = WC_Product_Vendors_Admin_Notes::can_edit( $note );
				$can_delete  = WC_Product_Vendors_Admin_Notes::can_delete( $note );
				?>
				<?php if ( $can_resolve || $can_edit || $can_delete ) : ?>
				<span class="wcpv-admin-note__actions">
					<?php if ( $can_resolve ) : ?>
						<button type="button" class="button-link wcpv-admin-note__resolve" aria-label="<?php esc_attr_e( 'Solve', 'woocommerce-product-vendors' ); ?>" title="<?php esc_attr_e( 'Solve', 'woocommerce-product-vendors' ); ?>"><span class="dashicons dashicons-yes" aria-hidden="true"></span></button>
					<?php endif; ?>
					<?php if ( $can_edit ) : ?>
						<button type="button" class="button-link wcpv-admin-note__edit" aria-label="<?php esc_attr_e( 'Edit', 'woocommerce-product-vendors' ); ?>" title="<?php esc_attr_e( 'Edit', 'woocommerce-product-vendors' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
					<?php endif; ?>
					<?php if ( $can_delete ) : ?>
						<button type="button" class="button-link wcpv-admin-note__delete" aria-label="<?php esc_attr_e( 'Delete', 'woocommerce-product-vendors' ); ?>" title="<?php esc_attr_e( 'Delete', 'woocommerce-product-vendors' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
					<?php endif; ?>
				</span>
				<?php endif; ?>
			</div>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Reads and validates the object type from the request.
	 *
	 * @since 2.6.0
	 * @return string Sanitized object type (may be empty/invalid; verify_request rejects it).
	 */
	private function request_object_type() {
		return isset( $_POST['object_type'] ) ? sanitize_key( wp_unslash( $_POST['object_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
	}

	/**
	 * Verifies an AJAX request (per-object nonce + capability). Exits on failure.
	 *
	 * @since 2.6.0
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object id.
	 * @return void
	 */
	private function verify_request( $object_type, $object_id ) {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		$valid = $object_id
			&& in_array( $object_type, WC_Product_Vendors_Admin_Notes::get_object_types(), true )
			&& wp_verify_nonce( $nonce, 'wcpv_admin_note_' . $object_type . '_' . $object_id )
			&& WC_Product_Vendors_Admin_Notes::current_user_can_manage();

		if ( ! $valid ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-product-vendors' ) ), 403 );
		}
	}

	/**
	 * Fetches a note and verifies it belongs to the request's object.
	 *
	 * The request nonce is scoped to a single object_type/object_id, so a note_id from a
	 * different object must be rejected. Sends a 403 and exits on a missing note or a
	 * mismatch.
	 *
	 * @since 2.6.0
	 * @param int    $note_id     Note id.
	 * @param string $object_type Requested object type.
	 * @param int    $object_id   Requested object id.
	 * @return object The formatted note (only returned when it matches the request object).
	 */
	private function get_request_note( $note_id, $object_type, $object_id ) {
		$note = WC_Product_Vendors_Admin_Notes::get_note( $note_id );

		if ( ! $note || $note->object_type !== $object_type || (int) $note->object_id !== (int) $object_id ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-product-vendors' ) ), 403 );
		}

		return $note;
	}

	/**
	 * AJAX: add a note.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_add_note() {
		$object_type = $this->request_object_type();
		$object_id   = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		$this->verify_request( $object_type, $object_id );

		$content = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in the data API; nonce verified in verify_request().
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		// Only types offered in the add-note form may be submitted here (empty = default note).
		if ( '' !== $type && ! WC_Product_Vendors_Admin_Notes::is_selectable_type( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid note type.', 'woocommerce-product-vendors' ) ), 400 );
		}

		$args   = '' !== $type ? array( 'type' => $type ) : array();
		$result = WC_Product_Vendors_Admin_Notes::add_note( $object_type, $object_id, $content, $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'note_id' => $result,
				'html'    => $this->note_li_html( WC_Product_Vendors_Admin_Notes::get_note( $result ) ),
			)
		);
	}

	/**
	 * AJAX: edit a note.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_edit_note() {
		$object_type = $this->request_object_type();
		$object_id   = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		$this->verify_request( $object_type, $object_id );

		$note_id = isset( $_POST['note_id'] ) ? absint( wp_unslash( $_POST['note_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$content = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in the data API; nonce verified in verify_request().

		// The note must belong to the object the nonce was issued for.
		$existing = $this->get_request_note( $note_id, $object_type, $object_id );

		// A solved red flag is locked as a record and cannot be edited.
		if ( ! WC_Product_Vendors_Admin_Notes::can_edit( $existing ) ) {
			wp_send_json_error( array( 'message' => __( 'A solved red flag can no longer be edited.', 'woocommerce-product-vendors' ) ) );
		}

		$result = WC_Product_Vendors_Admin_Notes::update_note( $note_id, $content );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'note_id' => $note_id,
				'html'    => $this->note_li_html( WC_Product_Vendors_Admin_Notes::get_note( $note_id ) ),
			)
		);
	}

	/**
	 * AJAX: delete a note.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_delete_note() {
		$object_type = $this->request_object_type();
		$object_id   = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		$this->verify_request( $object_type, $object_id );

		$note_id = isset( $_POST['note_id'] ) ? absint( wp_unslash( $_POST['note_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		// The note must belong to the object the nonce was issued for.
		$existing = $this->get_request_note( $note_id, $object_type, $object_id );

		// Red flags are an audit record and cannot be deleted (open or solved).
		if ( ! WC_Product_Vendors_Admin_Notes::can_delete( $existing ) ) {
			wp_send_json_error( array( 'message' => __( 'A red flag cannot be deleted.', 'woocommerce-product-vendors' ) ) );
		}

		$result = WC_Product_Vendors_Admin_Notes::delete_note( $note_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'note_id' => $note_id ) );
	}

	/**
	 * AJAX: resolve a note (close a flag).
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public function ajax_resolve_note() {
		$object_type = $this->request_object_type();
		$object_id   = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().

		$this->verify_request( $object_type, $object_id );

		$note_id = isset( $_POST['note_id'] ) ? absint( wp_unslash( $_POST['note_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_request().
		$reason  = isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in the data API; nonce verified in verify_request().

		// The note must belong to the object the nonce was issued for.
		$this->get_request_note( $note_id, $object_type, $object_id );

		$result = WC_Product_Vendors_Admin_Notes::resolve_note( $note_id, array( 'reason' => $reason ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'note_id' => $note_id,
				'html'    => $this->note_li_html( WC_Product_Vendors_Admin_Notes::get_note( $note_id ) ),
			)
		);
	}
}

WC_Product_Vendors_Admin_Notes_UI::init();
