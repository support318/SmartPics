<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_USER_LIFETIME_VALUE
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_USER_LIFETIME_VALUE extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {

		$this->set_integration( 'EDD' );
		$this->set_trigger_code( 'EDD_LIFETIME_VALUE' );
		$this->set_trigger_meta( 'THRESHOLD' );
		$this->set_is_pro( true );
		$this->set_is_login_required( false );
		$this->set_sentence(
			// translators: %1$s is a number comparison, %2$s is a specific amount
			sprintf( esc_attr_x( 'A customer makes a payment and their lifetime value is {{greater than, less than, or equal to:%1$s}} {{a specific amount:%2$s}}', 'Easy Digital Downloads', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'COMPARISON:' . $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_attr_x( 'A customer makes a payment and their lifetime value is {{greater than, less than, or equal to}} {{a specific amount}}', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );
		$this->add_action( 'edd_customer_post_update', 10, 3 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => 'COMPARISON',
				'label'           => esc_html_x( 'Comparison', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => Automator()->helpers->recipe->edd->options->pro->less_greater_options(),
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'Threshold', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		if ( ! isset( $trigger['meta']['COMPARISON'] ) ) {
			return false;
		}

		$threshold = floatval( $trigger['meta'][ $this->get_trigger_meta() ] );

		$comparison = $trigger['meta']['COMPARISON'];

		$data = $hook_args[2];

		if ( ! isset( $data['purchase_value'] ) ) {
			return false;
		}

		$updated_lifetime_value = floatval( $data['purchase_value'] );

		switch ( $comparison ) {
			case '=':
				if ( $updated_lifetime_value !== $threshold ) {
					return false;
				}
				break;
			case '!=':
				if ( $updated_lifetime_value === $threshold ) {
					return false;
				}
				break;
			case '<':
				if ( $updated_lifetime_value >= $threshold ) {
					return false;
				}
				break;
			case '>':
				if ( $updated_lifetime_value <= $threshold ) {
					return false;
				}
				break;
			case '>=':
				if ( $updated_lifetime_value < $threshold ) {
					return false;
				}
				break;
			case '<=':
				if ( $updated_lifetime_value > $threshold ) {
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_ID',
			'tokenName' => esc_html_x( 'Customer ID', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_EMAIL',
			'tokenName' => esc_html_x( 'Customer email', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_NAME',
			'tokenName' => esc_html_x( 'Customer name', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_STATUS',
			'tokenName' => esc_html_x( 'Customer status', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_DATE_CREATED',
			'tokenName' => esc_html_x( 'Customer date created', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_PAYMENT_IDS',
			'tokenName' => esc_html_x( 'Customer payment IDs', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_USER_ID',
			'tokenName' => esc_html_x( 'Customer user ID', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_NOTES',
			'tokenName' => esc_html_x( 'Customer notes', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_PURCHASE_VALUE',
			'tokenName' => esc_html_x( 'Lifetime value', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'EDD_CUSTOMER_PURCHASE_COUNT',
			'tokenName' => esc_html_x( 'Purchase count', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		return $tokens;
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$customer_id = $hook_args[1];

		$customer = edd_get_customer( $customer_id );

		if ( ! $customer ) {
			return $token_values;
		}

		$token_values['EDD_CUSTOMER_ID']             = empty( $customer->id ) ? '' : $customer->id;
		$token_values['EDD_CUSTOMER_EMAIL']          = empty( $customer->email ) ? '' : $customer->email;
		$token_values['EDD_CUSTOMER_NAME']           = empty( $customer->name ) ? '' : $customer->name;
		$token_values['EDD_CUSTOMER_PURCHASE_COUNT'] = empty( $customer->purchase_count ) ? '' : $customer->purchase_count;
		$token_values['EDD_CUSTOMER_PURCHASE_VALUE'] = empty( $customer->purchase_value ) ? '' : $customer->purchase_value;
		$token_values['EDD_CUSTOMER_STATUS']         = empty( $customer->status ) ? '' : $customer->status;
		$token_values['EDD_CUSTOMER_DATE_CREATED']   = empty( $customer->date_created ) ? '' : $customer->date_created;
		$token_values['EDD_CUSTOMER_PAYMENT_IDS']    = empty( $customer->payment_ids ) ? '' : $customer->payment_ids;
		$token_values['EDD_CUSTOMER_USER_ID']        = empty( $customer->user_id ) ? '' : $customer->user_id;
		$token_values['EDD_CUSTOMER_NOTES']          = empty( $customer->notes ) ? '' : $customer->notes;

		return $token_values;
	}
}
