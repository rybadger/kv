<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'main');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'e/:])meWbXa|a9Jgq^LR>oIq[&XUU9tF^Rct<N5+.J}3C #!]D:ra7@XQ<q3|cq]');
define('SECURE_AUTH_KEY',  'RxfQSgSf^y,S,27 g@s^NuNT|HGzzp-K%`ncl)e0YW$o*#?ozv(shf# *?c1$Tt-');
define('LOGGED_IN_KEY',    'xF`FSw4Xsm4kUpR>G*=A!27d>&p[/C o5cB^|WT`IzSb9H?,a.*UZ08U??z[%zGi');
define('NONCE_KEY',        'y3dqrNEwjHg qP*ia:WO5r;GLwF #U6MeOJs{En9Gf>nZiR^q}CG)VE]6TuZq}u-');
define('AUTH_SALT',        '#8f_KG7YP/f5v<lWor1 <XIsL(gYE5>p;2.8sEYm/)S0g15,q|<(x?9Czxg=jk%#');
define('SECURE_AUTH_SALT', '^yx1&YPmtC`y7>By{N:I u6c@1pNNIk{H~cK_&Qi3.< -i<%g!cZp$WEZ`qg3s#A');
define('LOGGED_IN_SALT',   '5DM0?_w%n~>mm$U0$uj`M}j#9<eBeWXKh,SvVrayoj}_;P$q$e13.iq:SQp>oCJi');
define('NONCE_SALT',       '[$_ 8>Kv)9Br)EdOfbq~C[xEQi4G Y!sPbG[(r;dWC^I*-9?P#+I6HvPD$`,ej V');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
