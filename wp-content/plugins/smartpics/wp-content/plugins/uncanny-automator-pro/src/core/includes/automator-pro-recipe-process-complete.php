<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Automator_Recipe_Process_Complete;

/**
 * Class Automator_Pro_Recipe_Process_Complete
 *
 * @package Uncanny_Automator_Pro
 */
class Automator_Pro_Recipe_Process_Complete extends Automator_Recipe_Process_Complete {

	/**
	 * @var Automator_Pro_Recipe_Process_Anon
	 */
	public $anon;

	/**
	 * @var
	 */
	public $user_action_result;

	/**
	 * Automator_Pro_Recipe_Process_Complete constructor.
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_continue_recipe_process',
			array(
				$this,
				'uap_maybe_continue_recipe_process_func',
			)
		);
		// Check if actions needs to be executed for do-nothing AND failure scenarios
		add_filter( 'automator_before_action_executed', array( $this, 'maybe_user_actions_failed' ), 1 );
	}

	/**
	 * @param $action
	 *
	 * @return false
	 */
	public function maybe_user_actions_failed( $action ) {
		// Should the action process further
		if ( $this->should_process_further() ) {
			return $action;
		}
		// Log actions since the process further is set to false.
		$action['process_further'] = false;
		$this->log_action( $action );

		return $action;
	}

