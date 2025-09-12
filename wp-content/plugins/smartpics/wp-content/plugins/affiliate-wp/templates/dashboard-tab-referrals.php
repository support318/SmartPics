<?php
/**
 * The template file for affiliate dashboard referrals
 *
 * This script retrieves the Affiliate ID, then fetches referrals associated with this ID, and displays them in a table.
 *
 * @var int               $affiliate_id                  The ID of the affiliate.
 * @var int               $referrals_per_page            Number of entries to display per page.
 * @var array             $statuses                      Array of status(es) of referrals i.e. 'paid', 'unpaid', 'rejected'.
 * @var int               $current_page                  The current page number.
 * @var int               $total_pages                   Calculation of total pages based on the count of referrals and per page limit.
 * @var bool              $load_conversion_notes_scripts To conditionally load the tooltip scripts for showing conversion notes
 * @var \AffWP\Referral[] $referrals                     Array of Referral Objects fetched using affiliate_wp()->referrals->get_referrals().
 *
 * There are several action hooks in this template:
 *  - 'affwp_referrals_dashboard_before_table'
 *  - 'affwp_referrals_dashboard_th'
 *  - 'affwp_referrals_dashboard_td'
 *  - 'affwp_referrals_dashboard_after_table'
 *
 * Each referral information is displayed in a table row, with details like Referral Reference, Amount, Description, Status and Date.
 * If there are conversion notes available for a referral, these are shown with a tooltip.
 * If no referral data is available, it displays 'You have not made any referrals yet.'
 *
 * @package AffiliateWP
 * @subpackage Templates
 * @version 1.0
 */

use AffiliateWP\Utils\Icons;

$affiliate_id                  = affwp_get_affiliate_id();
$referrals_per_page            = 30;
$statuses                      = [ 'paid', 'unpaid', 'rejected' ];
$current_page                  = affwp_get_current_page_number();
$total_pages                   = absint( ceil( affwp_count_referrals( $affiliate_id, $statuses ) / $referrals_per_page ) );
$referrals                     = affiliate_wp()->referrals->get_referrals(
	[
		'number'       => $referrals_per_page,
		'offset'       => $referrals_per_page * ( $current_page - 1 ),
		'affiliate_id' => $affiliate_id,
		'status'       => $statuses,
	]
);
?>

<div id="affwp-affiliate-dashboard-referrals" class="affwp-tab-content">

	<h4><?php esc_html_e( 'Referrals', 'affiliate-wp' ); ?></h4>

	<?php
	/**
	 * Fires before the referrals dashboard data table within the referrals template.
	 *
	 * @since 1.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	do_action( 'affwp_referrals_dashboard_before_table', $affiliate_id );
	?>

	<table id="affwp-affiliate-dashboard-referrals" class="affwp-table affwp-table-responsive">
		<thead>
			<tr>
				<th class="referral-amount"><?php esc_html_e( 'Reference', 'affiliate-wp' ); ?></th>
				<th class="referral-amount"><?php esc_html_e( 'Amount', 'affiliate-wp' ); ?></th>
				<th class="referral-description"><?php esc_html_e( 'Description', 'affiliate-wp' ); ?></th>
				<th class="referral-status"><?php esc_html_e( 'Status', 'affiliate-wp' ); ?></th>
				<th class="referral-date"><?php esc_html_e( 'Date', 'affiliate-wp' ); ?></th>
				<?php
				/**
				 * Fires in the dashboard referrals template, within the table header element.
				 *
				 * @since 1.0
				 */
				do_action( 'affwp_referrals_dashboard_th' );
				?>
			</tr>
		</thead>

		<tbody>
			<?php if ( $referrals ) : ?>

				<?php foreach ( $referrals as $referral ) : ?>
					<tr>
						<td class="referral-reference" data-th="<?php esc_html_e( 'Reference', 'affiliate-wp' ); ?>"><?php echo esc_html( $referral->reference() ); ?></td>
						<td class="referral-amount" data-th="<?php esc_html_e( 'Amount', 'affiliate-wp' ); ?>">
							<?php echo esc_html( affwp_currency_filter( affwp_format_amount( $referral->amount ) ) ); ?>
						</td>
						<td class="referral-description" data-th="<?php esc_html_e( 'Description', 'affiliate-wp' ); ?>"><?php echo wp_kses_post( nl2br( $referral->description ) ); ?></td>
						<td class="referral-status <?php echo esc_attr( $referral->status ); ?>" data-th="<?php esc_html_e( 'Status', 'affiliate-wp' ); ?>"><?php echo esc_html( affwp_get_referral_status_label( $referral ) ); ?></td>
						<td class="referral-date" data-th="<?php esc_html_e( 'Date', 'affiliate-wp' ); ?>"><?php echo esc_html( $referral->date_i18n( 'datetime' ) ); ?></td>
						<?php

						/**
						 * Fires within the table data of the dashboard referrals template.
						 *
						 * @since 1.0
						 *
						 * @param \AffWP\Referral $referral Referral object.
						 */
						do_action( 'affwp_referrals_dashboard_td', $referral );

						?>
					</tr>
				<?php endforeach; ?>

			<?php else : ?>

				<tr>
					<td class="affwp-table-no-data" colspan="5"><?php esc_html_e( 'You have not made any referrals yet.', 'affiliate-wp' ); ?></td>
				</tr>

			<?php endif; ?>
		</tbody>
	</table>

	<?php
	/**
	 * Fires after the data table within the affiliate area referrals template.
	 *
	 * @since 1.0
	 *
	 * @param int $affiliate_id Affiliate ID.
	 */
	do_action( 'affwp_referrals_dashboard_after_table', $affiliate_id );
	?>

	<?php if ( $total_pages > 1 ) : ?>

		<p class="affwp-pagination">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The function already escapes the content.
			echo paginate_links(
				[
					'current'      => $current_page,
					'total'        => $total_pages,
					'add_fragment' => '#affwp-affiliate-dashboard-referrals',
					'add_args'     => [
						'tab' => 'referrals',
					],
				]
			);
			?>
		</p>

	<?php endif; ?>

</div>
