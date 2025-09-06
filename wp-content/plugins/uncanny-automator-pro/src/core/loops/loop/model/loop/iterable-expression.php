<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression;

use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Fields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Backup;

/**
 * Class Iterable_Expression
 *
 * This class represents an iterable expression with type, fields, and backup.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Loop
 */
class Iterable_Expression {

	/**
	 * The type of the iterable expression.
	 *
	 * @var string
	 */
	protected string $type = '';

	/**
	 * The Fields instance representing the fields of the iterable expression.
	 *
	 * @var Fields
	 */
	protected Fields $fields;

	/**
	 * The Backup instance representing the backup of the iterable expression.
	 *
	 * @var Backup
	 */
	protected Backup $backup;

	/**
	 * Constructor to initialize the Iterable_Expression object with dependencies.
	 *
	 * @param Fields $fields The fields object.
	 * @param Backup $backup The backup object.
	 */
	public function __construct( Fields $fields, Backup $backup ) {
		$this->fields = $fields;
		$this->backup = $backup;
	}

	/**
	 * Set the type of the iterable expression.
	 *
	 * @param string $type The type of the iterable expression.
	 * @throws \InvalidArgumentException If the type is empty.
	 */
	public function set_type( string $type ) {
		if ( empty( $type ) ) {
			throw new \InvalidArgumentException( 'The type cannot be empty.' );
		}
		$this->type = $type;
	}

	/**
	 * Get the type of the iterable expression.
	 *
	 * @return string The type of the iterable expression.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set the fields object.
	 *
	 * @param Fields $fields The fields object.
	 */
	public function set_fields( Fields $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Get the fields object.
	 *
	 * @return Fields The fields object.
	 */
	public function get_fields(): Fields {
		return $this->fields;
	}

	/**
	 * Set the backup object.
	 *
	 * @param Backup $backup The backup object.
	 */
	public function set_backup( Backup $backup ) {
		$this->backup = $backup;
	}

	/**
	 * Get the backup object.
	 *
	 * @return Backup The backup object.
	 */
	public function get_backup(): Backup {
		return $this->backup;
	}

	 /**
	 * Convert Iterable_Expression data into an array format.
	 *
	 * @return array The iterable expression data as an array.
	 */
	public function to_array(): array {

		return array(
			'type'   => $this->type,
			'fields' => wp_json_encode(
				array(
					$this->fields->get_id() => array(
						'type'   => $this->fields->get_type(),
						'value'  => $this->fields->get_value(),
						'backup' => $this->fields->get_backup(),
					),
				)
			),
			'backup' => wp_json_encode(
				array(
					'sentence'      => $this->backup->get_sentence(),
					'sentence_html' => $this->backup->get_sentence_html(),
				)
			),
		);

	}
}
