<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WP_SUBMITCOMMENT
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WP_SUBMITCOMMENT {

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
		$this->trigger_code = 'WPCOMMENTSUBMITTED';
		$this->trigger_meta = 'SUBMITCOMMENTONPOST';
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
			/* translators: %1$s: Post type */
			'sentence'            => sprintf( esc_attr_x( "A guest comment is submitted on a user's {{post:%1\$s}}", 'WordPress', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress */
			'select_option_name'  => esc_attr_x( "A guest comment is submitted on a user's {{post}}", 'WordPress', 'uncanny-automator-pro' ),
			'action'              => 'comment_post',
			'priority'            => 90,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'anon_submit_comment' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {

		$options = Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
							esc_attr_x( 'Post type', 'WordPress', 'uncanny-automator-pro' ),
							'WPPOSTTYPES',
							array(
								'token'        => false,
								'is_ajax'      => true,
								'comments'     => true,
								'target_field' => $this->trigger_meta,
								'endpoint'     => 'select_all_post_from_SELECTEDPOSTTYPE',
							)
						),
						Automator()->helpers->recipe->field->select_field(
							$this->trigger_meta,
							esc_attr_x( 'Post', 'WordPress', 'uncanny-automator-pro' ),
							array(),
							null,
							false,
							false,
							$relevant_tokens = array(
								'POSTEXCERPT'            => esc_attr_x( 'Post excerpt', 'WordPress', 'uncanny-automator-pro' ),
								'POSTCONTENT'            => esc_attr_x( 'Post content (raw)', 'WordPress', 'uncanny-automator-pro' ),
								'POSTCONTENT_BEAUTIFIED' => esc_attr_x( 'Post content (formatted)', 'WordPress', 'uncanny-automator-pro' ),
								'WPPOSTTYPES_TYPE'       => esc_attr_x( 'Post type', 'WordPress', 'uncanny-automator-pro' ),
								'POSTAUTHORFN'           => esc_attr_x( 'Post author first name', 'WordPress', 'uncanny-automator-pro' ),
								'POSTAUTHORLN'           => esc_attr_x( 'Post author last name', 'WordPress', 'uncanny-automator-pro' ),
								'POSTAUTHORDN'           => esc_attr_x( 'Post author display name', 'WordPress', 'uncanny-automator-pro' ),
								'POSTAUTHOREMAIL'        => esc_attr_x( 'Post author email', 'WordPress', 'uncanny-automator-pro' ),
								'POSTAUTHORURL'          => esc_attr_x( 'Post author URL', 'WordPress', 'uncanny-automator-pro' ),
								'COMMENTID'              => esc_attr_x( 'Comment ID', 'WordPress', 'uncanny-automator-pro' ),
								'COMMENTAUTHOR'          => esc_attr_x( 'Commenter name', 'WordPress', 'uncanny-automator-pro' ),
								'COMMENTAUTHOREMAIL'     => esc_attr_x( 'Commenter email', 'WordPress', 'uncanny-automator-pro' ),
								'COMMENTAUTHORWEB'       => esc_attr_x( 'Commenter website', 'WordPress', 'uncanny-automator-pro' ),
								'COMMENTCONTENT'         => esc_attr_x( 'Comment content', 'WordPress', 'uncanny-automator-pro' ),
								'POSTCOMMENTURL'         => esc_attr_x( 'Comment URL', 'WordPress', 'uncanny-automator-pro' ),
								'POSTCOMMENTDATE'        => esc_attr_x( 'Comment submitted date', 'WordPress', 'uncanny-automator-pro' ),
								'POSTCOMMENTSTATUS'      => esc_attr_x( 'Comment status', 'WordPress', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);

		// Add Akismet checkbox if plugin is active
		if ( defined( 'AKISMET_VERSION' ) ) {
			$options['options_group'][ $this->trigger_meta ][] = array(
				'input_type'    => 'checkbox',
				'label'         => esc_html_x( 'Trigger only if the comment passes Akismet spam filtering', 'WordPress', 'uncanny-automator-pro' ),
				'option_code'   => 'AKISMET_CHECK',
				'is_toggle'     => true,
				'default_value' => false,
			);
		}

		return $options;
	}
	/**
 * Handle anonymous comment submission trigger.
 *
 * @param int    $comment_id        The comment ID.
 * @param string $comment_approved  The comment approval status ('0', '1', or 'spam').
 * @param array  $commentdata       The comment data array.
 */
	public function anon_submit_comment( $comment_id, $comment_approved, $commentdata ) {

		// Only trigger for anonymous users
		if ( ! empty( $commentdata['user_id'] ) ) {
			return;
		}

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_meta = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			if ( ! isset( $recipe['triggers'] ) || ! is_array( $recipe['triggers'] ) ) {
				continue;
			}

			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = absint( $trigger['ID'] );

				// Get selected post from trigger meta
				$selected_post = isset( $required_post_meta[ $recipe_id ][ $trigger_id ] ) ? $required_post_meta[ $recipe_id ][ $trigger_id ] : '';
				$is_any        = ( '-1' === (string) $selected_post );
				$match_post_id = (string) $commentdata['comment_post_ID'] === (string) $selected_post;

				// Check Akismet filtering
				if ( Automator()->helpers->recipe->wp->should_block_comment_by_akismet( $trigger, $comment_approved, $commentdata ) ) {
					continue;
				}

				// If matched
				if ( $is_any || $match_post_id ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		// If no matching recipe found
		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		$user_id = get_current_user_id();

		foreach ( $matched_recipe_ids as $match ) {
			$args = Automator()->maybe_add_trigger_entry(
				array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $match['recipe_id'],
					'trigger_to_match' => $match['trigger_id'],
					'post_id'          => $commentdata['comment_post_ID'],
				),
				false
			);

			if ( ! $args ) {
				continue;
			}

			foreach ( $args as $result ) {
				if ( true !== $result['result'] ) {
					continue;
				}

				$trigger_meta = array(
					'user_id'        => absint( $user_id ),
					'trigger_id'     => absint( $result['args']['trigger_id'] ),
					'trigger_log_id' => absint( $result['args']['trigger_log_id'] ),
					'run_number'     => absint( $result['args']['run_number'] ),
				);

				// Save comment ID token
				Automator()->db->token->save( 'comment_id', maybe_serialize( $comment_id ), $trigger_meta );

				// Complete trigger
				Automator()->maybe_trigger_complete( $result['args'] );
			}
		}
	}
}
