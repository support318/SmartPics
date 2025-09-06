<?php

namespace Uncanny_Automator_Pro;

/**
 * Class FLSUPPORT_ANON_TICKET_CLOSED
 * @package Uncanny_Automator_Pro
 */
class FLSUPPORT_ANON_TICKET_CLOSED extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'FLSUPPORT' );
		$this->set_trigger_code( 'FLST_TICKET_CLOSED' );
		$this->set_trigger_meta( 'TICKET_CLOSED' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// Trigger sentence - Fluent Support
		$this->set_sentence( esc_attr_x( 'A ticket is closed', 'Fluent Support', 'uncanny-automator-pro' ) );
		$this->set_readable_sentence( esc_attr_x( 'A ticket is closed', 'Fluent Support', 'uncanny-automator-pro' ) );
		$this->add_action( 'fluent_support/ticket_closed', 22, 2 );
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		return ! ( ! is_object( $hook_args[0] ) || ! is_object( $hook_args[1] ) );
	}

	/**
	 * Define Tokens.
	 *
	 * @param array $tokens
	 * @param array $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array(
			array(
				'tokenId'   => 'TICKET_ID',
				'tokenName' => __( 'Ticket ID', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'TICKET_TITLE',
				'tokenName' => __( 'Ticket subject', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'TICKET_CONTENT',
				'tokenName' => __( 'Ticket details', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'TICKET_PRIORITY',
				'tokenName' => __( 'Ticket priority', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'TICKET_PRODUCT_TITLE',
				'tokenName' => __( 'Ticket product', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'TICKET_ADMIN_URL',
				'tokenName' => __( 'Ticket admin URL', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
			array(
				'tokenId'   => 'CUSTOMER_EMAIL',
				'tokenName' => __( 'Customer email', 'uncanny-automator-pro' ),
				'tokenType' => 'email',
			),
			array(
				'tokenId'   => 'CUSTOMER_ID',
				'tokenName' => __( 'Customer ID', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
		);
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		list( $ticket, $person ) = $hook_args;
		$customer                = \FluentSupport\App\Models\Person::where( 'id', $ticket->customer_id )->first();
		$token_values            = array(
			'TICKET_ID'            => $ticket->id,
			'TICKET_TITLE'         => $ticket->title,
			'TICKET_CONTENT'       => $ticket->content,
			'TICKET_PRIORITY'      => $ticket->priority,
			'TICKET_PRODUCT_TITLE' => $ticket->product->title,
			'TICKET_ADMIN_URL'     => admin_url( "admin.php?page=fluent-support#/tickets/{$ticket->id}/view" ),
			'CUSTOMER_EMAIL'       => $customer->email,
			'CUSTOMER_ID'          => $customer->id,
		);

		return $token_values;
	}

}
