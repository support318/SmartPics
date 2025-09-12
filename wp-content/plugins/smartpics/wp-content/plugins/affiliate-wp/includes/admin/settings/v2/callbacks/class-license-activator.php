<?php
/**
 * Register the License Activator callback to be used in the Settings screens.
 *
 * @package    AffiliateWP
 * @subpackage AffiliateWP\Admin\Settings\V2\Callbacks
 * @copyright  Copyright (c) 2024, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.26.1
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Admin\Settings\V2\Callbacks;

/**
 * License Activator class.
 *
 * @since 2.26.1
 */
final class License_Activator extends Base {
	/**
	 * Script handle name.
	 *
	 * @since 2.26.1
	 * @var string The name of the script handle.
	 */
	const SCRIPT_HANDLE = 'affiliatewp-admin-license-activator';

	/**
	 * Constructor.
	 *
	 * @since 2.26.1
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * The callback name.
	 *
	 * @since 2.26.1
	 * @return string The callback name.
	 */
	public function get_name() : string {
		return 'license_activator';
	}

	/**
	 * Render a password field with a button to activate the license.
	 *
	 * @since 2.26.1
	 * @param array $args The arguments passed to the callback on settings register.
	 * @see \Affiliate_WP_Settings::register_settings
	 * @return void
	 */
	public function render( array $args ) : void {
		$setting_name = sprintf( 'affwp_settings[%s]', $args['id'] ?? '' );
		$activator    = $args['activator_options'] ?? [];
		$is_activated = ( 
			! empty( $activator['status_callback'] ) 
			&& is_callable( $activator['status_callback'] ) 
			&& (bool) $activator['status_callback']() 
		);

		?>

		<div class="affwp-settings-license-activator">
			<input
				type="password"
				class="<?php echo esc_attr( $args['size'] ?? 'regular' ); ?>-text"
				id="<?php echo esc_attr( $setting_name ); ?>"
				name="<?php echo esc_attr( $setting_name ); ?>"
				value="<?php echo esc_attr( $args['value'] ); ?>"
				<?php echo affiliatewp_tag_attr( 'disabled', $args['disabled'] ? 'disabled' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the function. ?>
			>
			<button
				type="button"
				class="button affwp-settings-license-activator__button"
				data-is-activated="<?php echo absint( $is_activated ); ?>"
				data-target="<?php echo esc_attr( $setting_name ); ?>"
				data-activate-ajax-action="<?php echo esc_attr( $activator['activate_ajax_action'] ); ?>"
				data-deactivate-ajax-action="<?php echo esc_attr( $activator['deactivate_ajax_action'] ); ?>"
				data-label-activated="<?php echo esc_attr( $activator['button_label_activated'] ); ?>"
				data-label-deactivated="<?php echo esc_attr( $activator['button_label_deactivated'] ); ?>"
				data-activation-message="<?php echo esc_attr( $activator['activation_message'] ); ?>"
				data-deactivation-message="<?php echo esc_attr( $activator['deactivation_message'] ); ?>"
				data-invalid-message="<?php echo esc_attr( $activator['invalid_message'] ); ?>"
				data-activation-error-message="<?php echo esc_attr( $activator['activation_error_message'] ); ?>"
				data-deactivation-error-message="<?php echo esc_attr( $activator['deactivation_error_message'] ); ?>"
				data-settings-to-watch="<?php echo esc_attr( wp_json_encode( $activator['settings_to_watch'] ) ); ?>"

			><?php echo $is_activated ? esc_html( $activator['button_label_activated'] ) : esc_html( $activator['button_label_deactivated'] ); ?></button>
		</div>
		<p class="affwp-settings-license-activator__status">
			<?php
			
			echo ! empty( $activator['status_message'] )
				? wp_kses( $activator['status_message'], affwp_kses() )
				: '';
			
			?>
		</p>
		<p class="description"><?php echo wp_kses( $args['desc'], affwp_kses() ); ?></p>

		<?php
	}

	/**
	 * Enqueue scripts.
	 *
	 * Will append necessary data to the script and initiate it right away.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function enqueue_assets() : void {
		affiliate_wp()->scripts->enqueue( self::SCRIPT_HANDLE );

		$data = wp_json_encode( [ 'nonce' => wp_create_nonce( $this->get_name() ) ] );

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			"affiliatewp.licenseActivator.data={$data}; affiliatewp.licenseActivator.init();"
		);
	}

	/**
	 * Get the default values for the callback settings.
	 *
	 * Returns an array of default values for the callback settings.
	 *
	 * @since 2.26.1
	 * @return array The default values for the callback settings.
	 */
	public function get_defaults() : array {
		return [
			'activate_ajax_action'       => '', // An ajax action to be called when there is an intention to activate.
			'deactivate_ajax_action'     => '', // An ajax action to be called when there is an intention to deactivate.
			'status_callback'            => '', // A callback function to check the activation status.
			'status_message'             => '', // An optional message to display beneath the key input.
			'button_label_activated'     => __( 'Remove Key', 'affiliate-wp' ),
			'button_label_deactivated'   => __( 'Verify Key', 'affiliate-wp' ),
			'activation_message'         => __( 'Your license was successfully activated.', 'affiliate-wp' ),
			'deactivation_message'       => __( 'You have deactivated the key from this site successfully.', 'affiliate-wp' ),
			'activation_error_message'   => __( 'Sorry, but the key you entered is not valid. Please try again', 'affiliate-wp' ),
			'deactivation_error_message' => __( 'An error occurred while trying to deactivate your license. Please try again', 'affiliate-wp' ),
			'invalid_message'            => __( 'Please enter a valid key.', 'affiliate-wp' ),
			'settings_to_watch'          => [],
		];
	}
}
