<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_REMOVEFROMGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BP_REMOVEFROMGROUP {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	private $action_code;
	private $action_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BPREMOVEFROMGROUP';
		$this->action_meta = 'BPREMOVEGROUPS';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddypress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyPress */
			'sentence'           => sprintf( esc_html_x( 'Remove user from {{a group:%1$s}}', 'BuddyPress', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyPress */
			'select_option_name' => esc_html_x( 'Remove user from {{a group}}', 'BuddyPress', 'uncanny-automator-pro' ),
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
			'uo_any_label'   => esc_html_x( 'All groups', 'BuddyPress', 'uncanny-automator' ),
			'status'         => array( 'public', 'hidden', 'private' ),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddypress->options->all_buddypress_groups( null, $this->action_meta, $bp_group_args ),
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
	 * @return void
	 *
	 * @since 1.1
	 */
	public function remove_from_bp_group( $user_id, $action_data, $recipe_id, $args ) {
		$all_user_groups = groups_get_user_groups( $user_id );

		if ( 0 === $all_user_groups['total'] ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_html_x( 'The user is not a member of any group.', 'BuddyPress', 'uncanny-automator-pro' ) );

			return;
		}

		$remove_from_bp_group = $action_data['meta'][ $this->action_meta ];

		if ( intval( '-1' ) !== intval( $remove_from_bp_group ) && ! in_array( absint( $remove_from_bp_group ), $all_user_groups['groups'], true ) ) {
			$action_data['complete_with_errors'] = true;
			// translators: Error message if user is not in selected group
			Automator()->complete->action( $user_id, $action_data, $recipe_id, sprintf( esc_html_x( 'The user is not a member of selected group (%s).', 'BuddyPress', 'uncanny-automator-pro' ), bp_get_group_name( absint( $remove_from_bp_group ) ) ) );

			return;
		}

		if ( intval( '-1' ) === intval( $remove_from_bp_group ) ) {
			foreach ( $all_user_groups['groups'] as $group ) {
				groups_leave_group( absint( $group ), $user_id );
			}
		} else {
			groups_leave_group( absint( $remove_from_bp_group ), $user_id );
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
