<?php
/**
 * Plugin Name:         Uncanny Automator Pro
 * Description:         Add hundreds of triggers, actions, and tokens plus delays, conditions, loops and more with this premium addon for Uncanny Automator.
 * Author:              Uncanny Automator
 * Author URI:          https://www.uncannyowl.com/
 * Plugin URI:          https://automatorplugin.com/
 * Text Domain:         uncanny-automator-pro
 * Domain Path:         /languages
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Version:             6.7.0.3
 * Requires at least:   5.6
 * Requires PHP:        7.3
 * Requires Plugins:    uncanny-automator
 */

use Uncanny_Automator\Automator_Functions;
use Uncanny_Automator_Pro\Automator_Pro_Load;

if ( ! defined( 'AUTOMATOR_PRO_PLUGIN_VERSION' ) ) {
	/**
	 * Specify Automator Pro version.
	 */
	define( 'AUTOMATOR_PRO_PLUGIN_VERSION', '6.7.0.3' );
}

if ( ! defined( 'AUTOMATOR_PRO_FILE' ) ) {
	/**
	 * Specify Automator Pro base file.
	 */
	define( 'AUTOMATOR_PRO_FILE', __FILE__ );
}

if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {

	add_action( 'admin_notices', 'automator_pro_version_check_admin_notice', - 99999 + 1 );

	/**
	 * Displays an error message (notice) if PHP version is less than 7.0
	 *
	 * @since 5.6
	 *
	 * @return void
	 */
	function automator_pro_version_check_admin_notice() {
		?>
		<div class="notice notice-error"
			 style="border-left: 4px solid #dc3232; font-weight: bold; background-color: #fff4e5; color: #000;">
			<p>
				<?php
				//Translators: %s: The version number of Uncanny Automator Pro.
				echo sprintf( esc_html__( 'Notice: Uncanny Automator Pro v%s requires PHP 7.0 or higher to run properly. Your current PHP version is below this requirement, so the plugin has been deactivated and all automations have stopped. Please upgrade your PHP version to ensure that your automations and other plugin features work correctly.', 'uncanny-automator-pro' ), esc_html( AUTOMATOR_PRO_PLUGIN_VERSION ) );
				?>
			</p>
		</div>
		<?php
	}

	// Stop loading the plugin.
	return;
}

/**
 * @param string $class
 *
 * @return void
 */
function automator_pro_autoloader( $class ) {

	$class = strtolower( $class );

	global $automator_pro_class_map;

	if ( ! $automator_pro_class_map ) {
		$automator_pro_class_map = include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_classmap.php';
		$automator_pro_class_map = array_change_key_case( $automator_pro_class_map, CASE_LOWER );
	}

	if ( isset( $automator_pro_class_map[ $class ] ) ) {
		include_once $automator_pro_class_map[ $class ];
	}
}

spl_autoload_register( 'automator_pro_autoloader' );

// Add other global variables for plugin.
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'globals.php';
// Add InitializePlugin class for other plugins checking for version.
require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'legacy.php';

/**
 * If Automator function is not defined AND Automator < 3.0, add Automator fallback
 *
 * @return Automator_Functions
 */
function Automator_Pro() { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	if ( defined( 'AUTOMATOR_PLUGIN_VERSION' ) && function_exists( 'Automator' ) ) {
		return Automator();
	}
	// this global variable stores many functions that can be used for integrations, triggers, actions, and closures.
	global $uncanny_automator;

	return $uncanny_automator;
}

// Include the Automator_Load class and kickstart Automator Pro.
if ( ! class_exists( '\Uncanny_Automator_Pro\Automator_Pro_Load', false ) ) {
	include_once UAPro_ABSPATH . 'src/class-automator-pro-load.php';
}

Automator_Pro_Load::get_instance();
