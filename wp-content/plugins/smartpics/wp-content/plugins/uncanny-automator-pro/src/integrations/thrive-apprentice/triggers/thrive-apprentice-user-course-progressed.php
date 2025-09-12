<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class THRIVE_APPRENTICE_USER_COURSE_PROGRESSED
 *
 * @package Uncanny_Automator
 */
class Thrive_Apprentice_User_Course_Progressed extends Trigger {

	/**
	 * Constant TRIGGER_CODE.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'THRIVE_APPRENTICE_USER_COURSE_PROGRESSED';

	/**
	 * Constant TRIGGER_META.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'THRIVE_APPRENTICE_USER_COURSE_PROGRESSED_META';

	/**
	 * The helper instance.
	 *
	 * @var Thrive_Apprentice_Pro_Helpers
	 */
	protected $helper;

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
		$this->add_action( 'thrive_apprentice_course_progress' );

		$this->set_sentence(
			sprintf(
				// translators: %1$s is the course title.
				esc_html_x( 'A user progresses in {{a course:%1$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
		/* Translators: Trigger sentence */
			esc_html_x( 'A user progresses in {{a course}}', 'Thrive Apprentice', 'uncanny-automator-pro' )
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
				'option_code'     => $this->get_trigger_meta(),
				'required'        => true,
				'label'           => esc_html_x( 'Course', 'Thrive Apprentice', 'uncanny-automator' ),
				'input_type'      => 'select',
				'options'         => $this->helper->get_dropdown_options_courses( true, true ),
				'relevant_tokens' => array(),
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
			'COURSE_ID'                   => array(
				'name'      => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'COURSE_ID',
				'tokenName' => esc_html_x( 'Course ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_TITLE'                => array(
				'name'      => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_TITLE',
				'tokenName' => esc_html_x( 'Course title', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_URL'                  => array(
				'name'      => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'COURSE_URL',
				'tokenName' => esc_html_x( 'Course URL', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_SUMMARY'              => array(
				'name'      => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_SUMMARY',
				'tokenName' => esc_html_x( 'Course summary', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_AUTHOR'               => array(
				'name'      => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_AUTHOR',
				'tokenName' => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_PERCENTAGE_COMPLETED' => array(
				'name'      => esc_html_x( 'Course percentage completed', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_PERCENTAGE_COMPLETED',
				'tokenName' => esc_html_x( 'Course percentage completed', 'Thrive Apprentice', 'uncanny-automator' ),
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

		list( $term, $user ) = $hook_args;

		if ( empty( $term ) || empty( $user ) ) {
			return false;
		}

		// Handle both array and object cases for term
		$course_id = 0;
		if ( is_object( $term ) && isset( $term->term_id ) ) {
			$course_id = $term->term_id;
		} elseif ( is_array( $term ) ) {
			// Check for both term_id and course_id
			$course_id = isset( $term['course_id'] ) ? $term['course_id'] : ( isset( $term['term_id'] ) ? $term['term_id'] : 0 );
		}

		if ( empty( $course_id ) ) {
			return false;
		}

		$this->set_user_id( is_array( $user ) ? absint( $user['user_id'] ) : absint( $user ) );

		$selected_course_id = $trigger['meta'][ $this->get_trigger_meta() ];

		// Match if any course is selected (-1) or if specific course matches
		return intval( '-1' ) === intval( $selected_course_id ) || (int) $selected_course_id === (int) $course_id;
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 * @return array The token values.
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		list( $term, $user ) = $hook_args;

		// Handle both array and object cases for term
		$course_id = 0;
		if ( is_object( $term ) && isset( $term->term_id ) ) {
			$course_id = $term->term_id;
		} elseif ( is_array( $term ) ) {
			// Check for both term_id and course_id
			$course_id = isset( $term['course_id'] ) ? $term['course_id'] : ( isset( $term['term_id'] ) ? $term['term_id'] : 0 );
		}

		if ( empty( $course_id ) ) {
			return array();
		}

		$user_id = is_array( $user ) ? absint( $user['user_id'] ) : absint( $user );

		// Get course term data
		$course_term  = get_term( $course_id );
		$course_title = is_object( $course_term ) ? $course_term->name : '';

		$tva_author = get_term_meta( $course_id, 'tva_author', true );
		$user_data  = ( ! empty( $tva_author ) && isset( $tva_author['ID'] ) ) ? get_userdata( $tva_author['ID'] ) : false;

		// Get course progress using helper method
		$progress = '0%';
		try {
			if ( $this->helper && method_exists( $this->helper, 'get_course_progress_percentage' ) ) {
				$progress = $this->helper->get_course_progress_percentage( $user_id, $course_id );
			}
		} catch ( \Exception $e ) {
			// Fallback to 0% if helper method fails
			$progress = '0%';
		}

		return array(
			'COURSE_ID'                   => $course_id,
			'COURSE_TITLE'                => $course_title,
			'COURSE_URL'                  => get_term_link( $course_id ),
			'COURSE_AUTHOR'               => is_object( $user_data ) && ! empty( $user_data ) ? $user_data->user_email : '',
			'COURSE_SUMMARY'              => get_term_meta( $course_id, 'tva_excerpt', true ),
			'COURSE_PERCENTAGE_COMPLETED' => $progress,
		);
	}
}
