<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Events_Manager_Pro_Helpers
 * @package Uncanny_Automator_Pro
 */
class Events_Manager_Pro_Helpers extends \Uncanny_Automator\Events_Manager_Helpers {
	/**
	 * Events_Manager_Pro_Helpers constructor.
	 */
	public function __construct() {
		add_action(
			'wp_ajax_select_all_tickets_from_SELECTEDEVENT',
			array(
				$this,
				'tickets_from_selected_event',
			),
			15
		);
	}

	/**
	 * @param \Uncanny_Automator_Pro\Events_Manager_Pro_Helpers $pro
	 */
	public function setPro( \Uncanny_Automator_Pro\Events_Manager_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	public function tickets_from_selected_event() {

		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();

		if ( isset( $_POST ) ) {
			global $wpdb;
			$table = $wpdb->prefix . 'em_tickets';
			$event = automator_filter_input( 'value', INPUT_POST );
			$query = "SELECT ticket_id,ticket_name FROM  $table WHERE event_id = $event ORDER BY ticket_id";

			$all_tickets = $wpdb->get_results( $query );

			foreach ( $all_tickets as $ticket ) {
				$fields[] = array(
					'value' => $ticket->ticket_id,
					'text'  => $ticket->ticket_name,
				);
			}
		}

		echo wp_json_encode( $fields );
		die();
	}
}
