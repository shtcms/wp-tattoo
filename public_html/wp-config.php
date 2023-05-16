<?php
define( 'WP_CACHE', false ); // By SiteGround Optimizer

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
define( 'DB_NAME', 'dblkjd2z5jjazw' );

/** Database username */
define( 'DB_USER', 'uxjnezza8igxg' );

/** Database password */
define( 'DB_PASSWORD', 'robtumooskol' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

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
define( 'AUTH_KEY',          '),Hi`RsCNG&bpkVbw:^__wm nPwng!(HLE-[gyxb`KbK%2{C6S`+;)cbwP]J1D$?' );
define( 'SECURE_AUTH_KEY',   'sgr(x~kD~no~apsPe]8=?jz_/?*uFu%h]?MiWC;)(FVDly$AN$#p]85QdAhCh])9' );
define( 'LOGGED_IN_KEY',     'Eh`+ohT9(C K4AbveLoYQgmn<lsX8*g!R4EK91ok+Q;ch`iz|ngc-sQN${dtajQC' );
define( 'NONCE_KEY',         '2:%ZV 2*W_F.FuyicnIIe: :#1s<YA+ *j}r`^%1JJ-`H1Qp1w_k+Hw^^^#-;%uN' );
define( 'AUTH_SALT',         'GG*DcqJ8(@5,t^ZK[W_Upam^Zp`mVkkZz9|9{X~wyGNho#![ecE&[ Rzk/31?1wW' );
define( 'SECURE_AUTH_SALT',  'I@NoLrs_b(YMIo=)t36D_e#wk#pckWFvpu&2ny`X(13PjMa)p{60KQ5a|P?R.8%b' );
define( 'LOGGED_IN_SALT',    'eGUhRxu]aDzn06C sTEX8iH[(FqkYFAGkU3$(;=aCZ>X3<fy|zg`JA[g5zA;0Z!d' );
define( 'NONCE_SALT',        ',_8c5s#h@f&,{HA*Fjn *gl@A#@ggA_xXrvW(h5kgIo,3(@p}sGS.PhQ0Jjp~;[n' );
define( 'WP_CACHE_KEY_SALT', 'xDU*iXu2DaWv_j Z]jV.)@j6afLVuSuf@M-Q=92}U(0oA`98{Vy8ZQyIozDDK?Fv' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'nur_';

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
define( 'WP_DEBUG', false );


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
