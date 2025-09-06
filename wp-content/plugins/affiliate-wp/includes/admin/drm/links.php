<?php
/**
 * DRM UTM Links
 *
 * Must return an array of UTM links.
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
 * Return an array of links used for UTM, based on the customer DRM level.
 *
 * @return array Array of links.
 */
return array(
	'initiated' => array(
		'email'   => array(
			'account'            => 'https://affiliatewp.com/drm/email/account',
			'docs-renew-license' => 'https://affiliatewp.com/drm/email/docs/renew-license',
		),
		'general' => array(
			'account'            => 'https://affiliatewp.com/drm/ipm/account',
			'docs-renew-license' => 'https://affiliatewp.com/drm/ipm/docs/renew-license',
		),
	),
	'low_level' => array(
		'email'   => array(
			'home'                => 'https://affiliatewp.com/drmlow/email',
			'account'             => 'https://affiliatewp.com/drmlow/email/account',
			'support'             => 'https://affiliatewp.com/drmlow/email/support',
			'pricing'             => 'https://affiliatewp.com/drmlow/email/pricing',
			'docs-locate-license' => 'https://affiliatewp.com/drmlow/email/docs/locate-license',
		),
		'general' => array(
			'home'                => 'https://affiliatewp.com/drmlow/ipm',
			'account'             => 'https://affiliatewp.com/drmlow/ipm/account',
			'support'             => 'https://affiliatewp.com/drmlow/ipm/support',
			'pricing'             => 'https://affiliatewp.com/drmlow/ipm/pricing',
			'docs-locate-license' => 'https://affiliatewp.com/drmlow/ipm/docs/locate-license',
		),
	),
	'med_level' => array(
		'email'   => array(
			'home'                => 'https://affiliatewp.com/drmmed/email',
			'account'             => 'https://affiliatewp.com/drmmed/email/account',
			'support'             => 'https://affiliatewp.com/drmmed/email/support',
			'pricing'             => 'https://affiliatewp.com/drmmed/email/pricing',
			'docs-locate-license' => 'https://affiliatewp.com/drmmed/email/docs/locate-license',
			'docs-renew-license'  => 'https://affiliatewp.com/drmmed/email/docs/renew-license',
		),
		'general' => array(
			'home'                => 'https://affiliatewp.com/drmmed/ipm',
			'account'             => 'https://affiliatewp.com/drmmed/ipm/account',
			'support'             => 'https://affiliatewp.com/drmmed/ipm/support',
			'pricing'             => 'https://affiliatewp.com/drmmed/ipm/pricing',
			'docs-locate-license' => 'https://affiliatewp.com/drmmed/ipm/docs/locate-license',
			'docs-renew-license'  => 'https://affiliatewp.com/drmmed/ipm/docs/renew-license',
		),
	),
	'locked'    => array(
		'email'   => array(
			'home'    => 'https://affiliatewp.com/drmlock/email',
			'account' => 'https://affiliatewp.com/drmlock/email/account',
			'support' => 'https://affiliatewp.com/drmlock/email/support',
			'pricing' => 'https://affiliatewp.com/drmlock/email/pricing',
		),
		'general' => array(
			'home'    => 'https://affiliatewp.com/drmlock/ipm',
			'account' => 'https://affiliatewp.com/drmlock/ipm/account',
			'support' => 'https://affiliatewp.com/drmlock/ipm/support',
			'pricing' => 'https://affiliatewp.com/drmlock/ipm/pricing',
		),
	),
);
