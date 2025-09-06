<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_INCORRECT_ANSWER
 *
 * @package Uncanny_Automator_Pro
 */
class LD_INCORRECT_ANSWER {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->setup_trigger();
		$this->set_helper( new Learndash_Pro_Helpers() );
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'LD' );
		$this->set_trigger_code( 'LD_INCORRECT_ANSWER' );
		$this->set_trigger_meta( 'LD_QUESTIONS' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/learndash/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( 'A user answers {{a quiz:%1$s}} question incorrectly {{a number of:%2$s}} time(s)', 'uncanny-automator-pro' ), 'LD_QUIZZES:' . $this->get_trigger_meta(), 'NUMTIMES' )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A user answers {{a quiz}} question incorrectly', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'automator_learndash_quiz_question_answered' );
		$this->set_action_args_count( 3 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						$this->get_helper()->all_ld_quizzes( null, 'LD_QUIZZES', true ),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code' => $this->get_trigger_meta(),
								'label'       => __( 'Question', 'uncanny-automator-pro' ),
							)
						),
					),
				),
				'options'       => array(
					Automator()->helpers->recipe->options->number_of_times(),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $correct, $question_id, $quiz_id ) = array_shift( $args );

		if ( true === $correct ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check contact status against the trigger meta
	 *
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public function validate_conditions( ...$args ) {
		list( $correct, $question_id, $quiz_id ) = $args[0];

		return $this->find_all( $this->trigger_recipes() )
					->where(
						array(
							'LD_QUIZZES',
							$this->get_trigger_meta(),
						)
					)
					->match(
						array(
							intval( $quiz_id ),
							intval( $question_id ),
						)
					)
					->format(
						array(
							'intval',
							'intval',
						)
					)
					->get();
	}
}
