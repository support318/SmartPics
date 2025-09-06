<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WP_UPDATETHEAUTHOROFPOST
 *
 * @package Uncanny_Automator_Pro
 */
class WP_UPDATETHEAUTHOROFPOST {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	protected $helper;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {

		if ( ! class_exists( 'Uncanny_Automator_Pro\Wp_Pro_Helpers' ) ) {
			return;
		}

		$this->setup_action();
		$this->helper = new Wp_Pro_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'WP' );
		$this->set_action_code( 'WP_UPDATETHEAUTHOROFPOST' );
		$this->set_action_meta( 'WP_UPDATETHEAUTHOROFPOST_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
			/* translators: Action - WordPress */
				esc_attr__( 'Update the author of {{a post:%1$s}}', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);
		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Update the author of {{a post}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
				'OLD_AUTHOR' => array(
					'name' => __( 'Old author of the post', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
				'NEW_AUTHOR' => array(
					'name' => __( 'New author of the post', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
			),
			$this->action_code
		);

		$this->register_action();
	}

	/**
	 * Load_options
	 *
	 * @return array
	 */
	public function load_options() {
		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					$this->helper->all_wp_post_types(
						__( 'Post type', 'uncanny-automator-pro' ),
						'WPPOSTTYPE',
						array(
							'token'        => false,
							'is_ajax'      => true,
							'target_field' => $this->get_action_meta(),
							'is_any'       => false,
							'endpoint'     => 'select_all_post_of_selected_post_type',
						)
					),
					Automator()->helpers->recipe->field->select_field( $this->get_action_meta(), __( 'Post', 'uncanny-automator-pro' ) ),
					$this->helper->all_wp_users(
						__( 'User', 'uncanny-automator-pro' ),
						$this->get_action_meta() . '_WPUSERS'
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process action method
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$post_id   = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		$author_id = sanitize_text_field( $parsed[ $this->get_action_meta() . '_WPUSERS' ] );

		if ( empty( $author_id ) || empty( $post_id ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, __( 'Post or Author not found.', 'uncanny-automator-pro' ) );

			return;
		}
		$old_author_id = get_post_field( 'post_author', absint( $post_id ) );

		if ( absint( $old_author_id ) === absint( $author_id ) ) {

			$message                             = __( 'New author is the same as previous author.', 'uncanny-automator-pro' );
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		$data = array(
			'ID'          => absint( $post_id ),
			'post_author' => absint( $author_id ),
		);

		$post_id = wp_update_post( $data, true, false );

		if ( is_wp_error( $post_id ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $post_id->get_error_message() );

			return;
		}

		$this->hydrate_tokens(
			array(
				'OLD_AUTHOR' => $old_author_id,
				'NEW_AUTHOR' => absint( $author_id ),
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
