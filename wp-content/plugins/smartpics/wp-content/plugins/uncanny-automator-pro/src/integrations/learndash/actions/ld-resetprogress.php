<?php

namespace Uncanny_Automator_Pro;

use LDLMS_DB;
use Uncanny_Automator\Recipe;

/**
 * Class LD_RESETPROGRESS
 *
 * @package Uncanny_Automator_Pro
 */
class LD_RESETPROGRESS {



	use Recipe\Action_Tokens;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	private $action_code;
	private $action_meta;
	private $quiz_list;
	private $assignment_list;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'RESETPROGRESS';
		$this->action_meta = 'LDCOURSE';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/learndash/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'sentence'           => sprintf(
			// translators: Action - LearnDash
				_x( "Reset the user's progress in {{a course:%1\$s}}", 'LearnDash', 'uncanny-automator-pro' ),
				$this->action_meta
			),
			'select_option_name' => _x( "Reset the user's progress in {{a course}}", 'LearnDash', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'reset_course_progress' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		// Set Action tokens.
		$tokens = Automator()->helpers->recipe->learndash->options->get_course_relevant_tokens( 'action', $this->action_meta );
		$this->set_action_tokens( $tokens, $this->action_code );

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$options_group = array(
			Automator()->helpers->recipe->learndash->options->all_ld_courses( null, $this->action_meta, false, false ),
		);

		// Maybe Add Tin Canny reset option
		if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
			$options_group[] = Automator()->helpers->recipe->field->text_field(
				'RESETTINCANNYDATA',
				esc_attr_x( 'Reset Tin Canny data', 'Learndash', 'uncanny-automator-pro' ),
				true,
				'checkbox',
				'',
				false,
				esc_attr_x( 'Also reset SCORM/xAPI records and bookmark data for this course', 'Learndash', 'uncanny-automator-pro' )
			);
		}

