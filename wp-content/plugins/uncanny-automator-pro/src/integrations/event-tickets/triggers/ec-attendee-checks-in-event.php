<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EC_ATTENDEE_CHECKS_IN_EVENT
 *
 * @package Uncanny_Automator_Pro
 */
class EC_ATTENDEE_CHECKS_IN_EVENT extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->set_integration( 'EC' );
		$this->set_trigger_code( 'EC_ATTENDEE_CHECKS_IN' );
		$this->set_trigger_meta( 'EC_EVENTS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// Trigger sentence - The Events Calendar
		$this->set_sentence( sprintf( esc_attr_x( 'An attendee checks in for {{an event:%1$s}}', 'The Events Calendar', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'An attendee checks in for {{an event}}', 'The Events Calendar', 'uncanny-automator-pro' ) );
		$this->add_action(
			array(
				'event_tickets_checkin',
				'eddtickets_checkin',
				'rsvp_checkin',
				'wootickets_checkin',
			),
			90,
			2
		);
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$events  = Automator()->helpers->recipe->event_tickets->options->all_ec_events( null, $this->get_trigger_meta() );
		$options = array();
		foreach ( $events['options'] as $k => $option ) {
			$options[] = array(
				'text'  => $option,
				'value' => $k,
			);
		}

		$events['relevant_tokens']['holder_name']  = esc_attr_x( 'Attendee name', 'Event Tickets', 'uncanny-automator-pro' );
		$events['relevant_tokens']['holder_email'] = esc_attr_x( 'Attendee email', 'Event Tickets', 'uncanny-automator-pro' );

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => __( 'Event', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $options,
				'relevant_tokens' => $events['relevant_tokens'],
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
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		if ( ! isset( $hook_args[0] ) ) {
			return false;
		}

		$selected_event_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$attendee_id       = $hook_args[0];
		$attendee_details  = tribe_tickets_get_attendees( $attendee_id, 'rsvp_order' );
		$event_ids         = array();
		foreach ( $attendee_details as $attendee_detail ) {
			$event_ids[] = $attendee_detail['event_id'];
		}

		return ( intval( '-1' ) === intval( $selected_event_id ) || in_array( $selected_event_id, $event_ids, true ) );
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
		$attendee_id      = $hook_args[0];
		$attendee_details = tribe_tickets_get_attendees( $attendee_id, 'rsvp_order' );
		$token_values     = array();
		foreach ( $attendee_details as $attendee_detail ) {
			$event_id = $attendee_detail['event_id'];
			$event    = get_post( $event_id );
			if ( ! $event instanceof \WP_Post ) {
				return $token_values;
			}

			$token_values = array(
				'EC_EVENTS_ID'        => $event->ID,
				'EC_EVENTS'           => $event->post_title,
				'EC_EVENTS_URL'       => get_permalink( $event->ID ),
				'EC_EVENTS_THUMB_ID'  => get_post_thumbnail_id( $event->ID ),
				'EC_EVENTS_THUMB_URL' => get_the_post_thumbnail_url( $event->ID ),
				'holder_name'         => $attendee_detail['holder_name'],
				'holder_email'        => $attendee_detail['holder_email'],
			);
		}

		return $token_values;
	}
}
