<?php

namespace Uncanny_Automator_Pro;

use \Uncanny_Automator\Recipe;

/**
 * Class GP_EARNSRANK
 *
 * @package Uncanny_Automator_Pro
 */
class GP_EARNSRANK {

	use Recipe\Triggers;

	public function __construct() {

		$this->setup_trigger();

	}

	public function setup_trigger() {

		$this->set_integration( 'GP' );

		$this->set_trigger_code( 'GPEARNSRANK' );

		$this->set_trigger_meta( 'GPRANK' );

		$this->set_author( Automator()->get_author_name( $this->get_trigger_code() ) );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/gamipress/' ) );

		$this->set_is_pro( true );

		/* translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user attains {{a rank:%1$s}}', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );

		$this->set_readable_sentence( esc_html__( 'A user attains {{a rank}}', 'uncanny-automator-pro' ) );

		$this->set_action_hook( 'gamipress_update_user_rank' );

		$this->set_action_args_count( 5 );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	/**
	 * Implements `prepare_to_run` abstract method from Trait Uncanny_Automator\Recipe\Triggers.
	 *
	 * Tells Automator to match the option fields during process.
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Implements `validate_trigger` abstract method from Trait Uncanny_Automator\Recipe\Trigger_Filters.
	 *
	 * Validates the trigger before its processed.
	 *
	 * @param array $args Spat operator contains all the arguments passed by the action hook.
	 */
	public function validate_trigger( ...$args ) {

		list( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) = $args[0];
		$this->set_user_id( $user_id ); // Explicitly updating the user ID so if an admin updates profile, it uses the passed ID.

		return ! empty( $user_id ); // Just check if user id is not empty since its a user type trigger.
	}

	/**
	 * Overwrites validate_conditions from Uncanny_Automator\Recipe\Trigger_Conditions Traits.
	 *
	 * Basically scans all recipes and return the matching recipe id and with trigger id.
	 *
	 * @param array $args The arguments passed by the action hook.
	 *
	 * @return array The matching recipe triggers in the format [123=>456] where 123 is the recipe id and 456 is the trigger id.
	 * @uses Uncanny_Automator\Recipe\Trigger_Recipe_Filters Trigger_Recipe_Filters
	 *
	 */
	public function validate_conditions( $args ) {

		list( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) = $args;

		$result = $this->find_all( $this->trigger_recipes() )
					   ->where( array( $this->get_trigger_meta() ) )
					   ->match( array( $new_rank->ID ) )
					   ->format( array( 'absint' ) )
					   ->get();

		return $result;

	}


	/**
	 * Callback method to $this->set_options_callback.
	 *
	 * @return array The normalized option data.
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->gamipress->options->list_gp_rank_types(
							__( 'Rank type', 'uncanny-automator' ),
							'GPRANKTYPES',
							array(
								'token'        => false,
								'is_ajax'      => true,
								'target_field' => $this->get_trigger_meta(),
								'endpoint'     => 'select_ranks_from_types_EARNSRANK',
							)
						),
						Automator()->helpers->recipe->field->select_field(
							$this->get_trigger_meta(),
							__( 'Rank', 'uncanny-automator' ),
							array(),
							null,
							false,
							'',
							array(),
							array(
								'supports_custom_value' => false,
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Overwrites parse_addition_tokens from `Uncanny_Automator\Recipe\Trigger_Tokens\Trigger_Tokens`
	 *
	 * @return The parsed tokens.
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		list( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) = $args['trigger_args'];

		// Manually parse relevant tokens.
		$hydrated_tokens = array(
			'GPRANKTYPES' => $new_rank->post_type,
			'GPRANK'      => $new_rank->post_title,
		);

		return $parsed + $hydrated_tokens;

	}

}
