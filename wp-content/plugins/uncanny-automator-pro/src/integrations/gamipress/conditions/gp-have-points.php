<?php

namespace Uncanny_Automator_Pro;

/**
 * Class GP_HAVE_POINTS
 *
 * @package Uncanny_Automator_Pro
 */
class GP_HAVE_POINTS extends Action_Condition {

	/**
	 * @return void
	 */
	public function define_condition() {
		$this->integration = 'GP';
		/* translators: Token */
		$this->name         = __( "The user's {{points type}} meets {{a condition}}", 'uncanny-automator-pro' );
		$this->code         = 'HAVE_POINTS';
		$this->dynamic_name = sprintf(
		/* translators: A token matches a value */
			esc_html__( "The user's {{points type:%2\$s}} total is {{greater than, less than, equal to, or not equal to:%1\$s}} {{a value:%3\$s}}", 'uncanny-automator-pro' ),
			'NUMBERCOND',
			'GP_POINTS_TYPE',
			'GP_POINTS'
		);
		$this->requires_user = true;
		$this->is_pro        = true;
	}

	/**
	 * @return array
	 */
	public function fields() {
		$point_types    = Automator()->helpers->recipe->gamipress->options->list_gp_points_types();
		$point_type_opt = array();
		foreach ( $point_types['options'] as $k => $option ) {
			$point_type_opt[] = array(
				'value' => $k,
				'text'  => _x( $option, 'GamiPress', 'uncanny-automator-pro' ),
			);
		}

		$number_condition     = Automator()->helpers->recipe->field->less_or_greater_than();
		$number_condition_opt = array();
		foreach ( $number_condition['options'] as $k => $option ) {
			$number_condition_opt[] = array(
				'value' => $k,
				'text'  => _x( $option, 'GamiPress', 'uncanny-automator-pro' ),
			);
		}

		return array(
			$this->field->select_field_args(
				array(
					'option_code'           => 'GP_POINTS_TYPE',
					'label'                 => esc_html_x( 'Point type', 'GamiPress', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => $point_type_opt,
					'supports_custom_value' => false,
					'options_show_id'       => false,
				)
			),
			$this->field->select_field_args(
				array(
					'option_code'            => 'NUMBERCOND',
					'label'                  => esc_html_x( 'Criteria', 'GamiPress', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => $number_condition_opt,
					'supports_custom_value'  => false,
					'options_show_id'        => false,
				)
			),
			$this->field->int(
				array(
					'option_code'            => 'GP_POINTS',
					'label'                  => esc_html_x( 'Value', 'GamiPress', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'supports_custom_value'  => false,
					'options_show_id'        => false,
				)
			),
		);
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {
		$points           = $this->get_parsed_option( 'GP_POINTS' );
		$number_condition = $this->get_parsed_option( 'NUMBERCOND' );
		$points_type      = $this->get_parsed_option( 'GP_POINTS_TYPE' );
		$user_points      = gamipress_get_user_points( $this->user_id, $points_type );

		$compare_points = $this->compare_given_points_by_user_points( $number_condition, $points, $user_points );

		if ( false === $compare_points ) {
			$log_error = sprintf( esc_attr_x( "The user's points (%1\$d) does not meet the conditional (%2\$s) criteria of entered value (%3\$d).", 'GamiPress', 'uncanny-automator-pro' ), $user_points, $this->get_parsed_option( 'NUMBERCOND_readable' ), $points );
			$this->condition_failed( $log_error );
		}

	}

	/**
	 * @param string $condition
	 * @param float $given_points
	 * @param float $user_points
	 *
	 * @return bool
	 */
	protected function compare_given_points_by_user_points( $condition, $given_points, $user_points ) {

		$user_points  = absint( $user_points );
		$given_points = absint( $given_points );

		if ( '<' === (string) $condition && $user_points < $given_points ) {
			return true;
		}
		if ( '>' === (string) $condition && $user_points > $given_points ) {
			return true;
		}
		if ( '=' === (string) $condition && $user_points === $given_points ) {
			return true;
		}
		if ( '!=' === (string) $condition && $user_points !== $given_points ) {
			return true;
		}
		if ( '<=' === (string) $condition && $user_points <= $given_points ) {
			return true;
		}
		if ( '>=' === (string) $condition && $user_points >= $given_points ) {
			return true;
		}

		return false;
	}
}
