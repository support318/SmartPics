<?php

namespace Uncanny_Automator_Pro;

use MeprHooks;
use MeprSubscription;

/**
 * Class MP_CANCELMEMBERSHIP
 *
 * @package Uncanny_Automator_Pro
 */
class MP_CANCELMEMBERSHIP {
	use Recipe\Action_Tokens;

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
		$this->action_code = 'MPCANCELMEMBERSHIP';
		$this->action_meta = 'MPUSERMEMBERSHIP';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/memberpress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - MemberPress */
			'sentence'           => sprintf( __( "Cancel the user's {{recurring membership:%1\$s}}", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MemberPress */
			'select_option_name' => __( "Cancel the user's {{recurring membership}}", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'cancel_memberships' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'CANCELLEDSUBS'        => array(
					'name' => __( 'Cancelled Subscriptions', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
				'CANCELLEDSUBS_AMOUNT' => array(
					'name' => __( 'Cancelled Subscriptions Amount', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->all_memberpress_products_recurring(
						null,
						$this->action_meta,
						array( 'uo_include_any' => false )
					),
				),
			),
		);
	}

	/**
	 * Get Helper Class.
	 *
	 * @return Memberpress_Pro_Helpers
	 */
	public function get_helper() {
		static $helper = null;
		if ( is_null( $helper ) ) {
			$helper = new Memberpress_Pro_Helpers( false );
		}

		return $helper;
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function cancel_memberships( $user_id, $action_data, $recipe_id, $args ) {

		$membership = Automator()->parse->text( $action_data['meta'][ $this->action_meta ], $recipe_id, $user_id, $args );
		$user_obj   = get_user_by( 'id', $user_id );
		$table      = MeprSubscription::account_subscr_table(
			'created_at',
			'DESC',
			'',
			'',
			'any',
			'',
			false,
			array(
				'member'   => $user_obj->user_login,
				'statuses' => array(
					MeprSubscription::$active_str,
					MeprSubscription::$suspended_str,
				),
			),
			MeprHooks::apply_filters(
				'mepr_user_subscriptions_query_cols',
				array(
					'id',
					'product_id',
					'created_at',
				)
			)
		);

		$error_message                 = '';
		$subscription_cancelled        = array();
		$subscription_cancelled_amount = 0;
		if ( $table['count'] > 0 ) {
			foreach ( $table['results'] as $row ) {
				if ( $row->sub_type == 'subscription' && ( $membership === $row->product_id || intval( '-1' ) === intval( $membership ) ) ) {
					$sub = new MeprSubscription( $row->id );
					try {
						if ( $sub->status !== MeprSubscription::$cancelled_str ) {
							$_REQUEST['silent'] = true; // Don't want to send cancellation notices.
							$sub->expire_txns(); //Expire associated transactions for the subscription
							$cancelled                     = $sub->cancel();
							$subscription_cancelled[]      = $row->id;
							$amount                        = ( $sub->total >= 0.00 ) ? $sub->total : $sub->price;
							$subscription_cancelled_amount = $subscription_cancelled_amount + $amount;
						}
					} catch ( \Exception $e ) {
						$error_message = $e->getMessage();
					}
				}
			}
		}

		$subscription_cancelled_str = '';
		if ( ! empty( $subscription_cancelled ) ) {
			$subscription_cancelled_str = implode( ',', $subscription_cancelled );
		}

		$this->hydrate_tokens(
			array(
				'MPUSERMEMBERSHIP'           => get_the_title( $membership ),
				'MPUSERMEMBERSHIP_ID'        => $membership,
				'MPUSERMEMBERSHIP_URL'       => get_permalink( $membership ),
				'MPUSERMEMBERSHIP_THUMB_ID'  => get_post_thumbnail_id( $membership ),
				'MPUSERMEMBERSHIP_THUMB_URL' => get_the_post_thumbnail_url( $membership ),
				'CANCELLEDSUBS'              => $subscription_cancelled_str,
				'CANCELLEDSUBS_AMOUNT'       => $subscription_cancelled_amount,
			)
		);

		Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
	}

}
