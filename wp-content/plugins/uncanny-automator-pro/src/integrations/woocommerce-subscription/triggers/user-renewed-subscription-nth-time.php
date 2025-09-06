<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers;

use Uncanny_Automator\Recipe\Trigger;

// WooCommerce tokens.
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Tags;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Categories;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Trigger\Loopable\Order_Items;

// WooCommerce Subscription common tokens.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Subscription_Storage;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Common_Tokens;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Order_Tokens;

// Legacy token storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

/**
 * Class User_Renewed_Subscription_Nth_Time
 *
 * Handles subscription renewal triggers.
 * This class maintains compatibility with existing automations while providing enhanced structure.
 *
 * Backwards Compatibility Notes:
 * - Preserves original trigger code (WC_SUBSCRIPTION_RENEWAL_COUNT)
 * - Preserves original trigger meta (WOOSUBSCRIPTIONS)
 * - Maintains original option structure for recipe compatibility
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Renewed_Subscription_Nth_Time extends Trigger {

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
	const TRIGGER_CODE = 'WC_SUBSCRIPTION_RENEWAL_COUNT';

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
		$this->order_tokens  = new Order_Tokens();

		// Dropdown label
		$this->set_readable_sentence(
			esc_html_x( 'A user renews a subscription to {{a product}} for the {{nth}} time', 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product, %2$s: Number of times */
				esc_html_x( 'A user renews a subscription to {{a product:%1$s}} for the {{nth:%2$s}} time', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				'RENEWAL_COUNT:' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		$this->add_action( 'woocommerce_subscription_renewal_payment_complete', 30, 2 );

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
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'     => 'RENEWAL_COUNT',
					'input_type'      => 'int',
					'label'           => esc_html_x( 'Count', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'min_number'      => 1,
					'default'         => 1,
					'description'     => esc_html_x( 'Enter the number of times the user has to renew the subscription for the trigger to fire.', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'placeholder'     => esc_html_x( 'Example: 1', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'relevant_tokens' => array(),
				)
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
		$last_order   = $hook_args[1] ?? null;

		// Basic validation
		if ( ! class_exists( '\WC_Subscription' ) || ! class_exists( '\WC_Order' ) ) {
			return false;
		}

		if ( ! $subscription instanceof \WC_Subscription || ! $last_order instanceof \WC_Order ) {
			return false;
		}

		// Get the renewal count
		$renewal_count = $subscription->get_payment_count( 'completed', 'renewal' );

		// Get subscription items
		$items = $subscription->get_items();

		// Check each item
		$matched = false;
		foreach ( $items as $item ) {
			$product = $item->get_product();

			// Skip if not a subscription product
			if ( ! $product || ( 'subscription' !== $product->get_type() && 'subscription_variation' !== $product->get_type() ) ) {
				continue;
			}

			$product_id = $item->get_product_id();

			// Get selected options
			$selected_product       = $trigger['meta'][ self::TRIGGER_META ] ?? null;
			$required_renewal_count = absint( $trigger['meta']['RENEWAL_COUNT'] ?? 1 );

			// Check if product matches
			$product_matches = -1 === intval( $selected_product ) || intval( $selected_product ) === intval( $product_id );

			// Check if renewal count matches
			$count_matches = intval( $renewal_count ) >= intval( $required_renewal_count );

			if ( $product_matches && $count_matches ) {
				$matched = true;
				break;
			}
		}

		return $matched;
	}

	/**
	 * Hydrate tokens
	 *
	 * Populates both subscription and order tokens.
	 * - Subscription tokens maintain legacy format for backwards compatibility
	 * - Order tokens provide additional data from the subscription's associated order
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$subscription = $hook_args[0] ?? null;
		$last_order   = $hook_args[1] ?? null;

		// Basic validation
		if ( ! class_exists( '\WC_Subscription' ) || ! class_exists( '\WC_Order' ) ) {
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

		// Add specific tokens for this trigger.
		$tokens['subscription_id'] = $subscription->get_id();
		$tokens['order_id']        = $last_order->get_id();
		$tokens['renewal_count']   = $subscription->get_payment_count( 'completed', 'renewal' );

		// Save order tokens for backwards compatibility.
		$legacy_token_storage = new Legacy_Token_Storage( $this->order_tokens, $this->trigger_records );
		$legacy_token_storage->save_legacy_tokens( $subscription, $last_order );

		return $tokens;
	}
}
