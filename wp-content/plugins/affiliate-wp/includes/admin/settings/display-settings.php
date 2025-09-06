<?php
/**
 * Admin: Settings
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AffWP Admin Header
 *
 * @since 2.9.2
 * @return void
 */
function affwp_admin_header() {
	if ( ! affwp_is_admin_page() ) {
		return;
	}

	$number_notifications     = affiliate_wp()->notifications->count_active_notifications();
	$notifications_panel_file = AFFILIATEWP_PLUGIN_DIR . 'includes/components/notifications/views/notifications-panel.php';

	if ( file_exists( $notifications_panel_file ) ) {
		require_once $notifications_panel_file;
	}
	?>
	<div id="affwp-header">
		<div id="affwp-header-wrapper">
			<section class="affwp-header-title">
				<img width="190" height="32" alt="AffiliateWP logo" src="<?php echo AFFILIATEWP_PLUGIN_URL . 'assets/images/logo-affiliatewp.svg'; ?>" />

				<?php if ( 'affiliate-wp-setup-screen' === affwp_get_current_screen() ) : ?>
					<h1><?php echo esc_html__( 'Setup Guide', 'affiliate-wp' ); ?></h1>
				<?php endif; ?>
			</section>
			<div id="affwp-header-actions">
				<button
					id="affwp-notification-button"
					class="affwp-round"
					x-data
					x-init="$store.affwpNotifications.numberActiveNotifications = <?php echo esc_js( $number_notifications ); ?>"
					@click="$store.affwpNotifications.openPanel()"
				>
					<span
						class="affwp-round affwp-number<?php echo 0 === $number_notifications ? ' affwp-hidden' : ''; ?>"
						x-show="$store.affwpNotifications.numberActiveNotifications > 0"
					>
						<?php
						echo wp_kses(
							sprintf(
								/* Translators: %1$s number of notifications; %2$s opening span tag; %3$s closing span tag */
								__( '%1$s %2$sunread notifications%3$s', 'affiliate-wp' ),
								'<span x-text="$store.affwpNotifications.numberActiveNotifications"></span>',
								'<span class="screen-reader-text">',
								'</span>'
							),
							[
								'span' => [
									'class'  => true,
									'x-text' => true,
								],
							]
						);
						?>
					</span>

					<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="affwp-notifications-icon"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.8333 2.5H4.16667C3.25 2.5 2.5 3.25 2.5 4.16667V15.8333C2.5 16.75 3.24167 17.5 4.16667 17.5H15.8333C16.75 17.5 17.5 16.75 17.5 15.8333V4.16667C17.5 3.25 16.75 2.5 15.8333 2.5ZM15.8333 15.8333H4.16667V13.3333H7.13333C7.70833 14.325 8.775 15 10.0083 15C11.2417 15 12.3 14.325 12.8833 13.3333H15.8333V15.8333ZM11.675 11.6667H15.8333V4.16667H4.16667V11.6667H8.34167C8.34167 12.5833 9.09167 13.3333 10.0083 13.3333C10.925 13.3333 11.675 12.5833 11.675 11.6667Z" fill="currentColor"></path></svg>
				</button>
				<span class="affwp-round">
					<a href="https://affiliatewp.com/docs/" target="_blank" rel="noopener noreferrer">
						<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.6665 10.0001C1.6665 5.40008 5.39984 1.66675 9.99984 1.66675C14.5998 1.66675 18.3332 5.40008 18.3332 10.0001C18.3332 14.6001 14.5998 18.3334 9.99984 18.3334C5.39984 18.3334 1.6665 14.6001 1.6665 10.0001ZM10.8332 13.3334V15.0001H9.1665V13.3334H10.8332ZM9.99984 16.6667C6.32484 16.6667 3.33317 13.6751 3.33317 10.0001C3.33317 6.32508 6.32484 3.33341 9.99984 3.33341C13.6748 3.33341 16.6665 6.32508 16.6665 10.0001C16.6665 13.6751 13.6748 16.6667 9.99984 16.6667ZM6.6665 8.33341C6.6665 6.49175 8.15817 5.00008 9.99984 5.00008C11.8415 5.00008 13.3332 6.49175 13.3332 8.33341C13.3332 9.40251 12.6748 9.97785 12.0338 10.538C11.4257 11.0695 10.8332 11.5873 10.8332 12.5001H9.1665C9.1665 10.9824 9.9516 10.3806 10.6419 9.85148C11.1834 9.43642 11.6665 9.06609 11.6665 8.33341C11.6665 7.41675 10.9165 6.66675 9.99984 6.66675C9.08317 6.66675 8.33317 7.41675 8.33317 8.33341H6.6665Z" fill="currentColor"></path></svg>
					</a>
				</span>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'in_admin_header', 'affwp_admin_header', 1 );

