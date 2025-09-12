<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class AFFWP_CREATE_AFFILIATE
 *
 * @package Uncanny_Automator_Pro
 */
class AFFWP_CREATE_AFFILIATE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'AFFWP' );
		$this->set_action_code( 'CREATE_AFFILIATE_CODE' );
		$this->set_action_meta( 'CREATE_AFFILIATE_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Affiliate WP */
		$this->set_sentence( sprintf( esc_attr__( 'Create {{an affiliate:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - Affiliate WP */
		$this->set_readable_sentence( esc_attr__( 'Create {{an affiliate}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
				'AFFILIATE_ID'  => array(
					'name' => __( 'Affiliate ID', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
				'AFFILIATE_URL' => array(
					'name' => __( 'Affiliate URL', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
			),
			$this->get_action_code()
		);

		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'user_name',
							'label'       => esc_attr__( 'Username', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Username', 'uncanny-automator-pro' ),
							'required'    => false,
							'description' => esc_attr__( 'The username of the existing WordPress account. If a username is not identified, an email address is required in the field below. If a matching username is not found, a user will not be created.', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'user_email',
							'input_type'  => 'email',
							'label'       => esc_attr__( 'Email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Email', 'uncanny-automator-pro' ),
							'required'    => false,
							'description' => esc_attr__( 'The email address of the WordPress account. This field is required if a username is not provided above. If an account matching the email address is not found, a new WordPress user will be created.', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'status',
							'label'       => esc_attr__( 'Status', 'uncanny-automator-pro' ),
							'options'     => affwp_get_affiliate_statuses(),
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'rate_type',
							'label'       => esc_attr__( 'Referral rate type', 'uncanny-automator-pro' ),
							'options'     => affwp_get_affiliate_rate_types(),
						)
					),
					Automator()->helpers->recipe->field->float(
						array(
							'option_code' => 'rate',
							'label'       => esc_attr__( 'Referral rate', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Rate (0.15)', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'payment_email',
							'input_type'  => 'email',
							'label'       => esc_attr__( 'Payment email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Payment email', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'notes',
							'label'       => esc_attr__( 'Affiliate notes', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'welcome_email',
							'label'       => esc_attr__( 'Send welcome email after creating an affiliate?', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
						)
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$affiliate                  = array();
		$affiliate['user_name']     = isset( $parsed['user_name'] ) ? sanitize_text_field( $parsed['user_name'] ) : '';
		$affiliate['user_email']    = isset( $parsed['user_email'] ) ? sanitize_email( $parsed['user_email'] ) : '';
		$affiliate['status']        = isset( $parsed['status'] ) ? sanitize_text_field( $parsed['status'] ) : '';
		$affiliate['rate_type']     = isset( $parsed['rate_type'] ) ? sanitize_text_field( $parsed['rate_type'] ) : '';
		$affiliate['rate']          = isset( $parsed['rate'] ) ? sanitize_text_field( $parsed['rate'] ) : '';
		$affiliate['payment_email'] = isset( $parsed['payment_email'] ) ? sanitize_text_field( $parsed['payment_email'] ) : '';
		$affiliate['notes']         = isset( $parsed['notes'] ) ? sanitize_text_field( $parsed['notes'] ) : '';
		$affiliate['welcome_email'] = isset( $parsed['welcome_email'] ) ? sanitize_text_field( $parsed['welcome_email'] ) : '';
		$affiliate['welcome_email'] = ( 'true' === $affiliate['welcome_email'] ) ? true : false;

		// Ensure that user_name or user_email is provided.
		if ( empty( $affiliate['user_name'] ) && empty( $affiliate['user_email'] ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = __( 'User login name or User email is required.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		// Check if user exists by user_name or user_email.
		$wp_user = $this->get_wp_user( $affiliate );
		if ( $wp_user ) {
			// If no user_name provided populate it.
			if ( empty( $affiliate['user_name'] ) ) {
				$affiliate['user_name'] = $wp_user->data->user_login;
			}
			// Remove user_email if WP user found.
			unset( $affiliate['user_email'] );
		}

		// Remove user_email if WP user found or not provided.
		if ( empty( $affiliate['user_email'] ) || $wp_user ) {
			unset( $affiliate['user_email'] );
		}

		// No user found with provided user_name and user_email is empty.
		if ( false === $wp_user && empty( $affiliate['user_email'] ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = sprintf( __( 'The user (%s) does not exist on the site.', 'uncanny-automator-pro' ), $affiliate['user_name'] );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		// Check for existing affiliate if we have WP_User.
		$affiliate_id = $wp_user ? affwp_get_affiliate_id( $wp_user->data->ID ) : false;
		if ( ! empty( $affiliate_id ) && (int) $affiliate_id > 0 ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = sprintf( __( 'The user is already an affiliate - %s.', 'uncanny-automator-pro' ), $affiliate['user_name'] );

			$this->hydrate_affiliate_tokens( $affiliate_id );

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		// Create new affiliate.
		$affiliate_id = affwp_add_affiliate( $affiliate );
		if ( false === $affiliate_id ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = sprintf( __( 'We are not able to create new affiliate, please retry later - %s.', 'uncanny-automator-pro' ), $affiliate['user_name'] );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		// All good, hydrate tokens and complete the action.
		$this->hydrate_affiliate_tokens( $affiliate_id );

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Get WP user by affiliate data.
	 *
	 * @param $affiliate
	 *
	 * @return mixed false || WP_User
	 */
	private function get_wp_user( $affiliate ) {

		$wp_user = ! empty( $affiliate['user_email'] ) ? get_user_by( 'email', $affiliate['user_email'] ) : false;

		if ( false === $wp_user ) {
			$wp_user = ! empty( $affiliate['user_name'] ) ? get_user_by( 'login', $affiliate['user_name'] ) : false;
		}

		return $wp_user;
	}

	/**
	 * Hydrate affiliate tokens.
	 *
	 * @param $affiliate_id
	 *
	 * @return void
	 */
	private function hydrate_affiliate_tokens( $affiliate_id ) {
		$this->hydrate_tokens(
			array(
				'AFFILIATE_ID'  => $affiliate_id,
				'AFFILIATE_URL' => affwp_get_affiliate_referral_url( array( 'affiliate_id' => $affiliate_id ) ),
			)
		);
	}

}
