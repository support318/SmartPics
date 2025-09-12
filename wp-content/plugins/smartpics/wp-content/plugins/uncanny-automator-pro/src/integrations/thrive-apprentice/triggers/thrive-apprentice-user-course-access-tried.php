<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class THRIVE_APPRENTICE_USER_COURSE_ACCESS_TRIED
 *
 * @package Uncanny_Automator
 */
class THRIVE_APPRENTICE_USER_COURSE_ACCESS_TRIED extends Trigger {

	protected $helper;

	/**
	 * Constant TRIGGER_CODE.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'THRIVE_APPRENTICE_USER_COURSE_ACCESS_TRIED';

	/**
	 * Constant TRIGGER_META.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'THRIVE_APPRENTICE_USER_COURSE_ACCESS_TRIED_META';

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
		$this->add_action( 'thrive_apprentice_restricted_course' );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 2 );

		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Course Name */
				esc_attr_x( 'A user attempts to access {{a restricted course:%1$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_attr_x( 'A user attempts to access {{a restricted course}}', 'Thrive Apprentice', 'uncanny-automator-pro' )
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
	 * Validate the trigger.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 *
	 * @return bool True if validation was successful.
	 */
	public function validate( $trigger, $hook_args ) {
		list( $course, $user ) = $hook_args;

		if ( empty( $course ) || empty( $user ) ) {
			return false;
		}

		$this->set_user_id( absint( $user['user_id'] ) );

		$course_id          = absint( $course['course_id'] );
		$selected_course_id = $trigger['meta'][ $this->get_trigger_meta() ];

		// Match if any course is selected (-1) or if specific course matches
		return intval( '-1' ) === intval( $selected_course_id ) || (int) $selected_course_id === (int) $course_id;
	}


	/**
	 * Hydrate tokens with values.
	 *
	 * @param array $trigger The trigger data.
	 * @param array $hook_args The hook arguments.
	 *
	 * @return array The token values.
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $course, $user ) = $hook_args;

		if ( empty( $course ) ) {
			return array();
		}

		$course_id = absint( $course['course_id'] );

		$tva_author_id = '';
		if ( ! empty( $course_id ) ) {
			$tva_author_meta = get_term_meta( $course_id, 'tva_author', true );
			if ( is_array( $tva_author_meta ) && ! empty( $tva_author_meta['ID'] ) ) {
				$tva_author_id = $tva_author_meta['ID'];
			}
		}

		$user_data     = ! empty( $tva_author_id ) ? get_userdata( $tva_author_id ) : null;
		$course_author = ( is_object( $user_data ) && ! empty( $user_data->user_email ) )
			? $user_data->display_name . ' (' . $user_data->user_email . ')'
			: '';

		$course_url = get_term_link( $course_id );
		if ( is_wp_error( $course_url ) ) {
			$course_url = '';
		}

		return array(
			'COURSE_ID'      => $course_id,
			'COURSE_TITLE'   => isset( $course['course_title'] ) ? $course['course_title'] : '',
			'COURSE_URL'     => $course_url,
			'COURSE_SUMMARY' => get_term_meta( $course_id, 'tva_excerpt', true ),
			'COURSE_AUTHOR'  => $course_author,
		);
	}


	/**
	 * Define tokens.
	 *
	 * @param array $trigger The trigger configuration.
	 * @param array $tokens The existing tokens.
	 *
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
				'tokenName' => esc_html_x( 'Course description', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'COURSE_AUTHOR'  => array(
				'name'      => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COURSE_AUTHOR',
				'tokenName' => esc_html_x( 'Course author', 'Thrive Apprentice', 'uncanny-automator' ),
			),
		);
	}
}
