<?php

namespace Uncanny_Automator_Pro;

/**
 * Auto-prune logs
 * Settings > General > Logs > Auto-prune logs
 *
 * @since   3.7 - Added.
 * @since   5.4 - Added new field units and fields.
 *
 * @version 3.7
 * @package Uncanny_Automator
 * @author  Daniela R. & Agustin B.
 *
 * @var bool $is_enabled              TRUE if this setting is enabled
 * @var int  $interval_number_of_days Integer with interval, in days
 */

?>

<form method="POST">

	<?php wp_nonce_field( 'uncanny_automator' ); ?>

	<div class="uap-settings-panel-content-separator"></div>

	<div class="uap-settings-panel-content-subtitle">
		<?php esc_html_e( 'Auto-prune recipe logs', 'uncanny-automator-pro' ); ?><uo-pro-tag></uo-pro-tag>
	</div>

	<div class="uap-settings-panel-content">
		<div class="uap-field uap-spacing-top--small">
			<uo-switch
				id="uap_automator_purge_days_switch"
				<?php echo $is_enabled ? 'checked' : ''; ?>
				status-label="<?php esc_attr_e( 'Enabled', 'uncanny-automator' ); ?>,<?php esc_attr_e( 'Disabled', 'uncanny-automator' ); ?>"
				class="uap-spacing-top"
			></uo-switch>
			<div id="uap-auto-prune-content" style="display: none;">

				<label class="uap-field-description" style="display:block;" for="uap_automator_purge_days">
					<?php echo esc_html_x( 'Remove logs older than:', 'Prune logs', 'uncanny-automator' ); ?>
				</label>

				<div class="uap-field-container">
					<input
						class="uap-field-input-number"
						min="1"
						max="9999"
						id="uap_automator_purge_days"
						name="uap_automator_purge_days"
						placeholder="<?php esc_attr_e( 'Ex: 10', 'uncanny-automator' ); ?>"
						value="<?php echo ! empty( $interval_number_of_days ) ? esc_attr( $interval_number_of_days ) : '10'; ?>"
						type="number"
					/>

					<select class="uap-field-select" id="uap_automator_purge_unit" name="uap_automator_purge_unit">
						<option <?php selected( 'minutes', $interval_unit, true ); ?> value="minutes">
							<?php echo esc_attr_x( 'minute(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
						<option <?php selected( 'hours', $interval_unit, true ); ?> value="hours">
							<?php echo esc_attr_x( 'hour(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
						<option <?php selected( 'days', $interval_unit, true ); ?> value="days">
							<?php echo esc_attr_x( 'day(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
						<option <?php selected( 'weeks', $interval_unit, true ); ?> value="weeks">
							<?php echo esc_attr_x( 'week(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
						<option <?php selected( 'months', $interval_unit, true ); ?> value="months">
							<?php echo esc_attr_x( 'month(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
						<option <?php selected( 'years', $interval_unit, true ); ?> value="years">
							<?php echo esc_attr_x( 'year(s)', 'Auto Prune', 'uncanny-automator' ); ?>
						</option>
					</select>
				</div>



			</div>
		</div>

		<uo-button
			type="submit"
			class="uap-spacing-top"
		>
			<?php esc_html_e( 'Save', 'uncanny-automator' ); ?>
		</uo-button>
	</div>





</form>

<script>

/**
 * We're adding this code here because it's an exception and applies only
 * to the content in this template. If this is used in multiple settings,
 * consider creating a global solution.
 */

// Get the switch element
const $switch = document.getElementById( 'uap_automator_purge_days_switch' );

// Get the content element
const $content = document.getElementById( 'uap-auto-prune-content' );

/**
 * Sets the visibility of the content
 *
 * @return {undefined}
 */
const setContentVisibility = () => {
	// Check if it's enabled
	if ( $switch.checked ) {
		// Show
		$content.style.display = 'block';
	} else {
		// Hide
		$content.style.display = 'none';
	}
}

// Evaluate on load
setContentVisibility();

// Evaluate when the value of the switch changes
$switch.addEventListener( 'change', () => {
	// Evaluate the visibility
	setContentVisibility();
} );

</script>
