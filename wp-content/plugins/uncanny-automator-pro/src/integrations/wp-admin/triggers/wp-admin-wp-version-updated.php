<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * Class WP_ADMIN_WP_VERSION_UPDATED
 * @package Uncanny_Automator_Pro
 */
class WP_ADMIN_WP_VERSION_UPDATED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WP_ADMIN' );
		$this->set_trigger_code( 'WP_ADMIN_VERSION_UPDATED' );
		$this->set_trigger_meta( 'WP_VERSION_UPDATED' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( esc_attr_x( 'WordPress version is updated', 'WP Admin', 'uncanny-automator-pro' ) );
		$this->set_readable_sentence( esc_attr_x( 'WordPress version is updated', 'WP Admin', 'uncanny-automator-pro' ) );
		$this->add_action( 'upgrader_process_complete', 10, 2 );
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $upgrade_obj, $upgrade_data ) = $hook_args;

		if ( empty( $upgrade_data['action'] ) || empty( $upgrade_data['type'] ) ) {
			return false;
		}

		return $upgrade_data['action'] === 'update' && $upgrade_data['type'] === 'core';
	}

	/**
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$common_tokens = $this->helpers->wp_admin_get_common_tokens_for_core_update();

		return array_merge( $tokens, $common_tokens );
	}

	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		return $this->helpers->wp_admin_parse_common_tokens( $hook_args );
	}
}
