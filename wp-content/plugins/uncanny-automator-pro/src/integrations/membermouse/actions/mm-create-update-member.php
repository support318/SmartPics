<?php

namespace Uncanny_Automator_Pro\Integrations\MemberMouse;

class MM_CREATE_UPDATE_MEMBER extends \Uncanny_Automator\Recipe\Action {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'MEMBER_MOUSE' );
		$this->set_action_code( 'MM_CREATE_UPDATE_MEMBER' );
		$this->set_action_meta( 'MM_MEMBER' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Create or update {{a member:%1$s}}', 'MemberMouse', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create or update {{a member}}', 'MemberMouse', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return array
	 */
	public function options() {

		return array(
			array(
				'input_type'            => 'select',
				'option_code'           => 'MM_MEMBER_MEMBERSHIP_LEVEL',
				'label'                 => _x( 'Membership', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'              => true,
				'supports_custom_value' => false,
				'options'               => $this->helpers->get_all_membership_levels(),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'MM_MEMBER_FIRSTNAME',
				'label'       => _x( 'First name', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'MM_MEMBER_LASTNAME',
				'label'       => _x( 'Last name', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
			),
			array(
				'input_type'  => 'email',
				'option_code' => 'MM_MEMBER_EMAIL',
				'label'       => _x( 'Email', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
				'description' => _x( 'If the entered email is already associated with an existing member, this action will update their info.', 'MemberMouse', 'uncanny-automator-pro' ),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'MM_MEMBER_PHONE',
				'label'       => _x( 'Phone', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
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
		$member_data['mm_new_membership'] = isset( $parsed['MM_MEMBER_MEMBERSHIP_LEVEL'] ) ? absint( sanitize_text_field( $parsed['MM_MEMBER_MEMBERSHIP_LEVEL'] ) ) : '';
		$member_data['mm_new_email']      = isset( $parsed['MM_MEMBER_EMAIL'] ) ? sanitize_email( $parsed['MM_MEMBER_EMAIL'] ) : '';
		$member_data['mm_new_first_name'] = isset( $parsed['MM_MEMBER_FIRSTNAME'] ) ? sanitize_text_field( $parsed['MM_MEMBER_FIRSTNAME'] ) : '';
		$member_data['mm_new_last_name']  = isset( $parsed['MM_MEMBER_LASTNAME'] ) ? sanitize_text_field( $parsed['MM_MEMBER_LASTNAME'] ) : '';
		$member_data['mm_new_phone']      = isset( $parsed['MM_MEMBER_PHONE'] ) ? sanitize_text_field( $parsed['MM_MEMBER_PHONE'] ) : '';

		$member_views   = new \MM_MembersView();
		$member_details = $member_views->createMember( $member_data );
		if ( $member_details->type === 'error' ) {
			$this->add_log_error( $member_details->message );

			return false;
		}

		return true;
	}

}
