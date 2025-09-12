<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * CLass WP_ADMIN_PLUGIN_ACTIVATED_DEACTIVATED
 * @package Uncanny_Automator_Pro
 */
class WP_ADMIN_PLUGIN_ACTIVATED_DEACTIVATED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WP_ADMIN' );
		$this->set_trigger_code( 'WP_ADMIN_PLUGIN_ACTION' );
		$this->set_trigger_meta( 'PLUGIN_ACTIVATED_DEACTIVATED' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A plugin:%1$s}} is {{activated/deactivated:%2$s}}', 'WP Admin', 'uncanny-automator-pro' ), 'WP_PLUGIN:' . $this->get_trigger_meta(), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A plugin}} is {{activated/deactivated}}', 'WP Admin', 'uncanny-automator-pro' ) );
		$this->add_action( array( 'activated_plugin', 'deactivated_plugin' ), 10, 2 );
	}

	/**
	 * options
	 *
	 * Override this method to display a default option group
	 *
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'  => 'select',
				'option_code' => 'WP_PLUGIN',
				'label'       => _x( 'Plugin', 'WP Admin', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => $this->helpers->get_all_installed_plugins(),
			),
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => _x( 'Status', 'WP Admin', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => $this->helpers->plugin_theme_statuses(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $hook_args[0] ) ) {
			return false;
		}

		$plugin                 = get_plugin_data( WP_PLUGIN_DIR . '/' . $hook_args[0] );
		$selected_plugin_status = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_plugin        = $trigger['meta']['WP_PLUGIN'];

		return ( ( intval( '-1' ) === intval( $selected_plugin ) || $selected_plugin === $plugin['Name'] ) &&
				 ( ( 'activated' === $selected_plugin_status && 'activated_plugin' === current_action() ) ||
				   ( 'deactivated' === $selected_plugin_status && 'deactivated_plugin' === current_action() ) ) );
	}


	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		$plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $hook_args[0] );

		return array(
			$this->get_trigger_meta() => $completed_trigger['meta']['PLUGIN_ACTIVATED_DEACTIVATED_readable'],
			'WP_PLUGIN'               => $plugin['Name'],
		);
	}
}
