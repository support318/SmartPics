<?php
/**
 * Affiliates Grouping Meta: Flat Rate Basis
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
// phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorrect -- You are not calculating this right.

namespace AffiliateWP\Admin\Affiliates\Groups\Meta;

affwp_require_util_traits( 'data' );

/**
 * Affiliate Group: Flat Rate Basis: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Flat_Rate_Basis {

	use \AffiliateWP\Utils\Data;

	/**
	 * Description of field.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_flat_rate_basis_description() : string {

		return sprintf(

			// Translators: %s is a tooltip with more information.
			__( 'Choose the flat rate basis for all affiliates in this group.%s', 'affiliate-wp' ),
			affwp_icon_tooltip(
				__( 'Flat rate referrals can be calculated on a per product or per order basis. This is the flat rate basis all affiliates in this group will be assigned (regardless of their own settings or the site default).', 'affiliate-wp' ),
				'normal',
				false
			)
		);
	}

	/**
	 * Flat Rate Basis: Column Value.
	 *
	 * @since 2.13.0
	 * @since 2.14.0 This values no longer shows, see `includes/admin/affiliates/groups/meta/trait-referral-rate.php`.
	 * @since 2.27.3 Removed some unnecessary code, see https://github.com/awesomemotive/affiliate-wp/pull/5351/files.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return string Markup.
	 */
	public function flat_rate_basis_column_value( \AffiliateWP\Groups\Group $group ) : string {

		static $rate_types       = null;
		static $flat_rate_types  = null;
		static $global_rate_type = null;
		static $global_setting   = null;
		static $settings         = null;

		$settings = is_null( $settings )
			? affiliate_wp()->settings->get_registered_settings()
			: $settings;

		$global_rate_type = is_null( $global_rate_type )
			? affiliate_wp()->settings->get(
				'referral_rate_type',
				$settings['commissions']['referral_rate_type']['std'] ?? 'percentage'
			)
			: $global_rate_type;

		$rate_types = is_null( $rate_types )
			? affwp_get_affiliate_rate_types()
			: $rate_types;

		$flat_rate_types = is_null( $flat_rate_types )
			? affwp_get_affiliate_flat_rate_basis_types()
			: $flat_rate_types;

		// For defaults.
		$global_setting = is_null( $global_setting )
			? affiliate_wp()->settings->get(
				'flat_rate_basis',
				$settings['commissions']['flat_rate_basis']['std'] ?? 'percentage'
			)
			: $global_setting;

		$group_flat_rate_basis = $group->get_meta( 'flat-rate-basis', '' );

		ob_start();

		?>

			<td
				class="flat-rate-basis column-flat-rate-basis"
				data-colname="<?php esc_attr_e( 'Flat Rate Basis', 'affiliate-wp' ); ?>"
				style="text-align: center; vertical-align: middle;">

				<?php

				// Percentage setting...
				if ( 'flat' === $group->get_meta( 'rate-type', '' ) ) {

					// Rate type.
					if ( 'per-product' === $group_flat_rate_basis ) {
						esc_html_e( 'Per Product', 'affiliate-wp' );
					} elseif ( 'per-order' === $group_flat_rate_basis ) {
						esc_html_e( 'Per Order', 'affiliate-wp' );
					}
				}

				?>
			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Flat Rate Basis: Column Header.
	 *
	 * @since 2.13.0
	 *
	 * @param string $position Position, either top or bottom.
	 *
	 * @return string Markup.
	 */
	public function flat_rate_basis_column_header( string $position ) : string {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column flat-rate-basis num" style="width: 20%">
			<?php esc_html_e( 'Flat Rate Basis', 'affiliate-wp' ); ?>
		</th>

		<?php

		return ob_get_clean();
	}

	/**
	 * Rate Field: Edit.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group.
	 *
	 * @return string Markup.
	 */
	public function flat_rate_basis_edit( \AffiliateWP\Groups\Group $group ) : string {

		ob_start();

		?>

		<template x-if="data.showRateFields && data.showRateBasis">

			<tr class="form-field term-name-wrap">

				<th scope="row">
					<?php esc_html_e( 'Flat Rate Basis', 'affiliate-wp' ); ?>
				</th>

				<td>
					<?php $this->flat_rate_basis_select( $group->get_meta( 'flat-rate-basis', '' ) ); ?>

					<p class="description" id="flat-rate-basis-description">
						<?php echo wp_kses( $this->get_flat_rate_basis_description(), affwp_get_tooltip_allowed_html() ); ?>
					</p>
				</td>
			</tr>

		</template>

		<?php

		return ob_get_clean();
	}

	/**
	 * Rate Field: Main/Add.
	 *
	 * @since 2.13.0
	 *
	 * @return string Markup.
	 */
	public function flat_rate_basis_main() : string {

		ob_start();

		?>

		<template x-if="data.showRateFields && data.showRateBasis">

			<div class="form-field term-name-wrap">

				<?php $this->flat_rate_basis_select(); ?>

				<p id="flat-rate-description" style="word-wrap: normal;">
					<?php echo wp_kses( $this->get_flat_rate_basis_description(), affwp_get_tooltip_allowed_html() ); ?>
				</p>
			</div>

		</template>

		<?php

		return ob_get_clean();
	}

	/**
	 * Rate Field: Save.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return bool If the rate was saved.
	 *
	 * @throws \Exception If you try and use this on a class that doesn't extend \AffiliateWP\Admin\Groups\Management.
	 */
	public function flat_rate_basis_save( \AffiliateWP\Groups\Group $group ) : bool {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( 'This trait method can only be called on \AffiliateWP\Admin\Groups\Management object.' );
		}

		if ( 'on' !== filter_input( INPUT_POST, 'custom-rate', FILTER_UNSAFE_RAW ) ) {

			// They turned off the rate type preference, unset all the values.
			$group->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						'rate'            => null,
						'rate-type'       => null,
						'flat-rate-basis' => null,
					),
				)
			);

			return true; // They are not setting a custom rate, bail saving this.
		}

		$rate_type = filter_input( INPUT_POST, 'rate-type', FILTER_UNSAFE_RAW );

		if ( 'flat' !== trim( $rate_type ) ) {

			// Unset flat rate basis since the rate type changed FROM flat.
			$group->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						'flat-rate-basis' => null,
					),
				)
			);

			return true; // They must have switched the type, allow saving of that.
		}

		$flate_rate_basis = filter_input( INPUT_POST, 'flat-rate-basis', FILTER_UNSAFE_RAW );

		if ( empty( trim( $flate_rate_basis ) ) ) {

			// Unset group rate type and flat rate basis since something went wrong here.
			$group->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						'rate'            => null,
						'rate-type'       => null,
						'flat-rate-basis' => null,
					),
				)
			);

			$this->add_error(
				'flat_rate_basis_required',
				sprintf(

					// Translators: %s is Error in bold.
					__( '%s: Flat rate basis is required.', 'affiliate-wp' ),
					sprintf(
						'<strong>%s</strong>',
						__( 'Error', 'affiliate-wp' )
					),
				)
			);

			return false; // Stop other meta from saving.
		}

		// Validate.
		if ( ! $this->string_is_one_of( $flate_rate_basis, array( 'per-product', 'per-order' ) ) ) {

			// Unset group rate type and flat rate basis since it's flat but you have no flat rate basis set.
			$group->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						'rate'            => null,
						'rate-type'       => null,
						'flat-rate-basis' => null,
					),
				)
			);

			$this->errors->add(
				'bad_value',
				sprintf(
					// Translators: %s is the name of the group.
					__( 'Flat rate basis type for %s must be either per-product or per-order.', 'affiliate-wp' ),
					sprintf(
						'<strong>%s</strong>',
						$group->get_title()
					)
				)
			);

			return false; // Stop other meta from saving.
		}

		$group->update(
			array(
				'type' => $this->group_type,
				'meta' => array(
					'rate-type'       => 'flat', // Yes, force this anyways so it's not changed.
					'flat-rate-basis' => trim( $flate_rate_basis ),
				),
			)
		);

		return true;
	}

	/**
	 * Select dropdown.
	 *
	 * @param string $flat_rate_basis Flat Rate Basis.
	 *
	 * @since 2.13.0
	 *
	 * @return void
	 */
	private function flat_rate_basis_select( $flat_rate_basis = '' ) : void {
		?>

		<fieldset class="flat-rate-basis">

			<legend class="screen-reader-text">
				<?php esc_html_e( 'Flat Rate Basis', 'affiliate-wp' ); ?>
			</legend>

			<?php foreach ( affwp_get_affiliate_flat_rate_basis_types() as $type => $name ) : ?>

				<?php

				if ( ! $this->string_is_one_of( $type, array( 'per_order', 'per_product' ) ) ) {
					continue; // We only support per_order and per_product right now.
				}

				// We use - e.g. per-order format in meta vs per_order.
				$type = str_replace( '_', '-', esc_attr( $type ) );

				?>

				<label for="flat-rate-basis[<?php echo esc_attr( $type ); ?>]" style="display: block">

					<input
						id="flat-rate-basis[<?php echo esc_attr( $type ); ?>]"
						name="flat-rate-basis"
						type="radio"
						value="<?php echo esc_attr( $type ); ?>"
						aria-required="true"
						required
						aria-describedby="flat-rate-basis-description"
						<?php if ( empty( $flat_rate_basis ) && 'per-product' === $type ) : // Adding new group (default to per-product). ?>
							checked
						<?php elseif ( 'per-product' === $type && 'per-product' === $flat_rate_basis ) : // {er-product FRB. ?>
							checked
						<?php elseif ( 'per-order' === $type && 'per-order' === $flat_rate_basis ) : // Per-order FRB. ?>
							checked
						<?php endif; ?>>

						<?php echo esc_html( $name ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>


		<?php
	}
}
