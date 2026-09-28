<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', '/var/www/html/undiecrush/wp-content/plugins/wp-super-cache/' );
define( 'DB_NAME', 'undiecrush' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '+]qeRaTUGlO^%BVs^hre$Zk}|ajy9#|jmDjcf By&)xj.,N%Jh&akALEH:C_`H8g' );
define( 'SECURE_AUTH_KEY',   'sVQ*e!hw*[$Hn?4[KdR$`t=)TZe1mJwuyX8I[r&b,vz7hAvs;s5.khOOAWYKJ=#l' );
define( 'LOGGED_IN_KEY',     '2=uB?45evw@yKd5&MY`se(mi8tKC _%?Lr4XF&s;nEI~.ZzGl+x*/UZ19(+;HSv}' );
define( 'NONCE_KEY',         '~$e{Qh 4M!l{B77m+(FCX&n!_Ij9aj{YqiB#N7w@C>5$hCURD1dC^Kv(-E*U03i#' );
define( 'AUTH_SALT',         'zD:#xDN*M2a7yhq0lQ(l2%R~}Jr)b3G?cfs~W]AIQ1m.mx@[CS<)LUTkPu[%MIno' );
define( 'SECURE_AUTH_SALT',  'oqN@N`T/|4~b2&/^)e6Y?[)Ng l~XbUB|8dHYcL?($oA@nVpU-=J%WsZ@Tp%+Sr#' );
define( 'LOGGED_IN_SALT',    '=wtdOs,$(-88LG1gpXokH[Q,1s9/:f_isOYH_U4>YC9by9.N79{(c,VgH^4:U]`%' );
define( 'NONCE_SALT',        '~Z:hxC_UPAt=((10.{To[Aepq=1*GZ@sx0BG+eA^Uvp}YC!*s>}em|qL;*ZF}@?m' );
define( 'WP_CACHE_KEY_SALT', ':^M,v:]4:dQy {B,MKJ_Nhm7<O#J.L(dRC_cp_edw(1;!~t!,[s#za.*VhYXUfQ@' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/** Security & Performance Hardening by Antigravity */
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
