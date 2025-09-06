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
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Variation_Product_Tokens;

// Uncanny Automator.
use Uncanny_Automator\Recipe\Trigger;

// Storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

/**
 * Class User_Purchased_Variable_Subscription
 *
 * Handles variable subscription purchase triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains original trigger code (WCSUBSCRIPTIONVARIATION) and meta (WOOSUBSCRIPTIONS)
 * - Preserves original token IDs and formats for existing automations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Purchased_Variable_Subscription extends Trigger {

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
	const TRIGGER_CODE = 'WCSUBSCRIPTIONVARIATION';

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
	 * Variation product tokens
	 *
	 * @var Variation_Product_Tokens
	 */
	protected $variation_product_tokens;

	/**
	 * Setup the trigger
	 *
	 * @return void
	 */
	public function setup_trigger() {

		$this->storage = new Subscription_Storage();

		// Common tokens as variation.
		$this->common_tokens = new Common_Tokens( self::TRIGGER_META, true );

		// Order tokens.
		$this->order_tokens = new Order_Tokens();

		// Variation product tokens.
		$this->variation_product_tokens = new Variation_Product_Tokens( self::TRIGGER_META );

		// Dropdown label.
		$this->set_readable_sentence(
			/* translators: %1$s: Variable subscription */
			esc_html_x( 'A user purchases {{a variable subscription}}', 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Variable subscription */
				esc_html_x( 'A user purchases {{a variable subscription:%1$s}}', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		$this->add_action( 'woocommerce_subscription_payment_complete', 30, 1 );

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

		$common_tokens            = $this->common_tokens->get_tokens();
		$order_tokens             = $this->order_tokens->get_tokens();
		$variation_product_tokens = $this->variation_product_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens, $variation_product_tokens );
	}

	/**
	 * Hydrate tokens
	 *
	 * Populates both subscription and order tokens.
	 * - Subscription tokens maintain legacy format for backwards compatibility
	 * - Order tokens provide additional data from the subscription's associated order
	 * - Uses subscription object for both token types since WC_Subscription extends WC_Order
	 *
	 * @param array $trigger The trigger.
	 * @param array $hook_args The hook args.
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$subscription = $hook_args[0] ?? null;

		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return array();
		}

		// Common tokens.
		$common_tokens = $this->common_tokens->get_tokens();

		// Variation product tokens.
		$variation_product_tokens = $this->variation_product_tokens->get_tokens();

		// Parse common tokens
		$tokens = array();
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Parse variation product tokens
		foreach ( $variation_product_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->variation_product_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Save legacy tokens for backwards compatibility. 😳
		$legacy_token_storage = new Legacy_Token_Storage( $this->order_tokens, $this->trigger_records );
		$legacy_token_storage->save_legacy_tokens( $subscription );

		return $tokens;
	}

	/**
	 * Options
	 *
	 * @return array
	 */
	public function options() {
		$field_variable_subscriptions = array(
			'option_code'     => self::TRIGGER_META,
			'label'           => esc_html_x( 'Variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
			'ajax'            => array(
				'endpoint' => 'uncanny_automator_pro_fetch_variations',
				'event'    => 'on_load',
			),
		);

		return array(
			$field_variable_subscriptions,
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

		if ( ! $subscription instanceof \WC_Subscription ) {
			return false;
		}

		$last_order_id = $subscription->get_last_order();

		// Only trigger for new subscriptions, not renewals
		if ( ! empty( $last_order_id ) && $last_order_id !== $subscription->get_parent_id() ) {
			return false;
		}

		// Set the user ID from the subscription
		$user_id = $subscription->get_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$selected_variable_subscription_id = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );

		// If the selected subscription is not set, return false.
		if ( 0 === $selected_variable_subscription_id ) {
			return false;
		}

		// Get subscription items
		$items = $subscription->get_items();
		if ( empty( $items ) ) {
			return false;
		}

		$product_ids = array();
		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				if ( $product->is_type( array( 'subscription_variation', 'variable-subscription' ) ) ) {
					$product_ids[] = $product->get_parent_id();
				}
			}
		}

		// If the selected subscription is any, return true.
		if ( -1 === $selected_variable_subscription_id ) {
			return true;
		}

		// Check if the selected variable subscription matches any of the subscription items
		return in_array( $selected_variable_subscription_id, $product_ids, true );
	}
}
