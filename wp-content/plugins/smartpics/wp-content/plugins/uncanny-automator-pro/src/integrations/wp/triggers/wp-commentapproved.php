<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_COMMENTAPPROVED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_COMMENTAPPROVED {

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
		$this->trigger_code = 'COMMENTAPPROVED';
		$this->trigger_meta = 'WPCOMMENTONPOST';

		if ( is_admin() ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$action      = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP core 'dim-comment' action does not use a nonce

			// This is a core WP behavior. We are not validating nonce here
			// because WordPress does not use nonce for dim-comment approval.
			if ( 'admin-ajax.php' === basename( $request_uri ) && 'dim-comment' === $action ) {
				$this->define_trigger();
			} else {
				add_action(
					'plugins_loaded',
					array( $this, 'plugins_loaded' ),
					19
				);
			}
		} else {
			$this->define_trigger();
		}
	}


	/**
	 *
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
			/* translators: %1$s: Post */
			'sentence'            => sprintf( esc_attr_x( "A user's comment on {{a post:%1\$s}} is approved", 'WordPress', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress */
			'select_option_name'  => esc_attr_x( "A user's comment on {{a post}} is approved", 'WordPress', 'uncanny-automator-pro' ),
			'action'              => 'transition_comment_status',
			'priority'            => 90,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'comment_approved' ),
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
							esc_attr_x( 'Post type', 'WordPress', 'uncanny-automator-pro' ),
							'WPPOSTTYPES',
							array(
								'token'           => false,
								'is_ajax'         => true,
								'relevant_tokens' => array(),
								'comments'        => true,
								'target_field'    => $this->trigger_meta,
								'endpoint'        => 'select_all_post_from_SELECTEDPOSTTYPE',
							)
						),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code'     => $this->trigger_meta,
								'label'           => esc_attr_x( 'Post', 'WordPress', 'uncanny-automator' ),
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
	 * @param int|string $new_status The new comment status.
	 * @param int|string $old_status The old comment status.
	 * @param \WP_Comment $comment Comment object.
	 */
	public function comment_approved( $new_status, $old_status, $comment ) {

		if ( 'approved' !== (string) $new_status ) {
			return;
		}

		if ( ! $comment instanceof \WP_Comment ) {
			return;
		}

		$manual_approval = get_option( 'comment_moderation' );
		if ( 1 !== absint( $manual_approval ) ) {
			return;
		}

		if ( isset( $comment->user_id ) && 0 === absint( $comment->user_id ) ) {
			return;
		}

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		//Add where option is set to Any post / specific post
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( intval( '-1' ) === intval( $required_post[ $recipe_id ][ $trigger_id ] ) || absint( $required_post[ $recipe_id ][ $trigger_id ] ) === absint( $comment->comment_post_ID ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		//  If recipe matches
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $comment->user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'post_id'          => $comment->comment_post_ID,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			do_action( 'uap_wp_comment_approve', $comment, $matched_recipe_id['recipe_id'], $matched_recipe_id['trigger_id'], $args );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {
						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
