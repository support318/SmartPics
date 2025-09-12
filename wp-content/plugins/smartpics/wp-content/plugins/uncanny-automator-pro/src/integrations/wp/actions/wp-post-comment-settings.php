<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Action;

class WP_POST_COMMENT_SETTINGS extends Action {

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'WP' );
		$this->set_action_code( 'WP_POST_COMMENTS' );
		$this->set_action_meta( 'WP_POSTS' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( '{{Enable/disable:%2$s}} comments on {{a post:%1$s}}', 'WordPress', 'uncanny-automator-pro' ), $this->get_action_meta(), 'WP_COMMENT_STATUS:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{Enable/disable}} comments on {{a post}}', 'WordPress', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array
	 */
	public function options() {

		return array(
			array(
				'option_code' => $this->get_action_meta(),
				'input_type'  => 'int',
				'label'       => esc_attr_x( 'Post ID', 'WordPress', 'uncanny-automator-pro' ),
				'required'    => true,
			),
			array(
				'option_code' => 'WP_COMMENT_STATUS',
				'input_type'  => 'select',
				'label'       => esc_attr_x( 'Comment status', 'WordPress', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => array(
					array(
						'value' => 'open',
						'text'  => esc_attr_x( 'Enable', 'WordPress', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'closed',
						'text'  => esc_attr_x( 'Disable', 'WordPress', 'uncanny-automator-pro' ),
					),
				),
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
		$post_id             = absint( $parsed[ $this->get_action_meta() ] );
		$post_comment_status = sanitize_text_field( $parsed['WP_COMMENT_STATUS'] );

		if ( is_null( get_post( $post_id ) ) ) {
			$this->add_log_error( esc_attr_x( 'Invalid post ID.', 'WordPress', 'uncanny-automator-pro' ) );

			return false;
		}

		$post_data    = array(
			'ID'             => $post_id,
			'comment_status' => $post_comment_status,
		);
		$post_updated = wp_update_post( $post_data, true );

		if ( is_wp_error( $post_updated ) ) {
			$message = $post_updated->get_error_message();

			$this->add_log_error( sprintf( esc_attr_x( '(%s)', 'WordPress', 'uncanny-automator-pro' ), $message ) );

			return false;
		}

		$this->hydrate_tokens(
			array(
				$this->get_action_meta() => get_the_title( $post_id ),
				'WP_COMMENT_STATUS'      => ( $post_comment_status === 'open' ) ? 'Enabled' : 'Disabled',
			)
		);

		return true;
	}

}
