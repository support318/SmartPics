<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USER_ID_MEETS_CONDITION
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_ID_MEETS_CONDITION extends Action_Condition {

	/**
	 * define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'WP';
		/*translators: Token */
		$this->name = __( "The user's ID meets {{a condition}}", 'uncanny-automator-pro' );
		/*translators: A token matches a value */
		$this->dynamic_name  = sprintf( esc_html__( "The user's ID meets {{a condition:%1\$s}} {{value:%2\$s}}", 'uncanny-automator-pro' ), 'CRITERIA', 'VALUE' );
		$this->code          = 'USERID_MEETS_CONDITION';
		$this->requires_user = true;
	}

	/**
	 * fields
	 *
	 * @return array
	 */
	public function fields() {
		$conditions = array(
			'option_code'           => 'CRITERIA',
			'label'                 => esc_html__( 'Criteria', 'uncanny-automator-pro' ),
			'required'              => true,
			'options_show_id'       => false,
			'options'               => apply_filters(
				'automator_pro_wp_token_meets_condition_criteria_options',
				array(
					array(
						'value' => 'is',
						'text'  => esc_html__( 'equal to', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'is_not',
						'text'  => esc_html__( 'not equal to', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'contains',
						'text'  => esc_html__( 'contains', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'does_not_contain',
						'text'  => esc_html__( 'does not contain', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'is_greater_than',
						'text'  => esc_html__( 'is greater than', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'is_greater_than_or_equal_to',
						'text'  => esc_html__( 'is greater than or equal to', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'is_less_than',
						'text'  => esc_html__( 'is less than', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'is_less_than_or_equal_to',
						'text'  => esc_html__( 'is less than or equal to', 'uncanny-automator-pro' ),
					),

				)
			),
			'supports_custom_value' => false,
		);

		return array(
			// Criteria field
			$this->field->select_field_args( $conditions ),
			// Value field
			$this->field->text(
				array(
					'option_code' => 'VALUE',
					'label'       => esc_html__( 'Value', 'uncanny-automator-pro' ),
					'placeholder' => esc_html__( 'Value', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
					'required'    => false,
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
	 */
	public function evaluate_condition() {
		$parsed_value  = $this->get_parsed_option( 'VALUE' );
		$criteria      = $this->get_option( 'CRITERIA' );
		$condition_met = $this->check_logic( $this->user_id, $criteria, $parsed_value );

		if ( false === $condition_met ) {
			$readable_criteria = $this->get_option( 'CRITERIA_readable' );
			$log_error         = sprintf( __( 'The user ID %1$s %2$s %3$s ', 'uncanny-automator-pro' ), $this->user_id, $readable_criteria, $parsed_value );
			$this->condition_failed( $log_error );
		}

	}

	/**
	 * Check_logic
	 *
	 * This function will check the values against the logic selected
	 *
	 * @param mixed $user_id
	 * @param mixed $criteria
	 * @param mixed $parsed_value
	 *
	 * @return bool
	 */
	public function check_logic( $user_id, $criteria, $parsed_value ) {

		switch ( $criteria ) {
			case 'is':
				$result = intval( $user_id ) === intval( $parsed_value ); //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				break;
			case 'is_not':
				$result = intval( $user_id ) !== intval( $parsed_value ); //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				break;
			case 'contains':
				if ( is_array( $parsed_value ) || is_object( $parsed_value ) ) {
					$parsed_value = join( ' ', $parsed_value );
				}
				$result = stripos( $user_id, $parsed_value ) !== false;
				break;
			case 'does_not_contain':
				if ( is_array( $parsed_value ) || is_object( $parsed_value ) ) {
					$parsed_value = join( ' ', $parsed_value );
				}
				$result = stripos( $user_id, $parsed_value ) === false;
				break;
			case 'is_greater_than':
				$result = intval( $user_id ) > intval( $parsed_value );
				break;
			case 'is_greater_than_or_equal_to':
				$result = intval( $user_id ) >= intval( $parsed_value );
				break;
			case 'is_less_than':
				$result = intval( $user_id ) < intval( $parsed_value );
				break;
			case 'is_less_than_or_equal_to':
				$result = intval( $user_id ) <= intval( $parsed_value );
				break;
			default:
				$result = true;
				break;
		}

		return $result;
	}
}