	/**
	 * @return bool
	 */
	public function should_process_further() {
		if ( empty( $this->user_action_result ) ) {
			return true;
		}

		if ( isset( $this->user_action_result['status'] ) && false === $this->user_action_result['status'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $action
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function get_recipe_id( $action ) {

		if ( empty( $action['recipe_id'] ) ) {
			throw new \Exception( 'Missing recipe ID' );
		}

		return (int) $action['recipe_id'];
	}

	/**
	 * @param $action
	 *
	 * @return void
	 */
	public function log_action( $action ) {

		extract( $action ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		$error_message = $this->extract_error_message();

		if ( $this->should_compete_with_errors() ) {
			$action_data['complete_with_errors']         = true;
			$action_data['args']['complete_with_errors'] = true;
		}

		if ( $this->should_compete_with_do_nothing() ) {
			$action_data['do-nothing']         = true;
			$action_data['args']['do-nothing'] = true;
		}
		$action_data = $this->maybe_add_user_action_message( $action_data, $error_message );

		Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
	}

	/**
	 * @return mixed|string|void
	 */
	public function extract_error_message() {
		$user_action_result = $this->user_action_result;
		$user_action_data   = isset( $user_action_result['data'] ) ? $user_action_result['data'] : array();

		return is_array( $user_action_data ) && array_key_exists( 'message', $user_action_data ) ? $user_action_data['message'] : __( 'There was an error completing recipe.', 'uncanny-automator-pro' );
	}

	/**
	 * @return bool
	 */
	public function should_compete_with_errors() {
		$user_action_result = $this->user_action_result;
		if ( true === $user_action_result['status'] ) {
			return false;
		}
		$user_action_data = isset( $user_action_result['data'] ) ? $user_action_result['data'] : array();
		if ( isset( $user_action_data['error'] ) && true === $user_action_data['error'] ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $action
	 * @param $error_message
	 *
	 * @return array
	 */
	public function maybe_add_user_action_message( $action, $error_message ) {
		if ( ! isset( $action['args'] ) || empty( $error_message ) ) {
			return $action;
		}

		if ( isset( $action['args']['user_action_message'] ) && ! empty( $action['args']['user_action_message'] ) ) {
			return $action;
		}

		$action['args']['user_action_message'] = $error_message;

		return $action;
	}

	/**
	 * @return bool
	 */
	public function should_compete_with_do_nothing() {
		$user_action_result = $this->user_action_result;
		if ( true === $user_action_result['status'] ) {
			return false;
		}
		$user_action_data = isset( $user_action_result['data'] ) ? $user_action_result['data'] : array();
		if ( isset( $user_action_data['error'] ) && true === $user_action_data['error'] ) {
			return false;
		}
		if ( 'do-nothing' === $user_action_data['fallback'] ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $attributes
	 *
	 * @return mixed
	 */
	public function uap_maybe_continue_recipe_process_func( $attributes ) {

		/**
		 * Clears the user_action_result property.
		 *
		 * This one introduced a side effect which was hard to catch.
		 *
		 * @ticket 1995440401/43046
		 * @since 4.9
		 */
		$this->user_action_result = '';

		$recipe_id   = absint( $attributes['recipe_id'] );
		$recipe_type = (string) Automator()->utilities->get_recipe_type( $recipe_id );

		// If the recipe type is not "Everyone", bail
		if ( 'anonymous' !== $recipe_type ) {
			return $attributes;
		}
		// check if user selector is set for the recipe
		$fields      = get_post_meta( $recipe_id, 'fields', true );
		$user_action = get_post_meta( $recipe_id, 'source', true );
		// Everyone recipe BUT user actions are not set
		if ( empty( $fields ) && empty( $user_action ) ) {
			return $attributes;
		}
		$cont_recipe_process = $attributes['maybe_continue_recipe_process'];
		$trigger_id          = absint( $attributes['trigger_id'] );
		$user_id             = absint( $attributes['user_id'] );
		$recipe_log_id       = absint( $attributes['recipe_log_id'] );
		$trigger_log_id      = absint( $attributes['trigger_log_id'] );
		$args                = $attributes['args'];
		$trigger_data        = Automator()->get_recipe_data( 'uo-trigger', $recipe_id );

		do_action_deprecated(
			'uap_before_anon_user_action_completed',
			array(
				$user_id,
				$recipe_id,
				$trigger_log_id,
				$args,
				$attributes,
			),
			'4.3',
			'automator_pro_before_anon_user_action_completed'
		);

		do_action( 'automator_pro_before_anon_user_action_completed', $user_id, $recipe_id, $trigger_log_id, $args, $attributes );

		$user_action_result       = $this->maybe_run_anonymous_recipe_user_actions( $recipe_id, $user_id, $recipe_log_id, $trigger_data, $args );
		$this->user_action_result = $user_action_result;
		$user_action_status       = isset( $user_action_result['status'] ) ? $user_action_result['status'] : false;
		$user_action_data         = isset( $user_action_result['data'] ) ? $user_action_result['data'] : array();
		$user_id                  = array_key_exists( 'user_id', $user_action_data ) ? $user_action_data['user_id'] : false;

		// May be trigger $args has user, use that
		if ( isset( $args['user_id'] ) && 0 !== $args['user_id'] && 0 === $user_id ) {
			$user_id = absint( $args['user_id'] );
		}

		// If $user_id found, update triggers, actions and recipe logs
		if ( $user_id && 0 !== $user_id ) {
			$args['user_id'] = $user_id;
			$this->maybe_update_recipe_log_user_id( $args, $user_action_result, $trigger_id, $trigger_log_id );
		}

		if ( true === $user_action_status ) {
			// attempt to update anonymous user to actual user!
			$args['user_action_message'] = array_key_exists( 'message', $user_action_data ) ? $user_action_data['message'] : '';
			do_action_deprecated(
				'uap_after_user_action_completed',
				array(
					$user_action_result,
					$user_id,
					$recipe_id,
					$trigger_log_id,
					$args,
				),
				'4.3',
				'automator_pro_after_user_action_completed'
			);

			do_action( 'automator_pro_after_user_action_completed', $user_action_result, $user_id, $recipe_id, $trigger_log_id, $args );
		}

		$attributes = array(
			'maybe_continue_recipe_process' => $cont_recipe_process,
			'recipe_id'                     => $recipe_id,
			'user_id'                       => $user_id,
			'recipe_log_id'                 => $recipe_log_id,
			'trigger_log_id'                => $trigger_log_id,
			'trigger_id'                    => $trigger_id,
			'args'                          => $args,
		);

		do_action_deprecated(
			'uap_after_user_action_do_nothing_completed',
			array(
				$user_action_result,
				$user_id,
				array(),
				$recipe_id,
				$trigger_log_id,
				$args,
			),
			'4.3',
			''
		);

		do_action_deprecated(
			'uap_after_anon_user_action_completed',
			array(
				$user_id,
				$recipe_id,
				$trigger_log_id,
				$attributes,
			),
			'4.3',
			'automator_pro_after_anon_user_action_completed'
		);

		do_action( 'automator_pro_after_anon_user_action_completed', $user_id, $recipe_id, $trigger_log_id, $attributes );

		return $attributes;
	}

	/**
	 * @param $args
	 * @param $user_action_result
	 * @param $trigger_id
	 * @param $trigger_log_id
	 *
	 * @return void
	 */
	public function maybe_update_recipe_log_user_id( $args, $user_action_result, $trigger_id, $trigger_log_id ) {
		global $wpdb;
		$user_id    = $args['user_id'];
		$table_name = $wpdb->prefix . 'uap_trigger_log';
		$wpdb->update(
			$table_name,
			array( 'user_id' => $user_id ),
			array( 'ID' => $args['trigger_log_id'] ),
			array( '%d' ),
			array( '%d' )
		);
		//attempt to update anonymous user to actual user!
		$table_name = $wpdb->prefix . 'uap_recipe_log';
		$wpdb->update(
			$table_name,
			array( 'user_id' => $user_id ),
			array( 'ID' => $args['recipe_log_id'] ),
			array( '%d' ),
			array( '%d' )
		);
		//attempt to update anonymous user to actual user!
		$table_name = $wpdb->prefix . 'uap_trigger_log_meta';
		$wpdb->update(
			$table_name,
			array(
				'user_id'  => $user_id,
				'run_time' => current_time( 'mysql' ),
			),
			array( 'automator_trigger_log_id' => $args['trigger_log_id'] ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		$run_number = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT run_number FROM $table_name WHERE automator_trigger_log_id = %d AND user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$args['trigger_log_id'],
				$user_id
			)
		);

		$parsed_data = key_exists( 'parsed_data', $user_action_result ) ? $user_action_result['parsed_data'] : array();
		$parsed_args = array(
			'user_id'        => $user_id,
			'trigger_id'     => $trigger_id,
			'trigger_log_id' => $trigger_log_id,
			'run_number'     => $run_number,
			'meta_key'       => 'parsed_data',
			'meta_value'     => maybe_serialize( $parsed_data ),
		);

		Automator()->insert_trigger_meta( $parsed_args );
	}

	/**
	 * Returns an array that contains an error message that is used for user selector.
	 *
	 * @return array The message.
	 */
	public static function invalid_message( $err_message = '' ) {

		return array(
			'status' => false,
			'data'   => array(
				'error'   => true,
				'message' => $err_message,
			),
		);

	}

	/**
	 * @param $recipe_id
	 * @param $maybe_user_id
	 * @param $recipe_log_id
	 * @param $trigger_data
	 * @param $args
	 *
	 * @return array
	 */
	public function maybe_run_anonymous_recipe_user_actions( $recipe_id, $maybe_user_id, $recipe_log_id, $trigger_data, $args ) {

		$replace_args = array(
			'recipe_id'      => $recipe_id,
			'recipe_log_id'  => $recipe_log_id,
			'trigger_id'     => $args['trigger_id'],
			'trigger_log_id' => $args['trigger_log_id'],
			'run_number'     => $args['run_number'],
			'user_id'        => $maybe_user_id,
		);

		// The fields.
		$fields = get_post_meta( $recipe_id, 'fields', true );

		// The selected user action.
		$user_action = get_post_meta( $recipe_id, 'source', true );

		// Initiate $data with empty array.
		$data = array();

		// Initiate $parsed_data with an empty array.
		$parsed_data = array();

		// Sanity check. Early bail when fields is empty or an object. Don't do foreach.
		if ( empty( $fields ) || is_object( $fields ) ) {
			return self::invalid_message( __( 'There was an issue parsing action fields. Please confirm action fields are correctly saved in your recipe.', 'uncanny-automator-pro' ) );
		}

		$fallback = array_key_exists( 'fallback', $fields ) ? $fields['fallback'] : array();

		foreach ( $fields as $key => $field_value ) {

			$data[ $key ] = Automator()->parse->text( $field_value, $recipe_id, $maybe_user_id, $replace_args );

			$parsed_data[ $key ] = $field_value;

		}

		// Sanity check. Bail if after hydrated data is empty.
		if ( empty( $data ) ) {
			return self::invalid_message( __( 'There was an issue parsing your data. Please check your data source and validate the data is passed correctly to the action.', 'uncanny-automator-pro' ) );
		}

		// Hydrate the $user_data.
		// @TODO: Move this to separate method.
		$user_data                       = array();
		$user_data['first_name']         = key_exists( 'firstName', $data ) ? $data['firstName'] : '';
		$user_data['last_name']          = key_exists( 'lastName', $data ) ? $data['lastName'] : '';
		$user_data['user_email']         = key_exists( 'email', $data ) ? $data['email'] : '';
		$user_data['user_login']         = key_exists( 'username', $data ) ? $data['username'] : $user_data['user_email'];
		$user_data['role']               = key_exists( 'role', $data ) ? $data['role'] : apply_filters( 'uap_default_user_role', get_option( 'default_role', 'subscriber' ) );
		$user_data['user_pass']          = key_exists( 'password', $data ) ? $data['password'] : apply_filters( 'uap_maybe_generate_anonymous_user_password', wp_generate_password(), $user_data );
		$user_data['prioritized_field']  = key_exists( 'prioritizedField', $fields ) ? $fields['prioritizedField'] : '';
		$user_data['unique_field_value'] = key_exists( 'uniqueFieldValue', $data ) ? $data['uniqueFieldValue'] : '';
		$user_data['unique_field']       = key_exists( 'uniqueField', $fields ) ? $fields['uniqueField'] : '';

		// Validate the required fields are not empty
		$user_data  = $this->maybe_sanitize_user_data( $user_data );
		$validation = $this->validate_user_data_before_new_user_creation( $user_data, $fallback, $user_action );

		if ( isset( $validation['status'] ) && false === $validation['status'] ) {
			return $validation;
		}

		if ( 'newUser' === (string) $user_action ) {
			return $this->user_action_on_new_user( $user_data, $fallback, $data );
		}

		if ( 'existingUser' === (string) $user_action ) {
			return $this->user_action_on_existing_user( $user_data, $fallback, $data );
		}

		// No user action ran. return false
		return array(
			'status' => false,
			'data'   => array(
				'error'   => true,
				'message' => __( 'There was an issue in running your recipe. Automator failed to run a new or existing user scenario.', 'uncanny-automator-pro' ),
			),
		);

	}

	/**
	 * @param $user_data
	 *
	 * @return mixed
	 */
	public function maybe_sanitize_user_data( $user_data ) {
		// Check if Email or username is empty
		if ( ! empty( $user_data['user_email'] ) && ! empty( $user_data['user_login'] ) ) {
			// Both required fields have data. return user data
			return $user_data;
		}
		// Check if user is logged in,
		// maybe grab logged in data
		if ( ! is_user_logged_in() ) {
			return $user_data;
		}
		// User is logged in, fill their email
		if ( empty( $user_data['user_email'] ) ) {
			$user_data['user_email'] = wp_get_current_user()->user_email;
		}
		// User is logged in, fill their user login
		if ( empty( $user_data['user_login'] ) ) {
			$user_data['user_login'] = wp_get_current_user()->user_login;
		}

		// User is logged in, fill their user login
		if ( empty( $user_data['unique_field_value'] ) ) {
			$user_data['unique_field_value'] = wp_get_current_user()->user_email;
		}

		return $user_data;
	}


	/**
	 * @param $user_data
	 * @param $fallback
	 * @param array $parsed_data
	 *
	 * @return array
	 */
	public function user_action_on_new_user( $user_data, $fallback, $parsed_data = array() ) {
		$user_id = $this->verify_if_user_exists_for_new_user_action( $user_data );

		if ( false === $user_id ) {
			//@var $user_id not defined yet, insert new user
			return $this->maybe_create_new_user_on_new_user_logic( $user_data, $fallback, $parsed_data );
		}

		return $this->maybe_run_on_existing_user_on_new_user_logic( $user_id, $fallback, $parsed_data );
	}

	/**
	 * @param $user_data
	 * @param $fallback
	 * @param $parsed_data
	 *
	 * @return array
	 */
	public function maybe_create_new_user_on_new_user_logic( $user_data, $fallback, $parsed_data ) {
		$user_id = wp_insert_user( $user_data );
		if ( is_wp_error( $user_id ) ) {
			$data = array(
				'user_id'  => email_exists( $user_data['user_email'] ),
				'fallback' => 'do-nothing',
				'action'   => 'newUser',
				'error'    => true,
				'message'  => sprintf(
				/* translators: 1. The user email, 2. The error */
					__( 'Creating a new user failed (%1$s). %2$s', 'uncanny-automator-pro' ),
					$user_data['user_email'],
					$user_id->get_error_message()
				),
			);

			return array(
				'status' => false,
				'data'   => $data,
			);
		}

		$data = array(
			'user_id'  => $user_id,
			'fallback' => $fallback,
			'action'   => 'newUser',
			'message'  => sprintf(
			/* translators: 1. The user email */
				__( 'A new user was created (%1$s)', 'uncanny-automator-pro' ),
				$user_data['user_email']
			),
		);

		if ( isset( $parsed_data['logUserIn'] ) && 'yes' === $parsed_data['logUserIn'] ) {
			$user = get_user_by( 'ID', $user_id );
			wp_set_current_user( $user_id, $user->user_login );
			wp_set_auth_cookie( $user_id );
			do_action( 'wp_login', $user->user_login, $user, false );
		}

		return array(
			'status'      => true,
			'data'        => $data,
			'parsed_data' => $parsed_data,
		);
	}

	/**
	 * @param $user_id
	 * @param $fallback
	 * @param $parsed_data
	 *
	 * @return array
	 */
	public function maybe_run_on_existing_user_on_new_user_logic( $user_id, $fallback, $parsed_data ) {
		switch ( $fallback ) {
			case 'select-existing-user':
				//Select Existing User
				$status = true;
				$user   = get_user_by( 'ID', $user_id );
				$data   = array(
					'user_id'  => $user_id,
					'fallback' => $fallback,
					'action'   => 'newUser',
					'message'  => sprintf(
					/* translators: 1. The user email */
						__( 'An existing user was found matching (%1$s), selected existing user', 'uncanny-automator-pro' ),
						$user->user_email
					),
				);

				if ( in_array( 'administrator', $user->roles, true ) ) {
					/* translators: 1. The user email */
					$data['message']  = sprintf( __( 'An existing user was found matching (%1$s), cannot select administrators', 'uncanny-automator-pro' ), $user->user_email );
					$data['fallback'] = 'do-nothing';
					$data['error']    = true;
					$status           = false;
				}

				$return = array(
					'status'      => $status,
					'data'        => $data,
					'parsed_data' => $parsed_data,
				);
				break;
			case 'do-nothing':
			default:
				$user = get_user_by( 'ID', $user_id );
				$data = array(
					'user_id'  => $user_id,
					'fallback' => $fallback,
					'action'   => 'newUser',
					'message'  => sprintf(
					/* translators: 1. The user email */
						__( 'An existing user was found matching (%1$s), "do nothing" selected in the recipe', 'uncanny-automator-pro' ),
						$user->user_login
					),
				);

				if ( in_array( 'administrator', $user->roles, true ) ) {
					/* translators: 1. The user email */
					$data['message']  = sprintf( __( 'An existing user was found matching (%1$s), cannot select administrators', 'uncanny-automator-pro' ), $user->user_email );
					$data['fallback'] = 'do-nothing';
					$data['error']    = true;
				}

				$return = array(
					'status' => false,
					'data'   => $data,
				);
				break;
		}

		return $return;
	}

	/**
	 * @param $user_data
	 * @param $fallback
	 * @param array $parsed_data
	 *
	 * @return array
	 */
	public function user_action_on_existing_user( $user_data, $fallback, $parsed_data = array() ) {
		$user_id = $this->verify_if_user_exists_for_existing_user_action( $user_data );

		if ( false === $user_id ) {
			//User not found
			return $this->maybe_create_user_on_existing_logic( $user_data, $fallback, $parsed_data );
		}

		return $this->maybe_use_existing_user_on_existing_logic( $user_id, $fallback, $parsed_data );
	}

	/**
	 * @param $user_data
	 * @param $fallback
	 * @param $parsed_data
	 *
	 * @return array
	 */
	public function maybe_create_user_on_existing_logic( $user_data, $fallback, $parsed_data ) {
		switch ( $fallback ) {
			case 'create-new-user':
				//Create a new user
				$user_id = wp_insert_user( $user_data );
				if ( is_wp_error( $user_id ) ) {
					$data = array(
						'user_id'  => 0,
						'fallback' => $fallback,
						'action'   => 'existingUser',
						'error'    => true,
						'message'  => sprintf(
						/* translators: 1. The user email, 2. The error */
							__( 'Creating a new user failed (%1$s). %2$s', 'uncanny-automator-pro' ),
							$user_data['user_email'],
							$user_id->get_error_message()
						),
					);

					return array(
						'status' => false,
						'data'   => $data,
					);
				}
				$data = array(
					'user_id'  => $user_id,
					'fallback' => $fallback,
					'action'   => 'existingUser',
					'message'  => sprintf(
					/* translators: 1. The user email */
						__( 'A user was not found matching (%1$s). A new user was created', 'uncanny-automator-pro' ),
						$user_data['user_email']
					),
				);

				if ( isset( $parsed_data['logUserIn'] ) && 'yes' === $parsed_data['logUserIn'] ) {
					$user = get_user_by( 'ID', $user_id );
					wp_set_current_user( $user_id, $user->user_login );
					wp_set_auth_cookie( $user_id );
					do_action( 'wp_login', $user->user_login, $user, false );
				}

				$return = array(
					'status'      => true,
					'data'        => $data,
					'parsed_data' => $parsed_data,
				);
				break;
			case 'do-nothing':
			default:
				$data = array(
					'user_id'  => 0,
					'fallback' => $fallback,
					'action'   => 'existingUser',
					'message'  => sprintf(
					/* translators: 1. The user ID, email or username */
						__( 'A user was not found matching (%1$s), "do nothing" selected in the recipe.', 'uncanny-automator-pro' ),
						$user_data['unique_field_value']
					),
				);

				$return = array(
					'status' => false,
					'data'   => $data,
				);
				break;
		}

		return $return;
	}

	/**
	 * @param $user_id
	 * @param $fallback
	 * @param $parsed_data
	 *
	 * @return array
	 */
	public function maybe_use_existing_user_on_existing_logic( $user_id, $fallback, $parsed_data ) {
		//User found!
		$status = true;
		$user   = get_user_by( 'ID', $user_id );
		if ( ! $user instanceof \WP_User ) {
			$data = array(
				'user_id'  => $user_id,
				'fallback' => 'do-nothing',
				'action'   => 'existingUser',
				'message'  => sprintf(
				/* translators: 1. The user email */
					__( 'A user was not found: (%s)', 'uncanny-automator-pro' ),
					$user_id
				),
			);

			return array(
				'status'      => false,
				'data'        => $data,
				'parsed_data' => $parsed_data,
			);
		}

		$data = array(
			'user_id'  => $user_id,
			'fallback' => $fallback,
			'action'   => 'existingUser',
			'message'  => sprintf(
			/* translators: 1. The user email */
				__( 'A user found matching (%1$s), user was selected', 'uncanny-automator-pro' ),
				$user->user_email
			),
		);

		if ( in_array( 'administrator', $user->roles, true ) ) {
			$data['message'] = sprintf(
			/* translators: 1. The user email */
				__( 'An existing user was found matching (%1$s), cannot select administrators', 'uncanny-automator-pro' ),
				$user->user_email
			);
			$data['fallback'] = 'do-nothing';
			$data['error']    = true;
			$status           = false;
		}

		return array(
			'status'      => $status,
			'data'        => $data,
			'parsed_data' => $parsed_data,
		);
	}

	/**
	 * @param $user_data
	 *
	 * @return bool|false|int
	 */
	public function verify_if_user_exists_for_new_user_action( $user_data ) {
		//If there's a priority to match
		$priority = 'email';
		if ( key_exists( 'prioritized_field', $user_data ) ) {
			$priority = (string) $user_data['prioritized_field'];
		}

		switch ( $priority ) {
			case 'username':
				$user_id = username_exists( $user_data['user_login'] );
				if ( $user_id ) {
					return $user_id;
				}
				if ( ! empty( $user_data['user_email'] ) ) {
					return email_exists( $user_data['user_email'] );
				}

				break;
			case 'email':
			default:
				$user_id = email_exists( $user_data['user_email'] );
				if ( $user_id ) {
					return $user_id;
				}
				if ( ! empty( $user_data['user_login'] ) ) {
					return username_exists( $user_data['user_login'] );
				}

				break;
		}

		return false;
	}

	/**
	 * @param $user_data
	 *
	 * @return bool|false|int
	 */
	public function verify_if_user_exists_for_existing_user_action( $user_data ) {
		//If there's a priority to match
		$unique_field = ( key_exists( 'unique_field', $user_data ) && ! empty( $user_data['unique_field'] ) ) ? $user_data['unique_field'] : 'email';
		$value        = $user_data['unique_field_value'];

		if ( empty( $value ) ) {
			return false;
		}

		switch ( $unique_field ) {
			case 'username':
				$user_id = username_exists( $value );

				return ! is_wp_error( $user_id ) ? $user_id : false;
			case 'id':
				$user_id = get_user_by( 'ID', $value );
				if ( $user_id instanceof \WP_User ) {
					return $user_id->ID;
				}

				return ! is_wp_error( $user_id ) ? $user_id : false;
			case 'email':
			default:
				$user_id = email_exists( $value );

				return ! is_wp_error( $user_id ) ? $user_id : false;
		}
	}

	/**
	 * @param $user_data
	 * @param $fallback
	 * @param $user_action
	 *
	 * @return array
	 */
	public function validate_user_data_before_new_user_creation( $user_data, $fallback, $user_action ) {
		// Do some validation here first:
		if ( ( empty( $user_data['user_email'] ) && empty( $user_data['user_login'] ) ) && 'do-nothing' !== $fallback ) {
			return array(
				'status' => false,
				'data'   => array(
					'user_id'  => 0,
					'error'    => true,
					'fallback' => $fallback,
					'action'   => $user_action,
					'message'  => sprintf(
					/* translators: 1. The user ID, email or username */
						__( 'Username and email fields were empty', 'uncanny-automator-pro' ),
						$user_data['unique_field_value']
					),
				),
			);
		}

		if ( empty( $user_data['user_email'] ) && 'do-nothing' !== $fallback ) {
			return array(
				'status' => false,
				'data'   => array(
					'user_id'  => 0,
					'fallback' => $fallback,
					'error'    => true,
					'action'   => $user_action,
					'message'  => sprintf(
					/* translators: 1. The user ID, email or username */
						__( 'Email field was empty', 'uncanny-automator-pro' ),
						$user_data['unique_field_value']
					),
				),
			);
		}

		if ( empty( $user_data['user_login'] ) && 'do-nothing' !== $fallback ) {
			return array(
				'status' => false,
				'data'   => array(
					'user_id'  => 0,
					'fallback' => $fallback,
					'error'    => true,
					'action'   => $user_action,
					'message'  => sprintf(
					/* translators: 1. The user ID, email or username */
						__( 'Username field was empty', 'uncanny-automator-pro' ),
						$user_data['unique_field_value']
					),
				),
			);
		}

		if ( ! is_email( $user_data['user_email'] ) && 'do-nothing' !== $fallback ) {
			return array(
				'status' => false,
				'data'   => array(
					'user_id'  => 0,
					'fallback' => $fallback,
					'error'    => true,
					'action'   => $user_action,
					'message'  => sprintf(
					/* translators: 1. The user ID, email or username */
						sprintf( __( 'Invalid email provided (%s)', 'uncanny-automator-pro' ), $user_data['user_email'] ),
						$user_data['unique_field_value']
					),
				),
			);
		}

		return $user_data;
	}

	/**
	 * Fetches a specific recipe log by status.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param string $status
	 *
	 * @return array
	 */
	public static function fetch_specific_recipe_log_by_status( $recipe_id, $recipe_log_id, $status ) {

		global $wpdb;

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}uap_action_log WHERE automator_recipe_id = %d AND automator_recipe_log_id = %d AND completed = %d",
				absint( $recipe_id ),
				absint( $recipe_log_id ),
				$status
			),
			ARRAY_A
		);

	}

}
