<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Flsupport_Tokens;

/**
 * Class Flsupport_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Flsupport_Pro_Tokens extends Flsupport_Tokens {

	public function __construct() {
		$this->extend_token_rules();
		parent::__construct();
	}

	private function extend_token_rules() {
		add_filter(
			'uap_fl_support_ticket_tokens',
			function( $actions, $args ) {
				$actions[] = 'fluent_support/response_added_by_agent';
				$actions[] = 'fluent_support/ticket_closed';
				return $actions;
			},
			20,
			2
		);

		add_filter(
			'uap_fl_support_agent_tokens',
			function( $actions, $args ) {
				$actions[] = 'fluent_support/response_added_by_agent';
				$actions[] = 'fluent_support/ticket_closed';
				return $actions;
			},
			20,
			2
		);

		add_filter(
			'uap_fl_support_ticket_response_tokens',
			function( $actions, $args ) {
				$actions[] = 'fluent_support/response_added_by_agent';
				return $actions;
			},
			20,
			2
		);
	}
}
