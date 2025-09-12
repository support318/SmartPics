<?php

if ( ! defined( 'UAPro_ABSPATH' ) ) {
	/**
	 * Automator Pro ABSPATH for file includes
	 */
	define( 'UAPro_ABSPATH', dirname( AUTOMATOR_PRO_FILE ) . DIRECTORY_SEPARATOR ); // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase
}

if ( ! defined( 'AUTOMATOR_STORE_URL' ) ) {
	/**
	 * URL of store powering the plugin
	 */
	define( 'AUTOMATOR_STORE_URL', 'https://automatorplugin.com/' );
}

if ( ! defined( 'AUTOMATOR_PRO_STORE_URL' ) ) {
	/**
	 * URL of store powering the plugin
	 */
	define( 'AUTOMATOR_PRO_STORE_URL', AUTOMATOR_STORE_URL );
}

if ( ! defined( 'AUTOMATOR_LICENSING_URL' ) ) {
	/**
	 * URL of store powering the plugin
	 */
	define( 'AUTOMATOR_LICENSING_URL', 'https://licensing.uncannyowl.com/' );
}

if ( ! defined( 'AUTOMATOR_PRO_ITEM_NAME' ) ) {
	/**
	 * Store Item download name/title
	 */
	define( 'AUTOMATOR_PRO_ITEM_NAME', 'Uncanny Automator Pro' );
}

if ( ! defined( 'AUTOMATOR_PRO_ITEM_ID' ) ) {
	/**
	 * Store Item ID
	 */
	define( 'AUTOMATOR_PRO_ITEM_ID', 506 );
}
