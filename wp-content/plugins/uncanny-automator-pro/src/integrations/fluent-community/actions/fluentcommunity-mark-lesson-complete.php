<?php

namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use FluentCommunity\Modules\Course\Model\Course;
use FluentCommunity\Modules\Course\Services\CourseHelper;
use Uncanny_Automator\Recipe\Action;

class FLUENTCOMMUNITY_MARK_LESSON_COMPLETE extends Action {

	protected $prefix = 'FLUENTCOMMUNITY_MARK_LESSON_COMPLETE';

	protected $helpers;

	/**
	 * Setup action.
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'FLUENT_COMMUNITY' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: The lesson title
				esc_html_x( 'Mark {{a lesson:%1$s}} in {{a course:%2$s}} as complete for a user', 'FluentCommunity', 'uncanny-automator' ),
				$this->get_action_meta() . ':' . $this->get_action_meta(),
				'COURSE:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'Mark {{a lesson}} as complete for a user', 'FluentCommunity', 'uncanny-automator' )
		);
	}

	/**
	 * Define the options for the action
	 *
	 * @return array The options for the action.
	 */
	public function options() {
		return array(
			array(
				'option_code'           => 'COURSE',
				'label'                 => esc_html_x( 'Course', 'FluentCommunity', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'options'               => $this->helpers->all_courses( false ),
				'supports_custom_value' => false,
			),
			array(
				'option_code'           => $this->get_action_meta(),
				'label'                 => esc_html_x( 'Lesson', 'FluentCommunity', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'options'               => array(),
				'ajax'                  => array(
					'endpoint'      => 'automator_fluentcommunity_lessons_fetch_for_action',
					'event'         => 'parent_fields_change',
					'listen_fields' => array( 'COURSE' ),
				),
				'supports_custom_value' => false,
			),
		);
	}
	/*
	 * Process the action
	 *
	 * @param int $user_id The user ID.
	 * @param array $action_data The action data.
	 * @param int $recipe_id The recipe ID.
	*/    /**
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
		$course_id = absint( $parsed['COURSE'] );
		$lesson_id = absint( $parsed[ $this->get_action_meta() ] );

		if ( ! $course_id || ! $lesson_id || ! $user_id ) {
			$this->add_log_error( esc_html_x( 'Missing user, course, or lesson.', 'FluentCommunity', 'uncanny-automator' ) );
			return false;
		}

		$lesson = \FluentCommunity\Modules\Course\Model\CourseLesson::where( 'id', $lesson_id )
			->where( 'space_id', $course_id )
			->where( 'status', 'published' )
			->first();

		if ( ! $lesson ) {
			$this->add_log_error( esc_html_x( 'Lesson not found, unpublished, or does not belong to the course.', 'FluentCommunity', 'uncanny-automator' ) );
			return false;
		}

		$completed = \FluentCommunity\Modules\Course\Services\CourseHelper::updateLessonCompletion( $lesson, $user_id, 'completed' );

		if ( ! $completed ) {
			$this->add_log_error( esc_html_x( 'Failed to mark lesson complete.', 'Fluent Community', 'uncanny-automator' ) );
			return false;
		}

		return true;
	}
}
