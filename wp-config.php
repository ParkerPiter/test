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
define( 'DB_NAME', 'test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         '~qplO<clZ&o0Y7D GTfeN`CO1L2>Qc3ts=dIL=Cz/,T#C%&><H-xlrV3&&MU5<2?' );
define( 'SECURE_AUTH_KEY',  'e_Vjdfgf_j`SSyD~djY_i[qU$yA40MDmD66wM8g.M3CaRt~D<8ZW[F7!g6 -_dgd' );
define( 'LOGGED_IN_KEY',    'eqnh8 4o2cj?2.5;Ll~T10WZc *,7kh77KKZ?<&k<]%^-e25~CqEV%2&Zpgs7u0[' );
define( 'NONCE_KEY',        'y3?DZk>]|jC_s*B5P/UbZ)@rnMeKE83NY}==aB-.`cC.>J1;6$qh9{t>RK=Cm]W<' );
define( 'AUTH_SALT',        '@y{siQ 60[0I>Ofif(5.?g,lxJ;M%JGkMxq(@pY2ifKK6faYEhpUOw$_CRme^pTB' );
define( 'SECURE_AUTH_SALT', '}ftol4brB:fGR3nJL$mPVy :T0ZX?:fb5UQ0gP/pS$zX)IS:w1/o#JaWa!W6./EE' );
define( 'LOGGED_IN_SALT',   '`3OJ~Vn56!oZ| wu&p>jDY-bO@ent>Wkl/v(=J*a_c|Ch44+H{wYy4GZO8]4~8bK' );
define( 'NONCE_SALT',       'vZ;%UQ4`EU7Iae,A({.]Pv]e&#l^T*hb7e0x9i1`>KS~b{gd%S*~V!M%-x|Mlkn&' );

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
$table_prefix = 'wp_';

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
