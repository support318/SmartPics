<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php // Load some inline styles ?>
<style>
.automator-required-error button{
	background-color: #fff;
	border: 1px solid transparent;
	border-radius: 8px;
	box-shadow: 0 2px 5px 0 rgb(0 0 0 / 10%);
	cursor: pointer;
	display: inline-block;
	font-size: 14px;
	font-weight: 500;
	line-height: 1.5;
	outline: none;
	padding: 5px 15px;
	position: relative;
	text-align: center;
	transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
	user-select: none;
	vertical-align: middle;
	white-space: nowrap;
	border-color: #9e9e9e;
	color: #3c3c3c;
	margin: 10px 0 0 0;
}
.auto-plugin-install .auto-plugin-install-loader {
	float: right;
	height: 10px!important;
	width: 10px!important;
	margin: 2px 0 0 10px!important;
	border: 2px solid #fff;
	border-top: 2px solid #c0c0c0;
}
.automator-required-error button:hover {
	background-color: #3c3c3c;
	border-color: #3c3c3c;
	color: #fff;
}
.automator-required-error p:empty, .automator-required-error p.auto-plugin-install-message:empty {
	display: none;
}
.automator-required-error.error.error-notice {
	border: 1px solid #d9c5c1;
	background: #f9e2e2;
	color: #000;
	border-radius:8px;
	padding: 15px;
	line-height: 24px;
	margin: 5px 0 15px;
	box-shadow: none;
}
.automator-required-error__heading {
	font-size: 16px;
	font-weight: 500;
	margin-bottom: 3px;
}
.automator-required-error__wrap {
	display: flex;
}
.automator-required-error__icon{
	padding: 2.5px 10px 0 0;
}
.automator-required-error__message {
	font-size: 15px;
	display: block; 
	color: #3c3c3c;
}
</style>

<div class="error error-notice automator-required-error">
	<div class="automator-required-error__wrap">
		<div class="automator-required-error__icon">
			<span class="dashicons dashicons-info-outline"></span>
		</div>
		<div class="automator-required-error__body">
			<div class="automator-required-error__heading">
				<?php esc_html_e( 'Uncanny Automator is not installed or activated', 'uncanny-automator-pro' ); ?>
			</div>
			<div class="automator-required-error__message">
				<?php esc_html_e( 'The Uncanny Automator plugin must be active for Uncanny Automator Pro to work.', 'uncanny-automator-pro' ); ?>
				<?php esc_html_e( 'Please click on the button below to install & activate Uncanny Automator.', 'uncanny-automator-pro' ); ?>
			</div>
			<?php echo $this->installer->button( 'uncanny-automator', admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-dashboard' ) ); ?> <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</div>
