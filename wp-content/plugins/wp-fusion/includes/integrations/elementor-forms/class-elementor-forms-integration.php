<?php
/**
 * WP Fusion - Elementor Forms Integration Handler.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.41.24
 */

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the integration with Elementor Forms.
 *
 * @since 3.41.24
 */
class WPF_Elementor_Forms_Integration extends Integration_Base {

	/**
	 * Get action ID.
	 *
	 * @since 3.41.24
	 * @return string ID
	 */
	public function get_name() {
		return 'wpfusion';
	}

	/**
	 * Get action label.
	 *
	 * @since 3.41.24
	 * @return string Label
	 */
	public function get_label() {
		return __( 'WP Fusion', 'wp-fusion' );
	}

	/**
	 * Get CRM fields.
	 *
	 * @since 3.41.24
	 * @return array The fields.
	 */
	public function get_fields() {
		$fields = array();

		$available_fields = wp_fusion()->settings->get_crm_fields_flat();

		foreach ( $available_fields as $field_id => $field_label ) {

			$remote_required = false;

			if ( 'Email' === $field_label ) {
				$remote_required = true;
			}

			$fields[] = array(
				'remote_label'    => $field_label,
				'remote_type'     => 'text',
				'remote_id'       => $field_id,
				'remote_required' => $remote_required,
			);

		}

		// Add as tag.
		if ( wp_fusion()->crm->supports( 'add_tags' ) ) {

			$fields[] = array(
				'remote_label'    => '+ Create tag(s) from',
				'remote_type'     => 'text',
				'remote_id'       => 'add_tag_e',
				'remote_required' => false,
			);

		}

		return $fields;
	}

	/**
	 * Registers settings.
	 *
	 * @since 3.41.24
	 *
	 * @param object $widget The widget instance.
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_wpfusion',
			array(
				'label'     => 'WP Fusion',
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$widget->add_control(
			'wpf_apply_tags',
			array(
				'label'       => __( 'Apply Tags', 'wp-fusion' ),
				// translators: %s: The CRM name.
				'description' => sprintf( __( 'The selected tags will be applied in %s when the form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => wp_fusion()->settings->get_available_tags_flat( false ),
				'multiple'    => true,
				'label_block' => true,
				'show_label'  => true,
			)
		);

		// If the CRM supports lists.
		if ( wp_fusion()->crm->supports( 'lists' ) ) {

			$widget->add_control(
				'wpf_apply_lists',
				array(
					'label'       => __( 'Apply Lists', 'wp-fusion' ),
					// translators: %s: The CRM name.
					'description' => sprintf( __( 'The selected lists will be applied in %s when the form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => wpf_get_option( 'available_lists', array() ),
					'multiple'    => true,
					'label_block' => true,
					'show_label'  => true,
				)
			);
		}

		$widget->add_control(
			'wpf_add_only',
			array(
				'label'       => __( 'Add Only', 'wp-fusion' ),
				'description' => __( 'Only add new contacts, don\'t update existing ones.', 'wp-fusion' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_block' => false,
				'show_label'  => true,
			)
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'local_id',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			)
		);

		$repeater->add_control(
			'remote_id',
			array(
				'type'    => Controls_Manager::SELECT,
				'default' => '',
			)
		);

		$widget->add_control(
			'wpfusion_fields_map',
			array(
				'label'       => __( 'Field Mapping', 'wp-fusion' ),
				'type'        => WPF_Elementor_Field_Mapping::CONTROL_TYPE,
				'separator'   => 'before',
				'render_type' => 'none',
				'fields'      => $repeater->get_controls(),
			)
		);

		do_action( 'wpf_elementor_forms_integration', $widget );

		$widget->end_controls_section();
	}

	/**
	 * Unsets WPF settings on export.
	 *
	 * @since 3.41.24
	 *
	 * @param array $element The element settings.
	 * @return array The element settings.
	 */
	public function on_export( $element ) {
		unset(
			$element['settings']['wpfusion_fields_map'],
			$element['settings']['wpf_apply_tags'],
			$element['settings']['wpf_apply_lists']
		);

		return $element;
	}

