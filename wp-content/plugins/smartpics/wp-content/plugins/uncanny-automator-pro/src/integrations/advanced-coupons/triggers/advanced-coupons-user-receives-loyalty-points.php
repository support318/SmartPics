<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class ADVANCED_COUPONS_USER_RECEIVES_LOYALTY_POINTS
 *
 * @package Uncanny_Automator
 */
class ADVANCED_COUPONS_USER_RECEIVES_LOYALTY_POINTS extends Trigger {

	/**
	 * Validate requirements are met for this trigger.
	 *
	 * @return bool
	 */
	public function requirements_met() {
		if ( ! class_exists( 'LPFW' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Set up trigger.
	 *
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'ACFWC' );
		$this->set_trigger_code( 'ACFWCUSERRECEIVESLOYALTYPOINTS' );
		$this->set_trigger_meta( 'ACFWCLOYALTYPOINTS' );
		$this->set_trigger_type( 'user' );
		$this->set_is_pro( true );
		$this->set_sentence(
			sprintf(
			// translators: Number of points - Advanced Coupons
				esc_attr_x( 'A user receives {{a number:%1$s}} of loyalty points', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'A user receives {{a number}} of loyalty points', 'Advanced Coupons', 'uncanny-automator-pro' ) );
		$this->add_action( 'lpfw_point_entry_created', 10, 2 );
	}

	/**
	 * Define options.
	 *
	 * @return array[]
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->field->int(
				array(
					'option_code' => $this->get_trigger_meta(),
					'label'       => _x( 'Loyalty points', 'Advanced Coupons', 'uncanny-automator-pro' ),
					'token_name'  => _x( 'Loyalty points', 'Advanced Coupons', 'uncanny-automator-pro' ),
				)
			),
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$points_to_check       = $trigger['meta'][ $this->get_trigger_meta() ];
		list( $data, $object ) = $hook_args;

		if ( absint( $data['points'] ) === absint( $points_to_check ) ) {
			$this->set_user_id( $data['user_id'] );

			return true;
		}

		return false;
	}

	/**
	 * hydrate_tokens
	 *
	 * @param mixed $completed_trigger
	 * @param mixed $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$data = $hook_args[0];

		return array(
			$this->get_trigger_meta() => $data['points'],
		);
	}

}
