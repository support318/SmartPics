<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wp_Helpers;

/**
 * Class WP_USERPOSTSTATUS
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USERPOSTSTATUS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'WPPOSTSTATUS';
		$this->trigger_meta = 'POSTSTATUSUPDATED';
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
			'sentence'            => sprintf( __( "{{A user's post:%1\$s}} is set to {{a specific:%2\$s}} status", 'uncanny-automator-pro' ), 'WPPOST', $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( "{{A user's post}} is set to {{a specific}} status", 'uncanny-automator-pro' ),
			'action'              => 'transition_post_status',
			'priority'            => 190,
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
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					'WPPOST' => array(
						Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'WPPOSTTYPES',
							array(
								'token'           => false,
								'is_ajax'         => true,
								'target_field'    => 'WPPOST',
								'endpoint'        => 'select_all_post_from_SELECTEDPOSTTYPE',
								'relevant_tokens' => array(),
							)
						),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code'     => 'WPPOST',
								'label'           => esc_attr__( 'Post', 'uncanny-automator' ),
								'input_type'      => 'select',
								'relevant_tokens' => array(),
							)
						),
					),
				),
				'options'       => array(
					Automator()->helpers->recipe->wp->options->pro->wp_post_statuses(
						__( 'Status', 'uncanny-automator-pro' ),
						$this->trigger_meta,
						array(
							'is_any' => true,
						)
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public function wp_post_updated( $new_status, $old_status, $post ) {

		// Avoid double call. T#25676
		if ( automator_filter_has_var( 'meta-box-loader' ) === true ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		$recipes              = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_status = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$required_post_id     = Automator()->get->meta_from_recipes( $recipes, 'WPPOST' );
		$required_post_type   = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );
		$new_status           = (string) $new_status;
		$post_id              = (int) $post->ID;
		$post_type            = (string) $post->post_type;

		$matched_recipe_ids = array();

		$user_obj = get_user_by( 'ID', (int) $post->post_author );
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];

				// Add where option is set to Any post status
				if ( ! isset( $required_post_status[ $recipe_id ] ) ) {
					continue;
				}
				if ( ! isset( $required_post_status[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Add where option is set to Any post type
				if ( ! isset( $required_post_type[ $recipe_id ] ) ) {
					continue;
				}

				if ( ! isset( $required_post_type[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Add where option is set to Any post ID
				if ( ! isset( $required_post_id[ $recipe_id ] ) ) {
					continue;
				}
				if ( ! isset( $required_post_id[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				$any = intval( '-1' );

				// Check if the post ID passes.
				$check_post_id = intval( $required_post_id[ $recipe_id ][ $trigger_id ] );
				$post_id_match = $check_post_id === $any || $post_id === $check_post_id;

				// Check if the post type passes.
				$check_post_type = (string) $required_post_type[ $recipe_id ][ $trigger_id ];
				$post_type_match = intval( $check_post_type ) === $any || $post_type === $check_post_type;

				// Check if the post status passes.
				$check_post_status = (string) $required_post_status[ $recipe_id ][ $trigger_id ];
				$post_status_match = intval( $check_post_status ) === $any || $new_status === $check_post_status;

				// All conditions passed.
				if ( $post_id_match && $post_status_match && $post_type_match ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_obj->ID,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $user_obj->ID,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);

							do_action( 'automator_loopable_token_hydrate', $result['args'], func_get_args() );

							// post_id Token
							Automator()->db->token->save( 'post_id', $post->ID, $trigger_meta );
							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}

}
