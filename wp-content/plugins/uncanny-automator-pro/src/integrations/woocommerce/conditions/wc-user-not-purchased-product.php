<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_USER_NOT_PURCHASED_PRODUCT
 *
 * @package Uncanny_Automator_Pro
 */
class WC_USER_NOT_PURCHASED_PRODUCT extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'WC';
		/*translators: Token */
		$this->name = __( 'The user has not purchased {{a specific product}}', 'uncanny-automator-pro' );
		$this->code = 'NOT_PURCHASED_A_PRODUCT';
		/*translators: A token matches a value */
		$this->dynamic_name  = sprintf( esc_html__( 'The user has not purchased {{a specific product:%1$s}}', 'uncanny-automator-pro' ), 'PRODUCT' );
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * fields
	 *
	 * @return array
	 */
	public function fields() {

		$products_field_args = array(
			'option_code'           => 'PRODUCT',
			'label'                 => esc_html__( 'Product', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->wc_products_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Course field
			$this->field->select_field_args( $products_field_args ),
		);
	}

	/**
	 * @return array[]
	 */
	public function wc_products_options() {
		$args    = array(
			'post_type'      => 'product',
			'posts_per_page' => 9999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);
		$return  = array();
		$options = Automator()->helpers->recipe->options->wp_query( $args, true, _x( 'Any product', 'uncanny-automator-pro' ) );
		if ( empty( $options ) ) {
			return $return;
		}
		foreach ( $options as $id => $text ) {
			$return[] = array(
				'value' => $id,
				'text'  => $text,
			);
		}

		return $return;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$product_id = $this->get_parsed_option( 'PRODUCT' );

		$validate = wc_customer_bought_product( '', $this->user_id, $product_id );

		// Check if the user has not purchased the specific product
		if ( false !== $validate ) {
			$message = __( 'User has purchased ', 'uncanny-automator-pro' ) . $this->get_option( 'PRODUCT_readable' );
			$this->condition_failed( $message );
		}
	}

}
