<?php
namespace Uncanny_Automator\Integrations\Loopable_Json;

use Uncanny_Automator_Pro\Integrations\Loopable_Json\Loopable_Json_Integration;

/**
 * Checks if the required dependencies for the Loopable JSON integration are loaded.
 *
 * This function checks the following conditions:
 * 1. If 'ABSPATH' is defined.
 * 2. If the class 'Loopable_Json_Integration' exists.
 * 3. If the 'create_loopable_token' method exists in the Loopable_Json_Integration class.
 * 4. If the Automator plugin version is 6.0 or higher.
 *
 * @return bool True if all dependencies are loaded, false otherwise.
 */
function is_dependencies_loaded() {

	$criterias = array();

	// Check if ABSPATH is defined.
	$criterias[] = defined( 'ABSPATH' );

	// Check if the class Loopable_Json_Integration exists.
	$criterias[] = class_exists( '\Uncanny_Automator_Pro\Integrations\Loopable_Json\Loopable_Json_Integration' );

	// Check if the method 'set_loopable_tokens' exists in the Loopable_Json_Trigger class.
	$criterias[] = method_exists( '\Uncanny_Automator\Integrations\Loopable_Json\Triggers\Loopable_Json_Trigger', 'set_loopable_tokens' );

	// Check if the method 'set_loopable_tokens' exists in the Loopable_Json_Action class.
	$criterias[] = method_exists( '\Uncanny_Automator\Integrations\Loopable_Json\Actions\Loopable_Json_Action', 'set_loopable_tokens' );

	// Check if the Automator plugin version is 6.0 or higher.
	$criterias[] = version_compare( AUTOMATOR_PLUGIN_VERSION, '6.0', '>=' );

	// All criteria must be true for the dependencies to be considered loaded.
	$criteria_met = count(
		array_filter(
			$criterias,
			function( $value ) {
				return $value === true;
			}
		)
	) === count( $criterias );

	return $criteria_met;

}

// Initialize the Loopable_Json_Integration class if all dependencies are loaded.
if ( is_dependencies_loaded() ) {
	new Loopable_Json_Integration();
}
