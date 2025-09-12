<?php

namespace Uncanny_Automator_Pro;

/***
 * Class ANON_ORDER_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_ORDER_STATUS_CHANGED {

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
		$this->trigger_code = 'ANONORDERSTATUSCHANGED';
		$this->trigger_meta = 'WCORDERSTATUS';
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
			/* translators: Anonymous trigger - WooCommerce */
			'sentence'            => sprintf(
				esc_attr__( '{{A product:%1$s}} has its associated order set to {{a specific status:%2$s}}', 'uncanny-automator-pro' ),
				'WOOPRODUCT',
				$this->trigger_meta
			),
			/* translators: Anonymous trigger - WooCommerce */
			'select_option_name'  => esc_attr__( '{{A product}} has its associated order set to {{a specific status}}', 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_order_status_changed',
			'priority'            => 999,
			'accepted_args'       => 1,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'woo_order_status_changed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );

	}

	/**
	 * @return array
	 */
	public function load_options() {
		$options            = Automator()->helpers->recipe->woocommerce->options->pro->all_wc_products( __( 'Product', 'uncanny-automator' ), 'WOOPRODUCT' );
		$options['options'] = array( '-1' => __( 'Any product', 'uncanny-automator' ) ) + $options['options'];
		$options_array      = array(
			'options' => array(
				$options,
				Automator()->helpers->recipe->woocommerce->options->wc_order_statuses( null, $this->trigger_meta ),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validate if trigger matches the condition.
	 *
	 * @param $order_id
	 */
	public function woo_order_status_changed( $order_id ) {

		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$to_status = $order->get_status();

		$recipes          = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_product = Automator()->get->meta_from_recipes( $recipes, 'WOOPRODUCT' );
		$required_status  = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$user_id          = $order->get_customer_id();
		$items            = $order->get_items();
		/** @var \WC_Order_Item_Product $item */
		foreach ( $items as $item ) {
			$product_id = (int) $item->get_product_id();
			//Add where option is set to Any product
			foreach ( $recipes as $recipe_id => $recipe ) {
				foreach ( $recipe['triggers'] as $trigger ) {
					$trigger_id = absint( $trigger['ID'] );
					$recipe_id  = absint( $recipe_id );

					if ( ! isset( $required_product[ $recipe_id ] ) || ! isset( $required_product[ $recipe_id ][ $trigger_id ] ) ) {
						continue;
					}

					if ( intval( '-1' ) !== intval( $required_product[ $recipe_id ][ $trigger_id ] ) && (int) $required_product[ $recipe_id ][ $trigger_id ] !== $product_id ) {
						continue;
					}

					if ( intval( '-1' ) !== intval( $required_status[ $recipe_id ][ $trigger_id ] ) && str_replace( 'wc-', '', $required_status[ $recipe_id ][ $trigger_id ] ) !== $to_status ) {
						continue;
					}

					$args = $this->run_trigger( $user_id, $recipe_id, $trigger_id );
					//Adding an action to save order id in trigger meta
					do_action( 'uap_wc_trigger_save_meta', $order_id, $recipe_id, $args, 'product' );
					do_action( 'uap_wc_order_item_meta', $item->get_id(), $order_id, $recipe_id, $args );

					$this->complete_trigger( $args );
				}
			}
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return \WC_Order|object
	 */
	public function validate_order( $order_id ) {
		if ( ! $order_id ) {
			return (object) array();
		}

		return wc_get_order( $order_id );
	}

	/**
	 * Run the trigger when all conditions have met
	 *
	 * @param $user_id
	 * @param $recipe_id
	 * @param $trigger_id
	 *
	 * @return array|bool|int|null
	 */
	public function run_trigger( $user_id, $recipe_id, $trigger_id ) {
		$pass_args = array(
			'code'             => $this->trigger_code,
			'meta'             => $this->trigger_meta,
			'user_id'          => $user_id,
			'recipe_to_match'  => $recipe_id,
			'trigger_to_match' => $trigger_id,
			'ignore_post_id'   => true,
		);

		if ( 0 !== $user_id ) {
			$pass_args['is_signed_in'] = true;
		}

		return Automator()->process->user->maybe_add_trigger_entry( $pass_args, false );
	}

	/**
	 * Completing the trigger
	 *
	 * @param $args
	 */
	public function complete_trigger( $args ) {
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $result ) {
			if ( true === $result['result'] ) {
				Automator()->process->user->maybe_trigger_complete( $result['args'] );
			}
		}
	}
}
