<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter;

use JsonSerializable;

/**
 * Class Backup
 *
 * This class represents the backup data, exclusive to Loop_Filter.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter
 */
class Backup implements JsonSerializable {

	/**
	 * The sentence for the backup.
	 *
	 * @var string
	 */
	protected $sentence = '';

	/**
	 * The HTML sentence for the backup.
	 *
	 * @var string
	 */
	protected $sentence_html = '';

	/**
	 * @var string
	 */
	protected $integration_name = '';

	/**
	 * Set the sentence.
	 *
	 * @param string $sentence The sentence string.
	 * @throws \InvalidArgumentException If the sentence is empty.
	 */
	public function set_sentence( $sentence ) {
		if ( empty( $sentence ) ) {
			throw new \InvalidArgumentException( 'The sentence cannot be empty.' );
		}
		$this->sentence = $sentence;
	}

	/**
	 * Get the sentence string.
	 *
	 * @return string The sentence string.
	 */
	public function get_sentence() {
		return $this->sentence;
	}

	/**
	 * Set the HTML sentence.
	 *
	 * @param string $sentence_html The sentence HTML string.
	 * @throws \InvalidArgumentException If the HTML is empty.
	 */
	public function set_sentence_html( $sentence_html ) {
		if ( empty( $sentence_html ) ) {
			throw new \InvalidArgumentException( 'The HTML sentence cannot be empty.' );
		}
		$this->sentence_html = $sentence_html;
	}

	/**
	 * Get the HTML sentence string.
	 *
	 * @return string The sentence HTML string.
	 */
	public function get_sentence_html() {
		return $this->sentence_html;
	}

	/**
	 * @param string $integration_name
	 * @return void
	 */
	public function set_integration_name( $integration_name ) {
		$this->integration_name = $integration_name;
	}

	/**
	 * @return string
	 */
	public function get_integration_name() {
		return $this->integration_name;
	}

	/**
	 * Specify the data that should be serialized to JSON.
	 *
	 * @return array The serialized data of the field.
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return array(
			'integration_name' => $this->get_integration_name(),
			'sentence'         => $this->get_sentence(),
			'sentence_html'    => htmlentities( $this->get_sentence_html() ),
		);
	}
}
