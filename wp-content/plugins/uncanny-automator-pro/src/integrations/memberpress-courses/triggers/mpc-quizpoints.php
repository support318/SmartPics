<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class MPC_QUIZPOINTS
 *
 * @package Uncanny_Automator_Pro
 */
class MPC_QUIZPOINTS {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		// Because we've added a new function in `get_all()` filter
		if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '4.6', '>=' ) ) {
			$this->setup_trigger();
		}
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'MPC' );

		$this->set_trigger_code( 'MPC_QUIZ_POINTS' );

		$this->set_trigger_meta( 'MPC_QUIZ' );

		$this->set_is_login_required( true );

		$this->set_is_pro( true );

		$this->set_action_args_count( 1 );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user achieves points {{greater than, less than or equal to:%1$s}} a {{value:%2$s}} on a {{quiz:%3$s}} {{a number of:%4$s}} time(s)', 'uncanny-automator-pro' ), 'NUMBERCOND', 'QUIZPOINTS', $this->get_trigger_meta(), 'NUMTIMES' ) );

		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( 'A user achieves points {{greater than, less than or equal to}} a {{value}} on a {{quiz}}', 'uncanny-automator-pro' ) ); // Non-active state sentence to show

		// @see * MeprHooks::do_action('mepr-event-mpca-quiz-attempt-completed',$this);
		$this->set_action_hook( 'mepr-event-mpca-quiz-attempt-completed' );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->field->less_or_greater_than(),
					/* translators: Noun */
					Automator()->helpers->recipe->field->int(
						array(
							'option_code' => 'QUIZPOINTS',
							'label'       => esc_attr__( 'Required points', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Example: 1', 'uncanny-automator-pro' ),
							'default'     => null,
						)
					),
					Automator()->helpers->recipe->memberpress_courses->options->pro->get_all_mp_quiz( null, $this->get_trigger_meta(), array( 'uo_include_any' => true ) ),
					Automator()->helpers->recipe->options->number_of_times(),
				),
			)
		);

	}

	/**
	 * Validates the trigger before anything.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		$is_valid = false;

		if ( isset( $args[0] ) ) {
			// Only run for mpca-quiz-attempt-completed.
			if ( isset( $args[0][0]->event ) && 'mpca-quiz-attempt-completed' === $args[0][0]->event ) {
				$is_valid = true;
			}
		}

		return $is_valid;

	}

	/**
	 * Prepare to run.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Check quiz id and quiz awarded points
	 *
	 * @param ...$args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {

		list( $mrpr ) = $args[0];

		$quiz_args = json_decode( $mrpr->args );

		$attempt_details = $this->get_attempt( absint( $quiz_args->attempt_id ) );

		$quiz_id = absint( $attempt_details->quiz_id );

		$quiz_points = absint( $attempt_details->points_awarded );

		// Bailout if `with_number_condition` is not found.
		if ( ! method_exists( $this, 'with_number_condition' ) ) {
			return array();
		}

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->equals( array( $quiz_id ) )
					->compare( array( '=' ) )
					->with_number_condition( $quiz_points, 'QUIZPOINTS' )
					->format( array( 'intval' ) )
					->get();

	}

	/**
	 * @param $attempt_id
	 *
	 * @return array|object|\stdClass|void|null
	 */
	private function get_attempt( $attempt_id = 0 ) {

		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT quiz_id,points_awarded from {$wpdb->prefix}mpcs_attempts WHERE id=%d",
				$attempt_id
			)
		);

	}

}
