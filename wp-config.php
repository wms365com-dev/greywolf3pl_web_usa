<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'rpnxqjmy_WPZCQ');

/** Database username */
define('DB_USER', 'rpnxqjmy_WPZCQ');

/** Database password */
define('DB_PASSWORD', '^/.B??#!.KnN.zXA.');

/** Database hostname */
define('DB_HOST', 'localhost');

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
define('AUTH_KEY', 'cdb5331f16e7f48895aade4744d3b8a773ec3ac5bfef1c0d2e535ea932a2e2ba');
define('SECURE_AUTH_KEY', '513ad01ca5cda23d9944e733794fc7996ef9422cb3cc66aa368803e285752bd5');
define('LOGGED_IN_KEY', 'fe201910211b94a2c86b7b908ff50e48faa43c92923559e6395ae825bc460c8a');
define('NONCE_KEY', 'd61bcc04bc241a701352f8efd5eded2a363cd6ae27917ba6e6182f8faf0bcb1f');
define('AUTH_SALT', '9a9bb4b3401a0310202086bb1e503c95f6b2e2fa0279e806be58626951419546');
define('SECURE_AUTH_SALT', 'cc3b72fb8c7aae188ee20c7751858a3fd0eedb8f8043c46bfce01e3349c82e66');
define('LOGGED_IN_SALT', '4cf55b93a614a8c821e67a0781499d52a15fc70cecfdfcfbd129bcbfbbfe3780');
define('NONCE_SALT', '6275aeb837879b54dcac0ddf4647abbf8340aec4b635ee1f6785549c9ebb6426');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'eZi_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 20);
define('EMPTY_TRASH_DAYS', 7);
define('WP_AUTO_UPDATE_CORE', true);

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
