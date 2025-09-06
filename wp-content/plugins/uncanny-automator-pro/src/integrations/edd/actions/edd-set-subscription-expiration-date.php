<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_SET_SUBSCRIPTION_EXPIRATION_DATE
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_SET_SUBSCRIPTION_EXPIRATION_DATE extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {

		if ( ! class_exists( 'EDD_Recurring' ) ) {
			return;
		}

		$this->set_integration( 'EDD' );
		$this->set_action_code( 'EDDR_SET_SUBSCRIPTION_EXPIRY' );
		$this->set_action_meta( 'EDDR_PRODUCTS' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Set {{a subscription download:%1$s}} to expire on {{a specific date:%2$s}} for the user', 'EDD Recurring', 'uncanny-automator' ), $this->get_action_meta(), 'EXPIRATION_DATE:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Set {{a subscription download}} to expire on {{a specific date}} for the user', 'EDD Recurring', 'uncanny-automator' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {

		$options = Automator()->helpers->recipe->options->edd->all_edd_downloads( '', $this->get_action_meta(), false, false, true );

		$all_subscription_products = array();
		foreach ( $options['options'] as $key => $option ) {
			$all_subscription_products[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_action_meta(),
				'label'           => _x( 'Download', 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ),
				'required'        => true,
				'options'         => $all_subscription_products,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'date',
				'option_code'     => 'EXPIRATION_DATE',
				'label'           => _x( 'Expiration date', 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ),
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'SUBSCRIPTION_ID' => array(
				'name' => __( 'Subscription ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'DOWNLOAD_ID'     => array(
				'name' => __( 'Download ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'DOWNLOAD_NAME'   => array(
				'name' => __( 'Download name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'EXPIRATION_DATE' => array(
				'name' => __( 'Expiration date', 'uncanny-automator-pro' ),
				'type' => 'datetime',
			),
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		// Get the selected product ID
		$download_id     = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		$expiration_date = sanitize_text_field( $parsed['EXPIRATION_DATE'] );

		if ( empty( $download_id ) ) {
			$this->add_log_error( esc_attr_x( 'Invalid download ID.', 'EDD Recurring', 'uncanny-automator' ) );

			return false;
		}

		if ( empty( $expiration_date ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter expiry date.', 'EDD Recurring', 'uncanny-automator' ) );

			return false;
		}

		$download_name = sanitize_text_field( $parsed[ $this->get_action_meta() . '_readable' ] );
		$subscriber    = new \EDD_Recurring_Subscriber( $user_id, true );
		$subscriptions = $subscriber->get_subscriptions( $download_id, array( 'active', 'trialling' ) );
		if ( empty( $subscriptions ) ) {
			$this->add_log_error( sprintf( esc_attr_x( 'The user does not have any active subscription to "%s".', 'EDD Recurring', 'uncanny-automator' ), $download_name ) );

			return false;
		}

		foreach ( $subscriptions as $subscription ) {
			$subs = new \EDD_Subscription( $subscription->id );
			$subs->update( array( 'expiration' => date( 'Y-m-d H:i:s', strtotime( $expiration_date ) ) ) );

			$this->hydrate_tokens(
				array(
					'SUBSCRIPTION_ID' => $subscription->id,
					'DOWNLOAD_ID'     => $download_id,
					'DOWNLOAD_NAME'   => $download_name,
					'EXPIRATION_DATE' => $subs->expiration,
				)
			);
			break;
		}

		return true;
	}
}