/**
 * Renders the settings fields for a particular settings section.
 *
 * Copied mostly verbatim from `do_settings_section()` with the addition of
 * setting a unique id attribute for the table row elements.
 *
 * @param string $page Slug title of the admin page whose settings fields you want to show.
 * @param string $section Slug title of the settings section whose fields you want to show.
 *
 * @throws Exception When a field is not registered.
 *
 * @since 2.7
 * @since 2.18.0 Support to display sections of settings.
 * @since 2.26.1 Added plus badge support.
 *
 * @global array $wp_settings_fields Storage array of settings fields and their pages/sections.
 */
function affwp_do_settings_fields( string $page, string $section ) {

	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	$tab_name     = str_replace( 'affwp_settings_', '', $section );
	$tab_sections = affiliate_wp()->settings->get_sections( $tab_name );

	if ( empty( $tab_sections ) ) {
		return; // No sections registered for this tab.
	}

	ob_start();

	?>

	<div class="affwp-accordion-disabled"> <!-- Remove -disabled to activate the accordion again. -->

	<?php foreach ( $tab_sections as $tab_handle => $tab_section ) : ?>

		<?php

		if ( empty( $tab_section['fields'] ) ) {
			continue; // Can't render sections without fields.
		}

		$section_classes = [
			'affwp-section',
			'affwp-accordion-item',
			$tab_section['class'] ?? '',
		];

		$template = $tab_section['template'] ?? '';

		if ( empty( $template ) ) {
			continue; // No template.
		}

		$template_func = "affiliatewp_section_template_{$template}";

		if ( ! is_callable( $template_func ) ) {
			continue;
		}

		?>

		<div
			class="<?php echo esc_attr( trim( implode( ' ', $section_classes ) ) ); ?>"
			id="<?php echo esc_attr( str_replace( '_', '-', $tab_handle ) ); ?>-settings"
			<?php echo affiliatewp_tag_attr( 'data-visibility', $tab_section['visibility'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
		>

			<div class="affwp-section-title affwp-accordion-title">
				<h2>
					<span class="affwp-accordion-heading"><?php echo esc_html( trim( $tab_section['title'] ) ); ?></span>

					<?php if ( 'pro' === $tab_section['license_level'] && ! affwp_can_access_pro_features() ) : ?>
						<span class="affwp-settings-label-pro">Pro</span>
					<?php elseif ( 'plus' === $tab_section['license_level'] && ! affiliatewp_can_access_plus_features() ) : ?>
						<span class="affwp-settings-label-pro">Plus</span>
					<?php endif; ?>

					<?php if ( ! empty( $tab_section['tooltip'] ) ) : ?>
						<?php affiliatewp_tooltip( $tab_section['tooltip'] ); ?>
					<?php endif; ?>
				</h2>
				<?php if ( ! empty( $tab_section['help_text'] ) ) : ?>
					<p class="affwp-section-help-text affwp-accordion-subtitle"><?php echo wp_kses( $tab_section['help_text'], affwp_kses() ); ?></p>
				<?php endif; ?>
			</div>

			<div class="affwp-accordion-content">

				<?php

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No need to escape.
				echo call_user_func(
					$template_func,
					$page,
					$section,
					$tab_section['fields']
				);

				?>

			</div>
		</div>

	<?php endforeach; ?>

	<?php

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped at this point.
	echo ob_get_clean();
}

/**
 * Render a section using the table template.
 *
 * @param string $page Slug title of the admin page whose settings field you want to show.
 * @param string $section Slug title of the settings section whose fields you want to show.
 * @param array  $fields The array of fields to render.
 *
 * @throws Exception When a field is not registered.
 *
 * @return string
 */
function affiliatewp_section_template_table( string $page, string $section, array $fields ) : string {

	ob_start();

	?>

	<table class="form-table">

		<?php foreach ( $fields as $field_key ) : ?>

			<?php

			$field = affiliatewp_get_field_from_settings( $page, $section, $field_key );

			if ( false === $field ) {
				continue; // Field doesn't exist, skip it.
			}

			// Append the necessary classes.
			$classes   = explode( ' ', $field['args']['class'] );
			$classes[] = 'affwp-setting-row';
			$classes   = implode( ' ', array_filter( $classes ) );

			// Check if we need to display the pro badge.
			$show_label_pro_badge =
				! isset( $field['args']['education_modal']['options'] ) &&
				! affwp_can_access_pro_features() &&
				! empty( $field['args']['education_modal'] ) &&
				(
					(
						isset( $field['args']['education_modal']['show_pro_badge'] ) &&
						true === $field['args']['education_modal']['show_pro_badge']
					) ||
					! isset( $field['args']['education_modal']['show_pro_badge'] )
				);

			?>

			<tr
				<?php

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
				echo affiliatewp_tag_attr( 'id', $field['args']['id'] );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
				echo affiliatewp_tag_attr( 'class', trim( $classes ) );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
				echo affiliatewp_tag_attr( 'data-visibility', $field['args']['visibility'] );

				?>
			>
				<th scope="row">
					<div>
						<?php if ( empty( $field['args']['label_for'] ) ) : ?>

							<?php
							$label_for = sprintf(
								'affwp_settings[%s]%s',
								$field['args']['id'],
								isset( $field['args']['options'] ) && ! empty( $field['args']['options'] )
									? sprintf( '[%s]', array_keys( $field['args']['options'] )[0] )
									: ''
							);
							?>

							<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
							<label<?php echo affiliatewp_tag_attr( 'for', $label_for ); ?>>
								<?php echo wp_kses( $field['title'], affwp_kses() ); ?>
							</label>

						<?php else : ?>

							<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
							<label<?php echo affiliatewp_tag_attr( 'for', $field['args']['label_for'] ); ?>>
								<?php echo wp_kses( $field['title'], affwp_kses() ); ?>
							</label>

						<?php endif; ?>

						<?php if ( $show_label_pro_badge ) : ?>
							<span class="affwp-settings-label-pro">Pro</span>
						<?php endif; ?>

						<?php if ( ! empty( $field['args']['tooltip'] ) && is_string( $field['args']['tooltip'] ) ) : ?>
							<?php affiliatewp_tooltip( $field['args']['tooltip'] ); ?>
						<?php endif; ?>

					</div>
				</th>
				<?php if ( is_callable( $field['callback'] ) ) : ?>
					<td><?php call_user_func( $field['callback'], $field['args'] ); ?></td>
				<?php endif; ?>
			</tr>

		<?php endforeach; ?>

	</table>

	<?php

	return ob_get_clean();
}

/**
 * Render a section using the table template.
 *
 * @param string $page Slug title of the admin page whose settings field you want to show.
 * @param string $section Slug title of the settings section whose fields you want to show.
 * @param array  $field_groups Groups of fields to render.
 *
 * @throws Exception When a field is not registered.
 *
 * @return string
 */
function affiliatewp_section_template_affiliate_signup_widget( string $page, string $section, array $field_groups ) : string {

	ob_start();

	?>

	<div class="affwp-admin-affiliate-signup-widget-wrapper">

		<div class="affwp-admin-customizer-field-groups">

			<?php foreach ( $field_groups as $field_group ) : ?>

				<?php if ( is_string( $field_group[0] ) ) : ?>

					<?php

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
					echo affiliatewp_section_template_table( $page, $section, $field_group );

					?>

				<?php endif; ?>

				<?php if ( is_array( $field_group[0] ) ) : ?>

					<div class="affwp-accordion" data-toggle data-theme="light">

						<?php foreach ( $field_group as $group_key => $group ) : ?>

							<div class="affwp-settings-group affwp-accordion-item<?php echo 0 === $group_key ? '' : ' affwp-accordion-collapsed'; ?>">

								<div class="affwp-accordion-title">
									<h3><?php echo esc_html( $group['title'] ?? "Group {$group_key}" ); ?></h3>
								</div>

								<div class="affwp-accordion-content">

									<?php

									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
									echo affiliatewp_section_template_table( $page, $section, $group['settings'] ?? [] );

									?>

								</div>

							</div>

						<?php endforeach; ?>
					</div>

				<?php endif; ?>

			<?php endforeach; ?>
		</div>

		<div class="affwp-admin-affiliate-signup-widget-preview">
			<?php do_action( 'affwp_admin_affiliate_signup_widget_preview' ); ?>
		</div>

	</div>
	<?php

	return ob_get_clean();
}

/**
 * Render a section using the affiliate_security template.
 *
 * @since 2.28.0
 *
 * @param string $page    Slug title of the admin page whose settings field you want to show.
 * @param string $section Slug title of the settings section whose fields you want to show.
 * @param array  $fields  The array of fields to render.
 *
 * @return string
 */
function affiliatewp_section_template_affiliate_security( string $page, string $section, array $fields ) : string {

	ob_start();

	?>

	<div class="affwp-security-section-wrapper">

		<div class="affwp-captcha-options-wrapper">

		<div class="affwp-captcha-type-selector">
			<?php
			// Render the CAPTCHA type radio field first
			$captcha_type_field = affiliatewp_get_field_from_settings( $page, $section, 'captcha_type' );
			if ( false !== $captcha_type_field && is_callable( $captcha_type_field['callback'] ) ) {
				?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label><?php echo wp_kses( $captcha_type_field['title'], affwp_kses() ); ?></label>
						</th>
						<td>
							<div class="captcha-selector-container">

							<?php if ( ! empty( $captcha_type_field['args']['desc'] ) ) : ?>
								<div class="captcha-selector__description">
									<?php if ( is_array( $captcha_type_field['args']['desc'] ) ) : ?>
										<?php foreach ( $captcha_type_field['args']['desc'] as $desc_paragraph ) : ?>
											<p class="description"><?php echo wp_kses( $desc_paragraph, affwp_kses() ); ?></p>
										<?php endforeach; ?>
									<?php else : ?>
										<p class="description"><?php echo wp_kses( $captcha_type_field['args']['desc'], affwp_kses() ); ?></p>
									<?php endif; ?>
								</div>
								<?php endif; ?>

								<div class="captcha-selector">
								<?php
								$options       = $captcha_type_field['args']['options'];
								$current_value = affiliate_wp()->settings->get( 'captcha_type', 'none' );

								foreach ( $options as $key => $label ) {
									$checked        = ( $current_value === $key ) ? 'checked' : '';
									$selected_class = ( $current_value === $key ) ? 'selected' : '';

									// Choose icon based on option
									$icon_content = '';
									switch ( $key ) {
										case 'none':
											$icon_content = \AffiliateWP\Utils\Icons::generate( 'captcha-none', '', [ 'width' => '64', 'height' => '64' ] );
											break;
										case 'recaptcha':
											$icon_content = '<img src="' . AFFILIATEWP_PLUGIN_URL . 'assets/images/captcha/recaptcha.svg" alt="' . esc_attr__( 'Google reCAPTCHA', 'affiliate-wp' ) . '" />';
											break;
										case 'hcaptcha':
											$icon_content = '<img src="' . AFFILIATEWP_PLUGIN_URL . 'assets/images/captcha/hcaptcha.svg" alt="' . esc_attr__( 'hCaptcha', 'affiliate-wp' ) . '" />';
											break;
										case 'turnstile':
											$icon_content = '<img src="' . AFFILIATEWP_PLUGIN_URL . 'assets/images/captcha/turnstile.svg" alt="' . esc_attr__( 'Cloudflare Turnstile', 'affiliate-wp' ) . '" />';
											break;
									}
									?>
									<label class="captcha-selector__option <?php echo $current_value === $key ? 'captcha-selector__option--selected' : ''; ?>" data-value="<?php echo esc_attr( $key ); ?>">
										<input type="radio"
												name="affwp_settings[captcha_type]"
												value="<?php echo esc_attr( $key ); ?>"
												class="captcha-selector__option-input"
												<?php echo $checked; ?> />
										<div class="captcha-selector__option-icon <?php echo $key === 'turnstile' ? 'captcha-selector__option-icon--turnstile' : ''; ?> <?php echo $key === 'none' ? 'captcha-selector__option-icon--none' : ''; ?>"><?php echo $icon_content; ?></div>
										<span class="captcha-selector__option-label"><?php echo esc_html( $label ); ?></span>
									</label>
									<?php
								}
								?>
								</div>




								<?php
								/*
								 * CAPTCHA Info Boxes
								 */
								$captcha_info_boxes = [
									'recaptcha' => [
										'description'   => __( 'Google reCAPTCHA is a free anti-spam service that filters automated abuse from your forms with either a familiar "I\'m not a robot" checkbox or an invisible background check, keeping real users on track.', 'affiliate-wp' ),
										'doc_text'      => __( 'For more details on how Google reCAPTCHA works, as well as a step by step setup guide, please check out our %s.', 'affiliate-wp' ),
										'doc_url'       => affwp_utm_link( 'https://affiliatewp.com/docs/how-to-set-up-and-use-recaptcha-in-affiliatewp/', 'captcha-security', 'recaptcha-info-box', 'security-settings' ),
										'has_lightbulb' => true,
									],
									'hcaptcha'  => [
										'description'   => __( 'hCaptcha is a free and privacy-oriented spam-prevention service that adds a quick human-verification step to your forms, blocking bots while letting legitimate visitors straight through.', 'affiliate-wp' ),
										'doc_text'      => __( 'For more details on how hCaptcha works, as well as a step by step setup guide, please check out our %s.', 'affiliate-wp' ),
										'doc_url'       => affwp_utm_link( 'https://affiliatewp.com/docs/how-to-set-up-and-use-hcaptcha-in-affiliatewp/', 'captcha-security', 'hcaptcha-info-box', 'security-settings' ),
										'has_lightbulb' => true,
									],
									'turnstile' => [
										'description'   => __( 'Cloudflare Turnstile is a free, puzzle-less CAPTCHA alternative that quietly validates visitors and stops bots without disruptive challenges, maintaining a seamless experience.', 'affiliate-wp' ),
										'doc_text'      => __( 'For more details on how Cloudflare Turnstile works, as well as a step by step setup guide, please check out our %s.', 'affiliate-wp' ),
										'doc_url'       => affwp_utm_link( 'https://affiliatewp.com/docs/how-to-set-up-and-use-turnstile-in-affiliatewp/', 'captcha-security', 'turnstile-info-box', 'security-settings' ),
										'has_lightbulb' => true,
									],
									'none'      => [
										'has_lightbulb' => false,
									],
								];
								?>
								<div class="captcha-selector__info-boxes">
									<?php foreach ( $captcha_info_boxes as $captcha_type => $info ) : ?>
										<div class="captcha-selector__info-box captcha-selector__info-box--<?php echo esc_attr( $captcha_type ); ?>" data-captcha-type="<?php echo esc_attr( $captcha_type ); ?>">
											<div class="captcha-selector__info-box-content">
												<div class="captcha-selector__info-box-icon">
													<?php if ( $info['has_lightbulb'] ) : ?>
														<?php \AffiliateWP\Utils\Icons::render( 'lightbulb' ); ?>
													<?php elseif ( 'none' === $captcha_type ) : ?>
														<?php \AffiliateWP\Utils\Icons::render( 'captcha-none' ); ?>
													<?php endif; ?>
												</div>
												<?php if ( $info['has_lightbulb'] ) : ?>
													<div class="captcha-selector__info-box-text">
														<p class="captcha-selector__info-box-description"><?php echo esc_html( $info['description'] ); ?></p>
														<p class="captcha-selector__info-box-link">
															<?php
															printf(
																/* translators: %s: Link to documentation */
																esc_html( $info['doc_text'] ),
																'<a href="' . esc_url( $info['doc_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'documentation', 'affiliate-wp' ) . '</a>'
															);
															?>
														</p>
													</div>
												<?php endif; ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>							</div>





						</td>
					</tr>

					<?php
					// Add CAPTCHA configuration fields to the main table
					// Define which fields are CAPTCHA-related vs general security settings
					$captcha_fields = [
						'recaptcha_type',
						'recaptcha_site_key',
						'recaptcha_secret_key',
						'recaptcha_score_threshold',
						'hcaptcha_site_key',
						'hcaptcha_secret_key',
						'turnstile_site_key',
						'turnstile_secret_key',
						'captcha_protect_login',
					];

					// Function to render a field with proper CAPTCHA type data attribute
					$render_captcha_field = function ( $field_key ) use ( $page, $section ) {
						$field = affiliatewp_get_field_from_settings( $page, $section, $field_key );

						if ( false === $field ) {
							return; // Field doesn't exist, skip it.
						}

						// Fix visibility structure for JavaScript compatibility
						if ( isset( $field['args']['visibility'] ) ) {
							$visibility = $field['args']['visibility'];

							if ( isset( $visibility['rule'] ) && isset( $visibility['field'] ) ) {
								// Check if this is a multi-rule case that's already properly structured
								if ( isset( $visibility['rule']['rule'] ) && is_array( $visibility['rule']['rule'] ) ) {
									$field['args']['visibility'] = [ 'rule' => $visibility['rule']['rule'] ];
								} else {
									$field['args']['visibility'] = [ 'rule' => $visibility['rule'] ];
								}
							} elseif ( ! isset( $visibility['rule'] ) && isset( $visibility['required_field'] ) ) {
								$field['args']['visibility'] = [ 'rule' => $visibility ];
							}
						}

						// Determine which CAPTCHA type this field belongs to
						$captcha_type = '';
						if ( strpos( $field_key, 'recaptcha' ) === 0 ) {
							$captcha_type = 'recaptcha';
						} elseif ( strpos( $field_key, 'hcaptcha' ) === 0 ) {
							$captcha_type = 'hcaptcha';
						} elseif ( strpos( $field_key, 'turnstile' ) === 0 ) {
							$captcha_type = 'turnstile';
						} elseif ( 'captcha_protect_login' === $field_key ) {
							$captcha_type = 'all'; // This field applies to all CAPTCHA types
						}

						$classes = array_filter(
							[
								'affwp-settings-field',
								'affwp-captcha-config-field',
								$field['args']['class'] ?? '',
							]
						);

						// Special styling for recaptcha_version field
						if ( 'recaptcha_version' === $field_key ) {
							$classes[] = 'affwp-recaptcha-version-field';
						}

						?>
						<tr class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
							data-captcha-type="<?php echo esc_attr( $captcha_type ); ?>"
							<?php
							echo affiliatewp_tag_attr( 'id', $field['args']['id'] );
							echo affiliatewp_tag_attr( 'data-visibility', $field['args']['visibility'] );
							?>
						>
							<th scope="row">
								<div>
									<?php if ( empty( $field['args']['label_for'] ) ) : ?>

										<?php
										$label_for = sprintf(
											'affwp_settings[%s]%s',
											$field['args']['id'],
											isset( $field['args']['options'] ) && ! empty( $field['args']['options'] )
												? sprintf( '[%s]', array_keys( $field['args']['options'] )[0] )
												: ''
										);
										?>

										<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
										<label<?php echo affiliatewp_tag_attr( 'for', $label_for ); ?>>
											<?php echo wp_kses( $field['title'], affwp_kses() ); ?>
										</label>

									<?php else : ?>

										<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
										<label<?php echo affiliatewp_tag_attr( 'for', $field['args']['label_for'] ); ?>>
											<?php echo wp_kses( $field['title'], affwp_kses() ); ?>
										</label>

									<?php endif; ?>

									<?php if ( ! empty( $field['args']['tooltip'] ) && is_string( $field['args']['tooltip'] ) ) : ?>
										<?php affiliatewp_tooltip( $field['args']['tooltip'] ); ?>
									<?php endif; ?>
								</div>
							</th>
							<td>
								<?php if ( is_callable( $field['callback'] ) ) : ?>
									<?php call_user_func( $field['callback'], $field['args'] ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php
					};

					// Render all CAPTCHA configuration fields
				foreach ( $captcha_fields as $field_key ) {
					if ( 'captcha_type' === $field_key ) {
						continue; // Skip the type field as we already rendered it
					}
					$render_captcha_field( $field_key );
				}
				?>

				</table>
				<?php
			}
			?>
		</div>

		<?php
		// Separate non-CAPTCHA fields for the security settings section
		$non_captcha_fields = [];
		$captcha_field_list = [
			'recaptcha_type',
			'recaptcha_site_key',
			'recaptcha_secret_key',
			'recaptcha_score_threshold',
			'hcaptcha_site_key',
			'hcaptcha_secret_key',
			'turnstile_site_key',
			'turnstile_secret_key',
			'captcha_protect_login',
		];

		foreach ( $fields as $field_key ) {
			if ( 'captcha_type' === $field_key ) {
				continue; // Skip the type field as we already rendered it
			}

			if ( ! in_array( $field_key, $captcha_field_list, true ) ) {
				$non_captcha_fields[] = $field_key;
			}
		}
		?>

	</div>

	<!-- Non-CAPTCHA Security Settings (always visible) -->
	<?php if ( ! empty( $non_captcha_fields ) ) : ?>
		<div class="affwp-security-settings">
			<table class="form-table">
				<?php
				// Render non-CAPTCHA fields
				foreach ( $non_captcha_fields as $field_key ) {
					$field = affiliatewp_get_field_from_settings( $page, $section, $field_key );

					if ( false === $field ) {
						continue; // Field doesn't exist, skip it.
					}

					// Fix visibility structure for JavaScript compatibility
					if ( isset( $field['args']['visibility'] ) ) {
						$visibility = $field['args']['visibility'];

						if ( isset( $visibility['rule'] ) && isset( $visibility['field'] ) ) {
							// Check if this is a multi-rule case that's already properly structured
							if ( isset( $visibility['rule']['rule'] ) && is_array( $visibility['rule']['rule'] ) ) {
								$field['args']['visibility'] = [ 'rule' => $visibility['rule']['rule'] ];
							} else {
								$field['args']['visibility'] = [ 'rule' => $visibility['rule'] ];
							}
						} elseif ( ! isset( $visibility['rule'] ) && isset( $visibility['required_field'] ) ) {
							$field['args']['visibility'] = [ 'rule' => $visibility ];
						}
					}

					$classes = array_filter(
						[
							'affwp-settings-field',
							'affwp-security-setting-field',
							$field['args']['class'] ?? '',
						]
					);

					?>
					<tr class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
						<?php
						echo affiliatewp_tag_attr( 'id', $field['args']['id'] );
						echo affiliatewp_tag_attr( 'data-visibility', $field['args']['visibility'] );
						?>
					>
						<th scope="row">
							<label for="<?php echo esc_attr( $field['args']['id'] ); ?>">
								<?php echo wp_kses( $field['title'], affwp_kses() ); ?>
							</label>
						</th>
						<td>
							<?php if ( is_callable( $field['callback'] ) ) : ?>
								<?php call_user_func( $field['callback'], $field['args'] ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
	<?php endif; ?>

	</div>

	<?php

	return ob_get_clean();
}

/**
 * Retrieve a field from the $wp_settings_fields global.
 *
 * @since 2.18.0
 * @since 2.18.2 We don't throw exception if the field doesn't exist anymore, returning false instead.
 *
 * @param string $page Slug title of the admin page whose settings field you want to show.
 * @param string $section Slug title of the settings section whose fields you want to show.
 * @param string $field_key The array of fields to render.
 *
 * @return mixed|false The field key. False in case the setting is not registered.
 */
function affiliatewp_get_field_from_settings( string $page, string $section, string $field_key ) {

	global $wp_settings_fields;

	$field_internal_key = "affwp_settings[{$field_key}]";

	if ( ! isset( $wp_settings_fields[ $page ][ $section ][ $field_internal_key ] ) ) {
		return false;
	}

	return $wp_settings_fields[ $page ][ $section ][ $field_internal_key ];
}

/**
 * Options Page
 *
 * Renders the options page contents.
 *
 * @since 1.0
 *
 * @throws Exception If the field is not registered.
 */
function affwp_settings_admin() {

	$active_tab = affiliate_wp()->settings->get_active_tab();

	ob_start();

	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php affwp_navigation_tabs( affwp_get_settings_tabs(), $active_tab, [ 'settings-updated' => false ] ); ?>
		</h2>
		<div id="tab_container">
			<form id="affwp-settings-form" method="post" action="options.php">
				<?php
				settings_fields( 'affwp_settings' );
				affwp_do_settings_fields( 'affwp_settings_' . $active_tab, 'affwp_settings_' . $active_tab );
				?>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->

	<?php

	 // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
	echo ob_get_clean();
}


/**
 * Retrieves the settings tabs.
 *
 * @since 1.0
 * @since 2.18.0 We now use Settings::get_tabs() method to retrieve tabs.
 *
 * @return array $tabs Settings tabs.
 */
function affwp_get_settings_tabs() : array {

	return affiliate_wp()->settings->get_tabs();
}

/**
 * Forces a license key check anytime the General settings tab is loaded
 *
 * @since 2.1.4
 *
 * @return void
 */
function affwp_check_license_before_settings_load() {

	if ( empty( $_GET['page'] ) || 'affiliate-wp-settings' !== $_GET['page'] ) {
		return;
	}

	if ( empty( $_GET['tab'] ) ) {
		return;
	}

	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], affwp_get_settings_tabs() ) ? $_GET['tab'] : 'general';

	if ( 'general' === $active_tab && affiliate_wp()->settings->get_license_key() ) {
		affiliate_wp()->settings->check_license( true );
	}
}
add_action( 'admin_init', 'affwp_check_license_before_settings_load', 0 );
