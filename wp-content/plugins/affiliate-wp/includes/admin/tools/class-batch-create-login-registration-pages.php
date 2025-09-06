<?php
/**
 * Tools: Create Login and Registration Pages Batch Processor
 *
 * @package    AffiliateWP
 * @subpackage Tools
 * @copyright  Copyright (c) 2024, Awesome Motive, inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.25.0
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffWP\Utils\Batch_Process;

use AffiliateWP\Installation_Tools;
use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

#[\AllowDynamicProperties]

/**
 * Implements the batch process to create the Login and Registration pages.
 *
 * @see \AffWP\Utils\Batch_Process\Base
 * @see \AffWP\Utils\Batch_Process
 *
 * @since 2.25.0
 */
class Batch_Create_Login_Registration_Pages extends Utils\Batch_Process implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @since 2.25.0
	 * @var   string
	 */
	public $batch_id = 'create-login-registration-pages';

	/**
	 * Capability needed to perform the current batch process.
	 *
	 * @since 2.25.0
	 * @var   string
	 */
	public $capability = 'edit_pages';

	/**
	 * Holds the user's choice.
	 *
	 * @since 2.25.0
	 * @var bool
	 */
	public bool $create_pages = false;

	/**
	 * Number of items to process per step.
	 *
	 * @since 2.25.0
	 * @var   int
	 */
	public $per_step = 1;

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @since 2.25.0
	 *
	 * @param null|array $data Data to initialize.
	 *
	 * @return void
	 */
	public function init( $data = null ) : void {
		if ( 'yes' !== sanitize_text_field( $data['create_login_registration_pages_choice'] ) ) {
			return;
		}
		$this->create_pages = true;
	}

	/**
	 * Don't have a use, but it needs to be declared.
	 *
	 * @since 2.25.0
	 *
	 * @return void
	 */
	public function pre_fetch() : void {}

	/**
	 * Processes a single step (batch).
	 *
	 * @since 2.25.0
	 *
	 * @return int|string The current step, or the return message, usually "done" indicating that the process finished.
	 */
	public function process_step() {

		if ( ! $this->create_pages ) {
			return 'done';
		}

		$methods = $this->get_methods_per_step();

		if (
			isset( $methods[ $this->step ] ) &&
			method_exists( Installation_Tools::get_instance(), $methods[ $this->step ] )
		) {
			call_user_func( [ Installation_Tools::get_instance(), $methods[ $this->step ] ] );

			$this->step++;

			return $this->step;
		}

		return 'done';
	}

	/**
	 * Retrieve the list of methods to be executed per step.
	 *
	 * Each method will be executed per step, starting from step 1.
	 *
	 * @since 2.25.0
	 *
	 * @return array The methods to execute per step.
	 */
	private function get_methods_per_step() : array {
		return [
			1 => 'create_login_page',
			2 => 'create_registration_page',
		];
	}

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @since 2.25.0
	 *
	 * @param  string $code Message code.
	 *
	 * @return string Message.
	 */
	public function get_message( $code ) : string {
		return '';
	}

	/**
	 * Refresh the page if the chosen option was Yes, so user can see the changes on the Settings screen.
	 *
	 * @since 2.25.0
	 *
	 * @return string The URL to redirect or an empty string if no redirection needed.
	 */
	public function get_redirect_url() : string {

		if ( $this->create_pages ) {
			return affwp_admin_url(
				'settings',
				[
					'tab' => 'affiliates',
				]
			);
		}

		// No redirection needed.
		return '';
	}

	/**
	 * Defines logic to execute after the batch processing is complete.
	 *
	 * @since 2.25.0
	 *
	 * @param string $batch_id Batch process ID.
	 *
	 * @return void
	 */
	public function finish( $batch_id ) : void {

		affwp_set_upgrade_complete( 'upgrade_v2250_create_login_registration_pages' );

		// Clean up.
		parent::finish( $batch_id );
	}
}
