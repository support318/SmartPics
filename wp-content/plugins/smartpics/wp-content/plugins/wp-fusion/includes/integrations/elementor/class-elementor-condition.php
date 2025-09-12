<?php

use ElementorPro\Modules\DisplayConditions\Conditions\Base\Condition_Base;
use ElementorPro\Modules\DisplayConditions\Classes\Comparator_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Pro Display Conditions.
 *
 * Adds WP Fusion tag-based conditions to Elementor Pro's display conditions.
 *
 * @since 3.45.5
 */
class WPF_Elementor_Condition extends Condition_Base {

	/**
	* The condition key for WP Fusion tags.
	*
	* @since 3.45.5
	* @var string
	*/
	const CONDITION_KEY = 'wpfusion_tags';

	/**
	 * Get condition name.
	 *
	 * @since 3.45.5
	 *
	 * @return string The condition name.
	 */
	public function get_name() {
		return 'wpfusion_tags';
	}

	/**
	 * Get condition label.
	 *
	 * @since 3.45.5
	 *
	 * @return string The condition label.
	 */
	public function get_label() {
		/* translators: %s: The name of the CRM */
		return sprintf( esc_html__( '%s Tags', 'wp-fusion' ), wp_fusion()->crm->name );
	}

	/**
	 * Get condition group.
	 *
	 * @since 3.45.5
	 *
	 * @return string The condition group.
	 */
	public function get_group() {
		return 'user';
	}

	/**
	 * Check if condition is met.
	 *
	 * @since 3.45.5
	 *
	 * @param array $args The condition arguments.
	 * @return bool Whether the condition is met.
	 */
	public function check( $args ): bool {

		if ( wpf_admin_override() ) {
			return true;
		}

		if ( Comparator_Provider::COMPARATOR_IS_ONE_OF === $args['comparator'] ) {
			$has_access = wpf_has_tag( $args['wpfusion_tags'] );
		} else {
			$has_access = ! wpf_has_tag( $args['wpfusion_tags'] );
		}

		// Run the result through WPF's standard access filters.
		return apply_filters( 'wpf_user_can_access', $has_access, wpf_get_current_user_id(), false );
	}

	/**
	 * Get condition options.
	 *
	 * @since 3.45.5
	 */
	public function get_options() {
		$comparators = Comparator_Provider::get_comparators(
			array(
				Comparator_Provider::COMPARATOR_IS_ONE_OF,
				Comparator_Provider::COMPARATOR_IS_NONE_OF,
			)
		);

		$this->add_control(
			'comparator',
			array(
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $comparators,
				'default' => Comparator_Provider::COMPARATOR_IS_ONE_OF,
			)
		);

		$available_tags = wp_fusion()->settings->get_available_tags_flat();

		$this->add_control(
			self::CONDITION_KEY,
			array(
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $available_tags,
				'multiple'    => true,
				'required'    => true,
				'label_block' => true,
				/* translators: %s: The name of the CRM */
				'description' => sprintf(
					esc_html__( 'Select which %s tags to check for.', 'wp-fusion' ),
					wp_fusion()->crm->name
				),
			)
		);
	}
}
