<?php

namespace Uncanny_Automator_Pro;

/**
 * Class GP_REVOKE_ALL_POINTS_A
 *
 * @package Uncanny_Automator_Pro
 */
class GP_REVOKE_ALL_POINTS_A {
	use Recipe\Action_Tokens;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GP';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'GPREVOKEALLPOINTS';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/gamipress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Actions - GamiPress */
			'sentence'           => sprintf( __( 'Revoke all {{of a certain type of:%1$s}} points from the user', 'uncanny-automator-pro' ), 'GPPOINTSTYPE' ),
			/* translators: Actions - GamiPress */
			'select_option_name' => __( 'Revoke all {{of a certain type of}} points from the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'revoke_points' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'USER_PREVIOUS_POINTS_TOTAL' => array(
					'name' => __( "The user's previous points total", 'uncanny-automator-pro' ),
					'type' => 'int',
				),
				'USER_NEW_POINTS_TOTAL'      => array(
					'name' => __( "The user's new points total", 'uncanny-automator-pro' ),
					'type' => 'int',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load options method.
	 *
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options'       => array(),
				'options_group' => array(
					'GPPOINTSTYPE' => array(
						Automator()->helpers->recipe->gamipress->options->list_gp_points_types(
							__( 'Point type', 'uncanny-automator-pro' ),
							'GPPOINTSTYPE',
							array(
								'token'       => false,
								'is_ajax'     => false,
								'include_all' => true,
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function revoke_points( $user_id, $action_data, $recipe_id, $args ) {

		$points_type = $action_data['meta']['GPPOINTSTYPE'];

		if ( 'ua-all-gp-types' === $points_type ) {
			foreach ( gamipress_get_points_types_slugs() as $points_type ) {
				$deduct_points = gamipress_get_user_points( absint( $user_id ), $points_type );
				gamipress_deduct_points_to_user( absint( $user_id ), absint( $deduct_points ), $points_type );
			}
		} else {
			$prev_points   = get_user_meta( $user_id, "_gamipress_{$points_type}_points", true );
			$deduct_points = gamipress_get_user_points( absint( $user_id ), $points_type );
			gamipress_deduct_points_to_user( absint( $user_id ), absint( $deduct_points ), $points_type );
			$this->uo_hydrate_tokens( $points_type, $prev_points, $user_id );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

	private function uo_hydrate_tokens( $points_type, $prev_points, $user_id ) {
		$new_points = get_user_meta( $user_id, "_gamipress_{$points_type}_points", true );
		$this->hydrate_tokens(
			array(
				'USER_PREVIOUS_POINTS_TOTAL' => ( isset( $prev_points ) ) ? $prev_points : 0,
				'USER_NEW_POINTS_TOTAL'      => ( isset( $new_points ) ) ? $new_points : 0,
			)
		);
	}

}
