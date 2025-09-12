<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class LD_HAS_USER_COMPLETED_ALL_COURSES_IN_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_HAS_USER_COMPLETED_ALL_COURSES_IN_GROUP extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'LD' );
		$this->set_meta( 'LD_HAS_USER_COMPLETED_ALL_COURSES_IN_GROUP' );
		$this->set_sentence( esc_html_x( 'The user {{has/has not}} completed all courses in {{a group}}', 'LearnDash group', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'The user {{has/has not:%1$s}} completed all courses in {{a group:%2$s}}', 'LearnDash group', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_in_group' ) );
	}

	/**
	 * @return bool
	 */
	protected function is_dependency_active() {
		return defined( 'LEARNDASH_VERSION' );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {

		return array(
			$this->get_meta() => array(
				array(
					'option_code'           => 'CRITERIA',
					'type'                  => 'select',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Criteria', 'Field criteria', 'uncanny-automator-pro' ),
					'options'               => array(
						array(
							'text'  => esc_html_x( 'has', 'Field criteria', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'has', 'Field criteria', 'uncanny-automator-pro' ),
						),
						array(
							'text'  => esc_html_x( 'has not', 'Field criteria', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'has-not', 'Field criteria', 'uncanny-automator-pro' ),
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Group', 'LearnDash group', 'uncanny-automator-pro' ),
					'options'               => $this->get_groups(),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @return array{array{text:string,value:string}}|array{}
	 */
	protected function get_groups() {

		$options = array();

		$all_groups = Automator()->helpers->recipe->learndash->options->all_ld_groups();

		foreach ( $all_groups['options'] as $group_id => $group ) {
			$options[] = array(
				'text'  => esc_attr( $group ),
				'value' => esc_attr( $group_id ),
			);
		}

		return $options;

	}

	/**
	 * @param array{LD_HAS_USER_COMPLETED_ALL_COURSES_IN_GROUP:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_in_group( $fields ) {

		$criteria = $fields['CRITERIA'];
		$group    = $fields['LD_HAS_USER_COMPLETED_ALL_COURSES_IN_GROUP'];

		if ( empty( $criteria ) || empty( $group ) ) {
			return array();
		}

		global $wpdb;
		$user_ids_completed_all_courses = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->prefix}learndash_user_activity WHERE activity_type = %s AND activity_completed != %d", 'group_progress', 0 ) );

		if ( intval( '-1' ) !== intval( $group ) ) {
			$user_ids_completed_all_courses = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->prefix}learndash_user_activity WHERE activity_type = %s AND activity_completed != %d AND post_id = %d", 'group_progress', 0, $group ) );
		}

		$user_ids = $user_ids_completed_all_courses;

		if ( 'has-not' === $criteria ) {
			/**
			 * @since 5.8.0.3 - Added cache_results and specified the fields return.
			 */
			$all_users    = new \WP_User_Query(
				array(
					'cache_results' => false,
					'fields'        => 'ID',
				)
			);
			$all_user_ids = $all_users->get_results();
			$user_ids     = array_diff( $all_user_ids, $user_ids_completed_all_courses );
		}

		return ! empty( $user_ids ) ? array_map( 'absint', $user_ids ) : array();
	}
}
