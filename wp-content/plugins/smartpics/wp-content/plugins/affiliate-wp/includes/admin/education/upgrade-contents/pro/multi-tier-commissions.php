<?php
/**
 * Multi-Tier Commissions addon upgrade modal content
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Education
 * @copyright   Copyright (c) 2024, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.23.2
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the content used by the MTC upgrade modal.
 *
 * @return array Array with the modal contents.
 */

$upgrade_utm_medium = \AffiliateWP\Admin\Education\Non_Pro::get_utm_medium();

return array(
	'title'   => esc_html__( 'Multi-Tier Commissions is a PRO Feature', 'affiliate-wp' ),
	'message' => function() {

		ob_start();

		?>
		<p>
			<?php esc_html_e( 'Reward your affiliates for their direct sales and the activity of their network, enhancing your affiliate program\'s reach.', 'affiliate-wp' ); ?>
		</p>
		<ul class="affwp-feature-benefits">
			<li><?php esc_html_e( 'Unlock new revenue streams as your affiliate network grows and sales volumes increase.', 'affiliate-wp' ); ?></li>
			<li><?php esc_html_e( 'Enable affiliates to maximize their earnings through a cascading commission structure.', 'affiliate-wp' ); ?></li>
			<li><?php esc_html_e( 'Foster a proactive affiliate community driven to expand and strengthen your market presence.', 'affiliate-wp' ); ?></li>
		</ul>
		<?php

		return ob_get_clean();
	},
	'doc'     => sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
		esc_url( affwp_utm_link( 'https://affiliatewp.com/docs/upgrade-affiliatewp-license/', $upgrade_utm_medium, 'AP - %name%' ) ),
		esc_html__( 'Already purchased?', 'affiliate-wp' )
	),
	'button'  => esc_html__( 'Upgrade to PRO', 'affiliate-wp' ),
	'url'     => affwp_admin_upgrade_link( $upgrade_utm_medium ),
	'modal'   => \AffiliateWP\Admin\Education\Non_Pro::upgrade_modal_text(),
);
