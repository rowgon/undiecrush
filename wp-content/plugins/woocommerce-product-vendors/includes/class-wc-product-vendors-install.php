<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Installation/Activation Class.
 *
 * Handles the activation/installation of the plugin.
 *
 * @category Installation
 * @package  WooCommerce Product Vendors/Install
 * @version  2.0.0
 */
class WC_Product_Vendors_Install {
	/**
	 * Object to manage caps.
	 *
	 * @var WC_Product_Vendors_Roles_Caps
	 */
	private static $roles;

	/** @var array DB updates that need to be run */
	private static $db_updates = array(
		'2.0.0' => 'admin/updates/wc-product-vendors-update-2.0.0.php',
	);

	/**
	 * Number of vendors migrated per legacy-notes batch.
	 *
	 * @var int
	 */
	const NOTES_MIGRATION_BATCH_SIZE = 100;

	/**
	 * Times the legacy-notes migration is rescheduled before giving up.
	 *
	 * @var int
	 */
	const NOTES_MIGRATION_MAX_ATTEMPTS = 3;

	/**
	 * Action Scheduler hook running a legacy-notes migration batch.
	 *
	 * @var string
	 */
	const NOTES_MIGRATION_HOOK = 'wcpv_migrate_admin_notes_batch';

	/**
	 * Action Scheduler group for the legacy-notes migration.
	 *
	 * @var string
	 */
	const NOTES_MIGRATION_GROUP = 'woocommerce-product-vendors';

	/**
	 * Initialize hooks
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function init( WC_Product_Vendors_Roles_Caps $roles ) {
		self::$roles = $roles;

		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'update_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'updated_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'activate_notice' ) );

		// Resumes an interrupted or failed notes migration; the version bump in install() is
		// not enough on its own, since check_version() only fires once per version.
		add_action( 'admin_init', array( __CLASS__, 'maybe_schedule_legacy_notes_migration' ) );

		// Registered on every load (incl. cron) so Action Scheduler can run migration batches.
		add_action( self::NOTES_MIGRATION_HOOK, array( __CLASS__, 'migrate_legacy_notes_batch' ) );

		return true;
	}

	/**
	 * Checks the plugin version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'wcpv_version' ) != WC_PRODUCT_VENDORS_VERSION ) ) {
			self::install();

			do_action( 'wcpv_updated' );
		}

		return true;
	}

	/**
	 * Perform actions
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function install_actions() {
		// add roles one time
		if ( 'yes' !== get_option( 'wcpv_add_roles' ) ) {
			self::$roles->add_default_roles();

			update_option( 'wcpv_add_roles', 'yes' );
		}

		// Needs to run every time since we do not know when bookings could be updated.
		self::$roles->remove_deprecated_caps();

		if ( ! empty( $_GET['dismiss_wcpv'] ) &&
			isset( $_GET['_wcpv_v2_notice_dismiss_nonce'] ) &&
			wp_verify_nonce( wc_clean( wp_unslash( $_GET['_wcpv_v2_notice_dismiss_nonce'] ) ), 'wcpv_v2_notice_dismiss_nonce' ) &&
			current_user_can( 'manage_options' )
		) {
			delete_option( 'wcpv_show_update_notice' );
			add_option( 'wcpv_show_update_notice', false );
		}

		if ( ! empty( $_GET['do_update_wcpv'] ) &&
			isset( $_GET['_wcpv_v2_notice_update_nonce'] ) &&
			wp_verify_nonce( wc_clean( wp_unslash( $_GET['_wcpv_v2_notice_update_nonce'] ) ), 'wcpv_v2_notice_update_nonce' ) &&
			current_user_can( 'manage_options' )
		) {
			self::update();

			wp_safe_redirect( add_query_arg( 'wcpv-updated', 'true', admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpv_vendor_settings' ) ) );
			exit;
		}

		return true;
	}

	/**
	 * Updates the plugin version in db
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_plugin_version() {
		delete_option( 'wcpv_version' );
		add_option( 'wcpv_version', WC_PRODUCT_VENDORS_VERSION );

		return true;
	}

	/**
	 * Updates the plugin version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_wcpv_version() {
		delete_option( 'wcpv_version' );
		add_option( 'wcpv_version', WC_PRODUCT_VENDORS_VERSION );

		return true;
	}

	/**
	 * Updates the commission table db version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_commission_db_version( $version = null ) {
		delete_option( 'wcpv_commissions_db_version' );
		add_option( 'wcpv_commissions_db_version', is_null( $version ) ? WC_PRODUCT_VENDORS_VERSION : $version );

		return true;
	}

	/**
	 * Updates the per product shipping table db version
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update_per_product_shipping_db_version( $version = null ) {
		delete_option( 'wcpv_per_product_shipping_db_version' );
		add_option( 'wcpv_per_product_shipping_db_version', is_null( $version ) ? WC_PRODUCT_VENDORS_VERSION : $version );

		return true;
	}

	/**
	 * Updates the admin notes table db version
	 *
	 * @since 2.6.0
	 * @param string $version Optional version to set.
	 * @return bool
	 */
	private static function update_admin_notes_db_version( $version = null ) {
		delete_option( 'wcpv_admin_notes_db_version' );
		add_option( 'wcpv_admin_notes_db_version', is_null( $version ) ? WC_PRODUCT_VENDORS_VERSION : $version );

		return true;
	}

