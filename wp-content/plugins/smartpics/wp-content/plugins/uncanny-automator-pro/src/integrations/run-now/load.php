<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Bail if class is not autoloaded.
 */
if ( ! class_exists( 'Uncanny_Automator_Pro\Integrations\Run_Now\Run_Now_Integration' ) ) {
	return;
}

if ( ! defined( 'AUTOMATOR_PLUGIN_VERSION' ) ) {
	return;
}

if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '5.1', '<' ) ) {
	return;
}

/**
 * Loads the REST handler.
 */
$rest = new Uncanny_Automator_Pro\Integrations\Run_Now\Rest();
$rest->initialize_rest();

/**
 * Loads the integration.
 */
new Uncanny_Automator_Pro\Integrations\Run_Now\Run_Now_Integration();
