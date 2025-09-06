<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_USER_PURCHASES_PRODUCT_NUM_TIMES_IN_DAYS
 *
 * @package Uncanny_Automator_Pro
 */
class WC_USER_PURCHASES_PRODUCT_NUM_TIMES_IN_DAYS extends \Uncanny_Automator_Pro\Action_Condition {

	public function define_condition() {
		$this->integration   = 'WC';
		$this->name          = esc_attr_x( 'The user has purchased {{a product}} in the past {{number of days}}', 'WooCommerce', 'uncanny-automator-pro' );
		$this->dynamic_name  = sprintf( esc_html_x( 'The user {{has/has not:%1$s}} purchased {{a product:%2$s}} {{a number of times:%3$s}} in the past {{number of days:%4$s}}', 'WooCommerce', 'uncanny-automator-pro' ), 'CONDITION', 'PRODUCT', 'TIMES', 'DAYS' );
		$this->code          = 'USER_PURCHASES_PRODUCT_NUM_TIMES_IN_DAYS';
		$this->requires_user = true;
	}

	/**
	 * Method fields
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

		$condition_options = array(
			array(
				'value' => 'has',
				'text'  => 'Has',
			),
			array(
				'value' => 'has_not',
				'text'  => 'Has not',
			),
		);

		return array(
			$this->field->select_field_args(
				array(
					'option_code'           => 'CONDITION',
					'label'                 => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => $condition_options,
					'supports_custom_value' => true,
				)
			),
			$this->field->select_field_args( $products_field_args ),
			$this->field->text(
				array(
					'option_code'            => 'TIMES',
					'label'                  => esc_html__( 'Times', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => true,
					'input_type'             => 'int',
					'required'               => true,
					'default'                => 1,
				)
			),
			$this->field->text(
				array(
					'option_code'            => 'DAYS',
					'label'                  => esc_html__( 'Days', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => true,
					'input_type'             => 'int',
					'required'               => true,
					'default'                => 30,
				)
			),
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
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$condition    = $this->get_option( 'CONDITION' );
		$product_id   = $this->get_parsed_option( 'PRODUCT' );
		$product_name = $this->get_option( 'PRODUCT_readable' );
		$times        = absint( $this->get_parsed_option( 'TIMES' ) );
		$days         = absint( $this->get_parsed_option( 'DAYS' ) );
		$validate     = absint( $this->wc_customer_bought_product_num_times_in_days( $this->user_id, $product_id, $days ) );

		if ( 'has' === $condition ) {
			if ( 0 === $validate ) {
				$log_error = sprintf( __( 'The user has not purchased %1$s in the past %2$d days', 'uncanny-automator-pro' ), $product_name, $days ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				$this->condition_failed( $log_error );
			}
			if ( $validate > 0 && $times !== $validate ) {
				$log_error = sprintf( __( 'The user has not purchased %1$s %2$d times in the past %3$d days', 'uncanny-automator-pro' ), $product_name, $times, $days ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				$this->condition_failed( $log_error );
			}
		}

		if ( 'has_not' === $condition ) {
			if ( $validate > 0 && $times < $validate ) {
				$log_error = sprintf( __( 'The user has purchased %1$s in the past %2$d days', 'uncanny-automator-pro' ), $product_name, $days ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				$this->condition_failed( $log_error );
			}
			if ( $validate > 0 && $times !== $validate ) {
				$log_error = sprintf( __( 'The user has purchased %1$s %2$d times in the past %3$d days', 'uncanny-automator-pro' ), $product_name, $times, $days ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				$this->condition_failed( $log_error );
			}
		}
	}

	/**
	 * @param $user_id
	 * @param $product_id
	 * @param $days
	 *
	 * @return int
	 */
	public function wc_customer_bought_product_num_times_in_days( $user_id, $product_id, $days ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof \WP_User ) {
			return 0;
		}

		global $wpdb;

		if ( intval( '-1' ) !== intval( $product_id ) ) {
			$return = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(meta_value)
FROM {$wpdb->prefix}woocommerce_order_itemmeta
WHERE meta_key = %s
  AND order_item_id IN (
  SELECT im.order_item_id
  FROM {$wpdb->prefix}woocommerce_order_items o
      JOIN {$wpdb->prefix}woocommerce_order_itemmeta im
          ON o.order_item_id = im.order_item_id
      JOIN $wpdb->postmeta pm
          ON o.order_id = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %d
      JOIN $wpdb->posts p
          ON o.order_id = p.ID
  WHERE p.post_date >= %s
AND im.meta_key = %s AND im.meta_value = %d
)",
					'_qty',
					'_customer_user',
					$user_id,
					wp_date( 'Y-m-d', strtotime( '-' . $days . ' days' ) ),
					'_product_id',
					$product_id
				)
			);
		} else {
			$return = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(meta_value)
FROM {$wpdb->prefix}woocommerce_order_itemmeta
WHERE meta_key = %s
  AND order_item_id IN (
  SELECT im.order_item_id
  FROM {$wpdb->prefix}woocommerce_order_items o
      JOIN {$wpdb->prefix}woocommerce_order_itemmeta im
          ON o.order_item_id = im.order_item_id
      JOIN $wpdb->postmeta pm
          ON o.order_id = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %d
      JOIN $wpdb->posts p
          ON o.order_id = p.ID
  WHERE p.post_date >= %s
)",
					'_qty',
					'_customer_user',
					$user_id,
					wp_date( 'Y-m-d', strtotime( '-' . $days . ' days' ) ),
				)
			);
		}

		return $return;
	}
}
