<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Commission Class.
 *
 * Model for commissions to handle CRUD process.
 *
 * @category Commission
 * @package  WooCommerce Product Vendors/Commission
 * @version  2.0.0
 * @since 2.0.0
 */
class WC_Product_Vendors_Commission {
	public $table_name;
	protected $masspay;
	private $table_columns = array(
		'id'                          => '%d',
		'order_id'                    => '%d',
		'order_item_id'               => '%d',
		'order_date'                  => '%s',
		'vendor_id'                   => '%d',
		'vendor_name'                 => '%s',
		'product_id'                  => '%d',
		'variation_id'                => '%d',
		'product_name'                => '%s',
		'variation_attributes'        => '%s',
		'product_amount'              => '%s',
		'product_quantity'            => '%s',
		'product_shipping_amount'     => '%s',
		'product_shipping_tax_amount' => '%s',
		'product_tax_amount'          => '%s',
		'product_commission_amount'   => '%s',
		'total_commission_amount'     => '%s',
		'commission_status'           => '%s',
		'paid_date'                   => '%s',
	);

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct( WC_Product_Vendors_Vendor_Payout_Interface $masspay ) {
		global $wpdb;

		$this->table_name = WC_PRODUCT_VENDORS_COMMISSION_TABLE;

		$this->masspay = $masspay;

		return true;
	}

	/**
	 * Get the vendor total earned commission on a specified order.
	 *
	 * @since 2.1.0
	 * @version 2.4.0
	 * @param string $vendor_id
	 * @param string $order_id
	 *
	 * @return array|bool Array values or false if none found.
	 */
	public function get_vendor_earned_commission_by_order_id( $vendor_id, $order_id ) {
		if ( empty( $vendor_id ) || empty( $order_id ) ) {
			return false;
		}

		$cache_key  = 'wcpv_earned_commission_' . $vendor_id . '_' . $order_id;
		$commission = get_transient( $cache_key );

		if ( false === $commission ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = "SELECT SUM( `total_commission_amount` ) FROM {$this->table_name} WHERE `order_id` = %d AND `vendor_id` = %d";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$commission = $wpdb->get_var( $wpdb->prepare( $sql, absint( $order_id ), absint( $vendor_id ) ) );

			set_transient( $cache_key, $commission, HOUR_IN_SECONDS );
		}

		if ( ! empty( $commission ) ) {
			return $commission;
		}

		return false;
	}

