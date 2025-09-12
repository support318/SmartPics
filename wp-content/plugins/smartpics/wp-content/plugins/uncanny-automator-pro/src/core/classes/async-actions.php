<?php

namespace Uncanny_Automator_Pro;

use \Uncanny_Automator\Automator_Status;

/**
 * Class Async_Actions
 * @package Uncanny_Automator
 */
class Async_Actions {

	/**
	 * @var array
	 */
	public $actions_and_fields = array();

	/**
	 * @var
	 */
	public $args;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

		add_filter( 'automator_before_action_executed', array( $this, 'maybe_postpone_action' ), 10, 2 );

		add_filter( 'automator_get_action_completed_status', array( $this, 'change_action_completed_status' ), 10, 7 );

		add_filter( 'automator_before_action_created', array( $this, 'maybe_complete_async_action' ), 10, 7 );

		add_filter( 'automator_action_log_date_time', array( $this, 'adjust_action_log_date_time' ), 10, 2 );

		add_filter( 'automator_action_created', array( $this, 'store_async_job_id' ), 10, 1 );

		add_filter( 'automator_action_log_status', array( $this, 'action_log_status' ), 10, 2 );

		/**
		 * Originally used this cron hook. But due to 8000 $args issues with Action Scheduler,
		 * it's abandoned until they fix the issue in the library. We are now using hash method
		 * and saving the details in _options table.
		 */
		add_action( 'automator_async_run', array( $this, 'run' ) );
		/**
		 * New hook to utilize hash workaround
		 * @since 4.7
		 */
		add_action( 'automator_async_run_with_hash', array( $this, 'run_with_hash' ) );

		add_action( 'wp_ajax_cancel_async_run', array( $this, 'cancel_async_run' ) );

		/**
		 * Filter added to modify some fields during the async execution
		 */
		add_filter(
			'automator_pro_before_async_action_executed',
			array(
				$this,
				'maybe_update_parts_of_action',
			)
		);

