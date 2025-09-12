<?php

namespace Uncanny_Automator_Pro;

use EDD_Subscription;
use Uncanny_Automator\Recipe\Action;

/**
 * Class EDD_CANCEL_SUBCRIPTION_BY_ID
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_CANCEL_SUBCRIPTION_BY_ID extends Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {

		if ( ! class_exists( 'EDD_Recurring' ) ) {
			return;
		}

		$this->set_integration( 'EDD' );
		$this->set_action_code( 'EDDR_CANCEL_BY_ID' );
		$this->set_action_meta( 'EDDR_SUBSCRIPTION_ID' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( "Cancel the user's subscription matching {{a subscription ID:%1\$s}}", 'EDD Recurring', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( "Cancel the user's subscription matching {{a subscription ID}}", 'EDD Recurring', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_action_meta(),
				'label'           => _x( 'Subscription ID', 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		// Get the selected subscription ID
		$subscription_id = sanitize_text_field( $parsed[ $this->get_action_meta() ] );

		if ( empty( $subscription_id ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a valid subscription ID.', 'EDD Recurring', 'uncanny-automator-pro' ) );

			return false;
		}

		$subscription = new EDD_Subscription( $subscription_id );
		if ( ! $subscription ) {
			$this->add_log_error( sprintf( esc_attr_x( 'The provided ID %d did not return a valid Subscription.', 'EDD Recurring', 'uncanny-automator-pro' ), $subscription_id ) );

			return false;
		}

		if ( false === $subscription->can_cancel() ) {
			$this->add_log_error( sprintf( esc_attr_x( 'The subscription %d is noncancellable.', 'EDD Recurring', 'uncanny-automator-pro' ), $subscription->id ) );

			return false;
		}

		if ( false === apply_filters( 'automator_pro_edd_cancel_subscription_by_id_can_cancel', true, $subscription_id, $subscription, $this ) ) {
			$this->add_log_error( sprintf( esc_attr_x( 'You are not allowed to cancel this subscription %d.', 'EDD Recurring', 'uncanny-automator-pro' ), $subscription->id ) );

			return false;
		}

		$subscription->cancel();

		return true;
	}
}
