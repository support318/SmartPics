<?php

namespace Uncanny_Automator_Pro;

use ElementorPro\Modules\Popup\Module;

/**
 * Class ELEM_SHOWPOPUP_A
 *
 * @package Uncanny_Automator_Pro
 */
class ELEM_SHOWPOPUP_A {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'ELEM';

	private $action_code = 'ELEMSHOWPOPUP';

	private $action_meta = 'ELEMPOPUP';

	/**
	 * Class construct.
	 *
	 * Defines and adds popup script to footer.
	 */
	public function __construct() {
		if ( ! class_exists( '\ElementorPro\Modules\Popup\Module' ) ) {
			return;
		}
		$this->define_action();

		add_action( 'wp_head', array( $this, 'show_popup' ) );

	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/elementor/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'requires_user'      => false,
			/* translators: Action sentence */
			'sentence'           => sprintf( __( 'Show {{a popup:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action sentence */
			'select_option_name' => esc_html__( 'Show {{a popup}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'process_popup' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );

	}

	/**
	 * Load options.
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options' => array(
				Automator()->helpers->recipe->elementor->options->pro->all_elementor_popups( null, $this->action_meta ),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );

	}

	/**
	 * Shows Elementor Popup.
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @return void
	 */
	public function process_popup( $user_id, $action_data, $recipe_id, $args ) {

		if ( isset( $action_data['meta'][ $this->action_meta ] ) ) {

			$popup_id = absint( $action_data['meta'][ $this->action_meta ] );

			$generated = $this->generate_popup_hash( $popup_id );

			if ( $generated ) {
				return Automator()->complete->action( $user_id, $action_data, $recipe_id );
			}

			$action_data['complete_with_errors'] = true;

			return Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_html__( 'Error: Unable to determine an IP address.', 'uncanny-automator-pro' ) );

		}

	}

	/**
	 * Shows the popup to the user owning that IP address.
	 *
	 * @return bool True if popup was proccessed. Otherwise, false.
	 */
	public function show_popup() {

		if ( empty( $this->get_ip_address() ) ) {
			return false;
		}

		$hash = 'automator-popup-hash-' . md5( $this->get_ip_address() );

		$existing_popups = automator_pro_get_option( $hash, false );

		// Early bail if empty.
		if ( empty( $existing_popups ) || ! is_array( $existing_popups ) ) {
			return false;
		}

		foreach ( $existing_popups as $popup_id ) {
			Module::add_popup_to_location( $popup_id ); //insert the popup to the current page
			?>
			<script>
				jQuery(window).on('elementor/frontend/init', function () { //wait for elementor to load
					elementorFrontend.on('components:init', function () { //wait for elementor pro to load
						setTimeout(() => {
							elementorProFrontend.modules.popup.showPopup({id: <?php echo esc_js( absint( $popup_id ) ); ?> });
						}, 500); // Delay half a second. Elementor document doesn't fully load sometimes.
					});
				});
			</script>
			<?php
		}

		// Delete the option.
		automator_pro_delete_option( $hash );

		return true;

	}

	/**
	 * Generates popup hash that can be used later on.
	 *
	 * @param int $popup_id The popup ID.
	 *
	 * @return bool False if cannot get IP Address or update has failed. Otherwise, true.
	 */
	private function generate_popup_hash( $popup_id ) {

		if ( empty( $this->get_ip_address() ) ) {
			return false;
		}

		$existing_popup[] = $popup_id;

		$popup_hash = md5( $this->get_ip_address() );

		return automator_pro_update_option( 'automator-popup-hash-' . $popup_hash, $existing_popup, true );
	}

	/**
	 * Returns the IP Address of the user.
	 *
	 * @return bool|string The IP Address. Otherwise, false.
	 */
	private function get_ip_address() {

		// Whether ip is from the share internet.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}

		// Whether ip is from the proxy.
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			// Whether ip is from the remote address.
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return false;

	}

}
