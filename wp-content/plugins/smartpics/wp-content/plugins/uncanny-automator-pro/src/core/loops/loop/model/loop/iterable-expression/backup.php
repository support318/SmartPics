<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression;

/**
 * Class Backup
 *
 * This class represents a backup with sentence and HTML sentence properties.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression
 */
class Backup {

	/**
	 * The sentence string.
	 *
	 * @var string
	 */
	protected string $sentence = '';

	/**
	 * The sentence HTML string.
	 *
	 * @var string
	 */
	protected string $sentence_html = '';

	/**
	 * Set the sentence string.
	 *
	 * @param string $sentence The sentence.
	 * @throws \InvalidArgumentException If the sentence is empty.
	 */
	public function set_sentence( string $sentence ) {
		if ( empty( $sentence ) ) {
			throw new \InvalidArgumentException( 'The sentence cannot be empty.' );
		}
		$this->sentence = $sentence;
	}

	/**
	 * Get the sentence string.
	 *
	 * @return string The sentence.
	 */
	public function get_sentence(): string {
		return $this->sentence;
	}

	/**
	 * Set the sentence HTML string.
	 *
	 * @param string $sentence_html The sentence HTML.
	 * @throws \InvalidArgumentException If the sentence HTML is empty.
	 */
	public function set_sentence_html( string $sentence_html ) {
		if ( empty( $sentence_html ) ) {
			throw new \InvalidArgumentException( 'The sentence HTML cannot be empty.' );
		}
		$this->sentence_html = $sentence_html;
	}

	/**
	 * Get the sentence HTML string.
	 *
	 * @return string The sentence HTML.
	 */
	public function get_sentence_html(): string {
		return $this->sentence_html;
	}
}