	/**
	 * Schedules the legacy vendor-notes migration when it still has work to do.
	 *
	 * Runs in Action Scheduler batches so it scales to large marketplaces without hitting
	 * max_execution_time. Hooked on `admin_init` (and called from install()) so an interrupted
	 * or failed run resumes on a later request — the version bump in install() must not be the
	 * only trigger. Falls back to a synchronous run when Action Scheduler is absent.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	public static function maybe_schedule_legacy_notes_migration() {
		if ( 'yes' === get_option( 'wcpv_admin_notes_migrated' ) ) {
			return;
		}

		// A batch is already pending or running; let it finish.
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::NOTES_MIGRATION_HOOK, null, self::NOTES_MIGRATION_GROUP ) ) {
			return;
		}

		$attempts = absint( get_option( 'wcpv_admin_notes_migration_attempts', 0 ) );

		// Give up rather than rescheduling a persistently failing migration forever.
		if ( $attempts >= self::NOTES_MIGRATION_MAX_ATTEMPTS ) {
			if ( self::NOTES_MIGRATION_MAX_ATTEMPTS === $attempts ) {
				update_option( 'wcpv_admin_notes_migration_attempts', $attempts + 1 );

				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error(
						sprintf( 'Legacy vendor notes migration abandoned after %d failed attempts.', self::NOTES_MIGRATION_MAX_ATTEMPTS ),
						array( 'source' => 'woocommerce-product-vendors' )
					);
				}
			}

			return;
		}

		update_option( 'wcpv_admin_notes_migration_attempts', $attempts + 1 );
		delete_option( 'wcpv_admin_notes_migration_failed' );

		// Action Scheduler ships with WooCommerce; process synchronously if it is somehow absent.
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			$offset = 0;

			do {
				$processed = self::process_legacy_notes_slice( $offset );

				if ( false === $processed ) {
					return;
				}

				$offset += self::NOTES_MIGRATION_BATCH_SIZE;
			} while ( self::NOTES_MIGRATION_BATCH_SIZE === $processed );

			self::finalize_legacy_notes_migration();

			return;
		}

		as_schedule_single_action( time(), self::NOTES_MIGRATION_HOOK, array( 0 ), self::NOTES_MIGRATION_GROUP );
	}

	/**
	 * Processes one batch of the legacy vendor-notes migration, then schedules the next.
	 *
	 * Action Scheduler callback. A transient failure leaves the migration unfinalised with
	 * nothing pending, so maybe_schedule_legacy_notes_migration() restarts it on a later load.
	 *
	 * @since 2.6.0
	 * @param int $offset Vendor-term offset for this batch.
	 * @return void
	 */
	public static function migrate_legacy_notes_batch( $offset = 0 ) {
		$offset    = absint( $offset );
		$processed = self::process_legacy_notes_slice( $offset );

		if ( false === $processed ) {
			return;
		}

		if ( self::NOTES_MIGRATION_BATCH_SIZE === $processed ) {
			as_schedule_single_action( time(), self::NOTES_MIGRATION_HOOK, array( $offset + self::NOTES_MIGRATION_BATCH_SIZE ), self::NOTES_MIGRATION_GROUP );

			return;
		}

		self::finalize_legacy_notes_migration();
	}

