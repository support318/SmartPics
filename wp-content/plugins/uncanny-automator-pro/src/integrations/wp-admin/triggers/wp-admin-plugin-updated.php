<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * Class WP_ADMIN_PLUGIN_UPDATED
 * @package Uncanny_Automator_Pro
 */
class WP_ADMIN_PLUGIN_UPDATED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WP_ADMIN' );
		$this->set_trigger_code( 'WP_ADMIN_PLUGIN_UPDATED' );
		$this->set_trigger_meta( 'WP_PLUGIN_UPDATED' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A plugin:%1$s}} is updated', 'WP Admin', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A plugin}} is updated', 'WP Admin', 'uncanny-automator-pro' ) );
		$this->add_action( 'upgrader_process_complete', 10, 2 );
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
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Plugin', 'WP Admin', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $this->helpers->get_all_installed_plugins(),
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $hook_args[1] ) ) {
			return false;
		}

		$upgrade_data = $hook_args[1];
		if ( $upgrade_data['action'] === 'update' && $upgrade_data['type'] !== 'plugin' ) {
			return false;
		}

		$selected_plugin = $trigger['meta'][ $this->get_trigger_meta() ];
		$updated_plugins = $upgrade_data['plugins'];
		$names           = array();

		foreach ( $updated_plugins as $plugin ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$names[]     = $plugin_data['Name'];
		}

		return ( intval( '-1' ) === intval( $selected_plugin ) || in_array( $selected_plugin, $names, true ) );
	}

	/**
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$common_tokens = $this->helpers->wp_admin_get_common_tokens();

		return array_merge( $tokens, $common_tokens );
	}

	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		return $this->helpers->wp_admin_parse_common_tokens( $hook_args, 'plugins' );
	}
}
