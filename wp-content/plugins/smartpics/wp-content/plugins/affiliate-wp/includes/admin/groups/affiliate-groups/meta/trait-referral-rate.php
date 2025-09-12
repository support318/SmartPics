<?php
/**
 * Affiliates Grouping Meta: Rate
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Affiliates
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Allowing comments in function call lines.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Allowing comments in function call lines.

namespace AffiliateWP\Admin\Affiliates\Groups\Meta;

/**
 * Affiliate Group: Referral Rate
 *
 * This isn't actually meta that is input, output, or stored, but is used
 * to add a column that combines the values for rate, rate type and flat rate basis
 * into a single column.
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.14.0
 */
trait Referral_Rate {

	/**
	 * Rate: Column Value.
	 *
	 * @since 2.14.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return string Markup.
	 */
	public function referral_rate_column_value( \AffiliateWP\Groups\Group $group ) : string {

		?>

			<td
				class="referral-rate column-referral-rate"
				data-colname="<?php esc_attr_e( 'Referral Rate', 'affiliate-wp' ); ?>"
				style="text-align: center; vertical-align: middle;">

				<?php

				$referral_rate = trim(
					sprintf(
						'%1$s %2$s',
						wp_strip_all_tags( $this->rate_column_value( $group ) ),
						wp_strip_all_tags( $this->flat_rate_basis_column_value( $group ) )
					)
				);

				// This simply shows the value for rate and flat rate basis in one column.
				echo esc_html(
					( empty( $referral_rate ) )
						? '&mdash;'
						: $referral_rate
				);

				?>
			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Rate: Column Header.
	 *
	 * @since 2.14.0
	 *
	 * @param string $position Position, either top or bottom.
	 *
	 * @return string Markup.
	 */
	public function referral_rate_column_header( string $position ) : string {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column referral-rate num" style="width: 15%">
			<?php esc_html_e( 'Referral Rate', 'affiliate-wp' ); ?>
		</th>

		<?php

		return ob_get_clean();
	}
}
