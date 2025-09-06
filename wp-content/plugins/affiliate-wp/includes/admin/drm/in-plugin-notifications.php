<?php
/**
 * DRM Notifications
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
 * In order to display in-plugin notifications, you need to return an array with the {state}_{level}. Eg.:
 *
 * 'unlicensed_low_level' => array(
 *     'title' => __( 'Unlicensed Low Level title', 'affiliate-wp' ),
 *     'content' => __( 'Unlicensed Low Level content', 'affiliate-wp' ),
 *     'buttons' => array(
 *         array(
 *             'type' => 'primary',
 *             'url' => esc_url( affwp_admin_url( 'settings' ) ),
 *             'text' => __( 'Activate license', 'affiliate-wp' )
 *         ),
 *         array(
 *             'type' => 'secondary',
 *             'url' => esc_url( affiliate_wp()->drm->get_utm_link( 'account' ) ),
 *             'text' => __( 'Get your license', 'affiliate-wp' )
 *         ),
 *     )
 * ),
 */
return array();
