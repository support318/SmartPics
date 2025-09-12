<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class THRIVE_APPRENTICE_USER_COURSE_MODULE_STARTED
 *
 * @package Uncanny_Automator
 */
class Thrive_Apprentice_User_Course_Module_Started extends Trigger {

	protected $helper;

	/**
	 * Constant TRIGGER_CODE.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'THRIVE_APPRENTICE_USER_COURSE_MODULE_STARTED';

	/**
	 * Constant TRIGGER_META.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'THRIVE_APPRENTICE_USER_COURSE_MODULE_STARTED_META';

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
		$this->add_action( 'thrive_apprentice_module_start' );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 2 );

		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Module Name, %2$s: Course Name */
				esc_html_x( 'A user starts {{a module:%1$s}} in {{a course:%2$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
				$this->get_trigger_meta(),
				'COURSE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'A user starts {{a module}} in {{a course}}', 'Thrive Apprentice', 'uncanny-automator-pro' )
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
				'label'           => esc_html_x( 'Course', 'Thrive Apprentice', 'uncanny-automator' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => $this->helper->get_dropdown_options_courses( true, true ),
				'relevant_tokens' => array(),
			),
			array(
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'Module', 'Thrive Apprentice', 'uncanny-automator' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(),
				'relevant_tokens' => array(),
				'ajax'            => array(
					'event'         => 'parent_fields_change',
					'endpoint'      => 'automator_thrive_apprentice_updated_modules_handler',
					'listen_fields' => array( 'COURSE' ),
				),
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

		list( $module, $user ) = $hook_args;

		if ( empty( $module ) || empty( $user ) ) {
			return false;
		}

		$this->set_user_id( absint( $user['user_id'] ) );

		$module_id          = absint( $module['module_id'] );
		$course_id          = absint( $module['course_id'] );
		$selected_module_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_course_id = $trigger['meta']['COURSE'];

		// Check module match - if any module is selected (-1) or if specific module matches
		$module_matches = intval( '-1' ) === intval( $selected_module_id ) || (int) $selected_module_id === (int) $module_id;

		// Check course match - if any course is selected (-1) or if specific course matches
		$course_matches = intval( '-1' ) === intval( $selected_course_id ) || (int) $selected_course_id === (int) $course_id;

		return $module_matches && $course_matches;
	}

	/**
	 * Define tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $tokens The destination.
	 * @return mixed
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array(
			'COURSE_ID'      => array(
				'name'      => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'COURSE_ID',
				'tokenName' => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_URL'     => array(
				'name'      => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_URL',
				'tokenName' => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_TITLE'   => array(
				'name'      => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_TITLE',
				'tokenName' => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_AUTHOR'  => array(
				'name'      => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_AUTHOR',
				'tokenName' => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_SUMMARY' => array(
				'name'      => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_SUMMARY',
				'tokenName' => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'MODULE_ID'      => array(
				'name'      => esc_html_x( 'Module ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'MODULE_ID',
				'tokenName' => esc_html_x( 'Module ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'MODULE_TITLE'   => array(
				'name'      => esc_html_x( 'Module title', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'MODULE_TITLE',
				'tokenName' => esc_html_x( 'Module title', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'MODULE_URL'     => array(
				'name'      => esc_html_x( 'Module URL', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'MODULE_URL',
				'tokenName' => esc_html_x( 'Module URL', 'Thrive Apprentice', 'uncanny-automator' ),
			),
		);
	}
	/**
	 * Hydrate tokens.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 * @return array The token values.
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		list( $module, $user ) = $hook_args;

		if ( empty( $module ) ) {
			return array();
		}

		$course_id = absint( $module['course_id'] );
		$module_id = absint( $module['module_id'] );

		$tva_author = get_term_meta( $course_id, 'tva_author', true );

		$user_data = ( ! empty( $tva_author ) && isset( $tva_author['ID'] ) ) ? get_userdata( $tva_author['ID'] ) : false;

		return array(
			'COURSE_ID'      => $course_id,
			'COURSE_URL'     => get_term_link( $course_id ),
			'COURSE_TITLE'   => isset( $module['course_title'] ) ? $module['course_title'] : '',
			'COURSE_AUTHOR'  => is_object( $user_data ) && ! empty( $user_data ) ? $user_data->user_email : '',
			'COURSE_SUMMARY' => get_term_meta( $course_id, 'tva_excerpt', true ),
			'MODULE_ID'      => $module_id,
			'MODULE_TITLE'   => isset( $module['module_title'] ) ? $module['module_title'] : '',
			'MODULE_URL'     => isset( $module['module_url'] ) ? $module['module_url'] : '',
		);
	}
}
