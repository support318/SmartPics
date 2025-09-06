<?php

namespace Uncanny_Automator_Pro\Integrations\Fluent_Booking;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class FLUENT_BOOKING_GROUP_MEETING_SCHEDULED_WITH_HOST
 *
 * @pacakge Uncanny_Automator_Pro
 */
class FLUENT_BOOKING_GROUP_MEETING_SCHEDULED_WITH_HOST extends Trigger {

	protected $helpers;

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'FLUENT_BOOKING' );
		$this->set_trigger_code( 'FB_GROUP_MEETING_SCHEDULED_WITH_HOST' );
		$this->set_trigger_meta( 'FB_MEETING_SPECIFIC_HOST' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// Trigger sentence - FluentBooking
		$this->set_sentence( sprintf( esc_attr_x( 'A group meeting is scheduled with {{a specific host:%1$s}}', 'FluentBooking', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A group meeting is scheduled with {{a specific host}}', 'FluentBooking', 'uncanny-automator-pro' ) );
		$this->add_action( 'fluent_booking/after_booking_scheduled', 10, 3 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code' => $this->get_trigger_meta(),
					'label'       => esc_attr_x( 'Meeting host', 'FluentBooking', 'uncanny-automator-pro' ),
					'options'     => $this->helpers->get_all_hosts_option(),
				)
			),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $booking, $calendarSlot, $bookingData ) = $hook_args;

		if ( 'group' !== $bookingData['event_type'] ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_host = absint( $trigger['meta'][ $this->get_trigger_meta() ] );
		$host_user_id  = absint( $bookingData['host_user_id'] );

		return ( $selected_host === $host_user_id );
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
		$common_tokens = $this->helpers->get_fluent_booking_common_tokens();

		return array_merge( $tokens, $common_tokens );
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $booking, $calendarSlot, $bookingData ) = $hook_args;

		$parsed_values                              = $this->helpers->parse_common_token_values( $booking, $bookingData );
		$parsed_values[ $this->get_trigger_meta() ] = $trigger['meta'][ $this->get_trigger_meta() . '_readable' ];

		return $parsed_values;
	}
}
