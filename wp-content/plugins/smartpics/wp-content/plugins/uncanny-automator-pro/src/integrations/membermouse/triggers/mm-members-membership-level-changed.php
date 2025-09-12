<?php

namespace Uncanny_Automator_Pro\Integrations\MemberMouse;

/**
 * Class MM_MEMBERS_MEMBERSHIP_LEVEL_CHANGED
 * @package Uncanny_Automator_Pro
 */
class MM_MEMBERS_MEMBERSHIP_LEVEL_CHANGED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_is_pro( true );
		$this->set_integration( 'MEMBER_MOUSE' );
		$this->set_trigger_code( 'MM_MEMBERSHIP_LEVEL_CHANGED' );
		$this->set_trigger_meta( 'MM_MEMBERSHIP_LEVEL' );
		$this->set_sentence( sprintf( esc_attr_x( "A member's membership level is changed to {{a different level:%1\$s}}", 'MemberMouse', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( "A member's membership level is changed to {{a different level}}", 'MemberMouse', 'uncanny-automator-pro' ) );
		$this->add_action( 'mm_member_membership_change', 10, 1 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => _x( 'Membership level', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
				'token_name'  => 'Selected Membership level',
				'options'     => $this->helpers->get_all_membership_levels( true ),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $hook_args[0], $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_membership_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$this->set_user_id( $hook_args[0]['member_id'] );

		return ( intval( '-1' ) === intval( $selected_membership_id ) || absint( $selected_membership_id ) === absint( $hook_args[0]['membership_level'] ) );
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array_merge( $this->helpers->get_all_member_tokens(), $tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$member_data     = $hook_args[0];
		$specific_tokens = array(
			$this->get_trigger_meta() => $trigger['meta'][ $this->get_trigger_meta() . '_readable' ],
		);

		$tokens = $this->helpers->parse_mm_token_values( $member_data );

		return array_merge( $tokens, $specific_tokens );
	}

}
