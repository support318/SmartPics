<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce High Performance Order Storage (HPOS) and blocks support.
 *
 * @since 3.42.9
 */
class WPF_WooCommerce_Compatibility {


	/**c
	 * Constructor.
	 *
	 * Enable HPOS support for WooCommerce.
	 *
	 * @since 3.42.9.
	 */
	public function __construct() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_supported_features' ) );
	}

	/**
	 * Declare support for High Performance Order Storage (HPOS).
	 *
	 * @since 3.40.45
	 *
	 * @link https://woocommerce.wordpress.com/2021/03/02/high-performance-order-storage-in-woocommerce-5-5/
	 */
	public function declare_supported_features() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPF_PLUGIN_PATH, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', WPF_PLUGIN_PATH, false );

			if ( wpf_get_option( 'email_optin' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPF_PLUGIN_PATH, false );
			} else {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPF_PLUGIN_PATH, true );
			}
		}
	}
}

new WPF_WooCommerce_Compatibility();
