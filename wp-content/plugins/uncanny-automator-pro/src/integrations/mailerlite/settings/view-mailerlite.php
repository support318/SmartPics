<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}
?>

<form method="POST" action="options.php">

	<?php settings_fields( $this->get_settings_id() ); ?>

	<div class="uap-settings-panel">

		<div class="uap-settings-panel-top">

			<div class="uap-settings-panel-title">

				<uo-icon integration="MAILERLITE"></uo-icon>

				<?php esc_html_e( 'MailerLite', 'uncanny-automator-pro' ); ?>

			</div>

			<?php if ( ! $vars['is_connected'] ) { ?>

				<?php if ( ! empty( $vars['alerts'] ) ) { ?>

					<?php foreach ( $vars['alerts'] as $alert ) { ?>

						<uo-alert class="uap-spacing-top" type="<?php echo esc_attr( $alert['type'] ); ?>" heading="<?php echo esc_attr( $alert['code'] ); ?>">

							<?php echo esc_html( $alert['message'] ); ?>

						</uo-alert>

					<?php } ?>

				<?php } ?>

				<div class="uap-settings-panel-content">

					<div class="uap-settings-panel-content-subtitle">

						<?php esc_html_e( 'Connect Uncanny Automator to MailerLite', 'uncanny-automator-pro' ); ?>

					</div><!--.uap-settings-panel-content-subtitle-->

					<p>
						<strong>
							<?php esc_html_e( 'Activating this integration will enable the following for use in your recipes:', 'uncanny-automator-pro' ); ?>
						</strong>
					</p>

					<ul>
						<li>
							<uo-icon id="bolt"></uo-icon>
							<strong>
								<?php esc_html_e( 'Action:', 'uncanny-automator-pro' ); ?>
							</strong>
							<?php esc_html_e( 'Add a subscriber to a group', 'uncanny-automator-pro' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon>
							<strong>
								<?php esc_html_e( 'Action:', 'uncanny-automator-pro' ); ?>
							</strong>
							<?php esc_html_e( 'Create a group', 'uncanny-automator-pro' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon>
							<strong>
								<?php esc_html_e( 'Action:', 'uncanny-automator-pro' ); ?>
							</strong>
							<?php esc_html_e( 'Remove a specific group', 'uncanny-automator-pro' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon>
							<strong>
								<?php esc_html_e( 'Action:', 'uncanny-automator-pro' ); ?>
							</strong>
							<?php esc_html_e( 'Remove a subscriber from a group', 'uncanny-automator-pro' ); ?>
						</li>
						<li>
							<uo-icon id="bolt"></uo-icon>
							<strong>
								<?php esc_html_e( 'Action:', 'uncanny-automator-pro' ); ?>
							</strong>
							<?php esc_html_e( 'Create or update a subscriber', 'uncanny-automator-pro' ); ?>
						</li>
					</ul>

					<div class="uap-settings-panel-content-separator"></div>

					<uo-alert heading="<?php esc_attr_e( 'Setup instructions', 'uncanny-automator-pro' ); ?>">

						<?php esc_html_e( "Connecting to MailerLite requires generating an API token from your MailerLite account. It's really easy, we promise!", 'uncanny-automator-pro' ); ?>

						<a target="_blank" href="<?php echo esc_url( $vars['knb_link'] ); ?>" title="<?php esc_html_e( 'Visit our Knowledge Base article', 'uncanny-automator-pro' ); ?>">
							<?php esc_html_e( 'Visit our Knowledge Base article', 'uncanny-automator-pro' ); ?> <uo-icon id="external-link"></uo-icon>
						</a>

						<?php esc_html_x( 'for instructions', 'uncanny-automator-knb-instructions', 'uncanny-automator-pro' ); ?>

					</uo-alert>

					<uo-text-field
						id="automator_mailerlite_api_token"
						name="automator_mailerlite_api_token"
						value="<?php echo esc_attr( $vars['api_token'] ); ?>"
						label="<?php esc_attr_e( 'API token', 'uncanny-automator-pro' ); ?>"
						class="uap-spacing-top"
						required
						<?php echo $vars['is_connected'] ? 'hidden disabled' : ''; ?>
					></uo-text-field>

				</div>

			<?php } ?>

			<?php if ( $vars['is_connected'] ) { ?>

				<div class="uap-settings-panel-content">

					<uo-alert heading="<?php esc_html_e( 'Uncanny Automator only supports connecting to one MailerLite account.', 'uncanny-automator-pro' ); ?>">

						<?php esc_html_e( 'If you create recipes and then change the connected MailerLite account, your previous recipes may no longer work.', 'uncanny-automator-pro' ); ?>

					</uo-alert>

				</div>

			<?php } ?>

		</div>

		<!-- Bottom panel -->
		<div class="uap-settings-panel-bottom">

			<?php if ( ! empty( $vars['is_connected'] ) ) { ?>

				<div class="uap-settings-panel-bottom-left">

					<div class="uap-settings-panel-user">

						<div class="uap-settings-panel-user__avatar">
							<?php echo esc_html( substr( $vars['client']['data']['name'], 0, 1 ) ); ?>
						</div>

						<div class="uap-settings-panel-user-info">

							<div class="uap-settings-panel-user-info__main">
								<?php echo esc_html( $vars['client']['data']['name'] ); ?>
								<uo-icon integration="MAILERLITE"></uo-icon>
							</div>

							<div class="uap-settings-panel-user-info__additional">
								ID: <?php echo esc_html( $vars['client']['data']['id'] ); ?>
								<?php echo esc_html( $vars['client']['data']['sender_email'] ); ?>
							</div>

						</div>

					</div>

				</div>

				<div class="uap-settings-panel-bottom-right">

					<uo-button href="<?php echo esc_url( $vars['disconnect_url'] ); ?>" color="danger">

						<uo-icon id="sign-out"></uo-icon>

						<?php esc_html_e( 'Disconnect', 'uncanny-automator-pro' ); ?>

					</uo-button>

				</div>

			<?php } else { ?>

				<uo-button id="automator-whatsapp-connect-btn" type="submit">

					<?php esc_html_e( 'Connect MailerLite account', 'uncanny-automator-pro' ); ?>

				</uo-button>

			<?php } ?>

		</div>

	</div>

</form>
