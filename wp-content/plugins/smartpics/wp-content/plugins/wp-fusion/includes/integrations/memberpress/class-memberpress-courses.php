<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles courses functionality.
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Courses {

	/**
	 * Get things started.
	 *
	 * @since 3.45.0
	 */
	public function __construct() {

		add_action( 'mpcs_completed_course', array( $this, 'course_completed' ) );
		add_action( 'mpcs_completed_lesson', array( $this, 'lesson_completed' ) );
		add_action( 'mpcs_completed_lesson', array( $this, 'quiz_progress' ) );
	}

	/**
	 * Track course completion.
	 *
	 * @since 3.41.15
	 *
	 * @param object $progress The course object.
	 */
	public function course_completed( $progress ) {
		$attrs     = $progress->get_values();
		$course_id = $attrs['course_id'];
		$settings  = (array) get_post_meta( $course_id, 'wpf-settings', true );

		if ( ! empty( $settings['apply_tags_course_complete'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_course_complete'], $attrs['user_id'] );
		}
	}

	/**
	 * Track lesson completion.
	 *
	 * @since 3.41.15
	 *
	 * @param object $progress The lesson object.
	 */
	public function lesson_completed( $progress ) {
		$attrs     = $progress->get_values();
		$lesson_id = $attrs['lesson_id'];
		if ( get_post_type( $lesson_id ) !== 'mpcs-lesson' ) {
			return;
		}

		$settings = (array) get_post_meta( $lesson_id, 'wpf-settings', true );

		if ( ! empty( $settings['apply_tags_lesson_complete'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_lesson_complete'], $attrs['user_id'] );
		}
	}

	/**
	 * Record quiz progress.
	 *
	 * @since 3.41.15
	 * @param object $progress The quiz object.
	 */
	public function quiz_progress( $progress ) {
		$attrs   = $progress->get_values();
		$quiz_id = $attrs['lesson_id'];
		if ( get_post_type( $quiz_id ) !== 'mpcs-quiz' ) {
			return;
		}

		$settings = (array) get_post_meta( $quiz_id, 'wpf-settings', true );

		$attempt       = \memberpress\courses\models\Attempt::get_one(
			array(
				'quiz_id' => $quiz_id,
				'user_id' => $attrs['user_id'],
			)
		);
		$attempt_attrs = $attempt->get_values();

		if ( ! empty( $settings['apply_tags_quiz_pass'] ) && intval( $attempt_attrs['score'] ) >= 50 ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_quiz_pass'], $attrs['user_id'] );
		}

		if ( ! empty( $settings['apply_tags_quiz_fail'] ) && intval( $attempt_attrs['score'] ) < 50 ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_quiz_fail'], $attrs['user_id'] );
		}
	}
}
