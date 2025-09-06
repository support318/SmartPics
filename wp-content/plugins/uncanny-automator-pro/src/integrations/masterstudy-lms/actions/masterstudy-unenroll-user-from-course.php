<?php

namespace Uncanny_Automator_Pro;

class MASTERSTUDY_UNENROLL_USER_FROM_COURSE extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {
		$args    = array(
			'post_type'      => 'stm-courses',
			'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);
		$options = array();
		$courses = get_posts( $args );
		foreach ( $courses as $course ) {
			$options[] = array(
				'text'  => $course->post_title,
				'value' => $course->ID,
			);
		}

		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'     => $this->get_action_meta(),
					'label'           => esc_html_x( 'Course', 'MasterStudy LMS', 'uncanny-automator-pro' ),
					'relevant_tokens' => array(),
					'options'         => $options,
				)
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'COURSE_ID'    => array(
				'name' => esc_html_x( 'Course ID', 'MasterStudy LMS', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'COURSE_TITLE' => array(
				'name' => esc_html_x( 'Course title', 'MasterStudy LMS', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'MSLMS' );
		$this->set_action_code( 'MSLMS_UNENROLL_USER' );
		$this->set_action_meta( 'MSLMS_COURSES' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %s is a course title
			sprintf( esc_attr_x( 'Unenroll the user from {{a course:%1$s}}', 'MasterStudy LMS', 'uncanny-automator-pro' ), $this->get_action_meta(), 'EXPIRATION_DATE:' . $this->get_action_meta() )
		);
		$this->set_readable_sentence( esc_attr_x( 'Unenroll the user from {{a course}}', 'MasterStudy LMS', 'uncanny-automator-pro' ) );
	}

	/**
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$course_id = sanitize_text_field( $parsed[ $this->get_action_meta() ] );

		if ( empty( $course_id ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a valid course ID.', 'MasterStudy LMS', 'uncanny-automator' ) );

			return false;
		}

		if ( ! class_exists( '\STM_LMS_Course' ) ) {
			$this->add_log_error( esc_attr_x( '"STM_LMS_Course" class not found.', 'MasterStudy LMS', 'uncanny-automator' ) );

			return false;
		}

		if ( ! class_exists( '\STM_LMS_Helpers' ) ) {
			$this->add_log_error( esc_attr_x( '"STM_LMS_Helpers" class not found.', 'MasterStudy LMS', 'uncanny-automator' ) );

			return false;
		}

		$is_enrolled = stm_lms_get_user_course( $user_id, $course_id );
		if ( empty( $is_enrolled ) ) {
			// translators: %d is a course ID
			$this->add_log_error( sprintf( esc_attr_x( 'The user is not enrolled into a course (%d).', 'MasterStudy LMS', 'uncanny-automator' ), $course_id ) );

			return false;
		}

		$authors   = array();
		$authors[] = get_post_field( 'post_author', $course_id, true );
		$authors[] = get_post_meta( $course_id, 'co_instructor', true );
		if ( in_array( $user_id, $authors, true ) ) {
			$this->add_log_error( esc_attr_x( 'Author can not be removed from a course.', 'MasterStudy LMS', 'uncanny-automator' ) );

			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! empty( $user->user_email ) && is_ms_lms_addon_enabled( 'coming_soon' ) ) {
			$coming_soon_emails      = get_post_meta( $course_id, 'coming_soon_student_emails', true ) ?? array();
			$unsubscribe_email_index = array_search( $user->user_email, array_column( $coming_soon_emails, 'email' ), true );

			unset( $coming_soon_emails[ $unsubscribe_email_index ] );
			update_post_meta( $course_id, 'coming_soon_student_emails', array_values( $coming_soon_emails ) );
		}

		stm_lms_get_delete_user_course( $user_id, $course_id );
		$meta = \STM_LMS_Helpers::parse_meta_field( $course_id );

		if ( ! empty( $meta['current_students'] ) && $meta['current_students'] > 0 ) {
			update_post_meta( $course_id, 'current_students', --$meta['current_students'] );
		}

		$this->hydrate_tokens(
			array(
				'COURSE_ID'    => $course_id,
				'COURSE_TITLE' => get_the_title( $course_id ),
			)
		);

		return true;
	}
}
