<?php

namespace Uncanny_Automator_Pro;

class CANCEL_SCHEDULED_ACTION_BY_USER extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'UOA' );
		$this->set_action_code( 'CANCEL_USERS_SCHEDULED_ACTIONS' );
		$this->set_action_meta( 'RECIPE' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr__( 'Cancel the scheduled actions of {{a recipe:%1$s}} for {{a user:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'RECIPE_USER:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Cancel the scheduled actions of {{a recipe}} for {{a user}}', 'uncanny-automator-pro' ) );

	}

	/**
	 * options
	 *
	 * @return array
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => 'RECIPE',
					'label'                 => _x( 'Recipe', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'options'               => $this->get_recipes_as_options(),
					'supports_custom_value' => true,
					'required'              => true,
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => 'RECIPE_USER',
					'label'       => _x( 'User', 'Uncanny Automator', 'uncanny-automator-pro' ),
				)
			),
		);
	}

	/**
	 * get_recipes_as_options
	 *
	 * @return array
	 */
	public function get_recipes_as_options() {
		$options = array();

		$recipes_data = Automator()->helpers->recipe->uncanny_automator->get_recipes();

		foreach ( $recipes_data['options'] as $recipe_id => $recipe ) {
			$options[] = array(
				'value' => $recipe_id,
				'text'  => $recipe,
			);
		}

		return $options;
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'RECIPE_NAME' => array(
				'name' => _x( 'Recipe name', 'Uncanny Automator', 'uncanny-automator-pro' ),
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
		$selected_recipe_id = (int) $this->get_parsed_meta_value( 'RECIPE' );
		$selected_user      = $this->get_parsed_meta_value( 'RECIPE_USER' );

		if ( is_numeric( $selected_user ) ) {
			$user = get_user_by( 'ID', $selected_user );
		} elseif ( is_email( $selected_user ) ) {
			$user = get_user_by( 'email', $selected_user );
		} else {
			$user = get_user_by( 'login', $selected_user );
		}

		if ( ! $user instanceof \WP_User ) {
			$this->add_log_error( sprintf( esc_attr_x( 'No user found matching: %s', 'Uncanny Automator', 'uncanny-automator-pro' ), $selected_user ) );

			return false;
		}

		$selected_user_id = $user->ID;
		$jobs             = Async_Actions::get_upcoming_jobs_for_user( $selected_user_id );
		$errors           = array();
		foreach ( $jobs as $job ) {
			if ( -1 !== $selected_recipe_id && $selected_recipe_id !== $job['recipe_id'] ) {
				continue;
			}

			$result = Async_Actions::cancel_job( $job['action_log_id'], $job['action_id'], $job['recipe_log_id'], $recipe_id );
			if ( ! empty( $result['error'] ) ) {
				$errors[] = $result['error'];
			}
		}

		if ( ! empty( $errors ) ) {
			throw new \Exception( implode( ', ', $errors ) );
		}

		return true;
	}

}
