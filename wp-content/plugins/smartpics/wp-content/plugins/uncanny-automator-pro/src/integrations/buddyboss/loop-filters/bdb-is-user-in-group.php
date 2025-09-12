<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Loop filter - The user {is/is not} in {a group}
 * Class BDB_IS_USER_IN_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_IS_USER_IN_GROUP extends Loop_Filter {


	public function setup() {
		$this->set_integration( 'BDB' );
		$this->set_meta( 'BDB_IS_USER_IN_GROUP' );
		$this->set_sentence( esc_html_x( 'The user {{is/is not}} in {{a group}}', 'Filter sentence', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'The user {{is/is not:%1$s}} in {{a group:%2$s}}', 'Filter sentence', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_in_group' ) );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {

		$groups_option = Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups(
			null,
			'',
			array(
				'uo_include_any' => true,
				'status'         => array( 'public', 'hidden', 'private' ),
			)
		);

		$options = array();

		foreach ( $groups_option['options'] as $id => $value ) {
			$options[] = array(
				'text'  => esc_attr( $value ),
				'value' => esc_attr( $id ),
			);
		}

		return array(
			$this->get_meta() => array(
				array(
					'option_code'            => 'CRITERIA',
					'type'                   => 'select',
					'supports_custom_value'  => false,
					'show_label_in_sentence' => false,
					'options_show_id'        => false,
					'label'                  => esc_html_x( 'Criteria', 'BuddyBoss', 'uncanny-automator-pro' ),
					'options'                => array(
						array(
							'text'  => esc_html_x( 'is', 'BuddyBoss', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'is', 'BuddyBoss', 'uncanny-automator-pro' ),
						),
						array(
							'text'  => esc_html_x( 'is not', 'BuddyBoss', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'is-not', 'BuddyBoss', 'uncanny-automator-pro' ),
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Group', 'BuddyBoss', 'uncanny-automator-pro' ),
					'options'               => $options,
				),
			),
		);

	}

	/**
	 * @param array{BDB_IS_USER_IN_GROUP:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_in_group( $fields ) {

		$criteria = $fields['CRITERIA'];
		$group    = $fields['BDB_IS_USER_IN_GROUP'];

		if ( empty( $criteria ) || empty( $group ) ) {
			return array();
		}

		global $wpdb;
		$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$wpdb->prefix}bp_groups_members" );
		if ( intval( '-1' ) !== intval( $group ) ) {
			$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->prefix}bp_groups_members WHERE group_id = %d", absint( $group ) ) );
		}

		$users = $user_ids;
		if ( 'is-not' === $criteria ) {
			$all_users    = new \WP_User_Query( array( 'fields' => 'ids' ) );
			$all_user_ids = $all_users->get_results();
			$users        = array_diff( $all_user_ids, $user_ids );
		}

		return ! empty( $users ) ? $users : array();

	}
}
