<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_DELETE_CUSTOMER_BY_ID
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_DELETE_CUSTOMER_BY_ID extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'EDD' );
		$this->set_action_code( 'EDD_DELETE_CUSTOMER_BY_ID' );
		$this->set_action_meta( 'EDD_CUSTOMER' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Delete a customer that matches {{a customer ID:%1$s}}', 'Easy Digital Downloads', 'uncanny-automator-pro' ), $this->get_action_meta(), 'EXPIRATION_DATE:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Delete a customer by {{ID}}', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'     => $this->get_action_meta(),
					'input_type'      => 'int',
					'label'           => _x( 'Customer ID', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
					'description'     => _x( 'Warning: Deleting a customer deletes all associated history (orders, subscriptions, notes, etc.)', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
					'relevant_tokens' => array(),
				)
			),
		);

	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'CUSTOMER_EMAIL' => array(
				'name' => __( 'Customer email(s)', 'uncanny-automator-pro' ),
				'type' => 'email',
			),
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$customer_ID = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		if ( empty( $customer_ID ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a customer ID.', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );

			return false;
		}

		$customer_data = edd_get_customer( $customer_ID );
		if ( empty( $customer_data ) ) {
			$this->add_log_error( esc_attr_x( 'Customer not found.', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );

			return false;
		}

		$customer_deleted = edd_delete_customer( $customer_data->id );
		if ( 1 !== $customer_deleted ) {
			$this->add_log_error( $customer_deleted );

			return false;
		}

		$this->hydrate_tokens(
			array(
				'CUSTOMER_EMAIL' => $customer_data->email,
			)
		);

		return true;
	}

}
