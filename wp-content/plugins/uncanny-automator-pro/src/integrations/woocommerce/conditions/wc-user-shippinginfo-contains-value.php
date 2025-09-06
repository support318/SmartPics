<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_USER_SHIPPINGINFO_CONTAINS_VALUE
 *
 * @package Uncanny_Automator_Pro
 */
class WC_USER_SHIPPINGINFO_CONTAINS_VALUE extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'WC';
		/*translators: Token */
		$this->name = __( "The user's {{shipping information}} {{contains}} {{a specific value}}", 'uncanny-automator-pro' );
		$this->code = 'SHIPPINGINFO_CONTAINS_VALUE';
		/*translators: A token matches a value */
		$this->dynamic_name  = sprintf( esc_html__( "The user's shipping {{information:%1\$s}} {{contains:%2\$s}} {{a specific value:%3\$s}}", 'uncanny-automator-pro' ), 'SHIPPING_FIELD', 'SHIPPING_CONDITION', 'SHIPPING_FIELD_VALUE' );
		$this->is_pro        = true;
		$this->requires_user = true;
		$this->deprecated    = false;
	}

	/**
	 * fields
	 *
	 * @return array
	 */
	public function fields() {

		$shipping_field_args = array(
			'option_code'           => 'SHIPPING_FIELD',
			'label'                 => esc_html__( 'Information', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->wc_shippinginfo_fields(),
			'supports_custom_value' => true,
			'options_show_id'       => false,
		);

		return array(
			// Product field
			$this->field->select_field_args( $shipping_field_args ),
			$this->field->select_field_args(
				array(
					'option_code'           => 'SHIPPING_CONDITION',
					'label'                 => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => array(
						array(
							'value' => 'contains',
							'text'  => __( 'contains', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'not_contains',
							'text'  => __( 'does not contain', 'uncanny-automator-pro' ),
						),
					),
					'supports_custom_value' => false,
					'options_show_id'       => false,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'SHIPPING_FIELD_VALUE',
					'label'       => esc_html__( 'Value', 'uncanny-automator-pro' ),
					'required'    => true,
				)
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function wc_shippinginfo_fields() {

		$options = array(
			'shipping_address_1' => __( 'Address line 1', 'uncanny-automator-pro' ),
			'shipping_address_2' => __( 'Address line 2', 'uncanny-automator-pro' ),
			'shipping_city'      => __( 'City', 'uncanny-automator-pro' ),
			'shipping_state'     => __( 'State', 'uncanny-automator-pro' ),
			'shipping_country'   => __( 'Country', 'uncanny-automator-pro' ),
			'shipping_phone'     => __( 'Phone', 'uncanny-automator-pro' ),
			'shipping_company'   => __( 'Company', 'uncanny-automator-pro' ),
		);

		foreach ( $options as $id => $text ) {
			$return[] = array(
				'value' => $id,
				'text'  => $text,
			);
		}

		return $return;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$shipping_field = $this->get_parsed_option( 'SHIPPING_FIELD' );
		$shipping_value = $this->get_parsed_option( 'SHIPPING_FIELD_VALUE' );
		$condition      = $this->get_parsed_option( 'SHIPPING_CONDITION' );
		$user_id        = $this->user_id;
		$user_meta      = get_user_meta( $user_id, $shipping_field, true );
		$position       = strpos( strtolower( $user_meta ), strtolower( $shipping_value ) );
		switch ( $condition ) {
			case 'contains':
				if ( false === $position ) {
					$message = __( 'Shipping info does not contain value: ', 'uncanny-automator-pro' ) . $shipping_value;
					$this->condition_failed( $message );
				}
				break;
			case 'not_contains':
				if ( is_numeric( $position ) ) {
					$message = __( 'Shipping info contains value: ', 'uncanny-automator-pro' ) . $shipping_value;
					$this->condition_failed( $message );
				}
				break;
		}
	}

}
