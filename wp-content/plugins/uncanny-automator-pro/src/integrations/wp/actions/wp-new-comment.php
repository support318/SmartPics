<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Action_Tokens;

/**
 * Class WP_NEW_COMMENT
 *
 * @package Uncanny_Automator
 */
class WP_NEW_COMMENT {

	use \Uncanny_Automator\Recipe\Actions;
	use Action_Tokens;

	public function __construct() {

		$this->setup_action();

		$this->register_action();

	}

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'WP' );

		$this->set_action_code( 'WP_NEW_COMMENT' );

		$this->set_action_meta( 'WP_NEW_COMMENT_META' );

		$this->set_is_pro( true );

		$this->set_requires_user( false );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'integration/wordpress-core/' ) );

		/* translators: Sentence name */
		$this->set_sentence( sprintf( esc_attr__( 'Add a comment to {{a post:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		$this->set_readable_sentence( esc_attr__( 'Add a comment to {{a post}}', 'uncanny-automator-pro' ) );
		$this->set_action_tokens(
			array(
				'COMMENT_ID' => array(
					'name' => __( 'Comment ID', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
			),
			$this->action_code
		);

		$this->set_options_callback( array( $this, 'load_options' ) );

	}

	/**
	 * Method load_options
	 *
	 * @return void
	 */
	public function load_options() {

		return array(
			'options_group' => array(
				$this->get_action_meta() => array(
					array(
						'input_type'     => 'text',
						'option_code'    => $this->get_action_meta() . '_POST_ID',
						'required'       => true,
						'supports_token' => true,
						'label'          => esc_html__( 'Post ID', 'uncanny-automator-pro' ),
					),
					array(
						'input_type'     => 'email',
						'option_code'    => $this->get_action_meta() . '_EMAIL',
						'required'       => true,
						'supports_token' => true,
						'label'          => esc_html__( "Commenter's email", 'uncanny-automator-pro' ),
						'description'    => esc_html__( 'If the email matches a WordPress user, the comment will be attributed to that user.', 'uncanny-automator-pro' ),
					),
					array(
						'input_type'     => 'text',
						'option_code'    => $this->get_action_meta() . '_NAME',
						'required'       => true,
						'supports_token' => true,
						'label'          => esc_html__( 'Name', 'uncanny-automator-pro' ),
						'description'    => esc_html__( "If the email provided above matches a WordPress user, that user's name will be used.", 'uncanny-automator-pro' ),
					),
					array(
						'input_type'     => 'textarea',
						'option_code'    => $this->get_action_meta() . '_COMMENT',
						'required'       => true,
						'supports_token' => true,
						'label'          => esc_html__( 'Comment', 'uncanny-automator-pro' ),
					),
				),
			),
		);

	}

	/**
	 * Method process_action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$post_id = isset( $parsed[ $this->get_action_meta() . '_POST_ID' ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() . '_POST_ID' ] ) : 0;
		$author  = isset( $parsed[ $this->get_action_meta() . '_NAME' ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() . '_NAME' ] ) : '';
		$content = isset( $parsed[ $this->get_action_meta() . '_COMMENT' ] ) ? wp_kses_post( $parsed[ $this->get_action_meta() . '_COMMENT' ] ) : '';
		$email   = isset( $parsed[ $this->get_action_meta() . '_EMAIL' ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() . '_EMAIL' ] ) : '';

		$existing_user = get_user_by( 'email', $email );

		$user_id = 0;

		if ( false !== $existing_user ) {
			// If the email provided above matches a WordPress user, that user's name will be used.
			$author  = $existing_user->data->display_name;
			$user_id = $existing_user->data->ID;
		}

		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $author,
			'comment_content'      => $content,
			'comment_author_email' => $email,
			'comment_author_url'   => '', // WordPress throws error notice if this is removed.
			'user_id'              => $user_id,
		);

		$comment = wp_new_comment( $comment_data, true );

		if ( is_wp_error( $comment ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $comment->get_error_message() );

			return;
		}

		$this->hydrate_tokens(
			array(
				'COMMENT_ID' => $comment,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

}
