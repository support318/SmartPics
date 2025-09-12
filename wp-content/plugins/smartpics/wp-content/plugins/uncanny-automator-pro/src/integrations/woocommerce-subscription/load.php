<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Bail if class is not autoloaded.
 */
if ( ! class_exists( '\Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Woocommerce_Subscription_Integration' ) ) {
	return;
}

/**
 * Loads the integration.
 */
new \Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Woocommerce_Subscription_Integration();
