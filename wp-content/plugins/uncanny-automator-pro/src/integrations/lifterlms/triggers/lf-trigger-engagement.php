<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LF_TRIGGER_ENGAGEMENT
 *
 * @package Uncanny_Automator_Pro
 */
class LF_TRIGGER_ENGAGEMENT extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'LF' );
		$this->set_trigger_code( 'LF_TRIGGER_AN_ENGAGEMENT' );
		$this->set_trigger_meta( 'LF_ENGAGEMENTS' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'A user triggers {{an engagement:%1$s}}', 'LifterLMS', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A user triggers {{an engagement}}', 'LifterLMS', 'uncanny-automator-pro' ) );
		$this->add_action(
			array(
				'lifterlms_engagement_send_email',
				'lifterlms_engagement_award_achievement',
				'lifterlms_engagement_award_certificate',
			),
			20,
			1
		);
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Engagement', 'LifterLMS', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => Automator()->helpers->recipe->options->lifterlms->pro->get_all_lf_engagements( true ),
				'relevant_tokens' => array(),
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

		$selected_engagement_id = $trigger['meta'][ $this->get_trigger_meta() ];

		list( $user_id, $generated_id, $related_id, $engagement_id ) = $hook_args[0];

		$this->set_user_id( $user_id );

		if ( intval( '-1' ) !== intval( $selected_engagement_id ) && absint( $selected_engagement_id ) !== absint( $engagement_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $trigger
	 * @param $tokens
	 *
	 * @return array|void
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens = array(
			array(
				'tokenId'   => 'LF_ENGAGEMENT_ID',
				'tokenName' => __( 'Engagement ID', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'LF_ENGAGEMENT',
				'tokenName' => __( 'Engagement title', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $tokens, $trigger_tokens );
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

		list( $user_id, $generated_id, $related_id, $engagement_id ) = $hook_args[0];

		$token_values = array(
			'LF_ENGAGEMENT'    => get_the_title( $engagement_id ),
			'LF_ENGAGEMENT_ID' => $engagement_id,
		);

		return $token_values;
	}
}