		// Maybe Add Course Timer reset option
		if ( Automator()->helpers->recipe->learndash::is_course_timer_activated() ) {
			$options_group[] = Automator()->helpers->recipe->field->text_field(
				'RESETTPCOURSETIMER',
				esc_attr_x( 'Reset Simple Course Timer data', 'Learndash', 'uncanny-automator-pro' ),
				true,
				'checkbox',
				'',
				false,
				esc_attr_x( 'Also reset time data from Toolkit Pro for this course', 'Learndash', 'uncanny-automator-pro' )
			);
		}

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => $options_group,
				),
			)
		);
	}

	/**
	 * Validation function when the action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 */
	public function reset_course_progress( $user_id, $action_data, $recipe_id, $args ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$course_id     = intval( $action_data['meta'][ $this->action_meta ] );
		$reset_tc_data = false;
		if ( isset( $action_data['meta']['RESETTINCANNYDATA'] ) && class_exists( '\UCTINCAN\Database\Admin' ) ) {
			$reset_tc_data = $action_data['meta']['RESETTINCANNYDATA'];
		}
		$reset_course_timer = false;
		if ( isset( $action_data['meta']['RESETTPCOURSETIMER'] ) && Automator()->helpers->recipe->learndash::is_course_timer_activated() ) {
			$reset_course_timer = $action_data['meta']['RESETTPCOURSETIMER'];
		}

		if ( '-1' === $course_id ) {
			// Complete with error.
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, _x( 'No course selected', 'Learndash', 'uncanny-automator-pro' ) );
			return;
		}

		$this->delete_user_activity( $user_id, $course_id );
		if ( $this->delete_course_progress( $user_id, $course_id ) ) {
			$this->reset_quiz_progress( $user_id, $course_id );
			$this->delete_assignments();
		}

		if ( 'true' === $reset_tc_data && class_exists( '\UCTINCAN\Database\Admin' ) ) {
			$this->reset_tincanny_data( $user_id, $course_id );
		}

		if ( 'true' === $reset_course_timer && Automator()->helpers->recipe->learndash::is_course_timer_activated() ) {
			$this->reset_course_timer( $user_id, $course_id );
		}

		$this->reset_quiz_progress( $user_id, $course_id );

		// Hydrate Action Tokens.
		$tokens = Automator()->helpers->recipe->learndash->options->hydrate_ld_course_action_tokens( $course_id, $user_id, $this->action_meta );
		$this->hydrate_tokens( $tokens );

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Delete course related meta keys from user meta table.
	 * Delete all activity related to a course from LD tables
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	public function delete_user_activity( $user_id, $course_id ) {
		global $wpdb;
		delete_user_meta( $user_id, 'completed_' . $course_id );
		//delete_user_meta( $user_id, 'course_' . $course_id . '_access_from' );
		delete_user_meta( $user_id, 'course_completed_' . $course_id );
		delete_user_meta( $user_id, 'learndash_course_expired_' . $course_id );

		$activity_ids = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT activity_id FROM ' . $wpdb->prefix . 'learndash_user_activity WHERE course_id = %d AND user_id = %d',
				$course_id,
				$user_id
			)
		);

		if ( $activity_ids ) {
			foreach ( $activity_ids as $activity_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}learndash_user_activity_meta WHERE activity_id = %d",
						$activity_id->activity_id
					)
				);
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}learndash_user_activity WHERE activity_id = %d",
						$activity_id->activity_id
					)
				);
			}
		}
	}

	/**
	 * Delete course progress from Usermeta Table
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	public function delete_course_progress( $user_id, $course_id ) {
		$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
		if ( ! empty( $usermeta ) && isset( $usermeta[ $course_id ] ) ) {
			unset( $usermeta[ $course_id ] );
			update_user_meta( $user_id, '_sfwd-course_progress', $usermeta );

			$last_know_step = get_user_meta( $user_id, 'learndash_last_known_page', true );
			if ( ! empty( $last_know_step ) ) {
				if ( false !== strpos( $last_know_step, ',' ) ) {
					$last_know_step = explode( ',', $last_know_step );

					if ( isset( $last_know_step[0] ) && isset( $last_know_step[1] ) ) {
						$step_id        = $last_know_step[0];
						$step_course_id = $last_know_step[1];

						if ( (int) $step_course_id === (int) $course_id ) {
							delete_user_meta( $user_id, 'learndash_last_known_page' );
						}
					}
				}
			}

			delete_user_meta( $user_id, 'learndash_last_known_course_' . $course_id );

			return true;
		}

		return false;
	}

	/**
	 * Get lesson quiz list
	 * Get Lesson assignment list
	 * Delete quiz progress, related to course, quiz etc
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	public function reset_quiz_progress( $user_id, $course_id ) {
		$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );
		foreach ( $lessons as $lesson ) {
			$this->get_topics_quiz( $user_id, $lesson->ID, $course_id );
			$lesson_quiz_list = learndash_get_lesson_quiz_list( $lesson->ID, $user_id, $course_id );

			if ( $lesson_quiz_list ) {
				foreach ( $lesson_quiz_list as $ql ) {
					$this->quiz_list[ $ql['post']->ID ] = 0;
				}
			}

			//grabbing lesson related assignments
			$assignments = get_posts(
				array(
					'post_type'      => 'sfwd-assignment',
					'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => 'lesson_id',
							'value'   => $lesson->ID,
							'compare' => '=',
						),
						array(
							'key'     => 'course_id',
							'value'   => $course_id,
							'compare' => '=',
						),
						array(
							'key'     => 'user_id',
							'value'   => $user_id,
							'compare' => '=',
						),
					),
				)
			);

			if ( $assignments ) {
				foreach ( $assignments as $assignment ) {
					$this->assignment_list[] = $assignment->ID;
				}
			}
		}

		$this->delete_quiz_progress( $user_id, $course_id );
	}

	/**
	 * Get topic quiz + assignment list
	 *
	 * @param $user_id
	 * @param $lesson_id
	 * @param $course_id
	 */
	public function get_topics_quiz( $user_id, $lesson_id, $course_id ) {
		$topic_list = learndash_get_topic_list( $lesson_id, $course_id );
		if ( $topic_list ) {
			foreach ( $topic_list as $topic ) {
				$topic_quiz_list = learndash_get_lesson_quiz_list( $topic->ID, $user_id, $course_id );
				if ( $topic_quiz_list ) {
					foreach ( $topic_quiz_list as $ql ) {
						$this->quiz_list[ $ql['post']->ID ] = 0;
					}
				}

				$assignments = get_posts(
					array(
						'post_type'      => 'sfwd-assignment',
						'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
						'meta_query'     => array(
							'relation' => 'AND',
							array(
								'key'     => 'lesson_id',
								'value'   => $topic->ID,
								'compare' => '=',
							),
							array(
								'key'     => 'course_id',
								'value'   => $course_id,
								'compare' => '=',
							),
							array(
								'key'     => 'user_id',
								'value'   => $user_id,
								'compare' => '=',
							),
						),
					)
				);

				if ( $assignments ) {
					foreach ( $assignments as $assignment ) {
						$this->assignment_list[] = $assignment->ID;
					}
				}
			}
		}
	}

	/**
	 * Actually deleting quiz data from user meta and pro quiz activity table
	 *
	 * @param $user_id
	 * @param null $course_id
	 */
	public function delete_quiz_progress( $user_id, $course_id = null ) {
		$quizzes = learndash_get_course_quiz_list( $course_id, $user_id );
		if ( $quizzes ) {
			foreach ( $quizzes as $quiz ) {
				$this->quiz_list[ $quiz['post']->ID ] = 0;
			}
		}
		global $wpdb;

		$quizz_progress = array();
		if ( ! empty( $this->quiz_list ) ) {
			$usermeta       = get_user_meta( $user_id, '_sfwd-quizzes', true );
			$quizz_progress = empty( $usermeta ) ? array() : $usermeta;
			foreach ( $quizz_progress as $k => $p ) {
				if ( key_exists( $p['quiz'], $this->quiz_list ) && absint( $p['course'] ) === absint( $course_id ) ) {
					$statistic_ref_id = $p['statistic_ref_id'];
					unset( $quizz_progress[ $k ] );
					if ( ! empty( $statistic_ref_id ) ) {

						if ( class_exists( '\LDLMS_DB' ) ) {
							$pro_quiz_stat_table     = LDLMS_DB::get_table_name( 'quiz_statistic' );
							$pro_quiz_stat_ref_table = LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
						} else {
							$pro_quiz_stat_table     = $wpdb->prefix . 'wp_pro_quiz_statistic';
							$pro_quiz_stat_ref_table = $wpdb->prefix . 'wp_pro_quiz_statistic_ref';
						}

						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM {$pro_quiz_stat_table} WHERE statistic_ref_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$statistic_ref_id
							)
						);
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM {$pro_quiz_stat_ref_table} WHERE statistic_ref_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$statistic_ref_id
							)
						);
					}
				}
			}
		}

		update_user_meta( $user_id, '_sfwd-quizzes', $quizz_progress );
		// Get quiz progress again for attempts
		$usermeta       = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$quizz_progress = empty( $usermeta ) ? array() : $usermeta;

		foreach ( $quizz_progress as $k => $p ) {
			if ( absint( $p['course'] ) === absint( $course_id ) ) {
				$statistic_ref_id = $p['statistic_ref_id'];
				unset( $quizz_progress[ $k ] );
				if ( ! empty( $statistic_ref_id ) ) {

					if ( class_exists( '\LDLMS_DB' ) ) {
						$pro_quiz_stat_table     = LDLMS_DB::get_table_name( 'quiz_statistic' );
						$pro_quiz_stat_ref_table = LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
					} else {
						$pro_quiz_stat_table     = $wpdb->prefix . 'wp_pro_quiz_statistic';
						$pro_quiz_stat_ref_table = $wpdb->prefix . 'wp_pro_quiz_statistic_ref';
					}

					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$pro_quiz_stat_table} WHERE statistic_ref_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$statistic_ref_id
						)
					);
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$pro_quiz_stat_ref_table} WHERE statistic_ref_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$statistic_ref_id
						)
					);
				}
			}
		}
		update_user_meta( $user_id, '_sfwd-quizzes', $quizz_progress );
	}

	/**
	 * Delete assignments of course, related to lessons / topics
	 */
	public function delete_assignments() {
		global $wpdb;
		$assignments = $this->assignment_list;
		//Utilities::log( $this->assignment_list, '$this->assignment_list', true, 'reset' );
		if ( $assignments ) {
			foreach ( $assignments as $assignment ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->posts} WHERE ID = %d",
						$assignment
					)
				);
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d",
						$assignment
					)
				);
			}
		}
	}

	/**
	 * Delete tin canny data on reset.
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	public function reset_tincanny_data( $user_id, $course_id ) {
		global $wpdb;
		$table_reporting = $wpdb->prefix . \UCTINCAN\Database\Admin::TABLE_REPORTING;
		$table_quiz      = $wpdb->prefix . \UCTINCAN\Database\Admin::TABLE_QUIZ;
		$table_resume    = $wpdb->prefix . \UCTINCAN\Database\Admin::TABLE_RESUME;

		// Delete from reporting table
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_reporting} WHERE user_id = %d AND course_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);

		// Delete from quiz table
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_quiz} WHERE user_id = %d AND course_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);

		// Delete from resume table
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_resume} WHERE user_id = %d AND course_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);
	}

	/**
	 * Reset course timer data on reset.
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	private function reset_course_timer( $user_id, $course_id ) {
		\uncanny_pro_toolkit\CourseTimer::delete_user_course_data( $user_id, $course_id );
	}
}
