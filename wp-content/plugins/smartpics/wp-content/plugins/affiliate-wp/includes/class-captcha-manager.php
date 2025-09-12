<?php
/**
 * CAPTCHA Manager
 *
 * Unified management for all CAPTCHA providers (reCAPTCHA, hCaptcha, Turnstile)
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.28.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unified CAPTCHA management class.
 *
 * Provides centralized functionality for all CAPTCHA providers to reduce code duplication
 * and ensure consistent behavior across registration, login, and other forms.
 *
 * @since 2.28.0
 */
class AffWP_Captcha_Manager {

	/**
	 * CAPTCHA provider configuration.
	 *
	 * @since 2.28.0
	 * @var array
	 */
	private static $providers = [
		'recaptcha' => [
			'name'         => 'Google reCAPTCHA',
			'site_key'     => 'recaptcha_site_key',
			'secret_key'   => 'recaptcha_secret_key',
			'response_key' => 'g-recaptcha-response',
			'remoteip_key' => 'g-recaptcha-remoteip',
			'verify_url'   => 'https://www.google.com/recaptcha/api/siteverify',
		],
		'hcaptcha'  => [
			'name'         => 'hCaptcha',
			'site_key'     => 'hcaptcha_site_key',
			'secret_key'   => 'hcaptcha_secret_key',
			'response_key' => 'h-captcha-response',
			'remoteip_key' => 'h-captcha-remoteip',
			'verify_url'   => 'https://hcaptcha.com/siteverify',
		],
		'turnstile' => [
			'name'         => 'Cloudflare Turnstile',
			'site_key'     => 'turnstile_site_key',
			'secret_key'   => 'turnstile_secret_key',
			'response_key' => 'cf-turnstile-response',
			'remoteip_key' => 'cf-turnstile-remoteip',
			'verify_url'   => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		],
	];

	/**
	 * Get the currently active CAPTCHA type.
	 *
	 * @since 2.28.0
	 *
	 * @return string|false The active CAPTCHA type or false if none enabled.
	 */
	public static function get_active_type() {
		$type = affiliate_wp()->settings->get( 'captcha_type', 'none' );

		if ( 'none' === $type || ! self::is_enabled( $type ) ) {
			return false;
		}

		return $type;
	}

	/**
	 * Check if a specific CAPTCHA type is enabled.
	 *
	 * @since 2.28.0
	 *
	 * @param string $type The CAPTCHA type to check (recaptcha, hcaptcha, turnstile).
	 * @return bool True if the CAPTCHA type is enabled, false otherwise.
	 */
	public static function is_enabled( $type ) {
		if ( ! isset( self::$providers[ $type ] ) ) {
			return false;
		}

		$config     = self::$providers[ $type ];
		$is_active  = ( affiliate_wp()->settings->get( 'captcha_type', 'none' ) === $type );
		$site_key   = affiliate_wp()->settings->get( $config['site_key'], '' );
		$secret_key = affiliate_wp()->settings->get( $config['secret_key'], '' );
		$enabled    = ( $is_active && ! empty( $site_key ) && ! empty( $secret_key ) );

		/**
		 * Filters whether a specific CAPTCHA type is enabled.
		 *
		 * @since 2.28.0
		 *
		 * @param bool   $enabled Whether the CAPTCHA type is enabled.
		 * @param string $type    The CAPTCHA type being checked.
		 */
		return (bool) apply_filters( "affwp_{$type}_enabled", $enabled, $type );
	}

	/**
	 * Check if any CAPTCHA provider is enabled.
	 *
	 * @since 2.28.0
	 *
	 * @return bool True if any CAPTCHA is enabled, false otherwise.
	 */
	public static function is_any_enabled() {
		return self::get_active_type() !== false;
	}

	/**
	 * Check if CAPTCHA is enabled for login forms.
	 *
	 * @since 2.28.0
	 *
	 * @return bool True if CAPTCHA is enabled for login forms.
	 */
	public static function is_login_enabled() {
		return self::is_any_enabled() && affiliate_wp()->settings->get( 'captcha_protect_login', false );
	}

