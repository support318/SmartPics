<?php
/**
 * Step 2 template file.
 */
?>
<div class="automator-setup-wizard-step-2-wrap">
	<div class="center automator-setup-wizard__branding">
		<img width="380" src="<?php echo esc_url( Uncanny_Automator\Utilities::automator_get_asset( 'backend/dist/img/logo-horizontal.svg' ) ); ?>" alt="" />
	</div>
	<div class="automator-setup-wizard__steps">
		<div class="automator-setup-wizard__steps__inner-wrap">
			<ol>
				<?php foreach ( $this->get_steps() as $step ) : ?>
					<li class="<?php echo implode( ' ', $step['classes'] ); ?>"> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span>
							<?php // translators: The step ?>
							<?php echo sprintf( esc_html__( 'Step %s', 'uncanny-automator' ), esc_html( $step['label'] ) ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>

	<?php if ( $this->is_user_connected() ) : ?>

		<div class="center row-1">
			<h2 class="title">
				<?php esc_html_e( 'License not activated', 'uncanny-automator' ); ?>
			</h2>
			<p style="width: 465px">
				<?php esc_html_e( 'Are you sure you want to skip license activation? Enter your Pro license to unlock unlimited app credits, plugin updates and premium support.', 'uncanny-automator' ); ?>
			</p>
			<p>
				<a
					href="<?php echo esc_url( $this->get_dashboard_uri( 1 ) ); ?>"
					title="<?php esc_html_e( 'Count me in!', 'uncanny-automator' ); ?>"
					class="uo-settings-btn uo-settings-btn--primary">
					<?php esc_html_e( 'Enter license key', 'uncanny-automator' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<?php // Not connected. ?>
		<div class="center row-1">
			<h2 class="title">
				<?php esc_html_e( 'Not connected', 'uncanny-automator' ); ?>
			</h2>
			<?php $error_message = get_transient( 'automator_setup_wizard_error' ); ?>
			<?php if ( ! empty( $error_message ) && ! isset( $_GET['skip'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<h3 style="color:#e94b35">
					<?php echo esc_html( get_transient( 'automator_setup_wizard_error' ) ); ?>
				</h3>
			<?php } ?>
			<p>
				<?php
					esc_html_e(
						'Your site is not connected to an Uncanny Automator account. You can still create recipes (automations) with any of our built-in integrations. To use unlimited app credits for integrations like Facebook, Slack, MailChimp and more, enter your Uncanny Automator Pro license key.',
						'uncanny-automator'
					);
				?>
			</p>
			<p>
				<a href="<?php echo esc_attr( $this->get_dashboard_uri( 1 ) ); ?>"
					id="ua-connect-account-btn"
					class="ua-connect-account-btn-class uo-settings-btn uo-settings-btn--primary"
					>
					<?php esc_html_e( 'Enter your license key', 'uncanny-automator' ); ?>
				</a>
			</p>
		</div>

	<?php endif; ?>

	<?php delete_transient( 'automator_setup_wizard_error' ); ?>

</div>
