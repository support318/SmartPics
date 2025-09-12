<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter;

use JsonSerializable;

/**
 * Class Fields
 *
 * Represents a collection of Field objects.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Iterable_Expression
 */
class Fields implements JsonSerializable {

	/**
	 * The collection of Field objects.
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Add a Field object to the collection.
	 *
	 * @param Field $field The Field object to add.
	 */
	public function add_field( Field $field ) {
		$this->fields[] = $field;
	}

	/**
	 * Get all Field objects.
	 *
	 * @return array The array of Field objects.
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Specify the data that should be serialized to JSON.
	 *
	 * @return array The serialized data of all fields.
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$fields_array = array();

		foreach ( $this->fields as $field ) {
			// Merge the serialized output of each field
			$fields_array = array_merge( $fields_array, $field->jsonSerialize() );
		}

		return $fields_array;
	}
}
