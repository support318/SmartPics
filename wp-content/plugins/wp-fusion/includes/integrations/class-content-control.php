<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Content Control integration class
 *
 * @since 3.43.7
 */

class WPF_Content_Control extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.43.7
	 * @var string $slug
	 */

	public $slug = 'content-control';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.43.7
	 * @var string $name
	 */
	public $name = 'Content Control';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.43.7
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/content-control/';

	/**
	 * Get things started.
	 *
	 * @since 3.43.7
	 */
	public function init() {
		add_action( 'content_control/rule_engine/register_rules', array( $this, 'add_rule' ) );
	}

	/**
	 * Add WP Fusion rule.
	 *
	 * @since 3.43.7
	 *
	 * @param array $rules The rules.
	 */
	public function add_rule( $rules ) {

		$verbs = $rules->get_verbs();

		$rule = array(
			'name'     => 'wpf_tags',
			'label'    => sprintf( __( '%s Tags (Any)', 'wp-fusion' ), wp_fusion()->crm->name ),
			'context'  => array( 'user' ),
			'category' => __( 'User', 'content-control' ),
			'format'   => '{category} {verb} {label}',
			'verbs'    => array( $verbs['has'], $verbs['doesnothave'] ),
			'fields'   => array(
				'wpf_tags' => array(
					'label'    => __( 'Tag(s)', 'content-control' ),
					'type'     => 'tokenselect',
					'multiple' => true,
					'options'  => wp_fusion()->settings->get_available_tags_flat(),
				),
			),
			'callback' => array( $this, 'can_access' ),
		);

		$rules->register_rule( $rule );
	}

	/**
	 * User can access content if he has or not any tags.
	 *
	 * @since 3.43.7
	 *
	 * @return bool True if user can access content.
	 */
	public function can_access() {

		$rule = ContentControl\Rules\current_rule();
		$tags = ContentControl\Rules\get_rule_option( 'wpf_tags', array() );

		$can_access = wpf_has_tag( $tags );

		if ( $rule->not_operand ) {
			$can_access = ! $can_access;
		}

		return apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $can_access );
	}
}

new WPF_Content_Control();
