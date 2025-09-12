<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class EC_REGISTEREDWITHWC
 *
 * @package Uncanny_Automator_Pro
 */
class EC_REGISTEREDWITHWC {


	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'EC' );
		$this->set_trigger_code( 'REGISTEREDWITHWC' );
		$this->set_trigger_meta( 'ECEVENTS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_login_required( false );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/the-events-calendar/' ) );
		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence */
				esc_attr__( 'An attendee is registered for {{an event:%1$s}} with WooCommerce', 'uncanny-automator' ),
				$this->get_trigger_meta()
			)
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'An attendee is registered for {{an event}} with WooCommerce', 'uncanny-automator' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create' );
		$this->set_action_args_count( 4 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * Method load_options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						Automator()->helpers->recipe->event_tickets->options->all_ec_events(
							__( 'Event', 'uncanny-automator-pro' ),
							$this->get_trigger_meta(),
							array(
								'is_ajax'      => true,
								'target_field' => 'EVENTTICKET',
								'endpoint'     => 'select_tickets_from_selected_event',
							)
						),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code' => 'EVENTTICKET',
								'label'       => esc_attr__( 'Ticket', 'uncanny-automator' ),
							)
						),
					),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $attendee, $attendee_data, $ticket, $__this ) = $args[0];
		if ( empty( $attendee ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		if ( empty( $args ) ) {
			return false;
		}
		list( $attendee, $attendee_data, $ticket, $__this ) = $args[0];
		$event_id                                           = isset( $attendee_data['post_id'] ) ? $attendee_data['post_id'] : null;
		$this->actual_where_values                          = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		// Find the event ID
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $event_id ) )
					->format( array( 'intval' ) )
					->get();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {
		return true;
	}

}
