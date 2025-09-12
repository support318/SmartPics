<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings\Woocommerce_Bookings_Integration' ) ) {
	return;
}

new Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings\Woocommerce_Bookings_Integration();
