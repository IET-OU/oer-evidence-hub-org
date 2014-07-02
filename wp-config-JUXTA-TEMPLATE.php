<?php
/**
 * Juxtalearn Hub site.
 */


/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'juxtalearn_hub_wp');

/** MySQL database username */
define('DB_USER', 'juxtalearn_user');

/** MySQL database password */
define('DB_PASSWORD', '*****');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');



/* NDF: HTTP proxy (http://wpengineer.com/1227/wordpress-proxysupport)
*/
define('WP_PROXY_HOST', 'wwwcache.open.ac.uk');
define('WP_PROXY_PORT', '80');


define('SMTP_HOST', 'smtpmail.open.ac.uk');
define('SMTP_PORT', 25);
//ini_set('SMTP', 'smtpmail.open.ac.uk');


/*
  NDF: API stuff, May 2014.
*/
define( 'JXL_COOKIE_SECRET_KEY', NULL );
define( 'JXL_COOKIE_DOMAIN', '.juxtalearn.net' );
//define( 'JXL_COOKIE_DOMAIN', '.escet.urjc.es' );
define( 'JXL_CLIPIT_BASE_URL', 'http://clipit.juxtalearn.net/clipit_apionly' );
//define( 'JXL_CLIPIT_BASE_URL', 'http://jxl1.escet.urjc.es/clipit_dev' );
define( 'JXL_CLIPIT_LOGIN', NULL );
define( 'JXL_CLIPIT_PASSWORD', NULL );
//define( 'JXL_CLIPIT_WP_SET_AUTH_COOKIE', TRUE );
# Legacy.
#define('JXL_CLIPIT_API_URL', 'http://jxl1.escet.urjc.es/clipit_dev/services/api/rest/%s/');



/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_4_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);
##define('WP_DEBUG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
