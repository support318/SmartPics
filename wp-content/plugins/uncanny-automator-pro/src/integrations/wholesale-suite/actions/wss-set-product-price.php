<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Wholesale_Suite_Helpers;

/**
 * Class WSS_SET_PRODUCT_PRICE
 *
 * @package Uncanny_Automator_Pro
 */
class WSS_SET_PRODUCT_PRICE {

	use Recipe\Actions;

	protected $free_helpers;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		if ( ! function_exists( 'wwlc_check_plugin_dependencies' ) ) {
			return;
		}
		$this->setup_action();
		$this->set_helpers( ( new Wholesale_Suite_Pro_Helpers() ) );
		$this->free_helpers = new Wholesale_Suite_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'WHOLESALESUITE' );
		$this->set_action_code( 'WSS_SET_PRICE' );
		$this->set_action_meta( 'WSS_PRODUCT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		/* translators: Action - Wholesale suite */
		$this->set_sentence( sprintf( esc_attr__( 'Set the wholesale price of {{a product:%1$s}} to {{a specific amount:%2$s}} for {{a role:%3$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'WHOLESALE_PRICE:' . $this->get_action_meta(), 'WSS_ROLES:' . $this->get_action_meta() ) );
		/* translators: Action - Wholesale suite */
		$this->set_readable_sentence( esc_attr__( 'Set the wholesale price of {{a product}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						$this->get_helpers()->all_wc_products( null, $this->get_action_meta(), false, true, true ),
						$this->free_helpers->get_all_wss_roles( null, 'WSS_ROLES', false, true, false ),
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'WHOLESALE_PRICE',
								'input_type'  => 'text',
								'label'       => esc_attr__( 'Wholesale price', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);

	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$product_id      = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$role            = isset( $parsed['WSS_ROLES'] ) ? sanitize_text_field( $parsed['WSS_ROLES'] ) : '';
		$wholesale_price = isset( $parsed['WHOLESALE_PRICE'] ) ? sanitize_text_field( $parsed['WHOLESALE_PRICE'] ) : '';

		if ( empty( $product_id ) ) {
			return;
		}

		if ( intval( '-1' ) !== intval( $product_id ) && is_null( get_post( $product_id ) ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, sprintf( __( 'A product matching (%s) was not found', 'uncanny-automator-pro' ), $parsed[ $this->get_action_meta() . '_readable' ] ) );

			return;
		}

		if ( intval( '-1' ) === intval( $role ) ) {
			$wwp_wholesale_role = \WWP_Wholesale_Roles::getInstance();
			$wss_roles          = $wwp_wholesale_role->getAllRegisteredWholesaleRoles();
			foreach ( $wss_roles as $role_name => $role_info ) {
				$this->update_product_wholesale_price( $role_name, $product_id, $wholesale_price );
			}
		}
		$this->update_product_wholesale_price( $role, $product_id, $wholesale_price );
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/***
	 * @param $role
	 * @param $product
	 * @param $price
	 *
	 * @return bool
	 */
	public function update_product_wholesale_price( $role, $product_id, $price ) {
		if ( intval( '-1' ) === intval( $product_id ) ) {
			$args     = array(
				'post_type'      => 'product',
				'posts_per_page' => 999,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			);
			$products = wc_get_products( $args );
			foreach ( $products as $product ) {
				update_post_meta( $product->get_id(), $role . '_wholesale_price', $price );
				update_post_meta( $product->get_id(), $role . '_have_wholesale_price', 'yes' );

			}

			return true;
		}
		update_post_meta( $product_id, $role . '_wholesale_price', $price );
		update_post_meta( $product_id, $role . '_have_wholesale_price', 'yes' );

		return true;
	}

}
