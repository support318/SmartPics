<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_POST_HAS_TAXONOMY_TERM
 *
 * @package Uncanny_Automator_Pro
 */
class WP_POST_HAS_TAXONOMY_TERM extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'WP';
		$this->name        = esc_html__( '{{A post}} {{has}} {{a taxonomy term}}', 'uncanny-automator-pro' );
		$this->code        = 'POST_HAS_OR_NOT_HAVE_TAXONOMY_TERM';
		/* translators: the meta key */
		$this->dynamic_name  = sprintf( esc_html__( '{{A post:%1$s}} {{has:%2$s}} {{a taxonomy term:%3$s}}', 'uncanny-automator-pro' ), 'WPPOST', 'RECIPE_CONDITION', 'WP_TAXONOMY_TERM' );
		$this->is_pro        = true;
		$this->requires_user = false;
		$this->deprecated    = false;
	}

	/**
	 * @return array
	 */
	public function fields() {
		return array(
			$this->field->text(
				array(
					'option_code' => 'WPPOST',
					'label'       => esc_html__( 'Post ID', 'uncanny-automator-pro' ),
					'required'    => true,
				)
			),
			$this->field->select_field_args(
				array(
					'option_code'            => 'RECIPE_CONDITION',
					'label'                  => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => array(
						array(
							'value' => 'has',
							'text'  => __( 'has', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'does_not_have',
							'text'  => __( 'does not have', 'uncanny-automator-pro' ),
						),
					),
					'supports_custom_value'  => false,
					'options_show_id'        => false,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'WP_TAXONOMY_TERM',
					'label'       => esc_html__( 'Taxonomy term', 'uncanny-automator-pro' ),
					'required'    => true,
					'description' => __( 'Accepts term ID, slug or name', 'uncanny-automator-pro' ),
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$condition  = $this->get_parsed_option( 'RECIPE_CONDITION' );
		$post_id    = $this->get_parsed_option( 'WPPOST' );
		$term       = $this->get_parsed_option( 'WP_TAXONOMY_TERM' );
		$results    = $this->check_if_post_has_term( $post_id, $term );
		$validation = (bool) $results['result'];
		$message    = $results['message'];

		// Have a message and failed conditions match.
		if ( false === $validation && ! empty( $message ) && 'has' === $condition ) {
			$this->condition_failed( $message );

			return;
		}

		// Generate a message if the condition is not met.
		$term_name = isset( $results['term_name'] ) ? $results['term_name'] : $term;
		$post_name = isset( $results['post_name'] ) ? $results['post_name'] : $post_id;
		switch ( $condition ) {
			case 'has':
				if ( false === $validation ) {
					/* translators: Post ID and Term */
					$message = sprintf( __( 'The Post: %1$s does not have taxonomy term: %2$s', 'uncanny-automator-pro' ), $post_name, $term_name );
					$this->condition_failed( $message );
				}
				break;
			case 'does_not_have':
				if ( false !== $validation ) {
					/* translators: Post ID and Term */
					$message = sprintf( __( 'The Post: %1$s has taxonomy term: %2$s', 'uncanny-automator-pro' ), $post_name, $term_name );
					$this->condition_failed( $message );
				}

				break;
		}
	}

	/**
	 * @param $post_id
	 * @param $term
	 *
	 * @return array
	 */
	private function check_if_post_has_term( $post_id, $term ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return array(
				'result'  => false,
				/* translators: Post ID */
				'message' => sprintf( __( 'The post ID (%s) provided is not a valid post.', 'uncanny-automator-pro' ), $post_id ),
			);
		}
		$term_name = '';
		$objects   = get_object_taxonomies( $post );
		if ( empty( $objects ) ) {
			return array(
				'result'  => false,
				/* translators: Post title */
				'message' => sprintf( __( 'The post (%s) does not contain any taxonomies.', 'uncanny-automator-pro' ), $post->post_title ),
			);
		}
		foreach ( $objects as $taxonomy ) {
			$term_object = $this->get_term( $term, $taxonomy );
			if ( empty( $term_object ) ) {
				continue;
			}
			$term_name = $term_object->name;
			if ( has_term( $term, $taxonomy, $post ) ) {
				return array(
					'result'    => true,
					'post_name' => $post->post_title,
					'term_name' => $term_name,
					'message'   => '',
				);
			}
			break;
		}

		if ( empty( $term_name ) ) {
			return array(
				'result'  => false,
				/* translators: Post title, Taxonomy term */
				'message' => sprintf( __( 'The post (%1$s) does not contain the specific taxonomy term (%2$s).', 'uncanny-automator-pro' ), $post->post_title, $term ),
			);
		}

		return array(
			'result'    => false,
			'post_name' => isset( $post->post_title ) ? $post->post_title : $post_id,
			'term_name' => $term_name,
			'message'   => '',
		);
	}

	/**
	 * @param $term
	 * @param $taxonomy
	 *
	 * @return array|false|\WP_Error|\WP_Term|null
	 */
	private function get_term( $term, $taxonomy ) {
		if ( is_numeric( $term ) ) {
			return get_term_by( 'term_id', $term, $taxonomy );
		}
		if ( strpos( $term, '-' ) ) {
			return get_term_by( 'slug', $term, $taxonomy );
		}

		return get_term_by( 'name', $term, $taxonomy );
	}

}