	/**
	 * Run
	 * Process form submission.
	 *
	 * @since 3.21.1
	 * @since 3.43.3 Switched from "sent_data" to "fields" for submitted data.
	 *
	 * @param object      $record       Elementor form record.
	 * @param object|bool $ajax_handler Ajax handler or false.
	 */
	public function run( $record, $ajax_handler = false ) {

		$sent_data     = $record->get( 'fields' );
		$form_settings = $record->get( 'form_settings' );
		$update_data   = array();
		$email_address = false;

		if ( ! empty( $form_settings['wpfusion_fields_map'] ) ) {

			foreach ( $form_settings['wpfusion_fields_map'] as $i => $field ) {

				if ( ! empty( $field['local_id'] ) && ! empty( $sent_data[ $field['local_id'] ] ) && ! empty( $field['remote_id'] ) ) {

					$value = $sent_data[ $field['local_id'] ]['value'];

					if ( false !== strpos( $field['remote_id'], 'add_tag_' ) ) {

						// Don't run the filter on dynamic tagging inputs.
						$update_data[ $field['remote_id'] ] = $value;
						continue;

					}

					// Let's get the type.
					$type = 'text';

					if ( isset( $form_settings['form_fields'][ $i ]['field_type'] ) ) {
						$type = $form_settings['form_fields'][ $i ]['field_type'];

						if ( 'acceptance' === $type ) {
							$type = 'checkbox';
						} elseif ( 'time' === $type ) {
							$type = 'date';
						} elseif ( 'textarea' === $type || 'hidden' === $type ) {
							$type = 'text';
						} elseif ( true === boolval( $form_settings['form_fields'][ $i ]['allow_multiple'] ) || ( 'checkbox' === $type && false !== strpos( strval( $form_settings['form_fields'][ $i ]['field_options'] ), PHP_EOL ) ) ) {
							$type = 'multiselect';
						}
					}

					if ( ! empty( $sent_data[ $field['local_id'] ]['raw_value'] ) && is_array( $sent_data[ $field['local_id'] ]['raw_value'] ) ) {
						$value = $sent_data[ $field['local_id'] ]['raw_value'];
						$type  = 'multiselect';
					}

					$update_data[ $field['remote_id'] ] = apply_filters( 'wpf_format_field_value', $value, $type, $field['remote_id'] );

					// For determining the email address, we'll try to find a field
					// mapped to the main lookup field in the CRM, but if not we'll take
					// the first email address on the form.
					if ( is_string( $value ) && is_email( $value ) && wpf_get_lookup_field() === $field['remote_id'] ) {
						$email_address = $value;
					} elseif ( false === $email_address && is_string( $value ) && is_email( $value ) ) {
						$email_address = $value;
					}
				}
			}
		}

		if ( false === $email_address ) {

			// Try to find any email address, in case it wasn't mapped.
			foreach ( $sent_data as $field ) {

				if ( is_string( $field['value'] ) && is_email( $field['value'] ) ) {
					$email_address = $field['value'];
					break;
				}
			}
		}

		if ( isset( $form_settings['wpf_add_only'] ) && 'yes' == $form_settings['wpf_add_only'] ) {
			$add_only = true;
		} else {
			$add_only = false;
		}

		if ( empty( $form_settings['wpf_apply_tags'] ) ) {
			$form_settings['wpf_apply_tags'] = array();
		}

		$form_settings['wpf_apply_tags'] = array_map( 'htmlspecialchars_decode', $form_settings['wpf_apply_tags'] );

		if ( empty( $update_data ) && empty( $form_settings['wpf_apply_tags'] ) ) {
			return;
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $form_settings['wpf_apply_tags'],
			'apply_lists'      => wp_fusion()->crm->supports( 'lists' ) && isset( $form_settings['wpf_apply_lists'] ) ? $form_settings['wpf_apply_lists'] : array(),
			'add_only'         => $add_only,
			'integration_slug' => 'elementor_forms',
			'integration_name' => 'Elementor Forms',
			'form_id'          => null,
			'form_title'       => null,
			'form_edit_link'   => null,
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		// Get submission id from table using user email.
		global $wpdb;
		$submission_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `submission_id` FROM {$wpdb->prefix}e_submissions_values WHERE `value` = %s AND `key` = 'email'",
				$email_address
			)
		);

		if ( intval( $submission_id ) !== 0 ) {
			$wpdb->insert(
				$wpdb->prefix . 'e_submissions_values',
				array(
					'submission_id' => $submission_id,
					'key'           => 'wpf_complete',
					'value'         => current_time( 'Y-m-d H:i:s' ),
				),
				array( '%d', '%s', '%s' )
			);

			$wpdb->insert(
				$wpdb->prefix . 'e_submissions_values',
				array(
					'submission_id' => $submission_id,
					'key'           => 'wpf_contact_id',
					'value'         => $contact_id,
				),
				array( '%d', '%s', '%s' )
			);

		}

		// Return after login + auto login.
		if ( isset( $_COOKIE['wpf_return_to'] ) && doing_wpf_auto_login() && $ajax_handler ) {

			$post_id = absint( $_COOKIE['wpf_return_to'] );
			$url     = get_permalink( $post_id );

			setcookie( 'wpf_return_to', '', time() - ( 15 * 60 ) );

			if ( ! empty( $url ) && wpf_user_can_access( $post_id ) ) {
				$ajax_handler->add_response_data( 'redirect_url', $url );
			}
		}
	}

	/**
	 * Handle panel request.
	 *
	 * @since 3.41.24
	 *
	 * @param array $data The request data.
	 */
	public function handle_panel_request( array $data ) {
		// This method is required by the parent class but not used.
	}

	/**
	 * Get field map control options.
	 *
	 * @since 3.37.13
	 *
	 * @return array The fields map control options.
	 */
	protected function get_fields_map_control_options() {
		return array(
			'default'   => $this->get_fields(),
			'condition' => array(),
		);
	}
}
