<?php
/**
 * WP Fusion - Elementor Forms Field Mapping.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.41.24
 */

use Elementor\Controls_Manager;
use Elementor\Control_Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Form field mapping control for Elementor Forms.
 *
 * @since 3.41.24
 */
class WPF_Elementor_Field_Mapping extends Control_Repeater {

	/**
	 * The control type.
	 *
	 * @since 3.41.24
	 * @var string
	 */
	const CONTROL_TYPE = 'wpf_fields_map';

	/**
	 * Get control type.
	 *
	 * Retrieve the control type, in this case `wpf_fields_map`.
	 *
	 * @since 3.41.24
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return self::CONTROL_TYPE;
	}

	/**
	 * Gets the default settings.
	 *
	 * @since 3.41.24
	 *
	 * @return array The default settings.
	 */
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(),
			array(
				'render_type' => 'none',
				'fields'      => array(
					array(
						'name' => 'local_id',
						'type' => Controls_Manager::HIDDEN,
					),
					array(
						'name' => 'remote_id',
						'type' => Controls_Manager::SELECT,
					),
				),
			)
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @since 3.41.24
	 */
	public function enqueue() {
		wp_enqueue_script( 'wpf-elementor-forms-script', WPF_DIR_URL . 'assets/js/wpf-elementor-forms.js', array( 'jquery' ), WP_FUSION_VERSION, true );

		wp_localize_script(
			'wpf-elementor-forms-script',
			'wpfElementorObject',
			array(
				'fields' => ( new WPF_Elementor_Forms_Integration() )->get_fields(),
			)
		);
	}
}
