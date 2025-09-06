<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_ORDERREFUNDEDASSCPRODUCT
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_ORDERREFUNDEDASSCPRODUCT {

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
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANONWCORDERREFUNDEDASSCPRODUCT';
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
			'sentence'            => sprintf( esc_attr__( '{{A product:%1$s}} has its associated order refunded', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Anonymous trigger - WooCommerce */
			'select_option_name'  => esc_attr__( '{{A product}} has its associated order refunded', 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_refund_created',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'order_refunded' ),
			'options_callback'    => array( $this, 'load_options' ),
		);
		$trigger = Woocommerce_Pro_Helpers::add_loopable_tokens( $trigger );

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {

		$options = Automator()->helpers->recipe->woocommerce->options->all_wc_products( __( 'Product', 'uncanny-automator' ), $this->trigger_meta );

		$options['options'] = array( '-1' => __( 'Any product', 'uncanny-automator' ) ) + $options['options'];
		$options_array      = array( 'options' => array( $options ) );

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $refund_id
	 * @param $args
	 */
	public function order_refunded( $refund_id, $args ) {

		if ( ! $refund_id ) {
			return;
		}

		if ( $args['amount'] < 0 ) {
			return;
		}

		$refund   = new \WC_Order_Refund( $refund_id );
		$order_id = $refund->get_parent_id();

		$items              = $args['line_items'];
		$product_ids        = array();
		$matched_product_id = array();
		$matched_recipe_ids = array();
		$product_refunded   = array();

		if ( empty( $items ) ) {
			return;
		}

		/** @var \WC_Order_Item_Product $item */
		foreach ( $items as $item_id => $item_row ) {

			if ( 0 < $item_row['qty'] ) {
				$item            = new \WC_Order_Item_Product( $item_id );
				$item_product_id = $item->get_product_id();

				// is item a valid product?
				if ( 0 < $item_product_id ) {
					$product_ids[]                        = $item_product_id;
					$product_refunded[ $item_product_id ] = $item_row;
				}
			}
		}

		if ( empty( $product_ids ) ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$required_product = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		if ( empty( $required_product ) ) {
			return;
		}

		/**
		 * Match Product IDs first!
		 */
		foreach ( $recipes as $recipe_id => $recipe ) {

			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				// Check if the product matches the refunded order
				if ( intval( '-1' ) === intval( $required_product[ $recipe_id ][ $trigger_id ] ) || in_array( (int) $required_product[ $recipe_id ][ $trigger_id ], array_map( 'absint', $product_ids ), true ) ) {
					$product_id = $required_product[ $recipe_id ][ $trigger_id ];
					// Logic for "Any" product
					if ( intval( '-1' ) === intval( $product_id ) ) {
						foreach ( $product_ids as $p_id ) {

							$matched_recipe_ids[] = array(
								'recipe_id'  => $recipe_id,
								'trigger_id' => $trigger_id,
								'product_id' => $p_id,
							);
						}
					} else {

						$matched_recipe_ids[ $recipe_id ] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
							'product_id' => $product_id,
						);

					}
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		$user_id = get_current_user_id();

		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'post_id'          => $matched_recipe_id['product_id'],
			);

			if ( 0 !== $user_id ) {
				$pass_args['is_signed_in'] = true;
			}

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			//Adding an action to save order id in trigger meta
			do_action( 'uap_wc_trigger_save_meta', $order_id, $matched_recipe_id['recipe_id'], $args, 'order' );

			if ( empty( $args ) ) {
				return;
			}

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

					// Refunded qty related to the line item
					Automator()->db->token->save( 'ORDERREFUNDED_PRODUCT_QTY', $product_refunded[ $matched_recipe_id['product_id'] ]['qty'], $trigger_meta );

					// Refunded amount related to the line item
					Automator()->db->token->save( 'ORDERREFUNDED_PRODUCT_AMOUNT', $product_refunded[ $matched_recipe_id['product_id'] ]['refund_total'], $trigger_meta );

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
