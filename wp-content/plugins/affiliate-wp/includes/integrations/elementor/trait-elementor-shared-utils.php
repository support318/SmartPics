<?php
/**
 * Trait: Elementor Shared Utils
 *
 * Provides shared utility methods for handling form data extraction and manipulation
 * within the context of Elementor form submissions. This trait is designed to
 * standardize and simplify the process of working with Elementor's form data.
 *
 * @package    AffiliateWP
 * @subpackage Integrations
 * @copyright  Copyright (c) 2024, Sandhills Development, LLC
 * @since      2.22.0
 */
trait Elementor_Shared_Utils {

	/**
	 * Get the form data for specified fields.
	 *
	 * @since 2.22.0
	 *
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param array $fields The fields to get data for.
	 * @param string $map_key The key for the fields map in form settings.
	 *
	 * @return array
	 */
	public function form_data( $record, array $fields, $map_key ) : array {
		$mapped_fields = $this->get_fields_map( $record, $map_key );
		$sent_data     = $record->get( 'sent_data' );

		$data = array();
		foreach ( $fields as $field ) {
			$data[$field] = isset( $mapped_fields[$field] ) ? $this->get_value( $sent_data, $mapped_fields, $field ) : '';
		}

		return $data;
	}

	/**
	 * Get the value of a field, based on the mapped field.
	 *
	 * @since 2.19.0
	 * @since 2.22.0 Moved to Elementor_Shared_Utils trait.
	 *
	 * @param array $sent_data The submitted form data.
	 * @param array $mapped_fields Mapping of form fields to their identifiers.
	 * @param string $field The field to retrieve value for.
	 * @param string $default Default value if the field is not set.
	 *
	 * @return string
	 */
	private function get_value( $sent_data, $mapped_fields, $field, $default = '' ) : string {
		if ( isset( $mapped_fields[ $field ] ) && isset( $sent_data[ $mapped_fields[$field] ] ) ) {
			return $sent_data[ $mapped_fields[$field] ];
		}

		return $default;
	}

	/**
	 * Get the mapped fields from a given map key.
	 *
	 * @since 2.19.0
	 * @since 2.22.0 Added the $map_key parameter and moved to Elementor_Shared_Utils trait.
	 *
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param string $map_key The key for the fields map in form settings.
	 *
	 * @return array
	 */
	private function get_fields_map( $record, $map_key ) : array {
		$map = array();

		foreach ( $record->get_form_settings( $map_key ) as $map_item ) {
			if ( empty( $map_item['remote_id'] ) ) {
				continue;
			}
			$map[ $map_item['remote_id'] ] = $map_item['local_id'] ?? '';
		}

		return $map;
	}

	/**
	 * Get the affiliate registration setting from the provided source.
	 *
	 * @since 2.25.0
	 *
	 * @param mixed $source The source object or array (e.g., $record or $instance).
	 *
	 * @return string The value of the affiliate registration setting.
	 */
	private function get_affiliate_registration_setting( $source ) : string {

		if ( is_array( $source ) ) {
			return $source['affiliate_registration'] ?? '';
		}

		if ( is_object( $source ) && method_exists( $source, 'get_form_settings' ) ) {
			return $source->get_form_settings( 'affiliate_registration' ) ?? '';
		}

		return '';
	}

	/**
	 * Check if the form setting indicates an affiliate registration form.
	 *
	 * @since 2.25.0
	 *
	 * @param string $affiliate_registration The value of the affiliate registration setting.
	 *
	 * @return bool True if the form is an affiliate registration form, false otherwise.
	 */
	private function is_affiliate_registration( $affiliate_registration ) : bool {
		return ! empty( $affiliate_registration ) && 'yes' === $affiliate_registration;
	}
}
