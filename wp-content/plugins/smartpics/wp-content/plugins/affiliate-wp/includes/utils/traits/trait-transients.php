<?php
/**
 * Transients Utilities
 *
 * @since 2.13.2
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2023, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

namespace AffiliateWP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\Transients' ) ) {
	return;
}

/**
 * Transients Utilities
 *
 * @since 2.13.2
 */
trait Transients {

	/**
	 * Create a transient key based on data.
	 *
	 * @since 2.13.2
	 *
	 * @param string $prefix The prefix.
	 * @param array  $data   The data.
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException If you supply an empty `$prefix`.
	 */
	protected function create_data_transient_key( $prefix = '', array $data = array() ) : string {

		if ( empty( $prefix ) ) {
			throw new \InvalidArgumentException( '$prefix must not be empty.' );
		}

		$signature = crc32( wp_json_encode( array( $prefix, $data, AFFILIATEWP_VERSION ) ) );

		/**
		 * Filter the resulting data transient key.
		 *
		 * @param string $key       The key.
		 * @param string $prefix    The prefix.
		 * @param array  $data      The data.
		 * @param string $signature The generated signature.
		 */
		return apply_filters(
			'affwp_data_transient_key',
			"affwp_{$prefix}{$signature}",
			$data,
			$signature
		);
	}
}
