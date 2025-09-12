<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers;

// WooCommerce tokens.
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Tags;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Categories;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Trigger\Loopable\Order_Items;

// WooCommerce Subscription common tokens.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Common_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Order_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Variation_Product_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Product_Extra_Tokens;

// Legacy token storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

// Automator.
use Uncanny_Automator\Recipe\Trigger;

use WC_Subscription;
use WC_Order;

/**
 * Class User_Renewed_Variation_Subscription
 *
 * Handles subscription variation renewal triggers.
 * This class maintains compatibility with existing automations while providing enhanced structure.
 *
 * Backwards Compatibility Notes:
 * - Preserves original trigger code (WCVARIATIONSUBSCRIPTIONRENEWED)
 * - Preserves original trigger meta (WOOSUBSCRIPTIONS)
 * - Maintains original option structure for recipe compatibility
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Renewed_Variation_Subscription extends Trigger {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	const INTEGRATION_CODE = 'WOOCOMMERCE_SUBSCRIPTION';

	/**
	 * Preserves the trigger code for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'WCVARIATIONSUBSCRIPTIONRENEWED';

	/**
	 * Preserves the trigger meta for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WOOSUBSCRIPTIONS';

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
	 * Variation product tokens
	 *
	 * @var Variation_Product_Tokens
	 */
	protected $variation_product_tokens;

	/**
	 * Product extra tokens
	 *
	 * @var Product_Extra_Tokens
	 */
	protected $product_extra_tokens;

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );

		$this->common_tokens            = new Common_Tokens( self::TRIGGER_META, true );
		$this->order_tokens             = new Order_Tokens();
		$this->variation_product_tokens = new Variation_Product_Tokens( self::TRIGGER_META );
		$this->product_extra_tokens     = new Product_Extra_Tokens();

		// Add the variation product tokens to the common tokens.
		$this->common_tokens->add_token(
			array(
				'tokenId'   => 'WOOVARIPRODUCT',
				'tokenName' => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			)
		);

		// Dropdown label
		$this->set_readable_sentence(
			esc_html_x( 'A user renews a subscription to {{a product variation}} of {{a variable subscription}}', 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Product variation, %2$s: Variable subscription */
				esc_html_x( 'A user renews a subscription to {{a product variation:%1$s}} of {{a variable subscription:%2$s}}', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				'WOOVARIPRODUCT:' . self::TRIGGER_META
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
				'label'           => esc_html_x( 'Variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(
					array(
						'value' => -1,
						'text'  => esc_html_x( 'Any variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					),
				),
				'ajax'            => array(
					'endpoint' => 'uncanny_automator_pro_fetch_variations',
					'event'    => 'on_load',
				),
				'relevant_tokens' => array(),
			),
			array(
				'option_code'     => 'WOOVARIPRODUCT',
				'label'           => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(
					array(
						'value' => -1,
						'text'  => esc_html_x( 'Any variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					),
				),
				'ajax'            => array(
					'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
					'event'         => 'parent_fields_change',
					'listen_fields' => array( self::TRIGGER_META ),
				),
				'relevant_tokens' => array(),
			),
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

		$common_tokens            = $this->common_tokens->get_tokens();
		$order_tokens             = $this->order_tokens->get_tokens();
		$variation_product_tokens = $this->variation_product_tokens->get_tokens();
		$product_extra_tokens     = $this->product_extra_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens, $variation_product_tokens, $product_extra_tokens );
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

		/** @var WC_Subscription $subscription */
		$subscription = $hook_args[0];

		/** @var WC_Order $renewal_order */
		$renewal_order = $hook_args[1];

		if ( ! is_a( $subscription, 'WC_Subscription' ) || ! is_a( $renewal_order, 'WC_Order' ) ) {
			return false;
		}

		$selected_variable_product = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );
		$selected_variation        = intval( $trigger['meta']['WOOVARIPRODUCT'] ?? 0 );

		return $this->subscription_item_matches( $subscription, $selected_variable_product, $selected_variation );
	}

	/**
	 * Returns true if $subscription has a matching line item,
	 * treating “–1” as a wildcard.
	 *
	 * - If $parent_id   === -1 → match anything, return true.
	 * - Else loop items once:
	 *     • skip any item whose product_id ≠ $parent_id
	 *     • if $variation_id === -1 → return true on first parent match
	 *     • else if item’s variation_id === $variation_id → return true
	 * - return false at end
	 *
	 * @param WC_Subscription $subscription
	 * @param int             $parent_id     Variable‐product ID, or -1 for “any”
	 * @param int             $variation_id  Variation ID, or -1 for “any”
	 * @return bool
	 */
	public function subscription_item_matches( WC_Subscription $subscription, int $parent_id, int $variation_id ): bool {

		// If the selected variable product is any, return true.
		if ( -1 === $parent_id ) {
			return true;
		}

		$any_variation = ( -1 === $variation_id );
		foreach ( $subscription->get_items() as $item ) {
			// Effecient and fast check to see if the item is the correct parent.
			if ( $item->get_product_id() !== $parent_id ) {
				continue;
			}
			// If the variation is any or the exact variation, return true.
			if ( $any_variation || $item->get_variation_id() === $variation_id ) {
				return true;
			}
		}

		return false;
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

		// Parse the variation product tokens.
		foreach ( $this->variation_product_tokens->get_tokens() as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->variation_product_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Save the legacy tokens for backwards compatibility
		$legacy_token_storage = new Legacy_Token_Storage( $this->order_tokens, $this->trigger_records );
		$legacy_token_storage->save_legacy_tokens( $subscription );

		// Save the extra tokens for backwards compatibility
		$legacy_token_storage->set_variation_product_tokens( $this->product_extra_tokens );
		$legacy_token_storage->save_product_extra_tokens( $subscription );

		// Add specific tokens for this trigger
		$tokens['subscription_id'] = $subscription->get_id();
		$tokens['order_id']        = $last_order->get_id();

		return $tokens;
	}
}
