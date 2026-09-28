<?php
/**
 * Admin Notes data API.
 *
 * @package WooCommerce Product Vendors/Admin Notes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Notes data API.
 *
 * Stores and retrieves admin-only notes attached to a vendor (taxonomy term) or a
 * product (post). This is the single, stable entry point used by the admin UI, AJAX
 * handlers and any programmatic integration (e.g. automations logging a note).
 *
 * All public read/write methods are gated to store admins (users with the
 * `manage_vendors` capability who are not themselves vendors). Trusted server-side
 * callers that run outside of an admin request (cron, REST, status transitions) should
 * use {@see WC_Product_Vendors_Admin_Notes::add_system_note()} which is not gated.
 *
 * @package WooCommerce Product Vendors/Admin Notes
 * @version 2.6.0
 */
class WC_Product_Vendors_Admin_Notes {
	/**
	 * Object type: vendor (a `wcpv_product_vendors` taxonomy term).
	 */
	const OBJECT_TYPE_VENDOR = 'vendor';

	/**
	 * Object type: product (a `product` post).
	 */
	const OBJECT_TYPE_PRODUCT = 'product';

	/**
	 * Source: note added manually by an admin through the UI.
	 */
	const SOURCE_MANUAL = 'manual';

	/**
	 * Source: note added programmatically by trusted server-side code.
	 */
	const SOURCE_SYSTEM = 'system';

	/**
	 * Source: note created by migrating the legacy single vendor notes field.
	 */
	const SOURCE_MIGRATED = 'migrated';

	/**
	 * Type: a general admin note (the default).
	 */
	const TYPE_NOTE = 'note';

	/**
	 * Type: a vendor/product violation flagged for attention.
	 */
	const TYPE_REDFLAG = 'redflag';

	/**
	 * Returns the notes table name.
	 *
	 * @return string
	 */
	private static function table() {
		return WC_PRODUCT_VENDORS_ADMIN_NOTES_TABLE;
	}

	/**
	 * Returns the supported object types.
	 *
	 * @return array
	 */
	public static function get_object_types() {
		return array( self::OBJECT_TYPE_VENDOR, self::OBJECT_TYPE_PRODUCT );
	}

	/**
	 * Whether the current user may manage admin notes.
	 *
	 * Store admins and shop managers have `manage_vendors`; vendor users never do.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		$is_vendor = class_exists( 'WC_Product_Vendors_Utils' ) && WC_Product_Vendors_Utils::is_vendor();

		return current_user_can( 'manage_vendors' ) && ! $is_vendor; // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability registered by WC Product Vendors.
	}

	/**
	 * Returns the registered note sources mapped to human labels.
	 *
	 * Integrations may register their own sources via the filter so system notes can be
	 * labelled/badged in the timeline.
	 *
	 * @return array
	 */
	public static function get_registered_sources() {
		/**
		 * Filters the registered admin note sources.
		 *
		 * @since 2.6.0
		 *
		 * @param array $sources Map of source slug => human label.
		 */
		return apply_filters(
			'wcpv_admin_note_sources',
			array(
				self::SOURCE_MANUAL   => __( 'Manual', 'woocommerce-product-vendors' ),
				self::SOURCE_SYSTEM   => __( 'System', 'woocommerce-product-vendors' ),
				self::SOURCE_MIGRATED => __( 'Migrated', 'woocommerce-product-vendors' ),
			)
		);
	}

