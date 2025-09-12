<?php

namespace Uncanny_Automator_Pro;

use TINCANNYSNC\Database;
use TINCANNYSNC\Module_CRUD;

/**
 * Class UOTC_CONDITION_USER_RECORDED_VERB_FOR_MODULE
 *
 * @package Uncanny_Automator
 */
class UOTC_CONDITION_USER_RECORDED_VERB_FOR_MODULE extends Action_Condition {

	/**
	 * Defines the condition.
	 *
	 * @return void
	 */
	public function define_condition() {
		$this->integration = 'UOTC';
		$this->name        = esc_html_x( 'A user has recorded {{a verb}} for {{a module}}', 'Uncanny Tincanny', 'uncanny-automator-pro' );
		$this->code        = 'USER_RECORDED_VERB_FOR_MODULE';
		// translators: %1$s is the criteria and %2$s is the group
		$this->dynamic_name  = sprintf( esc_html_x( 'The user has recorded a {{verb:%1$s}} for a {{module:%2$s}}', 'Uncanny Tincanny', 'uncanny-automator-pro' ), 'TCVERB', 'TCMODULE' );
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * Defines the fields.
	 *
	 * @return array
	 */
	public function fields() {
		$options = array();
		$modules = self::get_contents();

		$options[] = array(
			'text'  => esc_attr_x( 'Any module', 'Uncanny Tincanny', 'uncanny-automator-pro' ),
			'value' => '-1',
		);

		foreach ( $modules as $module ) {
			$options[] = array(
				'text'  => '(ID: ' . $module->ID . ') ' . $module->content,
				'value' => $module->ID,
			);
		}

		return array(
			$this->field->select(
				array(
					'option_code'            => 'TCVERB',
					'label'                  => esc_html_x( 'Verb', 'Uncanny Tincanny', 'uncanny-automator-pro' ),
					'required'               => true,
					'show_label_in_sentence' => false,
					'supports_custom_value'  => false,
					'options_show_id'        => false,
					'options'                => array(
						array(
							'text'  => esc_html_x( 'Any', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => '-1',
						),
						array(
							'text'  => esc_html_x( 'Completed', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'completed',
						),
						array(
							'text'  => esc_html_x( 'Passed', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'passed',
						),
						array(
							'text'  => esc_html_x( 'Failed', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'failed',
						),
						array(
							'text'  => esc_html_x( 'Answered', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'answered',
						),
						array(
							'text'  => esc_html_x( 'Attempted', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'attempted',
						),
						array(
							'text'  => esc_html_x( 'Experienced', 'Tin canny Reporting', 'uncanny-automator-pro' ),
							'value' => 'experienced',
						),
					),
				)
			),
			$this->field->select(
				array(
					'option_code'            => 'TCMODULE',
					'label'                  => esc_html_x( 'Module', 'Uncanny Tincanny', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => $options,
					'supports_custom_value'  => true,
					'options_show_id'        => false,
				)
			),
		);
	}

	/**
	 * Evaluates the condition.
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$module_id = intval( $this->get_parsed_option( 'TCMODULE' ) );
		$verb      = trim( $this->get_parsed_option( 'TCVERB' ) );
		$user_id   = absint( $this->user_id );

		global $wpdb;

		// Query all modules with the verb.
		if ( '-1' === (string) $module_id ) {

			if ( '-1' === (string) $verb ) {
				$id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}uotincan_reporting  WHERE user_id = %d AND verb != ''",
						$user_id
					)
				);

				if ( 0 === $id ) {
					$message = esc_html_x( 'The user has no verb for any module.', 'Uncanny Tincanny', 'uncanny-automator-pro' );
					$this->condition_failed( $message );
				}

				return;
			}

			// Query all modules with the verb.
			$id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}uotincan_reporting  WHERE user_id = %d AND verb = %s",
					$user_id,
					$verb
				)
			);

			if ( 0 === $id ) {
				// translators: %1$s is the verb
				$message = sprintf( esc_html_x( 'The user has no verb %1$s for any module.', 'Uncanny Tincanny', 'uncanny-automator-pro' ), $this->get_option( 'TCVERB_readable' ) );
				$this->condition_failed( $message );
			}

			return;
		}

		$module_data = self::get_item( $module_id );

		if ( empty( $module_data ) ) {
			$message = sprintf( esc_html_x( "The module %1\$s doesn't exist.", 'Uncanny Tincanny', 'uncanny-automator-pro' ), $this->get_option( 'TCMODULE_readable' ) );
			$this->condition_failed( $message );
			return;
		}

		$module_filename = $module_data['url'];

		$sql = $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}uotincan_reporting  WHERE user_id = %d AND module = %s AND verb = %s",
			$user_id,
			$module_filename,
			$verb
		);

		// Any verb
		if ( '-1' === (string) $verb ) {
			$sql = $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}uotincan_reporting  WHERE user_id = %d AND module = %s AND verb != ''",
				$user_id,
				$module_filename
			);
		}

		$id = (int) $wpdb->get_var( $sql );  // phpcs:ignore

		if ( 0 === $id ) {
			// translators: %1$s is the module and %2$s is the verb
			$message = sprintf( esc_html_x( 'The module %1$s has no verb %2$s for the user.', 'Uncanny Tincanny', 'uncanny-automator-pro' ), $this->get_option( 'TCMODULE_readable' ), $this->get_option( 'TCVERB_readable' ) );
			$this->condition_failed( $message );
		}
	}

	/**
	 * Get the contents of the modules.
	 *
	 * @return array
	 */
	private static function get_contents() {

		if ( class_exists( '\TINCANNYSNC\Database' ) ) {
			return Database::get_contents();
		}

		if ( class_exists( '\TINCANNYSNC\Module_CRUD' ) ) {
			return Module_CRUD::get_contents();
		}

		return array();
	}

	/**
	 * Get the item of the module.
	 *
	 * @param int $module_id
	 * @return array
	 */
	private static function get_item( $module_id ) {

		if ( class_exists( '\TINCANNYSNC\Database' ) ) {
			return Database::get_item( $module_id );
		}

		if ( class_exists( '\TINCANNYSNC\Module_CRUD' ) ) {
			return Module_CRUD::get_item( $module_id );
		}

		return array();
	}

	/**
	 * Check if Tin Canny dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return class_exists( '\TINCANNYSNC\Module_CRUD' ) || class_exists( '\TINCANNYSNC\Database' );
	}
}
