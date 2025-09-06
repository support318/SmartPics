<?php
/**
 * Holds the Divi integration class.
 *
 * @since 3.38.14
 *
 * @package WP Fusion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Divi integration class.
 *
 * @since 3.38.14
 */
class WPF_Divi extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'divi';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Divi';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/page-builders/divi/';

	/**
	 * Gets things started
	 *
	 * @since 3.17.2
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		if ( ! wpf_get_option( 'restrict_content', true ) ) {
			return;
		}

		add_filter( 'et_builder_get_parent_modules', array( $this, 'add_field' ) );
		add_filter( 'et_pb_module_shortcode_attributes', array( $this, 'shortcode_attributes' ), 10, 5 );
	}

	/**
	 * Register the settings on all Divi modules.
	 *
	 * @since  3.36.5
	 *
	 * @link https://gist.github.com/awah95/2d471f049eb3b7024003109d550eebb2
	 *
	 * @param  array $modules The registered modules.
	 *
	 * @access public
	 * @return array $modules The registered modules.
	 */
	public function add_field( $modules ) {
		// Ensure we run this code only once because it's expensive.
		static $is_applied = false;

		if ( $is_applied ) {
			return $modules;
		}

		// Bail early if the modules list empty.
		if ( empty( $modules ) ) {
			return $modules;
		}

		foreach ( $modules as $module_slug => $module ) {
			// Ensure fields list exist.
			if ( ! isset( $module->fields_unprocessed ) ) {
				continue;
			}

			// Docs: https://docs.diviengine.com/divi-form-builder/category/field-types/.
			$fields_list = $module->fields_unprocessed;

			if ( ! empty( $fields_list ) ) {

				$fields_list['wpf_tag'] = array(
					// translators: The name of the CRM.
					'label'       => sprintf( __( 'Required %s tags (any)', 'wp-fusion' ), wp_fusion()->crm->name ),
					'type'        => 'text',
					'tab_slug'    => 'custom_css',
					'toggle_slug' => 'visibility',
					'description' => __( 'Enter a comma-separated list of tag names or IDs that are required to view this element.', 'wp-fusion' ),
				);

				if ( 'on' === et_builder_dynamic_module_framework() ) {
					$fields_list['wpf_tag']['label'] .= '. ' . __( 'Warning: the Dynamic Module Framework is enabled in the Divi Builder Options. For best results, disable it.', 'wp-fusion' );
				}

				$fields_list['wpf_tags_all'] = array(
					// translators: The name of the CRM.
					'label'       => sprintf( __( 'Required %s tags (all)', 'wp-fusion' ), wp_fusion()->crm->name ),
					'type'        => 'text',
					'tab_slug'    => 'custom_css',
					'toggle_slug' => 'visibility',
					'description' => __( 'Enter a comma-separated list of tag names or IDs that are all required to view this element.', 'wp-fusion' ),
				);

				$fields_list['wpf_tags_not'] = array(
					// translators: The name of the CRM.
					'label'       => sprintf( __( 'Required %s Tags (Not)', 'wp-fusion' ), wp_fusion()->crm->name ),
					'type'        => 'text',
					'tab_slug'    => 'custom_css',
					'toggle_slug' => 'visibility',
					'description' => __( 'Enter a comma-separated list of tag names or IDs. If the user is logged in and has any of these tags, the content will be hidden.', 'wp-fusion' ),
				);

				$modules[ $module_slug ]->fields_unprocessed = $fields_list;
			}
		}

		$is_applied = true;

		return $modules;
	}


	/**
	 * Shortcode attributes
	 *
	 * @since 3.38.14
	 *
	 * @param array  $props The module properties.
	 * @param array  $attrs The module attributes.
	 * @param string $render_slug The module slug.
	 * @param string $_address The module address.
	 * @param string $content The module content.
	 *
	 * @access public
	 * @return  array Shortcode atts
	 */
	public function shortcode_attributes( $props, $attrs, $render_slug, $_address, $content ) {
		if ( ! empty( $attrs['wpf_tag'] ) || ! empty( $attrs['wpf_tags_not'] ) || ! empty( $attrs['wpf_tags_all'] ) ) {
			$can_access = true;

			if ( wpf_admin_override() ) {
				$can_access = true;
			} else {
				// If Required Tags.
				if ( ! empty( $attrs['wpf_tag'] ) ) {
					if ( ! wpf_is_user_logged_in() ) {
						$can_access = false;
					} else {
						$required_tags = explode( ',', $attrs['wpf_tag'] );
						$required_tags = array_map( 'trim', $required_tags );
						if ( ! wpf_has_tag( $required_tags ) ) {
							$can_access = false;
						}
					}
				}

				// If Required NOT Tags.
				if ( ! empty( $attrs['wpf_tags_not'] ) ) {
					$required_tags_not = explode( ',', $attrs['wpf_tags_not'] );
					$required_tags_not = array_map( 'trim', $required_tags_not );

					if ( wpf_has_tag( $required_tags_not ) ) {
						$can_access = false;
					}
				}

				// If Required ALL Tags.
				if ( ! empty( $attrs['wpf_tags_all'] ) ) {
					$required_tags_all = explode( ',', $attrs['wpf_tags_all'] );
					$required_tags_all = array_map( 'trim', $required_tags_all );

					foreach ( $required_tags_all as $tag ) {
						if ( ! wpf_has_tag( $tag ) ) {
							$can_access = false;
						}
					}
				}
			}

			$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), false );
			$can_access = apply_filters( 'wpf_divi_can_access', $can_access, $props );

			if ( false === $can_access ) {
				$props['disabled'] = 'on';
			}
		}

		return $props;
	}
}

new WPF_Divi();
