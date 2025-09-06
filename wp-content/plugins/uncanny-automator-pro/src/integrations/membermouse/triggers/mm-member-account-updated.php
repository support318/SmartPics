<?php

namespace Uncanny_Automator_Pro\Integrations\MemberMouse;

/**
 * @class MM_MEMBER_ACCOUNT_UPDATED
 * @package Uncanny_Automator_Pro
 */
class MM_MEMBER_ACCOUNT_UPDATED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_is_pro( true );
		$this->set_integration( 'MEMBER_MOUSE' );
		$this->set_trigger_code( 'MM_MEMBER_ACCOUNT_UPDATED' );
		$this->set_trigger_meta( 'MM_MEMBER' );
		$this->set_sentence( sprintf( esc_attr_x( 'A member’s account data of {{a specific field:%1$s}} is updated to {{a specific value:%2$s}}', 'MemberMouse', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'SPECIFIC_VALUE:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A member’s account data of {{a specific field}} is updated to {{a specific value}}', 'MemberMouse', 'uncanny-automator-pro' ) );
		$this->add_action( 'mm_member_account_update', 10, 1 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => _x( 'Account data', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
				'token_name'  => 'Selected field',
				'options'     => $this->helpers->get_all_member_fields( true ),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'SPECIFIC_VALUE',
				'label'       => _x( 'Value', 'MemberMouse', 'uncanny-automator-pro' ),
				'required'    => true,
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $hook_args[0], $trigger['meta'][ $this->get_trigger_meta() ], $trigger['meta']['SPECIFIC_VALUE'] ) ) {
			return false;
		}

		$specific_field = $trigger['meta'][ $this->get_trigger_meta() ];
		$specific_value = $trigger['meta']['SPECIFIC_VALUE'];

		return ( ( intval( '-1' ) === intval( $specific_field ) && ( '*' ) === (string) $specific_value ) || strtolower( $hook_args[0][ $specific_field ] ) === strtolower( $specific_value ) );
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
		return array_merge( $this->helpers->get_all_member_tokens( true ), $tokens );
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
		$member_data    = $hook_args[0];
		$specific_value = array(
			'SPECIFIC_VALUE'          => $trigger['meta']['SPECIFIC_VALUE'],
			$this->get_trigger_meta() => $trigger['meta'][ $this->get_trigger_meta() . '_readable' ],
		);

		return array_merge( $this->helpers->parse_mm_token_values( $member_data ), $this->helpers->parse_custom_field_values( $member_data ), $specific_value );
	}

}
