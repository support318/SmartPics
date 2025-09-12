<?php
/**
 * Affiliates Grouping Meta: Custom Rate
 *
 * Used for UI purposes only, no meta is stored.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Affiliates
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Allowing comments in function call lines.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Allowing comments in function call lines.

namespace AffiliateWP\Admin\Affiliates\Groups\Meta;

affwp_require_util_traits( 'data' );

/**
 * Affiliate Group: Custom Rate: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Custom_Rate {

	use \AffiliateWP\Utils\Data;

	/**
	 * Custom Rate Field: Description.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_custom_rate_description() : string {
		return __( 'Enable custom rate for all affiliates in this group.', 'affiliate-wp' );
	}

	/**
	 * Custom Rate Field: Edit.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group.
	 *
	 * @return string Markup.
	 */
	public function custom_rate_edit( \AffiliateWP\Groups\Group $group ) : string {

		ob_start();

		?>

		<tr class="form-field term-name-wrap">

			<th scope="row">
				<!-- <?php esc_html_e( 'Custom Rate', 'affiliate-wp' ); ?> -->
			</th>

			<td>
				<p>
					<label for="custom-rate">
						<?php $this->custom_rate_input( $group ); ?>&nbsp;<?php echo esc_html( $this->get_custom_rate_description() ); ?>
					</label>
				</p>
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Custom Rate Field: Main/Add.
	 *
	 * @since 2.13.0
	 *
	 * @return string Markup.
	 */
	public function custom_rate_main() : string {

		ob_start();

		?>

		<div class="form-field term-name-wrap">
			<p>
				<label for="custom-rate" id="custom-rate-description">
					<?php $this->custom_rate_input(); ?>&nbsp;<?php echo esc_html( $this->get_custom_rate_description() ); ?>
				</label>
			</p>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Does this have a custom rate?
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group|false $group Group object, false if new group.
	 *
	 * @return bool
	 */
	private function custom_rate_is_enabled( $group ) : bool {

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			return false;
		}

		return is_numeric( $group->get_meta( 'rate', false ) );
	}

	/**
	 * Input
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group|false $group Group object, false if new group.
	 *
	 * @return void
	 */
	private function custom_rate_input( $group = false ) : void {

		?>

		<input
			type="checkbox"
			name="custom-rate"
			id="custom-rate"
			<?php if ( $this->custom_rate_is_enabled( $group ) ) : ?>
				checked="checked"
			<?php endif; ?>
			x-init="data.showRateFields = $el.checked"
			x-on:change="data.showRateFields = $el.checked"
			aria-required="false"
			aria-describedby="custom-rate-description"><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- We want to eliminate the tabs from showing as an extra space.
	}
}
