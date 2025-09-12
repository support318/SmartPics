<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Search & Filter Pro integration class.
 *
 * @since 3.43.15
 */
class WPF_Search_Filter extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.43.15
	 * @var string $slug
	 */

	public $slug = 'search-filter';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.43.15
	 * @var string $name
	 */
	public $name = 'Search & Filter Pro';

	/**
	 * Gets things started.
	 *
	 * @since 3.43.15
	 */
	public function init() {

		// Class Search_Filter runs on the pre_get_posts action at priority 10000.
		wp_fusion()->access->filter_queries_priority = 10001;

		remove_action( 'pre_get_posts', array( wp_fusion()->access, 'filter_queries' ) );

		add_action( 'pre_get_posts', array( wp_fusion()->access, 'filter_queries' ), 10001 );
	}
}

new WPF_Search_Filter();
