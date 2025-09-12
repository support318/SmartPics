<?php

namespace Uncanny_Automator_Pro\Integration\Custom_Action\Actions;

use InvalidArgumentException;
use Uncanny_Automator\Recipe\Action;

/**
 * Class Run_Do_Action
 *
 * This class represents an action that triggers a WordPress hook with dynamic variables.
 *
 * @package Uncanny_Automator_Pro\Integration\Add_Action\Actions
 */
class Run_Do_Action extends Action {

	/**
	 * Sets up the action with the necessary properties and sentences.
	 *
	 * This method configures the action as a pro feature, assigns an integration code, and sets
	 * readable sentences to describe the action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_is_pro( true );
		$this->set_integration( 'DO_ACTION' );
		$this->set_action_code( 'DO_ACTION_CODE' );
		$this->set_action_meta( 'DO_ACTION_META' );
		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Action meta key */
				esc_attr_x( 'Call {{a do_action hook:%1$s}}', 'Custom Action', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			/* translators: Describes a WordPress hook being executed */
			esc_attr_x( 'Call {{a do_action hook}}', 'Custom Action', 'uncanny-automator-pro' )
		);
	}

	/**
	 * Provides options for configuring the WordPress hook and its variables.
	 *
	 * @return array Configuration options for the action.
	 */
	public function options() {

		$hook_name = array(
			'input_type'      => 'text',
			'relevant_tokens' => array(),
			'option_code'     => $this->get_action_meta(),
			'required'        => true,
			'label'           => esc_attr_x( 'Action hook', 'Custom Action', 'uncanny-automator-pro' ),
		);

		$hook_vars = array(
			'input_type'        => 'repeater',
			'relevant_tokens'   => array(),
			'option_code'       => 'HOOK_VARS',
			'label'             => esc_attr_x( 'Pass variables', 'Custom Action', 'uncanny-automator-pro' ),
			'description'       => __(
				/* translators: Explains how variables are passed into the hook. */
				'Variables will be passed to the hook in this exact order. Variables like <strong>null</strong>, <strong>[]</strong>, and <strong>array()</strong> will be passed as null and empty arrays.',
				'uncanny-automator-pro'
			),
			'required'          => false,
			'fields'            => array(
				array(
					'input_type'      => 'text',
					'option_code'     => 'VALUE',
					'label'           => esc_attr_x( 'Value', 'Custom Action', 'uncanny-automator-pro' ),
					'supports_tokens' => true,
					'required'        => false,
				),
			),
			/* translators: Non-personal infinitive verb for adding a row. */
			'add_row_button'    => esc_attr_x( 'Add a variable', 'Custom Action', 'uncanny-automator-pro' ),
			/* translators: Non-personal infinitive verb for removing a row. */
			'remove_row_button' => esc_attr_x( 'Remove a variable', 'Custom Action', 'uncanny-automator-pro' ),
		);

		return array( $hook_name, $hook_vars );
	}

	/**
	 * Processes the action by calling a WordPress hook with provided variables.
	 *
	 * @param int   $user_id    The ID of the user executing the action.
	 * @param array $action_data Data associated with the action.
	 * @param int   $recipe_id  The ID of the recipe being executed.
	 * @param array $args       Arguments passed to the action.
	 * @param mixed $parsed     Parsed data from the action configuration.
	 *
	 * @return bool True on success, false otherwise.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$hook_name = $parsed[ $this->get_action_meta() ] ?? '';
		$vars      = (array) json_decode( $parsed['HOOK_VARS'] ?? '' );

		if ( empty( $hook_name ) ) {
			throw new InvalidArgumentException( 'Hook name is required but empty.', 400 );
		}

		$args = array();

		foreach ( $vars as $var ) {
			$args[] = $this->parse( $var->VALUE );
		}

		array_unshift( $args, $hook_name );

		call_user_func_array( 'do_action', $args );

		return true;
	}

	/**
	 * Parses the given value to handle null and empty arrays.
	 *
	 * Converts strings such as 'null' or 'array()' into their respective PHP equivalents.
	 *
	 * @param mixed $value The value to parse.
	 *
	 * @return mixed The parsed value.
	 */
	protected function parse( $value ) {

		$output = null;

		switch ( $value ) {
			case 'null':
				break;
			case 'array()':
			case '[]':
				$output = array();
				break;
			default:
				$output = $value;
				break;
		}

		/**
		 * Filters the parsed output value.
		 *
		 * @param mixed $output The final parsed value.
		 * @param mixed $value  The original input value.
		 */
		return apply_filters( 'automator_do_action_parse_vars', $output, $value );
	}
}
