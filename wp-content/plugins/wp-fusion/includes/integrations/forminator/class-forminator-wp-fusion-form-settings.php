<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// this has to be "Wpfusion" because the autoloader uses ucwords() on the addon slug.
class Forminator_Wpfusion_Form_Settings extends Forminator_Integration_Form_Settings {
	use Forminator_WP_Fusion_Settings_Trait;
}

trait Forminator_WP_Fusion_Settings_Trait {

	/**
	 * @var Forminator_WP_Fusion_CustomField
	 * @since 3.42.0 WP_Fusion Custom Fields
	 */
	protected $custom_fields;


	/**
	 * WP_Fusion Form Settings wizard
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 * @return array
	 */
	public function module_settings_wizards() {

		// numerical array steps.
		$wizard = array(
			array(
				'callback'     => array( $this, 'pick_name' ),
				'is_completed' => array( $this, 'setup_name_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'select_list' ),
				'is_completed' => array( $this, 'select_list_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'map_fields' ),
				'is_completed' => array( $this, 'map_fields_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_options' ),
				'is_completed' => array( $this, 'setup_options_is_completed' ),
			),
		);

		if ( ! wpf_get_option( 'available_lists' ) ) {
			unset( $wizard[1] );
		}
		return $wizard;
	}

	/**
	 * Set up Connection Name
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function pick_name( $submitted_data ) {
		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}
		$template_params = array(
			'name'       => $this->get_multi_id_settings( $multi_id, 'name', '' ),
			'name_error' => '',
			'multi_id'   => $multi_id,
		);

		unset( $submitted_data['multi_id'] );

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;
		if ( $is_submit ) {
			$name                    = isset( $submitted_data['name'] ) ? $submitted_data['name'] : '';
			$template_params['name'] = $name;

			try {
				if ( empty( $name ) ) {
					throw new Error( esc_html__( 'Please pick valid name', 'wp-fusion' ) );
				}

				$time_added = $this->get_multi_id_settings( $multi_id, 'time_added', time() );
				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'name'       => $name,
						'time_added' => $time_added,
					)
				);

			} catch ( Error $e ) {
				$template_params['name_error'] = $e->getMessage();
				$has_errors                    = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'wp-fusion' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this WP Fusion Integration from this Form.', 'wp-fusion' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
									Forminator_Integration::get_button_markup( esc_html__( 'Next', 'wp-fusion' ), 'forminator-addon-next' ) .
									'</div>';

		$template = WPF_Forminator::pick_name_template( $template_params );

		return array(
			'html'       => $template,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}


	/**
	 * Set up Contact List
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function select_list( $submitted_data ) {
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard( esc_html__( 'Please pick valid connection', 'wp-fusion' ) );
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'list_id'       => $this->get_multi_id_settings( $multi_id, 'list_id', '' ),
			'list_id_error' => '',
			'multi_id'      => $multi_id,
			'error_message' => '',
			'lists'         => array(),
		);

		$is_submit                = ! empty( $submitted_data );
		$has_errors               = false;
		$lists                    = wpf_get_option( 'available_lists' );
		$template_params['lists'] = $lists;

		if ( $is_submit ) {
			$list_id                    = isset( $submitted_data['list_id'] ) ? $submitted_data['list_id'] : '';
			$template_params['list_id'] = $list_id;

			try {
				if ( empty( $list_id ) ) {
					throw new Error( esc_html__( 'Please pick a valid list', 'wp-fusion' ) );
				}

				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				if ( ! in_array( $list_id, array_keys( $lists ) ) ) {
					throw new Error( esc_html__( 'Please pick a valid list', 'wp-fusion' ) );
				}

				$list_name = $lists[ $list_id ];

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'list_id'   => $list_id,
						'list_name' => $list_name,
					)
				);

			} catch ( Error $e ) {
				$template_params['list_id_error'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'wp-fusion' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this WP Fusion Integration from this Form.', 'wp-fusion' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
									Forminator_Integration::get_button_markup( esc_html__( 'Next', 'wp-fusion' ), 'forminator-addon-next' ) .
									'</div>';

		$template = WPF_Forminator::select_list_template( $template_params );
		return array(
			'html'       => $template,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
			'has_back'   => true,
		);
	}

	/**
	 * Check if select contact list completed
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function select_list_is_completed( $submitted_data ) {
		$multi_id = '';
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		if ( empty( $multi_id ) ) {
			return false;
		}

		$list_id = $this->get_multi_id_settings( $multi_id, 'list_id', '' );

		if ( empty( $list_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get crm email field that's unique to the crm which is always equal to 'user_email'.
	 *
	 * @return string
	 */
	private function get_crm_email_field() {
		$crm_field      = 'email';
		$contact_fields = wpf_get_option( 'contact_fields', array() );

		foreach ( $contact_fields as $key => $field ) {
			if ( $key === 'user_email' ) {
				$crm_field = $field['crm_field'];
			}
		}

		return $crm_field;
	}


	/**
	 * Set up fields map
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function map_fields( $submitted_data ) {
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard( esc_html__( 'Please pick valid connection', 'wp-fusion' ) );
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		// find type of email.
		$email_fields                 = array();
		$forminator_field_element_ids = array();
		foreach ( $this->form_fields as $form_field ) {
			// collect element ids.
			$forminator_field_element_ids[] = $form_field['element_id'];
			if ( 'email' === $form_field['type'] ) {
				$email_fields[] = $form_field;
			}
		}

		$template_params = array(
			'fields_map'    => $this->get_multi_id_settings( $multi_id, 'fields_map', array() ),
			'multi_id'      => $multi_id,
			'error_message' => '',
			'fields'        => array(),
			'form_fields'   => $this->form_fields,
			'email_fields'  => $email_fields,
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$list_id = $this->get_multi_id_settings( $multi_id, 'list_id', 0 );

		$template_params['fields'] = wp_fusion()->settings->get_crm_fields_flat();

		if ( $is_submit ) {
			$fields_map                    = isset( $submitted_data['fields_map'] ) ? $submitted_data['fields_map'] : array();
			$template_params['fields_map'] = $fields_map;

			try {
				if ( empty( $fields_map ) ) {
					throw new Error( esc_html__( 'Please assign fields.', 'wp-fusion' ) );
				}

				$crm_email_field = $this->get_crm_email_field();

				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( ! isset( $fields_map[ $crm_email_field ] ) || empty( $fields_map[ $crm_email_field ] ) ) {
					$input_exceptions->add_input_exception( 'Please assign field for Email Address', 'email_error' );
				}

				$fields_map_to_save = array();
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $key => $title ) {
						if ( isset( $fields_map[ $key ] ) && ! empty( $fields_map[ $key ] ) ) {
							$element_id = $fields_map[ $key ];
							if ( ! in_array( $element_id, $forminator_field_element_ids, true ) ) {
								$input_exceptions->add_input_exception(
									sprintf(
									/* translators: %s: Field title */
										esc_html__( 'Please assign valid field for %s', 'wp-fusion' ),
										esc_html( $title )
									),
									$key . '_error'
								);
								continue;
							}

							$fields_map_to_save[ $key ] = $fields_map[ $key ];
						}
					}
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_setting_values( $multi_id, array( 'fields_map' => $fields_map ) );

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Error $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'wp-fusion' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this WP Fusion Integration from this Form.', 'wp-fusion' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
									Forminator_Integration::get_button_markup( esc_html__( 'Next', 'wp-fusion' ), 'forminator-addon-next' ) .
									'</div>';
		$template                  = WPF_Forminator::map_fields_template( $template_params );
		return array(
			'html'       => $template,
			'buttons'    => $buttons,
			'size'       => 'normal',
			'redirect'   => false,
			'has_errors' => $has_errors,
			'has_back'   => true,
		);
	}

	/**
	 * Check if fields mapped
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function map_fields_is_completed( $submitted_data ) {
		$multi_id = '';
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		if ( empty( $multi_id ) ) {
			return false;
		}

		$fields_map = $this->get_multi_id_settings( $multi_id, 'fields_map', array() );

		if ( empty( $fields_map ) || ! is_array( $fields_map ) || count( $fields_map ) < 1 ) {
			return false;
		}

		$crm_email_field = $this->get_crm_email_field();
		if ( ! isset( $fields_map[ $crm_email_field ] ) || empty( $fields_map[ $crm_email_field ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Set up options
	 *
	 * Contains :
	 * - Double opt-in form,
	 * - tags,
	 * - instant-responder,
	 * - send last broadcast
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function setup_options( $submitted_data ) {
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard( esc_html__( 'Please pick valid connection', 'wp-fusion' ) );
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$forminator_form_element_ids = array();
		foreach ( $this->form_fields as $field ) {
			$forminator_form_element_ids[ $field['element_id'] ] = $field;
		}

		$template_params = array(
			'multi_id'             => $multi_id,
			'error_message'        => '',
			'tags_fields'          => array(),
			'tags_selected_fields' => array(),
		);

		$saved_tags = $this->get_multi_id_settings( $multi_id, 'tags', array() );
		if ( isset( $submitted_data['tags'] ) && is_array( $submitted_data['tags'] ) ) {
			$saved_tags = $submitted_data['tags'];
		}

		$tags                 = wp_fusion()->settings->get_available_tags_flat();
		$tags_selected_fields = array();
		$tags_fields          = array();

		foreach ( $tags as $key => $value ) {
			if ( ! empty( $saved_tags ) && in_array( $key, $saved_tags ) ) {
				$tags_selected_fields[ $key ] = $value;
			} else {
				$tags_fields[ $key ] = $value;
			}
		}

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;

		if ( $is_submit ) {

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'tags' => $saved_tags,
					)
				);

				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully connected to your form', 'wp-fusion' ),
				);
				$is_close     = true;

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Error $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$template_params['tags_fields']          = $tags_fields;
		$template_params['tags_selected_fields'] = $tags_selected_fields;

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'wp-fusion' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this WP Fusion Integration from this Form.', 'wp-fusion' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
									Forminator_Integration::get_button_markup( esc_html__( 'Save', 'wp-fusion' ), 'sui-button-primary forminator-addon-finish' ) .
									'</div>';

		$template = WPF_Forminator::setup_options_template( $template_params );
		return array(
			'html'         => $template,
			'buttons'      => $buttons,
			'size'         => 'normal',
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
		);
	}

	/**
	 * Check if setup options completed
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function setup_options_is_completed( $submitted_data ) {
		// all settings here are optional, so it can be marked as completed.
		return true;
	}

	/**
	 * Generate multi id for multiple connection
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 * @return string
	 */
	public function generate_multi_id() {
		return uniqid( 'WP_Fusion_', true );
	}
}
