<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_SUBMITASSIGNMENT
 *
 * @package Uncanny_Automator_Pro
 */
class LD_SUBMITESSAYQUIZ {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'LD_SUBMITESSAYQUIZ';
		$this->trigger_meta = 'LDESSAYQUIZ';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/learndash/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - LearnDash */
			'sentence'            => sprintf(
				__( 'A user submits an essay for {{a quiz:%1$s}}', 'uncanny-automator-pro' ),
				$this->trigger_meta
			),
			/* translators: Logged-in trigger - LearnDash */
			'select_option_name'  => __( 'A user submits an essay for {{a quiz}}', 'uncanny-automator-pro' ),
			'action'              => 'learndash_new_essay_submitted',
			'priority'            => 20,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'ld_essay_submitted' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		Automator()->helpers->recipe->wp->options->load_options = true;

		$args = array(
			'post_type'      => 'sfwd-quiz',
			'posts_per_page' => 9999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$quizzes = Automator()->helpers->recipe->options->wp_query( $args, true, esc_attr__( 'Any quiz', 'uncanny-automator-pro' ) );

		$relevant_tokens = array(
			$this->trigger_meta . '_ID'               => __( 'Essay ID', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_TITLE'            => __( 'Essay title', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_SUBMISSION_DATE'  => __( 'Essay submission date', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_CONTENT'          => __( 'Essay content', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDQUIZ_ID'        => __( 'Quiz ID', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDQUIZ_TITLE'     => __( 'Quiz title', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDCOURSE_ID'      => __( 'Course ID', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDCOURSE_TITLE'   => __( 'Course title', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDLESSON_ID'      => __( 'Lesson ID', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDLESSON_TITLE'   => __( 'Lesson title', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDQUESTION_ID'    => __( 'Question ID', 'uncanny-automator-pro' ),
			$this->trigger_meta . '_LDQUESTION_TITLE' => __( 'Question title', 'uncanny-automator-pro' ),
		);

		$all_quizzes = array(
			'input_type'            => 'select',
			'option_code'           => $this->trigger_meta,
			'label'                 => __( 'Quiz', 'uncanny-automator-pro' ),
			'required'              => true,
			'supports_tokens'       => true,
			'is_ajax'               => true,
			'fill_values_in'        => 'LDESSAY' . $this->trigger_meta,
			'endpoint'              => 'ld_select_quiz_essays',
			'options'               => $quizzes,
			'relevant_tokens'       => $relevant_tokens,
			'options_show_id'       => true,
			'supports_custom_value' => false,
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						$all_quizzes,
						Automator()->helpers->recipe->field->select(
							array(
								'option_code'           => 'LDESSAY' . $this->trigger_meta,
								'label'                 => esc_attr__( 'Essay', 'uncanny-automator-pro' ),
								'supports_tokens'       => false,
								'relevant_tokens'       => array(),
								'token_name'            => null,
								'supports_custom_value' => false,
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $essay_id
	 * @param $essay_args
	 */
	public function ld_essay_submitted( $essay_id, $essay_args ) {

		if ( 0 === (int) $essay_id || empty( $essay_args ) ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		if ( empty( $recipes ) ) {
			return;
		}

		$question_post_id = get_post_meta( $essay_id, 'question_post_id', true );
		$quiz_post_id     = get_post_meta( $essay_id, 'quiz_post_id', true );
		$course_id        = get_post_meta( $essay_id, 'course_id', true );
		$lesson_id        = get_post_meta( $essay_id, 'lesson_id', true );

		$essay_args['user_id'] = get_current_user_id();

		$required_quiz_id  = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$required_essay_id = Automator()->get->meta_from_recipes( $recipes, 'LDESSAY' . $this->trigger_meta );
		if ( empty( $required_quiz_id ) || empty( $required_essay_id ) ) {
			return;
		}

		$matched_recipe_ids = array();
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$recipe_id  = absint( $recipe_id );
				$trigger_id = absint( $trigger['ID'] );
				if ( ! isset( $required_quiz_id[ $recipe_id ] ) || ! isset( $required_essay_id[ $recipe_id ] ) ) {
					continue;
				}
				if ( ! isset( $required_quiz_id[ $recipe_id ][ $trigger_id ] ) || ! isset( $required_essay_id[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}
				if (
					(
						intval( '-1' ) === intval( $required_quiz_id[ $recipe_id ][ $trigger_id ] ) ||
						absint( $quiz_post_id ) === absint( $required_quiz_id[ $recipe_id ][ $trigger_id ] )
					)
					&&
					(
						intval( '-1' ) === intval( $required_essay_id[ $recipe_id ][ $trigger_id ] ) ||
						absint( $question_post_id ) === absint( $required_essay_id[ $recipe_id ][ $trigger_id ] )
					)
				) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'ignore_post_id'   => true,
				'user_id'          => $essay_args['user_id'],
				'is_signed_in'     => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			if ( empty( $args ) ) {
				continue;
			}
			foreach ( $args as $result ) {
				if ( true !== $result['result'] ) {
					continue;
				}
				Automator()->insert_trigger_meta(
					array(
						'user_id'        => $essay_args['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'meta_key'       => 'LDESSAY_ID',
						'meta_value'     => $essay_id,
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					)
				);

				Automator()->insert_trigger_meta(
					array(
						'user_id'        => $essay_args['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'meta_key'       => 'LDESSAY_QUESTION_ID',
						'meta_value'     => $question_post_id,
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					)
				);

				Automator()->insert_trigger_meta(
					array(
						'user_id'        => $essay_args['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'meta_key'       => 'LDCOURSE',
						'meta_value'     => $course_id,
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					)
				);

				Automator()->insert_trigger_meta(
					array(
						'user_id'        => $essay_args['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'meta_key'       => 'LDLESSON',
						'meta_value'     => $lesson_id,
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					)
				);

				Automator()->insert_trigger_meta(
					array(
						'user_id'        => $essay_args['user_id'],
						'trigger_id'     => $result['args']['trigger_id'],
						'meta_key'       => 'LDQUIZ',
						'meta_value'     => $quiz_post_id,
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					)
				);

				Automator()->maybe_trigger_complete( $result['args'] );
			}
		}

	}
}
