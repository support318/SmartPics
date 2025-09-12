<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class LD_HAS_USER_COMPLETED_A_COURSE
 *
 * @package Uncanny_Automator_Pro
 */
class LD_HAS_USER_COMPLETED_A_COURSE extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'LD' );
		$this->set_meta( 'LD_HAS_USER_COMPLETED_A_COURSE' );
		$this->set_sentence( esc_html_x( 'The user {{has/has not}} completed {{a course}}', 'LearnDash course', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'The user {{has/has not:%1$s}} completed {{a course:%2$s}}', 'LearnDash course', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_in_completed_course' ) );
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
					'label'                 => esc_html_x( 'Course', 'LearnDash', 'uncanny-automator-pro' ),
					'options'               => $this->get_courses(),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @return bool
	 */
	protected function is_dependency_active() {
		return defined( 'LEARNDASH_VERSION' );
	}

	/**
	 * @return array{array{text:string,value:string}}|array{}
	 */
	protected function get_courses() {

		$options = array();

		$all_courses = Automator()->helpers->recipe->learndash->options->all_ld_courses();

		foreach ( $all_courses['options'] as $course_id => $course ) {
			$options[] = array(
				'text'  => esc_attr( $course ),
				'value' => esc_attr( $course_id ),
			);
		}

		return $options;

	}

	/**
	 * @param array{LD_HAS_USER_COMPLETED_A_COURSE:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_in_completed_course( $fields ) {

		$criteria = $fields['CRITERIA'];
		$course   = $fields['LD_HAS_USER_COMPLETED_A_COURSE'];

		if ( empty( $criteria ) || empty( $course ) ) {
			return array();
		}

		global $wpdb;

		$meta_key = 'course_completed_' . $course;
		if ( intval( '-1' ) === intval( $course ) ) {
			$meta_key = 'course_completed_%%';
		}

		$user_ids_completed_a_course = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE meta_key LIKE %s", $meta_key ) );

		$user_ids = $user_ids_completed_a_course;

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
			$user_ids     = array_diff( $all_user_ids, $user_ids_completed_a_course );
		}

		return ! empty( $user_ids ) ? array_map( 'absint', $user_ids ) : array();

	}
}
