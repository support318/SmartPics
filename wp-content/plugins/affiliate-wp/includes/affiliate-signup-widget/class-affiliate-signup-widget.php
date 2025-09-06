<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Affiliate Signup Widget class.
 *
 * @since 2.18.0
 */
class Affiliate_Signup_Widget {

	/**
	 * The ID of the affiliate widget container.
	 *
	 * @var string
	 */
	private $widget_container_class = 'affiliate-signup';

	/**
	 * Admin constructor.
	 *
	 * @since 2.18.0
	 */
	public function __construct() {
		// Display widget preview in admin.
		add_action( 'affwp_admin_affiliate_signup_widget_preview', [ $this, 'display' ] );

		// Register the customer as an affiliate.
		add_action( 'wp_ajax_affiliate_signup', [ $this, 'affiliate_signup' ] );
		add_action( 'wp_ajax_nopriv_affiliate_signup', [ $this, 'affiliate_signup' ] );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 10, 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ], 10, 1 );

		/**
		 * WooCommerce
		 */
		if ( class_exists( 'WooCommerce' ) ) {
			// Add the widget to the WooCommerce My Account page.
			add_action( 'woocommerce_account_dashboard', [ $this, 'maybe_display_account_dashboard' ] );

			// Maybe display widget on WooCommerce order confirmation page.
			add_action( 'woocommerce_thankyou', [ $this, 'maybe_display_order_confirmation' ] );

			// Maybe display widget on WooCommerce order details page.
			add_action( 'woocommerce_after_order_details', [ $this, 'maybe_display_order_details' ] );
		}
	}

	/**
	 * Display the widget on the WooCommerce purchase confirmation page.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @since 2.18.0
	 */
	public function maybe_display_order_confirmation( $order_id ) {
		$this->maybe_display_widget();
	}

	/**
	 * Hooks onto the woocommerce_after_order_details action to maybe display the widget.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 *
	 * @since 2.18.0
	 */
	public function maybe_display_order_details( $order ) {
		// Check if on the My Account page's Order details pages.
		if ( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) {
			$this->maybe_display_widget();
		}
	}

	/**
	 * Display the widget on the WooCommerce My Account Dashboard tab.
	 *
	 * @since 2.18.0
	 */
	public function maybe_display_account_dashboard() {
		return $this->maybe_display_widget();
	}

	/**
	 * Calculate how many hours since the affiliate registered.
	 *
	 * @since 2.18.0
	 */
	private function hours_since_registration() {
		$date_registered = affwp_get_affiliate_date_registered( affwp_get_affiliate_id() );

		$now                      = new DateTime();
		$registration_date        = new DateTime( $date_registered );
		$interval                 = $now->diff( $registration_date );
		$hours_since_registration = $interval->h + ( $interval->days * 24 );

		return $hours_since_registration;
	}

	/**
	 * Maybe show the widget.
	 *
	 * @since 2.18.0
	 */
	public function maybe_display_widget() {

		if ( 'affiliate_signup_widget' !== affiliate_wp()->settings->get( 'additional_registration_modes' ) ) {
			return;
		}

		if ( is_user_logged_in() && affwp_is_affiliate() ) {

			// Prevent widget from showing for any other registration method.
			if ( 'affiliate_signup_widget' !== affwp_get_affiliate_meta( affwp_get_affiliate()->affiliate_id, 'registration_method', true ) ) {
				return;
			}

			/**
			 * If an affiliate has been registered for less than 24 hours, keep
			 * showing the widget. The confirmation view will be shown.
			 */
			if ( $this->hours_since_registration() < 24 ) {
				return $this->display();
			}

			// Otherwise, don't show it.
			return;
		}

		// Check if the user has at least one completed order.
		if ( is_user_logged_in() && function_exists( 'wc_get_orders' ) ) {
			$user_id          = get_current_user_id();
			$completed_orders = wc_get_orders(
				[
					'customer' => $user_id,
					'status'   => 'completed',
					'return'   => 'ids',
					'limit'    => 1, // Limit to one to improve performance as we only need to know if there is at least one order.
				]
			);

			if ( empty( $completed_orders ) ) {
				return;  // No completed orders, so don't show the widget.
			}
		}

		// If all conditions pass, display the widget.
		return $this->display();
	}

	/**
	 * Generate the shades.
	 *
	 * @since 2.18.0
	 *
	 * @param string $brand_color The brand color.
	 */
	public function generate_shades( $brand_color ) {
		// Calculate luminance.
		list( $r, $g, $b ) = sscanf( $brand_color, '#%02x%02x%02x' );
		$luminance         = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b ) / 255;

		// Determine whether the color is dark or light.
		$isDark = $luminance < 0.5;

		// Generate shades based on whether the color is dark or light.
		$shades = $isDark ? $this->generate_dark_shades( $brand_color ) : $this->generate_light_shades( $brand_color );

		$cssVariables = '';
		foreach ( $shades as $key => $value ) {
			$cssVariables .= "--brand-{$key}: {$value}; ";
		}

		return $cssVariables;
	}

	/**
	 * Generate dark shades.
	 *
	 * @since 2.18.0
	 *
	 * @param string $color The brand color.
	 */
	private function generate_dark_shades( $color ) {
		return [
			'100' => "color-mix(in srgb, $color 15%, white 85%)",
			'105' => "color-mix(in srgb, $color 15%, white 85%)",
			'110' => "color-mix(in srgb, $color 60%, white 40%)",
			'115' => "color-mix(in srgb, $color 70%, white 30%)",
			'120' => "color-mix(in srgb, $color 10%, white 90%)",
			'125' => "color-mix(in srgb, $color 10%, white 90%)",
			'130' => "color-mix(in srgb, $color 10%, white 90%)",
			'200' => "color-mix(in srgb, $color 15%, white 85%)",
			'205' => "color-mix(in srgb, $color 15%, white 85%)",
			'210' => "color-mix(in srgb, $color 10%, white 90%)",
			'215' => "color-mix(in srgb, $color 10%, white 90%)",
			'220' => "color-mix(in srgb, $color 30%, black 70%)",
			'225' => "color-mix(in srgb, $color 10%, white 90%)",
			'230' => "color-mix(in srgb, $color 70%, black 30%)",
			'235' => "color-mix(in srgb, $color 90%, black 10%)",
			'240' => "color-mix(in srgb, $color 10%, white 90%)",
			'300' => "color-mix(in srgb, $color 10%, white 90%)",
			'305' => "color-mix(in srgb, $color 10%, white 90%)",
			'500' => $color,
		];
	}

	/**
	 * Generate light shades.
	 *
	 * @since 2.18.0
	 *
	 * @param string $color The brand color.
	 */
	private function generate_light_shades( $color ) {
		return [
			'100' => "color-mix(in srgb, $color 30%, black 70%)",
			'105' => "color-mix(in srgb, $color 30%, black 70%)",
			'110' => "color-mix(in srgb, $color 30%, black 70%)",
			'115' => "color-mix(in srgb, $color 30%, black 70%)",
			'120' => "color-mix(in srgb, $color 10%, white 90%)",
			'125' => "color-mix(in srgb, $color 30%, black 70%)",
			'130' => "color-mix(in srgb, $color 30%, black 70%)",
			'200' => "color-mix(in srgb, $color 30%, black 70%)",
			'205' => "color-mix(in srgb, $color 30%, black 70%)",
			'210' => "color-mix(in srgb, $color 10%, white 90%)",
			'215' => "color-mix(in srgb, $color 10%, white 90%)",
			'216' => "color-mix(in srgb, $color 80%, black 20%)",
			'220' => "color-mix(in srgb, $color 30%, black 70%)",
			'225' => "color-mix(in srgb, $color 10%, white 90%)",
			'230' => "color-mix(in srgb, $color 20%, black 80%)",
			'235' => "color-mix(in srgb, $color 30%, black 70%)",
			'240' => "color-mix(in srgb, $color 10%, white 90%)",
			'300' => "color-mix(in srgb, $color 30%, black 70%)",
			'305' => "color-mix(in srgb, $color 30%, black 70%)",
			'500' => $color,
		];
	}

	/**
	 * Get host styles.
	 *
	 * @since 2.18.0
	 *
	 * @return string
	 */
	public function get_host_styles() {
		$brand_color = affiliate_wp()->settings->get( 'affiliate_signup_widget_brand_color', '#4b64e2' );

		// Get the CSS variables string for the brand color
		$css_variables = $this->generate_shades( $brand_color );

		// Build the output string
		$output  = '<style id="brand-colors">:host {';
		$output .= $css_variables;
		$output .= '}</style>';

		return $output;
	}

	/**
	 * Get the data for the widget.
	 *
	 * @since 2.18.0
	 *
	 * @return array
	 */
	public function data() {
		$image                = affiliate_wp()->settings->get( 'affiliate_signup_widget_image', '' );
		$heading              = affiliate_wp()->settings->get( 'affiliate_signup_widget_heading_text', __( 'Earn with every referral!', 'affiliate-wp' ) );
		$text                 = affiliate_wp()->settings->get( 'affiliate_signup_widget_text', __( 'Join our affiliate program and earn commission on every sale you refer', 'affiliate-wp' ) );
		$button_text          = affiliate_wp()->settings->get( 'affiliate_signup_widget_button_text', __( 'Start Earning Today', 'affiliate-wp' ) );
		$button_text_loading  = __( 'Signing up...', 'affiliate-wp' );
		$button_color         = affiliate_wp()->settings->get( 'affiliate_signup_widget_button_color', '#ffffff' );
		$button_copy_text     = __( 'Copy', 'affiliate-wp' );
		$button_copied_text   = __( 'Link Copied!', 'affiliate-wp' );
		$button_id            = is_admin() ? 'signup-affiliate-preview' : 'signup-affiliate';
		$confirmation_heading = affiliate_wp()->settings->get( 'affiliate_signup_widget_confirmation_heading_text', 'Congrats, you\'re in! Start earning now' );
		$confirmation_text    = affiliate_wp()->settings->get( 'affiliate_signup_widget_confirmation_text', __( 'Share your affiliate link below with friends. When they buy, you earn!', 'affiliate-wp' ) );
		$terms_of_use_page_id = affiliate_wp()->settings->get( 'terms_of_use', '' );
		$error_heading        = __( 'Oops! We\'re Sorry', 'affiliate-wp' );
		$error_text           = __( 'There was a problem registering your affiliate account. Please try again later or contact our team.', 'affiliate-wp' );

		$affiliate = is_user_logged_in() && affwp_is_affiliate();

		return [
			'image'                => $image,
			'heading'              => $heading,
			'text'                 => nl2br( $text ),
			'button_text'          => $button_text,
			'button_text_loading'  => $button_text_loading,
			'button_color'         => $button_color,
			'button_id'            => $button_id,
			'terms_of_use_page_id' => $terms_of_use_page_id,
			'confirmation_heading' => $confirmation_heading,
			'confirmation_text'    => nl2br( $confirmation_text ),
			'button_copy_text'     => $button_copy_text,
			'button_copied_text'   => $button_copied_text,
			'affiliate_link'       => $affiliate ? $this->get_affiliate_link( affwp_get_affiliate_id() ) : '{affiliateLink}',
			'display_confirmation' => $affiliate && $this->hours_since_registration() < 24 ? true : false,
			'error_text'           => $error_text,
			'error_heading'        => $error_heading,
		];
	}

	/**
	 * Enqueue scripts within admin.
	 *
	 * @since 2.18.0
	 */
	public function enqueue_admin_scripts() {

		// Determine if user is on the Settings -> Affiliates page.
		$is_settings_affiliates_page = isset( $_GET['page'] ) && 'affiliate-wp-settings' === sanitize_text_field( $_GET['page'] ) && isset( $_GET['tab'] ) && 'affiliates' === sanitize_text_field( $_GET['tab'] );

		if ( ! $is_settings_affiliates_page ) {
			return;
		}

		// Only enqueue if WooCommerce integration is enabled.
		if ( ! affwp_is_integration_active( 'woocommerce' ) ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'affiliate-signup-widget-admin', AFFILIATEWP_PLUGIN_URL . 'assets/js/admin-affiliate-signup-widget' . $suffix . '.js', [], AFFILIATEWP_VERSION, true );

		$admin_script_data = [
			'affiliateLinkPreview' => $this->affiliate_link_preview(),
		];

		$data = array_merge( $admin_script_data, $this->get_script_data() );

		wp_localize_script( 'affiliate-signup-widget-admin', 'affiliateWidgetParams', $data );
	}

	/**
	 * Determine if scripts should be enqueued.
	 *
	 * @since 2.18.0
	 */
	private function should_enqueue_scripts() {
		$enqueue = false;

		// Check for WooCommerce pages.
		if (
			class_exists( 'WooCommerce' ) &&
			(
				is_order_received_page() || // Purchase confirmation page.
				( is_account_page() && ! is_wc_endpoint_url() ) || // Dashboard tab only.
				( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) // Order details page only.
			)
		) {
			$enqueue = true;
		}

		return $enqueue;
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 2.18.0
	 */
	public function enqueue_frontend_scripts() {

		if ( ! $this->should_enqueue_scripts() ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'affiliate-signup-widget', AFFILIATEWP_PLUGIN_URL . 'assets/js/affiliate-signup-widget' . $suffix . '.js', [], AFFILIATEWP_VERSION, true );

		wp_localize_script( 'affiliate-signup-widget', 'affiliateWidgetParams', $this->get_script_data() );
	}

	/**
	 * Prepares the script data for use in various places.
	 *
	 * @since 2.18.0
	 */
	protected function get_script_data() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$data   = $this->data();
		extract( $data );
		ob_start();

		echo $this->get_host_styles();
		include AFFILIATEWP_PLUGIN_DIR . 'includes/affiliate-signup-widget/template/index.php';
		$widget_html = ob_get_clean();

		return [
			'cssUrl'     => AFFILIATEWP_PLUGIN_URL . 'assets/css/affiliate-signup-widget' . $suffix . '.css',
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'widgetHtml' => $widget_html,
			'data'       => $data,
		];
	}

	/**
	 * Admin link preview.
	 *
	 * @since 2.18.0
	 * @todo The demo link should check pretty referral URLs so it's more accurate.
	 */
	public function affiliate_link_preview() {

		if ( affwp_is_affiliate() ) {
			$link = esc_url( urldecode( affwp_get_affiliate_referral_url( [ 'affiliate_id' => affwp_is_affiliate() ] ) ) );
		} else {
			// Create a demo link
			$link = esc_url( add_query_arg( affiliate_wp()->tracking->get_referral_var(), '123', home_url( '/' ) ) );
		}

		return $link;
	}

	/**
	 * Display the Affiliate Signup Widget.
	 *
	 * @since 2.18.0
	 */
	public function display( $order_id = '' ) {
		if ( is_admin() ) : ?>
		<div class="affwp-toggle">
			<label class="affwp-toggle__switch">
				<span class="affwp-toggle__label-text">
					<?php _e( 'Preview Affiliate Signup Confirmation', 'affiliate-wp' ); ?>
				</span>
				<input type="checkbox" id="viewToggle" class="affwp-toggle__input">
				<span class="affwp-toggle__slider affwp-toggle__slider--round"></span>
			</label>
		</div>
		<?php endif; ?>

		<div class="<?php echo esc_attr( $this->widget_container_class ); ?>"></div>
		<?php
	}

	/**
	 * Get the order ID from the order key.
	 *
	 * @since 2.18.0
	 */
	private function get_order_id_by_order_key( $order_key ) {
		// Get an instance of the WC_Order_Data_Store
		$order_data_store = WC_Data_Store::load( 'order' );

		// Use the data store to get the order ID from the order key
		$order_id = $order_data_store->get_order_id_by_order_key( $order_key );

		return $order_id;
	}

	/**
	 * Get an affiliate's link.
	 *
	 * @since 2.18.0
	 */
	private function get_affiliate_link( $affiliate_id ) {
		return esc_url( urldecode( affwp_get_affiliate_referral_url( [ 'affiliate_id' => $affiliate_id ] ) ) );
	}

	/**
	 * Affiliate Signup.
	 *
	 * @since 2.18.0
	 */
	public function affiliate_signup() {

		// Access the post ID from the form data.
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		// Get the order key from the query string.
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( $_POST['order_key'] ) : '';

		if ( $order_key && function_exists( 'wc_get_order' ) ) {
			$order_id = $this->get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );

			// Check if the order exists and it's a guest order.
			if ( $order && ! $order->get_user_id() ) {
				$guest_email      = $order->get_billing_email();
				$guest_first_name = $order->get_billing_first_name();
				$guest_last_name  = $order->get_billing_last_name();
			}
		}

		// Guest purchases.
		if ( ! is_user_logged_in() ) {
			$new_user = true;

			$user_email = $guest_email;
			$user_login = $user_email;
			$user_pass  = wp_generate_password( 24 );

			$user_first = $guest_first_name;
			$user_last  = $guest_last_name;

			$random_pass = true;

			$args = [
				'user_login'   => $user_email,
				'user_email'   => $user_email,
				'user_pass'    => $user_pass,
				'first_name'   => $user_first,
				'last_name'    => $user_last,
				'display_name' => trim( $user_first . ' ' . $user_last ),
			];

			$user_id = wp_insert_user( $args );

			if ( $random_pass ) {
				// Remember that we generated the password for the user.
				update_user_meta( $user_id, 'affwp_generated_pass', true );
			}
		} else {
			// Logged in users.
			$new_user = false;
			$user_id  = get_current_user_id();
			$user     = (array) get_userdata( $user_id );

			if ( isset( $user['data'] ) ) {
				$args = (array) $user['data'];
			} else {
				$args = [];
			}
		}

		$status = 'active';

		affwp_add_affiliate(
			[
				'user_id'             => $user_id,
				'status'              => $status,
				'dynamic_coupon'      => 1,
				'registration_method' => 'affiliate_signup_widget',
				'registration_url'    => esc_url_raw( get_permalink( $post_id ) ),
			]
		);

		if ( ! is_user_logged_in() ) {
			$this->log_user_in( $user_id, $user_login );
		}

		// Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		if ( true === $new_user ) {
			// Enable referral notifications by default for new users.
			affwp_update_affiliate_meta( $affiliate_id, 'referral_notifications', true );
		}

		/**
		 * Fires immediately after registering a user.
		 *
		 * @since 2.18.0
		 *
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $status       Affiliate status.
		 * @param array  $args         Data arguments used when registering the user.
		 */
		do_action( 'affwp_register_user', $affiliate_id, $status, $args );

		$affiliate_link = $this->get_affiliate_link( $affiliate_id );

		wp_send_json_success( [ 'affiliate_link' => $affiliate_link ] );
	}

	/**
	 * Logs the user in.
	 * Duplicate of log_user_in() from class-register.php.
	 *
	 * @since 2.18.0
	 * @todo Move to separate class so we can re-use it
	 *
	 * @param  $user_id    The user ID.
	 * @param  $user_login The `user_login` for the user.
	 * @param  $remember   Whether or not the browser should remember the user login.
	 */
	private function log_user_in( $user_id = 0, $user_login = '', $remember = false ) {

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		wp_set_auth_cookie( $user_id, $remember );
		wp_set_current_user( $user_id, $user_login );

		/**
		 * The `wp_login` action is fired here to maintain compatibility and stability of
		 * any WordPress core features, plugins, or themes hooking onto it.
		 *
		 * @param  string   $user_login The `user_login` for the user.
		 * @param  stdClass $user       The user object.
		 */
		do_action( 'wp_login', $user_login, $user );
	}
}
new Affiliate_Signup_Widget();
