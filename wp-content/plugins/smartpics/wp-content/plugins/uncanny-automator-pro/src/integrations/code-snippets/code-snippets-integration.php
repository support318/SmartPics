<?php

namespace Uncanny_Automator_Pro\Integrations\Code_Snippets;

use Uncanny_Automator\Integration;
use Uncanny_Automator\Integrations\Code_Snippets\Code_Snippets_Helpers;

/**
 * Class Code_Snippets_Integration
 *
 * @pacakge Uncanny_Automator_Pro
 */
class Code_Snippets_Integration extends Integration {
	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'CODE_SNIPPETS' );
		$this->set_name( 'Code Snippets' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/code-snippets-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers.

		// Load ajax methods.
		add_action( 'wp_ajax_get_all_scopes_by_code_types', array( $this->helpers, 'get_all_scopes_by_code_types' ) );

		// Load actions.
		new CODE_SNIPPETS_CREATE_SNIPPET( $this->helpers );

	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		if ( ! class_exists( '\Uncanny_Automator\Integrations\Code_Snippets\Code_Snippets_Helpers' ) ) {
			return false;
		}
		$this->helpers = new Code_Snippets_Helpers();
		return function_exists( 'Code_Snippets\code_snippets' );
	}
}
