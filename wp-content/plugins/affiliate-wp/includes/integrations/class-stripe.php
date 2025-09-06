<?php
/**
 * Integrations: WP Simple Pay
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

#[\AllowDynamicProperties]

/**
 * Implements an integration for WP Simple Pay (both lite and pro).
 *
 * @since 1.2
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_Stripe extends Affiliate_WP_Base {

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since   1.2
	 */
	public $context = 'stripe';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function init() {

		if ( ! function_exists( 'simpay_get_license' ) ) {
			return;
		}

		$license = simpay_get_license();

		if ( ! is_callable( array( $license, 'is_pro' ) ) ) {
			return;
		}

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		// Pro.
		if ( $license->is_pro() ) {
			// Track referral via Stripe Subscription or PaymentIntent metadata.
			add_filter(
				'simpay_get_subscription_args_from_payment_form_request',
				array( $this, 'maybe_track_referral_360' )
			);

			add_filter(
				'simpay_get_paymentintent_args_from_payment_form_request',
				array( $this, 'maybe_track_referral_360' )
			);

			// Track referral via Stripe Checkout Session metadata.
			add_filter( 'simpay_get_session_args_from_payment_form_request', array( $this, 'maybe_track_referral_360' ) );

			// Process referral upon Stripe webhook.
			add_action(
				'simpay_webhook_subscription_created',
				array( $this, 'process_referral_360' ), 10, 2
			);

			add_action(
				'simpay_webhook_payment_intent_succeeded',
				array( $this, 'process_referral_360' ), 10, 2
			);

			add_action( 'simpay_webhook_checkout_session_completed', array( $this, 'process_pro_referral_stripe_checkout' ), 10 );

			// Lite.
		} else {

			// Track referral via Stripe Checkout Session metadata.
			add_filter(
				'simpay_get_session_args_from_payment_form_request',
				array( $this, 'maybe_track_referral_lite' )
			);

			// Process referral upon payment receipt view.
			add_action(
				'simpay_payment_receipt_viewed',
				array( $this, 'process_referral_lite' )
			);
		}
	}

	/**
	 * Adds affiliate metadata to Stripe Payment or Subscription creation for Pro.
	 *
	 * @since 2.3.4
	 *
	 * @param array $object_args Subscription or PaymentIntent arguments.
	 *                           Both utilize Stripe metadata.
	 * @return array (Maybe) modified array of object metadata.
	 */
	public function maybe_track_referral_360( $object_args ) {
		if ( ! $this->was_referred() ) {
			return $object_args;
		}

		$object_args['metadata']['affwp_visit_id']     = affiliate_wp()->tracking->get_visit_id();
		$object_args['metadata']['affwp_affiliate_id'] = $this->affiliate_id;

		return $object_args;
	}

	/**
	 * Adds affiliate metadata to Stripe Checkout Session payment metadata for Lite.
	 *
	 * @since 2.3.4
	 *
	 * @param array $object_args Subscription or PaymentIntent arguments.
	 *                           Both utilize Stripe metadata.
	 * @return array (Maybe) modified array of object metadata.
	 */
	public function maybe_track_referral_lite( $object_args ) {
		if ( ! $this->was_referred() ) {
			return $object_args;
		}

		$object_args['payment_intent_data']['metadata']['affwp_visit_id'] =
			affiliate_wp()->tracking->get_visit_id();
		$object_args['payment_intent_data']['metadata']['affwp_affiliate_id'] =
			$this->affiliate_id;

		return $object_args;
	}

	/**
	 * Processes a referral upon Stripe webhook for Pro.
	 *
	 * @since 2.3.4
	 *
	 * @param \Stripe\Event                              $event Stripe Event.
	 * @param \Stripe\Subscription|\Stripe\PaymentIntent $object Stripe Subscription or PaymentIntent
	 */
	public function process_referral_360( $event, $object ) {
		$affiliate_id = isset( $object->metadata->affwp_affiliate_id ) ? $object->metadata->affwp_affiliate_id : 0;

		if ( 0 === $affiliate_id ) {
			$this->log( 'Stripe webhook not processed because affiliate ID was not set.' );
			return;
		}

		// Assign email.
		$this->email = $object->customer->email;

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$affiliate_id,
			array(
				'reference' => $object->id,
			)
		);
		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return;
		}

		$visit_id = isset( $object->metadata->affwp_visit_id )
			? intval( $object->metadata->affwp_visit_id )
			: false;

		switch ( $object->object ) {
			case 'subscription':
				$this->log( 'Processing referral for Stripe subscription.' );

				$invoice = $event->data->object;

				$stripe_amount = $invoice->amount_paid;
				$currency      = $invoice->currency;
				$mode          = $invoice->livemode;
				$description   = $object->plan->nickname;

				break;

			case 'payment_intent':
				$this->log( 'Processing referral for Stripe charge.' );

				$stripe_amount = $object->amount_received;
				$currency      = $object->currency;
				$mode          = $object->livemode;
				$description   = $object->description;

				break;

			case 'checkout.session':
				$this->log( 'Processing referral for Stripe Checkout Session.' );

				$stripe_amount = $object->amount_total;
				$currency      = $object->currency;
				$mode          = $object->livemode;
				$description   = $object->metadata->item_description;

				break;
		}

		// Fill any empty descriptions with the form's item description or title.
		if ( empty( $description ) ) {
			$form_id     = $object->metadata->simpay_form_id;
			$description = simpay_get_filtered( 'item_description', simpay_get_saved_meta( $form_id, '_item_description' ), $form_id );

			if ( empty( $description ) ) {
				$description = get_the_title( $form_id );
			}
		}

		// Adjust amount based on currency decimals.
		if ( $this->is_zero_decimal( $currency ) ) {
			$amount = $stripe_amount;
		} else {
			$amount = round( $stripe_amount / 100, 2 );
		}

		$amount = $this->calculate_referral_amount( $amount, $object->id, 0, $affiliate_id );

		if ( $this->is_affiliate_email( $this->email, $affiliate_id ) ) {
			$this->log( 'Referral not created because affiliate\'s own account was used.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// Hydrates the previously created referral.
		$this->hydrate_referral(
			$referral_id,
			array(
				'status'      => 'pending',
				'amount'      => $amount,
				'description' => $description,
				'visit_id'    => $visit_id,
				'custom'      => array(
					'affiliate_id' => $affiliate_id,
					'visit_id'     => $visit_id,
					'livemode'     => $mode,
				),
			)
		);

		$this->log( 'Pending referral created successfully during Stripe webhook processing.' );

		$completed = $this->complete_referral( $object->id );

		if ( true === $completed ) {
			$this->log( 'Referral completed successfully during Stripe webhook processing.' );
		} else{
			$this->log( 'Referral failed to be set to completed with complete_referral() during Stripe webhook processing.', $object );
		}
	}

	/**
	 * Process a referral upon Stripe Checkout Session completion for Pro.
	 *
	 * Handle WP Simple Pay's webhook action backwards compatibility.
	 *
	 * The action hook for Stripe Checkout has a different signature than a
	 * standard PaymentIntent or Subscription. This function handles the mapping
	 * of the Stripe Checkout Session to the appropriate Stripe object.
	 *
	 * @since 2.21.0
	 *
	 * @param \SimplePay\Vendor\Stripe\Event $event
	 */
	public function process_pro_referral_stripe_checkout( $event ) {
		return $this->process_referral_360( $event, $event->data->object );
	}

	/**
	 * Processes a referral upon payment receipt view for Lite.
	 *
	 * @since 2.16.0
	 *
	 * @param array<string, mixed> $payment_confirmation_data
	 */
	public function process_referral_lite( $payment_confirmation_data ) {
		$object = current( $payment_confirmation_data['paymentintents'] );

		$this->process_referral_360( null, $object );
	}


	/**
	 * Create a referral during stripe form submission if customer was referred
	 *
	 * Legacy < 3.6.0 support.
	 *
	 * @access  public
	 * @since   2.0
	 * @since   2.16.0 Deprecated.
	*/
	public function insert_referral( $object ) {
		// noop
	}

	/**
	 * Determine if this is a zero decimal currency
	 *
	 * @access public
	 * @since  2.0
	 * @since  2.16.0 Use WP Simple Pay's built in `simpay_is_zero_decimal()` function if available.
	 * @param  $currency String The currency code
	 * @return bool
	 */
	public function is_zero_decimal( $currency ) {
		if ( function_exists( 'simpay_is_zero_decimal' ) ) {
			return simpay_is_zero_decimal( $currency );
		}

		$is_zero = array(
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'JPY',
			'KMF',
			'KRW',
			'MGA',
			'PYG',
			'RWF',
			'VND',
			'VUV',
			'XAF',
			'XOF',
			'XPF',
		);

		return in_array( strtoupper( $currency ), $is_zero );
	}

	/**
	 * Sets up the reference link in the Referrals table
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function reference_link( $reference, $referral ) {

		if ( empty( $referral->context ) || 'stripe' != $referral->context ) {

			return $reference;

		}

		$test = '';

		if( ! empty( $referral->custom ) ) {
			$custom = maybe_unserialize( $referral->custom );
			$test   = empty( $custom['livemode'] ) ? 'test/' : '';
		}

		$endpoint = false !== strpos( $reference, 'sub_' ) ? 'subscriptions' : 'payments';

		$url = 'https://dashboard.stripe.com/' . $test . $endpoint  . '/' . $reference ;

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.5
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	function plugin_is_active() {
		return defined( 'SIMPLE_PAY_VERSION' );
	}
}

new Affiliate_WP_Stripe;
