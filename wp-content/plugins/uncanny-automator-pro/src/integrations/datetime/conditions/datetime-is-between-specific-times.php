<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_BETWEEN_SPECIFIC_TIMES
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_BETWEEN_SPECIFIC_TIMES extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The time}} {{is}} between {{a specific time}} and {{a specific time}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_BETWEEN_SPECIFIC_TIMES';
		$this->dynamic_name = sprintf(
		/* translators: 1. Time 2. Criteria 3. Time 4. Time */
			esc_html__( '{{The time:%1$s}} {{is:%2$s}} between {{a specific time:%3$s}} and {{a specific time:%4$s}}', 'uncanny-automator-pro' ),
			'TIME_A',
			'CRITERIA',
			'TIME_B',
			'TIME_C'
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
					'value' => 'is',
					'text'  => esc_html__( 'is', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is_not',
					'text'  => esc_html__( 'is not', 'uncanny-automator-pro' ),
				),
			),
			'supports_custom_value' => false,
		);

		return array(
			// Token field
			$this->field->text(
				array(
					'option_code' => 'TIME_A',
					'label'       => esc_html__( 'Time', 'uncanny-automator-pro' ),
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
					'label'       => esc_html__( 'Start time', 'uncanny-automator-pro' ),
					'input_type'  => 'time',
					'required'    => true,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'TIME_C',
					'label'       => esc_html__( 'End time', 'uncanny-automator-pro' ),
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
			$input  = $this->get_parsed_option( 'TIME_A' );
			$time_a = DateTime_Helpers::get_time( $input );
			$time_b = DateTime_Helpers::get_time( $this->get_parsed_option( 'TIME_B' ) );
			$time_c = DateTime_Helpers::get_time( $this->get_parsed_option( 'TIME_C' ) );

		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$date_format = get_option( 'time_format' );

		$error = $input . ' %s ' . $time_b->format( $date_format ) . ' %s ' . $time_c->format( $date_format );

		switch ( $criteria ) {
			case 'is':
				if ( ( $time_a != $time_b && $time_a < $time_b ) || ( $time_a != $time_c && $time_a > $time_c ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not between', 'uncanny-automator-pro' ), __( ' and ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_not':
				if ( $time_a == $time_b || $time_a == $time_c || ( $time_a > $time_b && $time_a < $time_c ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is between', 'uncanny-automator-pro' ), __( ' and ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}

	}


}
