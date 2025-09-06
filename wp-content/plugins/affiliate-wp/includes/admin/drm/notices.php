<?php
/**
 * DRM Notices
 *
 * Must return an array of notices. Example:
 *
 *     array(
 *          'notice_id' => array(
 *              'message' => 'The notice content.'
 *          )
 *     )
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
 * Retrieves an array of DRM notices to be displayed.
 *
 * In order to display notices, you need to return an array with the {state}_{level}. Eg.:
 *
 * 'unlicensed_low_level' => array(
 *     'level' => 'error',
 *     'message' => __( 'Unlicensed Low Level Message', 'affiliate-wp' ),
 * ),
 *
 * The level property can be any notice level described here: https://developer.wordpress.org/reference/hooks/admin_notices/
 * Possible values: info, error, warning, success. Default: error.
 *
 * The message property can return both strings or functions that return a string.
 *
 * @return array Array of notices to be displayed.
 */

try {
	$icon_tag = \AffiliateWP\Utils\Icons::to_base64( 'exclamation-triangle' );

	if ( ! empty( $icon_tag ) ) {
		$icon_tag = sprintf(
			'<img src="%s" width="24.067" height="24" alt="%s">',
			$icon_tag,
			esc_html( __( 'Exclamation Icon', 'affiliate-wp' ) )
		);
	}
} catch ( \Exception $e ) {
	$icon_tag = '';
}

return array(
	'unlicensed_initiated' => array(
		'level'   => 'info',
		'message' => function() {
			ob_start();
			?>
			<p>
				<?php
				printf(
					/* translators: %s - Link to the settings screen */
					esc_html__(
						'Please %s your license key for AffiliateWP to enable automatic updates.',
						'affiliate-wp'
					),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( sprintf( '%s#license_key', affwp_admin_url( 'settings' ) ) ),
						esc_html__( 'enter and activate', 'affiliate-wp' )
					)
				);
				?>
			</p>
			<?php
			return ob_get_clean();
		},
	),
	'unlicensed_low_level' => array(
		'message' => function() use ( $icon_tag ) {

			ob_start();

			?>

			<h2>
				<span class="affwp-exclamation-icon">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is safe at this point.
					echo $icon_tag;
					?>
				</span>
				<?php esc_html_e( 'Did you forget something? Your AffiliateWP license is missing.', 'affiliate-wp' ); ?>
			</h2>
			<p><?php esc_html_e( 'An active license is needed to access new features & addons, plugin updates (including security improvements), and our world class support!', 'affiliate-wp' ); ?></p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_license_key_field_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Activate Now', 'affiliate-wp' ); ?></a>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'docs-locate-license' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'Learn More', 'affiliate-wp' ); ?></a>
			</p>

			<?php

			return ob_get_clean();
		},
	),
	'unlicensed_med_level' => array(
		'message' => function() use ( $icon_tag ) {

			ob_start();

			?>

			<h2>
				<span class="affwp-exclamation-icon">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is safe at this point.
					echo $icon_tag;
					?>
				</span>
				<?php esc_html_e( 'Your business is at risk! Your AffiliateWP license is missing.', 'affiliate-wp' ); ?>
			</h2>
			<p><?php esc_html_e( 'To continue using AffiliateWP without interruption, you need to enter your license key right away.', 'affiliate-wp' ); ?></p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_license_key_field_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Activate Now', 'affiliate-wp' ); ?></a>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'docs-locate-license' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'Learn More', 'affiliate-wp' ); ?></a>
			</p>

			<?php

			return ob_get_clean();
		},
	),
	'invalid_initiated'    => array(
		'message' => function() use ( $icon_tag ) {

			ob_start();

			?>

			<h2>
				<span class="affwp-exclamation-icon">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is safe at this point.
					echo $icon_tag;
					?>
				</span>
				<?php esc_html_e( 'Heads up! Your AffiliateWP license has expired.', 'affiliate-wp' ); ?>
			</h2>
			<p><?php esc_html_e( 'An active license is needed to access new features & addons, plugin updates (including security improvements), and our world class support!', 'affiliate-wp' ); ?></p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'account' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Renew Now', 'affiliate-wp' ); ?></a>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'docs-renew-license' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'Learn More', 'affiliate-wp' ); ?></a>
			</p>

			<?php

			return ob_get_clean();
		},
	),
	'invalid_med_level'    => array(
		'message' => function() use ( $icon_tag ) {

			ob_start();

			?>

			<h2>
				<span class="affwp-exclamation-icon">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is safe at this point.
					echo $icon_tag;
					?>
				</span>
				<?php esc_html_e( 'Your business is at risk! Your AffiliateWP license has expired.', 'affiliate-wp' ); ?>
			</h2>
			<p><?php esc_html_e( 'Your AffiliateWP license key is expired and is required to continue using AffiliateWP. Fortunately, it\'s easy to renew your license key.', 'affiliate-wp' ); ?></p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'account' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Activate Now', 'affiliate-wp' ); ?></a>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'docs-renew-license' ) ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'Learn More', 'affiliate-wp' ); ?></a>
			</p>

			<?php

			return ob_get_clean();
		},
	),
);
