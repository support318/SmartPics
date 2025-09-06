<?php

namespace Uncanny_Automator_Pro\Integrations\M4IS;

/**
 * Class M4IS_ADD_USER_MEMBERSHIP_LEVEL
 *
 * @package Uncanny_Automator_Pro
 */
class M4IS_ADD_USER_MEMBERSHIP_LEVEL extends \Uncanny_Automator\Recipe\Action {

	public $prefix = 'M4IS_ADD_USER_MEMBERSHIP_LEVEL';

	/**
	 * Define and register the action by pushing it into the Automator object.
	 *
	 * @return void
	 */
	public function setup_action() {

		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'M4IS' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->action_code, 'knowledge-base/memberium-keap/' ) );
		$this->set_sentence(
			sprintf(
				/* translators: %1$s Membership Level*/
				esc_attr_x( 'Add the user to {{a membership level:%1$s}}', 'M4IS - add membership level action', 'uncanny-automator' ),
				$this->get_action_meta()
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'Add the user to {{a membership level}}', 'M4IS - add membership level action', 'uncanny-automator' ) );
		$this->set_action_tokens( $this->helpers->get_membership_action_token_config(), $this->action_code );
	}

	/**
	 * Define options.
	 *
	 * @return array
	 */
	public function options() {

		return array(
			array(
				'option_code'           => $this->get_action_meta(),
				'label'                 => _x( 'Membership level', 'M4IS - add membership level action', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'supports_custom_value' => true,
				'options'               => $this->helpers->get_membership_level_options(),
			),
		);

	}

	/**
	 * Process the action.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$membership_level = $this->helpers->get_membership_level_id_from_parsed( $parsed, $this->get_action_meta() );

		$response = $this->helpers->update_membership_level( $user_id, $membership_level );

		if ( is_wp_error( $response ) ) {
			throw new \Exception(
				sprintf(
					/* translators: %s - error message */
					esc_attr_x( 'Error adding user to the membership level: %s', 'M4IS - add membership level action', 'uncanny-automator' ),
					$response->get_error_message()
				)
			);
		}

		// Hydrate tokens.
		$this->hydrate_tokens(
			array(
				'MEMBERSHIP_NAME' => $response['name'],
				'TAG'             => $this->helpers->get_tag_names( $response['main_id'] ),
			)
		);

		return true;
	}

}
