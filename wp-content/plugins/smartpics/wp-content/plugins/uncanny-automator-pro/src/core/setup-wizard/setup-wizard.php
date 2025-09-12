<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
namespace Uncanny_Automator_Pro\Setup_Wizard;

use Exception;
use Uncanny_Automator\Automator_Load;
use Uncanny_Automator_Pro\Licensing;
use Uncanny_Automator_Pro\Utilities;

class Setup_Wizard {

	protected $path = '';

	/**
	 * @var \Uncanny_Automator_Pro\Licensing
	 */
	protected $licensing = null;

	/**
	 * The step ID of setup wizard.
	 *
	 * @var string
	 */
	protected $step_id = 'step-1.php';

	/**
	 * The enqueue_script ID.
	 *
	 * @var string
	 */
	public static $script_handler = 'automator_setup_wizard';

	/**
	 * The nonce key.
	 *
	 * @var string
	 */
	private $nonce_key = 'automator_setup_wizard_nonce';

	/**
	 * Setup wizard URL path.
	 *
	 * @var string
	 */
	protected $url_path = '';

	/**
	 * Setups the path and url_path.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->path     = UAPro_ABSPATH . 'src/core/setup-wizard/views';
		$this->url_path = plugins_url( 'setup-wizard/js/setup-wizard.js', __DIR__ );
	}

	/**
	 * Register require hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {

		add_action( 'wp_ajax_automator_setup_wizard_license_handler', array( $this, 'automator_setup_wizard_license_handler' ), 10, 1 );

		if ( is_admin() && 'uncanny-automator-setup-wizard' === filter_input( INPUT_GET, 'page' ) ) {
			add_filter( 'automator_setup_wizard_view_path', array( $this, 'overwrite_step_1_template_path' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		}

	}

	/**
	 * Register the script for the setup wizard.
	 *
	 * @return void
	 */
	public function register_scripts() {

		wp_register_script( self::$script_handler, $this->url_path, array(), AUTOMATOR_PRO_PLUGIN_VERSION, true );

		$params = array(
			'messageSuccess' => esc_html__( 'License has been activated. Redirecting in few seconds.', 'uncanny-automator-pro' ),
			'endpoint'       => admin_url( 'admin-ajax.php?action=automator_setup_wizard_license_handler' ),
			'nonce'          => wp_create_nonce( $this->nonce_key ),
		);

		wp_localize_script( self::$script_handler, 'automatorSetupWizard', $params );

	}

	/**
	 * Callback method to 'wp_ajax_automator_setup_wizard_license_handler'.
	 *
	 * @return void
	 */
	public function automator_setup_wizard_license_handler() {

		$json_input = file_get_contents( 'php://input' );
		$data       = json_decode( $json_input, true );

		$nonce   = $data['nonce'] ?? '';
		$license = $data['license'] ?? '';

		if ( empty( $license ) ) {
			wp_send_json(
				array(
					'message' => 'License key is empty.',
				),
				401
			);
		}

		if ( ! wp_verify_nonce( $nonce, $this->nonce_key ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'message' => 'Authentication Failed: Your session has expired or you lack the necessary privileges to perform this action. Please refresh the page and try again.',
				),
				401
			);
		}

		try {

			$license_handler_response = $this->license_handler( $license );

			if ( is_wp_error( $license_handler_response ) ) {
				wp_send_json(
					array(
						'message' => $license_handler_response->get_error_message(),
					),
					$license_handler_response->get_error_code()
				);
			}

			if ( true !== $license_handler_response ) {

				$message = 'Unexpected error has occured while validating license';

				if ( is_iterable( $license_handler_response ) ) {
					$message .= ': ' . wp_json_encode( $message );
				}

				wp_send_json(
					array(
						'message' => $message,
					)
				);
			}

			wp_send_json( $license_handler_response );

		} catch ( Exception $e ) {
			wp_send_json( $e->getMessage(), $e->getCode() );
		}

	}

	/**
	 * Overwrites the `step-1.php` path of setup wizard.
	 *
	 * @param string $path
	 *
	 * @return string The path of the view file.
	 */
	public function overwrite_step_1_template_path( $path ) {

		if ( basename( $path ) === $this->step_id ) {
			$path = $this->path . '/step-1.php';
		}

		if ( false !== strpos( $path, 'setup-wizard/src/views/step-2.php' ) ) {
			$path = $this->path . '/step-2.php';
		}

		return $path;
	}

	/**
	 * Handles license validation and storing.
	 *
	 * @return true|WP_Error
	 *
	 * @throws Exception
	 */
	public function license_handler( $license_key ) {

		// Check if Free method exists.
		if ( class_exists( '\Uncanny_Automator\Admin_Menu' ) && method_exists( '\Uncanny_Automator\Admin_Menu', 'licensing_call' ) ) {

			$utils           = Utilities::get_instance();
			$should_redirect = false; // Prevents redirecting.

			$this->licensing = $utils::get_class_instance( 'Uncanny_Automator_Pro\Licensing' );

			return $this->licensing->edd_activate_license( $license_key, $should_redirect );

		}

		throw new Exception( 'Unsupported operation', 400 );

	}
}