		add_action( 'rest_api_init', array( $this, 'register_routes_for_async_actions' ), 20 );
	}

	/**
	 * @return void
	 */
	public function register_routes_for_async_actions() {

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/async_run_now/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'async_run_now' ),
				'permission_callback' => array( $this, 'save_settings_permissions' ),
			)
		);

		register_rest_route(
			AUTOMATOR_REST_API_END_POINT,
			'/async_cancel_action/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'async_cancel_action' ),
				'permission_callback' => array( $this, 'save_settings_permissions' ),
			)
		);
	}
	/**
	 * Permission callback function that let the rest API allow or disallow access
	 *
	 * @return bool|\WP_Error
	 */
	public function save_settings_permissions() {

		if ( ! $this->valid_nonce() ) {
			return false;
		}

		$capability = 'manage_options';
		$capability = apply_filters_deprecated( 'uap_roles_modify_recipe', array( $capability ), '3.0', 'automator_capability_required' );
		$capability = apply_filters( 'automator_capability_required', $capability );

		// Restrict endpoint to only users who have the edit_posts capability.
		if ( ! current_user_can( $capability ) ) {
			return new \WP_Error( 'rest_forbidden', 'You do not have the capability to save settings.', array( 'status' => 403 ) );
		}

		// This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
		$setting = true;
		$setting = apply_filters_deprecated( 'uap_save_setting_permissions', array( $setting ), '3.0', 'automator_save_setting_permissions' );

		return apply_filters( 'automator_save_setting_permissions', $setting );
	}

	/**
	 * Checks the nonce of Rest API requests
	 *
	 * @return bool
	 */
	public function valid_nonce() {

		if ( empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ), 'wp_rest' );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function async_run_now( \WP_REST_Request $request ) {

		// Make sure we have a recipe ID and the newOrder
		if ( ! $request->has_param( 'item_log_id' ) || ! $request->has_param( 'item_id' ) ) {
			$return['success'] = false;
			$return['message'] = __( 'Action or Action Log ID is empty', 'uncanny-automator-pro' );

			return new \WP_REST_Response( $return, 400 );
		}

		$action_log_id = absint( $request->get_param( 'item_log_id' ) );
		$action_id     = absint( $request->get_param( 'item_id' ) );

		$async_job_id = (int) Automator()->db->action->get_meta( $action_log_id, 'async_job_id' );
		$job_details  = self::get_action_scheduler_action_details( $async_job_id );

		if ( false === $job_details ) {
			$return['success'] = false;
			$return['message'] = __( 'Could not find the delayed action details.', 'uncanny-automator-pro' );

			return new \WP_REST_Response( $return, 400 );
		}

		if ( 'automator_async_run_with_hash' !== $job_details['hook'] ) {
			$return['success'] = false;
			$return['message'] = __( 'The action is not a delayed action.', 'uncanny-automator-pro' );

			return new \WP_REST_Response( $return, 400 );
		}

		if ( empty( $job_details['args'] ) ) {
			$return['success'] = false;
			$return['message'] = __( 'Could not find the delayed action arguments.', 'uncanny-automator-pro' );

			return new \WP_REST_Response( $return, 400 );
		}

		$hash = array_shift( $job_details['args'] );

		try {
			$this->run_with_hash( $hash );
		} catch ( \Exception $e ) {
			$return['success'] = false;
			$return['message'] = $e->getMessage();

			return new \WP_REST_Response( $return, 400 );
		}

		$return['message'] = __( 'The request has been successfully sent.', 'uncanny-automator-pro' );
		$return['success'] = true;

		/**
		 * Fires after a successful API request.
		 *
		 * @since 6.0
		 */
		do_action( 'automator_pro_delayed_action_run_now', $action_id, $action_log_id, $return );

		return new \WP_REST_Response( $return, 200 );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function async_cancel_action( \WP_REST_Request $request ) {

		// Make sure we have a recipe ID and the newOrder
		if ( ! $request->has_param( 'item_log_id' ) || ! $request->has_param( 'item_id' ) ) {
			$return['success'] = false;
			$return['message'] = __( 'Action or Action Log ID is empty', 'uncanny-automator-pro' );

			return new \WP_REST_Response( $return, 400 );
		}

		$action_log_id = absint( $request->get_param( 'item_log_id' ) );
		$action_id     = absint( $request->get_param( 'item_id' ) );

		try {
			self::cancel_job( $action_log_id, $action_id );
		} catch ( \Exception $e ) {
			$return['success'] = false;
			$return['message'] = $e->getMessage();

			return new \WP_REST_Response( $return, 400 );
		}

		$return['message'] = __( 'Action successfully cancelled.', 'uncanny-automator-pro' );
		$return['success'] = true;

		/**
		 * Fires after a successful API request.
		 *
		 * @since 6.0
		 */
		do_action( 'automator_pro_delayed_action_cancelled', $action_id, $action_log_id, $return );

		return new \WP_REST_Response( $return, 200 );
	}

	/**
	 * @param $action_id
	 *
	 * @return array|false
	 */
	public static function get_action_scheduler_action_details( $action_id ) {
		// Load the action scheduler store
		$store = \ActionScheduler::store();

		// Fetch the action by ID
		$action = $store->fetch_action( $action_id );

		// Check if the action exists
		if ( ! $action ) {
			return false; // Action not found
		}

		// Get the scheduled date, hook, and args
		$scheduled_date = $store->get_date( $action_id );
		$hook           = $action->get_hook();
		$args           = $action->get_args();
		$status         = $store->get_status( $action_id );

		// Output the details
		$details = array(
			'scheduled_date' => $scheduled_date->format( 'Y-m-d H:i:s' ),
			'hook'           => $hook,
			'args'           => $args,
			'status'         => $status,
		);

		return $details;
	}

	/**
	 * maybe_postpone_action
	 *
	 * This function will check if there is an async_mode meta set and will schedule the action is needed.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	public function maybe_postpone_action( $action, $args = array() ) {

		if ( isset( $action['process_further'] ) && false === $action['process_further'] ) {
			return $action;
		}

		// If the action has failed conditions, no need to postpone it.
		if ( isset( $action['action_data']['failed_actions_conditions'] ) && true === $action['action_data']['failed_actions_conditions'] ) {
			return $action;
		}

		if ( ! isset( $action['action_data']['meta']['async_mode'] ) ) {
			return $action;
		}

		// Store trigger data for future
		$this->args = $args;

		$action['action_data']['async'] = $this->generate_async_settings( $action );

		if ( $action['action_data']['async']['timestamp'] < time() ) {
			unset( $action['action_data']['async'] );
			automator_log( 'maybe_postpone_action: time is in the past, running action as non-scheduled.' );

			return $action;
		}

		$action['action_data']['async']['job_id'] = $this->postpone( $action );

		$action['action_data']['args']['async'] = true;

		automator_log( 'Action was scheduled with a job ID: ' . $action['action_data']['async']['job_id'] );

		$this->log_action( $action );

		$action['process_further'] = false;

		return $action;
	}

	/**
	 * generate_async_settings
	 *
	 * This function will generate all the settings required for scheduling an action, such as mode, status and timestamp.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	public function generate_async_settings( $action ) {

		$settings['timestamp'] = $this->get_timestamp( $action );

		$settings['mode']   = $action['action_data']['meta']['async_mode'];
		$settings['status'] = 'waiting';

		return $settings;
	}

	/**
	 * get_timestamp
	 *
	 * This function will generate a timestamp, when an action is suposed to be scheduled.
	 *
	 * @param array $action
	 *
	 * @return int
	 */
	public function get_timestamp( $action ) {

		$async_mode = $action['action_data']['meta']['async_mode'];
		$timestamp  = current_time( 'timestamp' );

		switch ( $async_mode ) {
			case 'delay':
				$timestamp = $this->get_delay_seconds( $action );
				break;
			case 'schedule':
				$timestamp = $this->get_schedule_seconds( $action );
				break;
			case 'custom':
				$timestamp = $this->get_custom_delay( $action );
				break;
			default:
				// Do nothing
				break;
		}

		return $timestamp;
	}

	/**
	 * postpone
	 *
	 * This function will create a new job with the Action Scheduler and pass back its ID.
	 *
	 * @param array $action
	 *
	 * @return int
	 */
	public function postpone( $action ) {
		$timestamp = $action['action_data']['async']['timestamp'];

		$hook  = 'automator_async_run_with_hash';
		$group = apply_filters( 'automator_pro_asyc_actions_group', 'Uncanny Automator', $action );

		// Because over 8000 throws a fatal error :(. We
		// are saving the hash of the data in options table as a workaround.
		$hash = md5( wp_json_encode( $action ) );
		// save the action data in options table
		automator_pro_add_option( $hash, $action, false );

		return as_schedule_single_action( $timestamp, $hook, array( $hash ), $group );
	}

	/**
	 * log_action
	 *
	 * This function will go through the action process to create a record in Automator's action log
	 * The process will be intercepted later to change the completed status
	 *
	 * @param array $action
	 *
	 * @return void
	 */
	public function log_action( $action ) {
		extract( $action );
		$error_message = '';
		$recipe_log_id = $action_data['recipe_log_id'];
		Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message, $recipe_log_id, $args );
	}

	/**
	 * get_delay_seconds
	 *
	 * This fnction will translate the values from the delay UI into a timestamp.
	 *
	 * @param array $action
	 *
	 * @return int
	 */
	public function get_delay_seconds( $action ) {

		$unit       = $action['action_data']['meta']['async_delay_unit'];
		$number     = (int) $action['action_data']['meta']['async_delay_number'];
		$multiplier = 1;

		switch ( $unit ) {
			case 'minutes':
				$multiplier = 60;
				break;
			case 'hours':
				$multiplier = 60 * 60;
				break;
			case 'days':
				$multiplier = 60 * 60 * 24;
				break;
			case 'years':
				$multiplier = 60 * 60 * 24 * 365;
				break;
			default:
				// Do nothing
				break;
		}

		return time() + $number * $multiplier;
	}

	/**
	 * @param $action
	 *
	 * @return int|mixed|null
	 */
	public function get_custom_delay( $action ) {

		// Get async custom value
		$custom_value = $action['action_data']['meta']['async_custom'];

		$custom_value = $this->handle_timestamp_tokens( $custom_value );
		// Parse it via token parser
		$custom_value = Automator()->parse->text( $custom_value, $action['recipe_id'], $action['user_id'], $this->args );

		// Generate a timestamp
		$custom_value = $this->parse_date_time_string( $custom_value );

		return apply_filters( 'automator_pro_async_action_custom_date_time', $custom_value, $action, $this );
	}

	/**
	 * These token return current_time('timestamp') which returns time - timezone,
	 * by this point, the timestamp is already in the past. Manually handle this situation
	 *
	 * @param $string
	 *
	 * @return array|string|string[]
	 */
	public function handle_timestamp_tokens( $string ) {

		// Define the tokens to search for
		$timestamp_tokens = array(
			'{{current_unix_timestamp}}'     => time(),
			'{{currentdate_unix_timestamp}}' => strtotime( date( 'Y-m-d', time() ) ),
		);

		// Loop through the tokens and replace them in the "Date" string
		foreach ( $timestamp_tokens as $token => $value ) {
			$string = str_replace( $token, $value, $string );
		}

		return $string;
	}

	/**
	 * get_schedule_seconds
	 *
	 * This fnction will translate the values from the schedule UI into a timestamp.
	 *
	 * @param array $action
	 * @param mixed $gmt
	 *
	 * @return int
	 */
	public function get_schedule_seconds( $action ) {

		$date = trim( $action['action_data']['meta']['async_schedule_date'] );
		$time = trim( $action['action_data']['meta']['async_schedule_time'] );

		$date_format = 'Y-m-d';
		$time_format = 'g:i A';

		if ( defined( 'AUTOMATOR_PLUGIN_VERSION' ) && version_compare( AUTOMATOR_PLUGIN_VERSION, '4.1.1', '<' ) ) {
			$date_format = get_option( 'date_format' );
		}

		$date_time = \DateTime::createFromFormat( $date_format . ' ' . $time_format, $date . ' ' . $time, wp_timezone() );

		if ( ! $date_time ) {
			automator_log( \DateTime::getLastErrors(), 'DateTime::createFromFormat failed: ' );
			automator_log( $date, '$date: ' );
			automator_log( $date_format, '$date_format: ' );
			automator_log( $time, '$time: ' );
			automator_log( $time_format, '$time_format: ' );
			automator_log( wp_timezone(), 'wp_timezone(): ' );

			return time();
		}

		return $date_time->getTimestamp();
	}

	/**
	 * run
	 *
	 * This action will run the scheduled actions, when the time has come.
	 *
	 * @param array $action
	 *
	 * @return void
	 */
	public function run( $action ) {

		$this->run_execution( $action );
	}

	/**
	 * @param $action_hash
	 *
	 * @return void
	 * @since v4.7
	 */
	public function run_with_hash( $action_hash ) {
		$action = automator_pro_get_option( $action_hash );

		$this->run_execution( $action );

		automator_pro_delete_option( $action_hash );
		delete_option( $action_hash );
	}

	/**
	 * @param $action
	 *
	 * @return void
	 */
	private function run_execution( $action ) {
		$action = apply_filters( 'automator_pro_before_async_action_executed', $action );

		if ( isset( $action['process_further'] ) && false === $action['process_further'] ) {

			automator_log( 'Action was skipped by automator_pro_before_async_action_executed filter.' );

			return;
		}

		$action_id   = $action['action_data']['ID'];
		$recipe_id   = $action['recipe_id'];
		$action_post = get_post( $action_id );
		$recipe_post = get_post( $recipe_id );

		// If action or recipe no longer exists
		if ( empty( $action_post ) || empty( $recipe_post ) || ! $action_post instanceof \WP_Post || ! $recipe_post instanceof \WP_Post ) {
			$this->recipe_or_action_no_longer_exists( $action );

			return;
		}

		// If action or recipe is set to draft
		if ( 'publish' !== $action_post->post_status || 'publish' !== $recipe_post->post_status ) {
			$this->recipe_or_action_is_not_live( $action );

			return;
		}

		$action_code = $action['action_data']['meta']['code'];

		$action_execution_function = Automator()->get->action_execution_function_from_action_code( $action_code );

		$action['action_data']['async']['status']       = 'completed';
		$action['action_data']['async']['completed_at'] = current_time( 'timestamp' ); //phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		if ( isset( $action['process_further'] ) ) {
			unset( $action['process_further'] );
		}

		if ( empty( $action_execution_function ) ) {

			$this->missing_execution_function( $action );

		}

		try {

			call_user_func_array( $action_execution_function, $action );

			do_action( 'automator_pro_async_action_after_run_execution', $action );
		} catch ( \Error $e ) {
			$this->complete_with_error( $action, $e->getMessage() );
		} catch ( \Exception $e ) {
			$this->complete_with_error( $action, $e->getMessage() );
		}
	}

	/**
	 * @param $action
	 * @param $error
	 *
	 * @return void
	 */
	public function complete_with_error( $action, $error = '' ) {

		$recipe_id = $action['recipe_id'];
		$user_id   = $action['user_id'];

		$action['action_data']['complete_with_errors'] = true;

		Automator()->complete->action( $user_id, $action['action_data'], $recipe_id, $error );
	}

	/**
	 * missing_execution_function
	 *
	 * @param array $action
	 *
	 * @return void
	 */
	public function missing_execution_function( $action ) {

		$error = __( 'Action is missing or the integration plugin is deactivated.', 'uncanny-automator-pro' );

		automator_log( $error );

		$this->complete_with_error( $action, $error );
	}

	/**
	 * @param $action
	 *
	 * @return void
	 */
	public function recipe_or_action_is_not_live( $action ) {
		$error = __( 'The associated recipe or action is not live.', 'uncanny-automator-pro' );

		automator_log( $error, '$error', AUTOMATOR_DEBUG_MODE, 'recipe_or_action_is_not_live' );
		automator_log( $action, '$action', AUTOMATOR_DEBUG_MODE, 'recipe_or_action_is_not_live' );

		$this->complete_with_error( $action, $error );
	}

	/**
	 * @param $action
	 *
	 * @return void
	 */
	public function recipe_or_action_no_longer_exists( $action ) {

		$error = __( 'The associated recipe or action is no longer available.', 'uncanny-automator-pro' );

		automator_log( $error, '$error', AUTOMATOR_DEBUG_MODE, 'recipe_or_action_no_longer_exists' );
		automator_log( $action, '$action', AUTOMATOR_DEBUG_MODE, 'recipe_or_action_no_longer_exists' );
	}

	/**
	 * change_action_completed_status
	 *
	 * This function will intercept the action completion process at automator_get_action_completed_status filter and swap the completed status with 5 if the action was scheduled earlier.
	 *
	 * @param int $completed
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param string $error_message
	 * @param int $recipe_log_id
	 * @param array $args
	 *
	 * @return int
	 */
	public function change_action_completed_status( $completed, $user_id, $action_data, $recipe_id, $error_message, $recipe_log_id, $args ) {

		// If there was an error
		if ( 2 === $completed ) {
			return $completed;
		}

		// If async mode is not set
		if ( ! isset( $action_data['async']['mode'] ) ) {
			return $completed;
		}

		// If async status is not waiting
		if ( 'waiting' !== $action_data['async']['status'] ) {
			return $completed;
		}

		// Change the complted status to 5 (scheduled)
		$completed = 5;

		return $completed;
	}

	/**
	 * maybe_complete_async_action
	 *
	 * This function will intercept the action completion process at automator_before_action_created filter and mark the action complete if it was run by the scheduler earlier.
	 *
	 * @param bool $process_further
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param string $error_message
	 * @param int $recipe_log_id
	 * @param array $args
	 *
	 * @return bool
	 */
	public function maybe_complete_async_action( $process_further, $user_id, $action_data, $recipe_id, $error_message, $recipe_log_id, $args ) {

		if ( isset( $action_data['async']['status'] ) && 'completed' === $action_data['async']['status'] ) {

			Automator()->db->action->mark_complete( (int) $action_data['ID'], $recipe_log_id, $action_data['completed'], $error_message );

			do_action( 'uap_action_completed', $user_id, (int) $action_data['ID'], $recipe_id, $error_message, $args );

			$action_data['error_message'] = $error_message;

			do_action( 'automator_pro_async_action_execution_after_invoked', $action_data );

			Automator()->complete->recipe( $recipe_id, $user_id, $recipe_log_id, $args );

			$process_further = false;
		}

		return $process_further;
	}

	/**
	 * adjust_action_log_date_time
	 *
	 * This function will intercept the action creation process at automator_action_log_date_time filter and adjust the date to make sure that it reflects the scheduled date.
	 *
	 * @param mixed $date_time
	 * @param mixed $action
	 *
	 * @return string
	 */
	public function adjust_action_log_date_time( $date_time, $action ) {

		if ( ! isset( $action['async']['timestamp'] ) ) {
			return $date_time;
		}

		date_default_timezone_set( 'UTC' );

		$gmt_offset = get_option( 'gmt_offset' );

		$timestamp_with_offset = (int) $action['async']['timestamp'] + $gmt_offset * 60 * 60;

		return date( 'Y-m-d H:i:s', $timestamp_with_offset ); //phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * store_async_job_id
	 *
	 * This function will hook to automator_action_created action and store the scheduler's job ID in the DB.
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public function store_async_job_id( $args ) {

		extract( $args );

		$job_id = ! empty( $action_data['async']['job_id'] ) ? $action_data['async']['job_id'] : '';

		if ( ! empty( $job_id ) && ! empty( $action_log_id ) ) {
			$meta_key = 'async_job_id';
			Automator()->db->action->add_meta( (int) $user_id, (int) $action_log_id, (int) $action_id, $meta_key, (int) $job_id );
		}
	}

	/**
	 * action_log_status
	 *
	 * This function will intercept the status of each action in the log table and replace it with the appropriate status if an action was scheduled or cancelled.
	 *
	 * @param string $status
	 * @param object $action
	 *
	 * @return string
	 */
	public function action_log_status( $status, $action ) {

		if ( 5 === (int) $action->action_completed ) {

			$async_job_id = (int) Automator()->db->action->get_meta( $action->action_log_id, 'async_job_id' );
			if ( is_numeric( $async_job_id ) && class_exists( 'ActionScheduler' ) ) {
				$formatted_date = '';
				// Fetch the action from the store
				$async_data = \ActionScheduler::store()->fetch_action( $async_job_id );
				if ( $async_data instanceof \ActionScheduler_Action ) {
					$date = $async_data->get_schedule();
					if ( $date instanceof \ActionScheduler_SimpleSchedule ) {
						$date_object = $date->get_date();
						if ( $date_object instanceof \ActionScheduler_DateTime ) {
							// Format the scheduled date to a readable format
							$date_object->setTimezone( new \DateTimeZone( wp_timezone()->getName() ) );
							$dd = $date_object->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

							$formatted_date = sprintf( '<br /> [%s] ', $dd );
						}
					}
				}
			}

			$status = esc_attr_x( 'Scheduled', 'Action', 'uncanny-automator' );
			$status .= $formatted_date;
			$status .= ' ' . $this->cancel_link( $action );

			return $status;
		}

		if ( 7 === (int) $action->action_completed ) {
			$status = esc_attr_x( 'Cancelled', 'Action', 'uncanny-automator' );

			return $status;
		}

		return $status;
	}

	/**
	 * cancel_link
	 *
	 * Generates the cancellation link.
	 *
	 * @param array $action
	 *
	 * @return string
	 */
	public function cancel_link( $action ) {
		return sprintf(
			'(<a href="#" onclick="cancelAsyncRun( event, %s, %s, %s )" class="uap-log-table__async-cancel">%s</a>)',
			$action->action_log_id,
			$action->automator_action_id,
			$action->recipe_log_id,
			esc_attr_x( 'cancel', 'Action', 'uncanny-automator' )
		);
	}

	/**
	 * cancel_async_run
	 *
	 * This function handles the cancellation of a scheduled job when a corresponding link was clicked in the log table.
	 *
	 * @return void
	 */
	public function cancel_async_run() {

		if ( ! ( automator_filter_has_var( 'nonce', INPUT_POST ) && wp_verify_nonce( automator_filter_input( 'nonce', INPUT_POST ), 'load-recipes-ref' ) ) ) {
			wp_die();
		}

		$action_id     = (int) automator_filter_input( 'action_id', INPUT_POST );
		$action_log_id = (int) automator_filter_input( 'action_log_id', INPUT_POST );
		$recipe_log_id = (int) automator_filter_input( 'recipe_log_id', INPUT_POST );

		$response = self::cancel_job( $action_log_id, $action_id, $recipe_log_id );

		echo json_encode( $response );

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Function to modify parts of the action
	 *
	 * @param $action
	 *
	 * @return array|mixed
	 * @since 5.0
	 */
	public function maybe_update_parts_of_action( $action ) {
		// Filter to allow users to not update fields
		if ( false === apply_filters( 'automator_pro_update_async_action_fields', true, $action ) ) {
			return $action;
		}

		// Find action code
		$action_code = $this->get_action_code_from_action( $action );
		if ( empty( $action_code ) ) {
			return $action;
		}

		// Is action modifiable during delayed execution?
		if ( false === $this->is_modifiable_action( $action_code ) ) {
			return $action;
		}

		// Modify fields and return action
		return $this->fetch_updated_fields( $action_code, $action );
	}

	/**
	 * Get action code from the $action
	 *
	 * @param $action
	 *
	 * @return mixed|string
	 * @since 5.0
	 */
	public function get_action_code_from_action( $action ) {
		if ( ! isset( $action['action_data'] ) || ! isset( $action['action_data']['meta'] ) || ! isset( $action['action_data']['meta']['code'] ) ) {
			return '';
		}

		return $action['action_data']['meta']['code'];
	}

	/**
	 * Validate if action can be modified
	 *
	 * @param $action_code
	 *
	 * @return bool
	 * @since 5.0
	 */
	public function is_modifiable_action( $action_code ) {

		// Add a filter to include more action_code => fields
		$this->actions_and_fields = apply_filters(
			'automator_pro_maybe_update_action_fields',
			array(
				'SENDEMAIL' => array(
					'EMAILFROM',
					'EMAILFROMNAME',
					'EMAILTO',
					'REPLYTO',
					'EMAILCC',
					'EMAILBCC',
					'EMAILSUBJECT',
					'EMAILBODY',
				),
			)
		);

		return array_key_exists( $action_code, $this->actions_and_fields );
	}

	/**
	 * Function to modify the value and fetch updated content from the action meta
	 *
	 * @param $action_code
	 * @param $action
	 *
	 * @return array
	 * @since 5.0
	 */
	public function fetch_updated_fields( $action_code, $action ) {

		// Action ID
		$action_id = absint( $action['action_data']['ID'] );

		foreach ( $action['action_data']['meta'] as $key => $value ) {
			if ( ! in_array( $key, $this->actions_and_fields[ $action_code ], true ) ) {
				continue;
			}

			// Fetch new value from the action meta
			$new_value = get_post_meta( $action_id, $key, true );

			// Update existing value with the new value
			$action['action_data']['meta'][ $key ] = apply_filters( 'automator_pro_async_action_updated_field_value', $new_value, $key, $action_code, $action_id, $action );

			// Store the old value, just-in-case
			$action['action_data']['meta'][ $key . '_scheduled_value' ] = $value;
		}

		return $action;
	}

	/**
	 * @param $input
	 *
	 * @return string
	 */
	public function parse_date_time_string( $input ) {
		try {

			// Check for direct Unix timestamp or arithmetic on it
			if ( ctype_digit( $input ) || preg_match( '/^(\d+)\s*[\+\-]\s*(\d+)$/', $input, $matches ) ) {
				$timestamp = ctype_digit( $input ) ? (int) $input : $this->calculate_arithmetic_timestamp( $input, $matches );
				if ( $timestamp >= - 2147483648 && $timestamp <= 2147483647 ) {
					return $timestamp;
				} else {
					throw new \Exception( sprintf( __( 'Provided input: %s is not a valid timestamp.', 'uncanny-automator-pro' ), $input ) );
				}
			}

			// Preprocess and rearrange input directly without separate methods
			$input = $this->simplifying_date_input( $input );

			if ( stripos( $input, 'every' ) !== false ) {
				throw new \Exception( sprintf( __( "Recurring events like '%s' cannot be parsed into a single timestamp.", 'uncanny-automator-pro' ), $input ) );
			}

			// Use the WordPress timezone
			$timezone  = new \DateTimeZone( wp_timezone()->getName() );
			$date_time = new \DateTime( $input, $timezone );

			// Ensure the timestamp is valid
			if ( ! $date_time instanceof \DateTime ) {
				throw new \Exception( sprintf( __( "Cannot parse the input '%s'.", 'uncanny-automator-pro' ), $input ) );
			}

			return $date_time->getTimestamp();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * @param $input
	 * @param $matches
	 *
	 * @return int
	 */
	private function calculate_arithmetic_timestamp( $input, $matches ) {
		$baseTimestamp = (int) $matches[1];
		$offset        = (int) $matches[2];

		return strpos( $input, '+' ) !== false ? $baseTimestamp + $offset : $baseTimestamp - $offset;
	}

	/**
	 * @param $input
	 *
	 * @return string
	 */
	public function simplifying_date_input( $input ) {
		// Mappings for special expressions and synonyms, merged for simplicity
		$mappings = apply_filters(
			'automator_pro_custom_delay_synonyms_mapping',
			array(
				'midday'             => '12:00 PM',
				'midnight'           => '12:00 AM',
				'coming'             => 'next',
				'upcoming'           => 'next',
				'following'          => 'next',
				'tonight'            => 'today 8:00 PM',
				'tomorrow night'     => 'tomorrow 8:00 PM',
				'end of the day'     => 'today 11:59 PM',
				'eod'                => 'today 11:59 PM',
				'start of the day'   => 'today 12:00 AM',
				'sod'                => 'today 12:00 AM',
				'noon'               => '12:00 PM',
				'afternoon'          => '2:00 PM',
				'evening'            => '6:00 PM',
				'morning'            => '8:00 AM',
				'early morning'      => '5:00 AM',
				'late morning'       => '11:00 AM',
				'mid-morning'        => '10:00 AM',
				'pre-noon'           => '11:00 AM',
				'late night'         => '10:00 PM',
				'early evening'      => '5:00 PM',
				'pre-midnight'       => '11:00 PM',
				'next week'          => 'next week',
				'next month'         => 'next month',
				'next year'          => 'next year',
				'yesterday'          => 'yesterday',
				'day after tomorrow' => 'tomorrow +1 day',
				'last night'         => 'yesterday 8:00 PM',
				'this morning'       => 'today 8:00 AM',
				'this afternoon'     => 'today 2:00 PM',
				'this evening'       => 'today 6:00 PM',
				'weekend'            => 'next Saturday',
				'end of this week'   => 'this Sunday',
				'start of this week' => 'this Monday',
				'end of the month'   => 'last day of this month 11:59 PM',
				'start of the month' => 'first day of this month 12:00 AM',
				'mid of the month'   => '15th day of this month 12:00 PM',
			)
		);

		// Replace mappings in input
		foreach ( $mappings as $key => $value ) {
			if ( stripos( $input, $key ) !== false ) {
				$input = str_ireplace( $key, $value, $input );
			}
		}

		// Rearrange time to end if present
		if ( preg_match( '/(\b\d{1,2}(:\d{2})?\s*(am|pm)\b)/i', $input, $matches ) ) {
			$date_part = trim( preg_replace( '/\b\d{1,2}(:\d{2})?\s*(am|pm)\b/i', '', $input ) );
			$input     = $date_part . ( empty( $date_part ) ? '' : ' ' ) . $matches[0];
		}

		// Remove extra spaces and problematic words
		$input = str_replace( array( ' at ', ' this ' ), ' ', $input );

		return trim( preg_replace( '!\s+!', ' ', $input ) ); // Collapse multiple spaces into a single space
	}

	/**
	 * get_upcoming_jobs_for_user
	 *
	 * @param  mixed $user_id
	 * @return mixed
	 */
	public static function get_upcoming_jobs_for_user( $user_id ) {

		global $wpdb;

		$output = array();

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, automator_action_id, automator_recipe_id, automator_recipe_log_id
				FROM {$wpdb->prefix}uap_action_log
				WHERE user_id = %d
				AND completed = %d",
				$user_id,
				Automator_Status::IN_PROGRESS
			),
			ARRAY_A
		);

		foreach ( $results as $action_run ) {
			$output[] = array(
				'action_log_id' => (int) $action_run['ID'],
				'async_job_id'  => (int) Automator()->db->action->get_meta( $action_run['ID'], 'async_job_id' ),
				'action_id'     => (int) $action_run['automator_action_id'],
				'recipe_id'     => (int) $action_run['automator_recipe_id'],
				'recipe_log_id' => (int) $action_run['automator_recipe_log_id'],
			);
		}

		return $output;
	}

	/**
	 * @param $action_log_id
	 * @param $action_id
	 *
	 * @return string|null
	 */
	private static function get_recipe_log_id( $action_log_id, $action_id ) {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `automator_recipe_log_id`
				FROM {$wpdb->prefix}uap_action_log
				WHERE ID = %d
				AND automator_action_id = %d
				LIMIT 0, 1",
				$action_log_id,
				$action_id
			)
		);
	}

	/**
	 * cancel_job
	 *
	 * @param  mixed $action_log_id
	 * @param  mixed $action_id
	 * @param  mixed $recipe_log_id
	 *
	 * @return array
	 */
	public static function cancel_job( $action_log_id, $action_id, $recipe_log_id = '', $recipe_id = null ) {

		$response = array();

		$async_job_id = (int) Automator()->db->action->get_meta( $action_log_id, 'async_job_id' );
		$job_details = \ActionScheduler::store()->fetch_action( $async_job_id );

		if ( empty( $recipe_log_id ) || ! is_numeric( $recipe_log_id ) ) {
			$recipe_log_id = self::get_recipe_log_id( $action_log_id, $action_id );
		}

		$error_message = __( 'Scheduled action was manually cancelled.', 'uncanny-automator-pro' );
		if ( ! empty( wp_get_current_user() ) ) {
			$error_message = sprintf( __( 'Scheduled action was cancelled by %s.', 'uncanny-automator-pro' ), wp_get_current_user()->user_email );
		}

		if ( null !== $recipe_id ) {
			$recipe_title  = get_the_title( $recipe_id );
			$error_message = sprintf( __( 'Scheduled action was cancelled by recipe: %s.', 'uncanny-automator-pro' ), $recipe_title );
		}

		try {

			\ActionScheduler::store()->cancel_action( $async_job_id );
			$response['success'] = true;

			Automator()->db->action->mark_complete( $action_id, $recipe_log_id, 7, $error_message );

			Automator()->complete->recipe( null, null, $recipe_log_id );

			$action_hash = array_shift( $job_details->get_args() );

			automator_pro_delete_option( $action_hash );

		} catch ( \InvalidArgumentException $th ) {

			automator_log( 'cancel_async_run for action ' . $action_log_id . ' failed with the following error: ' . $th->getMessage() );
			$response['success'] = false;
			$response['error']   = $th;
		}

		return $response;
	}
}
