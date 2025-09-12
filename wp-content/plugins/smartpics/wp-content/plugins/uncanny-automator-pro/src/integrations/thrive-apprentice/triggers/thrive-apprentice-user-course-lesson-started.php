<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Trigger;
/**
 * Class THRIVE_APPRENTICE_USER_COURSE_LESSON_STARTED
 *
 * @package Uncanny_Automator
 */
class Thrive_Apprentice_User_Course_Lesson_Started extends Trigger {

	protected $helper;

	/**
	 * Constant TRIGGER_CODE.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'THRIVE_APPRENTICE_USER_COURSE_LESSON_STARTED';

	/**
	 * Constant TRIGGER_META.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'THRIVE_APPRENTICE_USER_COURSE_LESSON_STARTED_META';


	/**
	 * Define and register the trigger by pushing it into the Automator object.
	 *
	 * @return void.
	 */
	protected function setup_trigger() {
		$this->helper = array_shift( $this->dependencies );

		$this->set_integration( 'THRIVE_APPRENTICE' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );
		// The action hook to attach this trigger into.
		$this->add_action( 'thrive_apprentice_lesson_start' );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 2 );

		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Lesson Name, %2$s: Course Name */
				esc_html_x( 'A user starts {{a lesson:%1$s}} in {{a course:%2$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
				$this->get_trigger_meta(),
				'COURSE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'A user starts {{a lesson}} in {{a course}}', 'Thrive Apprentice', 'uncanny-automator-pro' )
		);
	}

	/**
	 * Loads available options for the Trigger.
	 *
	 * @return array The available trigger options.
	 */
	public function options() {
		return array(
			array(
				'option_code'     => 'COURSE',
				'required'        => true,
				'label'           => esc_html_x( 'Course', 'Thrive Apprentice', 'uncanny-automator' ),
				'input_type'      => 'select',
				'options'         => $this->helper->get_dropdown_options_courses( true, true ),
				'relevant_tokens' => array(),
			),
			array(
				'option_code'           => $this->get_trigger_meta(),
				'required'              => true,
				'label'                 => esc_html_x( 'Lesson', 'Thrive Apprentice', 'uncanny-automator' ),
				'input_type'            => 'select',
				'supports_custom_value' => false,
				'options'               => array(),
				'relevant_tokens'       => array(),
				'ajax'                  => array(
					'event'         => 'parent_fields_change',
					'endpoint'      => 'automator_thrive_apprentice_updated_lessons_handler',
					'listen_fields' => array( 'COURSE' ),
				),
			),
		);
	}

	/**
	 * Define tokens.
	 *
	 * @param array $trigger The trigger configuration.
	 * @param array $tokens The existing tokens.
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array(
			'COURSE_ID'      => array(
				'name'      => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'COURSE_ID',
				'tokenName' => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_TITLE'   => array(
				'name'      => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_TITLE',
				'tokenName' => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_URL'     => array(
				'name'      => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'COURSE_URL',
				'tokenName' => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_SUMMARY' => array(
				'name'      => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_SUMMARY',
				'tokenName' => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_AUTHOR'  => array(
				'name'      => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_AUTHOR',
				'tokenName' => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'LESSON_ID'      => array(
				'name'      => esc_html_x( 'Lesson ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'LESSON_ID',
				'tokenName' => esc_html_x( 'Lesson ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'LESSON_TITLE'   => array(
				'name'      => esc_html_x( 'Lesson title', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'LESSON_TITLE',
				'tokenName' => esc_html_x( 'Lesson title', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'LESSON_URL'     => array(
				'name'      => esc_html_x( 'Lesson URL', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'LESSON_URL',
				'tokenName' => esc_html_x( 'Lesson URL', 'Thrive Apprentice', 'uncanny-automator' ),
			),
		);
	}

	/**
	 * Validate the trigger.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 *
	 * @return bool True if validation was successful.
	 */
	public function validate( $trigger, $hook_args ) {
		list( $lesson, $user ) = $hook_args;

		if ( empty( $lesson ) || empty( $user ) ) {
			return false;
		}

		$lesson_id = absint( $lesson['lesson_id'] );
		$course_id = absint( $lesson['course_id'] );

		$selected_lesson_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_course_id = $trigger['meta']['COURSE'];

		$this->set_user_id( absint( $user['user_id'] ) );

		// If "Any course" is selected, return true
		if ( intval( '-1' ) === intval( $selected_course_id ) ) {
			return true;
		}

		// If "Any lesson" is selected, but course matches, return true
		if ( absint( $selected_course_id ) === $course_id && intval( '-1' ) === intval( $selected_lesson_id ) ) {
			return true;
		}

		// If "Specific lesson" is selected, but course and lesson matches, return true
		if ( absint( $selected_course_id ) === $course_id && absint( $selected_lesson_id ) === $lesson_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 * @return array The token values.
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		list( $lesson, $user ) = $hook_args;

		if ( empty( $lesson ) ) {
			return array();
		}

		$course_id = absint( $lesson['course_id'] );
		$lesson_id = absint( $lesson['lesson_id'] );

		$tva_author = get_term_meta( $course_id, 'tva_author', true );

		$user_data = ( ! empty( $tva_author ) && isset( $tva_author['ID'] ) ) ? get_userdata( $tva_author['ID'] ) : false;

		return array(
			'COURSE_ID'      => $course_id,
			'COURSE_URL'     => get_term_link( $course_id ),
			// TVA author's email address is wrong. Using the actual email of the user instead.
			'COURSE_AUTHOR'  => is_object( $user_data ) && ! empty( $user_data ) ? $user_data->user_email : '',
			'COURSE_SUMMARY' => get_term_meta( $course_id, 'tva_excerpt', true ),
			'COURSE_TITLE'   => isset( $lesson['course_title'] ) ? $lesson['course_title'] : '',
			'LESSON_TITLE'   => isset( $lesson['lesson_title'] ) ? $lesson['lesson_title'] : '',
			'LESSON_URL'     => isset( $lesson['lesson_url'] ) ? $lesson['lesson_url'] : '',
			'LESSON_ID'      => $lesson_id,
		);
	}
}