	/**
	 * Gets all commission that is tied to a specific order id.
	 *
	 * @since 2.0.35
	 * @version 2.4.0
	 * @param int    $order_id          Order ID.
	 * @param string $commission_status Commission status.
	 * @return array|bool Array of commissions or false if none found.
	 */
	public function get_commission_by_order_id( $order_id = null, $commission_status = 'all' ) {
		if ( null === $order_id ) {
			return false;
		}

		$cache_key   = 'wcpv_commission_order_' . $order_id . '_' . $commission_status;
		$commissions = get_transient( $cache_key );

		if ( false === $commissions ) {
			global $wpdb;

			if ( 'all' !== $commission_status ) {
				$commission_status = array( strtolower( wc_clean( $commission_status ) ) );
			} else {
				$commission_status = array( 'paid', 'unpaid', 'void' );
			}

			$commission_status = implode( ',', $commission_status );

			$commissions = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$this->table_name}
					WHERE `order_id` = %d
					AND `commission_status` IN (%s)",
					absint( $order_id ),
					$commission_status
				)
			);

			set_transient( $cache_key, $commissions, HOUR_IN_SECONDS );
		}

		if ( ! empty( $commissions ) ) {
			return $commissions;
		}

		return false;
	}

	/**
	 * Inserts a new commission record
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function insert( $order_id = '', $order_item_id = '', $order_date = NULL, $vendor_id = '', $vendor_name = '', $product_id = '', $variation_id = '', $product_name = '', $variation_attributes = '', $product_amount = '', $product_qty = '1', $product_shipping_amount = NULL, $product_shipping_tax_amount = NULL, $product_tax_amount = NULL, $product_commission_amount = '0', $total_commission_amount = '0', $commission_status = 'unpaid', $paid_date = NULL ) {

		global $wpdb;

		$sql = "INSERT INTO {$this->table_name} ( `order_id`, `order_item_id`, `order_date`, `vendor_id`, `vendor_name`, `product_id`, `variation_id`, `product_name`, `variation_attributes`, `product_amount`, `product_quantity`, `product_shipping_amount`, `product_shipping_tax_amount`, `product_tax_amount`, `product_commission_amount`, `total_commission_amount`, `commission_status`, `paid_date` )";

		$sql .= " VALUES ( %d, %d, %s, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )";

		$wpdb->query( $wpdb->prepare( $sql, $order_id, $order_item_id, $order_date, $vendor_id, $vendor_name, $product_id, $variation_id, $product_name, $variation_attributes, $product_amount, $product_qty, $product_shipping_amount, $product_shipping_tax_amount, $product_tax_amount, $product_commission_amount, $total_commission_amount, $commission_status, $paid_date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$last_id = $wpdb->insert_id;

		// Clear caches when new commission is inserted.
		$this->clear_commission_caches();

		do_action( 'wcpv_commissions_inserted' );

		return $last_id;
	}

	/**
	 * Should update commission data.
	 *
	 * @since 2.1.72
	 * @global WPDB $wpdb
	 */
	public function update( array $data, int $commission_id ): bool {
		global $wpdb;
		$placeholders = [];

		// Do not allow to edit id column.
		if ( array_key_exists( 'id', $data ) ) {
			unset( $data['id'] );
		}

		if ( array_diff_key( $data, $this->table_columns ) ) {
			throw new \InvalidArgumentException( sprintf(
				'Please provide valid table data. Invalid column(s):%1$s',
				implode( ',', array_keys( array_diff_key( $data, $this->table_columns ) ) ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			) );
		}

		foreach ( $data as $column_id => $value ) {
			$placeholders[] = $this->table_columns[ $column_id ];
		}

		$row_updated = (bool) $wpdb->update(
			$this->table_name,
			$data,
			[ 'id' => $commission_id ],
			$placeholders,
			[ 'id' => $this->table_columns['id'] ]
		);

		// Clear caches when commission is updated.
		$this->clear_commission_caches();

		/**
		 * Fire action hook.
		 *
		 * @since 2.1.72
		 */
		do_action( 'wcpv_commissions_updated', $data, $commission_id );

		return $row_updated;
	}

	/**
	 * Pays the vendor their commission using passed in mass payments
	 *
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $commission_ids list of commission ids
	 * @return bool
	 */
	public function pay( $commission_ids = array() ) {
		if ( empty( $commission_ids ) ) {
			WC_Product_Vendors_Logger::log( 'No commission to pay' );
			return;
		}

		$commission_data = $this->get_commission_data( $commission_ids );

		if ( empty( $commission_data ) ) {
			throw new Exception( __( 'No valid commission to pay.', 'woocommerce-product-vendors' ) ); // phpcs:ignore
		}

		// we want to combine each vendors total commission so that store owners
		// will not be charged per transaction for all items for each vendor
		if ( apply_filters( 'wcpv_combine_total_commission_payout_per_vendor', true ) ) {
			$commission_data = $this->combine_total_commission_per_vendor_and_order( $commission_data );
		}

		$this->masspay->do_payment( $commission_data );

		return true;
	}

	/**
	 * Gets commission data with caching
	 *
	 * @since 2.0.0
	 * @version 2.4.0
	 * @param array $commission_ids List of commission IDs.
	 * @return array|null Commission data or null if empty.
	 */
	public function get_commission_data( $commission_ids = array() ) {
		if ( empty( $commission_ids ) ) {
			return null;
		}

		// Get only the keys (commission id).
		$commission_ids = array_keys( $commission_ids );

		// Generate cache key based on commission IDs.
		$cache_key = 'wcpv_commission_' . md5( wp_json_encode( $commission_ids ) );

		// Try to get cached data.
		$commissions = get_transient( $cache_key );

		if ( false === $commissions ) {
			global $wpdb;

			// Create placeholders for each commission ID.
			$placeholders = implode( ',', array_fill( 0, count( $commission_ids ), '%d' ) );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = sprintf( "SELECT DISTINCT `id`, `order_id`, `order_item_id`, `vendor_id`, `total_commission_amount` FROM {$this->table_name} WHERE `id` IN ( %s ) AND `commission_status` = 'unpaid'", $placeholders );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$commissions = $wpdb->get_results( $wpdb->prepare( $sql, array_map( 'absint', $commission_ids ) ) );

			// Cache for 1 hour.
			set_transient( $cache_key, $commissions, HOUR_IN_SECONDS );
		}

		return $commissions;
	}

	/**
	 * Combines the total commission per vendor and order id.
	 *
	 * @access public
	 * @since 2.0.6
	 * @since 2.1.8
	 * @param array $commission_data
	 * @return array $combined_commission
	 */
	public function combine_total_commission_per_vendor_and_order( $commission_data = array() ) {
		if ( empty( $commission_data ) ) {
			return null;
		}

		$combined_commission = array();

		foreach( $commission_data as $commission ) {
			$vendor_id = $commission->vendor_id;
			$order_id  = $commission->order_id;
			if ( ! isset( $combined_commission[ $order_id ][ $vendor_id ] ) ) {
				if ( ! isset( $combined_commission[ $order_id ] ) ) {
					$combined_commission[ $order_id ] = array();
				}
				$combined_commission[ $order_id ][ $vendor_id ] = $commission;
			} else {
				// add to the total commission
				$combined_commission[ $order_id ][ $vendor_id ]->total_commission_amount += (float) $commission->total_commission_amount;
			}
		}

		// Flatten to a 1D array.
		$flat = call_user_func_array( 'array_merge', $combined_commission );

		return $flat;
	}

	/**
	 * Gets the list of unpaid commission ids
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $filtered_commission_ids
	 */
	public function get_unpaid_commission_ids() {
		global $wpdb;

		$commissions = $wpdb->get_results( "SELECT DISTINCT `id`, `order_id` FROM {$this->table_name} WHERE `commission_status` = 'unpaid'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$filtered_commission_ids = array();

		// filter out only commission that are unpaid and order status is processing, completed or partial-payment (deposit order).
		foreach( $commissions as $commission ) {
			$order = wc_get_order( $commission->order_id );

			if ( ! is_object( $order ) ) {
				continue;
			}

			$order_status = $order->get_status();

			/**
			 * Filter to determine if commission is valid to pay.
			 * This filter is used to allow other plugins to filter out commissions
			 *
			 * @since 2.4.3
			 * @param bool $is_valid     Whether the commission is valid to pay.
			 * @param object $commission The commission object.
			 * @param object $order      The order object.
			 */
			$is_valid = apply_filters( 'wcpv_commission_is_valid_to_pay', true, $commission, $order );

			if ( ( 'completed' === $order_status || 'processing' === $order_status || 'partial-payment' === $order_status ) && $is_valid ) {
				$filtered_commission_ids[ $commission->id ] = $commission->id;
			}
		}

		return $filtered_commission_ids;
	}

	/**
	 * Gets the list of unpaid commissions with caching
	 *
	 * @since 2.0.0
	 * @version 2.4.0
	 * @return array|false Array of unpaid commissions or false if none found.
	 */
	public function get_unpaid_commission_data() {
		$cache_key = 'wcpv_unpaid_commission_data';

		$commissions = get_transient( $cache_key );

		if ( false === $commissions ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = "SELECT * FROM {$this->table_name} WHERE commission_status = %s";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$commissions = $wpdb->get_results( $wpdb->prepare( $sql, 'unpaid' ) );

			// Cache for 30 minutes since unpaid data changes more frequently.
			set_transient( $cache_key, $commissions, 30 * MINUTE_IN_SECONDS );
		}

		return $commissions;
	}

	/**
	 * Deletes a commission record
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $commission_id
	 * @return bool
	 */
	public function delete( $commission_id = 0 ) {
		global $wpdb;

		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT `order_id` FROM {$this->table_name} WHERE `id` = %d", absint( $commission_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Clear caches before deletion.
		$this->clear_commission_caches();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE `id` = %d", absint( $commission_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// also delete order post meta.
		$order = wc_get_order( $order_id );
		if ( ! empty( $order ) ) {
			$order->delete_meta_data( '_wcpv_commission_added' );
			$order->save_meta_data();
		}

		do_action( 'wcpv_commissions_deleted' );

		return true;
	}

	/**
	 * Deletes a commission record by order id.
	 *
	 * @access public
	 * @since 2.1.27
	 * @version 2.1.27
	 * @param int $order_id
	 * @return bool
	 */
	public function delete_by_order_id( $order_id = 0 ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE `order_id` = %d", absint( $order_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$order = wc_get_order( absint( $order_id ) );

		if ( $order ) {
			// also delete order post meta
			$order->delete_meta_data( '_wcpv_commission_added' );
			$order->save_meta_data();
		}

		do_action( 'wcpv_commissions_deleted' );

		return true;
	}

	/**
	 * Update commission status with cache invalidation
	 *
	 * @since 2.0.0
	 * @version 2.4.0
	 * @param int    $commission_id Commission ID.
	 * @param int    $order_item_id Order item ID.
	 * @param string $commission_status New commission status.
	 * @return bool Success.
	 */
	public function update_status( $commission_id, $order_item_id, $commission_status ) {
		global $wpdb;

		$sql = "UPDATE {$this->table_name}";
		$sql .= " SET `commission_status` = %s,";

		if ( 'paid' === $commission_status ) {
			$sql .= " `paid_date` = %s";
			$date = date( 'Y-m-d H:i:s' );
		}

		if ( 'unpaid' === $commission_status ) {
			$sql .= " `paid_date` = %s";
			$date = '0000-00-00 00:00:00';
		}

		if ( 'void' === $commission_status ) {
			$sql .= " `paid_date` = %s";
			$date = '0000-00-00 00:00:00';
		}

		$sql .= " WHERE `id` = %d";
		$sql .= " AND `commission_status` != %s";

		// Updates the commission table.
		$wpdb->query(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Complex dynamic query.
				$commission_status,
				$date,
				(int) $commission_id,
				$commission_status
			)
		);

		// Clear related caches.
		$this->clear_commission_caches( $commission_id );

		// Also update the order item meta to leave a trail.
		$sql = "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta";
		$sql .= " SET `meta_value` = %s";
		$sql .= " WHERE `order_item_id` = %d";
		$sql .= " AND `meta_key` = %s";

		$wpdb->query(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Complex dynamic query.
				$commission_status,
				$order_item_id,
				'_commission_status'
			)
		);

		/**
		 * Fires after commission status is updated.
		 *
		 * @since 2.0.0
		 * @param int $commission_id Commission ID.
		 * @param int $order_item_id Order item ID.
		 */
		do_action( 'wcpv_commissions_updated', $commission_id, $order_item_id );

		return true;
	}

	/**
	 * Clear commission related caches
	 *
	 * @since 2.4.0
	 */
	private function clear_commission_caches() {
		global $wpdb;

		// Clear unpaid commission data cache.
		delete_transient( 'wcpv_unpaid_commission_data' );

		// Get all commission-related transients.
		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name
				FROM {$wpdb->options}
				WHERE option_name LIKE %s
				OR option_name LIKE %s
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_wcpv_commission_' ) . '%',
				$wpdb->esc_like( '_transient_wcpv_earned_commission_' ) . '%',
				$wpdb->esc_like( '_transient_wcpv_commission_order_' ) . '%'
			)
		);

		// Delete each transient.
		foreach ( $transients as $transient ) {
			$transient_name = str_replace( '_transient_', '', $transient );
			delete_transient( $transient_name );
		}
	}

	/**
	 * Calculates the commission for a product based on an order
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $product_id
	 * @param int $vendor_id
	 * @param int $quantity
	 * @return float $commission
	 */
	public function calc_order_product_commission( $product_id, $vendor_id, $product_amount, $quantity = 1 ) {
		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );

		$commission_array = WC_Product_Vendors_Utils::get_product_commission( $product_id, $vendor_data );

		$commission = $commission_array['commission'];
		$type       = $commission_array['type'];

		// bail if commission to set to 0.
		if ( '0' == $commission ) {
			return $commission;
		}

		if ( 'percentage' === $type ) {
			$commission = $product_amount * ( abs( $commission ) / 100 );

		} else { // fixed commission.
			$commission = ( abs( $commission ) * absint( $quantity ) );
		}

		return round( $commission, wc_get_rounding_precision() );
	}

	/**
	 * Queries the commission table based on filters
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int    $order_id
	 * @param string $year
	 * @param string $month
	 * @param string $commission_status
	 * @param int    $vendor_id
	 * @return array $query
	 */
	public function csv_filtered_query( $order_id = '', $year = '', $month = '', $commission_status = '', $vendor_id = '' ) {
		global $wpdb;

		// Query WHERE.
		$sql_where = array();

		// if order_id is set (search), we don't need other filters as search takes priority.
		$order_id = absint( $order_id );
		if ( $order_id ) {
			$sql_where[] = $wpdb->prepare( '`commission`.`order_id` = %d', $order_id );
		} else {
			$month = absint( $month );
			$year  = absint( $year );

			if ( $month && $year ) {
				$sql_where[] = $wpdb->prepare(
					'MONTH( `commission`.`order_date` ) = %d AND YEAR( `commission`.`order_date` ) = %d',
					$month,
					$year
				);
			}

			$commission_status = trim( $commission_status );
			if ( $commission_status ) {
				$sql_where[] = $wpdb->prepare( '`commission`.`commission_status` = %s', $commission_status );
			}

			$vendor_id = absint( $vendor_id );
			if ( $vendor_id ) {
				$sql_where[] = $wpdb->prepare( '`commission`.`vendor_id` = %d', $vendor_id );
			}
		}

		$sql_where = ( $sql_where ? ( ' WHERE ' . implode( ' AND ', $sql_where ) ) : '' );

		$items = $wpdb->get_results(
			'SELECT `commission`.`order_id`, `commission`.`order_date`, `commission`.`vendor_id`, `commission`.`vendor_name`,
			`commission`.`product_name`, `commission`.`variation_attributes`, `commission`.`product_amount`,
			`commission`.`product_quantity`, `commission`.`product_shipping_amount`,
			`commission`.`product_shipping_tax_amount`, `commission`.`product_tax_amount`,
			`commission`.`product_commission_amount`, `commission`.`total_commission_amount`,
			`commission`.`commission_status`, `commission`.`paid_date`
			FROM ' . WC_PRODUCT_VENDORS_COMMISSION_TABLE . ' AS `commission` ' . $sql_where, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$vendor_ids = array_unique( wp_list_pluck( $items, 'vendor_id' ) );
		_prime_term_caches( $vendor_ids );
		// Convert vendor id to vendor name.
		foreach ( $items as &$commission ) {
			$vendor = get_term( (int) $commission['vendor_id'], WC_PRODUCT_VENDORS_TAXONOMY );
			if ( ! is_wp_error( $vendor ) && is_object( $vendor ) ) {
				// Replace vendor name with current vendor name.
				$commission['vendor_name'] = wp_strip_all_tags( $vendor->name );
			}

			// Remove the vendor ID as it's not needed anymore.
			unset( $commission['vendor_id'] );
		}

		// we need to unserialize possible variation attributes.
		$items = array_map( array( 'WC_Product_Vendors_Utils', 'unserialize_attributes' ), $items );

		// add column headers.
		$headers = array(
			__( 'Order ID', 'woocommerce-product-vendors' ),
			__( 'Order Date', 'woocommerce-product-vendors' ),
			__( 'Vendor Name', 'woocommerce-product-vendors' ),
			__( 'Product Name', 'woocommerce-product-vendors' ),
			__( 'Variation Attributes', 'woocommerce-product-vendors' ),
			__( 'Product Amount', 'woocommerce-product-vendors' ),
			__( 'Product Quantity', 'woocommerce-product-vendors' ),
			__( 'Product Shipping Amount', 'woocommerce-product-vendors' ),
			__( 'Product Shipping Tax Amount', 'woocommerce-product-vendors' ),
			__( 'Product Tax Amount', 'woocommerce-product-vendors' ),
			__( 'Product Commission Amount', 'woocommerce-product-vendors' ),
			__( 'Total Commission Amount', 'woocommerce-product-vendors' ),
			__( 'Commission Paid Status', 'woocommerce-product-vendors' ),
			__( 'Commission Paid Date', 'woocommerce-product-vendors' ),
		);
		array_unshift( $items, $headers );

		// convert the array to string recursively.
		$items = implode( PHP_EOL, array_map( array( 'WC_Product_Vendors_Utils', 'convert2string' ), $items ) );

		return $items;
	}

	/**
	 * Should return commission id for order item.
	 *
	 * @since 2.1.74
	 *
	 * @param int $order_item_id Order item ID.
	 * @param int $order_id Order ID.
	 *
	 * @return int
	 */
	public function get_commission_id_by_order_item_id( $order_item_id, $order_id ) {
		global $wpdb;

		// check for existing commission data.
		$check_sql = 'SELECT `id`';
		$check_sql .= ' FROM ' . WC_PRODUCT_VENDORS_COMMISSION_TABLE;
		$check_sql .= ' WHERE `order_item_id` = %d';
		$check_sql .= ' AND `order_id` = %d';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$check_sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$order_item_id,
				$order_id
			)
		);
	}

	/**
	 * Should return commission by order item id and order id if exists, null otherwise.
	 *
	 * @since 2.1.72
	 *
	 * @param int $order_item_id Order item id.
	 * @param int $order_id Order id.
	 *
	 * @return array|null
	 */
	public function get_commission_by_order_item_id( $order_item_id, $order_id ) {
		global $wpdb;
		$table_name = WC_PRODUCT_VENDORS_COMMISSION_TABLE;

		$commission = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE `order_item_id` = %d AND `order_id` = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_item_id,
				$order_id
			),
			ARRAY_A
		);

		return ! empty( $commission ) ? current( $commission ) : null;
	}
}
