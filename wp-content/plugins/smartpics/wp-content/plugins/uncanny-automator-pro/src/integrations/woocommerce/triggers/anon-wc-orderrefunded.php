<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_ORDER_REFUNDED
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_ORDERREFUNDED {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WC';

	/**
	 * Trigger code
	 *
	 * @var string
	 */
	private $trigger_code;

	/**
	 * Trigger meta
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANONWCORDERREFUNDED';
		$this->trigger_meta = 'ORDERREFUNDED';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/woocommerce/' ),
			'is_pro'              => true,
			'type'                => 'anonymous',
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - WooCommerce */
			'sentence'            => esc_attr__( 'An order is refunded', 'uncanny-automator-pro' ),
			/* translators: Anonymous trigger - WooCommerce */
			'select_option_name'  => esc_attr__( 'An order is refunded', 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_order_refunded',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'order_refunded' ),
		);
		$trigger = Woocommerce_Pro_Helpers::add_loopable_tokens( $trigger );

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $order_id
	 */
	public function order_refunded( $order_id, $refund_id ) {
		if ( ! $order_id ) {
			return;
		}

		if ( ! $refund_id ) {
			return;
		}

		$user_id = get_current_user_id();
		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );

		foreach ( $recipes as $recipe_id => $recipe ) {
			$pass_args = array(
				'code'           => $this->trigger_code,
				'meta'           => $this->trigger_meta,
				'user_id'        => $user_id,
				'ignore_post_id' => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );
			//Adding an action to save order id in trigger meta
			do_action( 'uap_wc_trigger_save_meta', $order_id, $recipe_id, $args, 'product' );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {

						$trigger_meta = array(
							'trigger_id'     => (int) $result['args']['trigger_id'],
							'user_id'        => $user_id,
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						);

						// Order refund ID
						Automator()->db->token->save( 'ORDER_REFUND_ID', $refund_id, $trigger_meta );

						$refund = new \WC_Order_Refund( $refund_id );
						// Order refund amount
						Automator()->db->token->save( 'ORDER_REFUND_AMOUNT', $refund->get_amount(), $trigger_meta );

						// Order refund reason
						Automator()->db->token->save( 'ORDER_REFUND_REASON', $refund->get_reason(), $trigger_meta );

						// Manually added do_action for loopable tokens.
						do_action( 'automator_loopable_token_hydrate', $result['args'], func_get_args() );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

}
