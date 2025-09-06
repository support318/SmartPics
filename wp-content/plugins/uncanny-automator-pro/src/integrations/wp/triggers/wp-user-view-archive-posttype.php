<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WP_USER_VIEW_ARCHIVE_POSTTYPE
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_VIEW_ARCHIVE_POSTTYPE {
	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( Automator()->helpers->recipe->is_edit_page() ) {
			add_action(
				'wp_loaded',
				function () {
					$this->setup_trigger();
				},
				99
			);

			return;
		}
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WP' );
		$this->set_trigger_code( 'WP_USER_VIEWS_ARCHIVE_POST' );
		$this->set_trigger_meta( 'SPECIFICTAXONOMY' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %1$s is a term, %2$s is a taxonomy, %3$s is a post type
			sprintf( esc_html_x( 'A user views the archive of {{a specific term:%1$s}} in {{a specific taxonomy:%2$s}} of {{a type of post:%3$s}}', 'WordPress', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'WPTAXONOMIES:' . $this->get_trigger_meta(), 'WPSPOSTTYPES:' . $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_html_x( 'A user views {{a term}} archive', 'WordPress', 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->set_action_hook( 'template_redirect' );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}

	/**
	 * load_options()
	 *
	 * @return array
	 */
	public function load_options() {

		$options_array = array(
			'options_group' => array(
				$this->get_trigger_meta() => array(
					Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
						esc_html_x( 'Post type', 'WordPress', 'uncanny-automator-pro' ),
						'WPSPOSTTYPES',
						array(
							'token'           => true,
							'is_ajax'         => true,
							'is_any'          => true,
							'relevant_tokens' => array(),
							'endpoint'        => 'select_post_type_taxonomies_SELECTEDTAXONOMY',
							'target_field'    => 'WPTAXONOMIES',
						)
					),
					Automator()->helpers->recipe->wp->options->pro->all_wp_taxonomy(
						esc_html_x( 'Taxonomy', 'WordPress', 'uncanny-automator-pro' ),
						'WPTAXONOMIES',
						array(
							'token'        => true,
							'is_ajax'      => true,
							'target_field' => $this->get_trigger_meta(),
							'endpoint'     => 'select_all_terms_of_SELECTEDTAXONOMY',
							'is_any'       => true,
						)
					),
					Automator()->helpers->recipe->field->select_field_args(
						array(
							'option_code'           => $this->get_trigger_meta(),
							'options'               => array(),
							'required'              => true,
							'label'                 => esc_html_x( 'Term', 'WordPress', 'uncanny-automator-pro' ),
							'token'                 => true,
							'relevant_tokens'       => array(
								$this->get_trigger_meta() => esc_html_x( 'Taxonomy term', 'WordPress', 'uncanny-automator-pro' ),
							),
							'supports_custom_value' => false,
						)
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}


	/**
	 * Validate the trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( true === is_archive() ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare to run.
	 *
	 * Sets the conditional trigger to true.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Validates the conditions.
	 *
	 * @param array $args The trigger args.
	 *
	 * @return array The matching recipe and trigger IDs.
	 */
	public function validate_conditions( ...$args ) {

		global $post;

		$taxonomy_object = get_queried_object();

		// Bail if the required data is not set.
		if ( ! isset(
			$post->post_type,
			$taxonomy_object->taxonomy,
			$taxonomy_object->term_taxonomy_id
		) ) {
			return array();
		}

		return $this->find_all( $this->trigger_recipes() )
			->where( array( 'WPSPOSTTYPES', 'WPTAXONOMIES', $this->get_trigger_meta() ) )
			->match( // phpcs:ignore Uncanny_Automator.PHP.ForbiddenPHP8Features.ForbiddenPHP8Feature
				array(
					$post->post_type,
					$taxonomy_object->taxonomy,
					$taxonomy_object->term_taxonomy_id,
				)
			)
			->format( array( 'trim', 'trim', 'intval' ) )
			->get();
	}
}
