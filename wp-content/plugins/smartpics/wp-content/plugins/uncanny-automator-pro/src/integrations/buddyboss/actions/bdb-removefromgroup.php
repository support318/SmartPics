<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_REMOVEFROMGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_REMOVEFROMGROUP {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	private $action_code;
	private $action_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BDBREMOVEFROMGROUP';
		$this->action_meta = 'BDBREMOVEGROUPS';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddyboss/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( __( 'Remove user from {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => __( 'Remove user from {{a group}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'remove_from_bp_group' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$bp_group_args = array(
			'uo_include_any' => true,
			'uo_any_label'   => __( 'All groups', 'uncanny-automator' ),
			'status'         => array( 'public', 'hidden', 'private' ),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups( null, $this->action_meta, $bp_group_args ),
				),
			)
		);
	}

	/**
	 * Remove from BP Groups
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @since 1.1
	 * @return void
	 *
	 */
	public function remove_from_bp_group( $user_id, $action_data, $recipe_id, $args ) {

		$remove_from_bp_group = $action_data['meta'][ $this->action_meta ];
		if ( $remove_from_bp_group === '-1' ) {
			$all_user_groups = groups_get_user_groups( $user_id );
			if ( ! empty( $all_user_groups['groups'] ) ) {
				foreach ( $all_user_groups['groups'] as $group ) {
					$result = groups_leave_group( $group, $user_id );
				}
			}
		} else {
			groups_leave_group( $remove_from_bp_group, $user_id );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
