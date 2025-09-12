<?php
/**
 * Affiliates Grouping Meta: Rate
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
 * Affiliate Group: Rate: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Rate {

	use \AffiliateWP\Utils\Data;

	/**
	 * Rate Field: Description.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_rate_description() : string {

		return sprintf(

			// Translators: %s is a tooltip with more information.
			__( 'Referral rate for all affiliates in this group.%s', 'affiliate-wp' ),
			affwp_icon_tooltip(
				__( 'This is the rate all affiliates in this group will be assigned (regardless of their own settings or the site default).', 'affiliate-wp' ),
				'normal',
				false
			)
		);
	}

	/**
	 * Rate: Column Value.
	 *
	 * @since 2.13.0
	 * @since 2.14.0 This values no longer shows, see `includes/admin/affiliates/groups/meta/trait-referral-rate.php`.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return string Markup.
	 */
	public function rate_column_value( \AffiliateWP\Groups\Group $group ) : string {

		$settings = affiliate_wp()->settings->get_registered_settings();

		$global_setting = affiliate_wp()->settings->get(
			'referral_rate',
			$settings['commissions']['referral_rate']['std'] ?? 20
		);

		ob_start();

		$rate      = $group->get_meta( 'rate' );
		$rate_type = $group->get_meta( 'rate-type' );

		if ( ! is_string( $rate_type ) || ! $this->string_is_one_of( $rate_type, array( 'flat', 'percentage' ) ) ) {

			// No specific rate type, represent global.
			$rate_type = affwp_get_affiliate_rate_type();
		}

		?>

			<td
				class="rate column-rate"
				data-colname="<?php esc_attr_e( 'Referral Rate', 'affiliate-wp' ); ?>"
				style="text-align: center; vertical-align: middle;">

				<?php

				if (

					// Valid rate.
					! is_null( $rate ) &&
					! empty( trim( $rate ) ) &&
					$this->is_numeric_and_gt_zero( $rate )
				) {

					// Rate %/$/other amount.
					echo esc_html(
						sprintf(
							'%1$s%2$s',

							// Value.
							$this->rate_get_formatted_column_value_for_rate(
								$rate,
								$rate_type
							),

							// Percentage formatting (formatting can be applied with filter for all other rate types).
							'percentage' === $rate_type
								? '%'
								: ''
						)
					);
				}

				?>
			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Get the formatted column value for a rate and rate type.
	 *
	 * @since 2.13.0
	 * @since 2.27.3 Updated numbers to show as 0.20 instead of 0.2 when rates are flat.
	 *
	 * @param int    $rate      Rate value.
	 * @param string $rate_type Rate type.
	 *
	 * @return string
	 */
	private function rate_get_formatted_column_value_for_rate( $rate, string $rate_type ) : string {

		if ( 'percentage' === $rate_type ) {
			return $rate; // 10%, no formatting.
		}

		if ( 'flat' === $rate_type ) {
			return affwp_currency_filter( number_format( $rate, 2 ) ); // $10, use currency for flat.
		}

		/**
		 * Allow developers who add other rate types to set the format for it in affiliate groups.
		 *
		 * @since 2.13.0
		 *
		 * @param int    $rate      Rate value (without formatting).
		 * @param string $rate_type Rate type (other than percentage and flat).
		 */
		return apply_filters( 'affwp_affiliate_group_rate_column_value', $rate, $rate_type );
	}

	/**
	 * Rate: Column Header.
	 *
	 * @since 2.13.0
	 *
	 * @param string $position Position, either top or bottom.
	 *
	 * @return string Markup.
	 */
	public function rate_column_header( string $position ) : string {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column rate num" style="width: 10%">
			<?php esc_html_e( 'Rate', 'affiliate-wp' ); ?>
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
	public function rate_edit( \AffiliateWP\Groups\Group $group ) : string {

		$rate = $group->get_meta( 'rate', '' );

		ob_start();

		?>

		<template x-if="data.showRateFields">

			<tr class="form-field term-name-wrap">

				<th scope="row">
					<?php esc_html_e( 'Referral Rate', 'affiliate-wp' ); ?>
				</th>

				<td>
					<?php $this->rate_type_input( $rate ); ?>

					<p class="description" id="rate-description">
						<?php echo wp_kses( $this->get_rate_description(), affwp_get_tooltip_allowed_html() ); ?>
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
	public function rate_main() : string {

		ob_start();

		?>

		<template x-if="data.showRateFields">

			<div class="form-field term-name-wrap">

				<?php $this->rate_type_input(); ?>

				<p id="rate-description">
					<?php echo wp_kses( $this->get_rate_description(), affwp_get_tooltip_allowed_html() ); ?>
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
	 * @since 2.27.3 Updated to allow fractional values, see https://github.com/awesomemotive/affiliate-wp/issues/5346.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return bool If the rate was saved.
	 *
	 * @throws \Exception If you try and use this on a class that doesn't extend \AffiliateWP\Admin\Groups\Management.
	 */
	public function rate_save( \AffiliateWP\Groups\Group $group ) : bool {

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

		if ( ! $this->string_is_one_of( $rate_type, array_keys( affwp_get_affiliate_rate_types() ) ) ) {
			return true; // A valid rate type is required to continue.
		}

		$rate = filter_input( INPUT_POST, 'rate', FILTER_UNSAFE_RAW );

		if ( empty( trim( $rate ) ) || floatval( $rate <= 0 ) ) {

			// You can't have an empty rate, so make sure none of these get set.
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
				'rate_required',
				sprintf(

					// Translators: %s is Error in bold.
					__( '%s: Rate is required.', 'affiliate-wp' ),
					sprintf(
						'<strong>%s</strong>',
						__( 'Error', 'affiliate-wp' )
					),
				)
			);

			return false; // Stop any other meta from saving.
		}

		if ( ! $this->is_numeric_and_gt_zero( $rate ) ) {

			// The rate must be a greater than zero number, so make sure none of these get set.
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
				'bad_rate',
				sprintf(
					// Translators: %s is the name of the group.
					__( 'Rate for %s must be a positive value.', 'affiliate-wp' ),
					sprintf(
						'<strong>%s</strong>',
						$group->get_title()
					)
				)
			);

			$this->update_view( 'edit' );

			return false; // Stop any other meta from saving.
		}

		$group->update(
			array(
				'type' => $this->group_type,
				'meta' => array(
					'rate' => $rate,
				),
			)
		);

		return true;
	}

	/**
	 * Input
	 *
	 * @since 2.13.0
	 *
	 * @param int $rate The rate value for the input.
	 *
	 * @return void
	 */
	private function rate_type_input( $rate = '' ) : void {

		$default_rate = affiliate_wp()->settings->get( 'referral-rate', 20 );

		?>

		<input
			name="rate"
			id="rate"
			type="number"
			step="0.01"
			min="0"
			max="999999999"
			<?php if ( ! empty( $rate ) ) : ?>
				value="<?php echo esc_attr( $rate ); ?>"
			<?php else : ?>
				placeholder="<?php echo esc_attr( $default_rate ); ?>"
				value="<?php echo esc_attr( $default_rate ); ?>"
			<?php endif; ?>
			aria-required="true"
			required
			aria-describedby="rate-description"
			style="width: 75px">

		<?php
	}
}
