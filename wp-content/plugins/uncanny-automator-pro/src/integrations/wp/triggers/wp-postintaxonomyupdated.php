<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Log_Properties;
use Uncanny_Automator\Wp_Helpers;

/**
 * Class WP_POSTINTAXONOMYUPDATED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_POSTINTAXONOMYUPDATED {

	use Log_Properties;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	/**
	 * The trigger code.
	 *
	 * @var string
	 */
	private $trigger_code;

	/**
	 * The trigger meta.
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'WPPOSTINTAXONOMY';
		$this->trigger_meta = 'SPECIFICTAXONOMY';
		if ( Automator()->helpers->recipe->is_edit_page() ) {
			add_action(
				'wp_loaded',
				function () {
					$this->define_trigger();
				},
				99
			);

			return;
		}
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wordpress-core/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - WordPress Core */
			'sentence'            => sprintf( __( 'A user updates a post in {{a specific taxonomy:%1$s}}', 'uncanny-automator-pro' ), 'WPTAXONOMIES:' . $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( 'A user updates a post in {{a specific taxonomy}}', 'uncanny-automator-pro' ),
			'action'              => 'post_updated',
			'priority'            => 10,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'wp_post_updated' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		if ( method_exists( '\Uncanny_Automator\Wp_Helpers', 'common_trigger_loopable_tokens' ) ) {
			$trigger['loopable_tokens'] = Wp_Helpers::common_trigger_loopable_tokens();
		}

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {

		$options_array = array(
			'options_group' => array(
				$this->trigger_meta => array(
					Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
						__( 'Post type', 'uncanny-automator-pro' ),
						'WPPOSTTYPES',
						array(
							'token'           => true,
							'is_ajax'         => true,
							'relevant_tokens' => array(),
							'endpoint'        => 'select_post_type_taxonomies',
							'target_field'    => 'WPTAXONOMIES',
						)
					),
					Automator()->helpers->recipe->wp->options->pro->all_wp_taxonomy(
						__( 'Taxonomy', 'uncanny-automator-pro' ),
						'WPTAXONOMIES',
						array(
							'token'        => true,
							'is_ajax'      => true,
							'target_field' => $this->trigger_meta,
							'endpoint'     => 'select_all_terms_of_SELECTEDTAXONOMY',
							'is_any'       => true,
						)
					),
					Automator()->helpers->recipe->field->select_field_args(
						array(
							'option_code'           => $this->trigger_meta,
							'options'               => array(),
							'required'              => true,
							'label'                 => esc_html__( 'Term', 'uncanny-automator-pro' ),
							'token'                 => true,
							'relevant_tokens'       => array(
								$this->trigger_meta => esc_html__( 'Taxonomy term', 'uncanny-automator-pro' ),
							),
							'supports_custom_value' => false,
						)
					),
					Wp_Pro_Helpers::pro_conditional_child_taxonomy_checkbox(),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $post_ID
	 * @param $post_after
	 * @param $post_before
	 */
	public function wp_post_updated( $post_ID, $post_after, $post_before ) {

		// Bail on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Maybe bail on REST requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			if ( apply_filters( 'automator_wp_post_updates_prevent_trigger_on_rest_requests', true, $post_ID ) ) {
				return;
			}
		}

		// Maybe bail non public posts.
		$include_non_public_posts = apply_filters( 'automator_wp_post_updates_include_non_public_posts', false, $post_ID );
		if ( false === $include_non_public_posts ) {
			$__object = get_post_type_object( $post_after->post_type );
			if ( false === $__object->public ) {
				return false;
			}
		}

		$user_id = get_current_user_id();

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );

		$required_post_type = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );

		$post = $post_after;

		$required_taxonomy = Automator()->get->meta_from_recipes( $recipes, 'WPTAXONOMIES' );

		$required_term = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		$include_taxonomy_children = Automator()->get->meta_from_recipes( $recipes, 'WPTAXONOMIES_CHILDREN' );
		$include_taxonomy_children = ! empty( $include_taxonomy_children ) ? $include_taxonomy_children : array();

		$term_ids            = array();
		$matched_recipe_ids  = array();
		$matched_child_terms = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				// is post type
				if (
					intval( '-1' ) === intval( $required_post_type[ $recipe_id ][ $trigger_id ] ) // any post type
					|| $post->post_type === $required_post_type[ $recipe_id ][ $trigger_id ] // specific post type
					|| empty( $required_post_type[ $recipe_id ][ $trigger_id ] ) // Backwards compatibility -- the trigger didnt have a post type selection pre 2.10
				) {

					// is post taxonomy
					if (
						'0' === $required_taxonomy[ $recipe_id ][ $trigger_id ] // any taxonomy
					) {

						// any taxonomy also automatically means any term
						$matched_recipe_ids[] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
						);
						continue;

					} else {

						// specific taxonomy
						$post_terms = wp_get_post_terms( $post_ID, $required_taxonomy[ $recipe_id ][ $trigger_id ] );

						// is post term
						if (
							isset( $post_terms ) && ! empty( $post_terms ) // the taxomomy has terms
						) {

							// get all taxonomy term ids
							foreach ( $post_terms as $term ) {
								$term_ids[] = $term->term_id;
							}

							if (
								intval( '-1' ) === intval( $required_term[ $recipe_id ][ $trigger_id ] ) // any terms
								|| in_array( absint( $required_term[ $recipe_id ][ $trigger_id ] ), $term_ids, true ) // specific term
							) {
								$matched_recipe_ids[] = array(
									'recipe_id'  => $recipe_id,
									'trigger_id' => $trigger_id,
								);
							} else {

								// Not found, so check if we should include children
								$include_children = isset( $include_taxonomy_children[ $recipe_id ] ) ? $include_taxonomy_children[ $recipe_id ] : array();
								$include_children = isset( $include_children[ $trigger_id ] ) ? $include_children[ $trigger_id ] : false;
								$include_children = filter_var( strtolower( $include_children ), FILTER_VALIDATE_BOOLEAN );

								if ( $include_children ) {
									$child_term = Wp_Pro_Helpers::pro_get_term_child_of(
										$post_terms,
										$required_term[ $recipe_id ][ $trigger_id ],
										$required_taxonomy[ $recipe_id ][ $trigger_id ],
										$post_ID
									);

									if ( ! empty( $child_term ) ) {
										$matched_recipe_ids[] = array(
											'recipe_id'  => $recipe_id,
											'trigger_id' => $trigger_id,
											'post_id'    => $post_ID,
										);
										// Update log properties.
										$matched_child_terms[ $recipe_id ]                = isset( $matched_child_terms[ $recipe_id ] ) ? $matched_child_terms[ $recipe_id ] : array();
										$matched_child_terms[ $recipe_id ][ $trigger_id ] = $child_term->term_id . '( ' . $child_term->name . ' )';
									}
								}
							}
						}
					}
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {

				$__recipe_id  = $matched_recipe_id['recipe_id'];
				$__trigger_id = $matched_recipe_id['trigger_id'];
				$pass_args    = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $__recipe_id,
					'trigger_to_match' => $__trigger_id,
					'ignore_post_id'   => true,
				);

				$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);

							// post_id Token
							Automator()->db->token->save( 'post_id', $post_after->ID, $trigger_meta );

							$taxonomies = '';
							$terms      = '';

							// get terms and taxonomies
							if ( '0' === $required_taxonomy[ $recipe_id ][ $trigger_id ] ) {
								$all_taxonomies = array();
								$all_terms      = array();
								foreach ( get_object_taxonomies( $post, 'objects' ) as $taxonomy ) {
									$all_taxonomies[] = $taxonomy->label;
									if ( intval( '-1' ) === intval( $required_term[ $recipe_id ][ $trigger_id ] ) ) {
										$tax_terms = wp_get_post_terms( $post_ID, $taxonomy->name );
										if (
											isset( $tax_terms ) && ! empty( $tax_terms ) // the taxomomy has terms
										) {
											// get all taxonomy term names
											foreach ( $tax_terms as $term ) {
												$all_terms[] = $term->name;
											}
										}
									} else {
										$tax_terms = wp_get_post_terms( $post_ID, $required_term[ $recipe_id ][ $trigger_id ] );
										if (
											isset( $post_terms ) && ! empty( $post_terms ) // the taxomomy has terms
										) {
											// get all taxonomy term names
											foreach ( $tax_terms as $term ) {
												$all_terms[] = $term->name;
											}
										}
									}
								}

								$taxonomies = implode( ', ', $all_taxonomies );
								$terms      = implode( ', ', $all_terms );
							} else {
								$taxonomy = get_taxonomy( $required_taxonomy[ $recipe_id ][ $trigger_id ] );
								if ( false !== $taxonomy ) {
									$taxonomies = $taxonomy->label;

									if ( intval( '-1' ) === intval( $required_term[ $recipe_id ][ $trigger_id ] ) ) {
										$tax_terms = wp_get_post_terms( $post_ID, $taxonomy->name );
										if (
											isset( $tax_terms ) && ! empty( $tax_terms ) // the taxomomy has terms
										) {
											// get all taxonomy term names
											foreach ( $tax_terms as $term ) {
												$all_terms[] = $term->name;
											}
											$terms = implode( ', ', $all_terms );
										}
									} else {
										$term = get_term( $required_term[ $recipe_id ][ $trigger_id ], $taxonomy->name );
										if (
											isset( $term ) && ! empty( $term ) // the taxomomy has terms
										) {
											$terms = $term->name;
										}
									}
								}
							}

							// Update Log Properties for child term matches.
							if ( isset( $matched_child_terms[ $__recipe_id ] ) && isset( $matched_child_terms[ $__recipe_id ][ $__trigger_id ] ) ) {
								$this->set_trigger_log_properties(
									array(
										'type'       => 'string',
										'label'      => _x( 'Matched Child Term', 'WordPress', 'uncanny-automator' ),
										'value'      => $matched_child_terms[ $__recipe_id ][ $__trigger_id ],
										'attributes' => array(),
									)
								);
							}

							do_action( 'automator_loopable_token_hydrate', $result['args'], func_get_args() );

							Automator()->db->token->save( 'WPTAXONOMIES', maybe_serialize( $taxonomies ), $trigger_meta );
							Automator()->db->token->save( $this->trigger_meta, maybe_serialize( $terms ), $trigger_meta );
							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}
}
