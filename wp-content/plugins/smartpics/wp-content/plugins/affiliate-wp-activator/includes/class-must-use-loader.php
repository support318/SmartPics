<?php

namespace Tomi\AffiliateWpActivator;

class Must_Use_Loader {

	protected $mu_status;
	protected $mu_option;
	protected $uc_prefix;
	protected $full_slug;

	/** @noinspection PhpStatementHasEmptyBodyInspection */
	public function __construct( $full_slug, $prefix ) {

		$this->full_slug = $full_slug;
		$this->mu_option = strtolower( $prefix ) . '-mu-mode';
		$this->uc_prefix = strtoupper( str_replace( '-', '_', $prefix ) );

		// Register hooks
		if ( is_admin() ) {
			// add_action( 'admin_notices', array( $this, 'admin_notice_show' ) );
		}

		// Ensure the plugin is loaded
		/** @noinspection PhpUnusedLocalVariableInspection */
		$output = $this->mu_check();
	}

	/**
	 * Check the status of the must-use plugin and return an array
	 * @noinspection PhpStatementHasEmptyBodyInspection
	 */
	public function mu_check(): array {

		$this->mu_status = '';

		if ( defined( $this->uc_prefix . '_MODE_INIT' ) && ( $this->uc_prefix . '_MODE_INIT' ) ) {
			$this->mu_status = 'set';
		}

		if ( defined( $this->uc_prefix . '_MODE' ) && ( $this->uc_prefix . '_MODE' ) ) {
			$this->mu_status = 'loaded';
		}

		$instruction = 'Please update "Load before other plugins" option in "Settings" tab . ';

		$output = array();

		if ( get_option( $this->mu_option ) ) {

			if ( $this->mu_status === 'loaded' ) {
				// already loaded
			} elseif ( $this->mu_status === 'set' ) {
				$output['status']  = 'error';
				$output['message'] = '"Load before other plugins" option enabled but not loaded . ' . ' ' . $instruction;
			} else {
				$set = $this->mu_set();
				if ( $set['status'] === 'error' ) {
					update_option( $this->mu_option, 0 );
					$output['status']  = 'error';
					$output['message'] = 'Error setting "Load before other plugins" option . ' . ' ' . $instruction;
				}
			}
		} else {
			if ( $this->mu_status === 'set' || $this->mu_status === 'loaded' ) {
				$removed = $this->mu_remove();
				if ( $removed['status'] === 'error' ) {
					$output['status']  = 'error';
					$output['message'] = '"Load before other plugins" option is not disabled . ' . ' ' . $instruction;
				}
			}
		}

		if ( empty( $output ) ) {
			$output['status']  = 'ok';
			$output['message'] = '"Load before other plugins" option works as expected';
		}

		return $output;
	}

	/**
	 * Set the must-use plugin
	 */
	public function mu_set(): array {

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'mu-' . basename( $this->full_slug );

		$output = array();

		if ( ! is_dir( WPMU_PLUGIN_DIR ) && ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
			$output['status']  = 'error';
			$output['message'] = 'Error creating MU plugins directory . ';

			return $output;
		}

		if ( file_exists( $dest ) && ! wp_delete_file( $dest ) ) {
			$output['status']  = 'error';
			$output['message'] = 'Error deleting old MU file';

			return $output;
		}

		$data = $this->mu_write();

		if ( false === file_put_contents( $dest, $data ) ) {
			$output['status']  = 'error';
			$output['message'] = 'Error copying plugin file to MU plugins directory . ';

			return $output;
		}

		$output['status']  = 'ok';
		$output['message'] = 'MU plugin file set . ';

		return $output;
	}

	protected function mu_write() {

		$output = array();

		// Load the template content from the file
		$template_file = PN_B40560_DIR . '/includes/stubs/mu-template.txt';

		if ( ! file_exists( $template_file ) ) {
			$output['status']  = 'error';
			$output['message'] = 'MU template file is missing';

			return $output;
		}

		$template_content = file_get_contents( $template_file );

		// Define replacements (no regex here)
		$replacements = [
			'{{PREFIX}}' => $this->uc_prefix,
			'{{PLUGIN}}' => $this->full_slug
		];

		// Perform the replacements
		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template_content );
	}

	/**
	 * Remove the must-use plugin
	 */
	public function mu_remove(): array {

		$dest = trailingslashit( WPMU_PLUGIN_DIR ) . 'mu-' . basename( $this->full_slug );

		$output = array();

		if ( file_exists( $dest ) && ! wp_delete_file( $dest ) ) {
			$output['status']  = 'error';
			$output['message'] = 'Error deleting old MU file';

			return $output;
		}

		$output['status']  = 'ok';
		$output['message'] = 'MU file deleted. ';

		return $output;
	}

	/**
	 * Show admin notice if required
	 * @noinspection PhpUnused
	 */
	public function admin_notice_show() {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen->id === 'plugins' ) {
			$check = $this->mu_check();
			if ( $check['status'] === 'error' ) {
				echo '<div class="notice notice-warning is-dismissible">' . '<p>' . esc_html( $check['message'] ) . '</p>' . '</div>';
			}
		}
	}
}