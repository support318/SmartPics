<?php

namespace Uncanny_Automator_Pro\Integrations\M4IS;

/**
 * Class M4IS_ADD_TAG_CONTACT
 *
 * @package Uncanny_Automator_Pro
 */
class M4IS_USER_MEMBERSHIP_ACCESS extends \Uncanny_Automator_Pro\Action_Condition {

	/**
	 * Define and register the condition by pushing it into the Automator object.
	 */
	public function define_condition() {

		$this->integration  = 'M4IS';
		$this->name         = _x( 'The user {{Has / Does not have}} {{a Membership level}}', 'M4IS - Membership access condition', 'uncanny-automator-pro' );
		$this->code         = 'MEMBERSHIP_ACCESS';
		$this->dynamic_name = sprintf(
			/* translators: 1. as/Doesn't have 2. Membership Level */
			esc_attr_x( 'The user {{Has / Does not have:%1$s}} {{a Membership level:%2$s}}', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
			'HAS_DOESNT_HAVE',
			'MEMBERSHIP_LEVEL'
		);
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		$conditions = array(
			'option_code'           => 'HAS_DOESNT_HAVE',
			'label'                 => esc_attr_x( 'Criteria', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => array(
				array(
					'value' => 'has',
					'text'  => esc_attr_x( 'Has', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'does_not_have',
					'text'  => esc_attr_x( 'Does not have', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
				),
			),
			'supports_custom_value' => false,
		);

		$memberships = array(
			'option_code'           => 'MEMBERSHIP_LEVEL',
			'label'                 => esc_attr_x( 'Membership level', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_helpers()->get_membership_level_options( true ),
			'supports_custom_value' => false,
		);

		return array(
			// Has or doesn't have select.
			$this->field->select_field_args( $conditions ),
			// Membership level select.
			$this->field->select_field_args( $memberships ),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {

		$criteria = $this->get_parsed_option( 'HAS_DOESNT_HAVE' );
		$level    = $this->get_parsed_option( 'MEMBERSHIP_LEVEL' );

		// Get the WP user object as $this->user is not always the current user
		$user_data = get_userdata( $this->user_id );
		// Get the user email
		$user_email = mb_strtolower( $user_data->user_email );

		// Check if the user has the membership level.
		$has_membership = $this->get_helpers()->has_membership_level( $user_email, $level );
		if ( is_wp_error( $has_membership ) ) {
			// Pass the error to the condition_failed method
			$this->condition_failed( $has_membership->get_error_message() );
			return;
		}

		// Check conditions.
		$is_any = intval( '-1' ) === intval( $level );
		$failed = false;

		// If the user has the membership level and the criteria is "does not have"
		if ( $has_membership && 'does_not_have' === $criteria ) {
			$failed = $is_any ? 'has_any_membership' : 'has_membership';
		}

		// If the user doesn't have the membership level and the criteria is "has"
		if ( ! $has_membership && 'has' === $criteria ) {
			$failed = $is_any ? 'does_not_have_any_memberships' : 'does_not_have_membership';
		}

		// Condition is not met.
		if ( $failed ) {
			// Pass the error to the condition_failed method
			$this->condition_failed( $this->get_failed_message( $failed, $user_email, $level ) );
			return;
		}

		// If the condition is met, do nothing and let the action run.
	}

	/**
	 * Get error message.
	 *
	 * @param string $key
	 * @param string $email
	 * @param int $level
	 *
	 * @return string
	 */
	public function get_failed_message( $key, $email, $level ) {
		switch ( $key ) {
			case 'has_any_membership':
				return sprintf(
					/* translators: %s User email */
					_x( 'User "%s" has a membership', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
					$email
				);
			case 'has_membership':
				return sprintf(
					/* translators: %1$s User email, %2$s Membership */
					_x( 'User "%1$s" has "%2$s" membership', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
					$email,
					$this->get_helpers()->get_membership_level_name( $level )
				);
			case 'does_not_have_any_memberships':
				return sprintf(
					/* translators: %s User email */
					_x( 'User "%s" doesn\'t have any memberships', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
					$email
				);
			case 'does_not_have_membership':
				return sprintf(
					/* translators: %1$s User email, %2$s Membership */
					_x( 'User "%1$s" doesn\'t have "%2$s" membership', 'M4IS - Membership access condition', 'uncanny-automator-pro' ),
					$email,
					$this->get_helpers()->get_membership_level_name( $level )
				);
		}
	}

	/**
	 * Get Helper Class.
	 *
	 * @return \Uncanny_Automator_Pro\Integrations\M4IS\Helpers
	 */
	public function get_helpers() {
		static $helper = null;
		if ( is_null( $helper ) ) {
			$helper = new \Uncanny_Automator_Pro\Integrations\M4IS\M4IS_HELPERS_PRO();
		}
		return $helper;
	}
}
