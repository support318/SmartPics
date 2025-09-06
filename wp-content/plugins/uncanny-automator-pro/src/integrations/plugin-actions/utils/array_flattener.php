<?php
namespace Uncanny_Automator_Pro\Integrations\Plugin_Actions\Utils;

/**
 * Array_Flattener
 *
 * @since 5.10
 * @package Uncanny_Automator_Pro\Integrations\Plugin_Actions\Utils
 */
class Array_Flattener {

	/**
	 * @var mixed[]
	 */
	private $flattened_array = array();

	/**
	 * Flattens the array.
	 *
	 * @param mixed[] $array
	 * @param string $prefix
	 * @return void
	 */
	public function flatten_array( $array, $prefix = '' ) {
		if ( ! is_array( $array ) ) {
			return; // If it's not an array, there's nothing to flatten
		}
		$this->process_array( $array, $prefix );
	}

	/**
	 * Processes each element in the array.
	 *
	 * @param mixed[] $array
	 * @param string $prefix
	 * @return void
	 */
	private function process_array( $array, $prefix ) {
		foreach ( $array as $key => $value ) {
			$new_key = $this->build_key( $prefix, $key );

			if ( is_array( $value ) ) {
				$this->process_nested_array( $new_key, $value );
				continue;
			}

			if ( is_object( $value ) ) {
				$this->process_object( $new_key, $value );
				continue;
			}

			$this->process_scalar( $new_key, $value );
		}
	}

	/**
	 * Builds the key for the flattened array.
	 *
	 * @param string $prefix
	 * @param string|int $key
	 * @return string
	 */
	private function build_key( $prefix, $key ) {
		return $prefix === '' ? $key : $prefix . '.' . $key;
	}

	/**
	 * Processes nested arrays (associative or indexed).
	 *
	 * @param string $key
	 * @param mixed[] $value
	 * @return void
	 */
	private function process_nested_array( $key, $value ) {
		if ( $this->is_associative_array( $value ) ) {
			$this->process_array( $value, $key );
			return;
		}

		if ( $this->can_convert_to_string( $value ) ) {
			// Handle simple arrays like [1, 2, 3] by converting to string
			$this->flattened_array[ $key ] = implode( ',', $value );
		} else {
			// Handle more complex arrays gracefully, possibly return JSON
			$this->flattened_array[ $key ] = json_encode( $value );
		}
	}

	/**
	 * Processes objects by serializing them.
	 *
	 * @param string $key
	 * @param object $value
	 * @return void
	 */
	private function process_object( $key, $value ) {
		if ( method_exists( $value, '__toString' ) ) {
			// If object has __toString() method, use it
			$this->flattened_array[ $key ] = (string) $value;
		} else {
			// Fall back to serialize if no __toString() method
			$this->flattened_array[ $key ] = serialize( $value );
		}
	}

	/**
	 * Processes scalar values (non-array and non-object).
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	private function process_scalar( $key, $value ) {
		if ( is_scalar( $value ) ) {
			$this->flattened_array[ $key ] = $value;
		} else {
			// If the value is not a scalar, encode it as JSON
			$this->flattened_array[ $key ] = json_encode( $value );
		}
	}

	/**
	 * Checks if an array is associative.
	 *
	 * @param mixed[] $array
	 * @return bool
	 */
	private function is_associative_array( $array ) {
		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * Checks if an array can be safely converted to a string.
	 *
	 * @param mixed[] $array
	 * @return bool
	 */
	private function can_convert_to_string( $array ) {
		foreach ( $array as $value ) {
			if ( ! is_scalar( $value ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Retrieve the flattened array.
	 *
	 * @return mixed[]
	 */
	public function get_flattened_array() {
		return $this->flattened_array;
	}

	/**
	 * Retrieve the value by key.
	 *
	 * @param string $key
	 *
	 * @return string|int|null
	 */
	public function get_value( $key ) {
		return isset( $this->flattened_array[ $key ] ) ? $this->flattened_array[ $key ] : null;
	}
}
