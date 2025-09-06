<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WPDM_USER_DOWNLOADS_FILE
 *
 * @package Uncanny_Automator_Pro
 */
class WPDM_USER_DOWNLOADS_FILE {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( '\Uncanny_Automator\Wp_Download_Manager_Helpers' ) ) {
			return;
		}
		$this->setup_trigger();
		$this->set_helper( new \Uncanny_Automator\Wp_Download_Manager_Helpers() );

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WPDM' );
		$this->set_trigger_code( 'USER_DOWNLOADS_FILE_CODE' );
		$this->set_trigger_meta( 'SPECIFIC_FILE_DOWNLOADED_META' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_action_args_count( 1 );

		/* Translators: Trigger sentence - WP Download Manager */
		$this->set_sentence( sprintf( esc_html_x( 'A user downloads {{a file:%1$s}}', 'Wp Download Manager', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );

		/* Translators: Trigger sentence - WP Download Manager */
		$this->set_readable_sentence( esc_html_x( 'A user downloads {{a file}}', 'Wp Download Manager', 'uncanny-automator-pro' ) ); // Non-active state sentence to show

		$this->set_action_hook( 'wpdm_onstart_download' );
		//      $this->set_action_hook( 'after_download' );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->get_all_wpmd_files( $this->get_trigger_meta(), true ),
				),
			)
		);

	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		$is_valid = false;
		if ( isset( $args[0] ) ) {
			$is_valid = true;
		}

		return $is_valid;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check downloaded file against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $package ) = $args[0];

		// Get package ID.
		$file = $package['ID'];

		// Find the text in email subject
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $file ) )
					->format( array( 'intval' ) )
					->get();
	}

}
