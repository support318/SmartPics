<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

class Metabox_Helpers_Pro {

	public function __construct( $run_hooks = true ) {

		if ( $run_hooks ) {

			add_action( 'wp_ajax_automator_metabox_get_fields', array( $this, 'endpoint_get_metabox_fields' ) );

			add_action( 'wp_ajax_automator_metabox_get_post_types', array( $this, 'endpoint_get_metabox_post_types' ) );

		}

	}

	public function get_field_options( $code ) {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$code => array(
						Automator()->helpers->recipe->wp->options->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'POST_TYPE',
							array(
								'token'        => false,
								'is_any'       => false,
								'is_ajax'      => true,
								'target_field' => 'POST_ID',
								'endpoint'     => 'automator_metabox_get_post_types',
							)
						),
						array(
							'option_code'     => 'POST_ID',
							'label'           => __( 'Post', 'uncanny-automator-pro' ),
							'input_type'      => 'select',
							'required'        => true,
							'is_ajax'         => true,
							'endpoint'        => 'automator_metabox_get_fields',
							'fill_values_in'  => $code,
							'options'         => array(),
							'relevant_tokens' => array(),
						),
						array(
							'option_code'           => $code,
							'label'                 => __( 'Meta Box field', 'uncanny-automator-pro' ),
							'input_type'            => 'select',
							'supports_custom_value' => false,
							'required'              => true,
							'options'               => array(),
							'relevant_tokens'       => array(),
						),
					),
				),
			)
		);

	}

	/**
	 * Get all Metabox user profile fields.
	 *
	 * @param string $code The metabox field.
	 *
	 * @return array The metabox field.
	 */
	public function get_user_field_options( $code ) {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$code => array(
						array(
							'option_code'           => $code,
							'label'                 => __( 'Meta Box field', 'uncanny-automator-pro' ),
							'input_type'            => 'select',
							'supports_custom_value' => false,
							'required'              => true,
							'options'               => $this->get_metabox_user_fields(),
							'relevant_tokens'       => array(),
						),
					),
				),
			)
		);

	}

	/**
	 * A wp_ajax callback function to render metabox post fields.
	 *
	 * Shows JSON encoded response.
	 *
	 * @return void
	 */
	public function endpoint_get_metabox_fields() {

		Automator()->utilities->ajax_auth_check();

		$post_id = automator_filter_input( 'value', INPUT_POST );

		// For `Any single post`, use the post type value.
		if ( '-1' === $post_id ) {

			$trigger_options = automator_filter_input_array( 'values', INPUT_POST );

			if ( ! empty( $trigger_options ) && ! empty( $trigger_options['POST_TYPE'] ) ) {
				// Send back all fields from custom post type.
				wp_send_json( $this->get_metabox_fields( $trigger_options['POST_TYPE'] ) );
			}
		}

		wp_send_json( $this->get_metabox_fields( $post_id ) );

	}

	/**
	 * A wp_ajax callback function to return all Post types.
	 *
	 * Shows JSON encoded response.
	 *
	 * @return void
	 */
	public function endpoint_get_metabox_post_types() {

		Automator()->utilities->ajax_auth_check();

		$fields = array();

		$post_type = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

		$args = array(
			'posts_per_page'   => 999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'suppress_filters' => true,
			'fields'           => array( 'ids', 'titles' ),
		);

		$posts_list = Automator()->helpers->recipe->options->wp_query( $args, false, __( 'Any Post', 'uncanny-automator-pro' ) );

		$selected_post_type = get_post_type_object( $post_type );

		$label = '(no title)';

		if ( ! empty( $selected_post_type ) ) {
			$label = ! empty( $selected_post_type->labels->singular_name ) ? esc_html( $selected_post_type->labels->singular_name ) : '';
		}

		$fields[] = array(
			'value' => '-1',
			/* Translators: Field option */
			'text'  => sprintf( __( 'Any %s', 'uncanny-automator-pro' ), strtolower( $label ) ),
		);

		if ( ! empty( $posts_list ) ) {

			foreach ( $posts_list as $post_id => $post_title ) {

				/* Translators: Field option */
				$post_title = ! empty( $post_title ) ? $post_title : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $post_id );

				$fields[] = array(
					'value' => $post_id,
					'text'  => $post_title,
				);
			}
		}

		wp_send_json( $fields );

	}

	/**
	 * Get all Metabox fields by Post ID.
	 *
	 * @param integer $post_id The object ID.
	 *
	 * @return array The available Metabox fields for the specific Post filtered by Post ID.
	 */
	public function get_metabox_fields( $post_id = 0 ) {

		if ( ! function_exists( 'rwmb_get_object_fields' ) ) {
			return array();
		}

		$fields = array(
			array(
				'value' => '-1',
				'text'  => esc_attr__( 'Any field', 'uncanny-automator-pro' ),
			),
		);

		$metabox_fields = (array) rwmb_get_object_fields( $post_id );

		foreach ( $metabox_fields as $metabox_field ) {

			if ( ! empty( $metabox_field['id'] ) && ! empty( $metabox_field['name'] ) ) {

				$fields[] = array(
					'value' => $metabox_field['id'],
					'text'  => $metabox_field['name'],
				);

			}
		}

		return $fields;

	}

	/**
	 * Return all available Metabox user fields.
	 *
	 * @return array The collection of user fields.
	 */
	public function get_metabox_user_fields() {

		$fields = array();

		// Any user field option.
		$fields['-1'] = esc_attr__( 'Any field', 'uncanny-automator-pro' );

		$metabox_fields = (array) rwmb_get_object_fields( null, 'user' );

		foreach ( $metabox_fields as $metabox_field ) {

			if ( ! empty( $metabox_field['id'] ) && ! empty( $metabox_field['name'] ) ) {

				$fields[ $metabox_field['id'] ] = $metabox_field['name'];

			}
		}

		return $fields;

	}

	/**
	 * Validates the trigger.
	 *
	 * @param array $args The args from Trait method validate_trigger.
	 * @param string $type The type of Metabox field to validate against.
	 *
	 * @return boolean True if okay. Otherwise, false.
	 */
	public function validate_trigger( $args = array(), $type = 'post' ) {

		if ( ! function_exists( 'rwmb_get_object_fields' ) ) {
			return false;
		}

		if ( empty( $args ) ) {
			return false;
		}

		list( $meta_id, $object_id, $meta_key, $_meta_value ) = $args;

		if ( 'user' === $type ) {
			// Run validation for user type.
			return in_array( $meta_key, array_keys( rwmb_get_object_fields( null, 'user' ) ), true );
		}

		// Make sure its a Metabox field.
		return in_array( $meta_key, array_keys( rwmb_get_object_fields( $object_id ) ), true );

	}

}
