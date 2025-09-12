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
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

// Trigger.
use Uncanny_Automator\Recipe\Trigger;

/**
 * Class User_Expired_Trial_Subscription
 *
 * Handles subscription trial expiration triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains original trigger code (WCSUBSCRIPTIONTRIALEXPIRES) and meta (WOOSUBSCRIPTIONS)
 * - Preserves original token IDs and formats for existing automations
 *
 * Test case:
 *
 * $subscription_id = 30; // Change this to the subscription ID you want to test.
 * do_action( 'woocommerce_scheduled_subscription_trial_end', $subscription_id );
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Expired_Trial_Subscription extends Trigger {

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
	const TRIGGER_CODE = 'WCSUBSCRIPTIONTRIALEXPIRES';

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

		// Dropdown label.
		$this->set_readable_sentence(
			/* translators: %1$s: Subscription product */
			esc_html_x( "A user's trial period to {{a subscription}} expires", 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product, %2$s: Number of times */
				esc_html_x( "A user's trial period to {{a subscription:%1\$s}} expires {{a number of:%2\$s}} time(s)", 'WooCommerce Subscription', 'uncanny-automator-pro' ),
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

		$common_tokens = $this->common_tokens->get_tokens();
		$order_tokens  = $this->order_tokens->get_tokens();

		return array_merge( $tokens, $common_tokens, $order_tokens );
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

		$subscription_id = $hook_args[0] ?? 0;
		$subscription    = wcs_get_subscription( $subscription_id );
		$common_tokens   = $this->common_tokens->get_tokens();

		$tokens = array();

		// Parse common tokens
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Store the legacy tokens.
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
		$field_subscriptions = array(
			'option_code'     => self::TRIGGER_META,
			'label'           => esc_html_x( 'Subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
			'ajax'            => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
			),
		);

		return array(
			$field_subscriptions,
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

		$selected_subscription_id = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );

		// If the selected subscription is not set, return false.
		if ( 0 === $selected_subscription_id ) {
			return false;
		}

		// Get subscription items
		$items = $subscription->get_items();
		if ( empty( $items ) ) {
			return false;
		}

		$product_ids = array();
		foreach ( $items as $item ) {
			$product_ids[] = $item->get_product_id();
		}

		// If the selected subscription is any, return true.
		if ( -1 === $selected_subscription_id ) {
			return true;
		}

		// Check if the selected subscription matches any of the subscription items
		return in_array( $selected_subscription_id, $product_ids, true );
	}
}