	/**
	 * Migrates one slice of vendors (their legacy notes field) into the timeline.
	 *
	 * Each vendor's `vendor_data['notes']` is stored as a migrated timeline note, then removed
	 * from the vendor data. The legacy field is only cleared after a successful insert; a
	 * per-vendor failure is recorded so the run is not marked complete.
	 *
	 * @since 2.6.0
	 * @param int $offset Vendor-term offset.
	 * @return int|false Vendor terms fetched (a full batch means more remain), or false on a
	 *                   transient failure that should abort this run without finalising it.
	 */
	private static function process_legacy_notes_slice( $offset ) {
		if ( ! class_exists( 'WC_Product_Vendors_Admin_Notes' ) || ! class_exists( 'WC_Product_Vendors_Utils' ) ) {
			return false;
		}

		$vendors = get_terms(
			array(
				'taxonomy'   => WC_PRODUCT_VENDORS_TAXONOMY,
				'hide_empty' => false,
				'number'     => self::NOTES_MIGRATION_BATCH_SIZE,
				'offset'     => absint( $offset ),
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $vendors ) ) {
			return false;
		}

		foreach ( $vendors as $vendor ) {
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor->term_id );

			if ( empty( $vendor_data['notes'] ) ) {
				continue;
			}

			$note_id = WC_Product_Vendors_Admin_Notes::add_system_note(
				WC_Product_Vendors_Admin_Notes::OBJECT_TYPE_VENDOR,
				$vendor->term_id,
				$vendor_data['notes'],
				array( 'source' => WC_Product_Vendors_Admin_Notes::SOURCE_MIGRATED )
			);

			if ( is_wp_error( $note_id ) ) {
				update_option( 'wcpv_admin_notes_migration_failed', 'yes' );

				continue;
			}

			unset( $vendor_data['notes'] );
			WC_Product_Vendors_Utils::set_vendor_data( $vendor->term_id, $vendor_data );
		}

