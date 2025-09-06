<?php
/**
 * Admin: Plugins Screen Ajustments
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Plugins
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Plugins row action links
 *
 * @since 1.0
 * @since 2.9.5 Updated links
 * @param array $links already defined action links
 * @param string $file plugin file path and name being processed
 * @return array $links
 */
function affwp_plugin_action_links( $links, $file ) {
	// Bail if the file is not for affiliate-wp.
	if ( $file !== 'affiliate-wp/affiliate-wp.php' ) {
		return $links;
	}

	$affwp_links = array(
		affwp_admin_link( 'settings', __( 'Settings', 'affiliate-wp' ) ),
		'<a href="https://affiliatewp.com/docs/" target="_blank">' . esc_html__( 'Documentation', 'affiliate-wp' ) . '</a>',
		'<a href="https://affiliatewp.com/contact/?utm_source=WordPress&utm_campaign=affiliatewp&utm_medium=plugin-action-links&utm_content=Support" target="_blank">' . esc_html__( 'Support', 'affiliate-wp' ) . '</a>',
	);

	return array_merge( $affwp_links, $links );
}
add_filter( 'plugin_action_links', 'affwp_plugin_action_links', 10, 2 );
