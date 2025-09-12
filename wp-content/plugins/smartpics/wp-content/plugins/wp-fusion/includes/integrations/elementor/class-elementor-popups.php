<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Popups integration.
 *
 * @since 3.22.2
 */
class WPF_Elementor_Popups {

	/**
	 * Gets things started
	 *
	 * @since 3.22.2
	 */
	public function __construct() {

		if ( ! wpf_get_option( 'restrict_content', true ) ) {
			return;
		}

		// Control styles.
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_styles' ) );

		// Controls.
		add_action( 'elementor/element/popup_timing/timing/before_section_end', array( $this, 'render_controls' ), 10, 2 );

		// Filter template loading.
		add_filter( 'elementor/theme/get_location_templates/template_id', array( $this, 'get_template' ) );
	}

	/**
	 * Enqueue editor styles.
	 *
	 * @since 3.22.2
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'wpf-admin', WPF_DIR_URL . 'assets/css/wpf-admin.css', array(), WP_FUSION_VERSION );
	}


	/**
	 * Render widget controls.
	 *
	 * @since 3.22.2
	 *
	 * @param \Elementor\Element_Base $element The element.
	 * @param array                   $args The arguments.
	 */
	public function render_controls( $element, $args ) {

		$available_tags = wp_fusion()->settings->get_available_tags_flat();

		// Heading.

		$args = array(
			'type'  => \Elementor\Controls_Manager::HEADING,
			'label' => sprintf( __( 'When user has any of the %s tags', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$element->add_control( 'wp_fusion_heading', $args );

		// Condition.

		$args = array(
			'type'               => \Elementor\Controls_Manager::SELECT,
			'options'            => array(
				'show' => __( 'Show', 'elementor-pro' ),
				'hide' => __( 'Hide', 'elementor-pro' ),
			),
			'default'            => 'show',
			'frontend_available' => true,
			'condition'          => array(
				'wp_fusion' => 'yes',
			),
		);

		$element->add_control( 'wp_fusion_condition', $args );

		// Tags select.

		$args = array(
			'type'               => \Elementor\Controls_Manager::SELECT2,
			'multiple'           => true,
			'options'            => $available_tags,
			'frontend_available' => true,
			'condition'          => array(
				'wp_fusion' => 'yes',
			),
		);

		$element->add_control( 'wp_fusion_popup_tags', $args );

		// Switcher.

		$element->add_control(
			'wp_fusion',
			array(
				'type'               => \Elementor\Controls_Manager::SWITCHER,
				'classes'            => 'elementor-popup__display-settings__group-toggle',
				'frontend_available' => true,
			)
		);
	}


	/**
	 * Hide popup if conditions not met.
	 *
	 * @since 3.22.2
	 *
	 * @param int $post_id The post ID.
	 * @return bool|int Post ID
	 */
	public function get_template( $post_id ) {

		$popup_settings = get_post_meta( $post_id, '_elementor_popup_display_settings', true );

		if ( empty( $popup_settings ) || ! isset( $popup_settings['timing'] ) || ! isset( $popup_settings['timing']['wp_fusion'] ) || $popup_settings['timing']['wp_fusion'] != 'yes' ) {
			return $post_id;
		}

		// If no tags set.

		if ( empty( $popup_settings['timing']['wp_fusion_popup_tags'] ) ) {
			return $post_id;
		}

		$widget_tags = wpf_clean_tags( $popup_settings['timing']['wp_fusion_popup_tags'] );

		$can_access = true;

		if ( isset( $popup_settings['timing']['wp_fusion_condition'] ) && 'hide' === $popup_settings['timing']['wp_fusion_condition'] ) {

			if ( wpf_is_user_logged_in() ) {

				if ( wpf_has_tag( $widget_tags ) ) {
					$can_access = false;
				}
			}
		} elseif ( ! isset( $popup_settings['timing']['wp_fusion_condition'] ) || 'show' === $popup_settings['timing']['wp_fusion_condition'] ) {

			$can_access = wpf_has_tag( $widget_tags );

		}

		// Don't check for exclude_admins here otherwise the popup will show on every page.
		$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $post_id );

		if ( $can_access ) {
			return $post_id;
		} else {
			return false;
		}
	}
}