		return count( $vendors );
	}

	/**
	 * Finalises the legacy notes migration once every batch has run.
	 *
	 * Marks it complete only when nothing failed. Otherwise the flag is cleared without setting
	 * `wcpv_admin_notes_migrated`, so the next admin_init reschedules the run — already-migrated
	 * vendors have their legacy field cleared, making a re-run idempotent.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	private static function finalize_legacy_notes_migration() {
		if ( 'yes' !== get_option( 'wcpv_admin_notes_migration_failed' ) ) {
			update_option( 'wcpv_admin_notes_migrated', 'yes' );
			delete_option( 'wcpv_admin_notes_migration_attempts' );
		}

		delete_option( 'wcpv_admin_notes_migration_failed' );
	}

	/**
	 * Perform update action
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function update() {
		$current_commissions_db_version = '1.0.0';

		foreach ( self::$db_updates as $version => $updater ) {
			if ( version_compare( $current_commissions_db_version, $version, '<' ) ) {
				include( $updater );
				self::update_commission_db_version( $version );
			}
		}

		self::update_commission_db_version();
		self::update_per_product_shipping_db_version();

		delete_option( 'wcpv_show_update_notice' );
		add_option( 'wcpv_show_update_notice', false );

		return true;
	}

	/**
	 * Checks to see if we need update for 2.0.0
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function needs_update() {
		global $wpdb;

		$commissions = get_posts( array( 'posts_per_page' => 1, 'post_type' => 'shop_commission', 'post_status' => array( 'publish', 'private' ) ) );
		$vendors = $wpdb->get_row( "SELECT term_id FROM $wpdb->term_taxonomy WHERE `taxonomy` = 'shop_vendor'" );

		// if 1.0.0 commissions or vendors exists we need to update
		if ( ! empty( $commissions ) || ( ! is_wp_error( $vendors ) && ! empty( $vendors ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show update notice
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function update_notice() {
		$show_notice = get_option( 'wcpv_show_update_notice', true );

		if ( self::needs_update() && $show_notice ) {
			include_once( 'admin/updates/views/html-update-notice-2.0.0.php' );
		}

		return true;
	}

	/**
	 * Show updated notice
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function updated_notice() {
		if ( ! empty( $_GET['wcpv-updated'] ) ) {
		?>
			<div class="update-nag notice">
				<p><?php esc_html_e( 'WooCommerce Product Vendors data update complete. Thank you for updating to the latest version!', 'woocommerce-product-vendors' ); ?></p>
			</div>
		<?php
		}

		return true;
	}

	/**
	 * Do installs
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function install() {
		if ( ! defined( 'WCPV_INSTALLING' ) ) {
			define( 'WCPV_INSTALLING', true );
		}

		self::create_tables();
		self::update_commission_db_version();
		self::update_per_product_shipping_db_version();
		self::update_admin_notes_db_version();
		self::maybe_schedule_legacy_notes_migration();
		self::update_wcpv_version();
		self::clear_reports_transients();

		self::$roles->add_manager_caps();

		flush_rewrite_rules();

		return true;
	}

	/**
	 * Prepare tables for modification/add
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wcpv_commissions';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}wcpv_commissions` LIKE 'id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}wcpv_commissions DROP PRIMARY KEY, ADD `id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wcpv_per_product_shipping_rules';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}wcpv_per_product_shipping_rules` LIKE 'rule_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}wcpv_per_product_shipping_rules DROP PRIMARY KEY, ADD `rule_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		dbDelta( self::get_schema() );

		return true;
	}

	/**
	 * Shows activation notice for next steps
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function activate_notice() {
		$show_notice = get_option( 'wcpv_show_activate_notice', true );

		if ( $show_notice ) {
			?>
			<div class="updated woocommerce-message woocommerce-product-vendors-activated" style="border-left-color: #aa559a;">
				<h4><?php esc_html_e( 'WooCommerce Product Vendors Installed &#8211; To get started,', 'woocommerce-product-vendors' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpv_vendor_settings' ) ); ?>"><?php esc_html_e( 'configure your vendor settings', 'woocommerce-product-vendors' ); ?></a></h4>
			</div>
			<?php

			update_option( 'wcpv_show_activate_notice', '0' );
		}
	}

	/**
	 * Clears all reports transients
	 *
	 * @access public
	 * @since 2.0.0
	 * @since 2.2.0 Replace DELETE SQL query with utility class function
	 * @version 2.0.0
	 * @return bool
	 */
	public static function clear_reports_transients() {
		return WC_Product_Vendors_Utils::clear_reports_transients();
	}

	/**
	 * Add tables
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		return "
CREATE TABLE {$wpdb->prefix}wcpv_commissions (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	order_id bigint(20) NOT NULL,
	order_item_id bigint(20) NOT NULL,
	order_date datetime DEFAULT NULL,
	vendor_id bigint(20) NOT NULL,
	vendor_name longtext NOT NULL,
	product_id bigint(20) NOT NULL,
	variation_id bigint(20) NOT NULL,
	product_name longtext NOT NULL,
	variation_attributes longtext NOT NULL,
	product_amount longtext NOT NULL,
	product_quantity longtext NOT NULL,
	product_shipping_amount longtext,
	product_shipping_tax_amount longtext,
	product_tax_amount longtext,
	product_commission_amount longtext NOT NULL,
	total_commission_amount longtext NOT NULL,
	commission_status varchar(20) NOT NULL DEFAULT 'unpaid',
	paid_date datetime DEFAULT NULL,
	PRIMARY KEY  (id)
) $collate;
CREATE TABLE {$wpdb->prefix}wcpv_per_product_shipping_rules (
	rule_id bigint(20) NOT NULL AUTO_INCREMENT,
	product_id bigint(20) NOT NULL,
	rule_country varchar(10) NOT NULL,
	rule_state varchar(10) NOT NULL,
	rule_postcode varchar(200) NOT NULL,
	rule_cost varchar(200) NOT NULL,
	rule_item_cost varchar(200) NOT NULL,
	rule_order bigint(20) NOT NULL,
	PRIMARY KEY  (rule_id)
) $collate;
CREATE TABLE {$wpdb->prefix}wcpv_admin_notes (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	object_type varchar(20) NOT NULL,
	object_id bigint(20) unsigned NOT NULL,
	note longtext NOT NULL,
	source varchar(50) NOT NULL DEFAULT 'manual',
	type varchar(50) NOT NULL DEFAULT 'note',
	author_id bigint(20) unsigned NOT NULL DEFAULT 0,
	meta longtext NULL,
	created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	resolved_at datetime NULL DEFAULT NULL,
	resolved_by bigint(20) unsigned NULL DEFAULT NULL,
	PRIMARY KEY  (id),
	KEY object_type_id (object_type,object_id),
	KEY author_id (author_id),
	KEY source (source),
	KEY type (type),
	KEY resolved_at (resolved_at)
) $collate;
		";

		return true;
	}
}

WC_Product_Vendors_Install::init( new WC_Product_Vendors_Roles_Caps );
