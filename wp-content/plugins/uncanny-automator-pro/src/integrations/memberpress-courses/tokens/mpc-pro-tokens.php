<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Mpc_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Mpc_Pro_Tokens {

	public function __construct() {
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 22, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'mpc_pro_token' ), 200, 6 );
		add_filter(
			'automator_maybe_trigger_mpc_mpc_quiz_tokens',
			array(
				$this,
				'mpc_quiz_possible_tokens',
			),
			20,
			2
		);
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function mpc_quiz_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'QUIZ_ID',
				'tokenName'       => __( 'Quiz ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_URL',
				'tokenName'       => __( 'Quiz URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_TITLE',
				'tokenName'       => __( 'Quiz title', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_THUMB_ID',
				'tokenName'       => __( 'Quiz featured image ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_THUMB_URL',
				'tokenName'       => __( 'Quiz featured image URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSSIBLE_POINTS',
				'tokenName'       => __( 'Possible points', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'AWARDED_POINTS',
				'tokenName'       => __( 'Awarded points', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_SCORE',
				'tokenName'       => __( "User's score", 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ATTEMPT_ID',
				'tokenName'       => __( 'Attempt ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_STARTED_AT',
				'tokenName'       => __( 'Quiz started at', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_FINISHED_AT',
				'tokenName'       => __( 'Quiz finished at', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'QUIZ_COURSE_ID',
				'tokenName'       => __( 'Course ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * save_token_data
	 *
	 * @param mixed $args
	 * @param mixed $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		$trigger_codes = array( 'MPC_QUIZ_SCORE', 'MPC_QUIZ_POINTS' );

		if ( in_array( $args['entry_args']['code'], $trigger_codes, true ) ) {
			list( $mpc )       = $args['trigger_args'];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $mpc ) ) {
				$quiz_args  = json_decode( $mpc->args );
				$attempt_id = $quiz_args->attempt_id;
				Automator()->db->token->save( 'attempt_id', $attempt_id, $trigger_log_entry );
			}
		}
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function mpc_pro_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_codes = array( 'MPC_QUIZ_SCORE', 'MPC_QUIZ_POINTS' );

		if ( ! in_array( $pieces[1], $trigger_codes, true ) ) {
			return $value;
		}

		if ( $pieces[2] === 'NUMBERCOND' ) {
			if ( isset( $trigger_data[0]['meta']['NUMBERCOND'] ) ) {
				return $trigger_data[0]['meta']['NUMBERCOND'];
			}
		}

		if ( $pieces[2] === 'QUIZSCORE' ) {
			if ( isset( $trigger_data[0]['meta']['QUIZSCORE'] ) ) {
				return $trigger_data[0]['meta']['QUIZSCORE'];
			}
		}

		if ( $pieces[2] === 'QUIZPOINTS' ) {
			if ( isset( $trigger_data[0]['meta']['QUIZPOINTS'] ) ) {
				return $trigger_data[0]['meta']['QUIZPOINTS'];
			}
		}

		$to_replace = $pieces[2];
		$attempt_id = Automator()->db->token->get( 'attempt_id', $replace_args );
		global $wpdb;
		$attempt_details = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->prefix}mpcs_attempts WHERE id=%d", $attempt_id ) );

		switch ( $to_replace ) {
			case 'QUIZ_ID':
				$value = $attempt_details->quiz_id;
				break;
			case 'QUIZ_URL':
				$value = get_permalink( $attempt_details->quiz_id );
				break;
			case 'QUIZ_TITLE':
				$value = get_the_title( $attempt_details->quiz_id );
				break;
			case 'QUIZ_THUMB_ID':
				$value = get_post_thumbnail_id( $attempt_details->quiz_id );
				break;
			case 'QUIZ_THUMB_URL':
				$value = get_the_post_thumbnail_url( $attempt_details->quiz_id );
				break;
			case 'POSSIBLE_POINTS':
				$value = $attempt_details->points_possible;
				break;
			case 'AWARDED_POINTS':
				$value = $attempt_details->points_awarded;
				break;
			case 'QUIZ_SCORE':
				$value = $attempt_details->score;
				break;
			case 'QUIZ_STARTED_AT':
				$value = $attempt_details->started_at;
				break;
			case 'QUIZ_FINISHED_AT':
				$value = $attempt_details->finished_at;
				break;
			case 'ATTEMPT_ID':
				$value = $attempt_id;
				break;
			case 'QUIZ_COURSE_ID':
				$quiz_id = $attempt_details->quiz_id;
				$quiz    = new \memberpress\courses\models\Quiz( $quiz_id );
				$course  = $quiz->course();
				if ( $course && $course->ID ) {
					$value = $course->ID;
				} else {
					$value = 0;
				}
				break;
		}

		return $value;
	}

}
