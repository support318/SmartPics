<?php
/**
 * DRM Site Health Messages List
 *
 * Must return an array with site health test structured array.
 *
 * @see https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\DRM
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.21.1
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves an array of Site Health tests to be executed.
 *
 * Check the [WordPress documentation](https://developer.wordpress.org/reference/hooks/site_status_tests/) on how to add site health tests.
 *
 * @return array Array of tests.
 */
return array(
	'invalid'    => array(
		'label'       => __( 'AffiliateWP license key has expired.', 'affiliate-wp' ),
		'status'      => 'critical',
		'badge'       => array(
			'label' => __( 'AffiliateWP', 'affiliate-wp' ),
			'color' => 'red',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your AffiliateWP license key has expired which means you cannot access new features &amp; addons, plugin updates (including security improvements), or access our world class support.', 'affiliate-wp' )
		),
		'actions'     => sprintf(
			'<p>%s</p><p>%s</p>',
			sprintf(
				/* translators: 1: Link to the account page to new license. 2: Additional link attributes. 3: Accessibility text. */
				__( '<a href="%1$s" %2$s>Renew license now%3$s</a>', 'affiliate-wp' ),
				esc_url( affiliate_wp()->drm->get_utm_link( 'account' ) ),
				'target="_blank" rel="noopener"',
				sprintf(
					'<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>',
					/* translators: Hidden accessibility text. */
					__( '(opens in a new tab)', 'affiliate-wp' )
				)
			),
			sprintf(
				/* translators: 1: Link to the doc page on renewing license. 2: Additional link attributes. 3: Accessibility text. */
				__( '<a href="%1$s" %2$s>Learn more%3$s</a>', 'affiliate-wp' ),
				esc_url( affiliate_wp()->drm->get_utm_link( 'docs-renew-license' ) ),
				'target="_blank" rel="noopener"',
				sprintf(
					'<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>',
					/* translators: Hidden accessibility text. */
					__( '(opens in a new tab)', 'affiliate-wp' )
				)
			)
		),
		'test'        => 'affiliatewp_drm',
	),
	'unlicensed' => array(
		'label'       => __( 'AffiliateWP is not licensed', 'affiliate-wp' ),
		'status'      => 'critical',
		'badge'       => array(
			'label' => __( 'AffiliateWP', 'affiliate-wp' ),
			'color' => 'red',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'AffiliateWP is not licensed which means you can\'t access automatic updates, and other advanced features.', 'affiliate-wp' )
		),
		'actions'     => sprintf(
			'<a href="%s">Add your license</a>',
			esc_url( affwp_admin_url( 'settings' ) )
		),
		'test'        => 'affiliatewp_drm',
	),
	'locked'     => array(
		'label'       => __( 'AffiliateWP backend is deactivated', 'affiliate-wp' ),
		'status'      => 'critical',
		'badge'       => array(
			'label' => __( 'AffiliateWP', 'affiliate-wp' ),
			'color' => 'red',
		),
		'description' => sprintf(
			'<p>%s</p>',
			__( 'Your AffiliateWP license key is missing or is invalid. Without an active license key, your front-end website is unaffected. However, you can no longer disburse affiliate payouts, register &amp; manage affiliates, analyze performance data, and more.', 'affiliate-wp' )
		),
		'actions'     => sprintf(
			'<a href="%s">Fix this issue</a>',
			esc_url( affwp_admin_url( 'affiliates' ) )
		),
		'test'        => 'affiliatewp_drm',
	),
	'active'     => array(
		'label'       => __( 'Your AffiliateWP license is valid', 'affiliate-wp' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'AffiliateWP', 'affiliate-wp' ),
			'color' => 'blue',
		),
		'description' => __( 'You have access to updates, addons, new features, and more.', 'affiliate-wp' ),
		'actions'     => '',
		'test'        => 'affiliatewp_drm',
	),
);
