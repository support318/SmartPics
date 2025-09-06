<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_ORDERSTATUSCHANGE
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_ORDERSTATUSCHANGE {

	/**
	 * Integration code
	 * @var string
	 */
	public static $integration = 'WC';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;


	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANONWCORDERSTATUSCHANGE';
		$this->trigger_meta = 'WCORDERSTATUS';
		$this->define_trigger();
	}

	/**
	 *
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/woocommerce/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - WooCommerce */
			'sentence'            => sprintf( __( "A guest order's status is changed to {{a specific status:%1\$s}}", 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WooCommerce */
			'select_option_name'  => __( "A guest order's status is changed to {{a specific status}}", 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_order_status_changed',
			'priority'            => 30,
			'type'                => 'anonymous',
			'accepted_args'       => 4,
			'validation_function' => array( $this, 'order_status_changed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);
		$trigger = Woocommerce_Pro_Helpers::add_loopable_tokens( $trigger );

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$options_array = array(
			'options' => array(
				Automator()->helpers->recipe->woocommerce->options->wc_order_statuses( null, $this->trigger_meta ),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * @param $order_id
	 * @param $from
	 * @param $to
	 * @param $_order
	 */
	public function order_status_changed( $order_id, $from_status, $to_status, $_order ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$user_id = $order->get_user_id();
		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		if ( empty( $recipes ) ) {
			return;
		}
		$required_statuses  = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$recipe_id  = absint( $recipe_id );
				$trigger_id = absint( $trigger['ID'] );
				if ( ! isset( $required_statuses[ $recipe_id ] ) || ! isset( $required_statuses[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}
				$status = preg_replace( '/wc\-/', '', $required_statuses[ $recipe_id ][ $trigger_id ] );
				if ( intval( '-1' ) === intval( $status ) || $status === (string) $to_status ) {
					$matched_recipe_ids[ $recipe_id ] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'ignore_post_id'   => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			//Adding an action to save order id in trigger meta
			do_action( 'uap_wc_trigger_save_meta', $order_id, $matched_recipe_id['recipe_id'], $args, 'order' );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {
						// Manually added do_action for loopable tokens.
						do_action( 'automator_loopable_token_hydrate', $result['args'], func_get_args() );
						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
