<?php
/**
 * Affiliates Grouping Meta: Rate Type
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
 * Affiliate Group: Rate Type: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Rate_Type {

	use \AffiliateWP\Utils\Data;

	/**
	 * Description of field.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_rate_type_description() : string {

		return sprintf(

			// Translators: %s is a tooltip with more information.
			__( 'Choose a referral rate type for all affiliates in this group.%s', 'affiliate-wp' ),
			affwp_icon_tooltip(
				__( 'Referrals can be based on either a percentage or a flat rate amount. This is the rate type all affiliates in this group will be assigned (regardless of their own settings or the site default).', 'affiliate-wp' ),
				'normal',
				false
			)
		);
	}

	/**
	 * Rate Type: Column Value.
	 *
	 * @since 2.13.0
	 * @since 2.14.0 This values no longer shows, see `includes/admin/affiliates/groups/meta/trait-referral-rate.php`.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return string Markup.
	 */
	public function rate_type_column_value( \AffiliateWP\Groups\Group $group ) : string {

		$rate_types = affwp_get_affiliate_rate_types();

		$settings = affiliate_wp()->settings->get_registered_settings();

		$global_setting = affiliate_wp()->settings->get(
			'referral_rate_type',
			$settings['commissions']['referral_rate_type']['std'] ?? 'percentage'
		);

		if ( 'flat' === $global_setting ) {
			$global_setting_desc = $rate_types['flat'];
		} elseif ( 'percentage' === $global_setting ) {
			$global_setting_desc = $rate_types['percentage'];
		} else {
			$global_setting_desc = __( 'Unknown', 'affiliate-wp' );
		}

		ob_start();

		?>

			<td
				class="rate-type column-rate-type"
				data-colname="<?php esc_attr_e( 'Rate Type', 'affiliate-wp' ); ?>"
				style="text-align: center; vertical-align: middle;">

				<?php

				$rate_type = $group->get_meta( 'rate-type', '' );

				foreach ( $rate_types as $type => $name ) {

					if ( $rate_type !== $type ) {
						continue;
					}

					// Show the name stored.
					echo esc_html( $name );
				}

				?>
			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Rate Type: Column Header.
	 *
	 * @since 2.13.0
	 *
	 * @param string $position Position, either top or bottom.
	 *
	 * @return string Markup.
	 */
	public function rate_type_column_header( string $position ) : string {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column rate-type num" style="width: 15%">
			<?php esc_html_e( 'Rate Type', 'affiliate-wp' ); ?>
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
	public function rate_type_edit( \AffiliateWP\Groups\Group $group ) : string {

		ob_start();

		?>

		<template x-if="data.showRateFields">

			<tr class="form-field term-name-wrap">

				<th scope="row">
					<?php esc_html_e( 'Rate Type', 'affiliate-wp' ); ?>
				</th>

				<td>
					<?php $this->rate_type_select( $group->get_meta( 'rate-type', '' ) ); ?>

					<p class="description" id="rate-type-description">
						<?php echo wp_kses( $this->get_rate_type_description(), affwp_get_tooltip_allowed_html() ); ?>
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
	public function rate_type_main() : string {

		ob_start();

		?>

		<template x-if="data.showRateFields">

			<div class="form-field term-name-wrap">

				<?php $this->rate_type_select(); ?>

				<p id="rate-description">
					<?php echo wp_kses( $this->get_rate_type_description(), affwp_get_tooltip_allowed_html() ); ?>
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
	public function rate_type_save( \AffiliateWP\Groups\Group $group ) : bool {

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

		// Unset.
		if ( empty( trim( $rate_type ) ) ) {

			// You can't have an empty rate, so make sure nothing gets saved.
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
				'rate_type_required',
				sprintf(

					// Translators: %s is Error in bold.
					__( '%s: Rate type is required.', 'affiliate-wp' ),
					sprintf(
						'<strong>%s</strong>',
						__( 'Error', 'affiliate-wp' )
					),
				)
			);

			return false; // Stop other meta from saving.
		}

		// Validate.
		if ( ! $this->string_is_one_of( $rate_type, array( 'flat', 'percentage' ) ) ) {

			// They sent a rate type, but it wasn't valid, so save the group but don't set anything else.
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
				'bad_value',
				sprintf(
					// Translators: %s is the name of the group.
					__( 'Rate type for %s must be either flat or percentage.', 'affiliate-wp' ),
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
					'rate-type' => trim( $rate_type ),
				),
			)
		);

		return true;
	}

	/**
	 * Select drop-down.
	 *
	 * @param string $rate_type Rate type.
	 *
	 * @since 2.13.0
	 *
	 * @return void
	 */
	private function rate_type_select( string $rate_type = '' ) : void {

		?>

		<fieldset class="rate-type">

			<legend class="screen-reader-text">
				<?php esc_html_e( 'Referral Rate Type', 'affiliate-wp' ); ?>
			</legend>

			<?php foreach ( affwp_get_affiliate_rate_types() as $type => $name ) : ?>

				<?php

				if ( ! $this->string_is_one_of( $type, array( 'flat', 'percentage' ) ) ) {
					continue; // We only support flat and percentage right now.
				}

				?>

				<label for="rate-type[<?php echo esc_attr( $type ); ?>]" style="display: block">

					<input
						name="rate-type"
						id="rate-type[<?php echo esc_attr( $type ); ?>]"
						type="radio"
						value="<?php echo esc_attr( $type ); ?>"
						aria-required="true"
						required
						aria-describedby="rate-type-description"
						x-on:change="data.showRateBasis = ( 'flat' === $el.value );"
						x-init="data.showRateBasis = ( 'flat' === $el.value && $el.checked );"
						<?php if ( empty( $rate_type ) && 'percentage' === $type ) : // Adding new group (default to percentage). ?>
							checked
						<?php elseif ( 'percentage' === $type && 'percentage' === $rate_type ) : // Percentage rate type. ?>
							checked
						<?php elseif ( 'flat' === $type && 'flat' === $rate_type ) : // Flat rate type. ?>
							checked
						<?php endif; ?>>

						<?php echo esc_html( $name ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<?php
	}
}
