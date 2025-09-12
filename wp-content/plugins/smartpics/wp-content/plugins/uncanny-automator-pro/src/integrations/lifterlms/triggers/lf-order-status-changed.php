<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LF_ORDER_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class LF_ORDER_STATUS_CHANGED extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * Set up the trigger.
	 *
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'LF' );
		$this->set_trigger_code( 'LF_ORDER_STATUS_CHANGED' );
		$this->set_trigger_meta( 'LF_ORDER_PRODUCT_TYPE' );
		$this->set_is_pro( true );
		$this->set_readable_sentence( esc_attr_x( "A user's order status of {{a product type}} changes to {{a specific status}}", 'LifterLMS', 'uncanny-automator-pro' ) );
		$this->set_sentence(
			sprintf(
				esc_attr_x(
					"A user's order status of {{a product type:%1\$s}} changes to {{a specific status:%2\$s}}",
					'LifterLMS',
					'uncanny-automator-pro'
				),
				$this->get_trigger_meta(),
				$this->get_trigger_meta() . '_STATUS'
			)
		);
		$this->add_action( 'transition_post_status', 10, 3 );
	}

	/**
	 * Load Options for the trigger.
	 *
	 * @return array
	 */
	public function load_options() {

		$product_types = array(
			'input_type'  => 'select',
			'option_code' => $this->get_trigger_meta(),
			'label'       => esc_attr_x( 'Product type', 'LifterLMS', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'lifter_lms_retrieve_product_types',
				'event'    => 'on_load',
			),
		);

		$order_statuses = array(
			'input_type'  => 'select',
			'option_code' => $this->get_trigger_meta() . '_STATUS',
			'label'       => esc_attr_x( 'Order status', 'LifterLMS', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'lifter_lms_retrieve_order_statuses',
				'event'    => 'on_load',
			),
		);

		return array(
			'options' => array(
				$product_types,
				$order_statuses,
			),
		);
	}

	/**
	 * Validate the trigger.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		// List the arguments passed to the hook.
		list( $new_status, $old_status, $post ) = $hook_args;

		// If the post type is not an order bail.
		if ( ! is_a( $post, '\WP_Post' ) || 'llms_order' !== $post->post_type ) {
			return false;
		}

		// If the new status and old status are the same or empty bail.
		if ( empty( $new_status ) || empty( $old_status ) || $new_status === $old_status ) {
			return false;
		}

		// If the product type and status are not set bail.
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) || ! isset( $trigger['meta'][ $this->get_trigger_meta() . '_STATUS' ] ) ) {
			return false;
		}

		// If status is not the same as the new status bail.
		$status = $trigger['meta'][ $this->get_trigger_meta() . '_STATUS' ];
		if ( $status !== $new_status ) {
			return false;
		}

		// Get the order.
		$order = llms_get_post( $post );
		if ( ! $order || ! is_a( $order, 'LLMS_Order' ) ) {
			return false;
		}

		// If the product type is not the same as the selected product type bail.
		$product_type = $trigger['meta'][ $this->get_trigger_meta() ];
		$product_type = ! empty( $product_type ) ? str_replace( 'llms_', '', $product_type ) : '';
		if ( $product_type !== $order->get( 'product_type' ) ) {
			return false;
		}

		// Validated - set the user ID.
		$this->set_user_id( $order->get( 'user_id' ) );

		return true;
	}

	/**
	 * Define the tokens.
	 *
	 * @param  array $tokens
	 * @param  array $args
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$tokens = ! is_array( $tokens ) ? array() : $tokens;

		$tokens[] = array(
			'tokenId'   => 'LF_ORDER_ID',
			'tokenName' => esc_attr_x( 'Order ID', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$tokens[] = array(
			'tokenId'   => 'LF_ORDER_KEY',
			'tokenName' => esc_attr_x( 'Order key', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'LF_ORDER_PRODUCT_ID',
			'tokenName' => esc_attr_x( 'Product ID', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$tokens[] = array(
			'tokenId'   => 'LF_ORDER_PRODUCT_TITLE',
			'tokenName' => esc_attr_x( 'Product title', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => 'string',
		);

		return $tokens;
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		// List the arguments passed to the hook.
		list( $new_status, $old_status, $post ) = $hook_args;

		// Get the order.
		$order = llms_get_post( $post );
		if ( ! $order || ! is_a( $order, 'LLMS_Order' ) ) {
			return false;
		}

		$meta_key     = $this->get_trigger_meta();
		$trigger_meta = $trigger['meta'] ?? array();

		$token_values = array(
			$meta_key                => $trigger_meta[ $meta_key . '_readable' ] ?? ucfirst( $order->get( 'product_type' ) ),
			$meta_key . '_STATUS'    => $trigger_meta[ $meta_key . '_STATUS_readable' ] ?? ucfirst( str_replace( 'llms-', '', $new_status ) ),
			'LF_ORDER_ID'            => $post->ID,
			'LF_ORDER_KEY'           => $order->get( 'order_key' ),
			'LF_ORDER_PRODUCT_ID'    => $order->get( 'product_id' ),
			'LF_ORDER_PRODUCT_TITLE' => get_the_title( $order->get( 'product_id' ) ),
		);

		return $token_values;
	}
}
