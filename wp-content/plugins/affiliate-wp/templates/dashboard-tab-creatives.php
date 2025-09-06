<?php
/**
 * Affiliate Creatives Template.
 *
 * This template file is used to display a list of affiliate creatives in the affiliate dashboard.
 * It sets up the query parameters for fetching creatives and then renders them based on the conditions.
 *
 * @package AffiliateWP
 * @subpackage Templates
 *
 * @since 2.16.0
 */

affiliate_wp()->creatives_view
	->set_current_page( affwp_get_current_page_number() )
	->set_query_args(
		array(
			'cat'     => (int) filter_input( INPUT_GET, 'cat', FILTER_SANITIZE_NUMBER_INT ),
			'order'   => in_array( trim( strtolower( (string) filter_input( INPUT_GET, 'order' ) ) ), array( '', 'desc' ), true )
				? 'desc'
				: 'asc',
			'orderby' => filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? 'date_updated',
			'type'    => empty( affwp_filter_creative_type_input() )
				? 'any'
				: affwp_filter_creative_type_input(),
		)
	)
	->fetch_creatives();

?>
<div id="affwp-affiliate-dashboard-creatives" class="affwp-tab-content">

	<h4><?php esc_html_e( 'Creatives', 'affiliate-wp' ); ?></h4>

	<?php if ( ! empty( affiliate_wp()->creatives_view->get_creatives() ) ) : ?>

		<?php
		/**
		 * Fires immediately before creatives in the creatives tab of the affiliate area.
		 *
		 * @since 1.0
		 */
		do_action( 'affwp_before_creatives' );

		affiliate_wp()->creatives_view->render();

		/**
		 * Fires immediately after creatives in the creatives tab of the affiliate area.
		 *
		 * @since 1.0
		 */
		do_action( 'affwp_after_creatives' );
		?>

	<?php else : ?>

		<?php

		/**
		 * Fires immediately before creatives in the creatives tab of the affiliate area when there are no results.
		 *
		 * @since 2.12.0
		 */
		do_action( 'affwp_before_creatives_no_results' );

		affiliate_wp()->creatives_view->render();
		?>

		<p class="affwp-no-results"><?php esc_html_e( 'Sorry, there are currently no creatives available.', 'affiliate-wp' ); ?></p>

		<?php

		/**
		 * Fires immediately after creatives in the creatives tab of the affiliate area when there are no results.
		 *
		 * @since 2.12.0
		 */
		do_action( 'affwp_after_creatives_no_results' );
		?>

	<?php endif; ?>

</div>
