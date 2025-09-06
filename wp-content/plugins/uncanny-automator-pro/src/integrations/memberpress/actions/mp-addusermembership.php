<?php

namespace Uncanny_Automator_Pro;

use MeprBaseRealGateway;
use MeprEvent;
use MeprOptions;
use MeprProduct;
use MeprTransaction;
use MeprUser;
use MeprUtils;
use MeprSubscription;

/**
 * Class MP_ADDUSERMEMBERSHIP
 *
 * @package Uncanny_Automator_Pro
 */
class MP_ADDUSERMEMBERSHIP {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MP';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'MPADDUSERMEMBERSHIP';
		$this->action_meta = 'MPUSERMEMBERSHIP';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/memberpress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - MemberPress */
			'sentence'           => sprintf( __( 'Add the user to {{a membership:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MemberPress */
			'select_option_name' => __( 'Add the user to {{a membership}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'add_membership' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$taxn_status = array(
			/* translators: MemberPress membership status */
			MeprTransaction::$complete_str => __( 'Complete', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprTransaction::$pending_str  => __( 'Pending', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprTransaction::$failed_str   => __( 'Failed', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprTransaction::$refunded_str => __( 'Refunded', 'uncanny-automator-pro' ),
		);

		$subscription_status = array(
			/* translators: MemberPress membership status */
			MeprSubscription::$active_str    => __( 'Enabled', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprSubscription::$pending_str   => __( 'Pending', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprSubscription::$suspended_str => __( 'Suspended', 'uncanny-automator-pro' ),
			/* translators: MemberPress membership status */
			MeprSubscription::$cancelled_str => __( 'Cancelled', 'uncanny-automator-pro' ),
		);

		$mepr_options = MeprOptions::fetch();

		$pms      = array_keys( $mepr_options->integrations );
		$gateways = array( 'manual' => __( 'Manual', 'uncanny-automator-pro' ) );
		foreach ( $pms as $pm_id ) {
			$obj = $mepr_options->payment_method( $pm_id );
			if ( $obj instanceof MeprBaseRealGateway ) {
				$gateways[ $obj->id ] = sprintf( '%1$s (%2$s)', $obj->label, $obj->name );
			}
		}

		add_filter(
			'automator_option_select_field',
			function( $option ) {
				if ( $option['option_code'] === 'MP_SUB_STATUS' && true === $option['required'] ) {
					$option['required'] = false;
				}

				return $option;
			}
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->memberpress->pro->all_memberpress_products( __( 'Membership', 'uncanny-automator-pro' ), $this->action_meta, array( 'uo_include_any' => false ) ),
						Automator()->helpers->recipe->field->text_field( 'SUBTOTAL', __( 'Subtotal', 'uncanny-automator-pro' ), false, 'text', '0', false ),
						Automator()->helpers->recipe->field->text_field( 'TAXAMOUNT', __( 'Tax amount', 'uncanny-automator-pro' ), false, 'text', '0', false ),
						Automator()->helpers->recipe->field->text_field( 'TAXRATE', __( 'Tax rate', 'uncanny-automator-pro' ), false, 'text', '', false ),
						Automator()->helpers->recipe->field->select_field( 'STATUS', __( 'Status', 'uncanny-automator-pro' ), $taxn_status ),
						Automator()->helpers->recipe->field->select_field( 'MP_SUB_STATUS', __( 'Subscription status', 'uncanny-automator-pro' ), $subscription_status ),
						Automator()->helpers->recipe->field->select_field( 'GATEWAY', __( 'Gateway', 'uncanny-automator-pro' ), $gateways ),
						Automator()->helpers->recipe->field->text_field( 'EXPIRATIONDATE', __( 'Expiration date', 'uncanny-automator-pro' ), false, 'text', '', false, __( 'Leave empty to use expiry settings from the membership, or type a specific date in the format YYYY-MM-DD', 'uncanny-automator-pro' ) ),
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'SENDWELCOMEEMAIL',
								/* translators: allow special characters field */
								'required'    => false,
								'label'       => __( 'Send welcome email', 'uncanny-automator-pro' ),
								'input_type'  => 'checkbox',
								'is_toggle'   => true,
								'description' => __( 'If a welcome email is set in MemberPress for this membership, send it to the user when they are added.', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function add_membership( $user_id, $action_data, $recipe_id, $args ) {
		$product_id         = Automator()->parse->text( $action_data['meta'][ $this->action_meta ], $recipe_id, $user_id, $args );
		$sub_total          = Automator()->parse->text( $action_data['meta']['SUBTOTAL'], $recipe_id, $user_id, $args );
		$tax_amount         = Automator()->parse->text( $action_data['meta']['TAXAMOUNT'], $recipe_id, $user_id, $args );
		$tax_rate           = Automator()->parse->text( $action_data['meta']['TAXRATE'], $recipe_id, $user_id, $args );
		$tnx_status         = Automator()->parse->text( $action_data['meta']['STATUS'], $recipe_id, $user_id, $args );
		$sub_status         = Automator()->parse->text( $action_data['meta']['MP_SUB_STATUS'], $recipe_id, $user_id, $args );
		$gateway            = Automator()->parse->text( $action_data['meta']['GATEWAY'], $recipe_id, $user_id, $args );
		$expiration_date    = Automator()->parse->text( $action_data['meta']['EXPIRATIONDATE'], $recipe_id, $user_id, $args );
		$send_welcome_email = isset( $action_data['meta']['SENDWELCOMEEMAIL'] ) ? sanitize_text_field( $action_data['meta']['SENDWELCOMEEMAIL'] ) : '';

		$product = new MeprProduct( sanitize_key( $product_id ) );

		$txn  = new MeprTransaction();
		$user = new MeprUser();
		$user->load_user_data_by_id( $user_id );

		$txn->trans_num  = uniqid( 'ua-mp-' );
		$txn->user_id    = $user->ID;
		$txn->product_id = (int) $product_id;
		$txn->amount     = (float) $sub_total;
		$txn->tax_amount = (float) $tax_amount;
		$txn->total      = ( (float) $sub_total + (float) $tax_amount );
		$txn->tax_rate   = (float) $tax_rate;
		$txn->status     = sanitize_text_field( $tnx_status );
		$txn->gateway    = sanitize_text_field( $gateway );
		$txn->created_at = MeprUtils::ts_to_mysql_date( time() );

		$sub = false;
		// Check if it is not a one-time payment.
		if ( ! $product->is_one_time_payment() ) {
			$sub = new MeprSubscription();

			$sub->user_id                    = $user->ID;
			$sub->subscr_id                  = apply_filters( 'automator_mepr_subscr_id', 'ua-ts_' . uniqid(), $product_id );
			$sub->product_id                 = $product_id;
			$sub->price                      = isset( $sub_total ) ? MeprUtils::format_currency_us_float( $sub_total ) : MeprUtils::format_currency_us_float( $product->price );
			$sub->period                     = $product->period;
			$sub->period_type                = (string) $product->period_type;
			$sub->limit_cycles               = $product->limit_cycles;
			$sub->limit_cycles_num           = (int) $product->limit_cycles_num;
			$sub->limit_cycles_action        = $product->limit_cycles_action;
			$sub->limit_cycles_expires_after = (int) $product->limit_cycles_expires_after;
			$sub->limit_cycles_expires_type  = (string) $product->limit_cycles_expires_type;
			$sub->tax_amount                 = MeprUtils::format_currency_us_float( $txn->tax_amount );
			$sub->tax_rate                   = MeprUtils::format_currency_us_float( $tax_rate );
			$sub->total                      = MeprUtils::format_currency_us_float( $sub->price + $sub->tax_amount );
			$sub->status                     = $sub_status;
			$sub->gateway                    = $gateway;
			$sub->trial                      = $product->trial;
			$sub->trial_days                 = (int) $product->trial_days;
			$sub->trial_amount               = MeprUtils::format_currency_us_float( $product->trial_amount );
			$sub->trial_tax_amount           = ( isset( $trial_tax_amount ) ? (float) $trial_tax_amount : 0.0 );
			$sub->trial_total                = $sub->trial_amount;
			$sub->created_at                 = $txn->created_at;
			$sub->store();

			if ( $sub->id ) {
				$sub                  = new MeprSubscription( $sub->id );
				$txn->subscription_id = $sub->id;
			}
		}

		if ( isset( $expiration_date ) && ( $expiration_date === '' || is_null( $expiration_date ) ) ) {
			$expires_at_ts = $product->get_expires_at();
			if ( is_null( $expires_at_ts ) ) {
				$txn->expires_at = MeprUtils::db_lifetime();
			} else {
				$txn->expires_at = MeprUtils::ts_to_mysql_date( $expires_at_ts, 'Y-m-d 23:59:59' );
			}
		} else {
			$txn->expires_at = MeprUtils::ts_to_mysql_date( strtotime( $expiration_date ), 'Y-m-d 23:59:59' );
		}

		//If this subscription has a paid trail, we need to change the price of this transaction to the trial price yo!
		if ( $sub && $sub->trial ) {
			$txn->set_subtotal( MeprUtils::format_float( $sub->trial_amount ) );
			$expires_ts      = time() + MeprUtils::days( $sub->trial_days );
			$txn->expires_at = gmdate( 'c', $expires_ts );
		}

		$txn->store();

		if ( 'true' === $send_welcome_email ) {
			$sent                              = MeprUtils::maybe_send_product_welcome_notices( $txn, $user, false );
			$action_data['welcome_email_sent'] = $sent;
		}

		if ( $txn->status == MeprTransaction::$complete_str ) {

			$this->maybe_process_corp_account( $product_id, $txn );

			MeprEvent::record( 'transaction-completed', $txn );

			// This is a recurring payment
			if ( ( $sub = $txn->subscription() ) && $sub->txn_count > 1 ) {
				MeprEvent::record(
					'recurring-transaction-completed',
					$txn
				);
			} elseif ( ! $sub ) {
				MeprEvent::record(
					'non-recurring-transaction-completed',
					$txn
				);
			}
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Conditionally creates and stores a corporate account entry for a given object and product.
	 *
	 * This function checks whether the product is marked as a corporate product and,
	 * if the object is not already associated with a corporate account, creates and stores a new one.
	 *
	 * @param int   $product_id 			The ID of the product to check corporate status for.
	 * @param MeprTransaction $transaction  The txn object
	 *
	 * @return void
	 */
	private function maybe_process_corp_account( $product_id, MeprTransaction $transaction ) {
		if (class_exists('MPCA_Corporate_Account')) {
			$obj = $transaction;
			$type = 'transactions';

			//For Subscriptions that have a free coupon applied, we need to create the CA under the transaction
			if ($transaction->subscription_id > 0 && ($transaction->coupon_id < 1 || ($transaction->coupon_id > 0 && $transaction->amount > 0.00))) {
				$obj = $transaction->subscription();
				$type = 'subscriptions';
			}

			$is_corporate_product = get_post_meta($product_id, 'mpca_is_corporate_product', true);
			$num_sub_accounts = get_post_meta($product_id, 'mpca_num_sub_accounts', true);

			if ($is_corporate_product) {
				// check if obj has already been added to corporate_accounts db
				if (is_object(\MPCA_Corporate_Account::find_corporate_account_by_obj_id($obj->id, $type))) {
					return;
				}

				// create corporate account using the information from above
				$ca = new \MPCA_Corporate_Account();
				$ca->obj_id = $obj->id;
				$ca->obj_type = $type;
				$ca->num_sub_accounts = $num_sub_accounts;
				$ca->user_id = $obj->user_id;
				$ca->store();
			}
		}
	}

}
