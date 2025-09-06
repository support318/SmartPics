<?php

namespace Uncanny_Automator_Pro;

use EasyAffiliate\Models\User;
use Uncanny_Automator\Recipe;

/**
 * Class ESAF_ADD_AFFILIATE
 *
 * @package Uncanny_Automator_Pro
 */
class ESAF_ADD_AFFILIATE {
	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * @var Easy_Affiliate_Pro_Helpers
	 */
	public $pro_helpers;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
		$this->pro_helpers = new Easy_Affiliate_Pro_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'ESAF' );
		$this->set_action_code( 'ADD_AFFILIATE_CODE' );
		$this->set_action_meta( 'ADD_AFFILIATE_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Easy Affiliate */
		$this->set_sentence( sprintf( esc_attr__( 'Add a new {{affiliate:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - Easy Affiliate */
		$this->set_readable_sentence( esc_attr__( 'Add a new {{affiliate}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
				'affiliate_ID' => array(
					'name' => __( 'Affiliate ID', 'uncanny-automator-pro' ),
					'type' => 'int',
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
							'option_code' => 'first_name',
							'label'       => esc_attr__( 'First name', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'First name', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'last_name',
							'label'       => esc_attr__( 'Last name', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Last name', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_user_user_login',
							'label'       => esc_attr__( 'Username', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Username', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_user_user_email',
							'input_type'  => 'email',
							'label'       => esc_attr__( 'Email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Email', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_paypal_email',
							'input_type'  => 'email',
							'label'       => esc_attr__( 'PayPal email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'PayPal email', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_user_user_pass',
							'label'       => esc_attr__( 'Password', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Password', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_user_address_one',
							'label'       => esc_attr__( 'Address - Line 1', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Address - Line 1', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_user_address_two',
							'label'       => esc_attr__( 'Address - Line 2', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Address - Line 2', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_user_city',
							'label'       => esc_attr__( 'Address - City', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Address - City', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_user_state',
							'label'       => esc_attr__( 'Address - State', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Address - State', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'wafp_user_zip',
							'label'       => esc_attr__( 'Address - Zip', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Address - Zip', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'wafp_user_country',
							'label'       => esc_attr__( 'Address - Country', 'uncanny-automator-pro' ),
							'required'    => false,
							'options'     => $this->pro_helpers->get_countries(),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'send_notification',
							'label'       => esc_attr__( 'Send notification', 'uncanny-automator-pro' ),
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
		$aff                          = array();
		$aff['first_name']            = isset( $parsed['first_name'] ) ? sanitize_text_field( $parsed['first_name'] ) : '';
		$aff['last_name']             = isset( $parsed['last_name'] ) ? sanitize_text_field( $parsed['last_name'] ) : '';
		$aff['_wafp_user_user_login'] = isset( $parsed['_wafp_user_user_login'] ) ? sanitize_text_field( $parsed['_wafp_user_user_login'] ) : '';
		$aff['_wafp_user_user_email'] = isset( $parsed['_wafp_user_user_email'] ) ? sanitize_text_field( $parsed['_wafp_user_user_email'] ) : '';
		$aff['wafp_paypal_email']     = isset( $parsed['wafp_paypal_email'] ) ? sanitize_text_field( $parsed['wafp_paypal_email'] ) : '';
		$aff['_wafp_user_user_pass']  = isset( $parsed['_wafp_user_user_pass'] ) ? sanitize_text_field( $parsed['_wafp_user_user_pass'] ) : '';
		$aff['wafp_user_address_one'] = isset( $parsed['wafp_user_address_one'] ) ? sanitize_text_field( $parsed['wafp_user_address_one'] ) : '';
		$aff['wafp_user_address_two'] = isset( $parsed['wafp_user_address_two'] ) ? sanitize_text_field( $parsed['wafp_user_address_two'] ) : '';
		$aff['wafp_user_city']        = isset( $parsed['wafp_user_city'] ) ? sanitize_text_field( $parsed['wafp_user_city'] ) : '';
		$aff['wafp_user_zip']         = isset( $parsed['wafp_user_zip'] ) ? sanitize_text_field( $parsed['wafp_user_zip'] ) : '';
		$aff['wafp_user_country']     = isset( $parsed['wafp_user_country'] ) ? sanitize_text_field( $parsed['wafp_user_country'] ) : '';
		$send_notification            = isset( $parsed['send_notification'] ) ? sanitize_text_field( $parsed['send_notification'] ) : '';

		$send_notification = ( 'true' === $send_notification ) ? true : false;

		$user    = new User();
		$wp_user = get_user_by_email( $aff['_wafp_user_user_email'] );

		if ( $wp_user ) {
			$is_user_affiliate = get_user_meta( $wp_user, 'wafp_is_affiliate', true );
			if ( isset( $is_user_affiliate ) && true === $is_user_affiliate ) {
				$action_data['do-nothing']           = true;
				$action_data['complete_with_errors'] = true;
				$message                             = sprintf( __( 'The user is already an affiliate - %s', 'uncanny-automator-pro' ), $aff['_wafp_user_user_email'] );
				Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

				return;
			}
			$user->ID = $wp_user->ID;
		}

		$user->load_from_sanitized_array( $aff );
		$user->is_affiliate = true;
		$affiliate_id       = $user->store();
		do_action( 'esaf-process-signup', $user );
		$user->send_account_notifications( true, $send_notification );

		$this->hydrate_tokens(
			array( 'affiliate_ID' => $affiliate_id )
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
