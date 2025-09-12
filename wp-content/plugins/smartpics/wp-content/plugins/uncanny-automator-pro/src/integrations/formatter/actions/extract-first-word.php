<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

/**
 *
 */
class Extract_First_Word extends \Uncanny_Automator\Recipe\Action {

	private $case_sensitive = false;

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'FORMATTER' );
		$this->set_action_code( 'EXTRACT_FIRST_WORD' );
		$this->set_action_meta( 'SUBJECT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input text, output format
		$this->set_sentence( sprintf( esc_attr__( 'Extract the first word from {{a string:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Extract the first word from {{a string}}', 'uncanny-automator-pro' ) );

	}

	/**
	 * options
	 *
	 * @return array
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => $this->get_action_meta(),
					'label'       => _x( 'Input', 'Exctract the first word action', 'uncanny-automator-pro' ),
					'placeholder' => _x( 'Enter text', 'Exctract the first word action', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
					'description' => _x( 'This action will add two tokens: one for the first word and one for the rest of the string.', 'Exctract the first word action', 'uncanny-automator-pro' ),
				)
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'FIRST' => array(
				'name' => _x( 'First word', 'Extract the first word action', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'REST'  => array(
				'name' => _x( 'Rest of string', 'Extract the first word action', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * process_action
	 *
	 * @param mixed $user_id
	 * @param mixed $action_data
	 * @param mixed $recipe_id
	 * @param mixed $args
	 * @param mixed $parsed
	 *
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$subject = $this->get_parsed_meta_value( $this->get_action_meta() );

		$this->hydrate_tokens(
			$this->extract_tokens( $subject )
		);

		return true;
	}

	public function extract_tokens( $string ) {

		$words = explode( ' ', $string, 2 );

		return array(
			'FIRST' => array_shift( $words ),
			'REST'  => array_shift( $words ),
		);
	}
}
