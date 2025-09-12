<?php
/**
 * DRM Emails
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
 * Retrieves an array of DRM emails.
 *
 * In order to register emails, you need to return an array with the {state}_{level}. Eg.:
 *
 * 'unlicensed_low_level' => array(
 *     'subject' => 'The email subject',
 *     'message' => __( 'Some email content', 'affiliate-wp' ),
 * ),
 *
 * The message property can return both strings or functions that return a string.
 *
 * @return array Array of email contents.
 */
return array(
	'unlicensed_low_level' => array(
		'subject' => __( 'It looks like your AffiliateWP license key is missing', 'affiliate-wp' ),
		'message' => function() {

			ob_start();

			?>

			<h2><?php esc_html_e( 'Did You Forget Something?', 'affiliate-wp' ); ?></h2>
			<p><?php esc_html_e( 'Oops! It looks like your AffiliateWP license key is missing. Here\'s how to fix the problem fast and easy:', 'affiliate-wp' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Grab your key from your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'account', 'email' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a></li>
				<li><a href="<?php echo esc_url( affwp_admin_url( 'settings' ) ); ?>"><?php esc_html_e( 'Click here', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'to enter and activate it', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'That\'s it!', 'affiliate-wp' ); ?></li>
			</ol>
			<p><a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'support', 'email' ) ); ?>"><?php esc_html_e( 'Let us know', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'if you need assistance.', 'affiliate-wp' ); ?></p>

			<?php

			return ob_get_clean();
		},
	),
	'unlicensed_med_level' => array(
		'subject' => __( 'WARNING! Your Business is at Risk', 'affiliate-wp' ),
		'message' => function() {

			ob_start();

			?>

			<h2><?php esc_html_e( 'WARNING! Your Business is at Risk', 'affiliate-wp' ); ?></h2>

			<p><?php esc_html_e( 'To continue using AffiliateWP without interruption, you need to enter your license key right away. Here\'s how:', 'affiliate-wp' ); ?></p>

			<ol>
				<li><?php esc_html_e( 'Grab your key from your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'account', 'email' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a></li>
				<li><a href="<?php echo esc_url( affwp_admin_url( 'settings' ) ); ?>"><?php esc_html_e( 'Click here', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'to enter and activate it', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'That\'s it!', 'affiliate-wp' ); ?></li>
			</ol>

			<p><a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'support', 'email' ) ); ?>"><?php esc_html_e( 'Let us know', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'if you need assistance.', 'affiliate-wp' ); ?></p>

			<?php

			return ob_get_clean();
		},
	),
	'unlicensed_locked'    => array(
		'subject' => __( 'ALERT! AffiliateWP Backend is Deactivated', 'affiliate-wp' ),
		'message' => function() {

			ob_start();

			?>

			<h2><?php esc_html_e( 'ALERT! AffiliateWP Backend is Deactivated', 'affiliate-wp' ); ?></h2>
			<p><?php esc_html_e( 'Without an active license key, AffiliateWP cannot be managed on the backend. Your front-end website will remain intact, but you can’t:', 'affiliate-wp' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Disburse affiliate payouts', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'Register &amp; manage affiliates', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'Analyze performance data', 'affiliate-wp' ); ?></li>
			</ul>
			<p><?php esc_html_e( 'Fortunately, this problem is easy to fix by doing the following:', 'affiliate-wp' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Grab your key from your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'account', 'email' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a></li>
				<li><a href="<?php echo esc_url( affwp_admin_url( 'settings' ) ); ?>"><?php esc_html_e( 'Click here', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'to enter and activate it', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'That\'s it!', 'affiliate-wp' ); ?></li>
			</ol>
			<p><a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'support', 'email' ) ); ?>"><?php esc_html_e( 'Let us know', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'if you need assistance.', 'affiliate-wp' ); ?></p>

			<?php

			return ob_get_clean();
		},
	),
	'invalid_med_level'    => array(
		'subject' => __( 'WARNING! Your Business is at Risk', 'affiliate-wp' ),
		'message' => function() {

			ob_start();

			?>

			<h2><?php esc_html_e( 'WARNING! Your Business is at Risk', 'affiliate-wp' ); ?></h2>
			<p><?php esc_html_e( 'Your AffiliateWP license key is expired, but is required to continue using AffiliateWP. Fortunately, it\'s easy to renew your license key. Just do the following:', 'affiliate-wp' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Grab your key from your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'account', 'email' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a></li>
				<li><a href="<?php echo esc_url( affwp_admin_url( 'settings' ) ); ?>"><?php esc_html_e( 'Click here', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'to enter and activate it', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'That\'s it!', 'affiliate-wp' ); ?></li>
			</ol>
			<p><a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'support', 'email' ) ); ?>"><?php esc_html_e( 'Let us know', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'if you need assistance.', 'affiliate-wp' ); ?></p>

			<?php

			return ob_get_clean();
		},
	),
	'invalid_locked'       => array(
		'subject' => __( 'ALERT! AffiliateWP Backend is Deactivated', 'affiliate-wp' ),
		'message' => function() {

			ob_start();

			?>

			<h2><?php esc_html_e( 'ALERT! AffiliateWP Backend is Deactivated', 'affiliate-wp' ); ?></h2>
			<p><?php esc_html_e( 'Without an active license key, AffiliateWP cannot be managed on the backend. Your frontend will remain intact, but you can’t:', 'affiliate-wp' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Disburse affiliate payouts', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'Register &amp; manage affiliates', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'Analyze performance data', 'affiliate-wp' ); ?></li>
			</ul>
			<p><?php esc_html_e( 'Fortunately, this problem is easy to fix by doing the following:', 'affiliate-wp' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Grab your key from your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'account', 'email' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a></li>
				<li><a href="<?php echo esc_url( affwp_admin_url( 'settings' ) ); ?>"><?php esc_html_e( 'Click here', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'to enter and activate it', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'That\'s it!', 'affiliate-wp' ); ?></li>
			</ol>
			<p><a href="<?php echo esc_url( affiliate_wp()->drm->get_utm_link( 'support', 'email' ) ); ?>"><?php esc_html_e( 'Let us know', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'if you need assistance.', 'affiliate-wp' ); ?></p>

			<?php

			return ob_get_clean();
		},
	),
);
