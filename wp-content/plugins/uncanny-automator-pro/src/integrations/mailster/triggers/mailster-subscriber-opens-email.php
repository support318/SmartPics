<?php

namespace Uncanny_Automator_Pro\Integrations\Mailster;

/**
 * Class MAILSTER_SUBSCRIBER_OPENS_EMAIL
 * @package Uncanny_Automator_Pro
 */
class MAILSTER_SUBSCRIBER_OPENS_EMAIL extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'MAILSTER' );
		$this->set_trigger_code( 'SUBSCRIBER_OPENS_EMAIL' );
		$this->set_trigger_meta( 'MAILSTER_CAMPAIGNS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// translators: Mailster - Trigger
		$this->set_sentence( sprintf( esc_attr_x( 'A subscriber opens {{a Mailster email:%1$s}}', 'Mailster', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A subscriber opens {{a Mailster email}}', 'Mailster', 'uncanny-automator-pro' ) );
		$this->add_action( 'mailster_open', 10, 3 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'Campaign', 'Mailster', 'uncanny-automator-pro' ),
				'description'     => esc_html_x( 'Select the Mailster email campaign to monitor for open.', 'Mailster', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $this->helpers->get_all_mailster_campaigns( true ),
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $hook_args[0], $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		list( $subscriber_id, $campaign_id, $campaign_index ) = $hook_args;

		$selected_campaign = $trigger['meta'][ $this->get_trigger_meta() ];

		return intval( '-1' ) === intval( $selected_campaign ) || absint( $campaign_id ) === absint( $selected_campaign );
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens = array(
			array(
				'tokenId'   => 'MAILSTER_CAMPAIGN_TITLE',
				'tokenName' => esc_html_x( 'Campaign title', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'MAILSTER_SUBSCRIBER_EMAIL',
				'tokenName' => esc_html_x( 'Subscriber email', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'email',
			),
			array(
				'tokenId'   => 'MAILSTER_OPENED_TIMESTAMP',
				'tokenName' => esc_html_x( 'Open timestamp', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $trigger_tokens, $tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		list( $subscriber_id, $campaign_id, $campaign_index ) = $hook_args;

		$subscriber = mailster( 'subscribers' )->get( $subscriber_id );
		$campaign   = mailster( 'campaigns' )->get( $campaign_id );

		// Handle WP_Error or empty objects with fallback values
		$campaign_title = '';
		$subscriber_email = '';
		$opened_timestamp = '';

		if ( ! is_wp_error( $campaign ) && ! empty( $campaign ) && isset( $campaign->post_title ) ) {
			$campaign_title = $campaign->post_title;
		}

		if ( ! is_wp_error( $subscriber ) && ! empty( $subscriber ) && isset( $subscriber->email ) ) {
			$subscriber_email = $subscriber->email;
		}

		if ( ! is_wp_error( $subscriber ) && ! empty( $subscriber ) && ! is_wp_error( $campaign ) && ! empty( $campaign ) ) {
			$timestamp_result = mailster( 'actions' )->get_timestamp( 'open', $subscriber_id, $campaign_id, $campaign_index );
			if ( ! is_wp_error( $timestamp_result ) ) {
				$opened_timestamp = $timestamp_result;
			}
		}

		return array(
			'MAILSTER_CAMPAIGN_TITLE'   => $campaign_title,
			'MAILSTER_SUBSCRIBER_EMAIL' => $subscriber_email,
			'MAILSTER_OPENED_TIMESTAMP' => $opened_timestamp,
		);
	}
}
