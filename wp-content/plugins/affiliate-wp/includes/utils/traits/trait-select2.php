<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Select2 Utilities
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

namespace AffiliateWP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\Select2' ) ) {
	return;
}

affwp_require_util_traits( 'data' );

/**
 * Select2 Utilities
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_Data
 */
trait Select2 {

	use \AffiliateWP\Utils\Data;

	/**
	 * Load scripts and styles.
	 *
	 * Try and run this on the `wp_enqueue_scripts` or
	 * `admin_enqueue_scripts` hook.
	 *
	 * This will automatically enqueue `assets/js/select2-init.js`
	 * for you and pass the chosen selector to the JS instance.
	 *
	 * @since  2.12.0
	 * @since  2.13.0 This now just loads select2.
	 *
	 * @throws \InvalidArgumentException If you do not supply proper parameters.
	 */
	private function enqueue_select2() {

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Enqueue jQuery .select2().
		wp_enqueue_style( 'affwp-select2' );
		wp_enqueue_script( 'affwp-select2' );

		// Load the script.
		wp_enqueue_script(
			'affwp-select2-init',
			AFFILIATEWP_PLUGIN_URL . "assets/js/select2-init{$suffix}.js",
			array( 'jquery', 'affwp-select2' ),
			AFFILIATEWP_VERSION,
			true
		);
	}
}
