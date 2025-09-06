<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Wp_Mail_Smtp_Pro_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Wp_Mail_Smtp_Pro_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 * Add_Edd_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup method
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'WPMAILSMTPPRO' );
		$this->set_name( 'WP Mail SMTP Pro' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_icon( 'wp-mail-smtp-icon.svg' );
		$this->set_plugin_file_path( 'wp-mail-smtp-pro/wp_mail_smtp.php' );
	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $this->get_plugin_file_path() );
	}

}
