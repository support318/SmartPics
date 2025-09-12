<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class LD_IS_USER_ENROLLED_IN_COURSE
 *
 * @package Uncanny_Automator_Pro
 */
class LD_IS_USER_ENROLLED_IN_COURSE extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'LD' );
		$this->set_meta( 'LD_IS_USER_ENROLLED_IN_COURSE' );
		$this->set_sentence( esc_html_x( 'The user {{is/is not}} enrolled in {{a specific course}}', 'LearnDash filter sentence', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'The user {{is/is not:%1$s}} enrolled in {{a specific course:%2$s}}', 'LearnDash filter sentence', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_in_a_course' ) );
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
					'label'                 => esc_html_x( 'Criteria', 'LearnDash', 'uncanny-automator-pro' ),
					'options'               => array(
						array(
							'text'  => esc_html_x( 'is', 'LearnDash', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'is', 'LearnDash', 'uncanny-automator-pro' ),
						),
						array(
							'text'  => esc_html_x( 'is not', 'LearnDash', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'is-not', 'LearnDash', 'uncanny-automator-pro' ),
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Course', 'LearnDash', 'uncanny-automator-pro' ),
					'options'               => $this->get_all_courses(),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @return array{array{text:string,value:string}}|array{}
	 */
	protected function get_all_courses() {

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
	 * @param array{LD_IS_USER_ENROLLED_IN_COURSE:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_in_a_course( $fields ) {

		$criteria = $fields['CRITERIA'];
		$course   = $fields['LD_IS_USER_ENROLLED_IN_COURSE'];

		if ( empty( $criteria ) || empty( $course ) ) {
			return array();
		}

		$user_ids_enrolled_in_course = array();

		if ( intval( '-1' ) === intval( $course ) ) {
			$users_in_course = array();
			global $wpdb;
			$all_courses = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_status = %s", 'publish' ), ARRAY_A );
			foreach ( $all_courses as $course_id ) {
				$users_in_course_query = learndash_get_users_for_course( $course_id, array( 'cache_results' => false ), false );
				$users_in_course[]     = $users_in_course_query->get_results();
			}
			$user_ids_enrolled_in_course = array_unique( $users_in_course );
		} elseif ( intval( '-1' ) !== intval( $course ) ) {
			$users_in_course_query       = learndash_get_users_for_course( $course, array( 'cache_results' => false ), false );
			$user_ids_enrolled_in_course = $users_in_course_query->get_results();
		}

		$user_ids = $user_ids_enrolled_in_course;

		if ( 'is-not' === $criteria ) {
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
			$user_ids     = array_diff( $all_user_ids, $user_ids_enrolled_in_course );
		}

		return ! empty( $user_ids ) ? $user_ids : array();

	}
}
