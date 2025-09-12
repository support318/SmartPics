<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WP_POST_EQUALS_POST_TYPE
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
final class WP_POST_EQUALS_POST_TYPE extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->register_hooks();
		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_EQUALS_POST_TYPE' );
		$this->set_sentence( esc_html_x( 'A post is {{a specific type of post}}', 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				/* translators: Loop filter sentence */
				esc_html_x( 'A post is {{a specific type of post:%1$s}}', 'WordPress', 'uncanny-automator-pro' ),
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

		return array(
			$this->get_meta() => array(
				$post_type,
			),
		);

	}


	/**
	 * @param mixed[] $fields
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

		$posts = get_posts(
			array(
				'post_type'   => (string) $post_type,
				'fields'      => 'ids', // We're only interested in IDs.
				'numberposts' => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'post_status' => 'any', // This will include all post statuses.
			)
		);

		return (array) $posts;

	}

	/**
	 * @return void
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_retrieve_post_types', array( $this, 'retrieve_post_types_handler' ) );
	}

	/**
	 * @return void
	 */
	public function retrieve_post_types_handler() {

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'object'
		);
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

}
