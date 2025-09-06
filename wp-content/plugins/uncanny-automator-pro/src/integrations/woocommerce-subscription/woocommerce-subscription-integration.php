<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription;

// Endpoints.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints\Fetch_Variations;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints\Fetch_Variation_Options;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints\Fetch_Subscriptions;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints\Fetch_Woo_Statuses;

// Triggers.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Cancelled_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Expired_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Expired_Trial_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Expired_Variation_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Failed_Renewal_Payment;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Purchased_Specific_Variation;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Purchased_Variable_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Renewed_Subscription_Nth_Time;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Renewed_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Renewed_Variation_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Subscribed_To_Product;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Subscription_Status_Changed;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Switched_Subscription_Variation;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Variation_Subscription_Status_Changed;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Triggers\User_Expired_Trial_Variation_Subscription;

// Actions
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Create_Subscription_Order;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Create_Subscription_Order_With_Payment_Gateway;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Cancel_Subscription_Variation_Specific;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Shorten_Subscription_X_Days;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Set_Subscription_Status;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Set_Subscription_Variation_Status;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Extend_Subscription_Renewal;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Extend_Subscription_Renewal_Product_Specific;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Extend_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Extend_Subscription_Variation;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Remove_Product_From_Subscription;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions\Remove_Product_From_Variation_Subscription;

// Universal loopable tokens
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Loopable\Universal\User_Active_Subscriptions;

// Loop filters

// Integration.
use Uncanny_Automator\Integration;

// Migration.
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration\Migrate_WCS_Integration;
use Uncanny_Automator_Pro\Loop_Filters\WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION;

/**
 * Class Woocommerce_Subscription_Integration
 *
 * @package Uncanny_Automator_Pro
 *
 * @since 5.1
 */
class Woocommerce_Subscription_Integration extends Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_name( esc_html_x( 'Woo Subscriptions', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ) );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/woo-subscriptions-icon.svg' );

		// If the method exists, set the loopable tokens.
		if ( method_exists( $this, 'set_loopable_tokens' ) ) {

			$this->set_loopable_tokens(
				array(
					User_Active_Subscriptions::class,
				)
			);

			// Register the integration hooks.
			$this->register_hooks();

			return;

		}

		// If the method does not exist, set the loopable tokens. (Backwards compatibility)
		$loopable_tokens = new User_Active_Subscriptions( $this->get_integration() );
		// Registers the loopable tokens.
		$loopable_tokens->register_hooks();

		// Register integration hooks.
		$this->register_hooks();
	}

	/**
	 * Register AJAX hooks
	 *
	 * @return void
	 */
	protected function register_hooks() {

		add_action( 'wp_ajax_uncanny_automator_pro_fetch_variations', array( new Fetch_Variations(), 'handle' ) );
		add_action( 'wp_ajax_uncanny_automator_pro_fetch_variation_options', array( new Fetch_Variation_Options(), 'handle' ) );
		add_action( 'wp_ajax_automator_select_all_wc_subscriptions', array( new Fetch_Subscriptions(), 'handle' ) );
		add_action( 'wp_ajax_uncanny_automator_pro_fetch_woo_statuses', array( new Fetch_Woo_Statuses(), 'handle' ) );
	}
	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {

		// Check if WooCommerce Subscriptions is active.
		if ( ! class_exists( '\WC_Subscriptions' ) ) {
			return;
		}

		// Run the migration.
		new Migrate_WCS_Integration( '66_migration_to_woocommerce_subscription_v1' );

		// Triggers
		new User_Cancelled_Subscription();
		new User_Expired_Subscription();
		new User_Expired_Trial_Subscription();
		new User_Expired_Variation_Subscription();
		new User_Failed_Renewal_Payment();
		new User_Purchased_Specific_Variation();
		new User_Purchased_Variable_Subscription();
		new User_Renewed_Subscription_Nth_Time();
		new User_Renewed_Subscription();
		new User_Renewed_Variation_Subscription();
		new User_Subscribed_To_Product();
		new User_Subscription_Status_Changed();
		new User_Switched_Subscription_Variation();
		new User_Variation_Subscription_Status_Changed();
		new User_Expired_Trial_Variation_Subscription();

		// Actions
		new Create_Subscription_Order();
		new Create_Subscription_Order_With_Payment_Gateway();
		new Cancel_Subscription_Variation_Specific();
		new Shorten_Subscription_X_Days();
		new Set_Subscription_Status();
		new Set_Subscription_Variation_Status();
		new Extend_Subscription_Renewal();
		new Extend_Subscription_Renewal_Product_Specific();
		new Extend_Subscription();
		new Extend_Subscription_Variation();
		new Remove_Product_From_Subscription();
		new Remove_Product_From_Variation_Subscription();

		// Loop filters
		new WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION();
	}
}
