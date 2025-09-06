<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Ld_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Ld_Pro_Tokens {

	/**
	 *
	 */
	public function __construct( $load_action_hook = true ) {

		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
		if ( true === $load_action_hook ) {

			add_filter(
				'automator_maybe_trigger_ld_ld_usercompletesgroupscourse_tokens',
				array(
					$this,
					'group_course_possible_tokens',
				),
				9999,
				2
			);

			add_filter(
				'automator_maybe_trigger_ld_ld_groupleaderremovedgroup_tokens',
				array(
					$this,
					'group_leader_possible_tokens',
				),
				9999,
				2
			);

			add_filter(
				'automator_maybe_trigger_ld_ld_groupleaderaddedgroup_tokens',
				array(
					$this,
					'group_leader_possible_tokens',
				),
				9999,
				2
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'ld_group_leader_token',
				),
				9999,
				6
			);
			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'ld_tokens',
				),
				22,
				6
			);
			add_filter( 'automator_maybe_parse_token', array( $this, 'ld_assignment_tokens' ), 20, 6 );

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'ld_essayquiz_token',
				),
				99999,
				6
			);

			add_filter(
				'automator_learndash_quiz_q_and_a_tokens',
				array(
					$this,
					'add_trigger_codes_for_quiz_q_and_a_tokens',
				),
				10,
				1
			);
		}
	}

	/**
	 * save_token_data
	 *
	 * @param mixed $args
	 * @param mixed $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		if ( 'LD_GROUP_OR_ITS_CHILD' === $args['entry_args']['code'] ) {
			$group_id          = $args['trigger_args'][1];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $group_id ) ) {
				Automator()->db->token->save( 'group_id', $group_id, $trigger_log_entry );
			}
		}

		if ( 'LD_QUESTIONS' === $args['entry_args']['meta'] ) {
			list( $correct, $question_id, $quiz_id ) = $args['trigger_args'];
			//$question_id       = $args['trigger_args'][1]['question_post_id'];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $question_id ) ) {
				Automator()->db->token->save( 'question_id', $question_id, $trigger_log_entry );
				Automator()->db->token->save( 'quiz_id', $quiz_id, $trigger_log_entry );
			}
		}
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function ld_group_leader_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args = array() ) {

		if ( empty( $pieces ) || empty( $trigger_data ) || empty( $replace_args ) ) {
			return $value;
		}
		if ( ! isset( $pieces[2] ) ) {
			return $value;
		}
		if ( ! array_intersect(
			array(
				'GROUP_LEADER_ID',
				'GROUP_LEADER_NAME',
				'GROUP_LEADER_EMAIL',
			),
			$pieces
		) ) {
			return $value;
		}

		return Automator()->db->token->get( $pieces[2], $replace_args );
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function group_course_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'GROUP_COURSES',
				'tokenName'       => __( 'Group courses', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'LDGROUPCOURSES_course_points',
				'tokenName'       => __( 'Course points', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function group_leader_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'GROUP_LEADER_ID',
				'tokenName'       => __( 'Group Leader ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'GROUP_LEADER_NAME',
				'tokenName'       => __( 'Group Leader name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'GROUP_LEADER_EMAIL',
				'tokenName'       => __( 'Group Leader email', 'uncanny-automator-pro' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		return array_merge( $tokens, $fields );
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return false|int|mixed|string|\WP_Error
	 */
	public function ld_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) ) {
			return $value;
		}
		$trigger_codes = array(
			'LD_USERCOMPLETESGROUPSCOURSE',
			'LD_GROUPLEADERREMOVEDGROUP',
			'LD_GROUP_OR_ITS_CHILD',
			'LD_CORRECT_ANSWER',
			'LD_INCORRECT_ANSWER',
			'LD_USERADDEDGROUPCOURSEACCESS',
		);
		if ( ! array_intersect( $trigger_codes, $pieces ) ) {
			return $value;
		}

		if ( empty( $trigger_data ) ) {
			return $value;
		}
		foreach ( $trigger_data as $trigger ) {
			if ( empty( $trigger ) ) {
				continue;
			}

			$trigger_id     = $trigger['ID'];
			$trigger_log_id = $replace_args['trigger_log_id'];
			$meta_key       = 'COURSEINGROUP';
			$question_id    = '';
			$group_id       = Automator()->helpers->recipe->get_form_data_from_trigger_meta( $meta_key, $trigger_id, $trigger_log_id, $user_id );
			if ( 'LD_GROUP_OR_ITS_CHILD' === $pieces[1] || 'LD_USERADDEDGROUPCOURSEACCESS' === $pieces[1] ) {
				$group_id = Automator()->db->token->get( 'group_id', $replace_args );
			}
			if ( 'LD_CORRECT_ANSWER' === $pieces[1] || 'LD_INCORRECT_ANSWER' === $pieces[1] ) {
				$question_id = Automator()->db->token->get( 'question_id', $replace_args );
			}
			if ( ! empty( $group_id ) ) {
				if ( 'LDGROUPCOURSES_ID' === $pieces[2] || 'LDGROUP_ID' === $pieces[2] ) {
					$value = $group_id;
				} elseif ( 'LDCOURSE' === $pieces[2] || 'LDGROUP' === $pieces[2] ) {
					$value = get_the_title( $group_id );
				} elseif ( 'LDGROUPCOURSES_URL' === $pieces[2] || 'LDGROUP_URL' === $pieces[2] ) {
					$value = get_permalink( $group_id );
				} elseif ( 'LDGROUPCOURSES_THUMB_ID' === $pieces[2] || 'LDGROUP_THUMB_ID' === $pieces[2] ) {
					$value = get_post_thumbnail_id( $group_id );
				} elseif ( 'LDGROUPCOURSES_THUMB_URL' === $pieces[2] || 'LDGROUP_THUMB_URL' === $pieces[2] ) {
					$value = get_the_post_thumbnail_url( $group_id );
				} elseif ( 'LDGROUPLEADERID' === $pieces[2] ) {
					$value = $user_id;
				} elseif ( 'GROUP_COURSES' === $pieces[2] ) {
					$courses                          = array();
					$learndash_group_enrolled_courses = learndash_group_enrolled_courses( $group_id );
					foreach ( $learndash_group_enrolled_courses as $course_id ) {
						$courses[] = get_the_title( $course_id );
					}
					$value = join( ', ', $courses );
				} elseif ( 'LDGROUPCOURSES_course_points' === $pieces[2] ) {
					// LD Course points token
					$course_points                    = array();
					$learndash_group_enrolled_courses = learndash_group_enrolled_courses( $group_id );
					foreach ( $learndash_group_enrolled_courses as $course_id ) {
						$course_points[] = learndash_get_course_points( $course_id );
					}
					$value = join( ', ', $course_points );
				} elseif ( 'LDGROUP_LEADERS' === $pieces[2] ) {
					$group_leaders       = learndash_get_groups_administrators( $group_id, true );
					$group_leader_emails = array();

					foreach ( $group_leaders as $leader ) {
						$group_leader_emails[ $leader->ID ] = $leader->user_email;
					}
					$value = join( ', ', $group_leader_emails );
				}
			}

			if ( isset( $question_id ) && ! empty( $question_id ) ) {
				if ( 'LD_QUIZZES_ID' === $pieces[2] ) {
					$value = Automator()->db->token->get( 'quiz_id', $replace_args );
				} elseif ( 'LD_QUIZZES' === $pieces[2] ) {
					$quiz_id = Automator()->db->token->get( 'quiz_id', $replace_args );
					$value   = get_the_title( $quiz_id );
				} elseif ( 'LD_QUIZZES_URL' === $pieces[2] ) {
					$quiz_id = Automator()->db->token->get( 'quiz_id', $replace_args );
					$value   = get_permalink( $quiz_id );
				} elseif ( 'LD_QUESTIONS' === $pieces[2] ) {
					$value = get_the_title( $question_id );
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return false|int|mixed|string|\WP_Error
	 */
	public function ld_assignment_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) || empty( $trigger_data ) ) {
			return $value;
		}

		$assignment_keys = array( 'ASSIGNMENT_ID', 'ASSIGNMENT_URL' );

		if ( ! array_intersect( $assignment_keys, $pieces ) ) {
			return $value;
		}

		$parse_token_key = $pieces[2];

		if ( in_array( $parse_token_key, $assignment_keys, true ) ) {
			return Automator()->db->token->get( $parse_token_key, $replace_args );
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function ld_essayquiz_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args = array() ) {
		if ( empty( $pieces ) || empty( $trigger_data ) || empty( $replace_args ) ) {
			return $value;
		}

		if ( ! in_array( 'LD_SUBMITESSAYQUIZ', $pieces, true ) ) {
			return $value;
		}

		if ( empty( $trigger_data ) ) {
			return $value;
		}

		$trigger_id      = $replace_args['trigger_id'];
		$trigger_log_id  = $replace_args['trigger_log_id'];
		$trigger_user_id = $replace_args['user_id'];
		$ld_essay_id     = (int) Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDESSAY_ID', $trigger_id, $trigger_log_id, $user_id );

		if ( 0 === $ld_essay_id ) {
			return false;
		}

		$tokens = array(
			'LDESSAYQUIZ_ID',
			'LDESSAYQUIZ_TITLE',
			'LDESSAYQUIZ_SUBMISSION_DATE',
			'LDESSAYQUIZ_CONTENT',
			'LDESSAYQUIZ_LDQUIZ_ID',
			'LDESSAYQUIZ_LDQUIZ_TITLE',
			'LDESSAYQUIZ_LDCOURSE_ID',
			'LDESSAYQUIZ_LDCOURSE_TITLE',
			'LDESSAYQUIZ_LDLESSON_ID',
			'LDESSAYQUIZ_LDLESSON_TITLE',
			'LDESSAYQUIZ_LDQUESTION_ID',
			'LDESSAYQUIZ_LDQUESTION_TITLE',
		);

		$parse_token_key = $pieces[2];

		if ( in_array( $parse_token_key, $tokens, true ) ) {
			switch ( $parse_token_key ) {
				case 'LDESSAYQUIZ_ID':
					$value = $ld_essay_id;
					break;
				case 'LDESSAYQUIZ_TITLE':
					$value = get_the_title( $ld_essay_id );
					break;
				case 'LDESSAYQUIZ_SUBMISSION_DATE':
					$value = get_the_date( '', $ld_essay_id );
					break;
				case 'LDESSAYQUIZ_CONTENT':
					$value = wpautop( get_post_field( 'post_content', $ld_essay_id ) );
					break;
				case 'LDESSAYQUIZ_LDQUIZ_ID':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDQUIZ', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'LDESSAYQUIZ_LDQUIZ_TITLE':
					$quiz_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDQUIZ', $trigger_id, $trigger_log_id, $user_id );
					$value   = get_the_title( $quiz_id );
					break;
				case 'LDESSAYQUIZ_LDCOURSE_ID':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDCOURSE', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'LDESSAYQUIZ_LDCOURSE_TITLE':
					$course_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDCOURSE', $trigger_id, $trigger_log_id, $user_id );
					$value     = get_the_title( $course_id );

					break;
				case 'LDESSAYQUIZ_LDLESSON_ID':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDLESSON', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'LDESSAYQUIZ_LDLESSON_TITLE':
					$lesson_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDLESSON', $trigger_id, $trigger_log_id, $user_id );
					$value     = get_the_title( $lesson_id );
					break;
				case 'LDESSAYQUIZ_LDQUESTION_ID':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDESSAY_QUESTION_ID', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'LDESSAYQUIZ_LDQUESTION_TITLE':
					$question_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'LDESSAY_QUESTION_ID', $trigger_id, $trigger_log_id, $user_id );
					$value       = get_the_title( $question_id );
					break;
				default:
					$value = '';
					break;

			}

			$value = apply_filters( 'automator_ld_essayquiz_token_value', $value, $parse_token_key, $ld_essay_id );
		}

		return $value;
	}

	/**
	 * New tokens for Groups and Courses trigger.
	 *
	 * @return array[]
	 */
	public function group_course_tokens() {
		return array(
			'LD_COURSE_TITLE'              => array(
				'name' => __( 'Course title', 'uncanny-automator-pro' ),
			),
			'LD_COURSE_ID'                 => array(
				'name' => __( 'Course ID', 'uncanny-automator-pro' ),
			),
			'LD_COURSE_URL'                => array(
				'name' => __( 'Course URL', 'uncanny-automator-pro' ),
			),
			'LD_COURSE_FEATURED_IMAGE_ID'  => array(
				'name' => __( 'Course featured image ID', 'uncanny-automator-pro' ),
			),
			'LD_COURSE_FEATURED_IMAGE_URL' => array(
				'name' => __( 'Course featured image URL', 'uncanny-automator-pro' ),
			),
			'LD_GROUP_TITLE'               => array(
				'name' => __( 'Group title', 'uncanny-automator-pro' ),
			),
			'LD_GROUP_ID'                  => array(
				'name' => __( 'Group ID', 'uncanny-automator-pro' ),
			),
			'LD_GROUP_URL'                 => array(
				'name' => __( 'Group URL', 'uncanny-automator-pro' ),
			),
			'LD_GROUP_FEATURED_IMAGE_ID'   => array(
				'name' => __( 'Group featured image ID', 'uncanny-automator-pro' ),
			),
			'LD_GROUP_FEATURED_IMAGE_URL'  => array(
				'name' => __( 'Group featured image URL', 'uncanny-automator-pro' ),
			),
			'LDGROUP_LEADER_EMAIL'         => array(
				'name' => __( 'Group leader email', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * Hydrate tokens method for ban user trigger.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_group_course_tokens( $parsed, $args, $trigger ) {

		list( $course_id, $group_id ) = $args['trigger_args'];

		$group_leaders       = learndash_get_groups_administrators( $group_id, true );
		$group_leader_emails = array();

		foreach ( $group_leaders as $leader ) {
			$group_leader_emails[ $leader->ID ] = $leader->user_email;
		}

		return $parsed + array(
			'LD_COURSE_TITLE'              => get_the_title( $course_id ),
			'LD_COURSE_ID'                 => absint( $course_id ),
			'LD_COURSE_URL'                => get_permalink( $course_id ),
			'LD_COURSE_FEATURED_IMAGE_ID'  => get_post_thumbnail_id( $course_id ),
			'LD_COURSE_FEATURED_IMAGE_URL' => get_the_post_thumbnail_url( $course_id ),
			'LD_GROUP_TITLE'               => get_the_title( $group_id ),
			'LD_GROUP_ID'                  => absint( $group_id ),
			'LD_GROUP_URL'                 => get_permalink( $group_id ),
			'LD_GROUP_FEATURED_IMAGE_ID'   => get_post_thumbnail_id( $group_id ),
			'LD_GROUP_FEATURED_IMAGE_URL'  => get_the_post_thumbnail_url( $group_id ),
			'LDGROUP_LEADER_EMAIL'         => implode( ',', $group_leader_emails ),
		);
	}

	/**
	 * Add trigger codes for quiz q and a tokens.
	 *
	 * @param array $trigger_codes
	 *
	 * @return array
	 */
	public function add_trigger_codes_for_quiz_q_and_a_tokens( $trigger_codes ) {
		$trigger_codes[] = 'LD_SUBMITESSAYQUIZ';

		return $trigger_codes;
	}

	/**
	 * Hydrate Assignment Graded Tokens.
	 *
	 * @param array $parsed
	 * @param array $args
	 * @param array $trigger
	 *
	 * @return array
	 */
	public function hydrate_assignment_graded_tokens( $parsed, $args, $trigger ) {

		list( $assignment_id ) = $args['trigger_args'];

		$assignment = get_post( $assignment_id );
		if ( ! $assignment ) {
			return $parsed;
		}

		$user_id   = $assignment->post_author;
		$course_id = get_post_meta( $assignment_id, 'course_id', true );
		$step_id   = get_post_meta( $assignment_id, 'lesson_id', true );
		$status    = learndash_is_assignment_approved_by_meta( $assignment_id );

		// Set points not enabled as default.
		$points = $max_points = esc_html__( 'Not Enabled', 'learndash' );

		// Points Enabled.
		if ( learndash_assignment_is_points_enabled( $assignment_id ) ) {
			$points = get_post_meta( $assignment_id, 'points', true );
			if ( empty( $points ) ) {
				$points = 0;
			}
			$max_points = learndash_get_setting( $step_id, 'lesson_assignment_points_amount' );
		}

		$tokens = array(
			'LDCOURSE'                     => get_the_title( $course_id ),
			'LDCOURSE_ID'                  => absint( $course_id ),
			'LDCOURSE_URL'                 => get_permalink( $course_id ),
			'LDCOURSE_THUMB_ID'            => get_post_thumbnail_id( $course_id ),
			'LDCOURSE_THUMB_URL'           => get_the_post_thumbnail_url( $course_id ),
			'LDCOURSE_STATUS'              => learndash_course_status( $course_id, $user_id ),
			'LDCOURSE_ACCESS_EXPIRY'       => learndash_adjust_date_time_display( ld_course_access_expires_on( $course_id, $user_id ) ),
			'LDSTEP'                       => get_the_title( $step_id ),
			'LDSTEP_ID'                    => absint( $step_id ),
			'LDSTEP_URL'                   => get_permalink( $step_id ),
			'LDSTEP_THUMB_ID'              => get_post_thumbnail_id( $step_id ),
			'LDSTEP_THUMB_URL'             => get_the_post_thumbnail_url( $step_id ),
			'LDASSIGNMENT'                 => $assignment->post_title,
			'LDASSIGNMENT_AUTHOR'          => get_the_author_meta( 'display_name', $user_id ),
			'LDASSIGNMENT_STATUS'          => empty( $status ) ? esc_html__( 'Not Approved', 'learndash' ) : esc_html__( 'Approved', 'learndash' ),
			'LDASSIGNMENT_COURSE'          => get_the_title( $course_id ),
			'LDASSIGNMENT_STEP'            => get_the_title( $step_id ),
			'LDASSIGNMENT_URL'             => get_permalink( $assignment_id ),
			'LDASSIGNMENT_POINTS_EARNED'   => $points,
			'LDASSIGNMENT_POINTS_POSSIBLE' => $max_points,
		);

		return array_merge( $parsed, $tokens );
	}

	/**
	 * Hydrate Essay Graded Tokens.
	 *
	 * @param array $parsed
	 * @param array $args
	 * @param array $trigger
	 *
	 * @return array
	 */
	public function hydrate_essay_graded_tokens( $parsed, $args, $trigger ) {

		list( $quiz_id, $question_id, $essay, $submitted_essay ) = $args['trigger_args'];

		if ( ! is_a( $essay, 'WP_Post' ) ) {
			return $parsed;
		}

		$user_id          = $essay->post_author;
		$course_id        = get_post_meta( $essay->ID, 'course_id', true );
		$step_id          = get_post_meta( $essay->ID, 'lesson_id', true );
		$quiz_post_id     = get_post_meta( $essay->ID, 'quiz_post_id', true );
		$question_post_id = get_post_meta( $essay->ID, 'question_post_id', true );
		$max_points       = get_post_meta( $question_post_id, 'question_points', true );

		$tokens = array(
			'LDCOURSE'                        => get_the_title( $course_id ),
			'LDCOURSE_ID'                     => absint( $course_id ),
			'LDCOURSE_URL'                    => get_permalink( $course_id ),
			'LDCOURSE_THUMB_ID'               => get_post_thumbnail_id( $course_id ),
			'LDCOURSE_THUMB_URL'              => get_the_post_thumbnail_url( $course_id ),
			'LDCOURSE_STATUS'                 => learndash_course_status( $course_id, $user_id ),
			'LDCOURSE_ACCESS_EXPIRY'          => learndash_adjust_date_time_display( ld_course_access_expires_on( $course_id, $user_id ) ),
			'LDSTEP'                          => get_the_title( $step_id ),
			'LDSTEP_ID'                       => absint( $step_id ),
			'LDSTEP_URL'                      => get_permalink( $step_id ),
			'LDSTEP_THUMB_ID'                 => get_post_thumbnail_id( $step_id ),
			'LDSTEP_THUMB_URL'                => get_the_post_thumbnail_url( $step_id ),
			'LDQUIZ'                          => get_the_title( $quiz_post_id ),
			'LDQUIZ_ID'                       => absint( $quiz_post_id ),
			'LDESSAYQUESTION'                 => $essay->post_title,
			'LDESSAYQUESTION_POINTS_EARNED'   => (int) $submitted_essay['points_awarded'],
			'LDESSAYQUESTION_POINTS_POSSIBLE' => ! empty( $max_points ) ? (int) $max_points : 0,
		);

		if ( $this->can_get_ld_tokens_method( 'get_user_quiz_questions_and_answers' ) ) {
			$ld_tokens_instance           = $this->get_ld_tokens_instance();
			$result                       = $ld_tokens_instance->get_user_quiz_questions_and_answers( $user_id, $quiz_post_id );
			$tokens['LDQUIZ_Q_AND_A']     = ! empty( $result['result'] ) ? $result['result'] : '';
			$tokens['LDQUIZ_Q_AND_A_CSV'] = ! empty( $result['data'] ) ? $ld_tokens_instance->format_user_quiz_questions_and_answers_csv( $result['data'] ) : '';
		}

		return array_merge( $parsed, $tokens );
	}

	/**
	 * Get Common Tokens for Quiz Actions.
	 *
	 * @param string $meta_key
	 *
	 * @return array
	 */
	public static function get_common_quiz_action_tokens( $meta_key ) {

		$tokens = array(
			$meta_key                   => array(
				'name' => esc_attr_x( 'Quiz title', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_ID'           => array(
				'name' => esc_attr_x( 'Quiz ID', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'int',
			),
			$meta_key . '_URL'          => array(
				'name' => esc_attr_x( 'Quiz URL', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_THUMB_ID'     => array(
				'name' => esc_attr_x( 'Quiz featured image ID', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'int',
			),
			$meta_key . '_THUMB_URL'    => array(
				'name' => esc_attr_x( 'Quiz featured image URL', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_STATUS'       => array(
				'name' => _x( 'Quiz status', 'LearnDash tokens', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_COURSE_TITLE' => array(
				'name' => esc_attr_x( 'Course title', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_COURSE_ID'    => array(
				'name' => esc_attr_x( 'Course ID', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'int',
			),
			$meta_key . '_COURSE_URL'   => array(
				'name' => esc_attr_x( 'Course URL', 'LearnDash Token', 'uncanny-automator' ),
				'type' => 'text',
			),
			$meta_key . '_STEP_TITLE'   => array(
				'name' => esc_attr_x( 'Lesson/Topic title', 'LearnDash Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			$meta_key . '_STEP_ID'      => array(
				'name' => esc_attr_x( 'Lesson/Topic ID', 'LearnDash Token', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			$meta_key . '_STEP_URL'     => array(
				'name' => esc_attr_x( 'Lesson/Topic URL', 'LearnDash Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);

		return $tokens;
	}

	/**
	 * Hydrate Common Quiz Action Tokens.
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param int $user_id
	 * @param string $meta_key
	 * @param int $step_id
	 *
	 * @return array
	 */
	public static function hydrate_common_quiz_action_tokens( $quiz_id, $course_id, $user_id, $meta_key, $step_id ) {

		// Hydrate Quiz Tokens.
		$tokens                  = Automator()->helpers->recipe->learndash->options->hydrate_ld_quiz_action_tokens( $quiz_id, $user_id, $meta_key );
		$tokens['LDQUIZ_STATUS'] = self::ld_get_user_quiz_status( $user_id, $quiz_id );
		// Hydrate Course Tokens.
		$course_id                             = absint( $course_id );
		$tokens[ $meta_key . '_COURSE_TITLE' ] = get_the_title( $course_id );
		$tokens[ $meta_key . '_COURSE_ID' ]    = $course_id;
		$tokens[ $meta_key . '_COURSE_URL' ]   = get_permalink( $course_id );
		// Check if Step ID is set to -1
		$step_id = intval( $step_id );
		if ( - 1 === $step_id ) {
			$step_id = learndash_get_lesson_id( $quiz_id, $course_id );
		}
		$step_id = ! empty( $step_id ) && $step_id > 0 ? intval( $step_id ) : false;

		// Hydrate Step Tokens if Step ID is set.
		$tokens[ $meta_key . '_STEP_TITLE' ] = $step_id ? get_the_title( $step_id ) : '';
		$tokens[ $meta_key . '_STEP_ID' ]    = $step_id ? $step_id : '';
		$tokens[ $meta_key . '_STEP_URL' ]   = $step_id ? get_permalink( $step_id ) : '';

		return $tokens;
	}

	/**
	 * Quiz Status Token.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return string
	 */
	public static function ld_get_user_quiz_status( $user_id, $quiz_id, $course_id = 0 ) {

		$not_graded = get_post_status_object( 'not_graded' )->label;
		if ( empty( $user_id ) || empty( $quiz_id ) ) {
			return $not_graded;
		}

		$course_id     = empty( $course_id ) ? learndash_get_course_id( $quiz_id ) : $course_id;
		$quiz_progress = get_user_meta( $user_id, '_sfwd-quizzes', true );

		if ( empty( $quiz_progress ) ) {
			return $not_graded;
		}

		$status = array();
		foreach ( $quiz_progress as $key => $quiz_progress ) {
			if ( $quiz_progress['quiz'] === $quiz_id && $quiz_progress['course'] === $course_id ) {
				$graded = ! empty( $quiz_progress['graded'] ) ? $quiz_progress['graded'] : false;
				if ( empty( $graded ) ) {
					continue;
				}

				foreach ( $graded as $quiz_question_id => $graded ) {
					if ( isset( $graded['post_id'] ) ) {
						$graded_post = get_post( $graded['post_id'] );
						if ( $graded_post instanceof WP_Post ) {
							$status[ $graded['post_id'] ] = get_post_status_object( $graded['status'] )->label;
						}
					}
				}
			}
		}

		// No attempts found.
		if ( empty( $status ) ) {
			return $not_graded;
		}

		// Multiple attempts found return with Post ID.
		if ( count( $status ) > 1 ) {
			$return = '';
			foreach ( $status as $post_id => $post_status ) {
				$return .= $post_status . " ( {$post_id} ), ";
			}

			return rtrim( $return, ', ' );
		}

		// Single attempt found.
		return reset( $status );
	}

	/**
	 * Check if the method exists in \Uncanny_Automator\Ld_Tokens class.
	 *
	 * @param string $method
	 *
	 * @return bool
	 */
	public function can_get_ld_tokens_method( $method ) {
		if ( empty( $method ) || ! is_string( $method ) ) {
			return false;
		}

		return method_exists( $this->get_ld_tokens_instance(), $method );
	}

	/**
	 * Get Instance of \Uncanny_Automator\Ld_Tokens class.
	 *
	 * @return \Uncanny_Automator\Ld_Tokens
	 */
	public function get_ld_tokens_instance() {
		static $ld_tokens_instance = null;
		if ( is_null( $ld_tokens_instance ) ) {
			$ld_tokens_instance = new \Uncanny_Automator\Ld_Tokens( false );
		}

		return $ld_tokens_instance;
	}
}
