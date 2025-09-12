<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use WP_Taxonomy;
use WP_Term;

/**
 * Class WP_POST_HAS_OR_HAS_NOT_TERM_TAXONOMY
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
final class WP_POST_HAS_OR_HAS_NOT_TERM_TAXONOMY extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->register_hooks();
		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_HAS_OR_HAS_NOT_TERM_TAXONOMY' );
		$this->set_sentence( esc_html_x( '{{A type of post}} {{has/does not have}} {{a term}} in {{a taxonomy}}', 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Loop filter sentence */
				esc_html_x( '{{A type of post:%4$s}} {{has/does not have:%1$s}} {{a term:%2$s}} in {{a taxonomy:%3$s}}', 'WordPress', 'uncanny-automator-pro' ),
				'CONDITION',
				'TERM',
				'TAXONOMY',
				$this->get_meta()
			)
		);
		$this->set_loop_type( 'posts' );
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_posts' ) );

	}

	/**
	 * Loads the fields.
	 *
	 * @return mixed[]
	 */
	public function load_options() {

		$post_type = array(
			'option_code'     => $this->get_meta(),
			'type'            => 'select',
			'label'           => esc_html_x( 'Post type', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => array(),
			'ajax'            => array(
				'endpoint' => 'retrieve_post_types',
				'event'    => 'on_load',
			),
			'options_show_id' => false,
		);

		$condition = array(
			'option_code'     => 'CONDITION',
			'type'            => 'select',
			'label'           => esc_html_x( 'Condition', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => array(
				array(
					'text'  => 'has',
					'value' => 'has',
				),
				array(
					'text'  => 'does not have',
					'value' => 'does-not-have',
				),
			),
			'options_show_id' => false,
		);

		$tax = array(
			'option_code'     => 'TAXONOMY',
			'type'            => 'select',
			'label'           => esc_html_x( 'Taxonomy', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => array(),
			'ajax'            => array(
				'endpoint'      => 'retrieve_taxonomies',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( $this->get_meta() ),
			),
			'options_show_id' => true,
		);

		$term = array(
			'option_code'     => 'TERM',
			'type'            => 'select',
			'label'           => esc_html_x( 'Term', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => array(),
			'ajax'            => array(
				'endpoint'      => 'retrieve_terms',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'TAXONOMY' ),
			),
			'options_show_id' => false,
		);

		return array(
			$this->get_meta() => array(
				$post_type,
				$condition,
				$tax,
				$term,
			),
		);

	}


	/**
	 * @param string[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_posts( $fields ) {

		// Bail if field is empty.
		if ( empty( $fields[ $this->get_meta() ] ) ) {
			return array();
		}

		$post_type = is_string( $fields[ $this->get_meta() ] ) ? $fields[ $this->get_meta() ] : '';

		if ( ! post_type_exists( $post_type ) ) {
			throw new \Exception( 'Invalid post type selected', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$operator = 'does-not-have' === $fields['CONDITION'] ? 'NOT IN' : 'IN';
		$taxonomy = $fields['TAXONOMY'];
		$term     = $fields['TERM'];

		$posts = get_posts(
			array(
				'cache_results' => false,
				'post_type'     => (string) $post_type,
				'fields'        => 'ids', // We're only interested with IDs.
				'numberposts'   => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'post_status'   => 'any',
				'tax_query'     => array(
					array(
						'taxonomy' => $taxonomy,
						'operator' => $operator,
						'field'    => 'slug',
						'terms'    => $term,
					),
				),
			)
		);

		return (array) $posts;

	}

	/**
	 * @return void
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_retrieve_post_types', array( $this, 'retrieve_post_types_handler' ) );
		add_action( 'wp_ajax_retrieve_taxonomies', array( $this, 'retrieve_taxonomies_handler' ) );
		add_action( 'wp_ajax_retrieve_terms', array( $this, 'retrieve_terms_handler' ) );
	}

	/**
	 * @return void
	 */
	public function retrieve_post_types_handler() {

		Automator()->utilities->verify_nonce();

		$post_types = get_post_types( array(), 'object' );
		$options    = array();

		if ( ! empty( $post_types ) && is_iterable( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( $post_type instanceof \WP_Post_Type ) {
					$options[] = array(
						'text'  => $post_type->label,
						'value' => $post_type->name,
					);
				}
			}
		}

		$response = array(
			'success' => true,
			'options' => $options,
		);

		wp_send_json( $response );

	}

	/**
	 * Retrieves all taxonomies from a specific post type.
	 *
	 * @return void
	 */
	public function retrieve_taxonomies_handler() {

		Automator()->utilities->verify_nonce();

		$options = array();

		$selected_post_type = isset( $_POST['values']['WP_POST_HAS_OR_HAS_NOT_TERM_TAXONOMY']['value'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['values']['WP_POST_HAS_OR_HAS_NOT_TERM_TAXONOMY'] )['value'] ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$taxonomies = array_values( (array) get_object_taxonomies( $selected_post_type, 'objects' ) );

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy instanceof WP_Taxonomy ) {
				$options[] = array(
					'value' => $taxonomy->name,
					'text'  => $taxonomy->label,
				);
			}
		}

		$response = array(
			'success' => true,
			'options' => $options,
		);

		wp_send_json( $response );
	}

	/**
	 * Retrieves all terms from a specific taxonomy.
	 *
	 * @return void
	 */
	public function retrieve_terms_handler() {

		Automator()->utilities->verify_nonce();

		$options = array();

		$selected_taxonomy = isset( $_POST['values']['TAXONOMY']['value'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? sanitize_text_field( wp_unslash( $_POST['values']['TAXONOMY'] )['value'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $selected_taxonomy ) ) {
			return array();
		}

		$terms = (array) get_terms(
			array(
				'taxonomy'   => $selected_taxonomy,
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {

			if ( $term instanceof WP_Term ) {
				$options[] = array(
					'value' => $term->slug,
					'text'  => $term->name,
				);
			}
		}

		$response = array(
			'success' => true,
			'options' => $options,
		);

		wp_send_json( $response );

	}

}
