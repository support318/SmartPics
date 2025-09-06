<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

class Replace extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'FORMATTER' );
		$this->set_action_code( 'REPLACE' );
		$this->set_action_meta( 'SUBJECT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input text, output format
		$this->set_sentence( sprintf( esc_attr__( 'Replace values in {{a string:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Replace values in {{a string}}', 'uncanny-automator-pro' ) );

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
					'label'       => _x( 'Input', 'Search and replace', 'uncanny-automator-pro' ),
					'placeholder' => _x( 'Enter text', 'Search and replace', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
				)
			),
			array(
				'input_type'        => 'repeater',
				'relevant_tokens'   => array(),
				'option_code'       => 'ROWS',
				'label'             => _x( 'Search-replace pairs', 'Search and replace', 'uncanny-automator-pro' ),
				'description'       => _x( 'Rows will be processed from top to bottom.', 'Search and replace', 'uncanny-automator-pro' ),
				'required'          => true,
				'fields'            => array(
					array(
						'input_type'      => 'text',
						'option_code'     => 'SEARCH',
						'label'           => _x( 'Search for', 'Search and replace', 'uncanny-automator-pro' ),
						'supports_tokens' => true,
						'required'        => true,
					),
					array(
						'input_type'      => 'text',
						'option_code'     => 'REPLACE',
						'label'           => _x( 'Replace with', 'Search and replace', 'uncanny-automator-pro' ),
						'supports_tokens' => true,
						'required'        => false,
					),
				),
				/* translators: Non-personal infinitive verb */
				'add_row_button'    => _x( 'Add row', 'Search and replace', 'uncanny-automator-pro' ),
				/* translators: Non-personal infinitive verb */
				'remove_row_button' => _x( 'Remove row', 'Search and replace', 'uncanny-automator-pro' ),
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => 'CASE_SENSITIVE',
					'label'       => _x( 'Case sensitive', 'Search and replace', 'uncanny-automator-pro' ),
					'required'    => false,
					'input_type'  => 'checkbox',
					'is_toggle'   => false,
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
			'OUTPUT' => array(
				'name' => _x( 'Output', 'Search and replace', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * process_action
	 *
	 * @param  mixed $user_id
	 * @param  mixed $action_data
	 * @param  mixed $recipe_id
	 * @param  mixed $args
	 * @param  mixed $parsed
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$subject        = $this->get_parsed_meta_value( 'SUBJECT' );
		$rows           = json_decode( Automator()->parse->text( $action_data['meta']['ROWS'], $recipe_id, $user_id, $args ), true );
		$case_sensitive = $this->get_parsed_meta_value( 'CASE_SENSITIVE' );

		$this->hydrate_tokens(
			array(
				'OUTPUT' => $this->replace( $subject, $rows, $case_sensitive ),
			)
		);

		return true;
	}

	/**
	 * replace
	 *
	 * @param  string $subject
	 * @param  array $rows
	 * @param  mixed $case_sensitive
	 * @return string
	 */
	public function replace( $subject, $rows, $case_sensitive ) {

		$case_sensitive = filter_var( $case_sensitive, FILTER_VALIDATE_BOOLEAN );

		$output = $subject;

		if ( $case_sensitive ) {
			$output = str_replace( array_column( $rows, 'SEARCH' ), array_column( $rows, 'REPLACE' ), $output );
		} else {
			$output = str_ireplace( array_column( $rows, 'SEARCH' ), array_column( $rows, 'REPLACE' ), $output );
		}

		return $output;
	}

}
