<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_SUBMITASSIGNMENT
 *
 * @package Uncanny_Automator_Pro
 */
class LD_SUBMITASSIGNMENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	/**
	 * Trigger code
	 *
	 * @var string
	 */
	private $trigger_code;

	/**
	 * Trigger meta
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'LD_SUBMITASSIGNMENT';
		$this->trigger_meta = 'LDLESSONTOPIC';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 *
	 * @return void
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/learndash/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - LearnDash */
			'sentence'            => sprintf( esc_html_x( 'A user submits an assignment for {{a lesson or topic:%1$s}}', 'LearnDash', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - LearnDash */
			'select_option_name'  => esc_html_x( 'A user submits an assignment for {{a lesson or topic}}', 'Learndash', 'uncanny-automator-pro' ),
			'action'              => 'learndash_assignment_uploaded',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'assignment_uploaded' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Load options for the trigger
	 *
	 * @return array
	 */
	public function load_options() {
		$args = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => 9999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		);

		$course_relevant_tokens = array(
			'LDCOURSE'           => esc_html_x( 'Course title', 'Learndash', 'uncanny-automator' ),
			'LDCOURSE_ID'        => esc_html_x( 'Course ID', 'Learndash', 'uncanny-automator' ),
			'LDCOURSE_URL'       => esc_html_x( 'Course URL', 'Learndash', 'uncanny-automator' ),
			'LDCOURSE_THUMB_ID'  => esc_html_x( 'Course featured image ID', 'Learndash', 'uncanny-automator' ),
			'LDCOURSE_THUMB_URL' => esc_html_x( 'Course featured image URL', 'Learndash', 'uncanny-automator' ),
			'ASSIGNMENT_ID'      => esc_html_x( 'Assignment ID', 'Learndash', 'uncanny-automator' ),
			'ASSIGNMENT_URL'     => esc_html_x( 'Assignment URL', 'Learndash', 'uncanny-automator' ),
		);

		$lesson_relevant_tokens = array(
			$this->trigger_meta                => esc_html_x( 'Lesson/Topic title', 'Learndash', 'uncanny-automator' ),
			$this->trigger_meta . '_ID'        => esc_html_x( 'Lesson/Topic ID', 'Learndash', 'uncanny-automator' ),
			$this->trigger_meta . '_URL'       => esc_html_x( 'Lesson/Topic URL', 'Learndash', 'uncanny-automator' ),
			$this->trigger_meta . '_THUMB_ID'  => esc_html_x( 'Lesson/Topic featured image ID', 'Learndash', 'uncanny-automator' ),
			$this->trigger_meta . '_THUMB_URL' => esc_html_x( 'Lesson/Topic featured image URL', 'Learndash', 'uncanny-automator' ),
		);

		$course_options = Automator()->helpers->recipe->options->wp_query( $args, true, esc_html_x( 'Any course', 'Learndash', 'uncanny-automator' ) );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options'       => array(
					Automator()->helpers->recipe->options->number_of_times(),
				),
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDCOURSE',
							esc_html_x( 'Course', 'Learndash', 'uncanny-automator' ),
							$course_options,
							'',
							'',
							false,
							true,
							array(
								'target_field' => $this->trigger_meta,
								'endpoint'     => 'select_lessontopic_from_course_LD_SUBMITASSIGNMENT',
							),
							$course_relevant_tokens
						),
						Automator()->helpers->recipe->field->select_field(
							$this->trigger_meta,
							esc_html_x( 'Lesson/Topic', 'Learndash', 'uncanny-automator-pro' ),
							array(),
							false,
							false,
							false,
							$lesson_relevant_tokens
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param int   $assignment_post_id The assignment post ID.
	 * @param array $assignment_meta    The assignment meta data.
	 * @return void
	 */
	public function assignment_uploaded( $assignment_post_id, $assignment_meta ) {
		if ( empty( $assignment_meta ) ) {
			return;
		}

		$args = array(
			'code'         => $this->trigger_code,
			'meta'         => $this->trigger_meta,
			'post_id'      => $assignment_meta['lesson_id'],
			'user_id'      => $assignment_meta['user_id'],
			'is_signed_in' => true,
		);

		$args = Automator()->maybe_add_trigger_entry( $args, false );
		if ( $args ) {
			foreach ( $args as $result ) {
				if ( true === $result['result'] ) {
					Automator()->insert_trigger_meta(
						array(
							'user_id'        => $assignment_meta['user_id'],
							'trigger_id'     => $result['args']['trigger_id'],
							'meta_key'       => 'LDCOURSE',
							'meta_value'     => $assignment_meta['course_id'],
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						)
					);

					$trigger_meta = array(
						'user_id'        => $assignment_meta['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'trigger_log_id' => absint( $result['args']['trigger_log_id'] ),
						'run_number'     => absint( $result['args']['run_number'] ),
					);

					Automator()->db->token->save( 'ASSIGNMENT_URL', $assignment_meta['file_link'], $trigger_meta );
					Automator()->db->token->save( 'ASSIGNMENT_ID', $assignment_post_id, $trigger_meta );

					Automator()->maybe_trigger_complete( $result['args'] );
				}
			}
		}
	}
}
