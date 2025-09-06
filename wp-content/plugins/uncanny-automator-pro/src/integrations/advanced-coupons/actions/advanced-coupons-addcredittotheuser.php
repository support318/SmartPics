<?php

namespace Uncanny_Automator_Pro;

use API_Store_Credit_Entry;
use ACFWF\Models\Objects\Store_Credit_Entry;
use ACFWF\Helpers\Plugin_Constants;
use Uncanny_Automator\Recipe;

/**
 * Class ADVANCED_COUPONS_ADDCREDITTOTHEUSER
 *
 * @package Uncanny_Automator_Pro
 */
class ADVANCED_COUPONS_ADDCREDITTOTHEUSER {
	use Recipe\Action_Tokens;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'ACFWC';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'ACFWCADDCREDITTOTHEUSER';
		$this->action_meta = 'ADDCREDITTOTHEUSER';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/advanced-coupons/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - MemberPress */
			'sentence'           => sprintf( __( "Add {{a specific amount of:%1\$s}} store credit to the user's account", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MemberPress */
			'select_option_name' => __( "Add {{a specific amount of}} store credit to the user's account", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'add_credit' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'USERTOTALSTORECREDIT' => array(
					'name' => __( "User's total store credit", 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						array(
							'option_code' => $this->action_meta,
							'label'       => _x( 'Amount', 'Advanced Coupons', 'uncanny-automator-pro' ),
							'token_name'  => _x( 'Store credit amount', 'Advanced Coupons', 'uncanny-automator-pro' ),
							'input_type'  => 'float',
							'tokens'      => true,
							'required'    => true,
						),
						array(
							'option_code' => $this->action_meta . '_LABEL',
							'label'       => _x( 'Activity description', 'Advanced Coupons', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
							'tokens'      => true,
							'required'    => false,
							'description' => _x( 'The label users will see in the credit activity logs.', 'Advanced Coupons', 'uncanny-automator-pro' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Action method to execute event when any action executed.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function add_credit( $user_id, $action_data, $recipe_id, $args ) {

		$amount = floatval( Automator()->parse->text( $action_data['meta'][ $this->action_meta ], $recipe_id, $user_id, $args ) );
		$label  = Automator()->parse->text( $action_data['meta'][ $this->action_meta . '_LABEL' ], $recipe_id, $user_id, $args );

		if ( $amount <= 0 ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = _x( 'The amount entered to be added is not valid', 'Advanced Coupons', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$params = array(
			'user_id' => $user_id,
			'type'    => 'increase',
			'amount'  => $amount,
			'action'  => 'admin_increase_auto',
			'note'    => ! empty( $label ) ? $label : 'Uncanny Automator',
		);

		// create store credit entry object.
		$store_credit_entry = new Store_Credit_Entry();
		foreach ( $params as $prop => $value ) {
			$store_credit_entry->set_prop( $prop, $value );
		}
		$store_credit_entry->set_date_prop( 'date', gmdate( 'Y-m-d H:i:s' ), Plugin_Constants::DB_DATE_FORMAT );
		$store_credit_entry->set_prop( 'object_id', $recipe_id );

		// Save store credit entry.
		$check = $store_credit_entry->save();

		if ( is_wp_error( $check ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $check->get_error_message() );

			return;
		}

		$this->hydrate_tokens(
			array(
				'USERTOTALSTORECREDIT' => Automator()->helpers->recipe->advanced_coupons->get_current_balance_of_the_customer( $user_id ),
			)
		);

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
