<?php
/**
 * Admin: Edit Creative View
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

$creative   = affwp_get_creative( absint( $_GET['creative_id'] ?? 0 ) );
$is_private = 'private' === get_option( 'affwp_creative_name_privacy', '' ) && $creative->is_before_migration_time( 'date_updated' );

// QR Code meta data.
$qrcode_settings = $creative->get_qrcode_settings();

?>
<div class="wrap">

	<h2><?php esc_html_e( 'Edit Creative', 'affiliate-wp' ); ?></h2>

	<form method="post" id="affwp_edit_creative">

		<?php
		/**
		 * Fires at the top of the edit-creative admin screen.
		 *
		 * @since 1.0
		 *
		 * @param \AffWP\Creative $creative The creative object.
		 */
		do_action( 'affwp_edit_creative_top', $creative ); ?>

		<table class="form-table" data-current-context="<?php echo esc_attr( $creative->get_type() ); ?>">

			<tr class="form-row form-required" data-row="name">

				<th scope="row">
					<label for="name"><?php esc_html_e( 'Name', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<div class="affwp-creative-name-field">
						<input
							type="text"
							name="name"
							id="name"
							required
							value="<?php echo esc_attr( stripslashes( $creative->name ) ); ?>"
							class="regular-text"
							data-private="<?php echo $is_private ? 'yes' : 'no'; ?>"
						>
						<?php echo $is_private ? sprintf(
							'<span class="affwp-admin-creative-name-warning">%s</span>',
							affwp_icon_tooltip(
								esc_html__( 'Enter a more descriptive name for this creative. The Notes field below contains the original name for your reference.', 'affiliate-wp' ),
								'warning',
								false
							)
						) : ''; ?>
					</div>

					<p class="description"><?php esc_html_e( 'The name of this creative. Use this to briefly describe the creative to your affiliates.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<?php

			/**
			 * Fires before description.
			 *
			 * @since 2.12.0
			 *
			 * @param \AffWP\Creative $creative The creative object.
			 */
			do_action( 'affwp_edit_before_description', $creative );

			?>

			<tr class="form-row form-required" data-row="description">

				<th scope="row">
					<label for="description"><?php esc_html_e( 'Description', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<textarea name="description" rows="5" cols="50" id="description" class="large-text"><?php echo esc_html( $creative->description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'An optional description for this creative. Use this to provide additional information about the creative to your affiliates.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required" data-row="type">
				<th scope="row">
					<label for="type"><?php esc_html_e( 'Type', 'affiliate-wp' ); ?></label>
				</th>
				<td>
					<select name="type" id="type" class="affwp-use-select2">
						<?php foreach ( affwp_get_creative_types() as $creative_type => $label ) : ?>

							<?php
							$show_education = 'qr_code' === $creative_type && ! affwp_can_access_pro_features();
							$education_name = $show_education
								? esc_html__( 'QR Code Creatives', 'affiliate-wp' )
								: '';
							$education_utm  = $show_education
								? esc_html__( 'qr-code-creative-type', 'affiliate-wp' )
								: '';
							?>

							<option
								value="<?php echo esc_attr( $creative_type ); ?>"
								<?php selected( $creative->get_type(), $creative_type ); ?>
								class="<?php echo esc_attr( affiliatewp_get_pro_feature_option_classes( 'qr_code', $creative_type, $creative->get_type() ) ); ?>"
								<?php

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
								echo affiliatewp_tag_attr( 'data-name', $education_name );

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
								echo affiliatewp_tag_attr( 'data-utm-content', $education_utm );

								?>
							>
								<?php echo esc_html( $label ); ?>
							</option>

						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the type of the creative.', 'affiliate-wp' ); ?></p>
				</td>
			</tr>

			<tr class="form-row form-required" data-row="image">

				<th scope="row">
					<label for="image"><?php esc_html_e( 'Image', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input
						id="image"
						name="image"
						type="text"
						class="upload_field regular-text"
						value="<?php echo esc_attr( $creative->image ); ?>"
						<?php echo esc_html( $creative->get_type() === 'image' ? 'required' : '' ); ?>
					>
					<input class="upload_image_button button-secondary" type="button" value="Choose Image" />
					<p class="description"><?php esc_html_e( 'Select your image. You can also enter an image URL if your image is hosted elsewhere.', 'affiliate-wp' ); ?></p>

					<?php if ( ! empty( $creative->image ) ) { ?>
						<div id="preview_image">
							<img src="<?php echo esc_attr( $creative->image ); ?>" />
						</div>
					<?php } else { ?>
						<div id="preview_image" style="display: none;">

						</div>
					<?php } ?>

				</td>

			</tr>

			<tr class="form-row form-required" data-row="text">

				<th scope="row">
					<label for="text" data-context="text_link"><?php esc_html_e( 'Text', 'affiliate-wp' ); ?></label>
					<label for="text" data-context="image"><?php esc_html_e( 'Alt Text', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input
						type="text"
						name="text"
						id="text"
						value="<?php echo esc_attr( stripslashes( $creative->text ) ); ?>"
						class="regular-text" maxlength="255"
						<?php echo esc_html( $creative->get_type() === 'text_link' ? 'required' : '' ); ?>
					>
					<p class="description" data-context="text_link"><?php esc_html_e( 'Text for this creative.', 'affiliate-wp' ); ?></p>
					<p class="description" data-context="image"><?php esc_html_e( "Enter descriptive text for the image's alternative text (alt text).", 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row form-required" data-row="url">

				<th scope="row">
					<label for="url"><?php esc_html_e( 'URL', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input
						type="text"
						name="url"
						id="url"
						value="<?php echo esc_url_raw( $creative->url ); ?>"
						class="regular-text"
						<?php echo $creative->get_type() === 'qr_code' ? 'disabled="disabled"' : ''; ?>
					>
					<p class="description"><?php esc_html_e( 'The URL this creative should link to. Based on your Referral Settings, the affiliate&#8217;s ID or username will be automatically appended.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row" data-row="qr_preview">

				<th scope="row">
					<label for="url"><?php esc_html_e( 'QR Code', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<?php if ( ! affwp_can_access_pro_features() ) : ?>

						<p class="qrcode-inline-notice description">
							<?php esc_html_e( 'QR Code creatives allow for seamless, scannable promotions, ensuring your affiliates capture mobile audiences effectively. While your current QR Code still works for your affiliates, editing and customization are exclusive to the Pro plan.', 'affiliate-wp' ); ?>
						</p>
						<?php if ( affwp_is_upgrade_required( 'pro' ) !== true ) : ?>

							<p class="qrcode-inline-notice-cta description" >
								<a href="<?php echo esc_url_raw( affwp_admin_url( 'settings' ) ); ?>" target="_blank" rel="noopener noreferrer">
									<strong><?php esc_html_e( 'Enter Your License Key Now to Unlock and Customize Your QR Code Campaigns', 'affiliate-wp' ); ?></strong>
								</a>
							</p>

						<?php else : ?>

							<p class="qrcode-inline-notice-cta description">
								<a href="<?php echo esc_url_raw( affwp_admin_upgrade_link( 'affiliatewp-creatives-edit', 'Upgrade to AffiliateWP Pro' ) ); ?>" target="_blank" rel="noopener noreferrer">
									<strong><?php esc_html_e( 'Upgrade to Pro Now to Unlock and Customize Your QR Code Campaigns', 'affiliate-wp' ); ?></strong>
								</a>
							</p>

						<?php endif; ?>

					<?php endif; ?>

					<div id="qr-code">

						<p><strong><?php esc_html_e( 'Preview', 'affiliate-wp' ); ?></strong></p>
						<div id="qr-preview" data-settings="<?php echo esc_attr( wp_json_encode( $qrcode_settings ) ); ?>"></div>

					</div>

					<div>

						<label for="qrcode-url"><strong><?php esc_html_e( 'URL', 'affiliate-wp' ); ?></strong></label>
						<div class="affwp-education-modal-input-wrapper">
							<input
								type="text"
								name="url"
								id="qrcode-url"
								placeholder="<?php echo esc_url_raw( home_url() ); ?>"
								value="<?php echo esc_url_raw( $creative->url ); ?>"
								class="regular-text affwp-qrcode-url-field"
								<?php echo $creative->get_type() === 'qr_code' && affwp_can_access_pro_features() ? '' : 'disabled="disabled"'; ?>
							>
							<span
								<?php echo $creative->get_type() === 'qr_code' && affwp_can_access_pro_features() ? '' : ' class="affwp-education-modal"'; ?>
								data-name="<?php esc_html_e( 'QR Code Creatives', 'affiliate-wp' ); ?>"
								data-utm-content="<?php esc_html_e( 'QR Code Creative Type', 'affiliate-wp' ); ?>"
							></span>
						</div>
						<p class="description"><?php esc_html_e( 'The URL the QR code should link to. Based on your Referral Settings, the affiliate&#8217;s ID or username will be automatically appended.', 'affiliate-wp' ); ?></p>

					</div>

					<?php if ( affwp_can_access_pro_features() ) : ?>

						<div id="qrcode-color-pickers">

							<div class="qrcode-color-picker-container" data-default-color="<?php echo esc_attr( affiliatewp_get_qrcode_default_colors()['code'] ); ?>">
								<label for="qrcode-code-color" class="color-picker-label"><strong><?php esc_html_e( 'Code Color', 'affiliate-wp' ); ?></strong></label>
								<input id="qrcode-code-color" type="text" class="affwp-color-picker" value="<?php echo esc_attr( $qrcode_settings['color']['dark'] ); ?>" name="qrcode_code_color" />
							</div>

							<div class="qrcode-color-picker-container" data-default-color="<?php echo esc_attr( affiliatewp_get_qrcode_default_colors()['bg'] ); ?>">
								<label for="qrcode-bg-color" class="color-picker-label"><strong><?php esc_html_e( 'Background Color', 'affiliate-wp' ); ?></strong></label>
								<input id="qrcode-bg-color" type="text" class="affwp-color-picker" value="<?php echo esc_attr( $qrcode_settings['color']['light'] ); ?>" name="qrcode_bg_color" />
							</div>

						</div>

						<p class="qrcode-disclaimer description" style="display:none;">
							<strong><?php esc_html_e( 'Remember to test the QR code with your new colors to ensure it remains scannable.', 'affiliate-wp' ); ?></strong>
						</p>

					<?php endif; ?>

				</td>

			</tr>

			<tr class="form-row form-required" data-row="date">

				<th scope="row">
					<label for="date"><?php esc_html_e( 'Created', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<input class="medium-text" type="text" name="date" id="date" value="<?php echo esc_attr( $creative->date_i18n( 'datetime' ) ); ?>" disabled="1" />
					<p class="description"><?php esc_html_e( 'The date the creative was created. This cannot be changed.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<?php affwp_creative_privacy_toggle( $creative ); ?>

			<?php

			/**
			 * Fires before status row.
			 *
			 * @since 2.13.0
			 * @since 2.15.0 Updated to reflect edit (not new).
			 */
			do_action( 'affwp_edit_creative_before_status', $creative );

			?>

			<tr class="form-row form-required" data-row="status">

				<th scope="row">
					<label for="status"><?php esc_html_e( 'Status', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<select name="status" id="status" class="affwp-use-select2">

						<?php foreach ( affwp_get_creative_statuses() as $status_key => $label ) : ?>

							<?php
							$show_education = 'scheduled' === $status_key && ! affwp_can_access_pro_features();
							$education_name = $show_education
								? esc_html__( 'Scheduled Creatives', 'affiliate-wp' )
								: '';
							$education_utm  = $show_education
								? esc_html__( 'scheduled-creatives', 'affiliate-wp' )
								: '';
							?>

							<option
								value="<?php echo esc_attr( $status_key ); ?>"
								<?php selected( $creative->status, $status_key ); ?>
								class="<?php echo esc_attr( affiliatewp_get_pro_feature_option_classes( 'scheduled', $status_key, $creative->status ) ); ?>"
								<?php echo affiliatewp_tag_attr( 'data-name', $education_name ); ?>
								<?php echo affiliatewp_tag_attr( 'data-utm-content', $education_utm ); ?>
							>
								<?php echo esc_html( $label ); ?>
							</option>

						<?php endforeach; ?>

					</select>
					<p class="description"><?php esc_html_e( 'Select the status of the creative. A creative can be Active, Inactive, or Scheduled.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

			<tr class="form-row affwp-schedule-creatives-setting <?php echo ( '0000-00-00 00:00:00' !== $creative->start_date || '0000-00-00 00:00:00' !== $creative->end_date ) ? '' : 'affwp-hidden' ?>" data-row="start_date">
				<th scope="row">
					<label id="affwp-creative-schedule" for="start_date"><?php esc_html_e( 'Schedule', 'affiliate-wp' ); ?></label>
				</th>

				<td class="schedule-creative-date-fields">
					<?php if ( true === affwp_is_upgrade_required( 'pro' ) ) : ?>
						<div class="affwp-upgrade-setting-cta">
							<p>
								<?php esc_html_e( 'Scheduling allows you to set start and end dates for your creatives, giving you more flexibility and control over your affiliate campaigns.', 'affiliate-wp' ); ?>
							</p>
							<h4>
								<a href="<?php echo esc_url( affwp_admin_upgrade_link( 'affiliatewp-creatives-edit', 'Upgrade to AffiliateWP Pro' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to AffiliateWP Pro to Unlock Creative Scheduling', 'affiliate-wp' ); ?></a>
							</h4>
						</div>
					<?php else : ?>
						<p class="description"><?php esc_html_e( "Set a specific time frame for your creative's visibility.", 'affiliate-wp' ); ?></p>
					<?php endif; ?>
					<div>
						<input type="text" class="affwp-schedule-creative-datepicker" autocomplete="off" id="start_date" name="start_date"
							value="<?php echo '0000-00-00 00:00:00' === $creative->start_date ? '' : esc_attr( affwp_date_i18n( strtotime( $creative->start_date ), 'm/d/Y' ) ); ?>"
							placeholder="<?php esc_html_e( 'mm/dd/yyyy', 'affiliate-wp' ); ?>"
							<?php echo esc_attr( true === affwp_is_upgrade_required( 'pro' ) ? 'disabled' : '' ); ?>/>
						<p class="description"><?php esc_html_e( 'Start date.', 'affiliate-wp' ); ?></p>
					</div>
					<div>
						<input type="text" class="affwp-schedule-creative-datepicker" autocomplete="off" name="end_date"
							value="<?php echo '0000-00-00 00:00:00' === $creative->end_date ? '' : esc_attr( affwp_date_i18n( strtotime( $creative->end_date ), 'm/d/Y' ) ); ?>"
							placeholder="<?php esc_html_e( 'mm/dd/yyyy', 'affiliate-wp' ); ?>"
							<?php echo esc_attr( true === affwp_is_upgrade_required( 'pro' ) ? 'disabled' : '' ); ?>/>
						<p class="description"><?php esc_html_e( 'End date.', 'affiliate-wp' ); ?></p>
					</div>
					<p class="affwp-schedule-description"><?php echo esc_html( affwp_get_creative_schedule_desc( $creative ) ); ?></p>
				</td>

			</tr>

			<tr class="form-row">

				<th scope="row">
					<label for="notes"><?php esc_html_e( 'Notes', 'affiliate-wp' ); ?></label>
				</th>

				<td>
					<textarea name="notes" rows="5" cols="50" id="notes" class="large-text"><?php echo esc_html( $creative->notes ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter any notes for this creative. Notes are only visible to an affiliate manager.', 'affiliate-wp' ); ?></p>
				</td>

			</tr>

		</table>

		<?php
		/**
		 * Fires at the bottom of the edit-creative admin screen.
		 *
		 * @since 1.0
		 *
		 * @param \AffWP\Creative $creative The creative object.
		 */
		do_action( 'affwp_edit_creative_bottom', $creative );
		?>

		<input type="hidden" name="affwp_action" value="update_creative" />

		<?php submit_button( __( 'Update Creative', 'affiliate-wp' ) ); ?>

	</form>

</div>
