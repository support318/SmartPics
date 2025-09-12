<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_OpenAI_Pro_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Open_AI_Pro_Integration {

	use Recipe\Integrations;

	public function __construct() {

		$this->setup();

	}

	/**
	 * Sets up OpenAI integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'OPEN_AI' );

		$this->set_name( 'OpenAI' );

		$this->set_icon( '/img/openai-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );

		$this->set_connected( '' !== automator_pro_get_option( 'automator_open_ai_secret', '' ) ? true : false );

		$this->set_settings_url( automator_get_premium_integrations_settings_url( 'open-ai' ) );

	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}

	/**
	 * Determines whether the integration should be loaded or not.
	 *
	 * @return bool True. Always.
	 */
	public function plugin_active() {

		// Check if Automator is atleast 4.10.
		return defined( 'AUTOMATOR_PLUGIN_VERSION' ) && version_compare( AUTOMATOR_PLUGIN_VERSION, '4.10', '>=' );

	}

}

