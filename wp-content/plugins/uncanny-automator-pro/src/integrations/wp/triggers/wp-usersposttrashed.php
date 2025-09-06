<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USERSPOSTTRASHED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USERSPOSTTRASHED {

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

		$this->trigger_code = 'WPUSERSPOSTTRASHED';
		$this->trigger_meta = 'SPECIFICPOSTTYPETRASHED';

		if ( is_admin() ) {
			global $pagenow;

			if (
				'post.php' === $pagenow
				&& automator_filter_has_var( 'action' )
				&& 'edit' === automator_filter_input( 'action' )
				&& automator_filter_has_var( 'post' )
			) {
				$post_type = get_post_type( automator_filter_input( 'post' ) );
				if ( 'uo-recipe' === $post_type ) {
					add_action( 'wp_loaded', array( $this, 'plugins_loaded' ), 99 );

					return;
				}
			}
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
			'sentence'            => sprintf( __( "A user's {{specific type of post:%1\$s}} is moved to the trash", 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( "A user's {{specific type of post}} is moved to the trash", 'uncanny-automator-pro' ),
			'action'              => 'trashed_post',
			'priority'            => 10,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'users_post_trashed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
						__( 'Post type', 'uncanny-automator-pro' ),
						$this->trigger_meta,
						array(
							'relevant_tokens' => array(),
						)
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
	public function users_post_trashed( $postID ) {
		$post        = get_post( $postID );
		$post_author = get_user_by( 'ID', $post->post_author );

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_type = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( $required_post_type[ $recipe_id ][ $trigger_id ] === $post->post_type ||
					 intval( '-1' ) === intval( $required_post_type[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);

				}
			}

			if ( ! empty( $matched_recipe_ids ) ) {
				foreach ( $matched_recipe_ids as $matched_recipe_id ) {
					$pass_args = array(
						'code'             => $this->trigger_code,
						'meta'             => $this->trigger_meta,
						'user_id'          => $post->post_author,
						'recipe_to_match'  => $matched_recipe_id['recipe_id'],
						'trigger_to_match' => $matched_recipe_id['trigger_id'],
					);

					$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

					if ( $args ) {
						foreach ( $args as $result ) {
							if ( true === $result['result'] ) {

								$trigger_meta = array(
									'user_id'        => get_current_user_id(),
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

			return;
		}

	}

}