	/**
	 * Returns the registered note types.
	 *
	 * Each type maps to metadata: `label` (display), `is_flag` (whether the type marks a
	 * violation that should be highlighted), and `selectable` (whether admins can pick it
	 * in the add-note form; system/event types are typically not selectable).
	 *
	 * Integrations register their own types (e.g. a workflow's "payout failed" event) via
	 * the filter — see docs/admin-notes.md.
	 *
	 * @return array Map of type slug => metadata array.
	 */
	public static function get_registered_note_types() {
		/**
		 * Filters the registered admin note types.
		 *
		 * @since 2.6.0
		 *
		 * @param array $types Map of type slug => { label, is_flag, selectable }.
		 */
		return apply_filters(
			'wcpv_admin_note_types',
			array(
				self::TYPE_NOTE    => array(
					'label'      => __( 'Note', 'woocommerce-product-vendors' ),
					'is_flag'    => false,
					'selectable' => true,
				),
				self::TYPE_REDFLAG => array(
					'label'      => __( 'Red flag', 'woocommerce-product-vendors' ),
					'is_flag'    => true,
					'selectable' => true,
				),
			)
		);
	}

	/**
	 * Returns whether a note type is a "flag" (a violation to highlight).
	 *
	 * @param string $type Type slug.
	 * @return bool
	 */
	public static function is_flag_type( $type ) {
		$types = self::get_registered_note_types();

		return ! empty( $types[ $type ]['is_flag'] );
	}

	/**
	 * Returns whether a note type is registered and selectable in the add-note form.
	 *
	 * Used to validate the type an admin submits; non-selectable types (e.g. system/event
	 * types) are added programmatically via add_system_note(), not through the UI.
	 *
	 * @since 2.6.0
	 * @param string $type Type slug.
	 * @return bool
	 */
	public static function is_selectable_type( $type ) {
		$types = self::get_registered_note_types();

		return ! empty( $types[ $type ]['selectable'] );
	}

	/**
	 * Returns registered note type slugs that should count as flags.
	 *
	 * @since 2.6.0
	 * @return array
	 */
	private static function get_flag_types() {
		$types = array();

		foreach ( self::get_registered_note_types() as $type => $meta ) {
			if ( ! empty( $meta['is_flag'] ) ) {
				$types[] = sanitize_key( $type );
			}
		}

		$types = array_values( array_filter( array_unique( $types ) ) );

		return ! empty( $types ) ? $types : array( self::TYPE_REDFLAG );
	}

	/**
	 * Validates an object type + id pair, ensuring the object exists.
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @return int|WP_Error Sanitized object id, or WP_Error when invalid.
	 */
	private static function validate_object( $object_type, $object_id ) {
		if ( ! in_array( $object_type, self::get_object_types(), true ) ) {
			return new WP_Error( 'wcpv_invalid_object_type', __( 'Invalid note object type.', 'woocommerce-product-vendors' ) );
		}

		$object_id = absint( $object_id );

		if ( ! $object_id ) {
			return new WP_Error( 'wcpv_invalid_object_id', __( 'Invalid note object id.', 'woocommerce-product-vendors' ) );
		}

		if ( self::OBJECT_TYPE_VENDOR === $object_type ) {
			$term = get_term( $object_id, WC_PRODUCT_VENDORS_TAXONOMY );

			if ( ! $term || is_wp_error( $term ) ) {
				return new WP_Error( 'wcpv_vendor_not_found', __( 'Vendor not found.', 'woocommerce-product-vendors' ) );
			}
		} else {
			$post = get_post( $object_id );

			if ( ! $post || 'product' !== $post->post_type ) {
				return new WP_Error( 'wcpv_product_not_found', __( 'Product not found.', 'woocommerce-product-vendors' ) );
			}
		}

		return $object_id;
	}

	/**
	 * Adds a note as the current user.
	 *
	 * Gated to store admins. For programmatic/server-side use see add_system_note().
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param string $content     Note content.
	 * @param array  $args        Optional. { source, type, author_id, meta }.
	 * @return int|WP_Error Inserted note id, or WP_Error on failure.
	 */
	public static function add_note( $object_type, $object_id, $content, $args = array() ) {
		if ( ! self::current_user_can_manage() ) {
			return new WP_Error( 'wcpv_forbidden', __( 'You are not allowed to add notes.', 'woocommerce-product-vendors' ) );
		}

		return self::insert_note( $object_type, $object_id, $content, $args );
	}

