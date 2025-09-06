<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Discuz;

/**
 * Class WP_DISCUZ_ADD_COMMENT_TO_POST
 *
 * @package Uncanny_Automator_Pro
 */
class WP_DISCUZ_ADD_COMMENT_TO_POST extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'WPDISCUZ' );
		$this->set_action_code( 'WPD_ADD_COMMENT' );
		$this->set_action_meta( 'WPD_COMMENT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Add {{a comment:%1$s}} to {{a post:%2$s}}', 'wpDiscuz', 'uncanny-automator-pro' ), $this->get_action_meta(), $this->get_action_meta() . '_POST_ID:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Add {{a comment}} to {{a post}}', 'wpDiscuz', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return array
	 */
	public function options() {

		return array(
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
				'description'    => esc_html__( 'If the email matches a WordPress user, the comment will be attributed to that user.', 'uncanny-automator' ),
			),
			array(
				'input_type'     => 'text',
				'option_code'    => $this->get_action_meta() . '_NAME',
				'required'       => true,
				'supports_token' => true,
				'label'          => esc_html__( 'Name', 'uncanny-automator-pro' ),
				'description'    => esc_html__( "If the email provided above matches a WordPress user, that user's name will be used.", 'uncanny-automator' ),
			),
			array(
				'input_type'     => 'textarea',
				'option_code'    => $this->get_action_meta(),
				'required'       => true,
				'supports_token' => true,
				'label'          => esc_html__( 'Comment', 'uncanny-automator-pro' ),
			),
		);
	}

	public function define_tokens() {
		return array(
			'COMMENT_ID' => array(
				'name' => __( 'Comment ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$post_id       = isset( $parsed[ $this->get_action_meta() . '_POST_ID' ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() . '_POST_ID' ] ) : 0;
		$author        = isset( $parsed[ $this->get_action_meta() . '_NAME' ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() . '_NAME' ] ) : '';
		$content       = isset( $parsed[ $this->get_action_meta() ] ) ? wp_kses_post( $parsed[ $this->get_action_meta() ] ) : '';
		$email         = isset( $parsed[ $this->get_action_meta() . '_EMAIL' ] ) ? sanitize_email( $parsed[ $this->get_action_meta() . '_EMAIL' ] ) : '';
		$existing_user = get_user_by( 'email', $email );

		if ( empty( $content ) ) {
			$this->add_log_error( esc_attr_x( 'Comment content is empty.', 'wpDiscuz', 'uncanny-automator-pro' ) );

			return false;
		}

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
			'posted_by_automator'  => true, // to avoid infinity loop with other comment triggers
		);

		$comment = wp_new_comment( $comment_data, true );

		if ( is_wp_error( $comment ) ) {
			$this->add_log_error( esc_attr_x( $comment->get_error_message(), 'wpDiscuz', 'uncanny-automator-pro' ) );

			return false;
		}

		$this->hydrate_tokens(
			array( 'COMMENT_ID' => $comment )
		);

		return true;
	}
}
