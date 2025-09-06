<?php
/**
 * Nonce Utilities
 *
 * @since 2.12.0
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

namespace AffiliateWP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\Nonce' ) ) {
	return;
}

affwp_require_util_traits( 'data' );

/**
 * Nonce Utilities
 *
 * @since 2.12.0
 */
trait Nonce {

	use \AffiliateWP\Utils\Data;

	/**
	 * Base name for generating nonces.
	 *
	 * You can change this in your __construct().
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $nonce_base = 'affwp_nonce';

	/**
	 * Get a nonce action/name value.
	 *
	 * @since  2.12.0
	 *
	 * @param string $action  What action.
	 * @param string $context Context.
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException If either parameters are not non-empty strings.
	 */
	protected function nonce_action( $action, $context ) {

		if (
			! $this->is_string_and_nonempty( $action ) ||
			! $this->is_string_and_nonempty( $context )
		) {
			throw new \InvalidArgumentException( '$action and $contect must be non-empty strings.' );
		}

		static $generated = array(); // Cache for nonces.

		if ( isset( $generated[ $this->nonce_base ][ $action ][ $context ] ) ) {
			return $generated[ $this->nonce_base ][ $action ][ $context ];
		}

		// Create a very small name for this nonce (for GET).
		$minified = crc32( "affiliate-wp-{$this->nonce_base}-{$action}-{$context}" );

		// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Assigning cache value while returning.
		return $generated[ $action ][ $context ] = "_affwp_nonce_{$minified}";
	}

	/**
	 * Verify a nonce.
	 *
	 * Use this before `check_admin_referer()` in case
	 * nonce expired to avoid `die()`.
	 *
	 * @since  2.12.0
	 *
	 * @param string $action  For action.
	 * @param string $context For context.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If either parameters are not non-empty strings.
	 */
	protected function verify_nonce_action( $action, $context ) {

		if (
			! $this->is_string_and_nonempty( $action ) ||
			! $this->is_string_and_nonempty( $context )
		) {
			throw new \InvalidArgumentException( '$action and $contect must be non-empty strings.' );
		}

		$nonce_action = $this->nonce_action( $action, $context );

		if ( ! isset( $_REQUEST[ $nonce_action ] ) ) {
			return false;
		}

		return wp_verify_nonce(
			$_REQUEST[ $nonce_action ],
			$nonce_action
		);
	}
}
