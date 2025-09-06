<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression;

/**
 * Class Fields
 *
 * This class represents a set of fields with validation for required properties.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression
 */
class Fields {

	/**
	 * The ID of the field.
	 *
	 * @var string
	 */
	protected string $id = '';

	/**
	 * The type of the field.
	 *
	 * @var string
	 */
	protected string $type = '';

	/**
	 * The value of the field.
	 *
	 * @var string
	 */
	protected string $value = '';

	/**
	 * The backup associative array for the field.
	 * Must contain 'show_label_in_sentence' and 'label' keys.
	 *
	 * @var array
	 */
	protected array $backup = array();

	/**
	 * Set the ID of the field.
	 *
	 * @param string $id The ID of the field.
	 * @throws \InvalidArgumentException If the ID is empty.
	 */
	public function set_id( string $id ) {
		if ( empty( $id ) ) {
			throw new \InvalidArgumentException( 'The ID cannot be empty.' );
		}
		$this->id = $id;
	}

	/**
	 * Get the ID of the field.
	 *
	 * @return string The ID of the field.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Set the type of the field.
	 *
	 * @param string $type The type of the field.
	 * @throws \InvalidArgumentException If the type is empty.
	 */
	public function set_type( string $type ) {
		if ( empty( $type ) ) {
			throw new \InvalidArgumentException( 'The type cannot be empty.' );
		}
		$this->type = $type;
	}

	/**
	 * Get the type of the field.
	 *
	 * @return string The type of the field.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set the value of the field.
	 *
	 * @param string $value The value of the field.
	 * @throws \InvalidArgumentException If the value is empty.
	 */
	public function set_value( string $value ) {
		if ( empty( $value ) ) {
			throw new \InvalidArgumentException( 'The value cannot be empty.' );
		}
		$this->value = $value;
	}

	/**
	 * Get the value of the field.
	 *
	 * @return string The value of the field.
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * Set the backup array for the field.
	 *
	 * The backup array must contain 'show_label_in_sentence' and 'label' keys.
	 *
	 * @param array $backup The backup array.
	 * @throws \InvalidArgumentException If 'show_label_in_sentence' or 'label' is missing from the array.
	 */
	public function set_backup( array $backup ) {
		if ( ! isset( $backup['show_label_in_sentence'] ) || ! isset( $backup['label'] ) ) {
			throw new \InvalidArgumentException( 'The backup array must contain "show_label_in_sentence" and "label" keys.' );
		}
		$this->backup = $backup;
	}

	/**
	 * Get the backup array for the field.
	 *
	 * @return array The backup array.
	 */
	public function get_backup(): array {
		return $this->backup;
	}
}
