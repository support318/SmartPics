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
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

/**
 * Class User_Subscription_Status_Changed
 *
 * Handles subscription status change triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains original trigger code (WCSUBSCRIPTIONSTATUSCHANGED) and meta (WOOSUBSCRIPTIONS, WOOSUBSCRIPTIONSTATUS)
 * - Preserves original token IDs and formats for existing automations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Subscription_Status_Changed extends Trigger {

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
	const TRIGGER_CODE = 'WCSUBSCRIPTIONSTATUSCHANGED';

	/**
	 * Preserves the trigger meta for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WOOSUBSCRIPTIONS';

	/**
	 * Preserves the trigger meta status for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META_STATUS = 'WOOSUBSCRIPTIONSTATUS';

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

		$this->common_tokens->add_token(
			array(
				'tokenId'   => 'WOOSUBSCRIPTIONSTATUS',
				'tokenName' => esc_html_x( 'Status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			)
		);

		// Dropdown label.
		$this->set_readable_sentence(
			/* translators: %1$s: Subscription product, %2$s: Status */
			esc_html_x( "A user's subscription to {{a product}} is set to {{a status}}", 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Subscription product, %2$s: Status */
				esc_html_x( "A user's subscription to {{a product:%1\$s}} is set to {{a status:%2\$s}}", 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				self::TRIGGER_META_STATUS . ':' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/woocommerce-subscription/' ) );

		$this->add_action( 'woocommerce_subscription_status_updated', 30, 3 );

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

		// Add subscription status token
		$status_token = array(
			'tokenId'   => 'SUBSCRIPTION_STATUS',
			'tokenName' => esc_html_x( 'Subscription status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		return array_merge( $tokens, $common_tokens, $order_tokens, array( $status_token ) );
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

		$subscription  = $this->get_wc_subscription( $hook_args );
		$new_status    = $hook_args[1] ?? '';
		$common_tokens = $this->common_tokens->get_tokens();

		$tokens = array();

		// Parse the status token.
		$tokens['WOOSUBSCRIPTIONSTATUS'] = $new_status;

		// Parse common tokens
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Store legacy tokens for backwards compatibility.
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

		$any_subscription_option = array(
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
			'options'         => array_merge( $any_subscription_option, $subscriptions ),
			'relevant_tokens' => array(),
		);

		// Get subscription statuses
		$statuses = $this->get_wcs_statuses();

		$field_statuses = array(
			'option_code'     => self::TRIGGER_META_STATUS,
			'label'           => esc_html_x( 'Status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $statuses,
			'relevant_tokens' => array(),
		);

		return array(
			$field_subscriptions,
			$field_statuses,
		);
	}

	/**
	 * Get WooCommerce Subscription statuses
	 *
	 * @return array
	 */
	protected function get_wcs_statuses() {

		$any_status_option = array(
			array(
				'value' => -1,
				'text'  => esc_html_x( 'Any status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			),
		);

		$statuses = array();

		if ( function_exists( 'wcs_get_subscription_statuses' ) ) {
			$wc_statuses = wcs_get_subscription_statuses();

			foreach ( $wc_statuses as $status_key => $status_name ) {
				$statuses[] = array(
					'value' => $status_key,
					'text'  => $status_name,
				);
			}
		}

		return array_merge( $any_status_option, $statuses );
	}

	/**
	 * Validate the trigger.
	 *
	 * @param array $trigger The trigger.
	 * @param array $hook_args The hook args.
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		$wc_subscription = $this->get_wc_subscription( $hook_args );
		$new_status      = $hook_args[1] ?? '';

		if ( ! class_exists( '\WC_Subscription' )
			|| ! $wc_subscription instanceof \WC_Subscription ) {
			return false;
		}

		// Set the user ID from the subscription
		$user_id = $wc_subscription->get_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$selected_subscription_id = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );
		$selected_status          = $trigger['meta'][ self::TRIGGER_META_STATUS ] ?? '';

		// If the selected subscription is not set, return false.
		if ( 0 === $selected_subscription_id ) {
			return false;
		}

		// Check if the selected status matches the new status
		$status_matches = ( -1 === intval( $selected_status ) ) || ( "wc-{$new_status}" === $selected_status );
		if ( ! $status_matches ) {
			return false;
		}

		// If the selected subscription is any, return true.
		if ( -1 === $selected_subscription_id ) {
			return true;
		}

		// Check if the selected subscription matches any of the subscription items
		$items = $wc_subscription->get_items();
		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product_id ) ) {
				if ( intval( $selected_subscription_id ) === $product_id ) {
					return true;
				}
			}
		}

		return false;
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
