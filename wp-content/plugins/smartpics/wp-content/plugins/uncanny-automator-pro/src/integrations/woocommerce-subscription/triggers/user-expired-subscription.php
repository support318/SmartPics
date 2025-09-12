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
use Uncanny_Automator\Recipe\Trigger;

// Storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

/**
 * Class User_Expired_Subscription
 *
 * Handles subscription expiration triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains WOOSUBSCRIPTIONS_ prefix for subscription tokens
 * - Preserves original token IDs and formats for existing automations
 * - New order tokens are added without prefix to avoid conflicts
 * - Original trigger code (WCSUBSCRIPTIONEXPIRED) and meta (WOOSUBSCRIPTIONS) are preserved
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Expired_Subscription extends Trigger {

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
	const TRIGGER_CODE = 'WCSUBSCRIPTIONEXPIRED';

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
			esc_html_x( "A user's subscription to {{a product}} expires", 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product, %2$s: Number of times */
				esc_html_x( "A user's subscription to {{a product:%1\$s}} expires {{a number of:%2\$s}} times", 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				'NUMTIMES:' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		$this->add_action( 'woocommerce_subscription_status_expired', 30, 2 );

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

		$subscription = $this->get_wc_subscription( $hook_args );

		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return array();
		}

		// Parse common tokens
		$common_tokens = $this->common_tokens->get_tokens();

		$tokens = array();
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
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
		$subscriptions = $this->storage->get_subscriptions();

		$any_option = array(
			array(
				'value' => -1,
				'text'  => esc_html_x( 'Any subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			),
		);

		$field_subscriptions = array(
			'option_code'     => self::TRIGGER_META,
			'label'           => esc_html_x( 'Subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'options'         => array_merge( $any_option, $subscriptions ),
			'relevant_tokens' => array(),
		);

		$number_of_times = Automator()->helpers->recipe->options->number_of_times();

		return array(
			$field_subscriptions,
			$number_of_times,
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
		$selected_subscription_id = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );
		$wc_subscription          = $this->get_wc_subscription( $hook_args );

		if ( ! class_exists( '\WC_Subscription' )
			|| ! $wc_subscription instanceof \WC_Subscription ) {
			return false;
		}

		// If the selected subscription is not set, return false.
		if ( 0 === $selected_subscription_id ) {
			return false;
		}

		// If the selected subscription is any, return true.
		if ( -1 === $selected_subscription_id ) {
			return true;
		}

		$subscription_product_ids = $this->storage->get_subscription_product_ids( $wc_subscription );

		// Fire if the selected subscription is in the subscription product ids.
		return in_array( intval( $selected_subscription_id ), $subscription_product_ids, true );
	}

	/**
	 * Get the WC Subscription from the hook args.
	 *
	 * @param array $hook_args The hook args.
	 * @return \WC_Subscription
	 */
	protected function get_wc_subscription( $hook_args ) {
		return $hook_args[0] ?? null;
	}
}