	/**
	 * Adds a red-flag note (a flagged violation) as the current user.
	 *
	 * Convenience wrapper around add_note() for the common case. For a programmatic
	 * red flag, call add_system_note() with `[ 'type' => self::TYPE_REDFLAG ]`.
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param string $content     Note content.
	 * @param array  $args        Optional. { source, type, author_id, meta }.
	 * @return int|WP_Error Inserted note id, or WP_Error on failure.
	 */
	public static function add_redflag( $object_type, $object_id, $content, $args = array() ) {
		$args['type'] = self::TYPE_REDFLAG;

		return self::add_note( $object_type, $object_id, $content, $args );
	}

	/**
	 * Adds a note from trusted server-side code (not capability gated).
	 *
	 * Intended for automations, cron, REST or status transitions where there may be no
	 * current user. Defaults the source to `system` and the author to 0.
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param string $content     Note content.
	 * @param array  $args        Optional. { source, type, author_id, meta }.
	 * @return int|WP_Error Inserted note id, or WP_Error on failure.
	 */
	public static function add_system_note( $object_type, $object_id, $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'source'    => self::SOURCE_SYSTEM,
				'author_id' => 0,
			)
		);

		return self::insert_note( $object_type, $object_id, $content, $args );
	}

	/**
	 * Sanitizes note content and rejects empty values.
	 *
	 * @param string $content Raw note content.
	 * @return string|WP_Error Sanitized content, or WP_Error when empty.
	 */
	private static function validate_content( $content ) {
		$content = trim( wp_kses_post( $content ) );

		if ( '' === $content ) {
			return new WP_Error( 'wcpv_empty_note', __( 'Note content cannot be empty.', 'woocommerce-product-vendors' ) );
		}

		return $content;
	}

	/**
	 * Runs a notes query for a prepared WHERE clause and returns formatted rows.
	 *
	 * @param string $where Prepared WHERE clause.
	 * @param string $order 'ASC' or 'DESC' (default DESC).
	 * @param string $limit Optional prepared LIMIT/OFFSET clause.
	 * @return array Array of formatted note objects.
	 */
	private static function query_notes( $where, $order = 'DESC', $limit = '' ) {
		global $wpdb;

		$table = self::table();
		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		// $where and $limit are prepared by the caller; $table and $order are safe internal values.
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at {$order}, id {$order} {$limit}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map( array( __CLASS__, 'format_row' ), $rows );
	}

	/**
	 * Inserts a note row. Internal; callers enforce their own capability policy.
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param string $content     Note content.
	 * @param array  $args        Optional. { source, type, author_id, meta }.
	 * @return int|WP_Error Inserted note id, or WP_Error on failure.
	 */
	private static function insert_note( $object_type, $object_id, $content, $args = array() ) {
		global $wpdb;

		$validated = self::validate_object( $object_type, $object_id );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$object_id = $validated;
		$content   = self::validate_content( $content );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$args = wp_parse_args(
			$args,
			array(
				'author_id' => get_current_user_id(),
				'source'    => self::SOURCE_MANUAL,
				'type'      => self::TYPE_NOTE,
				'meta'      => array(),
			)
		);

		$now = current_time( 'mysql' );

		$data = array(
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'note'        => $content,
			'source'      => sanitize_key( $args['source'] ),
			'type'        => sanitize_key( $args['type'] ),
			'author_id'   => absint( $args['author_id'] ),
			'meta'        => ! empty( $args['meta'] ) ? wp_json_encode( $args['meta'] ) : null,
			'created_at'  => $now,
			'updated_at'  => $now,
		);

		/**
		 * Filters the note row data before insertion.
		 *
		 * @since 2.6.0
		 *
		 * @param array  $data        Row data.
		 * @param string $object_type Object type.
		 * @param int    $object_id   Object id.
		 */
		$data = apply_filters( 'wcpv_admin_note_insert_data', $data, $object_type, $object_id );

		$inserted = $wpdb->insert( self::table(), $data, array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( ! $inserted ) {
			return new WP_Error( 'wcpv_note_insert_failed', __( 'Could not save the note.', 'woocommerce-product-vendors' ) );
		}

		$note_id = (int) $wpdb->insert_id;

		/**
		 * Fires after an admin note is added.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $note_id     Note id.
		 * @param string $object_type Object type.
		 * @param int    $object_id   Object id.
		 * @param object $note        The formatted note row.
		 */
		do_action( 'wcpv_admin_note_added', $note_id, $object_type, $object_id, self::fetch_note( $note_id ) );

		return $note_id;
	}

	/**
	 * Retrieves a single note (gated).
	 *
	 * @param int $note_id Note id.
	 * @return object|null Formatted note, or null.
	 */
	public static function get_note( $note_id ) {
		if ( ! self::current_user_can_manage() ) {
			return null;
		}

		return self::fetch_note( $note_id );
	}

	/**
	 * Retrieves the notes for an object, newest first by default (gated).
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param array  $args        Optional. { order, number, offset, source }.
	 * @return array Array of formatted note objects.
	 */
	public static function get_notes( $object_type, $object_id, $args = array() ) {
		if ( ! self::current_user_can_manage() ) {
			return array();
		}

		global $wpdb;

		$object_id = absint( $object_id );

		if ( ! in_array( $object_type, self::get_object_types(), true ) || ! $object_id ) {
			return array();
		}

		$args = wp_parse_args(
			$args,
			array(
				'order'  => 'DESC',
				'number' => 0,
				'offset' => 0,
				'source' => '',
			)
		);

		$where = $wpdb->prepare( 'object_type = %s AND object_id = %d', $object_type, $object_id );

		if ( ! empty( $args['source'] ) ) {
			$where .= $wpdb->prepare( ' AND source = %s', sanitize_key( $args['source'] ) );
		}

		$limit = '';

		if ( absint( $args['number'] ) > 0 ) {
			$limit = $wpdb->prepare( 'LIMIT %d OFFSET %d', absint( $args['number'] ), absint( $args['offset'] ) );
		}

		return self::query_notes( $where, $args['order'], $limit );
	}

	/**
	 * Summarizes the notes on a vendor's products, grouped by product (gated).
	 *
	 * Callers that render a list should pass a $limit; callers that aggregate across every
	 * product (e.g. the product screen's "notes elsewhere" totals) must not, or the sum would
	 * silently undercount.
	 *
	 * @param int $vendor_id Vendor term id.
	 * @param int $limit     Optional. Max products returned, newest activity first. 0 for all.
	 * @return array Row objects { object_id, note_count, redflag_open, redflag_solved, last_created_at }, newest activity first.
	 */
	public static function get_vendor_product_note_summary( $vendor_id, $limit = 0 ) {
		if ( ! self::current_user_can_manage() ) {
			return array();
		}

		$vendor_id = absint( $vendor_id );
		$limit     = absint( $limit );

		if ( ! $vendor_id ) {
			return array();
		}

		global $wpdb;

		$table                  = self::table();
		$flag_types             = self::get_flag_types();
		$flag_type_placeholders = implode( ',', array_fill( 0, count( $flag_types ), '%s' ) );
		$params                 = array_merge( $flag_types, $flag_types, $flag_types, array( self::OBJECT_TYPE_PRODUCT, WC_PRODUCT_VENDORS_TAXONOMY, $vendor_id ) );
		$limit_sql              = '';

		if ( $limit ) {
			$limit_sql = ' LIMIT %d';
			$params[]  = $limit;
		}

		// Join notes to their product's vendor term instead of expanding a (potentially huge)
		// product-id IN() list; groups per product for the rollup.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $table/$flag_type_placeholders/$limit_sql are trusted internal values; params passed via array_merge.
		$sql = $wpdb->prepare(
			"SELECT n.object_id,
				SUM( CASE WHEN n.type NOT IN ( {$flag_type_placeholders} ) THEN 1 ELSE 0 END ) AS note_count,
				SUM( CASE WHEN n.type IN ( {$flag_type_placeholders} ) AND n.resolved_at IS NULL THEN 1 ELSE 0 END ) AS redflag_open,
				SUM( CASE WHEN n.type IN ( {$flag_type_placeholders} ) AND n.resolved_at IS NOT NULL THEN 1 ELSE 0 END ) AS redflag_solved,
				MAX(n.created_at) AS last_created_at
			FROM {$table} n
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = n.object_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE n.object_type = %s AND tt.taxonomy = %s AND tt.term_id = %d
			GROUP BY n.object_id ORDER BY last_created_at DESC{$limit_sql}",
			$params
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Returns the note / red-flag counts for a single object in one query (gated).
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @return array { note_count, redflag_open, redflag_solved } as ints.
	 */
	public static function get_object_note_counts( $object_type, $object_id ) {
		$empty = array(
			'note_count'     => 0,
			'redflag_open'   => 0,
			'redflag_solved' => 0,
		);

		if ( ! self::current_user_can_manage() ) {
			return $empty;
		}

		$object_id = absint( $object_id );

		if ( ! in_array( $object_type, self::get_object_types(), true ) || ! $object_id ) {
			return $empty;
		}

		global $wpdb;

		$table                  = self::table();
		$flag_types             = self::get_flag_types();
		$flag_type_placeholders = implode( ',', array_fill( 0, count( $flag_types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $table and $flag_type_placeholders are trusted internal values; params passed via array_merge.
		$sql = $wpdb->prepare(
			"SELECT
				SUM( CASE WHEN type NOT IN ( {$flag_type_placeholders} ) THEN 1 ELSE 0 END ) AS note_count,
				SUM( CASE WHEN type IN ( {$flag_type_placeholders} ) AND resolved_at IS NULL THEN 1 ELSE 0 END ) AS redflag_open,
				SUM( CASE WHEN type IN ( {$flag_type_placeholders} ) AND resolved_at IS NOT NULL THEN 1 ELSE 0 END ) AS redflag_solved
			FROM {$table} WHERE object_type = %s AND object_id = %d",
			array_merge( $flag_types, $flag_types, $flag_types, array( $object_type, $object_id ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$row = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		if ( ! $row ) {
			return $empty;
		}

		return array(
			'note_count'     => (int) $row->note_count,
			'redflag_open'   => (int) $row->redflag_open,
			'redflag_solved' => (int) $row->redflag_solved,
		);
	}

	/**
	 * Returns per-vendor note totals in two grouped queries (gated).
	 *
	 * Each total combines the vendor's own notes with those on its products. Intended for
	 * the Vendors list table, which would otherwise run per-row counts (an N+1 pattern);
	 * callers should compute this once per request and look up by vendor id.
	 *
	 * @since 2.6.0
	 * @param array $vendor_ids Optional. Restrict to these vendor term ids (e.g. the ones on
	 *                          the current list-table page). Empty for every vendor.
	 * @return array Map of vendor term id => { note_count, redflag_open, redflag_solved } (ints).
	 */
	public static function get_all_vendor_note_totals( $vendor_ids = array() ) {
		if ( ! self::current_user_can_manage() ) {
			return array();
		}

		global $wpdb;

		$table                  = self::table();
		$flag_types             = self::get_flag_types();
		$flag_type_placeholders = implode( ',', array_fill( 0, count( $flag_types ), '%s' ) );
		$totals                 = array();

		$vendor_ids     = array_values( array_unique( array_filter( array_map( 'absint', (array) $vendor_ids ) ) ) );
		$vendor_filter  = '';
		$product_filter = '';

		if ( ! empty( $vendor_ids ) ) {
			$id_placeholders = implode( ',', array_fill( 0, count( $vendor_ids ), '%d' ) );
			$vendor_filter   = " AND object_id IN ( {$id_placeholders} )";
			$product_filter  = " AND tt.term_id IN ( {$id_placeholders} )";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $table/$flag_type_placeholders/filters are trusted internal values; params passed via array_merge.

		// 1. Vendor-level notes, grouped by the vendor term id.
		$vendor_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT object_id AS vendor_id,
					SUM( CASE WHEN type NOT IN ( {$flag_type_placeholders} ) THEN 1 ELSE 0 END ) AS note_count,
					SUM( CASE WHEN type IN ( {$flag_type_placeholders} ) AND resolved_at IS NULL THEN 1 ELSE 0 END ) AS redflag_open,
					SUM( CASE WHEN type IN ( {$flag_type_placeholders} ) AND resolved_at IS NOT NULL THEN 1 ELSE 0 END ) AS redflag_solved
				FROM {$table} WHERE object_type = %s{$vendor_filter} GROUP BY object_id",
				array_merge( $flag_types, $flag_types, $flag_types, array( self::OBJECT_TYPE_VENDOR ), $vendor_ids )
			)
		);

		// 2. Product notes, mapped to the owning vendor via the term relationships.
		$product_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT tt.term_id AS vendor_id,
					SUM( CASE WHEN n.type NOT IN ( {$flag_type_placeholders} ) THEN 1 ELSE 0 END ) AS note_count,
					SUM( CASE WHEN n.type IN ( {$flag_type_placeholders} ) AND n.resolved_at IS NULL THEN 1 ELSE 0 END ) AS redflag_open,
					SUM( CASE WHEN n.type IN ( {$flag_type_placeholders} ) AND n.resolved_at IS NOT NULL THEN 1 ELSE 0 END ) AS redflag_solved
				FROM {$table} n
				INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = n.object_id
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE n.object_type = %s AND tt.taxonomy = %s{$product_filter} GROUP BY tt.term_id",
				array_merge( $flag_types, $flag_types, $flag_types, array( self::OBJECT_TYPE_PRODUCT, WC_PRODUCT_VENDORS_TAXONOMY ), $vendor_ids )
			)
		);

		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		foreach ( array( $vendor_rows, $product_rows ) as $rows ) {
			if ( ! is_array( $rows ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				$vendor_id = (int) $row->vendor_id;

				if ( ! isset( $totals[ $vendor_id ] ) ) {
					$totals[ $vendor_id ] = array(
						'note_count'     => 0,
						'redflag_open'   => 0,
						'redflag_solved' => 0,
					);
				}

				$totals[ $vendor_id ]['note_count']     += (int) $row->note_count;
				$totals[ $vendor_id ]['redflag_open']   += (int) $row->redflag_open;
				$totals[ $vendor_id ]['redflag_solved'] += (int) $row->redflag_solved;
			}
		}

		return $totals;
	}

	/**
	 * Returns the number of notes for an object (gated).
	 *
	 * @param string $object_type One of the OBJECT_TYPE_* constants.
	 * @param int    $object_id   Vendor term id or product post id.
	 * @param string $type        Optional. Restrict to a single note type (e.g. redflag).
	 * @param bool   $open_only   Optional. Only count unresolved notes.
	 * @return int
	 */
	public static function get_notes_count( $object_type, $object_id, $type = '', $open_only = false ) {
		if ( ! self::current_user_can_manage() ) {
			return 0;
		}

		global $wpdb;

		$object_id = absint( $object_id );

		if ( ! in_array( $object_type, self::get_object_types(), true ) || ! $object_id ) {
			return 0;
		}

		$table  = self::table();
		$where  = 'object_type = %s AND object_id = %d';
		$params = array( $object_type, $object_id );

		if ( '' !== $type ) {
			$where   .= ' AND type = %s';
			$params[] = sanitize_key( $type );
		}

		if ( $open_only ) {
			$where .= ' AND resolved_at IS NULL';
		}

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Whether a note may be edited by an admin.
	 *
	 * Solved red flags are locked as an audit record.
	 *
	 * @param object $note Formatted note object.
	 * @return bool
	 */
	public static function can_edit( $note ) {
		return ! ( self::is_flag_type( $note->type ) && ! empty( $note->is_resolved ) );
	}

	/**
	 * Whether a note may be deleted by an admin.
	 *
	 * Red flags are an audit record and cannot be deleted.
	 *
	 * @param object $note Formatted note object.
	 * @return bool
	 */
	public static function can_delete( $note ) {
		return ! self::is_flag_type( $note->type );
	}

	/**
	 * Updates a note's content (gated).
	 *
	 * @param int    $note_id Note id.
	 * @param string $content New content.
	 * @return true|WP_Error
	 */
	public static function update_note( $note_id, $content ) {
		if ( ! self::current_user_can_manage() ) {
			return new WP_Error( 'wcpv_forbidden', __( 'You are not allowed to edit notes.', 'woocommerce-product-vendors' ) );
		}

		global $wpdb;

		$note_id  = absint( $note_id );
		$existing = self::fetch_note( $note_id );

		if ( ! $existing ) {
			return new WP_Error( 'wcpv_note_not_found', __( 'Note not found.', 'woocommerce-product-vendors' ) );
		}

		if ( ! self::can_edit( $existing ) ) {
			return new WP_Error( 'wcpv_note_locked', __( 'A solved red flag can no longer be edited.', 'woocommerce-product-vendors' ) );
		}

		$content = self::validate_content( $content );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$updated = $wpdb->update(
			self::table(),
			array(
				'note'       => $content,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $note_id ),
			array( '%s', '%s' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $updated ) {
			return new WP_Error( 'wcpv_note_update_failed', __( 'Could not update the note.', 'woocommerce-product-vendors' ) );
		}

		/**
		 * Fires after an admin note is updated.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $note_id Note id.
		 * @param object $note    The formatted note row.
		 */
		do_action( 'wcpv_admin_note_updated', $note_id, self::fetch_note( $note_id ) );

		return true;
	}

	/**
	 * Marks a note resolved (gated), recording who/when and an optional resolution reason.
	 *
	 * Used to close a flag (e.g. a red flag) once the admin has acted on it. Resolution is
	 * one-way; record a new note if the issue recurs.
	 *
	 * @param int   $note_id Note id.
	 * @param array $args    Optional. { reason }.
	 * @return true|WP_Error
	 */
	public static function resolve_note( $note_id, $args = array() ) {
		if ( ! self::current_user_can_manage() ) {
			return new WP_Error( 'wcpv_forbidden', __( 'You are not allowed to resolve notes.', 'woocommerce-product-vendors' ) );
		}

		global $wpdb;

		$note_id  = absint( $note_id );
		$existing = self::fetch_note( $note_id );

		if ( ! $existing ) {
			return new WP_Error( 'wcpv_note_not_found', __( 'Note not found.', 'woocommerce-product-vendors' ) );
		}

		if ( ! self::is_flag_type( $existing->type ) ) {
			return new WP_Error( 'wcpv_note_not_flag', __( 'Only flagged notes can be solved.', 'woocommerce-product-vendors' ) );
		}

		if ( ! empty( $existing->is_resolved ) ) {
			return new WP_Error( 'wcpv_note_already_resolved', __( 'This red flag has already been solved.', 'woocommerce-product-vendors' ) );
		}

		$args   = wp_parse_args( $args, array( 'reason' => '' ) );
		$meta   = is_array( $existing->meta ) ? $existing->meta : array();
		$reason = trim( wp_kses_post( $args['reason'] ) );

		if ( '' !== $reason ) {
			$meta['resolution'] = $reason;
		}

		$now = current_time( 'mysql' );

		$updated = $wpdb->update(
			self::table(),
			array(
				'resolved_at' => $now,
				'resolved_by' => get_current_user_id(),
				'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'updated_at'  => $now,
			),
			array( 'id' => $note_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $updated ) {
			return new WP_Error( 'wcpv_note_resolve_failed', __( 'Could not resolve the note.', 'woocommerce-product-vendors' ) );
		}

		/**
		 * Fires after a note is resolved.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $note_id Note id.
		 * @param object $note    The formatted note row.
		 */
		do_action( 'wcpv_admin_note_resolved', $note_id, self::fetch_note( $note_id ) );

		return true;
	}

	/**
	 * Deletes a note (gated).
	 *
	 * @param int $note_id Note id.
	 * @return true|WP_Error
	 */
	public static function delete_note( $note_id ) {
		if ( ! self::current_user_can_manage() ) {
			return new WP_Error( 'wcpv_forbidden', __( 'You are not allowed to delete notes.', 'woocommerce-product-vendors' ) );
		}

		global $wpdb;

		$note_id  = absint( $note_id );
		$existing = self::fetch_note( $note_id );

		if ( ! $existing ) {
			return new WP_Error( 'wcpv_note_not_found', __( 'Note not found.', 'woocommerce-product-vendors' ) );
		}

		if ( ! self::can_delete( $existing ) ) {
			return new WP_Error( 'wcpv_note_locked', __( 'A red flag cannot be deleted.', 'woocommerce-product-vendors' ) );
		}

		$deleted = $wpdb->delete( self::table(), array( 'id' => $note_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $deleted ) {
			return new WP_Error( 'wcpv_note_delete_failed', __( 'Could not delete the note.', 'woocommerce-product-vendors' ) );
		}

		/**
		 * Fires after an admin note is deleted.
		 *
		 * @since 2.6.0
		 *
		 * @param int    $note_id     Note id.
		 * @param string $object_type Object type.
		 * @param int    $object_id   Object id.
		 */
		do_action( 'wcpv_admin_note_deleted', $note_id, $existing->object_type, $existing->object_id );

		return true;
	}

	/**
	 * Fetches and formats a single note row. Internal; not capability gated.
	 *
	 * @param int $note_id Note id.
	 * @return object|null
	 */
	private static function fetch_note( $note_id ) {
		global $wpdb;

		$note_id = absint( $note_id );

		if ( ! $note_id ) {
			return null;
		}

		$table = self::table();

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $note_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		return self::format_row( $row );
	}

	/**
	 * Formats a raw DB row into a note object with author name and decoded meta.
	 *
	 * @param object|null $row Raw DB row.
	 * @return object|null
	 */
	private static function format_row( $row ) {
		if ( ! $row ) {
			return null;
		}

		$author      = $row->author_id ? get_user_by( 'id', $row->author_id ) : false;
		$meta        = ! empty( $row->meta ) ? json_decode( $row->meta, true ) : array();
		$resolved_at = isset( $row->resolved_at ) ? $row->resolved_at : null;
		$resolved_by = isset( $row->resolved_by ) ? (int) $row->resolved_by : 0;
		$resolver    = $resolved_by ? get_user_by( 'id', $resolved_by ) : false;

		return (object) array(
			'id'               => (int) $row->id,
			'object_type'      => $row->object_type,
			'object_id'        => (int) $row->object_id,
			'note'             => $row->note,
			'source'           => $row->source,
			'type'             => isset( $row->type ) ? $row->type : self::TYPE_NOTE,
			'author_id'        => (int) $row->author_id,
			'author_name'      => $author ? $author->display_name : __( 'System', 'woocommerce-product-vendors' ),
			'created_at'       => $row->created_at,
			'updated_at'       => $row->updated_at,
			'meta'             => $meta,
			'is_resolved'      => ! empty( $resolved_at ),
			'resolved_at'      => $resolved_at,
			'resolved_by'      => $resolved_by,
			'resolved_by_name' => $resolver ? $resolver->display_name : '',
			'resolution'       => isset( $meta['resolution'] ) ? $meta['resolution'] : '',
		);
	}
}
