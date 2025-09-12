<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Data Utilities
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty lines OK here.

namespace AffiliateWP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\Data' ) ) {
	return;
}

/**
 * Data Utilities
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_Data
 */
trait Data {

	/**
	 * Is a value a positive and numeric?
	 *
	 * @since 2.12.0
	 *
	 * @param  mixed $value The value that has to be a postive numeric.
	 * @return bool
	 */
	protected function is_numeric_and_at_least_zero( $value ) {
		return is_numeric( $value ) && intval( $value ) >= 0;
	}

	/**
	 * Is a value numeric and greater than zero?
	 *
	 * @since 2.12.0
	 * @since 2.27.3 Updated to allow fractional values, see https://github.com/awesomemotive/affiliate-wp/issues/5346.
	 *
	 * @param  mixed $value A numeric string or int.
	 * @return bool
	 */
	protected function is_numeric_and_gt_zero( $value ) {

		return ! is_null( $value ) &&
			is_numeric( $value ) &&
				floatval( $value ) > 0;
	}

	/**
	 * Is a value a string and non-empty.
	 *
	 * Note if you send e.g. ' ' (with a space), we consider this empty.
	 *
	 * @since 2.12.0
	 *
	 * @param  mixed $value The value.
	 * @return bool
	 */
	protected function is_string_and_nonempty( $value ) {
		return is_string( $value ) && ! empty( trim( $value ) );
	}

	/**
	 * Converts a numeric value to Intval or returns zero.
	 *
	 * @since 2.12.0
	 *
	 * @param  mixed $value Value.
	 * @return int          Intval of the value, or zero.
	 */
	protected function get_numeric_intval_or_zero( $value ) {

		if ( is_numeric( $value ) ) {
			return intval( $value );
		}

		return 0;
	}

	/**
	 * Values returned must be a positive numeric or -1.
	 *
	 * @since  2.12.0
	 *
	 * @param  string $value The value.
	 * @return int           The numeric value, or -1.
	 */
	protected function get_positive_numeric_or_negative_one( $value ) {

		if ( ! is_numeric( $value ) || -1 === $value || '-1' === $value ) {
			return -1;
		}

		return absint( $value );
	}

	/**
	 * Try and wp_json_encode() a an array, or return a default.
	 *
	 * @since  2.12.0
	 *
	 * @param  mixed  $data    The item to encode.
	 * @param  string $default What to return if we can't encode the data.
	 * @return mixed           Encoded data, or the `$default`.
	 */
	protected function json_encode( $data, $default = '' ) {

		$encoded = wp_json_encode( $data );

		if ( ! is_string( $encoded ) ) {
			return $default;
		}

		return $encoded;
	}

	/**
	 * Plucks a property from a list of objects from the database.
	 *
	 * @param  array  $results  The list of objects from the database.
	 * @param  string $property The property to pluck.
	 * @return array|WP_Error   The property value plucked into an array of values.
	 *                          `WP_Error` if something went wrong.
	 *
	 * @throws \InvalidArgumentException If you supply improper parameters.
	 */
	protected function pluck_property_from_objects( $results, $property ) {

		if ( ! is_array( $results ) ) {
			throw new \InvalidArgumentException( '$results must be an array' );
		}

		if ( ! is_string( $property ) ) {
			throw new \InvalidArgumentException( '$property must be a string.' );
		}

		if ( empty( $property ) ) {
			throw new \InvalidArgumentException( '$property must be a non-empty string.' );
		}

		try {

			$return = array_map(
				function( $object ) use ( $property ) {

					// Returns the property from the list of filtered objects.
					return $object->$property;
				},
				array_filter(
					$results,

					// Determines if the object has the property you're requesting.
					function( $object ) use ( $property ) {

						if ( isset( $object->$property ) ) {
							return $object->$property;
						}

						throw new \Exception( "You asked us to pluck property '{$property}' but it was not found in object: " . json_encode( $object ) );
					}
				)
			);

		// Return a WP_Error.
		} catch ( \Exception $error ) {

			// Catch issues where objects don't have the property, and convert to WP_Error.
			return new \WP_Error( 'unexpected_results', $error->getMessage(), $error );
		}

		return $return;
	}

	/**
	 * Is a string one of many values.
	 *
	 * @since  2.12.0
	 *
	 * @param  string $string The string.
	 * @param  array  $list   The list of values the string can be (OR).
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you supply invalid parameters.
	 */
	protected function string_is_one_of( $string, $list ) {

		if ( ! is_string( $string ) ) {
			throw new \InvalidArgumentException( '$string must be a string.' );
		}

		if ( ! is_array( $list ) ) {
			throw new \InvalidArgumentException( '$list must be an array.' );
		}

		if ( array_filter(
			$list,
			function( $maybe_string ) {
				return is_string( $maybe_string );
			}
		) !== $list ) {
			throw new \InvalidArgumentException( '$list must only contain strings.' );
		}

		return in_array( $string, $list, true );
	}

	/**
	 * Get a virtually unlimited value.
	 *
	 * @since  2.12.0
	 *
	 * @param int $max Return this value if the virtual limit is above this.
	 *
	 * @return int
	 */
	protected function virtually_unlimited( $max = -1 ) {

		/**
		 * Filter the value we use for virtually unlimited results.
		 *
		 * @param int $max By default it's 9,000,000 (nine million).
		 *
		 * @since 2.12.0
		 */
		$virtually_unlimited = absint( apply_filters( 'virutally_unlimited', 9000000 ) );

		if ( -1 === $max ) {
			return $virtually_unlimited; // No max was set, use the filtered amount.
		}

		if ( $virtually_unlimited > $max ) {
			return $max; // A max was set, and it's above the max, so use the max.
		}

		return $virtually_unlimited; // The virtual limit is still below the max.
	}
}
