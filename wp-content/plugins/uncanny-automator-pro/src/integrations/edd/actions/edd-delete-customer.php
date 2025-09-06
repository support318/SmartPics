<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_DELETE_CUSTOMER
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_DELETE_CUSTOMER extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'EDD' );
		$this->set_action_code( 'EDD_DELETE_CUSTOMER' );
		$this->set_action_meta( 'EDD_CUSTOMER' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Delete a customer that matches {{an email:%1$s}}', 'Easy Digital Downloads', 'uncanny-automator' ), $this->get_action_meta(), 'EXPIRATION_DATE:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Delete a customer by {{email}}', 'Easy Digital Downloads', 'uncanny-automator' ) );
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
					'label'           => _x( 'Email', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
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
			'CUSTOMER_ID' => array(
				'name' => __( 'Customer ID', 'uncanny-automator-pro' ),
				'type' => 'int',
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
		$customer_email = sanitize_email( $parsed[ $this->get_action_meta() ] );
		if ( empty( $customer_email ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter an email address.', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );

			return false;
		}

		$customer_data = edd_get_customer_by( 'email', $customer_email );
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
				'CUSTOMER_ID' => $customer_data->id,
			)
		);

		return true;
	}

}
