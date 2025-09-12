<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EC_USERATTENDEVENT
 *
 * @package Uncanny_Automator_Pro
 */
class EC_USERATTENDEVENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'EC';

	private $trigger_code;
	private $trigger_meta;

	/**
	 *  Set Triggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'USERATTENDEVENT';
		$this->trigger_meta = 'ATTENDEVENT';
		$this->define_trigger();
	}

	/**
	 *  Define trigger settings
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/the-events-calendar/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - The Events Calendar */
			'sentence'            => sprintf( __( 'A user checks in for {{an event:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - The Events Calendar */
			'select_option_name'  => __( 'A user checks in for {{an event}}', 'uncanny-automator-pro' ),
			'action'              => array(
				'event_tickets_checkin',
				'eddtickets_checkin',
				'rsvp_checkin',
				'wootickets_checkin',
			),
			'priority'            => 9999,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'event_checkins' ),
			'options'             => array(
				Automator()->helpers->recipe->event_tickets->options->all_ec_events( __( 'Event', 'uncanny-automator' ), $this->trigger_meta ),
			),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Event checkins callback function.
	 *
	 * @param int $attendee_id Attendee id.
	 * @param object $qr QR code data.
	 */
	public function event_checkins( $attendee_id, $qr ) {

		if ( ! $attendee_id ) {
			return;
		}
		$attendee_details = tribe_tickets_get_attendees( $attendee_id, 'rsvp_order' );
		if ( empty( $attendee_details ) ) {
			return;
		}

		$attendee = false;
		foreach ( $attendee_details as $detail ) {
			if ( (int) $detail['attendee_id'] !== (int) $attendee_id ) {
				continue;
			}
			$attendee = $detail;
		}

		if ( ! $attendee ) {
			return;
		}

		$attendee_user = get_user_by( 'email', $attendee['holder_email'] );
		if ( ! $attendee_user ) {
			return;
		}

		$args = array(
			'code'         => $this->trigger_code,
			'meta'         => $this->trigger_meta,
			'post_id'      => (int) $attendee['event_id'],
			'user_id'      => $attendee_user->ID,
			'is_signed_in' => true,
		);

		Automator()->maybe_add_trigger_entry( $args );

	}
}
