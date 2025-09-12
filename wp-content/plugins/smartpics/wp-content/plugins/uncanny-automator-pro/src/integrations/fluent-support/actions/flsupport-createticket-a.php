<?php

namespace Uncanny_Automator_Pro;

/**
 * Class FLSUPPORT_CREATETICKET_A
 *
 * @package Uncanny_Automator_Pro
 */
class FLSUPPORT_CREATETICKET_A {

	use Recipe\Action_Tokens;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'FLSUPPORT';

	/**
	 * Action code var.
	 *
	 * @var string
	 */
	private $action_code;
	/**
	 * Action meta var.
	 *
	 * @var string
	 */
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'FLSUPPORT_CREATETICKET_A';
		$this->action_meta = 'CREATETICKET';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/fluent-support/' ),
			'is_pro'             => true,
			'requires_user'      => false,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - Fluent Support */
			'sentence'           => sprintf( __( 'Create {{a ticket:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - Fluent Support */
			'select_option_name' => __( 'Create {{a ticket}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'create_ticket' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'FS_TICKET_URL' => array(
					'name' => __( 'Ticket URL', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
				'FS_TICKET_ID'  => array(
					'name' => __( 'Ticket ID', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load options.
	 *
	 * @return array
	 */
	public function load_options() {

		$options_array = array(
			'options_group' => array(
				$this->action_meta => $this->load_options_input( false ),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * This method is used to list all input options for the action/triggers.
	 *
	 * @param $payment_method
	 *
	 * @return array
	 */
	public function load_options_input() {

		$products_args = array(
			'option_code'              => 'FLS_TICKETPRODUCTID',
			'label'                    => esc_attr__( 'Product/Service', 'uncanny-automator-pro' ),
			'input_type'               => 'select',
			'supports_tokens'          => true,
			'required'                 => false,
			'default_value'            => null,
			'supports_custom_value'    => true,
			'custom_value_description' => esc_attr__( 'Product/Service ID', 'uncanny-automator-pro' ),
			'options'                  => Automator()->helpers->recipe->fluent_support->pro->get_all_product_options( array( esc_attr__( 'Not Applicable', 'uncanny-automator-pro' ) ) ),
		);

		$priorities_args = array(
			'option_code'              => 'FLS_TICKETPRIORITY',
			'label'                    => esc_attr__( 'Priority', 'uncanny-automator-pro' ),
			'input_type'               => 'select',
			'requires_user'            => false,
			'supports_tokens'          => true,
			'required'                 => false,
			'default_value'            => null,
			'supports_custom_value'    => true,
			'custom_value_description' => esc_attr__( 'Priority slug', 'uncanny-automator-pro' ),
			'options'                  => Automator()->helpers->recipe->fluent_support->pro->get_all_ticket_priorities_options( array( esc_attr__( 'Not Applicable', 'uncanny-automator-pro' ) ) ),
		);

		$options_array = array(
			Automator()->helpers->recipe->field->text_field( 'FLS_CUSTOMER_EMAIL', esc_html__( 'Customer email', 'uncanny-automator-pro' ), true, 'email', '', true, esc_html__( 'If the customer does not exist, a new customer will be created.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->text_field( 'FLS_FIRST_NAME', esc_html__( 'First name', 'uncanny-automator-pro' ), true, 'text', '', false, esc_html__( 'If an existing customer already has a first name set or a matching WordPress user is found, this field will not be used.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->text_field( 'FLS_LAST_NAME', esc_html__( 'Last name', 'uncanny-automator-pro' ), true, 'text', '', false, esc_html__( 'If an existing customer already has a last name set or a matching WordPress user is found, this field will not be used.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->text_field( 'FLS_SUBJECT', esc_html__( 'Subject', 'uncanny-automator-pro' ), true, 'text', '', true ),
			Automator()->helpers->recipe->field->text_field( 'FLS_TICKET_DETAILS', esc_html__( 'Ticket Details', 'uncanny-automator-pro' ), true, 'textarea', '', true ),
			Automator()->helpers->recipe->field->select( $products_args ),
			Automator()->helpers->recipe->field->select( $priorities_args ),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 *
	 * @return false|void
	 */
	public function create_ticket( $user_id, $action_data, $recipe_id, $args ) {

		// First make sure all required class exist
		if ( ! class_exists( '\FluentSupport\App\Models\Ticket' ) ) {
			$error_message                       = __( '`\FluentSupport\App\Models\Ticket` class is missing.', 'uncanny-automator-pro' );
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		// Base ticket data.
		$ticket_data = array(
			'customer_id'     => 0,
			'title'           => sanitize_text_field( Automator()->parse->text( $action_data['meta']['FLS_SUBJECT'], $recipe_id, $user_id, $args ) ),
			'content'         => wp_unslash( wp_kses_post( Automator()->parse->text( $action_data['meta']['FLS_TICKET_DETAILS'], $recipe_id, $user_id, $args ) ) ),
			'product_id'      => (int) Automator()->parse->text( $action_data['meta']['FLS_TICKETPRODUCTID'], $recipe_id, $user_id, $args ),
			'client_priority' => sanitize_text_field( Automator()->parse->text( $action_data['meta']['FLS_TICKETPRIORITY'], $recipe_id, $user_id, $args ) ),
		);

		$customer_email = Automator()->parse->text( $action_data['meta']['FLS_CUSTOMER_EMAIL'], $recipe_id, $user_id, $args );

		$error_messages = array();
		if ( empty( $ticket_data['title'] ) ) {
			$error_messages[] = esc_html__( '`Subject` is required.', 'uncanny-automator-pro' );
		}

		if ( empty( $ticket_data['content'] ) ) {
			$error_messages[] = esc_html__( '`Ticket details` is required.', 'uncanny-automator-pro' );
		}

		if ( empty( $customer_email ) ) {
			$error_messages[] = esc_html__( '`Customer email` is required.', 'uncanny-automator-pro' );
		} elseif ( ! is_email( $customer_email ) ) {
			$error_messages[] = esc_html__( '`Customer email` is invalid.', 'uncanny-automator-pro' );
		}

		if ( ! empty( $error_messages ) ) {
			$error_message                       = implode( '<br/>', $error_messages );
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		try {

			// First check if Fluent Support customer exists by email. If it does, get the customer_id.
			$customer = \FluentSupport\App\Models\Customer::where( 'email', $customer_email )->first();
			if ( $customer && $customer instanceof \FluentSupport\App\Models\Customer ) {
				$ticket_data['customer_id'] = $customer->id;
			} else {

				$maybe_new_customer = array(
					'first_name'      => sanitize_text_field( Automator()->parse->text( $action_data['meta']['FLS_FIRST_NAME'], $recipe_id, $user_id, $args ) ),
					'last_name'       => sanitize_text_field( Automator()->parse->text( $action_data['meta']['FLS_LAST_NAME'], $recipe_id, $user_id, $args ) ),
					'email'           => $customer_email,
					'last_ip_address' => '',
				);

				// Maybe create a new customer and get the customer id.
				$customer = \FluentSupport\App\Models\Customer::maybeCreateCustomer( $maybe_new_customer );
				if ( $customer && $customer instanceof \FluentSupport\App\Models\Customer ) {
					$ticket_data['customer_id'] = $customer->id;
				}
			}

			if ( empty( $ticket_data['customer_id'] ) ) {
				$error_message                       = esc_html__( 'Unable to create or detect customer.', 'uncanny-automator-pro' );
				$action_data['do-nothing']           = true;
				$action_data['complete_with_errors'] = true;
				Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

				return;
			}

			if ( ! empty( $ticket_data['product_id'] ) ) {
				$ticket_data['product_source'] = 'local';
			}

			$customer = \FluentSupport\App\Models\Customer::findOrFail( $ticket_data['customer_id'] );

			// Fix: Without mailbox id subject/title is not getting displayed in the tickets admin.
			$mailbox                   = \FluentSupport\App\Services\Helper::getDefaultMailBox();
			$ticket_data['mailbox_id'] = $mailbox->id;

			/*
			 * Filter ticket data
			 *
			 * @param array  $ticketData
			 * @param object $customer
			 */
			$ticket_data = apply_filters( 'fluent_support/create_ticket_data', $ticket_data, $customer ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			/*
			 * Action before ticket create
			 *
			 * @param array  $ticketData
			 * @param object $customer
			 */
			do_action( 'fluent_support/before_ticket_create', $ticket_data, $customer ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$created_ticket = \FluentSupport\App\Models\Ticket::create( $ticket_data );

			/*
			 * Action on ticket create
			 *
			 * @param object $createdTicket
			 * @param object $customer
			 */
			do_action( 'fluent_support/ticket_created', $created_ticket, $customer ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$this->hydrate_tokens(
				array(
					'FS_TICKET_URL' => admin_url( "admin.php?page=fluent-support#/tickets/{$created_ticket->id}/view" ),
					'FS_TICKET_ID'  => $created_ticket->id,
				)
			);

			Automator()->complete_action( $user_id, $action_data, $recipe_id );
		} catch ( Exception $e ) {
			$error_message                       = $e->getMessage();
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
		}

	}

}
