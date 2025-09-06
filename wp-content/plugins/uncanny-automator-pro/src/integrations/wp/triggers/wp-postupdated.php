<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wp_Helpers;

/**
 * Class WP_POSTUPDATED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_POSTUPDATED {

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
		$this->trigger_code = 'WPPOSTUPDATED';
		$this->trigger_meta = 'POSTUPDATED';
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
			'sentence'            => sprintf( __( 'A user updates {{a post:%2$s}}', 'uncanny-automator-pro' ), 'WPPOSTTYPES:' . $this->trigger_meta, $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( 'A user updates {{a post}}', 'uncanny-automator-pro' ),
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
	 * @return \array[][]
	 */
	public function load_options() {

		$relevant_tokens = array(
			'WPPOSTTYPES'                    => __( 'Post title', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_ID'                 => __( 'Post ID', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_URL'                => __( 'Post URL', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_THUMB_ID'           => __( 'Post featured image ID', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_THUMB_URL'          => __( 'Post featured image URL', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_TYPE'               => __( 'Post type', 'uncanny-automator-pro' ),
			'POSTEXCERPT'                    => __( 'Post excerpt', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_CONTENT'            => __( 'Post content (raw)', 'uncanny-automator-pro' ),
			'WPPOSTTYPES_CONTENT_BEAUTIFIED' => __( 'Post content (formatted)', 'uncanny-automator-pro' ),
			'POSTAUTHORFN'                   => __( 'Post author first name', 'uncanny-automator-pro' ),
			'POSTAUTHORLN'                   => __( 'Post author last name', 'uncanny-automator-pro' ),
			'POSTAUTHORDN'                   => __( 'Post author display name', 'uncanny-automator-pro' ),
			'POSTAUTHOREMAIL'                => __( 'Post author email', 'uncanny-automator-pro' ),
			'POSTAUTHORURL'                  => __( 'Post author URL', 'uncanny-automator-pro' ),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'WPPOSTTYPES',
							array(
								'relevant_tokens' => array(),
								'token'           => false,
								'is_ajax'         => true,
								'target_field'    => $this->trigger_meta,
								'endpoint'        => 'select_all_post_from_SELECTEDPOSTTYPE',
							)
						),
						Automator()->helpers->recipe->field->select_field(
							$this->trigger_meta,
							__( 'Post', 'uncanny-automator-pro' ),
							array(),
							null,
							false,
							false,
							$relevant_tokens,
							array( 'supports_tokens' => false )
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $post_ID
	 * @param $post_after
	 * @param $post_before
	 *
	 * @return bool|void
	 */
	public function wp_post_updated( $post_ID, $post_after, $post_before ) {

		// Prevent run on auto save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Prevent if publishing a post.
		if ( 'publish' === $post_after->post_status && 'publish' !== $post_before->post_status ) {
			return false;
		}

		$ignore_statuses = apply_filters(
			'automator_pro_post_updated_ignore_statuses',
			array(
				'trash',
				'draft',
				'future',
			),
			$post_ID,
			$post_after,
			$post_before
		);

		// Prevent if the status is excluded
		if ( in_array( $post_after->post_status, $ignore_statuses, true ) ) {
			return false;
		}

		// Avoid double call. T#25676
		if ( automator_filter_has_var( 'meta-box-loader' ) === true ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		$user_id            = get_current_user_id();
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post_type = Automator()->get->meta_from_recipes( $recipes, 'WPPOSTTYPES' );
		$required_post      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = absint( $trigger['ID'] );
				$recipe_id  = absint( $recipe_id );

				if ( ! isset( $required_post_type[ $recipe_id ] ) ) {
					continue;
				}

				if ( ! isset( $required_post_type[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				if ( ! isset( $required_post[ $recipe_id ] ) ) {
					continue;
				}

				if ( ! isset( $required_post[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}
				//Add where option is set to Any post type
				if ( intval( '-1' ) === intval( $required_post_type[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				} elseif ( $required_post_type[ $recipe_id ][ $trigger_id ] === $post_before->post_type && intval( '-1' ) === intval( $required_post[ $recipe_id ][ $trigger_id ] ) ) {
					//Add where option is set to Any post of specific post type
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				} elseif ( $required_post_type[ $recipe_id ][ $trigger_id ] === $post_before->post_type && absint( $post_ID ) === absint( $required_post[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

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
				'ignore_post_id'   => true,
				'is_signed_in'     => true,
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

						do_action( 'automator_loopable_token_hydrate', $result['args'], func_get_args() );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
