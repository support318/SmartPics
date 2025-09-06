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
define( 'DB_NAME', 'dbdmbgquc0hwbq' );

/** Database username */
define( 'DB_USER', 'utq2rkfg4me6n' );

/** Database password */
define( 'DB_PASSWORD', 'db5uzgcmaq6k' );

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
define( 'AUTH_KEY',          'nBqfe]_#&2&r`3,@`qu{oB^>>+98y*6<4i=ZUpuqM8WUGK,KHVe:1T9{FU|mNVOt' );
define( 'SECURE_AUTH_KEY',   '{<R3y^bWt2x+w6:vH-Uw(YC.*&MO3W^&>5Ctxu fU^PrbXSj;Te*zUEYr]5`R`km' );
define( 'LOGGED_IN_KEY',     '/s]6p,u-26g:V-K^IBjaK=arWi>THpy;xWH5oq0%5=lp*#<w3(LnH.y^wY$rv1}2' );
define( 'NONCE_KEY',         '.k><-`nwPtvnE`o`(l_34+@hgvu!|29RIrJ({sa}zd(Yr@L>nEu%W}B@4!Jt~!_q' );
define( 'AUTH_SALT',         'eDr>rMVe~toe0@9V=!LrFr*=AolI3ZIwnSi!v3700!fiVer0qR@lSkO`eA:o>Tx&' );
define( 'SECURE_AUTH_SALT',  '!ZDMsc=RWKcyNL>1pY|MnE1Ud#X&VIk}>qgJ0pQ*~3mVto}qsfjvXr$8D%:?kw=F' );
define( 'LOGGED_IN_SALT',    ';.-YG/P/<hy.~=otbRpCZQc03{vq >7qwxH]dS[YU!9PR}f/J) scdec6fI#65IJ' );
define( 'NONCE_SALT',        'utX8y(KnX<`}5.bV_ycqkJ5JLU&;#cqRC{w=T+(m wSbdK$$r3Ys3x{z%d`IVy_n' );
define( 'WP_CACHE_KEY_SALT', '4QczK_A?TM`{ffKE5QSsY`=J^-OQA#bb)VqbG/.rX[7V4O.:uDn,9c=% JlY*3H2' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'bwe_';


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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
