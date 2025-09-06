<?php

namespace Uncanny_Automator_Pro;

use WC_Order_Item_Product;

/**
 * Class WC_PURCHVARIABLEPRROD
 *
 * @package Uncanny_Automator_Pro
 */
class WC_PURCHVARIABLEPROD {
	/**
	 * Integration code
	 *
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
	 * @var string
	 */
	private $trigger_condition;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code      = 'WCPURCHVARIPROD';
		$this->trigger_meta      = 'WOOVARIPRODUCT';
		$this->trigger_condition = 'TRIGGERCOND';
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
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - WooCommerce */
			'sentence'            => sprintf( __( 'A user {{completes, pays for, lands on a thank you page for:%3$s}} {{A variable product:%1$s}} with {{a variation:%2$s}} selected', 'uncanny-automator-pro' ), 'WOOVARIABLEPRODUCTS:' . $this->trigger_meta, $this->trigger_meta, $this->trigger_condition . ':' . $this->trigger_meta ),
			/* translators: Logged-in trigger - WooCommerce */
			'select_option_name'  => __( 'A user {{completes, pays for, lands on a thank you page for}} {{a variable product}} with {{a variation}} selected', 'uncanny-automator-pro' ),
			'action'              => array(
				'woocommerce_order_status_completed',
				'woocommerce_thankyou',
				'woocommerce_payment_complete',
			),
			'priority'            => 999,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'wc_payment_completed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);
		$trigger = Woocommerce_Pro_Helpers::add_loopable_tokens( $trigger );
		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$trigger_conditions                  = Automator()->helpers->recipe->woocommerce->pro->get_woocommerce_trigger_conditions( $this->trigger_condition );
		$trigger_conditions['default_value'] = 'woocommerce_order_status_completed';

		$options_array = array(
			'options_group' => array(
				$this->trigger_meta => array(
					Automator()->helpers->recipe->woocommerce->options->pro->all_wc_variable_products(
						__( 'Product', 'uncanny-automator-pro' ),
						'WOOVARIABLEPRODUCTS',
						array(
							'token'        => false,
							'is_ajax'      => true,
							'target_field' => $this->trigger_meta,
							'endpoint'     => 'select_variations_from_WOOSELECTVARIATION_with_any_option',
						)
					),
					Automator()->helpers->recipe->field->select_field(
						$this->trigger_meta,
						__( 'Variation', 'uncanny-automator-pro' )
					),
					$trigger_conditions,
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $order_id
	 */
	public function wc_payment_completed( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$user_id            = $order->get_customer_id();
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_product   = Automator()->get->meta_from_recipes( $recipes, 'WOOVARIABLEPRODUCTS' );
		$required_variation = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$required_condition = Automator()->get->meta_from_recipes( $recipes, $this->trigger_condition );
		$matched_recipe_ids = array();
		$trigger_cond_ids   = array();
		if ( ! $recipes ) {
			return;
		}

		if ( ! $required_variation ) {
			return;
		}

		//Add where Product ID is set for trigger
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = absint( $trigger['ID'] );
				if ( ! isset( $required_condition[ $recipe_id ] ) && ! isset( $required_condition[ $recipe_id ][ $trigger_id ] ) ) {
					if ( 'completed' === $order->get_status() ) {
						// fallback option for older triggers
						// that do not have trigger conditions set
						$trigger_cond_ids[] = $recipe_id;
					}
					continue;
				}
				if ( current_action() === (string) $required_condition[ $recipe_id ][ $trigger_id ] ) {
					$trigger_cond_ids[] = $recipe_id;
				}
			}
		}

		if ( empty( $trigger_cond_ids ) ) {
			return;
		}

		if ( 'woocommerce_order_status_completed' === current_action() ) {
			if ( 'completed' !== $order->get_status() ) {
				return;
			}
		}

		$items              = $order->get_items();
		$product_variations = array();
		$product_ids        = array();
		/** @var WC_Order_Item_Product $item */
		foreach ( $items as $item ) {
			$product_ids[]        = (int) $item->get_product_id();
			$product_variations[] = (int) $item->get_variation_id();
		}

		//Add where Product ID is set for trigger
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$recipe_id  = absint( $recipe_id );
				$trigger_id = absint( $trigger['ID'] );
				if ( ! in_array( $recipe_id, $trigger_cond_ids, true ) ) {
					continue;
				}
				if ( ! isset( $required_product[ $recipe_id ] ) || ! isset( $required_product[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}
				if ( ! isset( $required_variation[ $recipe_id ] ) || ! isset( $required_variation[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}
				if (
					(
						intval( '-1' ) === intval( $required_product[ $recipe_id ][ $trigger_id ] ) ||
						in_array( absint( $required_product[ $recipe_id ][ $trigger_id ] ), $product_ids, true )
					)
					&&
					(
						intval( '-1' ) === intval( $required_variation[ $recipe_id ][ $trigger_id ] ) ||
						in_array( absint( $required_variation[ $recipe_id ][ $trigger_id ] ), $product_variations, true )
					)
				) {
					$matched_recipe_ids[] = array(
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
			do_action( 'uap_wc_trigger_save_meta', $order_id, $matched_recipe_id['recipe_id'], $args, 'product' );

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
