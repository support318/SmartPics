<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_USER_LEADS_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USER_LEADS_GROUP extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {
		$this->integration = 'LD';
		/*translators: Token */
		$this->name = __( 'The user {{is/is not}} a leader of {{a group}}', 'uncanny-automator-pro' );
		$this->code = 'LEADER_OF_GROUP';
		// translators: %1$s is the criteria and %2$s is the group
		$this->dynamic_name  = sprintf( esc_html__( 'The user {{is/is not:%1$s}} a leader of {{a group:%2$s}}', 'uncanny-automator-pro' ), 'CRITERIA', 'GROUP' );
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * Fields
	 *
	 * @return array
	 */
	public function fields() {

		$criteria_args = array(
			'option_code'           => 'CRITERIA',
			'label'                 => esc_html__( 'Criteria', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => array(
				array(
					'value' => 'is',
					'text'  => esc_html_x( 'is', 'Filter', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is-not',
					'text'  => esc_html_x( 'is not', 'Filter', 'uncanny-automator-pro' ),
				),
			),
			'supports_custom_value' => false,
		);

		$groups_field_args = array(
			'option_code'           => 'GROUP',
			'label'                 => esc_html__( 'Group', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->ld_groups_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Criteria field
			$this->field->select_field_args( $criteria_args ),
			// Course field
			$this->field->select_field_args( $groups_field_args ),
		);
	}

	/**
	 * Load options
	 *
	 * @return array[]
	 */
	public function ld_groups_options() {
		$args      = array(
			'post_type'      => 'groups',
			'posts_per_page' => 9999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);
		$ld_groups = array(
			array(
				'value' => '-1',
				'text'  => esc_html__( 'Any group', 'uncanny-automator-pro' ),
			),
		);
		$groups    = Automator()->helpers->recipe->options->wp_query( $args, false, false );
		if ( empty( $groups ) ) {
			return array();
		}
		foreach ( $groups as $group_id => $group_title ) {
			$ld_groups[] = array(
				'value' => $group_id,
				'text'  => $group_title,
			);
		}

		return $ld_groups;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$group_ids   = learndash_get_administrators_group_ids( $this->user_id );
		$criteria    = $this->get_parsed_option( 'CRITERIA' );
		$group       = $this->get_parsed_option( 'GROUP' );
		$is_any      = intval( '-1' ) === intval( $group );
		$leads_group = $is_any ? ! empty( $group_ids ) : array_intersect( $group_ids, array( $group ) );

		// Any group.
		if ( $is_any ) {

			// Is the leader of any group.
			if ( 'is' === $criteria ) {
				// No groups.
				if ( empty( $group_ids ) ) {
					$message = __( 'User is not a leader of any group.', 'uncanny-automator-pro' );
					$this->condition_failed( $message );
				}
			}

			// Is not the leader of any group.
			if ( 'is-not' === $criteria ) {
				if ( ! empty( $group_ids ) ) {
					$message = __( 'User is a leader of a group.', 'uncanny-automator-pro' );
					$this->condition_failed( $message );
				}
			}
		}

		// Specific group.
		if ( ! $is_any ) {

			// Is the leader of the group.
			if ( 'is' === $criteria ) {

				// No groups.
				if ( empty( $group_ids ) ) {
					$message = __( 'User is not a leader of any group.', 'uncanny-automator-pro' );
					$this->condition_failed( $message );
				} else {

					// Does not lead specific group.
					if ( empty( $leads_group ) ) {
						$message = __( 'User is not a leader of ', 'uncanny-automator-pro' ) . $this->get_option( 'GROUP_readable' );
						$this->condition_failed( $message );
					}
				}
			}

			// Is not the leader of the group.
			if ( 'is-not' === $criteria ) {

				// Leads specific group.
				if ( ! empty( $leads_group ) ) {
					$message = __( 'User is a leader of ', 'uncanny-automator-pro' ) . $this->get_option( 'GROUP_readable' );
					$this->condition_failed( $message );
				}
			}
		}
	}
}
