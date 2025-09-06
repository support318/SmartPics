<?php

namespace Uncanny_Automator_Pro;

use EDD_Recurring_Subscriber;

/**
 * Class EDD_USER_HAS_ACTIVE_SUBSCRIPTION
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_USER_HAS_ACTIVE_SUBSCRIPTION extends Action_Condition {

	/**
	 * @return void
	 */
	public function define_condition() {
		$this->integration   = 'EDD';
		$this->name          = esc_attr_x( 'The user has an active subscription to {{a specific download}}', 'EDD Recurring', 'uncanny-automator-pro' );
		$this->code          = 'EDDR_ACTIVE_SUBSCRIPTION';
		$this->dynamic_name  = sprintf(
			esc_html_x( 'The user has an active subscription to {{a specific download:%s}}', 'EDD Recurring', 'uncanny-automator-pro' ),
			'EDD_DOWNLOAD'
		);
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {
		$options = Automator()->helpers->recipe->options->edd->all_edd_downloads( '', 'EDD_DOWNLOAD', false, false, true );

		$all_subscription_products = array();
		foreach ( $options['options'] as $key => $option ) {
			$all_subscription_products[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			$this->field->select(
				array(
					'option_code'            => 'EDD_DOWNLOAD',
					'label'                  => esc_html_x( 'Download', 'EDD Recurring', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => true,
					'required'               => true,
					'options'                => $all_subscription_products,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$download_id = $this->get_parsed_option( 'EDD_DOWNLOAD' );
		$subscriber  = new EDD_Recurring_Subscriber( $this->user_id, true );

		if ( empty( $subscriber->get_subscriptions( $download_id, array( 'active' ) ) ) ) {
			$this->condition_failed( sprintf( esc_attr_x( 'The user does not have any active subscription to %s.', 'EDD Recurring', 'uncanny-automator-pro' ), $this->get_option( 'EDD_DOWNLOAD_readable' ) ) );
		}

		// If the condition is met, do nothing and let the action run.
	}

	/**
	 * Check if EDD Recurring dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return class_exists( 'EDD_Recurring' );
	}
}
