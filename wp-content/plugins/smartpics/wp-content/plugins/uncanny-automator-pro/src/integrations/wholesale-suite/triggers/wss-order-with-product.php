<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class WSS_ORDER_WITH_PRODUCT {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->setup_trigger();
		$this->set_helper( new Wholesale_Suite_Pro_Helpers() );
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WHOLESALESUITE' );
		$this->set_trigger_code( 'WSS_ORDER_PLACED' );
		$this->set_trigger_meta( 'WSS_PRODUCT' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/wholesale-suite/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( 'A wholesale order for {{a specific product:%1$s}} is received', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A wholesale order for {{a specific product}} is received', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( '_wwp_add_order_meta' );
		$this->set_action_args_count( 3 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->all_wc_products( null, $this->get_trigger_meta(), true ),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $order_id, $posted_data, $user_wholesale_role ) = array_shift( $args );

		if ( empty( $order_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check contact status against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $order_id, $posted_data, $user_wholesale_role ) = $args[0];
		$order                                                = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$items = $order->get_items();
		/** @var \WC_Order_Item_Product $item */
		foreach ( $items as $item ) {
			$matched = $this->find_all( $this->trigger_recipes() )
							->where( array( $this->get_trigger_meta() ) )
							->match( array( $item->get_product_id() ) )
							->format( array( 'intval' ) )
							->get();
		}

		return $matched;

	}

}
