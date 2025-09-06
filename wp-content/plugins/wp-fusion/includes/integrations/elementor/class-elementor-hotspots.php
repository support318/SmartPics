<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Hotspots integration.
 *
 * @since 3.45.5
 */
class WPF_Elementor_Hotspots {

	/**
	 * Gets things started.
	 *
	 * @since 3.45.5
	 */
	public function __construct() {

		add_action( 'elementor/element/hotspot/hotspot_section/before_section_end', array( $this, 'register_hotspot_section' ), 10, 2 );
		add_filter( 'elementor/widget/render_content', array( $this, 'filter_hotspot_content' ), 10, 2 );
	}

	/**
	 * Register hotspot section.
	 *
	 * @since 3.45.5
	 *
	 * @param \Elementor\Widget_Base $widget The widget instance.
	 * @param array                  $args Section arguments.
	 */
	public function register_hotspot_section( $widget, $args ) {

		$repeater = new \Elementor\Repeater();

		$repeater_control = $widget->get_controls( 'hotspot' );
		$existing_fields  = $repeater_control['fields'];
		$field_names      = array_keys( $existing_fields );
		$field_count      = count( $field_names );

		for ( $i = 0; $i < 5 && $i < $field_count; $i++ ) {
			$field_name = $field_names [ $i ];
			$repeater->add_control( $field_name, $existing_fields[ $field_name ] );
		}

		$active_values = array( 'active', 'default' );
		if ( in_array( get_option( 'elementor_experiment-e_element_cache' ), $active_values, true ) ) {
			$repeater->add_control(
				'wpf-disable-element-caching',
				array(
					'type'            => \Elementor\Controls_Manager::RAW_HTML,
					'raw'             => __( '<strong>Important:</strong> Having Element Caching enabled will prevent WP Fusion from working as expected.', 'wp-fusion' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				)
			);
		}

		$repeater->add_control(
			'wpf-required-tags',
			array(
				// translators: %s is the CRM name.
				'label'       => esc_html( sprintf( __( 'Required %s Tags (Any)', 'wp-fusion' ), wp_fusion()->crm->name ) ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'default'     => array(),
				'options'     => wp_fusion()->settings->get_available_tags_flat(),
				'label_block' => true,
			)
		);

		for ( $i = 2; $i < $field_count; $i++ ) {
			$field_name = $field_names[ $i ];
			$repeater->add_control( $field_name, $existing_fields[ $field_name ] );
		}

		$widget->update_control(
			'hotspot',
			array(
				'fields' => $repeater->get_controls(),
			)
		);
	}

	/**
	 * Filter hotspot content.
	 *
	 * @since 3.45.5
	 *
	 * @param string                 $widget_content The widget content.
	 * @param \Elementor\Widget_Base $widget The widget instance.
	 *
	 * @return string The filtered widget content.
	 */
	public function filter_hotspot_content( $widget_content, $widget ) {

		if ( wpf_admin_override() ) {
			return $widget_content;
		}

		$widget_name = $widget->get_name();

		if ( 'hotspot' === $widget_name ) {
			$settings = $widget->get_settings_for_display();

			foreach ( $settings['hotspot'] as $hotspot ) {
				$required_tags = isset( $hotspot['wpf-required-tags'] ) ? $hotspot['wpf-required-tags'] : array();

				if ( empty( $required_tags ) ) {
					continue;
				}

				if ( ! wpf_has_tag( $required_tags ) ) {
					$widget_content = preg_replace(
						'/<[^>]*?elementor-repeater-item-' . $hotspot['_id'] . '[^>]*>.*?<\/[^>]*>/s',
						'',
						$widget_content
					);
				}
			}
		}

		return $widget_content;
	}
}
