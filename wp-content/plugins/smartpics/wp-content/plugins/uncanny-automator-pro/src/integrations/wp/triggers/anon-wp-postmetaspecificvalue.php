<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WP_POSTMETASPECIFICVALUE
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WP_POSTMETASPECIFICVALUE {

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
	 * The post meta.
	 *
	 * @var string
	 */
	public $post_meta = 'WPPOSTTYPES';

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANONWPPOSTMETASPECIFCVAL';
		$this->trigger_meta = 'POSTSPECIFICUMETAVAL';

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
			'type'                => 'anonymous',
			/* translators: Logged-in trigger - WordPress Core */
			'sentence'            => sprintf( esc_attr__( '{{A specific meta key:%2$s}} of a {{specific type of post:%1$s}} updates to {{a specific value:%3$s}}', 'uncanny-automator-pro' ), $this->post_meta, 'SPECIFICUMETAKEY', $this->trigger_meta ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => esc_attr__( '{{A specific meta key}} of a {{specific type of post}} updates to {{a specific value}}', 'uncanny-automator-pro' ),
			'action'              => array(
				'update_postmeta', // when meta is updated
				'added_post_meta', // when meta is added
			),
			'priority'            => 10,
			'accepted_args'       => 4,
			'validation_function' => array( $this, 'updated_post_meta_data' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$options_array = array(
			'options' => array(
				Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
					esc_attr__( 'Post type', 'uncanny-automator-pro' ),
					$this->post_meta,
					array(
						'relevant_tokens' => array(
							$this->post_meta               => __( 'Post title', 'uncanny-automator-pro' ),
							$this->post_meta . '_ID'       => __( 'Post ID', 'uncanny-automator-pro' ),
							$this->post_meta . '_URL'      => __( 'Post URL', 'uncanny-automator-pro' ),
							$this->post_meta . '_THUMB_ID' => __( 'Post featured image ID', 'uncanny-automator-pro' ),
							$this->post_meta . '_THUMB_URL' => __( 'Post featured image URL', 'uncanny-automator-pro' ),
							$this->post_meta . '_TYPE'     => __( 'Post type', 'uncanny-automator-pro' ),
							$this->post_meta . '_EXCERPT'  => __( 'Post excerpt', 'uncanny-automator-pro' ),
							$this->post_meta . '_CONTENT'  => __( 'Post content (raw)', 'uncanny-automator-pro' ),
							$this->post_meta . '_CONTENT_BEAUTIFIED' => __( 'Post content (formatted)', 'uncanny-automator-pro' ),
							$this->post_meta . '_STATUS'   => __( 'Post status', 'uncanny-automator-pro' ),
							'POSTAUTHORFN'                 => __( 'Post author first name', 'uncanny-automator-pro' ),
							'POSTAUTHORLN'                 => __( 'Post author last name', 'uncanny-automator-pro' ),
							'POSTAUTHORDN'                 => __( 'Post author display name', 'uncanny-automator-pro' ),
							'POSTAUTHOREMAIL'              => __( 'Post author email', 'uncanny-automator-pro' ),
							'POSTAUTHORURL'                => __( 'Post author URL', 'uncanny-automator-pro' ),
						),
					)
				),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'SPECIFICUMETAKEY',
						'input_type'  => 'text',
						'label'       => esc_attr__( 'Meta key', 'uncanny-automator-pro' ),
						'description' => esc_attr__( 'Enter * to trigger on all meta key.', 'uncanny-automator-pro' ),
					)
				),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => $this->trigger_meta,
						'input_type'  => 'text',
						'label'       => esc_attr__( 'Meta value', 'uncanny-automator-pro' ),
						'description' => esc_attr__( 'Enter * to trigger on all meta value', 'uncanny-automator-pro' ),
					)
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $meta_id
	 * @param $post_id
	 * @param $meta_key
	 * @param $meta_value
	 */
	public function updated_post_meta_data( $meta_id, $post_id, $meta_key, $_meta_value ) {
		$ignore_meta_keys = apply_filters( 'automator_pro_trigger_update_postmeta_ignore_keys', array( '_edit_lock' ), $post_id, $meta_key, $_meta_value );

		if ( in_array( $meta_key, $ignore_meta_keys, true ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( true === apply_filters( 'automator_pro_trigger_update_postmeta_logged_in_req', false, $post_id, $meta_key, $_meta_value ) && ! is_user_logged_in() ) {
			return;
		}

		$post = get_post( $post_id );
		// sanity check
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				if ( key_exists( $this->trigger_meta, $trigger['meta'] ) ) {
					$trigger_field = $trigger['meta']['SPECIFICUMETAKEY'];
					$trigger_value = $trigger['meta'][ $this->trigger_meta ];
					$post_type     = $trigger['meta'][ $this->post_meta ];
					if (
						(
							intval( $post_type ) === intval( '-1' ) ||
							(string) $post_type === $post->post_type
						) &&
						(
							'*' === (string) $trigger_field ||
							strtolower( $trigger_field ) === strtolower( $meta_key )
						) &&
						(
							'*' === (string) $trigger_value ||
							strtolower( $trigger_value ) === strtolower( $_meta_value )
						)
					) {
						$matched_recipe_ids[ $trigger['ID'] ] = array(
							'recipe_id'  => $recipe['ID'],
							'trigger_id' => $trigger['ID'],
							'meta_field' => $meta_key,
							'meta_value' => $_meta_value,
						);
					}
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		foreach ( $matched_recipe_ids as $trigger_id => $recipe_id ) {
			$args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'recipe_to_match'  => $recipe_id['recipe_id'],
				'trigger_to_match' => $trigger_id,
				'ignore_post_id'   => true,
				'user_id'          => $user_id,
			);

			$result = Automator()->maybe_add_trigger_entry( $args, false );

			if ( $result ) {
				foreach ( $result as $r ) {
					if ( true === $r['result'] ) {
						if ( isset( $r['args'] ) && isset( $r['args']['trigger_log_id'] ) ) {
							//Saving form values in trigger log meta for token parsing!
							$save_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $r['args']['trigger_id'],
								'run_number'     => $r['args']['run_number'],
								'trigger_log_id' => $r['args']['trigger_log_id'],
							);

							// post_id Token
							Automator()->db->token->save( 'post_id', $post->ID, $save_meta );
							Automator()->db->token->save( 'SPECIFICUMETAKEY', maybe_serialize( $recipe_id['meta_field'] ), $save_meta );
							Automator()->db->token->save( $this->trigger_meta, maybe_serialize( $recipe_id['meta_value'] ), $save_meta );

						}
						Automator()->maybe_trigger_complete( $r['args'] );
					}
				}
			}
		}
	}
}
