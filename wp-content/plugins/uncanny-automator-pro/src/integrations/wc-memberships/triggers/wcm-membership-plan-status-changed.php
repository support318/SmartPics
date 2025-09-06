<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class WCM_MEMBERSHIP_PLAN_STATUS_CHANGED
 *
 * @pacakge Uncanny_Automator
 */
class WCM_MEMBERSHIP_PLAN_STATUS_CHANGED extends Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'WCMEMBERSHIPS' );
		$this->set_trigger_code( 'WCM_STATUS_CHANGED' );
		$this->set_trigger_meta( 'WCM_MEMBERSHIP_PLANS' );
		$this->set_helper( new Wc_Memberships_Pro_Helpers() );
		$this->set_is_pro( true );
		$this->set_sentence(
			sprintf(
				// translators: 1: Membership plan, 2: Status
				esc_html_x(
					"A user's access to {{a membership plan:%1\$s}} is changed to {{a status:%2\$s}}",
					'Woo Memberships',
					'uncanny-automator-pro'
				),
				$this->get_trigger_meta(),
				'WCM_STATUS:' . $this->get_trigger_meta()
			)
		);
		$this->set_readable_sentence(
			esc_html_x(
				"A user's access to {{a membership plan}} is changed to {{a status}}",
				'Woo Memberships',
				'uncanny-automator-pro'
			)
		);
		$this->add_action( 'wc_memberships_user_membership_status_changed', 10, 3 );
	}

	/**
	 * @return array
	 */
	public function options() {
		$plans   = Automator()->helpers->recipe->wc_memberships->options->wcm_get_all_membership_plans( '', '', array( 'is_any' => true ) );
		$options = array();
		foreach ( $plans['options'] as $k => $option ) {
			$options[] = array(
				'text'  => $option,
				'value' => $k,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'Membership plan', 'Woo Memberships', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $options,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'option_code'     => 'WCM_STATUS',
				'label'           => esc_html_x( 'Status', 'Woo Memberships', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $this->get_helper()->get_all_membership_statuses(),
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $user_membership, $old_status, $new_status ) = $hook_args;

		if ( $old_status === $new_status ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $trigger['meta']['WCM_STATUS'] ) ) {
			return false;
		}

		$user_membership_id     = $user_membership->plan_id;
		$selected_membership_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_status        = str_replace( 'wcm-', '', $trigger['meta']['WCM_STATUS'] );

		return ( intval( '-1' ) === intval( $selected_membership_id ) || absint( $selected_membership_id ) === absint( $user_membership_id ) ) && ( intval( '-1' ) === intval( $selected_status ) || $selected_status === $new_status );
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
		$common_tokens = array(
			array(
				'tokenId'   => 'MEMBERSHIP_ID',
				'tokenName' => esc_html_x( 'Membership ID', 'Woo Memberships', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'MEMBERSHIP_PLAN_ID',
				'tokenName' => esc_html_x( 'Membership plan ID', 'Woo Memberships', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'MEMBERSHIP_PLAN_TITLE',
				'tokenName' => esc_html_x( 'Membership plan', 'Woo Memberships', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'MEMBERSHIP_PLAN_OLD_STATUS',
				'tokenName' => esc_html_x( 'Previous status', 'Woo Memberships', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'MEMBERSHIP_PLAN_CURRENT_STATUS',
				'tokenName' => esc_html_x( 'New status', 'Woo Memberships', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $tokens, $common_tokens );
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

		list( $user_membership, $old_status, $new_status ) = $hook_args;

		$trigger_tokens = (array) $this->get_helper()->parse_order_tokens( $user_membership );

		$tokens = array(
			'MEMBERSHIP_ID'                  => $user_membership->id,
			'MEMBERSHIP_PLAN_ID'             => $user_membership->plan_id,
			'MEMBERSHIP_PLAN_TITLE'          => $user_membership->plan->name,
			'MEMBERSHIP_PLAN_OLD_STATUS'     => $old_status,
			'MEMBERSHIP_PLAN_CURRENT_STATUS' => $new_status,
		);

		return array_merge( $trigger_tokens, $tokens );
	}
}
