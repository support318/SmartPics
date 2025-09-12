<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_SPECIFIC_TIME
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_SPECIFIC_TIME extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The time}} {{is before or after}} {{a specific time}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_SPECIFIC_TIME';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( '{{The time:%1$s}} {{is before or after:%2$s}} {{a specific time:%3$s}}', 'uncanny-automator-pro' ),
			'TIME_A',
			'CRITERIA',
			'TIME_B'
		);
		$this->requires_user = false;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {
		$conditions = array(
			'option_code'           => 'CRITERIA',
			'label'                 => esc_html__( 'Criteria', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => array(
				array(
					'value' => 'is_before',
					'text'  => esc_html__( 'is before', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is_after',
					'text'  => esc_html__( 'is after', 'uncanny-automator-pro' ),
				),
			),
			'supports_custom_value' => false,
		);

		return array(
			// Token field
			$this->field->text(
				array(
					'option_code' => 'TIME_A',
					'label'       => esc_html__( 'TIME', 'uncanny-automator-pro' ),
					'placeholder' => '{{current_date_and_time}}',
					'default'     => '{{current_date_and_time}}',
					'input_type'  => 'text',
					'required'    => true,
				)
			),
			// Criteria field
			$this->field->select_field_args( $conditions ),
			// Value field
			$this->field->text(
				array(
					'option_code' => 'TIME_B',
					'label'       => esc_html__( 'Time', 'uncanny-automator-pro' ),
					'input_type'  => 'time',
					'required'    => true,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		try {
			$time_a = DateTime_Helpers::get_time( $this->get_parsed_option( 'TIME_A' ) );
			$time_b = DateTime_Helpers::get_time( $this->get_parsed_option( 'TIME_B' ) );
		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$format = get_option( 'time_format' );

		$error = $time_a->format( $format ) . ' %s ' . $time_b->format( $format );

		switch ( $criteria ) {
			case 'is_before':
				if ( $time_a == $time_b || $time_a > $time_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not before ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_after':
				if ( $time_a == $time_b || $time_a < $time_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not after ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}
	}


}
