<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wpcode_Helpers;

/**
 * Class Wpcode_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wpcode_Pro_Helpers {

	/**
	 * @return array
	 */
	public function get_only_on_demand_snippets() {
		$options = array();

		if ( class_exists( 'Uncanny_Automator\Wpcode_Helpers' ) ) {
			$helper   = new Wpcode_Helpers();
			$snippets = $helper->get_wpcode_snippets( array( 'snippet_status' => array( 'publish', 'draft' ) ) );
			foreach ( $snippets['options'] as $snippet_id => $snippet ) {
				$snippet_details = wpcode_get_snippet( $snippet_id );
				if ( 'on_demand' === $snippet_details->get_location() ) {
					$options[] = array(
						'text'  => $snippet,
						'value' => $snippet_id,
					);
				}
			}
		}

		return $options;
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_code_types( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_TYPES',
			'label'                 => esc_attr_x( 'Code type', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$code_types = wpcode()->execute->get_options();

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => true,
			'default_value'         => 'php',
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $code_types,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_statuses( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_STATUSES',
			'label'                 => esc_attr_x( 'Code status', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$code_statuses = array(
			'active'   => 'Active',
			'inactive' => 'Inactive',
		);

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => true,
			'default_value'         => 'active',
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $code_statuses,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_locations( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_LOCATIONS',
			'label'                 => esc_attr_x( 'Location', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args                  = wp_parse_args( $args, $defaults );
		$locations_by_category = wpcode()->auto_insert->get_type_categories();
		$all_locations         = array();
		foreach ( $locations_by_category as $category_key => $category_data ) {
			/**
			 * @var \WPCode_Auto_Insert_Type $type
			 */
			foreach ( $category_data['types'] as $type ) {
				if ( 'pro' === $type->code_type && ! class_exists( 'WPCode_Premium' ) ) {
					continue;
				}
				$label_pill = ! empty( $type->label_pill ) ? ' (' . $type->label_pill . ')' : '';
				$locations  = $type->get_locations();
				foreach ( $locations as $location_slug => $location ) {
					$all_locations[ $location_slug ] = $type->get_label() . ' - ' . $location['label'] . $label_pill;
				}
			}
		}

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => false,
			'default_value'         => null,
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $all_locations,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_insert_methods( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_INSERT_METHOD',
			'label'                 => esc_attr_x( 'Insert Method', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$code_insert_method = array(
			'1' => 'Auto Insert',
			'0' => 'Shortcode',
		);

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => false,
			'options_show_id'       => false,
			'description'           => esc_attr_x( 'Please select the location, in case of auto insert method.', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $code_insert_method,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_device_types( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_DEVICE_TYPES',
			'label'                 => esc_attr_x( 'Device type', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$device_type = array(
			'any' => 'Any device type',
		);

		if ( class_exists( 'WPCode_Premium' ) ) {
			$device_type = array(
				'mobile'  => 'Mobile only',
				'desktop' => 'Desktop only',
			) + $device_type;
		}

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => false,
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $device_type,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_tags( $args = array() ) {
		$defaults = array(
			'option_code'           => 'WPCODE_TAGS',
			'label'                 => esc_attr_x( 'Tag', 'insert-headers-and-footers', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => true,
			'relevant_tokens'       => array(),
		);

		$args = wp_parse_args( $args, $defaults );
		$tags = get_terms(
			array(
				'taxonomy' => 'wpcode_tags',
			)
		);

		$all_tags = array();
		foreach ( $tags as $tag ) {
			$all_tags[ $tag->slug ] = $tag->name;
		}

		$option = array(
			'option_code'              => $args['option_code'],
			'label'                    => $args['label'],
			'input_type'               => 'select',
			'required'                 => false,
			'options_show_id'          => false,
			'relevant_tokens'          => $args['relevant_tokens'],
			'options'                  => $all_tags,
			'supports_multiple_values' => true,
			'supports_custom_value'    => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_code_types', $option );
	}
}
