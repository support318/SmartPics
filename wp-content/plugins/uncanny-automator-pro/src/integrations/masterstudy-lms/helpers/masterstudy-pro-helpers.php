<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Masterstudy_Helpers;

/**
 * Class Masterstudy_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Masterstudy_Pro_Helpers extends Masterstudy_Helpers {

	/**
	 * @param $load_hooks
	 */
	public function __construct( $load_hooks = true ) {

		if ( ! $load_hooks ) {
			return;
		}

		// Selectively load options
		if ( property_exists( '\Uncanny_Automator\Masterstudy_Helpers', 'load_options' ) ) {

			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		}

		add_action(
			'wp_ajax_select_mslms_lesson_from_course_x',
			array(
				$this,
				'select_lesson_from_course_func',
			)
		);
	}

	/**
	 * @param Masterstudy_Pro_Helpers $pro
	 */
	public function setPro( Masterstudy_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * Return all the specific fields of a form ID provided in ajax call
	 *
	 * @param string $include_any
	 */
	public function select_lesson_from_course_func() {

		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();

		if ( ! isset( $_POST ) ) {
			echo wp_json_encode( $fields );
			die();
		}

		$mslms_course_id = absint( $_POST['values']['MSLMSCOURSE'] );
		// Bail.
		if ( empty( $mslms_course_id ) || $mslms_course_id < 1 ) {
			echo wp_json_encode( $fields );
			die();
		}

		// Attempt to load the curriculum repository class.
		$curriculum_repository = $this->pro_get_curriculum_repository();
		if ( $curriculum_repository ) {
			$fields = $this->pro_get_curriculum_material_options_by_post_type( $curriculum_repository, $mslms_course_id, 'stm-lessons' );
			echo wp_json_encode( $fields );
			die();
		}

		// Query the database for lessons by curriculum meta.
		global $wpdb;

		$lessons = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title
				FROM $wpdb->posts
				WHERE FIND_IN_SET(
					ID,
					(SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = 'curriculum')
				)
				AND post_type = 'stm-lessons'
				ORDER BY post_title ASC",
				absint( $mslms_course_id )
			)
		);

		if ( ! empty( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				$fields[] = array(
					'value' => $lesson->ID,
					'text'  => $lesson->post_title,
				);
			}
		}

		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * Get curriculum options by post type.
	 *
	 * @param \MasterStudy\Lms\Repositories\CurriculumRepository $curriculum_repo
	 * @param int    $course_id
	 * @param string $post_type
	 *
	 * @return array - value and text array.
	 */
	private function pro_get_curriculum_material_options_by_post_type( $curriculum_repo, $course_id, $post_type ) {
		$materials  = array();
		$curriculum = $curriculum_repo->get_curriculum( absint( $course_id ) );
		if ( ! empty( $curriculum ) && is_array( $curriculum ) && isset( $curriculum['materials'] ) ) {
			if ( ! empty( $curriculum['materials'] ) && is_array( $curriculum['materials'] ) ) {
				foreach ( $curriculum['materials'] as $material ) {
					if ( $material['post_type'] === $post_type ) {
						$materials[] = array(
							'value' => $material['post_id'],
							'text'  => $material['title'],
						);
					}
				}
			}
		}
		return $materials;
	}

	/**
	 * Get course curriculum materials.
	 *
	 * @param int $course_id
	 *
	 * @return array
	 */
	public function pro_get_course_curriculum_materials( $course_id ) {
		$materials = array();

		// Use curriculum repository class.
		$curriculum_repo = $this->pro_get_curriculum_repository();
		if ( $curriculum_repo ) {
			$curriculum = $curriculum_repo->get_curriculum( absint( $course_id ) );
			if ( ! empty( $curriculum ) && is_array( $curriculum ) && isset( $curriculum['materials'] ) ) {
				if ( ! empty( $curriculum['materials'] ) && is_array( $curriculum['materials'] ) ) {
					foreach ( $curriculum['materials'] as $material ) {
						$materials[] = array(
							'title'     => $material['title'],
							'post_id'   => $material['post_id'],
							'post_type' => $material['post_type'],
						);
					}
				}
			}

			return $materials;
		}

		// No materials found, try to get them from meta_key curriculum.
		$curriculum = get_post_meta( absint( $course_id ), 'curriculum', true );
		if ( ! empty( $curriculum ) ) {
			$curriculum       = \STM_LMS_Helpers::only_array_numbers( explode( ',', $curriculum ) );
			$curriculum_posts = get_posts(
				array(
					'post__in'       => $curriculum,
					'posts_per_page' => 999,
					'post_type'      => array( 'stm-lessons', 'stm-quizzes' ),
					'post_status'    => 'publish',
				)
			);
			if ( ! empty( $curriculum_posts ) ) {
				foreach ( $curriculum_posts as $material ) {
					$materials[] = array(
						'title'     => $material->post_title,
						'post_id'   => $material->ID,
						'post_type' => $material->post_type,
					);
				}
			}
		}

		return $materials;
	}

	/**
	 * Enroll user to course if not already enrolled.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return void
	 */
	public function maybe_enroll_user_to_course( $user_id, $course_id ) {
		// Enroll the user in the course if they are not already enrolled.
		$course = stm_lms_get_user_course( $user_id, $course_id, array( 'user_course_id' ) );
		if ( ! count( $course ) ) {
			\STM_LMS_Course::add_user_course( $course_id, $user_id, \STM_LMS_Course::item_url( $course_id, '' ), 0 );
			\STM_LMS_Course::add_student( $course_id );
		}
	}

	/**
	 * Check if curriculum repository class exists.
	 *
	 * @return mixed bool|\MasterStudy\Lms\Repositories\CurriculumRepository
	 */
	private function pro_get_curriculum_repository() {
		static $pro_curriculum_repository = null;
		if ( is_null( $pro_curriculum_repository ) ) {
			if ( class_exists( '\MasterStudy\Lms\Repositories\CurriculumRepository' ) ) {
				$pro_curriculum_repository = new \MasterStudy\Lms\Repositories\CurriculumRepository();
			} else {
				$pro_curriculum_repository = false;
			}
		}
		return $pro_curriculum_repository;
	}


}
