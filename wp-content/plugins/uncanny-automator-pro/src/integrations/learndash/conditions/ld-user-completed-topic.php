<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_COMPLETED_A_TOPIC
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USER_COMPLETED_TOPIC extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'LD';
		/*translators: Token */
		$this->name = __( 'The user has completed {{a topic}}', 'uncanny-automator-pro' );
		$this->code = 'COMPLETED_A_TOPIC';
		// translators: A token matches a value
		$this->dynamic_name  = sprintf( esc_html__( 'The user has completed {{a topic:%1$s}}', 'uncanny-automator-pro' ), 'TOPIC' );
		$this->is_pro        = true;
		$this->requires_user = true;

	}

	/**
	 * Fields
	 *
	 * @return array
	 */
	public function fields() {

		$topics_field_args = array(
			'option_code'           => 'TOPIC',
			'label'                 => esc_html__( 'Topic', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->ld_topics_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Course field
			$this->field->select_field_args( $topics_field_args ),
		);
	}

	/**
	 * Load options
	 *
	 * @return array[]
	 */
	public function ld_topics_options() {

		$topics = Automator()->helpers->recipe->learndash->all_ld_topics();
		if ( empty( $topics['options'] ) ) {
			return array();
		}

		return $this->normalize_topics_options( $topics['options'] );

	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	public function normalize_topics_options( $options ) {
		$ld_topics = array();
		foreach ( $options as $topic_id => $topic_title ) {
			if ( intval( '-1' ) === $topic_id ) {
				continue;
			}
			$ld_topics[] = array(
				'value' => $topic_id,
				'text'  => $topic_title,
			);
		}

		return $ld_topics;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$parsed_topic = $this->get_parsed_option( 'TOPIC' );

		$has_completed = learndash_is_topic_complete( $this->user_id, $parsed_topic );

		// Check if the user is enrolled in the course here
		if ( false === (bool) $has_completed ) {

			$message = __( 'User has not completed topic ', 'uncanny-automator-pro' ) . $this->get_option( 'TOPIC_readable' );
			$this->condition_failed( $message );
		}
	}

}
