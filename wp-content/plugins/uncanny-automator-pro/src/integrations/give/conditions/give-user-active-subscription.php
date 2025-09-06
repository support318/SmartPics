<?php

namespace Uncanny_Automator_Pro;

use Give_Recurring_Subscriber;

/**
 * Class GIVE_USER_ACTIVE_SUBSCRIPTION
 *
 * @pacakge Uncanny_Automator_Pro
 */
class GIVE_USER_ACTIVE_SUBSCRIPTION extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {
		$this->integration   = 'GIVEWP';
		$this->name          = esc_attr_x( 'The user {{has/does not have}} an active recurring donation', 'GiveWP Recurring', 'uncanny-automator-pro' );
		$this->code          = 'GIVEWP_USER_SUBSCRIPTION';
		$this->dynamic_name  = sprintf( esc_html_x( 'The user {{have/does not have:%s}} an active recurring donation', 'GiveWP Recurring', 'uncanny-automator-pro' ), 'CONDITION_CRITERIA' );
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return void
	 */
	public function fields() {
		return array(
			$this->field->select(
				array(
					'option_code'            => 'CONDITION_CRITERIA',
					'supports_custom_value'  => false,
					'label'                  => esc_html__( 'Criteria', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options_show_id'        => false,
					'options'                => array(
						array(
							'value' => 'has',
							'text'  => esc_html__( 'has', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'does_not_have',
							'text'  => esc_html__( 'does not have', 'uncanny-automator-pro' ),
						),
					),
				)
			),
		);
	}

	/**
	 * Method evaluate_condition
	 *
	 * If the conditions fail, use the $this->condition_failed( $log_message ) inside this method
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$criteria   = $this->get_option( 'CONDITION_CRITERIA' );
		$subscriber = new Give_Recurring_Subscriber( $this->user_id, true );

		if ( ! $subscriber instanceof Give_Recurring_Subscriber ) {
			$this->condition_failed( sprintf( 'The user (ID: %d) does not have any recurring donations.', $this->user_id ) );
		}

		$subscriptions = $subscriber->get_subscriptions( 0, array( 'status' => array( 'active' ) ) );

		if ( 'has' === $criteria && empty( $subscriptions ) ) {
			$this->condition_failed( 'No active recurring donations found for this user.' );

			return;
		}

		if ( 'does_not_have' === $criteria && ! empty( $subscriptions ) ) {
			$this->condition_failed( 'Recurring subscriptions found for this user.' );
		}
	}

	/**
	 * Check if Give Recurring dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return class_exists( 'Give_Recurring_Subscriber' );
	}
}
