<?php
/**
 * LACE Evidence Hub (Learning Analytics Community Exchange).
 *
 * @link http://evidence.laceproject.eu/
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
define('DB_NAME', 'oer_evidence_hub_wp');

/** MySQL database username */
define('DB_USER', 'evidencehub_user');

/** MySQL database password */
define('DB_PASSWORD', '*****');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');



/* Open University-specific configuration. 
   HTTP proxy (http://wpengineer.com/1227/wordpress-proxysupport)
*/
define('WP_PROXY_HOST', 'wwwcache.open.ac.uk');
define('WP_PROXY_PORT', '80');

define('SMTP_HOST', 'smtpmail.open.ac.uk');
define('SMTP_PORT', 25);
//ini_set('SMTP', 'smtpmail.open.ac.uk');



/* LACE SVG logo, URL field, etc.
*  @link https://github.com/mhawksey/wp-evidence-hub/issues/6
*  @link https://github.com/mhawksey/wp-evidence-hub/issues/21
*/
define( 'WP_EVIDENCE_HUB_SVG_STYLE',
    'svg #barchart .positive, svg #map circle.positive {
  x-fill: rgb(255, 146, 6);  x-OER-Hub-orange: 1;
  fill: #43672B;  x-LACE-green: 1;
}
svg #barchart .negative, svg #map circle.negative {
  fill: #596190;  x-LACE-blue: 1;
}' );
define( '_X_WP_EVIDENCE_HUB_SVG_LOGO', 'images/oer-evidence-hub-logo.svg' );
define( '_X_WP_EVIDENCE_HUB_SVG_LOGO_SCALE', '[ 0.7, 0.7 ]' );
define( 'WP_EVIDENCE_HUB_SVG_LOGO', 'images/LACEweblogo.svg' );
define( 'WP_EVIDENCE_HUB_SVG_LOGO_SCALE', json_encode(array( 0.15, 0.15 )));

define( 'WP_EVIDENCE_HUB_URL_FIELD', TRUE );


/* IET attribution.
*/
define( 'IET_ATTRIBUTION_COPYRIGHT_HTML',
  '&copy; %s <a href="http://www.laceproject.eu/"><abbr title=' .
  '"Learning Analytics Community Exchange">LACE</abbr> Consortium</a> and contributors' );
define( 'IET_ATTRIBUTION_LICENSE_CURIE', 'cc:by/4.0/88x31' );
define( 'IET_ATTRIBUTION_CSS', '#iet-attribution-logo svg g { fill: #596190 }' );
#define( 'IET_ATTRIBUTION_AVATAR_ID', 'images/iet-logo-lace-green-28px.png' );



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
$table_prefix  = 'wp_';

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
