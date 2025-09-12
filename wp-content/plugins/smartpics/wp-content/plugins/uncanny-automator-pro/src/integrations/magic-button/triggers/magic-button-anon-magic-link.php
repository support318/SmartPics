<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MAGIC_BUTTON_ANON_MAGIC_LINK
 *
 * @package Uncanny_Automator_Pro
 */
class MAGIC_BUTTON_ANON_MAGIC_LINK {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MAGIC_BUTTON';

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
		$this->trigger_code = 'ANONWPMAGICLINK';
		$this->trigger_meta = 'MAGICLINK';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/automator-core/' ),
			'is_pro'              => true,
			'type'                => 'anonymous',
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - WordPress Core. 1. Shortcode */
			'sentence'            => sprintf( __( '%1$s is clicked', 'uncanny-automator' ), '[automator_link id="{{id:ANONWPMAGICLINK}}" text="' . __( 'Click here', 'uncanny-automator-pro' ) . '"]' ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( '{{A magic link}} is clicked', 'uncanny-automator' ),
			'action'              => 'automator_magic_button_action',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'save_anon_data' ),
			'options'             => array(),
			'inline_css'          => $this->inline_css(),
		);
		Automator()->register->trigger( $trigger );
	}

	/**
	 * A piece of CSS that it's added only when this item
	 * is on the recipe
	 *
	 * @return string The CSS, with the CSS tags
	 */
	public function inline_css() {
		// Start output
		ob_start();
		?>
		<style>
			.item[data-id="{{item_id}}"] .item-title {
				user-select: auto;
			}

			.item[data-id="{{item_id}}"] .item-title__integration {
				user-select: none;
			}

			.item[data-id="{{item_id}}"] .item-title__normal,
			.item[data-id="{{item_id}}"] .item-title__token {
				margin-right: 0;
			}
		</style>
		<?php
		// Get output
		$output = ob_get_clean();

		// Return output
		return $output;
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $trigger_id
	 * @param $user_id
	 */
	public function save_anon_data( $trigger_id, $user_id ) {
		$recipe_id = Automator()->get->maybe_get_recipe_id( $trigger_id );

		$args_to_pass = array(
			'code'             => $this->trigger_code,
			'meta'             => $this->trigger_meta,
			'recipe_to_match'  => $recipe_id,
			'trigger_to_match' => $trigger_id,
			'ignore_post_id'   => true,
			'user_id'          => $user_id,
		);
		$results      = Automator()->maybe_add_trigger_entry( $args_to_pass, false );
		// Save trigger meta
		if ( $results ) {
			foreach ( $results as $result ) {
				if ( true === $result['result'] ) {
					$save_meta = array(
						'user_id'        => $user_id,
						'trigger_id'     => $result['args']['trigger_id'],
						'run_number'     => $result['args']['run_number'], //get run number
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'ignore_user_id' => true,
					);

					if ( $user_id != 0 ) {
						$user_data = get_userdata( $user_id );

						$save_meta['meta_key']   = 'email';
						$save_meta['meta_value'] = $user_data->user_email;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'username';
						$save_meta['meta_value'] = $user_data->user_login;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'user_id';
						$save_meta['meta_value'] = $user_data->ID;
						Automator()->insert_trigger_meta( $save_meta );
					}

					if ( automator_filter_has_var( 'automator_button_post_id' ) ) {
						$save_meta['meta_key']   = 'automator_button_post_id';
						$save_meta['meta_value'] = absint( automator_filter_input( 'automator_button_post_id' ) );
						Automator()->insert_trigger_meta( $save_meta );
						$post_data = get_post( absint( automator_filter_input( 'automator_button_post_id' ) ) );
						if ( ! empty( $post_data ) && isset( $post_data->ID ) ) {

							/**
							 * Fires automator_pro_magic_button_triggered action when a magic button is triggered.
							 *
							 * @param array $save_meta
							 * @param array $result
							 *
							 * @return void
							 */
							do_action( 'automator_pro_magic_link_triggered', $save_meta, $result );

							$save_meta['meta_key']   = 'automator_button_post_title';
							$save_meta['meta_value'] = $post_data->post_title;
							Automator()->insert_trigger_meta( $save_meta );

							$save_meta['meta_key']   = 'automator_button_post_url';
							$save_meta['meta_value'] = get_permalink( $post_data->ID );
							Automator()->insert_trigger_meta( $save_meta );
						}
					}
					Automator()->maybe_trigger_complete( $result['args'] );
				}
			}
		}
	}
}
