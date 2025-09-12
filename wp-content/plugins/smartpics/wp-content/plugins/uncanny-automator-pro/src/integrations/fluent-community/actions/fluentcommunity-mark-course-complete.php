<?php

namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use FluentCommunity\Modules\Course\Model\Course;
use FluentCommunity\Modules\Course\Services\CourseHelper;
use Uncanny_Automator\Recipe\Action;

class FLUENTCOMMUNITY_MARK_COURSE_COMPLETE extends Action {

	protected $prefix = 'FLUENTCOMMUNITY_MARK_COURSE_COMPLETE';

	protected $helpers;

	/**
	 * Setup the action
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'FLUENT_COMMUNITY' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: The course title
				esc_html_x( 'Mark {{a course:%1$s}} as complete for a user', 'FluentCommunity', 'uncanny-automator' ),
				$this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'Mark {{a course}} as complete for a user', 'FluentCommunity', 'uncanny-automator' )
		);
	}
	/**
	 * Options.
	 *
	 * @return mixed
	 */
	public function options() {

		return array(
			array(
				'option_code'           => $this->get_action_meta(),
				'label'                 => esc_html_x( 'Course', 'FluentCommunity', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'options'               => $this->helpers->all_courses( false ),
				'supports_custom_value' => false,
			),

		);
	}
	/**
	 * Process action.
	 *
	 * @param mixed $user_id The user ID.
	 * @param mixed $action_data The data.
	 * @param mixed $recipe_id The ID.
	 * @param mixed $args The arguments.
	 * @param mixed $parsed The parsed.
	 * @return mixed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$course_id = absint( $parsed[ $this->get_action_meta() ] );

		if ( ! $course_id || ! $user_id ) {
			$this->add_log_error( esc_html_x( 'Missing course ID or user ID.', 'Fluent Community', 'uncanny-automator' ) );
			return false;
		}

		$course = \FluentCommunity\Modules\Course\Model\Course::find( $course_id );

		if ( ! $course ) {
			$this->add_log_error( esc_html_x( 'Invalid course ID.', 'Fluent Community', 'uncanny-automator' ) );
			return false;
		}

		// loop through all lessons and mark as complete
		$lesson_ids = \FluentCommunity\Modules\Course\Services\CourseHelper::getCoursePublishedLessonIds( $course_id );

		if ( empty( $lesson_ids ) ) {
			$this->add_log_error( esc_html_x( 'No lessons found in this course.', 'Fluent Community', 'uncanny-automator' ) );
			return false;
		}

		$completed = false;

		foreach ( $lesson_ids as $lesson_id ) {
			$lesson = \FluentCommunity\Modules\Course\Model\CourseLesson::find( $lesson_id );
			if ( $lesson ) {
				\FluentCommunity\Modules\Course\Services\CourseHelper::updateLessonCompletion( $lesson, $user_id, 'completed' );
				$completed = true;
			}
		}

		if ( ! $completed ) {
			$this->add_log_error( esc_html_x( 'Failed to mark any lesson as complete.', 'Fluent Community', 'uncanny-automator' ) );
			return false;
		}

		// add course completion record
		\FluentCommunity\Modules\Course\Services\CourseHelper::completeCourse( $course, $user_id );

		// return token data
		$this->hydrate_tokens(
			array(
				'COURSE_ID'    => $course->id,
				'COURSE_TITLE' => $course->title,
			)
		);

		return true;
	}


	/**
	 * Define tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $tokens The destination.
	 * @return mixed
	 */
	public function define_tokens() {
		return array(
			'COURSE_ID'    => array(
				'name'      => esc_html_x( 'Course ID', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'COURSE_ID',
				'tokenName' => esc_html_x( 'Course ID', 'FluentCommunity', 'uncanny-automator' ),
			),
			'COURSE_TITLE' => array(
				'name'      => esc_html_x( 'Course title', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_TITLE',
				'tokenName' => esc_html_x( 'Course title', 'FluentCommunity', 'uncanny-automator' ),
			),
		);
	}
}