	/**
	 * Get configuration for a specific CAPTCHA provider.
	 *
	 * @since 2.28.0
	 *
	 * @param string $type The CAPTCHA type.
	 * @return array|false The provider configuration or false if not found.
	 */
	public static function get_config( $type ) {
		return isset( self::$providers[ $type ] ) ? self::$providers[ $type ] : false;
	}

	/**
	 * Validate CAPTCHA response for the active provider.
	 *
	 * @since 2.28.0
	 *
	 * @param array  $data    The form data containing CAPTCHA response.
	 * @param string $context The context where validation is happening (register, login, etc).
	 * @return bool True if validation passes, false otherwise.
	 */
	public static function validate_response( $data, $context = 'general' ) {
		$active_type = self::get_active_type();

		if ( ! $active_type ) {
			return true; // No CAPTCHA enabled, validation passes.
		}

		switch ( $active_type ) {
			case 'recaptcha':
				return self::validate_recaptcha_response( $data, $context );
			case 'hcaptcha':
				return self::validate_hcaptcha_response( $data );
			case 'turnstile':
				return self::validate_turnstile_response( $data );
			default:
				return false;
		}
	}

	/**
	 * Validate reCAPTCHA response.
	 *
	 * @since 2.28.0
	 *
	 * @param array  $data    The form data.
	 * @param string $context The validation context.
	 * @return bool True if valid, false otherwise.
	 */
	private static function validate_recaptcha_response( $data, $context ) {
		$config = self::get_config( 'recaptcha' );

		if ( ! $config || empty( $data[ $config['response_key'] ] ) || empty( $data[ $config['remoteip_key'] ] ) ) {
			return false;
		}

		$request = wp_safe_remote_post(
			$config['verify_url'],
			[
				'body' => [
					'secret'   => affiliate_wp()->settings->get( $config['secret_key'] ),
					'response' => $data[ $config['response_key'] ],
					'remoteip' => $data[ $config['remoteip_key'] ],
				],
			]
		);

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		$version  = affiliate_wp()->settings->get( 'recaptcha_type', '' );

		if ( 'v3' === $version ) {
			// reCAPTCHA v3 validation.
			if ( ! isset( $response['score'] ) ) {
				return false;
			}

			// Check action if context is provided and post ID exists.
			if ( ! empty( $data['affwp_post_id'] ) ) {
				$expected_action = "affiliate_{$context}_" . $data['affwp_post_id'];
				if ( isset( $response['action'] ) && $expected_action !== $response['action'] ) {
					return false;
				}
			}

			// Check score threshold.
			$threshold = affiliate_wp()->settings->get( 'recaptcha_score_threshold', '0.4' );
			if ( floatval( $response['score'] ) <= floatval( $threshold ) ) {
				return false;
			}
		} else {
			// reCAPTCHA v2 validation.
			if ( empty( $response['success'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate hCaptcha response.
	 *
	 * @since 2.28.0
	 *
	 * @param array $data The form data.
	 * @return bool True if valid, false otherwise.
	 */
	private static function validate_hcaptcha_response( $data ) {
		$config = self::get_config( 'hcaptcha' );

		if ( ! $config || empty( $data[ $config['response_key'] ] ) || empty( $data[ $config['remoteip_key'] ] ) ) {
			return false;
		}

		$request = wp_remote_post(
			$config['verify_url'],
			[
				'body' => [
					'secret'   => affiliate_wp()->settings->get( $config['secret_key'] ),
					'response' => $data[ $config['response_key'] ],
					'remoteip' => $data[ $config['remoteip_key'] ],
				],
			]
		);

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		return ! empty( $response['success'] );
	}

	/**
	 * Validate Turnstile response.
	 *
	 * @since 2.28.0
	 *
	 * @param array $data The form data.
	 * @return bool True if valid, false otherwise.
	 */
	private static function validate_turnstile_response( $data ) {
		$config = self::get_config( 'turnstile' );

		if ( ! $config || empty( $data[ $config['response_key'] ] ) || empty( $data[ $config['remoteip_key'] ] ) ) {
			return false;
		}

		$request = wp_safe_remote_post(
			$config['verify_url'],
			[
				'body' => [
					'secret'   => affiliate_wp()->settings->get( $config['secret_key'] ),
					'response' => $data[ $config['response_key'] ],
					'remoteip' => $data[ $config['remoteip_key'] ],
				],
			]
		);

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		return ! empty( $response['success'] );
	}

	/**
	 * Get error message for failed CAPTCHA validation.
	 *
	 * @since 2.28.0
	 *
	 * @param string $type The CAPTCHA type that failed.
	 * @return string The error message.
	 */
	public static function get_error_message( $type = null ) {
		if ( ! $type ) {
			$type = self::get_active_type();
		}

		$messages = [
			'recaptcha' => [
				'v2' => __( 'Please verify that you are not a robot', 'affiliate-wp' ),
				'v3' => __( 'Google reCAPTCHA verification failed, please try again later.', 'affiliate-wp' ),
			],
			'hcaptcha'  => __( 'hCaptcha verification failed, please try again later.', 'affiliate-wp' ),
			'turnstile' => __( 'Cloudflare Turnstile verification failed, please try again later.', 'affiliate-wp' ),
		];

		if ( 'recaptcha' === $type ) {
			$version = affiliate_wp()->settings->get( 'recaptcha_type', '' );
			return $messages['recaptcha'][ $version ];
		}

		return isset( $messages[ $type ] ) ? $messages[ $type ] : __( 'CAPTCHA verification failed, please try again.', 'affiliate-wp' );
	}

	/**
	 * Render CAPTCHA field HTML for templates.
	 *
	 * Consolidates all CAPTCHA rendering logic to eliminate template code duplication.
	 * Handles all three CAPTCHA providers with proper script enqueuing and form integration.
	 *
	 * @since 2.28.0
	 *
	 * @param string $context        The form context ('register', 'login', etc).
	 * @param string $form_id_suffix Unique form ID suffix for multiple forms on same page.
	 * @param string $button_text    Text for the submit button.
	 * @return string HTML output for CAPTCHA field and submit button.
	 */
	public static function render_captcha_field( $context = 'general', $form_id_suffix = null, $button_text = 'Submit' ) {
		// Check if CAPTCHA should be rendered for this context
		if ( 'login' === $context && ! self::is_login_enabled() ) {
			// Login context but login protection disabled - show regular submit button
			return self::render_submit_button( $button_text );
		}

		if ( 'login' !== $context && ! self::is_any_enabled() ) {
			// Non-login context but no CAPTCHA enabled - show regular submit button
			return self::render_submit_button( $button_text );
		}

		$active_type = self::get_active_type();
		if ( ! $active_type ) {
			return self::render_submit_button( $button_text );
		}

		// Generate unique form ID suffix if not provided
		if ( null === $form_id_suffix ) {
			$form_id_suffix = wp_rand( 1000, 9999 );
		}

		$output = '';

		// Render CAPTCHA field based on active type
		switch ( $active_type ) {
			case 'recaptcha':
				$output .= self::render_recaptcha_field( $context, $form_id_suffix, $button_text );
				break;
			case 'hcaptcha':
				$output .= self::render_hcaptcha_field( $context, $form_id_suffix, $button_text );
				break;
			case 'turnstile':
				$output .= self::render_turnstile_field( $context, $form_id_suffix, $button_text );
				break;
		}

		return $output;
	}

	/**
	 * Render reCAPTCHA field with proper script enqueuing.
	 *
	 * @since 2.28.0
	 *
	 * @param string $context        Form context.
	 * @param string $form_id_suffix Form ID suffix.
	 * @param string $button_text    Submit button text.
	 * @return string HTML output.
	 */
	private static function render_recaptcha_field( $context, $form_id_suffix, $button_text ) {
		// Enqueue reCAPTCHA script
		affwp_enqueue_script( 'affwp-recaptcha' );

		$config   = self::get_config( 'recaptcha' );
		$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );
		$version  = affiliate_wp()->settings->get( 'recaptcha_type', '' );
		$post_id  = get_the_ID();

		// Fallback for post ID when not in proper post context
		if ( empty( $post_id ) ) {
			// Try to get from query vars
			$post_id = get_queried_object_id();

			// Still empty? Use a reliable fallback
			if ( empty( $post_id ) ) {
				// Use the current page URL hash for consistency
				$current_url = is_admin() ? admin_url( 'admin.php' ) : home_url( $_SERVER['REQUEST_URI'] ?? '' );
				$post_id     = substr( md5( $current_url ), 0, 8 ); // 8-character hash
			}
		}

		ob_start();
		?>
		<?php if ( 'v2' === $version ) : ?>
			<div class="g-recaptcha affwp-recaptcha-v2" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
		<?php endif; ?>

		<input type="hidden" name="<?php echo esc_attr( $config['remoteip_key'] ); ?>" value="<?php echo esc_attr( affiliate_wp()->tracking->get_ip() ); ?>" />

		<?php
		if ( 'v3' === $version ) :
			$callback_function = 'on' . ucfirst( $context ) . 'Submit_' . $form_id_suffix;
			$form_id           = 'affwp-' . $context . '-form-' . $form_id_suffix;
			$action_name       = 'affiliate_' . $context . '_' . $post_id;

			?>
			<input type="hidden" name="affwp_post_id" value="<?php echo esc_attr( $post_id ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $config['response_key'] ); ?>" id="<?php echo esc_attr( $config['response_key'] ); ?>-<?php echo esc_attr( $form_id_suffix ); ?>" value="" />

			<input class="button g-recaptcha"
					data-sitekey="<?php echo esc_attr( $site_key ); ?>"
					data-callback="<?php echo esc_attr( $callback_function ); ?>"
					type="submit"
					data-action="<?php echo esc_attr( $action_name ); ?>"
					value="<?php echo esc_attr( $button_text ); ?>" />

			<script>
			function <?php echo esc_js( $callback_function ); ?>(token) {
				// Primary form ID - try exact match first
				let form = document.getElementById("<?php echo esc_js( $form_id ); ?>");

				// Fallback: if exact form not found, search for any matching registration form
				if (!form) {
					const forms = document.querySelectorAll('form[id^="affwp-<?php echo esc_js( $context ); ?>-form-"]');
					if (forms.length > 0) {
						form = forms[0]; // Use the first matching form
					}
				}

				const tokenField = document.getElementById("<?php echo esc_js( $config['response_key'] ); ?>-<?php echo esc_js( $form_id_suffix ); ?>");

				if (!form || !tokenField) {
					return;
				}

				// Set the token
				tokenField.value = token;

				// Check form validity and submit
				if (form.checkValidity()) {
					form.submit();
					return;
				}

				// Reset reCAPTCHA if form validation failed
				if (typeof grecaptcha !== "undefined" && grecaptcha.reset) {
					const submitButton = form.querySelector(".g-recaptcha[data-callback=\"<?php echo esc_js( $callback_function ); ?>\"]");
					if (submitButton && submitButton.hasAttribute("data-recaptcha-widget-id")) {
						const widgetId = parseInt(submitButton.getAttribute("data-recaptcha-widget-id"));
						grecaptcha.reset(widgetId);
					}
				}

				// Show validation errors
				form.reportValidity();
			}
			</script>
		<?php else : ?>
			<?php echo self::render_submit_button( $button_text ); ?>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render hCaptcha field with proper script enqueuing.
	 *
	 * @since 2.28.0
	 *
	 * @param string $context        Form context.
	 * @param string $form_id_suffix Form ID suffix.
	 * @param string $button_text    Submit button text.
	 * @return string HTML output.
	 */
	private static function render_hcaptcha_field( $context, $form_id_suffix, $button_text ) {
		// Enqueue hCaptcha script
		affwp_enqueue_script( 'affwp-hcaptcha' );

		$config   = self::get_config( 'hcaptcha' );
		$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );

		$output  = '';
		$output .= '<div class="h-captcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
		$output .= '<input type="hidden" name="' . esc_attr( $config['remoteip_key'] ) . '" value="' . esc_attr( affiliate_wp()->tracking->get_ip() ) . '" />';
		$output .= self::render_submit_button( $button_text );

		return $output;
	}

	/**
	 * Render Turnstile field with proper script enqueuing.
	 *
	 * @since 2.28.0
	 *
	 * @param string $context        Form context.
	 * @param string $form_id_suffix Form ID suffix.
	 * @param string $button_text    Submit button text.
	 * @return string HTML output.
	 */
	private static function render_turnstile_field( $context, $form_id_suffix, $button_text ) {
		// Enqueue Turnstile script
		affwp_enqueue_script( 'affwp-turnstile' );

		$config   = self::get_config( 'turnstile' );
		$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );

		$output  = '';
		$output .= '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
		$output .= '<input type="hidden" name="' . esc_attr( $config['remoteip_key'] ) . '" value="' . esc_attr( affiliate_wp()->tracking->get_ip() ) . '" />';
		$output .= self::render_submit_button( $button_text );

		return $output;
	}

	/**
	 * Render a standard submit button.
	 *
	 * @since 2.28.0
	 *
	 * @param string $button_text Submit button text.
	 * @return string HTML output.
	 */
	private static function render_submit_button( $button_text ) {
		return '<input type="submit" class="button" value="' . esc_attr( $button_text ) . '" />';
	}

	/**
	 * Render the CAPTCHA widget.
	 *
	 * @since 2.28.0
	 *
	 * @param string $context The form context ('register', 'login', etc).
	 * @return string HTML output for CAPTCHA widget only.
	 */
	public static function render_captcha_widget( $context = 'general' ) {
		// Check if CAPTCHA should be rendered for this context.
		if ( 'login' === $context && ! self::is_login_enabled() ) {
			return '';
		}

		if ( 'login' !== $context && ! self::is_any_enabled() ) {
			return '';
		}

		$active_type = self::get_active_type();
		if ( ! $active_type ) {
			return '';
		}

		// Enqueue appropriate script.
		switch ( $active_type ) {
			case 'recaptcha':
				affwp_enqueue_script( 'affwp-recaptcha' );
				break;
			case 'hcaptcha':
				affwp_enqueue_script( 'affwp-hcaptcha' );
				break;
			case 'turnstile':
				affwp_enqueue_script( 'affwp-turnstile' );
				break;
		}

		$form_id_suffix = wp_rand( 1000, 9999 );

		ob_start();
		?>
		<?php if ( 'recaptcha' === $active_type ) : ?>
			<?php
			$config   = self::get_config( 'recaptcha' );
			$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );
			$version  = affiliate_wp()->settings->get( 'recaptcha_type', '' );
			?>
			<?php if ( 'v2' === $version ) : ?>
				<div class="g-recaptcha affwp-recaptcha-v2" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<?php endif; ?>
			<input type="hidden" name="<?php echo esc_attr( $config['remoteip_key'] ); ?>" value="<?php echo esc_attr( affiliate_wp()->tracking->get_ip() ); ?>" />
		<?php elseif ( 'hcaptcha' === $active_type ) : ?>
			<?php
			$config   = self::get_config( 'hcaptcha' );
			$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );
			?>
			<div class="h-captcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<input type="hidden" name="<?php echo esc_attr( $config['remoteip_key'] ); ?>" value="<?php echo esc_attr( affiliate_wp()->tracking->get_ip() ); ?>" />
		<?php elseif ( 'turnstile' === $active_type ) : ?>
			<?php
			$config   = self::get_config( 'turnstile' );
			$site_key = affiliate_wp()->settings->get( $config['site_key'], '' );
			?>
			<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<input type="hidden" name="<?php echo esc_attr( $config['remoteip_key'] ); ?>" value="<?php echo esc_attr( affiliate_wp()->tracking->get_ip() ); ?>" />
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
}
