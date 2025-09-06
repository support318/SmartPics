<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WPSP_SUBSCRIPTION_RENEWED
 *
 * @package Uncanny_Automator
 */
class WPSP_SUBSCRIPTION_RENEWED {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPSIMPLEPAY';
	private $trigger_code      = 'WPSPSUBSCRIPTIONRENEWED';
	private $trigger_meta      = 'WPSPFORMSUBSCRIPTION';

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-simple-pay/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'type'                => 'anonymous',
			'is_pro'              => true,
			/* translators: Logged-in trigger - WP Simple Pay */
			'sentence'            => sprintf( esc_attr__( 'A subscription for {{a form:%1$s}} is renewed', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WP Simple Pay */
			'select_option_name'  => esc_attr__( 'A subscription for {{a form}} is renewed', 'uncanny-automator-pro' ),
			'action'              => 'simpay_webhook_invoice_payment_succeeded',
			'priority'            => 20,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'invoice_payment_succeeded' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp_simple_pay->options->list_wp_simpay_forms(
						null,
						$this->trigger_meta,
						array(
							'is_any'          => true,
							'is_subscription' => true,
						)
					),
				),
			)
		);
	}

	/**
	 * @param \SimplePay\Vendor\Stripe\Event $type Stripe webhook event.
	 * @param \SimplePay\Vendor\Stripe\Invoice $object Stripe PaymentIntent.
	 */
	public function invoice_payment_succeeded( $type, $object ) {
		if ( ! isset( $object->lines ) || ! isset( $object->lines->data ) ) {
			return;
		}
		/** @var \SimplePay\Vendor\Stripe\InvoiceLineItem $data */
		$data    = array_shift( $object->lines->data );
		$form_id = $data->metadata->simpay_form_id;

		if ( empty( $form_id ) ) {
			return;
		}
		///** @var \SimplePay\Vendor\Stripe\Invoice $invoice */
		$user_id       = 0;
		$billing_email = $object->customer_email;
		if ( is_email( $billing_email ) ) {
			$user_id = false === email_exists( $billing_email ) ? 0 : email_exists( $billing_email );
		}
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_form      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$form_name          = get_the_title( $form_id );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if (
					absint( $required_form[ $recipe_id ][ $trigger_id ] ) === absint( $form_id ) ||
					intval( '-1' ) === intval( $required_form[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		$customer_data                           = array();
		$customer_data['name']                   = $object->customer_name;
		$customer_data['email']                  = $object->customer_email;
		$customer_data['phone']                  = $object->customer_phone;
		$customer_data['address']['line1']       = $object->customer_address->line1;
		$customer_data['address']['line2']       = $object->customer_address->line2;
		$customer_data['address']['city']        = $object->customer_address->city;
		$customer_data['address']['postal_code'] = $object->customer_address->postal_code;
		$customer_data['address']['state']       = $object->customer_address->state;
		$customer_data['address']['country']     = $object->customer_address->country;

		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'ignore_post_id'   => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );
			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {
						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						);

						Automator()->db->token->save( $this->trigger_meta . '_ID', $form_id, $trigger_meta );
						Automator()->db->token->save( 'WPSPFORMSUBSCRIPTION', $form_name, $trigger_meta );
						Automator()->db->token->save( 'lines', maybe_serialize( json_decode( wp_json_encode( $object->lines ), true ) ), $trigger_meta );
						Automator()->db->token->save( 'customer_data', maybe_serialize( $customer_data ), $trigger_meta );
						Automator()->db->token->save( 'meta_data', maybe_serialize( json_decode( wp_json_encode( $data->metadata ), true ) ), $trigger_meta );
						Automator()->db->token->save( 'AMOUNT_DUE', $object->amount_due, $trigger_meta );
						Automator()->db->token->save( 'AMOUNT_PAID', $object->amount_paid, $trigger_meta );
						Automator()->db->token->save( 'AMOUNT_REMAINING', $object->amount_remaining, $trigger_meta );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
