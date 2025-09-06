<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop_filter;

use JsonSerializable;

/**
 * Class Field
 *
 * Represents a single field with ID, type, value, and backup.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Loop_filter
 */
class Field implements JsonSerializable {

	/**
	 * The ID of the field (used as the key in JSON).
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * The type of the field.
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * @var string
	 */
	protected $readable = '';

	/**
	 * The value of the field.
	 *
	 * @var string|null
	 */
	protected $value = null;

	/**
	 * The backup associative array for the field.
	 *
	 * @var array
	 */
	protected $backup = array();

	/**
	 * Set the ID of the field.
	 *
	 * @param string $id The ID of the field.
	 * @throws \InvalidArgumentException If the ID is empty.
	 */
	public function set_id( $id ) {
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
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the readable of the field.
	 *
	 * @param string $readable
	 */
	public function set_readable( $readable ) {
		$this->readable = $readable;
	}

	/**
	 * Get the readable of the field.
	 *
	 * @return string The readable of the field.
	 */
	public function get_readable() {
		return $this->readable;
	}

	/**
	 * Set the type of the field.
	 *
	 * @param string $type The type of the field.
	 * @throws \InvalidArgumentException If the type is empty.
	 */
	public function set_type( $type ) {
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
	public function get_type() {
		return $this->type;
	}

	/**
	 * Set the value of the field.
	 *
	 * @param string|null $value The value of the field (can be null).
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	/**
	 * Get the value of the field.
	 *
	 * @return string|null The value of the field.
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Set the backup array for the field.
	 *
	 * @param array $backup The backup array.
	 */
	public function set_backup( array $backup ) {
		$this->backup = $backup;
	}

	/**
	 * Get the backup array for the field.
	 *
	 * @return array The backup array.
	 */
	public function get_backup() {
		return $this->backup;
	}

	/**
	 * Specify the data that should be serialized to JSON.
	 *
	 * @return array The serialized data of the field.
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return array(
			$this->id => array(
				'type'   => $this->type,
				'value'  => $this->value,
				'backup' => $this->backup,
			),
		);
	}
}
