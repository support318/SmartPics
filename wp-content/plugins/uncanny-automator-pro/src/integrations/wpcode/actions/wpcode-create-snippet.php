<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WPCODE_CREATE_SNIPPET
 *
 * @package Uncanny_Automator_Pro
 */
class WPCODE_CREATE_SNIPPET {

	use Recipe\Actions;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
		$this->set_helpers( new Wpcode_Pro_Helpers() );
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'WPCODE_IHAF' );
		$this->set_action_code( 'IHAF_CREATE_CODE_SNIPPET' );
		$this->set_action_meta( 'IHAP_CODE_SNIPPET' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - WPCode Snippet */
		$this->set_sentence( sprintf( esc_attr__( 'Create {{a code snippet:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WPCode Snippet */
		$this->set_readable_sentence( esc_attr__( 'Create a code snippet', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		// Define the default fields
		$default_fields = array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => $this->get_action_meta(),
					/* translators: Snippet Title */
					'label'       => esc_attr_x( 'Name', 'WPCode', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
				)
			),
			$this->get_helpers()->get_all_code_types(),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'      => 'WPCODE_CODE',
					/* translators: Code */
					'label'            => esc_attr_x( 'Code', 'WPCode', 'uncanny-automator-pro' ),
					'input_type'       => 'textarea',
					'supports_tinymce' => false,
				)
			),
			$this->get_helpers()->get_all_statuses(),
			$this->get_helpers()->get_all_insert_methods(),
			$this->get_helpers()->get_all_locations(),
			$this->get_helpers()->get_all_device_types(),
			array(
				'input_type'        => 'repeater',
				'relevant_tokens'   => array(),
				'option_code'       => 'WPCODE_SHORTCODE_ATTRIBUTES',
				'label'             => esc_attr__( 'Shortcode attributes', 'uncanny-automator-pro' ),
				'required'          => false,
				'fields'            => array(
					array(
						'input_type'      => 'text',
						'option_code'     => 'WPCODE_ATTRIBUTE_NAME',
						'label'           => esc_attr__( 'Attribute name', 'uncanny-automator-pro' ),
						'supports_tokens' => true,
						'required'        => false,
					),
				),
				'add_row_button'    => esc_attr__( 'Add attribute', 'uncanny-automator-pro' ),
				'remove_row_button' => esc_attr__( 'Remove attribute', 'uncanny-automator-pro' ),
			),
			$this->get_helpers()->get_all_tags(),
			Automator()->helpers->recipe->field->int(
				array(
					'option_code' => 'WPCODE_PRIORITY',
					'required'    => false,
					'default'     => 10,
					/* translators: Priority */
					'label'       => esc_attr_x( 'Priority', 'WPCode', 'uncanny-automator-pro' ),
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => 'WPCODE_NOTES',
					'required'    => false,
					/* translators: Notes */
					'label'       => esc_attr_x( 'Notes', 'WPCode', 'uncanny-automator-pro' ),
				)
			),
		);

		// Define the fields that are used only if they have WPCode Premium
		$premium_only_fields = array();

		if ( class_exists( 'WPCode_Premium' ) ) {
			$premium_only_fields = array(
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'WPCODE_SHORTCODE_NAME',
						'label'       => esc_attr_x( 'Custom Shortcode', 'WPCode', 'uncanny-automator-pro' ),
						'placeholder' => esc_attr_x( 'Custom Shortcode name', 'WPCode', 'uncanny-automator-pro' ),
						'description' => esc_attr_x( 'Use this field to define a custom shortcode name instead of the id-based one.', 'WPCode', 'uncanny-automator-pro' ),
						'required'    => false,
					)
				),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'WPCODE_START_DATE',
						'label'       => esc_attr_x( 'Start date', 'WPCode', 'uncanny-automator-pro' ),
						'input_type'  => 'date',
						'required'    => false,
					)
				),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'WPCODE_END_DATE',
						'label'       => esc_attr_x( 'End date', 'WPCode', 'uncanny-automator-pro' ),
						'input_type'  => 'date',
						'required'    => false,
					)
				),
			);
		}

		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array_merge(
					$default_fields,
					// Add the premium only fields
					$premium_only_fields
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$snippet_data                         = array();
		$snippet_data['title']                = isset( $parsed['IHAP_CODE_SNIPPET'] ) ? sanitize_text_field( $parsed['IHAP_CODE_SNIPPET'] ) : '';
		$snippet_data['code']                 = isset( $parsed['WPCODE_CODE'] ) ? sanitize_text_field( $parsed['WPCODE_CODE'] ) : '';
		$snippet_data['code_type']            = isset( $parsed['WPCODE_TYPES'] ) ? sanitize_text_field( $parsed['WPCODE_TYPES'] ) : 'html';
		$snippet_data['tags']                 = isset( $parsed['WPCODE_TAGS'] ) ? json_decode( sanitize_text_field( $parsed['WPCODE_TAGS'] ) ) : '';
		$snippet_data['location']             = isset( $parsed['WPCODE_LOCATIONS'] ) ? sanitize_text_field( $parsed['WPCODE_LOCATIONS'] ) : '';
		$snippet_data['auto_insert']          = isset( $parsed['WPCODE_INSERT_METHOD'] ) ? sanitize_text_field( $parsed['WPCODE_INSERT_METHOD'] ) : '';
		$snippet_data['status']               = isset( $parsed['WPCODE_STATUSES'] ) ? sanitize_text_field( $parsed['WPCODE_STATUSES'] ) : '';
		$snippet_data['notes']                = isset( $parsed['WPCODE_NOTES'] ) ? sanitize_text_field( $parsed['WPCODE_NOTES'] ) : '';
		$snippet_data['start_date']           = isset( $parsed['WPCODE_START_DATE'] ) ? sanitize_text_field( $parsed['WPCODE_START_DATE'] ) : '';
		$snippet_data['end_date']             = isset( $parsed['WPCODE_END_DATE'] ) ? sanitize_text_field( $parsed['WPCODE_END_DATE'] ) : '';
		$snippet_data['priority']             = isset( $parsed['WPCODE_PRIORITY'] ) ? absint( sanitize_text_field( $parsed['WPCODE_PRIORITY'] ) ) : 10;
		$snippet_data['device_type']          = isset( $parsed['WPCODE_DEVICE_TYPES'] ) ? sanitize_text_field( $parsed['WPCODE_DEVICE_TYPES'] ) : 'any';
		$snippet_data['shortcode_name']       = isset( $parsed['WPCODE_SHORTCODE_NAME'] ) ? sanitize_text_field( $parsed['WPCODE_SHORTCODE_NAME'] ) : '';
		$snippet_data['shortcode_attributes'] = isset( $parsed['WPCODE_SHORTCODE_ATTRIBUTES'] ) ? json_decode( sanitize_text_field( $parsed['WPCODE_SHORTCODE_ATTRIBUTES'] ), true ) : '';

		if ( 'text' === $snippet_data['code_type'] ) {
			$snippet_data['code'] = wpautop( $snippet_data['code'] );
		}

		$snippet_data['status'] = 'active' === $snippet_data['status'] ? true : false;

		if ( 'php' === $snippet_data['code_type'] ) {
			$snippet_data['code'] = preg_replace( '|^\s*<\?(php)?|', '', $snippet_data['code'] );
		}

		if ( 'js' === $snippet_data['code_type'] && apply_filters( 'wpcode_strip_script_tags_for_js', true ) ) {
			$snippet_data['code'] = preg_replace( '|^\s*<script[^>]*>|', '', $snippet_data['code'] );
			$snippet_data['code'] = preg_replace( '|</(script)>\s*$|', '', $snippet_data['code'] );
		}

		$attributes = array();
		if ( isset( $snippet_data['shortcode_attributes'] ) ) {
			foreach ( $snippet_data['shortcode_attributes'] as $attribute ) {
				$attributes[] = $attribute['WPCODE_ATTRIBUTE_NAME'];
			}
		}

		$snippet = new \WPCode_Snippet(
			array(
				'title'                => $snippet_data['title'],
				'code'                 => $snippet_data['code'],
				'active'               => $snippet_data['status'],
				'code_type'            => $snippet_data['code_type'],
				'location'             => $snippet_data['location'],
				'auto_insert'          => absint( $snippet_data['auto_insert'] ),
				'tags'                 => $snippet_data['tags'],
				'priority'             => $snippet_data['priority'],
				'device_type'          => $snippet_data['device_type'],
				'note'                 => wp_unslash( $snippet_data['notes'] ),
				'custom_shortcode'     => str_replace( '-', '_', $snippet_data['shortcode_name'] ),
				'shortcode_attributes' => $attributes,
				'schedule'             => array(
					'start' => $snippet_data['start_date'],
					'end'   => $snippet_data['end_date'],
				),
			)
		);

		$snippet_id = $snippet->save();

		if ( false === $snippet_id ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html__( 'We are unable to save snippet.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
