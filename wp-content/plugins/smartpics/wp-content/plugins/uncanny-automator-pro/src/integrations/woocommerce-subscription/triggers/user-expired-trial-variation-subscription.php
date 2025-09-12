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
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Product_Extra_Tokens;

// Uncanny Automator.
use Uncanny_Automator\Recipe\Trigger;

// Legacy token storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

// Subscription provider.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Provider;

/**
 * Class User_Expired_Trial_Variation_Subscription
 *
 * Handles variation subscription trial expiration triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains original trigger code (WCVARIATIONSUBSCRIPTIONTRIALEXPIRES) and meta (WOOSUBSCRIPTIONS)
 * - Preserves original token IDs and formats for existing automations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Expired_Trial_Variation_Subscription extends Trigger {

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
	const TRIGGER_CODE = 'WCVARIATIONSUBSCRIPTIONTRIALEXPIRES';

	/**
	 * Preserves the trigger meta for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WOOSUBSCRIPTIONS';

	/**
	 * Variation product meta
	 *
	 * @var string
	 */
	const VARIATION_META = 'WOOVARIPRODUCT';

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
	 * Product extra tokens
	 *
	 * @var Product_Extra_Tokens
	 */
	protected $product_extra_tokens;

	/**
	 * Setup the trigger
	 *
	 * @return void
	 */
	public function setup_trigger() {

		$this->storage       = new Subscription_Storage();
		$this->common_tokens = new Common_Tokens( self::TRIGGER_META, true );
		$this->order_tokens  = new Order_Tokens();

		// Support for legacy tokens.
		$this->variation_product_tokens = new Variation_Product_Tokens( 'WOOSUBSCRIPTIONS' );

		// Product extra tokens.
		$this->product_extra_tokens = new Product_Extra_Tokens( 'WOOVARIPRODUCT' );

		// Add the Variation (WOOVARIPRODUCT) token to the trigger.
		$this->common_tokens->add_token(
			array(
				'tokenId'   => 'WOOVARIPRODUCT',
				'tokenName' => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			)
		);

		// Dropdown label.
		$this->set_readable_sentence(
			/* translators: %1$s: Variation, %2$s: Variable subscription */
			esc_html_x( "A user's trial period to {{a specific variation}} of {{a variable subscription}} expires", 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Variation, %2$s: Variable subscription, %3$s: Number of times */
				esc_html_x( "A user's trial period to {{a specific variation:%1\$s}} of {{a variable subscription:%2\$s}} expires {{a number of:%3\$s}} time(s)", 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::VARIATION_META . ':' . self::TRIGGER_META,
				self::TRIGGER_META,
				'NUMTIMES:' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		$this->add_action( 'woocommerce_scheduled_subscription_trial_end', 30, 1 );

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
		$product_extra_tokens     = $this->product_extra_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens, $variation_product_tokens, $product_extra_tokens );
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
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$subscription_id          = $hook_args[0] ?? 0;
		$subscription             = wcs_get_subscription( $subscription_id );
		$common_tokens            = $this->common_tokens->get_tokens();
		$variation_product_tokens = $this->variation_product_tokens->get_tokens();

		$tokens = array();

		// Parse common tokens
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Parse variation token.
		if ( is_a( $subscription, 'WC_Subscription' ) ) {
			$subscription_provider = new Subscription_Provider( $subscription );
			$variation             = $subscription_provider->get_subscription_variation();
			// Parse the variation token.
			$tokens['WOOVARIPRODUCT'] = $variation['variation_name'] ?? '??';
		}

		// Parse variation product tokens.
		foreach ( $variation_product_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->variation_product_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Store the order tokens using Legacy_Token_Storage for backwards compatibility.
		$legacy_token_storage = new Legacy_Token_Storage( $this->order_tokens, $this->trigger_records );
		$legacy_token_storage->save_legacy_tokens( $subscription );

		// Store the variation product tokens.
		$legacy_token_storage->set_variation_product_tokens( $this->product_extra_tokens );
		$legacy_token_storage->save_product_extra_tokens( $subscription );

		return $tokens;
	}

	/**
	 * Options
	 *
	 * @return array
	 */
	public function options() {

		$field_variable_subscription = array(
			'option_code'           => self::TRIGGER_META,
			'label'                 => esc_html_x( 'Variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'required'              => true,
			'options'               => array(),
			'relevant_tokens'       => array(),
			'supports_custom_value' => false,
			'ajax'                  => array(
				'endpoint'     => 'uncanny_automator_pro_fetch_variations',
				'event'        => 'on_load',
				'target_field' => self::VARIATION_META,
			),
		);

		$field_variation = array(
			'option_code'           => self::VARIATION_META,
			'label'                 => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'required'              => true,
			'options'               => array(),
			'relevant_tokens'       => array(),
			'supports_custom_value' => false,
			'ajax'                  => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( self::TRIGGER_META ),
			),
		);

		return array(
			$field_variable_subscription,
			$field_variation,
			Automator()->helpers->recipe->options->number_of_times(),
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

		$subscription_id = $hook_args[0] ?? 0;

		if ( empty( $subscription_id ) ) {
			return false;
		}

		$subscription = wcs_get_subscription( $subscription_id );

		if ( ! $subscription || ! is_a( $subscription, 'WC_Subscription' ) ) {
			return false;
		}

		// Set the user ID from the subscription
		$user_id = $subscription->get_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$selected_parent_id    = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );
		$selected_variation_id = intval( $trigger['meta'][ self::VARIATION_META ] ?? 0 );

		// If either selection is not set, return false
		if ( 0 === $selected_parent_id || 0 === $selected_variation_id ) {
			return false;
		}

		// Get subscription items
		$items = $subscription->get_items();
		if ( empty( $items ) ) {
			return false;
		}

		$product_ids        = array();
		$product_parent_ids = array();

		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				if ( $product->is_type( array( 'subscription_variation', 'variable-subscription' ) ) ) {
					$product_ids[]        = (int) $product->get_id();
					$product_parent_ids[] = (int) $product->get_parent_id();
				}
			}
		}

		if ( empty( $product_parent_ids ) ) {
			return false;
		}

		// Check if the selected parent product and variation match
		$parent_matches    = ( -1 === $selected_parent_id ) || in_array( $selected_parent_id, $product_parent_ids, true );
		$variation_matches = ( -1 === $selected_variation_id ) || in_array( $selected_variation_id, $product_ids, true );

		return $parent_matches && $variation_matches;
	}
}
