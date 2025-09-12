<?php
/**
 * File overwrite for step-1 template.
 *
 * @see src/core/admin/setup-wizard/src/views/step-1.php
 * @var $this
 *
 * @since 5.8
 */

use Uncanny_Automator_Pro\Setup_Wizard\Setup_Wizard as Setup_Wizard;

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'Uncanny_Automator\Utilities' ) ) {
	return;
}
?>

<div id="automator-setup-step-1" class="automator-pro-setup-wizard-step-1">

	<div class="center row-1">

		<div class="automator-setup-wizard__branding">
			<img width="380" src="<?php echo esc_url( Uncanny_Automator\Utilities::automator_get_asset( 'backend/dist/img/logo-horizontal.svg' ) ); ?>" alt="" />
		</div>

		<div class="automator-setup-wizard__steps">
			<div class="automator-setup-wizard__steps__inner-wrap">
				<ol>
					<?php foreach ( $this->get_steps() as $step ) : ?>
						<li class="<?php echo implode( ' ', $step['classes'] ); ?>"> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<?php // translators: The step ?>
								<?php echo sprintf( esc_html__( 'Step %s', 'uncanny-automator-pro' ), esc_html( $step['label'] ) ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>

		<h2 class="title">
			<?php esc_html_e( 'Welcome to the Uncanny Automator Setup Wizard!', 'uncanny-automator-pro' ); ?>
		</h2>

		<p>
			<?php esc_html_e( "You're just minutes away from building powerful automations that connect your plugins, sites and apps together. Enter your Pro license key below to unlock unlimited app credits, plugin updates and premium support.", 'uncanny-automator-pro' ); ?>
		</p>

		<p>
			<form method="POST" action="" id="license_key_form">
				<div class="uap-form-control">
					<label id="license_label" for="license_key">
						<?php esc_html_e( 'Uncanny Automator Pro license key', 'uncanny-automator-pro' ); ?>
					</label>
				</div>
				<div class="uap-form-control">
					<input type="text" id="license_key" autocomplete="off"/>
				</div>
				<div class="uap-form-control uap-form-control-bottom">
					<uo-alert id="feedback" class="message-response hidden"></uo-alert>
					<uo-button id="license_key_btn">
						<?php esc_html_e( 'Activate license', 'uncanny-automator-pro' ); ?>
					</uo-button>
				</div>
			</form>
		</p>

	</div>

	<div class="row-2">
		<p class="footer-actions" style="justify-content: center;">
			<span>
				<uap-setup-wizard-step-1-skip
					url-next-step="<?php echo esc_url( $this->get_dashboard_uri( 2 ) ); ?>&skip=true"
					url-connect-account="<?php echo esc_url( $this->get_connect_button_uri() ); ?>"
				></uap-setup-wizard-step-1-skip>
			</span>
		</p>
	</div>
</div>

<?php
wp_enqueue_script( Setup_Wizard::$script_handler );
?>
