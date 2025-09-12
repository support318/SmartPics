<?php
/**
 * Field Mapping Control for Referral Tracking
 *
 * @package    AffiliateWP
 * @subpackage Integrations
 * @copyright  Copyright (c) 2024, Sandhills Development, LLC
 * @since      2.22.0
 */

use Elementor\Control_Repeater;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Custom control for mapping referral fields within Elementor forms.
 *
 * Extends Elementor's Control_Repeater to provide a specialized control
 * that allows users to map form fields to specific referral data points
 * in AffiliateWP.
 *
 * @since 2.22.0
 */
class Referral_Field_Mapping extends Control_Repeater {

	/**
	 * Defines the control type identifier for the referral fields mapping control.
	 *
	 * @since 2.22.0
	 *
	 * @var string
	 */
	const CONTROL_TYPE = 'affiliatewp_referral_fields_map';

	/**
	 * Get the type of the control.
	 *
	 * Overrides the parent method to return a custom control type for the field mapping.
	 *
	 * @since 2.22.0
	 * @return string The control type.
	 */
	public function get_type() : string {
		return self::CONTROL_TYPE;
	}

	/**
	 * Get the default settings for the control.
	 *
	 * Overrides the parent method to provide default settings specific to the field mapping control.
	 *
	 * @since 2.22.0
	 * @return array The default settings array.
	 */
	protected function get_default_settings() : array {
		return array_merge( parent::get_default_settings(), array(
			'render_type' => 'none',
			'fields'      => array(
				array(
					'name' => 'remote_id',
					'type' => Controls_Manager::HIDDEN,
				),
				array(
					'name' => 'local_id',
					'type' => Controls_Manager::SELECT,
				),
			),
		 ) );
	}

	/**
	 * Retrieve mappable fields for the control.
	 *
	 * @since 2.22.0
	 * @return array An array of mappable fields.
	 */
	private static function get_mappable_fields() : array {
		return array(
			'email'      => esc_html__( 'Email', 'affiliate-wp' ),
			'first_name' => esc_html__( 'First Name', 'affiliate-wp' ),
			'last_name'  => esc_html__( 'Last Name', 'affiliate-wp' ),
		);
	}

	/**
	 * Enqueue necessary scripts for the control.
	 *
	 * @since 2.22.0
	 */
	public function enqueue() : void {

		$fields = $this->get_mappable_fields();

		wp_localize_script(
			'affwp-elementor',
			'AffiliateWPReferralFields',
			array(
				'fields' => array_map(
					function( $field_id, $field_label ) {

						return array(
							'remote_id'       => $field_id,
							'remote_label'    => $field_label,
							'remote_type'     => $field_id === 'email' ? 'email' : 'text',
							'remote_required' => $field_id === 'email',
						);
					},
					array_keys( $fields ), // Passes $field_id.
					array_values( $fields ) // Passes $field_label.
				),
			)
		);

	}

}
