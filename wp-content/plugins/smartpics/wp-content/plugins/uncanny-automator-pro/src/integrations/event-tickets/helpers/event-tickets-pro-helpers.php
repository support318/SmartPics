<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Event_Tickets_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Event_Tickets_Pro_Helpers {

	/**
	 * Event_Tickets_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options
		add_action( 'wp_ajax_select_tickets_from_selected_event', array( $this, 'select_event_tickets' ) );
		add_action( 'wp_ajax_select_rsvp_tickets_from_selected_event', array( $this, 'select_rsvp_event_tickets' ) );
	}

	/**
	 * @return void
	 */
	public function select_event_tickets() {
		Automator()->utilities->ajax_auth_check();
		$fields = array();
		if ( automator_filter_has_var( 'value', INPUT_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			$event_id = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$fields[] = array(
				'value' => '-1',
				'text'  => __( 'Any ticket', 'uncanny-automator-pro' ),
			);

			$tickets = \Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );

			if ( ! empty( $tickets ) ) {
				foreach ( $tickets as $ticket ) {
					$ticket_id   = $ticket->ID;
					$ticket_name = $ticket->name;
					// Check if the post title is defined
					$ticket_name = ! empty( $ticket_name ) ? $ticket_name : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $ticket_id );

					$fields[] = array(
						'value' => $ticket_id,
						'text'  => $ticket_name,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @return void
	 */
	public function select_rsvp_event_tickets() {
		Automator()->utilities->ajax_auth_check();
		$fields = array();
		if ( automator_filter_has_var( 'value', INPUT_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			$event_id = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$tickets = \Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
			if ( ! empty( $tickets ) ) {
				/** @var \Tribe__Tickets__Ticket_Object $ticket */
				foreach ( $tickets as $ticket ) {
					if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
						continue;
					}
					$ticket_id   = $ticket->ID;
					$ticket_name = $ticket->name;
					// Check if the post title is defined
					$ticket_name = ! empty( $ticket_name ) ? $ticket_name : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $ticket_id );

					$fields[] = array(
						'value' => $ticket_id,
						'text'  => $ticket_name,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @param $label
	 * @param $option_code
	 * @param $extra_args
	 *
	 * @return mixed|null
	 */
	public function all_ec_rsvp_events( $label = null, $option_code = 'ECEVENTS', $extra_args = array() ) {
		if ( ! $label ) {
			$label = esc_attr__( 'Event', 'uncanny-automator-pro' );
		}

		$is_ajax      = key_exists( 'is_ajax', $extra_args ) ? $extra_args['is_ajax'] : false;
		$target_field = key_exists( 'target_field', $extra_args ) ? $extra_args['target_field'] : '';
		$end_point    = key_exists( 'endpoint', $extra_args ) ? $extra_args['endpoint'] : '';

		$args = array(
			'posts_per_page' => 9999,
			'orderby'        => 'title',
			'order'          => 'DESC',
			'post_type'      => 'tribe_events',
			'post_status'    => 'publish',
		);

		$all_events = Automator()->helpers->recipe->options->wp_query( $args, false );

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			//'default_value'      => 'Any post',
			'options'         => $all_events,
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_all_ec_events', $option );
	}

	/**
	 * Portable function to add a filter to disable background processing.
	 *
	 * @since 5.8
	 *
	 * @return void
	 */
	public static function disable_core_background_actions() {
		add_filter( 'automator_is_background_action', array( self::class, 'app_integration_disable_background_actions' ), 10, 2 );
	}

	/**
	 * Disable for background processing actions.
	 *
	 * @since 5.8
	 *
	 * @param bool $is_background_processing
	 * @param string[] $action
	 *
	 * @return bool
	 */
	public static function app_integration_disable_background_actions( $is_background_processing, $action ) {

		$action_code = $action['action_data']['meta']['code'] ?? '';

		$action = Automator()->get_action( $action_code );

		if ( isset( $action['background_processing'] ) && true === $action['background_processing'] ) {
			return false;
		}

		return $is_background_processing;

	}
}
