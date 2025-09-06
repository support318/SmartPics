<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_FETCH_AN_EXISTING_USER
 *
 * @package Uncanny_Automator_Pro
 */
class WP_FETCH_AN_EXISTING_USER extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'WP' );
		$this->set_action_code( 'WP_FETCH_USER' );
		$this->set_action_meta( 'WP_USER' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Fetch {{an existing user:%1$s}}', 'WordPress', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Fetch {{an existing user}}', 'WordPress', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {
		$options = array(
			array(
				'text'  => esc_attr_x( 'Email', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'email',
			),
			array(
				'text'  => esc_attr_x( 'Username', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'login',
			),
			array(
				'text'  => esc_attr_x( 'User ID', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'ID',
			),
		);

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => 'LOOKUP_FIELD',
				'label'           => _x( 'Lookup field', 'WordPress', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $options,
				'default'         => 'email',
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_action_meta(),
				'label'           => _x( 'User', 'WordPress', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'USER_ID'                => array(
				'name' => _x( 'User ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'USERNAME'               => array(
				'name' => _x( 'Username', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_EMAIL'             => array(
				'name' => _x( 'Email', 'uncanny-automator-pro' ),
				'type' => 'email',
			),
			'USER_FIRST_NAME'        => array(
				'name' => _x( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_LAST_NAME'         => array(
				'name' => _x( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_DISPLAY_NAME'      => array(
				'name' => _x( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_ROLE'              => array(
				'name' => _x( 'User role', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_REGISTRATION_DATE' => array(
				'name' => _x( 'Registration date', 'uncanny-automator-pro' ),
				'type' => 'date',
			),
		);
	}

	/**
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$field = sanitize_text_field( $parsed['LOOKUP_FIELD'] );
		$value = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		if ( 'email' === $field ) {
			$value = sanitize_email( $parsed[ $this->get_action_meta() ] );
		}

		if ( empty( $value ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a value to fetch the user.', 'WordPress', 'uncanny-automator-pro' ) );

			return false;
		}

		$user = get_user_by( $field, $value );

		if ( false === $user ) {
			$this->add_log_error( sprintf( esc_attr_x( 'No user matching %1$s: %2$s was found.', 'WordPress', 'uncanny-automator-pro' ), $field, $value ) );

			return false;
		}

		$this->hydrate_tokens(
			array(
				'USER_ID'                => $user->ID,
				'USERNAME'               => $user->user_login,
				'USER_EMAIL'             => $user->user_email,
				'USER_FIRST_NAME'        => $user->first_name,
				'USER_LAST_NAME'         => $user->last_name,
				'USER_DISPLAY_NAME'      => $user->display_name,
				'USER_ROLE'              => join( ', ', $user->roles ),
				'USER_REGISTRATION_DATE' => date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ),
			)
		);

		return true;
	}

}
