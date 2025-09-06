<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage;

// Services.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Order_Resolver;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Order_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Product_Extra_Tokens;

/**
 * Legacy_Token_Storage
 *
 * Handles the storage of legacy tokens for backwards compatibility.
 *
 * WooCommerce Subscription uses different tokenIdentifier for order tokens (i.e. 'WOOSUBSCRIPTIONS')
 * The new trigger framework does not support multiple tokens having different tokenIdentifier.
 *
 * Therefore, we need to save the legacy tokens in a different way. Otherwise, we'll break existing automations.
 *
 * Using Automator()->db->token->save() we save the tokens with 'WOOSUBSCRIPTIONS' as the tokenIdentifier.
 * so when the parser reads it, it knows which tokens to read.
 *
 * @since 5.6
 */
class Legacy_Token_Storage {

	/**
	 * @var Order_Tokens
	 */
	private $order_tokens;

	/**
	 * @var array
	 */
	private $trigger_records;

	/**
	 * @var Product_Extra_Tokens
	 */
	private $product_extra_tokens;

	/**
	 * @var string
	 */
	private $order_tokens_key = 'WOOSUBSCRIPTIONS';

	/**
	 * Constructor
	 *
	 * @param Order_Tokens $order_tokens The order tokens.
	 * @param string       $trigger_records The trigger records.
	 *
	 * @return void
	 */
	public function __construct( Order_Tokens $order_tokens, $trigger_records ) {
		$this->order_tokens    = $order_tokens;
		$this->trigger_records = $trigger_records;
	}

	/**
	 * Save legacy tokens.
	 *
	 * The new trigger framework does not support multiple tokens having different tokenIdentifier.
	 * Therefore, we need to save the legacy tokens in a different way. Otherwise, we'll break existing automations.
	 *
	 * @param \WC_Subscription $subscription The subscription.
	 * @return void
	 */
	public function save_legacy_tokens( $subscription ) {

		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return;
		}

		// Order tokens.
		$order_tokens = $this->order_tokens->get_tokens();

		$order_resolver = new Order_Resolver( $subscription );
		$order          = $order_resolver->get_order();

		// If the order is not a WC_Order, return.
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Parse order tokens using the best available order data
		$tokens = array();
		foreach ( $order_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->order_tokens->parse_token( $token_id, $order );
			}
		}

		Automator()->db->token->save( $this->order_tokens_key, wp_json_encode( $tokens ), $this->trigger_records );
	}

	/**
	 * Set order tokens key.
	 *
	 * @param string $key The key.
	 */
	public function set_order_tokens_key( $key ) {
		$this->order_tokens_key = $key;
	}

	/**
	 * Set variation product tokens.
	 *
	 * @param Product_Extra_Tokens $product_extra_tokens The destination.
	 */
	public function set_variation_product_tokens( Product_Extra_Tokens $product_extra_tokens ) {

		$this->product_extra_tokens = $product_extra_tokens;
	}

	/**
	 * Save variation product tokens.
	 *
	 * @param \WC_Subscription $subscription The subscription.
	 * @return void
	 */
	public function save_product_extra_tokens( $subscription ) {

		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return;
		}

		$product_extra_tokens = $this->product_extra_tokens->get_tokens();

		$order_resolver = new Order_Resolver( $subscription );
		$order          = $order_resolver->get_order();

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$tokens = array();
		foreach ( $product_extra_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->product_extra_tokens->parse_token( $token_id, $order );
			}
		}

		Automator()->db->token->save( 'WOOVARIPRODUCT', wp_json_encode( $tokens ), $this->trigger_records );
	}
}
