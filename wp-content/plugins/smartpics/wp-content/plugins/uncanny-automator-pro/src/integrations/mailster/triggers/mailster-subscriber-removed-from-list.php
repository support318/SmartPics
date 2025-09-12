<?php

namespace Uncanny_Automator_Pro\Integrations\Mailster;

/**
 * Class MAILSTER_SUBSCRIBER_REMOVED_FROM_LIST
 * @package Uncanny_Automator_Pro
 */
class MAILSTER_SUBSCRIBER_REMOVED_FROM_LIST extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'MAILSTER' );
		$this->set_trigger_code( 'SUBSCRIBER_REMOVED_FROM_LIST' );
		$this->set_trigger_meta( 'MAILSTER_LISTS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// translators: Mailster - Trigger
		$this->set_sentence( sprintf( esc_attr_x( 'A new subscriber is removed from {{a Mailster list:%1$s}}', 'Mailster', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A new subscriber is removed from {{a Mailster list}}', 'Mailster', 'uncanny-automator-pro' ) );
		$this->add_action( 'mailster_list_removed', 10, 2 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'List', 'Mailster', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $this->helpers->get_all_mailster_lists( true ),
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

		$selected_list = $trigger['meta'][ $this->get_trigger_meta() ];

		return ( intval( '-1' ) === intval( $selected_list ) || absint( $hook_args[0] ) === absint( $selected_list ) );
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
				'tokenId'   => 'MAILSTER_LIST_ID',
				'tokenName' => esc_html_x( 'List ID', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'MAILSTER_LIST_TITLE',
				'tokenName' => esc_html_x( 'List title', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'MAILSTER_SUBSCRIBER_EMAIL',
				'tokenName' => esc_html_x( 'Subscriber email', 'Mailster', 'uncanny-automator-pro' ),
				'tokenType' => 'email',
			),
			array(
				'tokenId'   => 'MAILSTER_SUBSCRIBER_STATUS',
				'tokenName' => esc_html_x( 'Subscriber status', 'Mailster', 'uncanny-automator-pro' ),
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
		list( $list_id, $subscriber_id ) = $hook_args;

		$subscriber = mailster( 'subscribers' )->get( $subscriber_id );
		$list       = mailster( 'lists' )->get( $list_id );

		// Handle WP_Error or empty objects with fallback values
		$list_name = '';
		$subscriber_email = '';
		$subscriber_status = '';

		if ( ! is_wp_error( $list ) && ! empty( $list ) && isset( $list->name ) ) {
			$list_name = $list->name;
		}

		if ( ! is_wp_error( $subscriber ) && ! empty( $subscriber ) ) {
			if ( isset( $subscriber->email ) ) {
				$subscriber_email = $subscriber->email;
			}
			
			if ( isset( $subscriber->status ) ) {
				$status_result = mailster( 'subscribers' )->get_status( $subscriber->status, true );
				if ( ! is_wp_error( $status_result ) ) {
					$subscriber_status = $status_result;
				}
			}
		}

		return array(
			'MAILSTER_LIST_ID'           => $list_id,
			'MAILSTER_LIST_TITLE'        => $list_name,
			'MAILSTER_SUBSCRIBER_EMAIL'  => $subscriber_email,
			'MAILSTER_SUBSCRIBER_STATUS' => $subscriber_status,
		);
	}
}
