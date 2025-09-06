<?php
/**
 * Default upgrade modal content
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
 * Return the default content used by upgrade modals.
 *
 * @return array Array with the modal contents.
 */

$upgrade_utm_medium = \AffiliateWP\Admin\Education\Non_Pro::get_utm_medium();

return array(
	'title'   => esc_html__( 'is a PRO Feature', 'affiliate-wp' ),
	'message' => '<p>' . esc_html(
		sprintf( /* translators: %s - addon name. */
			__( 'We\'re sorry, %s is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'affiliate-wp' ),
			'%name%'
		)
	) . '</p>',
	'doc'     => sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
		esc_url( affwp_utm_link( 'https://affiliatewp.com/docs/upgrade-affiliatewp-license/', $upgrade_utm_medium, 'AP - %name%' ) ),
		esc_html__( 'Already purchased?', 'affiliate-wp' )
	),
	'button'  => esc_html__( 'Upgrade to PRO', 'affiliate-wp' ),
	'url'     => affwp_admin_upgrade_link( $upgrade_utm_medium ),
	'modal'   => \AffiliateWP\Admin\Education\Non_Pro::upgrade_modal_text(),
);
