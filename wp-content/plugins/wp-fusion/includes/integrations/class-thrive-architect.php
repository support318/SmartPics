<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_ThriveArchitect extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.43.3
	 * @var string $slug
	 */

	public $slug = 'thrive-architect';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.43.3
	 * @var string $name
	 */
	public $name = 'Thrive Architect';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.43.3
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/page-builders/thrive-architect/';

	/**
	 * Gets things started.
	 *
	 * @since 3.43.3
	 */
	public function init() {
		add_action( 'tcb_landing_page_template_redirect', array( wp_fusion()->access, 'template_redirect' ) );
	}
}

new WPF_ThriveArchitect();
