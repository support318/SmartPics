<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_PRODSTOCKSTATUS
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_PRODSTOCKSTATUS {

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
		$this->trigger_code = 'WCPRODSTOCKSTATUS';
		$this->trigger_meta = 'WOOPRODUCTSTOCKSTATUS';
		$this->define_trigger();

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
			'sentence'            => sprintf( __( "{{A product's:%1\$s}} inventory status is set to {{a specific status:%2\$s}}", 'uncanny-automator-pro' ), $this->trigger_meta, $this->trigger_meta . '_STOCKSTATUS' ),
			/* translators: Logged-in trigger - WooCommerce */
			'select_option_name'  => __( "{{A product's}} inventory status is set to {{a specific status}}", 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_product_set_stock_status',
			'priority'            => 90,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'product_stock_status' ),
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
		$status_list        = Automator()->helpers->recipe->woocommerce->options->pro->wc_stock_statuses( esc_attr__( 'Status', 'uncanny-automator-pro' ), $this->trigger_meta . '_STOCKSTATUS', true );
		$options_array      = array(
			'options' => array(
				$options,
				$status_list,
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $id Product
	 */
	public function product_stock_status( $product_id, $stock_status, $product ) {

		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$product_id         = $product->get_id();
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$stock_status_pr    = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta . '_STOCKSTATUS' );
		$matched_recipe_ids = array();

		//Add where option is set to Any post / specific post
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( ( intval( '-1' ) === intval( $required_post[ $recipe_id ][ $trigger_id ] ) || absint( $required_post[ $recipe_id ][ $trigger_id ] ) === absint( $product_id ) ) ) {
					if ( $stock_status_pr[ $recipe_id ][ $trigger_id ] === $stock_status || intval( '-1' ) === intval( $stock_status_pr[ $recipe_id ][ $trigger_id ] ) ) {
						$matched_recipe_ids[] = array(
							'recipe_id'  => $recipe_id,
							'trigger_id' => $trigger_id,
						);
					}
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
							Automator()->db->token->save( 'WCPRODSTOCKSTATUS', $stock_status, $trigger_meta );
						}

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

	public function remove_product_qty_token( $option ) {
		if ( isset( $option['relevant_tokens'][ $this->trigger_meta . '_ORDER_QTY' ] ) ) {
			unset( $option['relevant_tokens'][ $this->trigger_meta . '_ORDER_QTY' ] );
		}

		return $option;
	}

}
