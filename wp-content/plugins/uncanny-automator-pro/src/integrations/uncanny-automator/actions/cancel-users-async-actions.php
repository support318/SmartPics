<?php

namespace Uncanny_Automator_Pro;

/**
 *
 */
class Cancel_Users_Async_Actions extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'UOA' );
		$this->set_action_code( 'CANCEL_USERS_ASYNC_ACTIONS' );
		$this->set_action_meta( 'RECIPE' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input date, from format, to format
		$this->set_sentence( sprintf( esc_attr__( "Cancel the user's scheduled actions for {{a recipe:%1\$s}}", 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( "Cancel the user's scheduled actions for {{a recipe}}", 'uncanny-automator-pro' ) );

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

		$recipes_data = Automator()->get_recipes_data( false );

		foreach ( $recipes_data as $recipe_id => $recipe ) {
			$options[] = array(
				'value' => $recipe_id,
				'text'  => get_the_title( $recipe_id ),
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

		$selected_recipe_id = $this->get_parsed_meta_value( 'RECIPE' );

		if ( '-1' !== intval( $selected_recipe_id ) ) {
			$this->hydrate_tokens(
				array(
					'RECIPE_NAME' => get_the_title( $selected_recipe_id ),
				)
			);
		}

		$jobs = Async_Actions::get_upcoming_jobs_for_user( $user_id );

		$errors = array();

		foreach ( $jobs as $job ) {

			if ( '-1' !== $selected_recipe_id && (int) $selected_recipe_id !== $job['recipe_id'] ) {
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
