<?php

namespace Uncanny_Automator_Pro\Integrations\Mailster;

/**
 * Class MAILSTER_SUBSCRIBER_CLICKS_LINK
 * @package Uncanny_Automator_Pro
 */
class MAILSTER_SUBSCRIBER_CLICKS_LINK extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'MAILSTER' );
		$this->set_trigger_code( 'SUBSCRIBER_CLICKS_A_LINK' );
		$this->set_trigger_meta( 'MAILSTER_CAMPAIGNS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// translators: Mailster - Trigger
		$this->set_sentence( sprintf( esc_attr_x( 'A subscriber clicks a link in {{a Mailster email:%1$s}}', 'Mailster', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A subscriber clicks a link in {{a Mailster email}}', 'Mailster', 'uncanny-automator-pro' ) );
		$this->add_action( 'mailster_click', 10, 5 );
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
				'description'     => esc_html_x( 'Select the Mailster email campaign to monitor for link clicks.', 'Mailster', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $this->helpers->get_all_mailster_campaigns( true ),
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_trigger_meta() . '_LINK',
				'label'           => esc_html_x( 'Link URL', 'Mailster', 'uncanny-automator-pro' ),
				'required'        => false,
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

		list( $subscriber_id, $campaign_id, $target, $index, $campaign_index ) = $hook_args;

		$selected_campaign = $trigger['meta'][ $this->get_trigger_meta() ];
		$link_to_click     = $trigger['meta'][ $this->get_trigger_meta() . '_LINK' ];

		// Campaign validation
		$campaign_match = ( intval( '-1' ) === intval( $selected_campaign ) || absint( $campaign_id ) === absint( $selected_campaign ) );

		// Link validation - if empty, accept any link; if not empty, compare URLs without query parameters
		$link_match = true;
		if ( ! empty( $link_to_click ) ) {
			// Parse URLs to get base URL without query parameters and fragments
			$configured_parts = wp_parse_url( $link_to_click );
			$target_parts     = wp_parse_url( $target );
			
			// Rebuild base URLs (scheme + host + path)
			$configured_base = ( $configured_parts['scheme'] ?? 'https' ) . '://' . ( $configured_parts['host'] ?? '' ) . ( $configured_parts['path'] ?? '' );
			$target_base     = ( $target_parts['scheme'] ?? 'https' ) . '://' . ( $target_parts['host'] ?? '' ) . ( $target_parts['path'] ?? '' );
			
			$link_match = $configured_base === $target_base;
		}

		return $campaign_match && $link_match;
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
				'tokenId'   => 'MAILSTER_CLICKED_LINK',
				'tokenName' => esc_html_x( 'Clicked link URL', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
			array(
				'tokenId'   => 'MAILSTER_CLICKED_TIMESTAMP',
				'tokenName' => esc_html_x( 'Clicked timestamp', 'Mailster', 'uncanny-automator-pro' ),
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
		list( $subscriber_id, $campaign_id, $target, $index, $campaign_index ) = $hook_args;

		$subscriber = mailster( 'subscribers' )->get( $subscriber_id );
		$campaign   = mailster( 'campaigns' )->get( $campaign_id );

		// Handle WP_Error or empty objects with fallback values
		$campaign_title = '';
		$subscriber_email = '';
		$clicked_timestamp = '';

		if ( ! is_wp_error( $campaign ) && ! empty( $campaign ) && isset( $campaign->post_title ) ) {
			$campaign_title = $campaign->post_title;
		}

		if ( ! is_wp_error( $subscriber ) && ! empty( $subscriber ) && isset( $subscriber->email ) ) {
			$subscriber_email = $subscriber->email;
		}

		if ( ! is_wp_error( $subscriber ) && ! empty( $subscriber ) && ! is_wp_error( $campaign ) && ! empty( $campaign ) ) {
			$timestamp_result = mailster( 'actions' )->get_timestamp( 'click', $subscriber_id, $campaign_id, $index );
			if ( ! is_wp_error( $timestamp_result ) ) {
				$clicked_timestamp = $timestamp_result;
			}
		}

		return array(
			'MAILSTER_CAMPAIGN_TITLE'    => $campaign_title,
			'MAILSTER_SUBSCRIBER_EMAIL'  => $subscriber_email,
			'MAILSTER_CLICKED_LINK'      => $target,
			'MAILSTER_CLICKED_TIMESTAMP' => $clicked_timestamp,
		);
	}
}
