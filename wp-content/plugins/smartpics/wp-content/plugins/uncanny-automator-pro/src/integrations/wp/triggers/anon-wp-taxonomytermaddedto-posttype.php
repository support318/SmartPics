<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Log_Properties;

/**
 *Class ANON_WP_TAXONOMYTERMADDEDTO_POSTTYPE
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WP_TAXONOMYTERMADDEDTO_POSTTYPE {

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
		$this->trigger_code = 'WPPOSTTAXONOMY';
		$this->trigger_meta = 'SPECIFICTAXONOMY';
		if ( Automator()->helpers->recipe->is_edit_page() ) {
			add_action(
				'wp_loaded',
				function () {
					$this->define_trigger();
				},
				99
			);
		} else {
			$this->define_trigger();
		}

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wordpress-core/' ),
			'is_pro'              => true,
			'type'                => 'anonymous',
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - WordPress Core */
			'sentence'            => sprintf( esc_attr__( '{{A taxonomy term:%1$s}} is added to a {{specific type of post:%2$s}}', 'uncanny-automator-pro' ), $this->trigger_meta, 'WPPOSTTYPES:' . $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => esc_attr__( '{{A taxonomy term}} is added to a {{specific type of post}}', 'uncanny-automator-pro' ),
			'action'              => 'added_term_relationship',
			'priority'            => 10,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'term_added' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

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
							'is_any'          => true,
							'relevant_tokens' => array(),
							'endpoint'        => 'select_post_type_taxonomies_SELECTEDTAXONOMY',
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
							'label'                 => esc_html__( 'Taxonomy term', 'uncanny-automator-pro' ),
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
	public function term_added( $post_ID, $term_id, $taxonomy ) {
		$user_id                   = get_current_user_id();
		$recipes                   = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_type        = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );
		$required_taxonomy         = Automator()->get->meta_from_recipes( $recipes, 'WPTAXONOMIES' );
		$required_term             = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$include_taxonomy_children = Automator()->get->meta_from_recipes( $recipes, 'WPTAXONOMIES_CHILDREN' );
		$include_taxonomy_children = ! empty( $include_taxonomy_children ) ? $include_taxonomy_children : array();
		$matched_recipe_ids        = array();
		$matched_child_terms       = array();
		$post                      = get_post( $post_ID );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( $post->post_type === (string) $required_post_type[ $recipe_id ][ $trigger_id ] ) {
					// Any taxonomy.
					if ( intval( '-1' ) === intval( $required_taxonomy[ $recipe_id ][ $trigger_id ] ) ) {
						$matched_recipe_ids[] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
						);
					} elseif ( (string) $taxonomy === (string) $required_taxonomy[ $recipe_id ][ $trigger_id ] ) {
						// Specific taxonomy.

						$matched = false;

						// Any Term.
						if ( intval( '-1' ) === intval( $required_term[ $recipe_id ][ $trigger_id ] ) ) {
							$matched = true;

						} else {

							// Term Match.
							if ( absint( $term_id ) === absint( $required_term[ $recipe_id ][ $trigger_id ] ) ) {
								$matched = true;

							} else {

								// Check if we need to include children.
								$include_children = isset( $include_taxonomy_children[ $recipe_id ] ) ? $include_taxonomy_children[ $recipe_id ] : array();
								$include_children = isset( $include_children[ $trigger_id ] ) ? $include_children[ $trigger_id ] : false;
								$include_children = filter_var( strtolower( $include_children ), FILTER_VALIDATE_BOOLEAN );

								// Check if the term is a child of the required term.
								if ( $include_children ) {
									$current_term = get_term( $term_id, $taxonomy );
									if ( ! is_wp_error( $current_term ) ) {
										$child_term = Wp_Pro_Helpers::pro_get_term_child_of(
											array( $current_term ),
											$required_term[ $recipe_id ][ $trigger_id ],
											$taxonomy,
											$post_ID
										);
										if ( ! empty( $child_term ) ) {
											$matched = true;

											// Update log properties.
											$matched_child_terms[ $recipe_id ]                = isset( $matched_child_terms[ $recipe_id ] ) ? $matched_child_terms[ $recipe_id ] : array();
											$matched_child_terms[ $recipe_id ][ $trigger_id ] = $child_term->term_id . '( ' . $child_term->name . ' )';
										}
									}
								}
							}
						}

						if ( $matched ) {
							$matched_recipe_ids[] = array(
								'recipe_id'  => $recipe_id,
								'trigger_id' => $trigger_id,
							);
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
									'trigger_log_id' => absint( $result['args']['trigger_log_id'] ),
									'run_number'     => absint( $result['args']['run_number'] ),
								);

								Automator()->db->token->save( $this->trigger_meta, maybe_serialize( get_term( $term_id )->name ), $trigger_meta );
								Automator()->db->token->save( 'WPTAXONOMIES', maybe_serialize( $taxonomy ), $trigger_meta );
								// post_id Token
								Automator()->db->token->save( 'post_id', $post_ID, $trigger_meta );

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

								Automator()->process->user->maybe_trigger_complete( $result['args'] );
							}
						}
					}
				}
			}
		}
	}
}
