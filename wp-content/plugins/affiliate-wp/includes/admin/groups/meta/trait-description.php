<?php
/**
 * Affiliates Grouping Meta: Description
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
 * Affiliate Group: Description: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Description {

	use \AffiliateWP\Utils\Data;

	/**
	 * Width for the column.
	 *
	 * @since 2.13.0
	 *
	 * @var string
	 */
	protected $description_column_width = '30%';

	/**
	 * Description Field: Description.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_description_description() : string {

		return sprintf(
			'%1$s%2$s',
			sprintf(

				// Translators: %1$s is the single title, e.g. category, group, etc.
				__( 'Describes the %1$s.', 'affiliate-wp' ),
				isset( $this->single_title )
					? strtolower( $this->single_title )
					: __( 'group', 'affiliate-wp' )
			),
			affwp_icon_tooltip(
				sprintf(

					// Translators: %1$s is an explanation of the group type.
					__( 'This description only appears here in the dashboard (admin) and can only be seen by administrators and those who can manage %1$s.', 'affiliate-wp' ),
					isset( $this->item_single, $this->plural_title )
						? sprintf(

							// Translators: %1$s is the name of the item (single) e.g. 'creative'.
							__( '%1$s %2$s', 'affiliate-wp' ),
							strtolower( $this->item_single ),
							strtolower( $this->plural_title )
						)
						: __( 'group', 'affiliate-wp' )
				),
				'normal',
				false
			)
		);
	}

	/**
	 * Description: Column Value.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return string Markup.
	 */
	public function description_column_value( \AffiliateWP\Groups\Group $group ) : string {

		ob_start();

		?>

			<td
				class="rate column-description"
				data-colname="<?php esc_attr_e( 'Description', 'affiliate-wp' ); ?>"
				style="text-align: left; vertical-align: middle;">

				<?php

				echo esc_html(
					wp_trim_words( $group->get_meta( 'description' ), 55 )
				);

				?>

			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Description: Column Header.
	 *
	 * @since 2.13.0
	 *
	 * @param string $position Position, either top or bottom.
	 *
	 * @return string Markup.
	 */
	public function description_column_header( string $position ) : string {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column description" style="width: <?php echo esc_attr( $this->description_column_width ); ?>">
			<?php esc_html_e( 'Description', 'affiliate-wp' ); ?>
		</th>

		<?php

		return ob_get_clean();
	}

	/**
	 * Description Field: Edit.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group.
	 *
	 * @return string Markup.
	 */
	public function description_edit( \AffiliateWP\Groups\Group $group ) : string {

		$description = $group->get_meta( 'description', '' );

		ob_start();

		?>

		<tr class="form-field term-name-wrap">

			<th scope="row">
				<?php esc_html_e( 'Description', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php $this->description_textarea( $description ); ?>

				<p class="description" id="description">
					<?php echo wp_kses( $this->get_description_description(), affwp_get_tooltip_allowed_html() ); ?>
				</p>
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Description Field: Main/Add.
	 *
	 * @since 2.13.0
	 *
	 * @return string Markup.
	 */
	public function description_main() : string {

		ob_start();

		?>

		<div class="form-field term-name-wrap">

			<label for="group-description">
				<?php esc_html_e( 'Description', 'affiliate-wp' ); ?>
			</label>

			<?php $this->description_textarea(); ?>

			<p id="description">
				<?php echo wp_kses( $this->get_description_description(), affwp_get_tooltip_allowed_html() ); ?>
			</p>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Description Field: Save.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return bool If the description was saved.
	 *
	 * @throws \Exception If you try and use this on a class that doesn't extend \AffiliateWP\Admin\Groups\Management.
	 */
	public function description_save( \AffiliateWP\Groups\Group $group ) : bool {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( 'This trait method can only be called on \AffiliateWP\Admin\Groups\Management object.' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Action validation happens as a part of the management class.
		$description = filter_input( INPUT_POST, 'group-description', FILTER_UNSAFE_RAW );

		// Unset description.
		if ( empty( trim( $description ) ) ) {

			$group->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						'description' => null, // Unset description.
					),
				)
			);

			return true;
		}

		$group->update(
			array(
				'type' => $this->group_type,
				'meta' => array(
					'description' => esc_html( str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', htmlentities2( $description ) ) ),
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
	 * @param int $description The description value for the input.
	 *
	 * @return void
	 */
	private function description_textarea( $description = '' ) : void {

		?>

		<textarea
			@keydown.enter.prevent
			name="group-description"
			id="group-description"
			aria-required="false"
			style="min-height: 100px;"
			aria-describedby="description"><?php echo esc_textarea( $description ); ?></textarea>

		<?php
	}
}
