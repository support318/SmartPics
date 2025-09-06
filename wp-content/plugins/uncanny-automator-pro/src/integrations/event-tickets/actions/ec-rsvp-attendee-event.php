<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class EC_RSVP_ATTENDEE_EVENT
 *
 * @package Uncanny_Automator_Pro
 */
class EC_RSVP_ATTENDEE_EVENT {

	use Recipe\Actions;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'EC' );
		$this->set_action_code( 'ECRSVPATTENDEEFOREVNT' );
		$this->set_action_meta( 'ECEVENTS' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Event Tickets */
		$this->set_sentence( sprintf( esc_attr__( 'RSVP on behalf of {{an attendee:%1$s}} for {{an event:%2$s}}', 'uncanny-automator-pro' ), 'RSVPATTENDEENAME:' . $this->get_action_meta(), $this->get_action_meta() ) );

		/* translators: Action - Event Tickets */
		$this->set_readable_sentence( esc_attr__( 'RSVP on behalf of {{an attendee}} for {{an event}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->event_tickets->options->pro->all_ec_rsvp_events(
						__( 'Event', 'uncanny-automator-pro' ),
						$this->get_action_meta(),
						array(
							'is_ajax'      => true,
							'target_field' => 'RSVPEVENTTICKET',
							'endpoint'     => 'select_rsvp_tickets_from_selected_event',
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'RSVPEVENTTICKET',
							'label'       => esc_attr__( 'Ticket', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RSVPATTENDEENAME',
							'label'       => esc_attr__( 'Attendee name', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'John Doe', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RSVPATTENDEEEMAIL',
							'label'       => esc_attr__( 'Attendee email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'john@doe.com', 'uncanny-automator-pro' ),
							'input_type'  => 'email',
						)
					),
					Automator()->helpers->recipe->field->int(
						array(
							'option_code' => 'RSVPATTENDEEQTY',
							'label'       => esc_attr__( 'Number of guests', 'uncanny-automator-pro' ),
							'default'     => 1,
							'min_number'  => 1,
							'max_number'  => 999,
						)
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action. Most of the code is borrowed from TEC itself.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$event_id       = isset( $parsed[ $this->get_action_meta() ] ) ? absint( wp_strip_all_tags( $parsed[ $this->get_action_meta() ] ) ) : 0;
		$ticket_id      = isset( $parsed['RSVPEVENTTICKET'] ) ? absint( wp_strip_all_tags( $parsed['RSVPEVENTTICKET'] ) ) : 0;
		$attendee_name  = isset( $parsed['RSVPATTENDEENAME'] ) ? sanitize_text_field( $parsed['RSVPATTENDEENAME'] ) : '';
		$attendee_email = isset( $parsed['RSVPATTENDEEEMAIL'] ) ? sanitize_email( $parsed['RSVPATTENDEEEMAIL'] ) : '';
		$attendee_qty   = isset( $parsed['RSVPATTENDEEQTY'] ) ? absint( wp_strip_all_tags( $parsed['RSVPATTENDEEQTY'] ) ) : 1;

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp       = tribe( 'tickets.rsvp' );
		$post_id    = $event_id;
		$order_id   = \Tribe__Tickets__RSVP::generate_order_id();
		$product_id = $ticket_id;

		$attendee_details = array(
			'full_name'    => $attendee_name,
			'email'        => $attendee_email,
			'order_status' => 'yes',
			'optout'       => false,
			'order_id'     => $order_id,
		);

		$has_tickets = $rsvp->generate_tickets_for( $product_id, $attendee_qty, $attendee_details, false );

		if ( is_wp_error( $has_tickets ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = $has_tickets->get_error_message();
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}
		/**
		 * Fires when an RSVP attendee tickets have been generated.
		 *
		 * @param int $order_id ID of the RSVP order
		 * @param int $post_id ID of the post the order was placed for
		 * @param string $attendee_order_status 'yes' if the user indicated they will attend
		 */
		do_action( 'event_tickets_rsvp_tickets_generated', $order_id, $post_id, 'yes' );

		$send_mail_stati = array( 'yes' );

		/**
		 * Filters whether a confirmation email should be sent or not for RSVP tickets.
		 *
		 * This applies to attendance and non attendance emails.
		 *
		 * @param bool $send_mail Defaults to `true`.
		 */
		$send_mail = apply_filters( 'tribe_tickets_rsvp_send_mail', true );

		if ( $send_mail && $has_tickets ) {
			/**
			 * Filters the attendee order stati that should trigger an attendance confirmation.
			 *
			 * Any attendee order status not listed here will trigger a non attendance email.
			 *
			 * @param array $send_mail_stati An array of default stati triggering an attendance email.
			 * @param int $order_id ID of the RSVP order
			 * @param int $post_id ID of the post the order was placed for
			 * @param string $attendee_order_status 'yes' if the user indicated they will attend
			 */
			$send_mail_stati = apply_filters(
				'tribe_tickets_rsvp_send_mail_stati',
				$send_mail_stati,
				$order_id,
				$post_id,
				'yes'
			);

			// No point sending tickets if their current intention is not to attend
			if ( in_array( 'yes', $send_mail_stati, true ) ) {
				$rsvp->send_tickets_email( $order_id, $post_id );
			} else {
				$rsvp->send_non_attendance_confirmation( $order_id, $post_id );
			}
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
