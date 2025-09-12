<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USERRECREPLYONCOMMENT
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USERRECREPLYONCOMMENT {

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
		$this->trigger_code = 'WPREPLYONCOMMENT';
		$this->trigger_meta = 'REPLYTOUSERSCOMMENT';
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
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'is_pro'              => true,
			/* translators: Logged-in trigger - WordPress */
			'sentence'            => sprintf( esc_attr__( "A user's comment on {{a specific type of post:%1\$s}} receives a reply", 'uncanny-automator-pro' ), 'WPPOSTTYPES' ),
			/* translators: Logged-in trigger - WordPress */
			'select_option_name'  => esc_attr__( "A user's comment on {{a specific type of post}} receives a reply", 'uncanny-automator-pro' ),
			'action'              => 'comment_post',
			'priority'            => 90,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'reply_on_comment' ),
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
						'WPPOSTTYPES',
						array(
							'comments'        => true,
							'relevant_tokens' => array(
								'COMMENTPARENT'      => __( 'Comment URL', 'uncanny-automator-pro' ),
								'COMMENTAUTHOR'      => __( 'Replier name', 'uncanny-automator-pro' ),
								'COMMENTAUTHOREMAIL' => __( 'Replier email', 'uncanny-automator-pro' ),
								'COMMENTAUTHORWEB'   => __( 'Replier website', 'uncanny-automator-pro' ),
								'COMMENTCONTENT'     => __( 'Reply content', 'uncanny-automator-pro' ),
								'POSTCOMMENTURL'     => __( 'Reply URL', 'uncanny-automator-pro' ),
								'POSTCOMMENTDATE'    => __( 'Reply submitted date', 'uncanny-automator-pro' ),
								'POSTCOMMENTSTATUS'  => __( 'Reply status', 'uncanny-automator-pro' ),
							),
						)
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $comment_id
	 * @param $comment_approved
	 * @param $commentdata
	 */
	public function reply_on_comment( $comment_id, $comment_approved, $commentdata ) {
		if ( $commentdata['user_id'] && 0 !== $commentdata['comment_parent'] ) {
			$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
			$required_post_type = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );
			$post_type          = get_post_type( $commentdata['comment_post_ID'] );
			$parent_comment     = get_comment( $commentdata['comment_parent'] );
			$user_id            = absint( $parent_comment->user_id );
			$matched_recipe_ids = array();

			//Add where option is set to specific post type
			foreach ( $recipes as $recipe_id => $recipe ) {
				foreach ( $recipe['triggers'] as $trigger ) {
					$trigger_id = $trigger['ID'];
					if ( intval( '-1' ) === intval( $required_post_type[ $recipe_id ][ $trigger_id ] ) || $required_post_type[ $recipe_id ][ $trigger_id ] === $post_type ) {
						$matched_recipe_ids[] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
						);
					}
				}
			}

			//	If recipe matches
			if ( empty( $matched_recipe_ids ) ) {
				return;
			}

			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
				);

				$args = Automator()->maybe_add_trigger_entry( $pass_args, false );
				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {
							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => absint( $result['args']['trigger_id'] ),
								'trigger_log_id' => absint( $result['args']['trigger_log_id'] ),
								'run_number'     => absint( $result['args']['run_number'] ),
							);

							// Comment ID
							Automator()->db->token->save( 'comment_id', maybe_serialize( $comment_id ), $trigger_meta );

							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}
}
