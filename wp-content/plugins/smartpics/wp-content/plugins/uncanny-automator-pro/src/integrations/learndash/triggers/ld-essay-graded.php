<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_ESSAY_GRADED
 *
 * @package Uncanny_Automator_Pro
 */
class LD_ESSAY_GRADED {

	use Recipe\Triggers;

	/**
	 * @var Ld_Pro_Tokens|null
	 */
	protected $ld_pro_tokens = null;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 *
	 * @return void
	 */
	public function setup_trigger() {
		$this->set_integration( 'LD' );
		$this->set_is_pro( true );
		$this->set_trigger_code( 'LD_ESSAY_GRADED' );
		$this->set_trigger_meta( 'LDESSAYQUESTION' );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/learndash/' ) );
		$this->set_sentence(
			/* Translators: 1. Assignment */
			sprintf( esc_html__( '{{An essay question:%1$s}} is graded', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( '{{An essay question}} is graded', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'learndash_essay_response_data_updated' );
		$this->set_action_args_count( 4 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}


	/**
	 * Load Condition Options.
	 *
	 * @return array
	 */
	public function load_options() {

		$trigger_meta = $this->get_trigger_meta();

		$course_relevant_tokens = array(
			'LDCOURSE'               => esc_attr__( 'Course title', 'uncanny-automator' ),
			'LDCOURSE_ID'            => esc_attr__( 'Course ID', 'uncanny-automator' ),
			'LDCOURSE_URL'           => esc_attr__( 'Course URL', 'uncanny-automator' ),
			'LDCOURSE_THUMB_ID'      => esc_attr__( 'Course featured image ID', 'uncanny-automator' ),
			'LDCOURSE_THUMB_URL'     => esc_attr__( 'Course featured image URL', 'uncanny-automator' ),
			'LDCOURSE_STATUS'        => esc_attr__( 'Course status', 'uncanny-automator' ),
			'LDCOURSE_ACCESS_EXPIRY' => esc_attr__( 'Course access expiry date', 'uncanny-automator' ),
		);

		$lesson_relevant_tokens = array(
			'LDSTEP'           => esc_attr__( 'Lesson/Topic title', 'uncanny-automator-pro' ),
			'LDSTEP_ID'        => esc_attr__( 'Lesson/Topic ID', 'uncanny-automator-pro' ),
			'LDSTEP_URL'       => esc_attr__( 'Lesson/Topic URL', 'uncanny-automator-pro' ),
			'LDSTEP_THUMB_ID'  => esc_attr__( 'Lesson/Topic featured image ID', 'uncanny-automator-pro' ),
			'LDSTEP_THUMB_URL' => esc_attr__( 'Lesson/Topic featured image URL', 'uncanny-automator-pro' ),
		);

		$quiz_relevant_tokens = array(
			'LDQUIZ'    => esc_attr__( 'Quiz title', 'uncanny-automator' ),
			'LDQUIZ_ID' => esc_attr__( 'Quiz ID', 'uncanny-automator' ),
		);

		if ( empty( $this->ld_pro_tokens ) ) {
			$this->ld_pro_tokens = new \Uncanny_Automator_Pro\Ld_Pro_Tokens( false );
		}

		if ( $this->ld_pro_tokens->can_get_ld_tokens_method( 'get_user_quiz_questions_and_answers' ) ) {
			$quiz_relevant_tokens['LDQUIZ_Q_AND_A']     = esc_attr__( 'Quiz questions and answers', 'uncanny-automator-pro' );
			$quiz_relevant_tokens['LDQUIZ_Q_AND_A_CSV'] = esc_attr__( 'Quiz question & answers ( unformatted )', 'uncanny-automator-pro' );
		}

		$question_relevant_tokens = array(
			$trigger_meta                      => esc_attr__( 'Question', 'uncanny-automator-pro' ),
			$trigger_meta . '_POINTS_EARNED'   => esc_attr__( 'Points earned', 'uncanny-automator-pro' ),
			$trigger_meta . '_POINTS_POSSIBLE' => esc_attr__( 'Points possible (total points possible for the essay)', 'uncanny-automator-pro' ),
		);

		// Query for courses.
		$args           = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);
		$course_options = Automator()->helpers->recipe->options->wp_query( $args, true, __( 'Any course', 'uncanny-automator' ) );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$trigger_meta => array(
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDCOURSE',
							__( 'Course', 'uncanny-automator' ),
							$course_options,
							'', //default
							'', //placeholder
							false, //supports tokens
							true, //is ajax
							array(
								'target_field' => 'LDSTEP',
								'endpoint'     => 'select_lessontopic_from_course',
							),
							$course_relevant_tokens
						),
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDSTEP',
							__( 'Lesson/Topic', 'uncanny-automator-pro' ),
							array(),
							'', //default
							'', //placeholder
							false, //supports tokens
							true, //is ajax
							array(
								'target_field' => 'LDQUIZ',
								'endpoint'     => 'select_quizzes_from_course_lessontopic_trigger',
							),
							$lesson_relevant_tokens
						),
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDQUIZ',
							__( 'Quiz', 'uncanny-automator' ),
							array(),
							'', //default
							'', //placeholder
							false, //supports tokens
							true, //is ajax
							array(
								'target_field' => $trigger_meta,
								'endpoint'     => 'select_essay_questions_from_course_lessontopic_quiz',
							),
							$quiz_relevant_tokens
						),

						Automator()->helpers->recipe->field->select(
							array(
								'option_code'     => $trigger_meta,
								'label'           => esc_attr__( 'Question', 'uncanny-automator' ),
								'options'         => array(),
								'relevant_tokens' => $question_relevant_tokens,
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Validate the trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		list( $quiz_id, $question_id, $essay, $submitted_essay ) = array_shift( $args );

		// Ensure it's an essay and graded.
		if ( ! is_a( $essay, 'WP_Post' ) || 'sfwd-essays' !== $essay->post_type || 'graded' !== $essay->post_status ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Validate the submitted data.
	 *
	 * @param ...$args

	 * @return bool
	 */
	public function validate_conditions( ...$args ) {

		list( $quiz_id, $question_id, $essay, $submitted_essay ) = array_shift( $args );

		$essay_id         = $essay->ID;
		$course_id        = get_post_meta( $essay->ID, 'course_id', true );
		$step_id          = get_post_meta( $essay->ID, 'lesson_id', true );
		$quiz_post_id     = get_post_meta( $essay->ID, 'quiz_post_id', true );
		$question_post_id = get_post_meta( $essay->ID, 'question_post_id', true );
		$trigger_meta     = $this->get_trigger_meta();

		// Find the matching recipe.
		return $this->find_all( $this->trigger_recipes() )
					->where(
						array(
							$trigger_meta,
							'LDCOURSE',
							'LDSTEP',
							'LDQUIZ',
						)
					)
					->match( array( $question_post_id, $course_id, $step_id, $quiz_post_id ) )
					->format( array( 'intval', 'intval', 'intval', 'intval' ) )
					->get();
	}

	/**
	 * Parse additional tokens.
	 *
	 * @param array $parsed
	 * @param array $args
	 * @param array $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {
		if ( empty( $this->ld_pro_tokens ) ) {
			$this->ld_pro_tokens = new \Uncanny_Automator_Pro\Ld_Pro_Tokens( false );
		}
		return $this->ld_pro_tokens->hydrate_essay_graded_tokens( $parsed, $args, $trigger );
	}

}
