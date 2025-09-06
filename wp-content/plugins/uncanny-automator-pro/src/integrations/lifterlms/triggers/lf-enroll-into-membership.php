<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LF_ENROLL_INTO_MEMBERSHIP
 *
 * @package Uncanny_Automator_Pro
 */
class LF_ENROLL_INTO_MEMBERSHIP extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'LF' );
		$this->set_trigger_code( 'LF_ENROLL_IN_MEMBERSHIP' );
		$this->set_trigger_meta( 'LF_MEMBERSHIPS' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'A user enrolls in {{a membership:%1$s}}', 'LifterLMS', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A user enrolls in {{a membership}}', 'LifterLMS', 'uncanny-automator-pro' ) );
		$this->add_action( 'llms_user_added_to_membership_level', 10, 2 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$options = Automator()->helpers->recipe->options->lifterlms->all_lf_memberships( '', $this->get_trigger_meta() );

		$memberships = array();
		foreach ( $options['options'] as $key => $option ) {
			$memberships[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Membership', 'LifterLMS', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $memberships,
				'relevant_tokens' => $options['relevant_tokens'],
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_membership_id          = $trigger['meta'][ $this->get_trigger_meta() ];
		list( $user_id, $membership_id ) = $hook_args;

		$this->set_user_id( $user_id );

		if ( intval( '-1' ) !== intval( $selected_membership_id ) && absint( $selected_membership_id ) !== absint( $membership_id ) ) {
			return false;
		}

		return true;
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
		list( $user_id, $membership_id ) = $hook_args;

		$token_values = array(
			'LF_MEMBERSHIPS'     => get_the_title( $membership_id ),
			'LF_MEMBERSHIPS_ID'  => $membership_id,
			'LF_MEMBERSHIPS_URL' => get_permalink( $membership_id ),
		);

		return $token_values;
	}
}
