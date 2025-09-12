<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USERPOSTSTATUS
 *
 * @package Uncanny_Automator_Pro
 */
class WP_POSTTRASHED {

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

		$this->trigger_code = 'WPPOSTTRASHED';
		$this->trigger_meta = 'POSTTYPE';

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
	 * @return void
	 */
	public function plugins_loaded() {
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
			'sentence'            => sprintf( __( '{{A post:%1$s}} is moved to the trash', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( '{{A post}} is moved to the trash', 'uncanny-automator-pro' ),
			'action'              => 'trashed_post',
			'priority'            => 10,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'wp_post_trashed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return \array[][]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'WPPOSTTYPES',
							array(
								'token'           => false,
								'is_ajax'         => true,
								'target_field'    => $this->trigger_meta,
								'endpoint'        => 'select_all_post_from_SELECTEDPOSTTYPE',
								'relevant_tokens' => array(),
							)
						),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code'     => $this->trigger_meta,
								'label'           => esc_attr__( 'Post', 'uncanny-automator-pro' ),
								'relevant_tokens' => array(),
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $post_id
	 */
	public function wp_post_trashed( $postID ) {
		$current_user_id = get_current_user_id();
		$post            = get_post( $postID );
		$post_author     = get_user_by( 'ID', $post->post_author );

		$recipes           = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_postType = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );
		$required_post     = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				//Add where option is set to Any post type
				if ( intval( '-1' ) === intval( $required_postType[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				} //Add where option is set to Any post of specific post type
				elseif ( $required_postType[ $recipe_id ][ $trigger_id ] === $post->post_type && - 1 === intval( $required_post[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				} //Add where option is set to a specific post of specific post type
				elseif ( $required_postType[ $recipe_id ][ $trigger_id ] == $post->post_type && $postID == $required_post[ $recipe_id ][ $trigger_id ] ) {
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
					'user_id'          => $current_user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $current_user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);

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
