<?php
/**
 * Admin: Delete Creative View
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

// Don't allow direct access to file.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Check if is a good request.
if (
	! isset( $_REQUEST['_wpnonce'] ) ||
	! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-creatives' ) &&
	! wp_verify_nonce( $_REQUEST['_wpnonce'], 'affwp-creative-nonce' )
) {
	return;
}

// Sanitize each item of the array (always int) while returning the creative objects.
$to_delete = array_filter(
	array_map(
		function( $creative_id ) {
			return affwp_get_creative( $creative_id );
		},
		array_filter(
			isset( $_GET['creative_id'] ) && empty( $_GET['creative_id'] ) || ! isset( $_GET['creative_id'] )
				? array()
				: (array) $_GET['creative_id'],
			function( $maybe_id ) {
				return is_numeric( $maybe_id ) && intval( $maybe_id ) >= 1;
			}
		)
	)
);

// Count the creatives found.
$creatives_count = count( $to_delete );

?>
<div class="wrap">

	<h2>
		<?php
		echo esc_html(
			_n(
				'Delete Creative',
				'Delete Creatives',
				$creatives_count,
				'affiliate-wp'
			)
		);
		?>
	</h2>

	<?php if ( $creatives_count ) : ?>
		<form method="post" id="affwp_delete_creative">

			<?php
			/**
			 * Fires at the top of the delete-creatives admin screen.
			 *
			 * @since 1.0
			 * @since 2.12.0 Added top version.
			 *
			 * @param int $to_delete The ID of the creative.
			 */
			do_action( 'affwp_delete_creative_top', $to_delete );
			?>

			<p>
				<?php
				echo esc_html(
					_n(
						'Are you sure you want to delete this creative?',
						'Are you sure you want to delete these creatives?',
						$creatives_count,
						'affiliate-wp'
					)
				);
				?>
			</p>

			<ul>
				<?php foreach ( $to_delete as $creative ) : ?>
					<li>
						<?php
						printf(
							/* translators: 1: Creative ID, 2: Creative name */
							esc_html_x(
								'Creative ID #%1$d: %2$s',
								'Creative ID, creative name',
								'affiliate-wp'
							),
							esc_html( $creative->ID ),
							esc_html( $creative->name )
						);
						?>
						<input type="hidden" name="affwp_creative_ids[]" value="<?php echo esc_attr( $creative->ID ); ?>"/>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php
			/**
			 * Fires at the bottom of the delete-creatives admin screen.
			 *
			 * @since 1.0
			 * @since 2.12.0 Added bottom version.
			 *
			 * @param int $to_delete The ID of the creative.
			 */
			do_action( 'affwp_delete_creative_bottom', $to_delete );
			?>

			<input type="hidden" name="affwp_action" value="delete_creatives" />
			<?php
				wp_nonce_field(
					'affwp_delete_creatives_nonce',
					'affwp_delete_creatives_nonce'
				);

				submit_button(
					_n(
						'Delete Creative',
						'Delete Creatives',
						$creatives_count,
						'affiliate-wp'
					)
				);
			?>
		</form>

	<?php else : ?>
		<p><?php esc_html_e( 'Nothing to delete', 'affiliate-wp' ); ?></p>
	<?php endif; ?>

</div>
