<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * Class WP_ADMIN_THEME_SWITCH
 * @package Uncanny_Automator_Pro
 */
class WP_ADMIN_THEME_SWITCH extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WP_ADMIN' );
		$this->set_trigger_code( 'WP_ADMIN_THEME_ACTION' );
		$this->set_trigger_meta( 'THEME_ACTIVATED_DEACTIVATED' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A theme:%1$s}} is {{activated/deactivated:%2$s}}', 'WP Admin', 'uncanny-automator-pro' ), 'WP_THEME:' . $this->get_trigger_meta(), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A theme}} is {{activated/deactivated}}', 'WP Admin', 'uncanny-automator-pro' ) );
		$this->add_action( 'switch_theme', 10, 3 );
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
				'option_code' => 'WP_THEME',
				'label'       => _x( 'Theme', 'WP Admin', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => $this->helpers->get_all_installed_themes(),
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
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $trigger['meta']['WP_THEME'] ) ) {
			return false;
		}

		list( $new_theme_name, $new_theme, $old_theme ) = $hook_args;
		$old_theme_name                                 = $old_theme->get( 'Name' );
		$selected_theme_status                          = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_theme                                 = $trigger['meta']['WP_THEME'];

		return ( intval( '-1' ) === intval( $selected_theme ) ||
				 ( ( 'activated' === $selected_theme_status && $selected_theme === $new_theme_name ) ||
				   ( 'deactivated' === $selected_theme_status && $selected_theme === $old_theme_name ) ) );
	}


	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		list( $new_theme_name, $new_theme, $old_theme ) = $hook_args;
		$theme_name                                     = $new_theme_name;
		if ( 'deactivated' === $completed_trigger['meta']['THEME_ACTIVATED_DEACTIVATED'] ) {
			$theme_name = $old_theme->get( 'Name' );
		}

		return array(
			$this->get_trigger_meta() => $completed_trigger['meta']['THEME_ACTIVATED_DEACTIVATED_readable'],
			'WP_THEME'                => $theme_name,
		);
	}
}
