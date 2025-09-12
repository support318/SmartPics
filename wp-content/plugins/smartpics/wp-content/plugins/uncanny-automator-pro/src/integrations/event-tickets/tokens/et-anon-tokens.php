<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ET_ANON_TOKENS
 *
 * @package Uncanny_Automator_Pro
 */
class ET_ANON_TOKENS {


	/**
	 *
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_ec_attendeeregistered_tokens',
			array(
				$this,
				'et_anonattendee_possible_tokens',
			),
			20,
			2
		);

		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
		add_filter(
			'automator_maybe_trigger_ec_registeredwithwc_tokens',
			array(
				$this,
				'ec_wc_possible_tokens',
			),
			20,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'et_anonattendee_tokens' ), 200, 6 );

	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|mixed
	 */
	public function et_anonattendee_possible_tokens( $tokens = array(), $args = array() ) {

		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'holder_name',
				'tokenName'       => __( 'Attendee name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'holder_email',
				'tokenName'       => __( 'Attendee email', 'uncanny-automator-pro' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * save_token_data
	 *
	 * @param mixed $args
	 * @param mixed $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		if ( in_array( 'REGISTEREDWITHWC', $args['entry_args'], true ) ) {
			list ( $attendee, $attendee_data, $ticket, $__this ) = $args['trigger_args'];
			$trigger_log_entry                                   = $args['trigger_entry'];
			if ( ! empty( $attendee ) ) {
				Automator()->db->token->save( 'attendee_id', $attendee->ID, $trigger_log_entry );
				Automator()->db->token->save( 'attendee_data', maybe_serialize( $attendee_data ), $trigger_log_entry );
			}
		}
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function ec_wc_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$event_id = isset( $args['triggers_meta']['ECEVENTS'] ) ? $args['triggers_meta']['ECEVENTS'] : '-1';

		$fields = array(
			array(
				'tokenId'         => 'TICKET_NAME',
				'tokenName'       => __( 'Ticket name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ORDER_ID',
				'tokenName'       => __( 'Order ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ORDER_STATUS',
				'tokenName'       => __( 'Order status', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ORDER_PAYMENT_METHOD',
				'tokenName'       => __( 'Payment method', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ORDER_TOTAL',
				'tokenName'       => __( 'Order total', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_FIRST_NAME',
				'tokenName'       => __( 'Billing first name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_LAST_NAME',
				'tokenName'       => __( 'Billing last name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_COMPANY',
				'tokenName'       => __( 'Billing company', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_COUNTRY',
				'tokenName'       => __( 'Billing country', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_ADDRESS_1',
				'tokenName'       => __( 'Billing address line 1', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_ADDRESS_2',
				'tokenName'       => __( 'Billing address line 2', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_CITY',
				'tokenName'       => __( 'Billing city', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_STATE',
				'tokenName'       => __( 'Billing state', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_POSTCODE',
				'tokenName'       => __( 'Billing postcode', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_PHONE',
				'tokenName'       => __( 'Billing phone', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'BILLING_EMAIL',
				'tokenName'       => __( 'Billing email', 'uncanny-automator-pro' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_code,
			),
		);

		if ( intval( '-1' ) === intval( $event_id ) ) {
			$fields[] = array(
				'tokenId'         => 'holder_name',
				'tokenName'       => __( 'Attendee name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			);
			$fields[] = array(
				'tokenId'         => 'holder_email',
				'tokenName'       => __( 'Attendee email', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			);
		}

		$final_tokens = array_merge( $tokens, $fields );

		return Automator()->utilities->remove_duplicate_token_ids( $final_tokens );
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return false|float|int|mixed|string|\WP_Error
	 */
	public function et_anonattendee_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( empty( $pieces ) || empty( $trigger_data ) || empty( $replace_args ) ) {
			return $value;
		}

		if ( in_array( 'ATTENDEEREGISTERED', $pieces, true ) ) {

			$to_replace = $pieces[2];
			$event_id   = Automator()->db->token->get( 'event_id', $replace_args );
			$event      = get_post( $event_id );

			if ( ! $event instanceof \WP_Post ) {
				return $value;
			}

			switch ( $to_replace ) {
				case 'ANONATTENDEE':
					$value = esc_html( $event->post_title );
					break;
				case 'ANONATTENDEE_URL':
					$value = get_permalink( $event->ID );
					break;
				case 'ANONATTENDEE_THUMB_ID':
					$value = get_post_thumbnail_id( $event->ID );
					break;
				case 'ANONATTENDEE_THUMB_URL':
					$value = get_the_post_thumbnail_url( $event->ID );
					break;
				case 'holder_name':
					$value = Automator()->db->token->get( 'holder_name', $replace_args );
					$value = maybe_unserialize( $value );
					break;
				case 'holder_email':
					$value = Automator()->db->token->get( 'holder_email', $replace_args );
					$value = maybe_unserialize( $value );
					break;
				default:
					$value = $event->ID;
					break;
			}
		}

		if ( in_array( 'REGISTEREDWITHWC', $pieces, true ) ) {

			$to_replace    = $pieces[2];
			$attendee_data = Automator()->db->token->get( 'attendee_data', $replace_args );
			if ( empty( $attendee_data ) ) {
				return $value;
			}
			$event_id = $attendee_data['post_id'];
			$event    = get_post( $event_id );

			if ( ! $event instanceof \WP_Post ) {
				return $value;
			}
			$order_id = $attendee_data['order_id'];
			$order    = wc_get_order( $order_id );
			switch ( $to_replace ) {
				case 'ECEVENTS':
					$value = esc_html( $event->post_title );
					break;
				case 'ECEVENTS_URL':
					$value = get_permalink( $event->ID );
					break;
				case 'ECEVENTS_THUMB_ID':
					$value = get_post_thumbnail_id( $event->ID );
					break;
				case 'ECEVENTS_THUMB_URL':
					$value = get_the_post_thumbnail_url( $event->ID );
					break;
				case 'TICKET_NAME':
					$ticket_id = $attendee_data['ticket_id'];
					try {
						// Attempt to get the ticket provider class.
						$ticket_provider_class = (string) \Tribe__Tickets__Tickets::get_event_ticket_provider( $ticket_id );
						$ticket_provider       = new $ticket_provider_class();
						$ticket                = $ticket_provider->get_ticket( $event_id, $ticket_id );
						$value                 = $ticket->name;

					} catch ( \Exception $e ) {
						// Just get the post
						$ticket = get_post( $ticket_id );
						$value  = $ticket->post_title;
					}
					break;
				case 'ORDER_ID':
					$value = $order_id;

					break;
				case 'ORDER_STATUS':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_status();
					}
					break;
				case 'ORDER_PAYMENT_METHOD':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_payment_method_title();
					}
					break;
				case 'ORDER_TOTAL':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_total();
					}
					break;
				case 'BILLING_FIRST_NAME':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_first_name();
					}
					break;
				case 'BILLING_LAST_NAME':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_last_name();
					}
					break;
				case 'BILLING_EMAIL':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_email();
					}
					break;
				case 'BILLING_PHONE':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_phone();
					}
					break;
				case 'BILLING_COMPANY':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_company();
					}
					break;
				case 'BILLING_COUNTRY':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_country();
					}
					break;
				case 'BILLING_ADDRESS_1':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_address_1();
					}
					break;
				case 'BILLING_ADDRESS_2':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_address_2();
					}
					break;
				case 'BILLING_CITY':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_city();
					}
					break;
				case 'BILLING_STATE':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_state();
					}
					break;
				case 'BILLING_POSTCODE':
					if ( $order instanceof \WC_Order ) {
						$value = $order->get_billing_postcode();
					}
					break;
				case 'holder_name':
					$value = $attendee_data['full_name'];
					break;
				case 'holder_email':
					$value = $attendee_data['email'];
					break;
				case 'ECEVENTS_ID':
					$value = $attendee_data['post_id'];
					break;
			}
		}

		return $value;
	}

}
