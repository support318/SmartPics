<?php
$affiliate_id     = affwp_get_affiliate_id();
?>
<div id="affwp-affiliate-dashboard-url-generator" class="affwp-tab-content">
	<?php
	/**
	 * Fires at the top of the Affiliate URLs dashboard tab.
	 *
	 * @since 2.0.5
	 *
	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_urls_top', $affiliate_id );

	affiliate_wp()->affiliate_links->render_affiliate_link( $affiliate_id );

	/**
	 * Fires just before the Custom Link Generator.
	 *
	 * @since 2.0.5
	 *
	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_urls_before_generator', $affiliate_id );

	/**
	 * Render the Custom Links Generator.
	 *
	 * @since 2.14.0
	 *
	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_render_custom_link_generator', $affiliate_id );

	/**
	 * Fires at the bottom of the Affiliate URLs dashboard tab.
	 *
	 * @since 2.0.5
	 *
	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_urls_bottom', $affiliate_id );
	?>

</div>
