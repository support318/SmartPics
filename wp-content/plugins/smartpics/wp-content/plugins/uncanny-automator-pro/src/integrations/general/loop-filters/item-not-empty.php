<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Loop filter - The item in the loop meets {{a condition}}
 *
 * Class ITEM_NOT_EMPTY
 *
 * @package Uncanny_Automator_Pro
 */
class ITEM_NOT_EMPTY extends Loop_Filter {

	/**
	 * @var string
	 */
	const META = 'ITEM_NOT_EMPTY';

	/**
	 * Sets up the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$static_sentence = esc_html_x(
			'The item is not empty',
			'Filter sentence',
			'uncanny-automator-pro'
		);

		$dynamic_sentence = sprintf(
			esc_html_x(
				'The item is not empty',
				'Filter sentence',
				'uncanny-automator-pro'
			),
			self::META
		);

		$this->set_integration( 'GEN' );
		$this->set_meta( self::META );
		$this->set_sentence( $static_sentence );
		$this->set_sentence_readable( $dynamic_sentence );
		$this->set_entities( array( $this, 'get_items' ) );
		$this->set_loop_type( Entity_Factory::TYPE_TOKEN );

	}

	/**
	 * @param array{ITEM_NOT_EMPTY:string} $fields
	 *
	 * @return array{mixed[]}
	 */
	public function get_items( $fields ) {

		$loopable_items_array = $this->get_loopable_items();

		// Remove empty items.
		$loopable_items_array = $this->filter_empty_arrays( $loopable_items_array );

		if ( empty( $loopable_items_array ) ) {
			return array();
		}

		return $loopable_items_array;

	}

	/**
	 * Filters an array of associative arrays, removing those that contain only empty values.
	 *
	 * @param array $array The input array of associative arrays.
	 * @return array The filtered array with non-empty associative arrays.
	 */
	public function filter_empty_arrays( $array ) {

		// Function to check if all values in the array are empty.
		$is_all_empty = function( $arr ) {
			return array_filter( $arr ) === array();
		};

		// Filter the main array.
		$filtered_array = array_filter(
			$array,
			function( $inner_array ) use ( $is_all_empty ) {
				return ! $is_all_empty( $inner_array );
			}
		);

		// Reset keys to be sequential (optional).
		return array_values( $filtered_array );

	}

}
