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

// Legacy token storage.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage\Legacy_Token_Storage;

// Uncanny Automator.
use Uncanny_Automator\Recipe\Trigger;

/**
 * Class User_Switched_Subscription_Variation
 *
 * Handles subscription variation switch triggers with backwards compatibility support.
 * This class maintains compatibility with existing automations while providing enhanced token support.
 *
 * Backwards Compatibility Notes:
 * - Maintains original trigger code (WCSUBSCRIPTIONSSWITCHED) and meta (WOOVARIPRODUCT)
 * - Preserves original token IDs and formats for existing automations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers
 */
class User_Switched_Subscription_Variation extends Trigger {

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
	const TRIGGER_CODE = 'WCSUBSCRIPTIONSSWITCHED';

	/**
	 * Preserves the trigger meta for back compat.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'WOOVARIPRODUCT';

	/**
	 * Variation from meta
	 *
	 * @var string
	 */
	const VARIATION_FROM_META = 'WOOVARIPRODUCT_FROM';

	/**
	 * Variation to meta
	 *
	 * @var string
	 */
	const VARIATION_TO_META = 'WOOVARIPRODUCT_TO';

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

		$this->storage                  = new Subscription_Storage();
		$this->common_tokens            = new Common_Tokens( self::TRIGGER_META, true );
		$this->order_tokens             = new Order_Tokens( self::TRIGGER_META );
		$this->variation_product_tokens = new Variation_Product_Tokens( self::TRIGGER_META );
		$this->product_extra_tokens     = new Product_Extra_Tokens( self::TRIGGER_META );

		// Dropdown label.
		$this->set_readable_sentence(
			/* translators: %1$s: Previous variation, %2$s: New variation */
			esc_html_x( "A user's subscription switches from {{a specific variation}} to {{a specific variation}}", 'WooCommerce Subscription', 'uncanny-automator-pro' )
		);

		// On select label.
		$this->set_sentence(
			sprintf(
				/* translators: %1$s: Product, %2$s: Previous variation, %3$s: New variation */
				esc_html_x( "A user's subscription switches from {{a specific variation:%2\$s}} to {{a specific variation:%3\$s}}", 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				self::TRIGGER_META,
				self::VARIATION_FROM_META . ':' . self::TRIGGER_META,
				self::VARIATION_TO_META . ':' . self::TRIGGER_META
			)
		);

		$this->set_integration( self::INTEGRATION_CODE );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_login_required( true );
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

		$this->add_action( 'woocommerce_subscription_item_switched', 30, 4 );
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

		// Add variation-specific tokens
		$variation_tokens = array(
			array(
				'tokenId'   => 'WOOVARIPRODUCT_FROM',
				'tokenName' => esc_html_x( 'Previous variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WOOVARIPRODUCT_TO',
				'tokenName' => esc_html_x( 'New variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge(
			$tokens,
			$common_tokens,
			$order_tokens,
			$variation_product_tokens,
			$variation_tokens,
			$product_extra_tokens
		);
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

		list( $order, $subscription, $add_line_item, $remove_line_item ) = $hook_args;
		$common_tokens = $this->common_tokens->get_tokens();

		$tokens = array();

		// Parse common tokens
		foreach ( $common_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->common_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Parse variation product tokens.
		$variation_product_tokens = $this->variation_product_tokens->get_tokens();
		foreach ( $variation_product_tokens as $token ) {
			$token_id = $token['tokenId'] ?? null;
			if ( null !== $token_id ) {
				$tokens[ $token_id ] = $this->variation_product_tokens->parse_token( $token_id, $subscription );
			}
		}

		// Add variation-specific tokens
		$variation_id_from = wc_get_order_item_meta( $remove_line_item, '_variation_id', true );
		$variation_id_to   = wc_get_order_item_meta( $add_line_item, '_variation_id', true );
		// Get variation names
		$previous_variation = wc_get_product( $variation_id_from );
		$new_variation      = wc_get_product( $variation_id_to );

		$tokens['WOOVARIPRODUCT_FROM'] = $previous_variation ? $previous_variation->get_name() : '';
		$tokens['WOOVARIPRODUCT_TO']   = $new_variation ? $new_variation->get_name() : '';

		// Save the legacy tokens for backwards compatibility
		$legacy_token_storage = new Legacy_Token_Storage( $this->order_tokens, $this->trigger_records );
		$legacy_token_storage->set_order_tokens_key( self::TRIGGER_META );
		$legacy_token_storage->save_legacy_tokens( $subscription, $order );

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
			'label'                 => esc_html_x( 'Product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'required'              => true,
			'options'               => array(),
			'relevant_tokens'       => array(),
			'supports_custom_value' => false,
			'ajax'                  => array(
				'endpoint'     => 'uncanny_automator_pro_fetch_variations',
				'event'        => 'on_load',
				'target_field' => self::VARIATION_FROM_META,
			),
		);

		$field_variation_from = array(
			'option_code'           => self::VARIATION_FROM_META,
			'label'                 => esc_html_x( 'Previous variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'required'              => true,
			'options'               => array(),
			'relevant_tokens'       => array(),
			'supports_custom_value' => false,
			'ajax'                  => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( self::TRIGGER_META ),
				'target_field'  => self::VARIATION_TO_META,
			),
		);

		$field_variation_to = array(
			'option_code'           => self::VARIATION_TO_META,
			'label'                 => esc_html_x( 'New variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
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
			$field_variation_from,
			$field_variation_to,
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

		list( $order, $subscription, $add_line_item, $remove_line_item ) = $hook_args;

		// Basic validation - ensure we have all required objects and the items are different
		if ( ! isset( $order ) || ! isset( $subscription ) || $add_line_item === $remove_line_item ) {
			return false;
		}

		// Get the product and variation IDs
		$product_id        = wc_get_order_item_meta( $add_line_item, '_product_id', true );
		$variation_id_from = wc_get_order_item_meta( $remove_line_item, '_variation_id', true );
		$variation_id_to   = wc_get_order_item_meta( $add_line_item, '_variation_id', true );

		// Get the selected values from the trigger
		$selected_product_id     = intval( $trigger['meta'][ self::TRIGGER_META ] ?? 0 );
		$selected_variation_from = intval( $trigger['meta'][ self::VARIATION_FROM_META ] ?? 0 );
		$selected_variation_to   = intval( $trigger['meta'][ self::VARIATION_TO_META ] ?? 0 );

		// If any selection is not set, return false
		if ( 0 === $selected_product_id || 0 === $selected_variation_from || 0 === $selected_variation_to ) {
			return false;
		}

		// Check if the product matches
		$product_matches = ( -1 === $selected_product_id ) || ( intval( $selected_product_id ) === intval( $product_id ) );
		if ( ! $product_matches ) {
			return false;
		}

		// Check if the variations match
		$from_matches = ( -1 === $selected_variation_from ) || ( intval( $selected_variation_from ) === intval( $variation_id_from ) );
		$to_matches   = ( -1 === $selected_variation_to ) || ( intval( $selected_variation_to ) === intval( $variation_id_to ) );

		return $from_matches && $to_matches;
	}
}
