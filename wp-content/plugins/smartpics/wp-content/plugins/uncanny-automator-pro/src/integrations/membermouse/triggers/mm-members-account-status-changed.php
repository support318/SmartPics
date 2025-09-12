<?php

namespace Uncanny_Automator_Pro\Integrations\MemberMouse;

/**
 * Class MM_MEMBERS_ACCOUNT_STATUS_CHANGED
 * @package Uncanny_Automator_Pro
 */
class MM_MEMBERS_ACCOUNT_STATUS_CHANGED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_is_pro( true );
		$this->set_integration( 'MEMBER_MOUSE' );
		$this->set_trigger_code( 'MM_ACCOUNT_STATUS_CHANGED' );
		$this->set_trigger_meta( 'MM_MEMBER_STATUS' );
		$this->set_sentence( sprintf( esc_attr_x( "A member's account status is changed to {{a different status:%1\$s}}", 'MemberMouse', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( "A member's account status is changed to {{a different status}}", 'MemberMouse', 'uncanny-automator-pro' ) );
		$this->add_action( 'mm_member_status_change', 10, 1 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => _x( 'Status', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
				'token_name'  => 'Selected status',
				'options'     => $this->helpers->get_all_statuses( true ),
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

		$selected_status_id = $trigger['meta'][ $this->get_trigger_meta() ];

		return ( intval( '-1' ) === intval( $selected_status_id ) || str_replace( '_', ' ', strtolower( $selected_status_id ) ) === strtolower( $hook_args[0]['status_name'] ) );
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
		$tokens          = $this->helpers->parse_mm_token_values( $member_data );

		return array_merge( $specific_tokens, $tokens );
	}

}
