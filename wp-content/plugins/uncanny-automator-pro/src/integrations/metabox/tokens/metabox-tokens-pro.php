<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Uncanny_Automator_Pro\Metabox_Tokens_Pro
 *
 * @since 4.3.0
 * @package Uncanny_Automator_Pro\Metabox_Tokens_Pro
 */
class Metabox_Tokens_Pro {


	/**
	 * The common tokens. A callback method in each trigger via $this->set_tokens()
	 *
	 * @return array[] The list of tokens where array key is the token identifier.
	 */
	public function common_tokens() {

		return array(
			'FIELD_NAME'  => array(
				'name'         => __( 'Meta Box field name', 'uncanny-automator-pro' ),
				'hydrate_with' => 'trigger_args|2',
			),
			'FIELD_VALUE' => array(
				'name'         => __( 'Meta Box field value', 'uncanny-automator-pro' ),
				'hydrate_with' => 'trigger_args|3',
			),
		);

	}

	public function user_tokens() {

		return array(
			'DESCRIPTION'  => array(
				'name' => __( "Updated user's biographical info", 'uncanny-automator-pro' ),
			),
			'DISPLAY_NAME' => array(
				'name' => __( "Updated user's display name", 'uncanny-automator-pro' ),
			),
			'USER_EMAIL'   => array(
				'name' => __( "Updated user's email", 'uncanny-automator-pro' ),
			),
			'FIRST_NAME'   => array(
				'name' => __( "Updated user's first name", 'uncanny-automator-pro' ),
			),
			'ID'           => array(
				'name'         => __( "Updated user's ID", 'uncanny-automator-pro' ),
				'hydrate_with' => 'trigger_args|1',
			),
			'LAST_NAME'    => array(
				'name' => __( "Updated user's last name", 'uncanny-automator-pro' ),
			),
			'NICKNAME'     => array(
				'name' => __( "Updated user's nickname", 'uncanny-automator-pro' ),
			),
			'USERNAME'     => array(
				'name' => __( "Updated user's username", 'uncanny-automator-pro' ),
			),
		);

	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_tokens( $parsed, $args, $trigger ) {

		$post_id = $args['trigger_args'][1];

		return $parsed + array(
			'POST_TYPE'           => get_the_title( $post_id ),
			'POST_TYPE_ID'        => absint( $post_id ),
			'POST_TYPE_URL'       => get_permalink( $post_id ),
			'POST_TYPE_THUMB_ID'  => get_post_thumbnail_id( $post_id ),
			'POST_TYPE_THUMB_URL' => get_the_post_thumbnail_url( $post_id ),
		);

	}

	/**
	 * Populate the user token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_tokens( $parsed, $args, $trigger ) {

		$user_id = $args['trigger_args'][1];

		return $parsed + array(
			'DISPLAY_NAME' => $this->get_user_info( 'user_data', 'display_name', $user_id ),
			'USER_EMAIL'   => $this->get_user_info( 'user_data', 'user_email', $user_id ),
			'DESCRIPTION'  => $this->get_user_info( 'meta', 'description', $user_id ),
			'FIRST_NAME'   => $this->get_user_info( 'meta', 'first_name', $user_id ),
			'LAST_NAME'    => $this->get_user_info( 'meta', 'last_name', $user_id ),
			'NICKNAME'     => $this->get_user_info( 'meta', 'nickname', $user_id ),
			'USERNAME'     => $this->get_user_info( 'user_data', 'user_login', $user_id ),
		);
	}

	public function get_user_info( $type = '', $key = '', $user_id = 0 ) {

		if ( 'meta' === $type ) {
			return get_user_meta( $user_id, $key, true );
		}

		$user_data = get_userdata( $user_id );

		if ( empty( $user_data ) ) {
			return '';
		}

		return isset( $user_data->data->$key ) ? sanitize_text_field( $user_data->data->$key ) : '';

	}

}
