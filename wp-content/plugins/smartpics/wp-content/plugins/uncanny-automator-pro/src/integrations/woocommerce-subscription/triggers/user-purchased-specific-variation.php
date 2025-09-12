<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers;

// WooCommerce tokens.
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Tags;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Categories;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Trigger\Loopable\Order_Items;

// WooCommerce Subscription common tokens.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Subscription_Storage;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Common_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Order_Tokens;

// Automator.
use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

/**
 * Class User_Purchased_Specific_Variation
 *
 * Handles subscription purchase triggers for specific variations.
 * This class maintains compatibility with existing automations while providing enhanced structure.
 *
 * Backwards Compatibility Notes:
 * - Preserves original trigger code (WCSPECIFICSUBVARIATION)
 * - Preserves original trigger meta (WOOSUBSCRIPTIONS)
 * - Maintains original option structure for recipe compatibility
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Purchased_Specific_Variation extends Trigger {

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
	const TRIGGER_CODE = 'WCSPECIFICSUBVARIATION';

	/**
	 * Preserves the trigger meta for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WOOSUBSCRIPTIONS';

	/**
	 * Storage
	 *
	 * @var Subscription_Storage
	 */
	protected $storage;

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
	 * Setup the trigger
	 *
	 * @return void
	 */
	public function setup_trigger() {

		$this->storage       = new Subscription_Storage();
		$this->common_tokens = new Common_Tokens( self::TRIGGER_META );
		$this->order_tokens  = new Order_Tokens( self::TRIGGER_CODE );

		// Dropdown label
		$this->set_readable_sentence(
			esc_html_x( 'A user purchases {{a variable subscription}} with {{a variation}} selected', 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product, %2$s: Variation */
				esc_html_x( 'A user purchases {{a variable subscription:%1$s}} with {{a variation:%2$s}} selected', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				'WOOVARIPRODUCT:' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		// Set loopable tokens for the trigger.
		$this->set_loopable_tokens(
			array(
				'ORDER_ITEMS'        => Order_Items::class,
				'PRODUCT_TAGS'       => Product_Tags::class,
				'PRODUCT_CATEGORIES' => Product_Categories::class,
			)
		);

		$this->add_action( 'woocommerce_subscription_payment_complete', 30, 1 );
	}

	/**
	 * Define tokens
	 *
	 * Merges both subscription and order tokens while maintaining backwards compatibility.
	 * - Subscription tokens use WOOSUBSCRIPTIONS_ prefix (legacy format)
	 * - Order tokens use unprefixed format (new addition)
	 *
	 * @param array $trigger The trigger.
	 * @param array $tokens The tokens.
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$common_tokens = $this->common_tokens->get_tokens();
		$order_tokens  = $this->order_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens );
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
				'options'         => array(),
				'ajax'            => array(
					'endpoint' => 'uncanny_automator_pro_fetch_variations',
					'event'    => 'on_load',
				),
				'relevant_tokens' => array(),
			),
			array(
				'option_code'     => 'WOOVARIPRODUCT',
				'label'           => esc_html_x( 'Option', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(),
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
	 * Validate the trigger.
	 *
	 * @param array $trigger The trigger.
	 * @param array $hook_args The hook args.
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		$subscription = $hook_args[0] ?? null;

		// Basic validation
		if ( ! $subscription instanceof \WC_Subscription ) {
			return false;
		}

		// Only process new subscriptions
		$last_order_id = $subscription->get_last_order();
		if ( ! empty( $last_order_id ) && $last_order_id !== $subscription->get_parent_id() ) {
			return false;
		}

		// Get selected options
		$selected_subscription = $trigger['meta'][ self::TRIGGER_META ] ?? null;
		$selected_variation    = $trigger['meta']['WOOVARIPRODUCT'] ?? null;

		// Get subscription items
		$items              = $subscription->get_items();
		$product_ids        = array();
		$product_parent_ids = array();

		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( ! $this->is_valid_subscription_product( $product ) ) {
				continue;
			}
			$product_ids[]        = $product->get_id();
			$product_parent_ids[] = $product->get_parent_id();
		}

		// Validate subscription and variation match
		$subscription_matches = intval( $selected_subscription ) === -1
			|| in_array( absint( $selected_subscription ), array_map( 'absint', $product_parent_ids ), true );

		$variation_matches = intval( $selected_variation ) === -1
			|| in_array( absint( $selected_variation ), array_map( 'absint', $product_ids ), true );

		return $subscription_matches && $variation_matches;
	}

	/**
	 * Hydrate tokens
	 *
	 * Populates both subscription and order tokens.
	 * - Subscription tokens maintain legacy format for backwards compatibility
	 * - Order tokens provide additional data from the subscription's associated order
	 * - Uses subscription object for both token types since WC_Subscription extends WC_Order
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$subscription = $hook_args[0] ?? null;

		if ( ! $subscription instanceof \WC_Subscription ) {
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

		// This trigger uses its trigger code for the order tokens.
		// Since its compatible with Trigger framework 3, we can just parse it right away.
		foreach ( $this->order_tokens->get_tokens() as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->order_tokens->parse_token( $token_id, $subscription );
			}
		}

		return $tokens;
	}

	/**
	 * Check if product is a valid subscription variation
	 *
	 * @param mixed $product
	 * @return bool
	 */
	private function is_valid_subscription_product( $product ) {

		return class_exists( '\WC_Subscriptions_Product' )
			&& \WC_Subscriptions_Product::is_subscription( $product )
			&& $product->is_type( array( 'subscription_variation', 'variable-subscription' ) );
	}
}
