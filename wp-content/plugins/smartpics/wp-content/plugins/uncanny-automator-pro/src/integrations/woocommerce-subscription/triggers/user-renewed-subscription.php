<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers;

// WooCommerce tokens.
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Tags;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Categories;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Trigger\Loopable\Order_Items;

// WooCommerce Subscription common tokens.
use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Common_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Order_Tokens;

/**
 * Class User_Renewed_Subscription
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Renewed_Subscription extends Trigger {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	const INTEGRATION_CODE = 'WOOCOMMERCE_SUBSCRIPTION';

	/**
	 * Trigger code
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'WC_SUBSCRIPTIONRENEWED';

	/**
	 * Trigger meta
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WCSRENEWED';

	/**
	 * Common tokens
	 *
	 * @var Common_Tokens
	 */
	protected $common_tokens;

	/**
	 * Order tokens
	 *
	 * @var Order_Tokens
	 */
	protected $order_tokens;

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );

		// The trigger is active only if WooCommerce Subscriptions is active
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}

		$this->common_tokens = new Common_Tokens( self::TRIGGER_META );
		$this->order_tokens  = new Order_Tokens();

		// Dropdown label
		$this->set_readable_sentence(
			esc_html_x( 'A user renews a subscription to {{a product}}', 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product */
				esc_html_x( 'A user renews a subscription to {{a product:%1$s}} {{a number of:%2$s}} times', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				'NUMTIMES:' . self::TRIGGER_META
			)
		);

		$this->set_action_hook( 'woocommerce_subscription_renewal_payment_complete' );
		$this->set_action_args_count( 2 );

		// Set loopable tokens for the trigger.
		$this->set_loopable_tokens(
			array(
				'ORDER_ITEMS'        => Order_Items::class,
				'PRODUCT_TAGS'       => Product_Tags::class,
				'PRODUCT_CATEGORIES' => Product_Categories::class,
			)
		);
	}

	/**
	 * Options
	 *
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'option_code'     => self::TRIGGER_META,
				'label'           => esc_html_x( 'Product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(
					array(
						'value' => -1,
						'text'  => esc_html_x( 'Any subscription product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					),
				),
				'description'     => esc_html_x( 'The payment must be completed for the trigger to fire.', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'ajax'            => array(
					'endpoint' => 'automator_select_all_wc_subscriptions',
					'event'    => 'on_load',
				),
				'relevant_tokens' => array(),
			),
			Automator()->helpers->recipe->options->number_of_times(),
		);
	}

	/**
	 * Define the tokens for this trigger
	 *
	 * @param array $trigger
	 * @param array $tokens
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$common_tokens = $this->common_tokens->get_tokens();
		$order_tokens  = $this->order_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens );
	}

	/**
	 * Validate the trigger before processing
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		if ( ! isset( $hook_args[0] ) || ! isset( $hook_args[1] ) ) {
			return false;
		}

		if ( ! class_exists( 'WC_Subscription' ) || ! class_exists( 'WC_Order' ) ) {
			return false;
		}

		$subscription  = $hook_args[0];
		$renewal_order = $hook_args[1];

		if ( ! $subscription instanceof \WC_Subscription ) {
			return false;
		}

		if ( ! $renewal_order instanceof \WC_Order ) {
			return false;
		}

		// Get the selected product from the recipe
		$selected_product = $trigger['meta'][ self::TRIGGER_META ] ?? null;

		// If no product is selected, return false
		if ( empty( $selected_product ) ) {
			return false;
		}

		// If "Any subscription product" is selected, return true
		if ( intval( $selected_product ) === -1 ) {
			return true;
		}

		// Get the subscription product ID
		$subscription_product_id = 0;
		$items                   = $subscription->get_items();
		if ( ! empty( $items ) ) {
			$item                    = reset( $items );
			$subscription_product_id = $item->get_product_id();
		}

		// Check if the selected product matches the subscription product
		return intval( $selected_product ) === $subscription_product_id;
	}

	/**
	 * Hydrate the tokens with actual values
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$subscription = $hook_args[0] ?? null;
		$last_order   = $hook_args[1] ?? null;

		// Basic validation
		if ( ! class_exists( 'WC_Subscription' ) || ! class_exists( 'WC_Order' ) ) {
			return array();
		}

		if ( ! $subscription instanceof \WC_Subscription || ! $last_order instanceof \WC_Order ) {
			return array();
		}

		$tokens = array();

		// Parse common tokens
		foreach ( $this->common_tokens->get_tokens() as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Parse order tokens
		foreach ( $this->order_tokens->get_tokens() as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->order_tokens->parse_token( $token_id, $last_order );
			}
		}

		// Add specific tokens for this trigger
		$tokens['subscription_id'] = $subscription->get_id();
		$tokens['order_id']        = $last_order->get_id();

		return $tokens;
	}
}
