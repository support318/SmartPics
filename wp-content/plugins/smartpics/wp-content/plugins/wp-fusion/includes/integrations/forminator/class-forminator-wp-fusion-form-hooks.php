<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Forminator integration hooks class.
 *
 * @since 3.42.0
 *
 * @link https://wpfusion.com/documentation/lead-generation/forminator/
 */

class Forminator_Wpfusion_Form_Hooks extends Forminator_Integration_Form_Hooks {

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$addon_setting_values = $this->settings_instance->get_settings_values();
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to active campaign.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_contact_sync( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
				);
			}
		}

		return $data;
	}


	/**
	 * Get status on contact sync to WP Fusion
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 * @since 3.42.0 Add $form_entry_fields
	 *
	 * @param       $connection_id
	 * @param       $submitted_data
	 * @param       $connection_settings
	 * @param       $form_settings
	 * @param array               $form_entry_fields
	 *
	 * @return array `is_sent` true means its success send data to WP_Fusion, false otherwise
	 */
	private function get_status_on_contact_sync( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {

		$form_id = $this->module_id;

		try {
			$args = array();

			if ( wpf_get_option( 'available_lists' ) && ! isset( $connection_settings['list_id'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'List ID not properly set up.', 'wp-fusion' ) );
			}

			$fields_map = $connection_settings['fields_map'];

			$email_field_key = wpf_get_lookup_field();

			if ( isset( $submitted_data[ $connection_settings['fields_map'][ $email_field_key ] ] ) ) {
				$email = $submitted_data[ $connection_settings['fields_map'][ $email_field_key ] ];
				$email = strtolower( trim( $email ) );
			} else {
				$email = false;
			}

			// process rest extra fields if available.
			foreach ( $fields_map as $field_id => $element_id ) {
				if ( ! empty( $element_id ) && isset( $submitted_data[ $element_id ] ) ) {

					$type = 'text';

					// Get the type of the field from the form entry fields.
					foreach ( $form_entry_fields as $form_entry_field ) {
						if ( $form_entry_field['name'] === $element_id ) {
							$type = $form_entry_field['field_type'];
						}
					}

					$args[ $field_id ] = apply_filters( 'wpf_format_field_value', $submitted_data[ $element_id ], $type, $field_id );
				}
			}

			$form_args = array(
				'email_address'    => $email,
				'update_data'      => $args,
				'apply_tags'       => ! empty( $connection_settings['tags'] ) ? $connection_settings['tags'] : array(),
				'add_only'         => false,
				'integration_slug' => 'wp-fusion',
				'integration_name' => 'Forminator',
				'form_id'          => $form_id,
				'form_title'       => forminator_get_form_name( $form_id ),
				'form_edit_link'   => admin_url( 'admin.php?page=forminator-cform-wizard&id=' . $form_id ),
			);

			$contact_id = WPF_Forms_Helper::process_form_data( $form_args );

			if ( is_wp_error( $contact_id ) ) {
				return array(
					'is_sent'         => false,
					'connection_name' => $connection_settings['name'],
				);
			}

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => sprintf( esc_html__( 'Successfully send data to %s', 'wp-fusion' ), wp_fusion()->crm->name ),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, sprintf( 'Failed to Send to %s', wp_fusion()->crm->name ) );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
			);
		}
	}



	/**
	 * Loop through addon meta data on multiple WP Fusion(s)
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param $addon_meta_datas
	 *
	 * @return array
	 */
	protected function on_render_entry_multi_connection( $addon_meta_datas ) {
		$additional_entry_item = array();
		foreach ( $addon_meta_datas as $addon_meta_data ) {
			$additional_entry_item[] = $this->get_additional_entry_item( $addon_meta_data );
		}

		return $additional_entry_item;
	}

	/**
	 * Format additional entry item as label and value arrays
	 *
	 * - Integration Name : its defined by user when they adding WP_Fusion integration on their form
	 * - Sent To WP_Fusion : will be Yes/No value, that indicates whether sending data to WP_Fusion was successful
	 * - Info : Text that are generated by addon when building and sending data to WP_Fusion @see Forminator_WP_Fusion_Form_Hooks::add_entry_fields()
	 * - Below subentries will be added if full log enabled, @see Forminator_WP_Fusion::is_show_full_log() @see FORMINATOR_ADDON_WP_FUSION_SHOW_FULL_LOG
	 *      - API URL : URL that wes requested when sending data to WP_Fusion
	 *      - Data sent to WP_Fusion : encoded body request that was sent
	 *      - Data received from WP_Fusion : json encoded body response that was received
	 *
	 * @param $addon_meta_data
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 * @return array
	 */
	protected function get_additional_entry_item( $addon_meta_data ) {

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}
		$status                = $addon_meta_data['value'];
		$additional_entry_item = array(
			'label' => esc_html__( 'WP Fusion Integration', 'wp-fusion' ),
			'value' => '',
		);

		$sub_entries = array();
		if ( isset( $status['connection_name'] ) ) {
			$sub_entries[] = array(
				'label' => esc_html__( 'Integration Name', 'wp-fusion' ),
				'value' => $status['connection_name'],
			);
		}

		if ( isset( $status['is_sent'] ) ) {
			$is_sent       = true === $status['is_sent'] ? esc_html__( 'Yes', 'wp-fusion' ) : esc_html__( 'No', 'wp-fusion' );
			$sub_entries[] = array(
				'label' => esc_html__( 'Sent To WP Fusion', 'wp-fusion' ),
				'value' => $is_sent,
			);
		}

		if ( isset( $status['description'] ) ) {
			$sub_entries[] = array(
				'label' => esc_html__( 'Info', 'wp-fusion' ),
				'value' => $status['description'],
			);
		}

		if ( Forminator_WP_Fusion::is_show_full_log() ) {
			// too long to be added on entry data enable this with `define('FORMINATOR_ADDON_WP_FUSION_SHOW_FULL_LOG', true)`.
			if ( isset( $status['url_request'] ) ) {
				$sub_entries[] = array(
					'label' => esc_html__( 'API URL', 'wp-fusion' ),
					'value' => $status['url_request'],
				);
			}

			if ( isset( $status['data_sent'] ) ) {
				$sub_entries[] = array(
					'label' => esc_html__( 'Data sent to WP Fusion', 'wp-fusion' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_sent'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}

			if ( isset( $status['data_received'] ) ) {
				$sub_entries[] = array(
					'label' => esc_html__( 'Data received from WP Fusion', 'wp-fusion' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_received'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
		}

		$additional_entry_item['sub_entries'] = $sub_entries;

		// return single array.
		return $additional_entry_item;
	}

	/**
	 * WP_Fusion will add a column on the title/header row
	 * its called `WP Fusion Info` which can be translated on forminator lang
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 * @return array
	 */
	public function on_export_render_title_row(): array {

		$export_headers = array(
			'info' => esc_html__( 'WP Fusion Info', 'wp-fusion' ),
		);

		$form_id                = $this->form_id;
		$form_settings_instance = $this->form_settings_instance;

		/**
		 * Filter WP_Fusion headers on export file
		 *
		 * @since 3.42.0
		 *
		 * @param array                                         $export_headers         headers to be displayed on export file.
		 * @param int                                           $form_id                current Form ID.
		 * @param Forminator_WP_Fusion_Form_Settings $form_settings_instance WP_Fusion Form Settings instance.
		 */
		$export_headers = apply_filters(
			'forminator_addon_wp_fusion_export_headers',
			$export_headers,
			$form_id,
			$form_settings_instance
		);

		return $export_headers;
	}

	/**
	 * WP_Fusion will add a column that give user information whether sending data to WP_Fusion successfully or not
	 * It will only add one column even its multiple connection, every connection will be separated by comma
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param                             $addon_meta_data
	 *
	 * @return array
	 */
	public function on_export_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {

		$form_id                = $this->form_id;
		$form_settings_instance = $this->form_settings_instance;

		/**
		 *
		 * Filter WP_Fusion metadata that previously saved on db to be processed
		 *
		 * @since 3.42.0
		 *
		 * @param array                                         $addon_meta_data
		 * @param int                                           $form_id                current Form ID.
		 * @param Forminator_WP_Fusion_Form_Settings $form_settings_instance WP_Fusion Form Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_wp_fusion_metadata',
			$addon_meta_data,
			$form_id,
			$form_settings_instance
		);

		$export_columns = array(
			'info' => $this->get_from_addon_meta_data( $addon_meta_data, 'description', '' ),
		);

		/**
		 * Filter WP_Fusion columns to be displayed on export submissions
		 *
		 * @since 3.42.0
		 *
		 * @param array                                         $export_columns         column to be exported.
		 * @param int                                           $form_id                current Form ID.
		 * @param Forminator_Form_Entry_Model                   $entry_model            Form Entry Model.
		 * @param array                                         $addon_meta_data        meta data saved by addon on entry fields.
		 * @param Forminator_WP_Fusion_Form_Settings $form_settings_instance WP_Fusion Form Settings instance.
		 */
		$export_columns = apply_filters(
			'forminator_addon_wp_fusion_export_columns',
			$export_columns,
			$form_id,
			$entry_model,
			$addon_meta_data,
			$form_settings_instance
		);

		return $export_columns;
	}

	/**
	 * Get Addon meta data, will be recursive if meta data is multiple because of multiple connection added
	 *
	 * @since 3.42.0 WP_Fusion Addon
	 *
	 * @param        $addon_meta_data
	 * @param        $key
	 * @param string          $default
	 *
	 * @return string
	 */
	protected function get_from_addon_meta_data( $addon_meta_data, $key, $default = '' ) {
		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return $default;
		}

		$addon_meta_data = $addon_meta_data[0];

		// make sure its `status`, because we only add this.
		if ( 'status' !== $addon_meta_data['name'] ) {
			if ( stripos( $addon_meta_data['name'], 'status-' ) === 0 ) {
				$meta_data = array();
				foreach ( $addon_meta_datas as $addon_meta_data ) {
					// make it like single value so it will be processed like single meta data.
					$addon_meta_data['name'] = 'status';

					// add it on an array for next recursive process.
					$meta_data[] = $this->get_from_addon_meta_data( array( $addon_meta_data ), $key, $default );
				}

				return implode( ', ', $meta_data );
			}

			return $default;

		}

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return $default;
		}
		$status = $addon_meta_data['value'];
		if ( isset( $status[ $key ] ) ) {
			$connection_name = '';
			if ( 'connection_name' !== $key ) {
				if ( isset( $status['connection_name'] ) ) {
					$connection_name = '[' . $status['connection_name'] . '] ';
				}
			}

			return $connection_name . $status[ $key ];
		}

		return $default;
	}
}
