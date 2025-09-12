<?php
namespace Uncanny_Automator_Pro;

class JetEngine_Helpers {

	public function __construct( $hooks_loaded = true ) {
		if ( $hooks_loaded ) {
			add_action( 'wp_ajax_automator_jetengine_get_post_field_by_post_type', array( $this, 'get_post_field_by_post_type' ) );
		}
	}

	public function get_post_field_by_post_type() {

		Automator()->utilities->ajax_auth_check();

		$post_type = automator_filter_input( 'value', INPUT_POST );

		$post_fields = $this->get_metabox_fields( false );

		$options_dropdown = array(
			array(
				'text'  => esc_html__( 'Any field', 'uncanny-automator' ),
				'value' => -1,
			),
		);

		foreach ( $post_fields as $post_field ) {
			if ( in_array( $post_type, $post_field['args']['allowed_post_type'], true ) ) {
				foreach ( $post_field['meta_fields']  as $meta_field ) {
					$options_dropdown[] = array(
						'text'  => $meta_field['title'],
						'value' => $meta_field['name'],
					);
				}
			}
		}

		$cpt_fields = $this->get_cpt_fields( $post_type );

		if ( ! empty( $cpt_fields ) ) {
			foreach ( $cpt_fields as $cpt_field ) {
				$options_dropdown[] = $cpt_field;
			}
		}

		// Count = 1 means we only have 'Any' field.
		if ( 1 === count( $options_dropdown ) ) {
			wp_send_json( array() );
		}

		wp_send_json( $options_dropdown );

	}

	/**
	 * Retrieving specific JetEngine Field by id.
	 */
	public function get_post_field( $field_id = '' ) {

		$post_fields = $this->get_metabox_fields();

		if ( empty( $post_fields ) && ! is_array( $post_fields ) ) {
			return array();
		}

		return array_filter(
			// Type casted as array to avoid PHP 8 issue.
			(array) $this->get_metabox_fields(),
			function( $post_field ) use ( $field_id ) {
				return $field_id === $post_field['name'];
			}
		);

	}

	/**
	 * Retrieving all JetEngine's metabox for post object.
	 *
	 * @param boolean $meta_fields_only Determines whether to show only meta fields or whole object.
	 *
	 * @return array The JetEngine's metaboxes for post type.
	 */
	public function get_metabox_fields( $meta_fields_only = true ) {

		$metaboxes = (array) get_option( 'jet_engine_meta_boxes', array() );

		$post_fields = array_filter(
			$metaboxes,
			function( $metabox ) {
				return 'post' === $metabox['args']['object_type'];
			}
		);

		if ( $meta_fields_only ) {
			$post_fields = array_column( $post_fields, 'meta_fields' );
			return end( $post_fields );
		}

		return $post_fields;

	}

	/**
	 * Retrieves a specific post type meta fields.
	 *
	 * @param string $post_type Specific post type slug or name to fetch the fields from.
	 * @param bool $meta_fields_only Pass true to output a list of meta fields name. Used in validation logic.
	 *
	 * @return array The list of meta fields
	 */
	public function get_cpt_fields( $post_type = '', $meta_fields_only = false ) {

		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT meta_fields FROM {$wpdb->prefix}jet_post_types
				WHERE slug = %s
				",
				$post_type
			)
		);

		$cpt_fields   = null !== $result ? maybe_unserialize( $result ) : array();
		$ready_fields = array();

		if ( empty( $cpt_fields ) ) {
			return array();
		}

		foreach ( $cpt_fields as $field ) {

			$ready_fields[] = array(
				'text'  => $field['title'],
				'value' => $field['name'],
			);

		}

		if ( $meta_fields_only ) {
			return array_column( $ready_fields, 'value' );
		}

		return $ready_fields;

	}


	/**
	 * Retrieve a specific cpt field by object and and meta key.
	 *
	 * @param int $object_id The object ID.
	 * @param string $meta_key The meta field name.
	 *
	 * @return array The field.
	 */
	public function is_cpt_field( $object_id = 0, $meta_key = '' ) {

		$cpt_ready_fields = $this->get_cpt_fields( get_post_type( $object_id ), true );

		return in_array( $meta_key, $cpt_ready_fields, true );

	}

}
