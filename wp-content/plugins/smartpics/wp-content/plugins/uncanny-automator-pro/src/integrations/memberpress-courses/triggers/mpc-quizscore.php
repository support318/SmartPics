<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class MPC_QUIZSCORE
 *
 * @package Uncanny_Automator_Pro
 */
class MPC_QUIZSCORE {

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

		$this->set_trigger_code( 'MPC_QUIZ_SCORE' );

		$this->set_trigger_meta( 'MPC_QUIZ' );

		$this->set_is_login_required( true );

		$this->set_is_pro( true );

		$this->set_action_args_count( 1 );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user achieves a score {{greater than, less than or equal to:%1$s}} a {{value:%2$s}} on a {{quiz:%3$s}} {{a number of:%4$s}} time(s)', 'uncanny-automator-pro' ), 'NUMBERCOND', 'QUIZSCORE', $this->get_trigger_meta(), 'NUMTIMES' ) );

		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( 'A user achieves a score {{greater than, less than or equal to}} a {{value}} on a {{quiz}}', 'uncanny-automator-pro' ) ); // Non-active state sentence to show

		// @see MeprHooks::do_action( 'mepr-event-mpca-quiz-attempt-completed', $this );
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
							'option_code' => 'QUIZSCORE',
							'label'       => esc_attr__( 'Required score', 'uncanny-automator-pro' ),
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
	 * Validate the trigger.
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
	 * Method prepare_to_run
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Check quiz id and quiz score
	 *
	 * @param ...$args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {

		list( $mrpr ) = $args[0];

		$quiz_args = json_decode( $mrpr->args );

		$attempt_details = $this->get_attempt( $quiz_args->attempt_id );

		$quiz_id = absint( $attempt_details->quiz_id );

		$quiz_score = absint( $attempt_details->score );

		// Bailout if `with_number_condition` is not found.
		if ( ! method_exists( $this, 'with_number_condition' ) ) {
			return array();
		}

		$match = $this->find_all( $this->trigger_recipes() )
			// Set to compare the field with.
					  ->where( array( $this->get_trigger_meta() ) )
			// Only compare quiz id.
					  ->equals( array( $quiz_id ) )
			// With equality sign.
					  ->compare( array( '=' ) )
			// Tell the filter that we have number condition.
			// Compare if `$quiz_score` [NUMBERCOND (e.g >, <, >=, <=)] `QUIZSCORE`
					  ->with_number_condition( $quiz_score, 'QUIZSCORE' )
			// Also format the quiz id with intval.
			// The number_conditoin is always casted as intval for both field and value to compare.
					  ->format( array( 'intval' ) )
			// Finally, get the result.
					  ->get();

		return $match;

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
				"SELECT quiz_id,score from {$wpdb->prefix}mpcs_attempts WHERE id=%d",
				$attempt_id
			)
		);

	}

}



