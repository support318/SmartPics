<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Jet_Crm_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class  JETCRM_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class JETCRM_STATUS_CHANGED {

	use Recipe\Actions;

	protected $helper;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Jet_Crm_Helpers' ) ) {
			return;
		}
		$this->setup_action();
		$this->helper = new Jet_Crm_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'JETCRM' );
		$this->set_action_code( 'JETCRM_STATUS_UPDATED' );
		$this->set_action_meta( 'JETCRM_CONTACT_STATUS' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		/* translators: Action - JetPack CRM */
		$this->set_sentence( sprintf( esc_attr__( "Change a contact's status to {{a new status:%1\$s}}", 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - JetPack CRM */
		$this->set_readable_sentence( esc_attr__( "Change a contact's status to {{a new status}}", 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						$this->helper->contact_statuses( $this->get_action_meta() ),
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'CONTACT_EMAIL',
								'input_type'  => 'email',
								'label'       => esc_attr__( 'Email', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);

	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$status        = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$contact_email = isset( $parsed['CONTACT_EMAIL'] ) ? sanitize_text_field( $parsed['CONTACT_EMAIL'] ) : '';

		if ( empty( $status ) || empty( $contact_email ) ) {
			return;
		}

		global $wpdb;
		$contact = $wpdb->get_row( $wpdb->prepare( "SELECT `ID`,`zbsc_status` FROM `{$wpdb->prefix}zbs_contacts` WHERE zbsc_email LIKE %s", $contact_email ) );
		if ( ! empty( $contact ) && $status !== $contact->zbsc_status ) {
			$wpdb->update( "{$wpdb->prefix}zbs_contacts", array( 'zbsc_status' => $status ), array( 'ID' => $contact->ID ) );
			Automator()->complete->action( $user_id, $action_data, $recipe_id );

			return;
		}
		$error_message = sprintf( __( 'Contact was not found matching (%s)', 'uncanny-automator-pro' ), $contact_email );
		if ( ! empty( $contact ) && $status === $contact->zbsc_status ) {
			$error_message = sprintf( __( 'Contact status is already set to (%s)', 'uncanny-automator-pro' ), $status );
		}
		$action_data['do-nothing']           = true;
		$action_data['complete_with_errors'] = true;
		Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
	}

}
