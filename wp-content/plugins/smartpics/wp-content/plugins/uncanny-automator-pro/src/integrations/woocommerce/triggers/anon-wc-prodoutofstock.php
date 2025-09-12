<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_PRODOUTOFSTOCK
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_PRODOUTOFSTOCK {

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
		$this->trigger_code = 'WCPRODOUTOFSTOCK';
		$this->trigger_meta = 'WOOPRODUCTSTOCK';
		//$this->define_trigger();

		add_filter(
			'uap_option_all_wc_products',
			array(
				$this,
				'remove_product_qty_token',
			)
		);
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
			'sentence'            => sprintf( __( ' {{A product:%1$s}} is out of stock', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WooCommerce */
			'select_option_name'  => __( '{{A product}} is out of stock', 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_no_stock',
			'priority'            => 90,
			'accepted_args'       => 2,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'product_out_of_stock' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {

		$options = Automator()->helpers->recipe->woocommerce->pro->all_wc_products( __( 'Product', 'uncanny-automator' ), $this->trigger_meta );

		$options['options'] = array( '-1' => __( 'Any product', 'uncanny-automator' ) ) + $options['options'];
		$options_array      = array( 'options' => array( $options ) );

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $id Product
	 */
	public function product_out_of_stock( $product ) {

		if ( 'product' !== get_post_type( $product->get_id() ) ) {
			return;
		}

		$product_id         = $product->get_id();
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		//Add where option is set to Any post / specific post
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( intval( '-1' ) === intval( $required_post[ $recipe_id ][ $trigger_id ] ) || absint( $required_post[ $recipe_id ][ $trigger_id ] ) === absint( $product_id ) ) {
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

		$user_id = get_current_user_id();

		//	If recipe matches
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'post_id'          => $product_id,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {

						if ( isset( $result['args'] ) && isset( $result['args']['trigger_log_id'] ) ) {
							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => (int) $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);

							Automator()->db->token->save( 'product_id', $product_id, $trigger_meta );
							Automator()->db->token->save( 'product_stock', get_post_meta( $product_id, '_stock', true ), $trigger_meta );
							Automator()->db->token->save( 'product_sku', get_post_meta( $product_id, '_sku', true ), $trigger_meta );
						}

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

	public function remove_product_qty_token( $option ) {
		if ( isset( $option['relevant_tokens']['WOOPRODUCTSTOCK_ORDER_QTY'] ) ) {
			unset( $option['relevant_tokens']['WOOPRODUCTSTOCK_ORDER_QTY'] );
		}

		return $option;
	}

}
