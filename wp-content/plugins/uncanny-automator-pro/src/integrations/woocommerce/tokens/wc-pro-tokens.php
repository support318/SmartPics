<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wc_Tokens;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Class Wc_Pro_Tokens
 *
 * @package Uncanny_Automator
 */
class Wc_Pro_Tokens extends Wc_Tokens {

	/**
	 * @var array
	 */
	public $possible_order_fields_pro = array();
	/**
	 * @var array
	 */
	public $item_details = array();

	/**
	 * @var
	 */
	public $order_id;
	/**
	 * @var
	 */
	public $item_id;
	/**
	 * @var
	 */
	public $term_id;

	/**
	 * @var array
	 */
	public $product_details = array();

	/**
	 * Wc_Pro_Tokens constructor.
	 */
	public function __construct( $load_action_hook = true ) {

		if ( true === $load_action_hook ) {
			$this->possible_order_fields_pro = array(
				'billing_first_name'    => esc_attr__( 'Billing first name', 'uncanny-automator' ),
				'billing_last_name'     => esc_attr__( 'Billing last name', 'uncanny-automator' ),
				'billing_company'       => esc_attr__( 'Billing company', 'uncanny-automator' ),
				'billing_country'       => esc_attr__( 'Billing country', 'uncanny-automator' ),
				'billing_country_name'  => esc_attr__( 'Billing country (full name)', 'uncanny-automator' ),
				'billing_address_1'     => esc_attr__( 'Billing address line 1', 'uncanny-automator' ),
				'billing_address_2'     => esc_attr__( 'Billing address line 2', 'uncanny-automator' ),
				'billing_city'          => esc_attr__( 'Billing city', 'uncanny-automator' ),
				'billing_state'         => esc_attr__( 'Billing state', 'uncanny-automator' ),
				'billing_state_name'    => esc_attr__( 'Billing state (full name)', 'uncanny-automator' ),
				'billing_postcode'      => esc_attr__( 'Billing postcode', 'uncanny-automator' ),
				'billing_phone'         => esc_attr__( 'Billing phone', 'uncanny-automator' ),
				'billing_email'         => esc_attr__( 'Billing email', 'uncanny-automator' ),
				'shipping_first_name'   => esc_attr__( 'Shipping first name', 'uncanny-automator' ),
				'shipping_last_name'    => esc_attr__( 'Shipping last name', 'uncanny-automator' ),
				'shipping_company'      => esc_attr__( 'Shipping company', 'uncanny-automator' ),
				'shipping_country'      => esc_attr__( 'Shipping country', 'uncanny-automator' ),
				'shipping_country_name' => esc_attr__( 'Shipping country (full name)', 'uncanny-automator' ),
				'shipping_address_1'    => esc_attr__( 'Shipping address line 1', 'uncanny-automator' ),
				'shipping_address_2'    => esc_attr__( 'Shipping address line 2', 'uncanny-automator' ),
				'shipping_city'         => esc_attr__( 'Shipping city', 'uncanny-automator' ),
				'shipping_state'        => esc_attr__( 'Shipping state', 'uncanny-automator' ),
				'shipping_state_name'   => esc_attr__( 'Shipping state (full name)', 'uncanny-automator' ),
				'shipping_postcode'     => esc_attr__( 'Shipping postcode', 'uncanny-automator' ),
				'order_date'            => esc_attr__( 'Order date', 'uncanny-automator' ),
				'order_id'              => esc_attr__( 'Order ID', 'uncanny-automator' ),
				'order_comments'        => esc_attr__( 'Order comments', 'uncanny-automator' ),
				'order_total'           => esc_attr__( 'Order total', 'uncanny-automator' ),
				'order_total_raw'       => esc_attr__( 'Order total (unformatted)', 'uncanny-automator' ),
				'order_status'          => esc_attr__( 'Order status', 'uncanny-automator' ),
				'order_subtotal'        => esc_attr__( 'Order subtotal', 'uncanny-automator' ),
				'order_subtotal_raw'    => esc_attr__( 'Order subtotal (unformatted)', 'uncanny-automator' ),
				'order_tax'             => esc_attr__( 'Order tax', 'uncanny-automator' ),
				'order_tax_raw'         => esc_attr__( 'Order tax (unformatted)', 'uncanny-automator' ),
				'order_discounts'       => esc_attr__( 'Order discounts', 'uncanny-automator' ),
				'order_discounts_raw'   => esc_attr__( 'Order discounts (unformatted)', 'uncanny-automator' ),
				'order_coupons'         => esc_attr__( 'Order coupons', 'uncanny-automator' ),
				'order_products'        => esc_attr__( 'Order products', 'uncanny-automator' ),
				'order_products_qty'    => esc_attr__( 'Order products and quantity', 'uncanny-automator' ),
				'payment_method'        => esc_attr__( 'Payment method', 'uncanny-automator' ),
				'order_qty'             => esc_attr__( 'Order quantity', 'uncanny-automator' ),
				'order_products_links'  => esc_attr__( 'Order products links', 'uncanny-automator' ),
				'order_summary'         => esc_attr__( 'Order summary', 'uncanny-automator' ),
				'shipping_method'       => esc_attr__( 'Shipping method', 'uncanny-automator' ),
				'order_fees'            => esc_attr__( 'Order fee', 'uncanny-automator' ),
				'order_fees_raw'        => esc_attr__( 'Order fee (unformatted)', 'uncanny-automator' ),
				'order_shipping'        => esc_attr__( 'Shipping fee', 'uncanny-automator' ),
				'order_shipping_raw'    => esc_attr__( 'Shipping fee (unformatted)', 'uncanny-automator' ),
				'user_total_spend'      => esc_attr__( "User's total spend", 'uncanny-automator' ),
				'user_total_spend_raw'  => esc_attr__( "User's total spend (unformatted)", 'uncanny-automator' ),
			);

			if ( function_exists( 'stripe_wc' ) || class_exists( '\WC_Stripe_Helper' ) || function_exists( 'woocommerce_gateway_stripe' ) ) {
				$this->possible_order_fields_pro['stripe_fee']        = esc_attr__( 'Stripe fee', 'uncanny-automator' );
				$this->possible_order_fields_pro['stripe_fee_raw']    = esc_attr__( 'Stripe fee (unformatted)', 'uncanny-automator' );
				$this->possible_order_fields_pro['stripe_payout']     = esc_attr__( 'Stripe payout', 'uncanny-automator' );
				$this->possible_order_fields_pro['stripe_payout_raw'] = esc_attr__( 'Stripe payout (unformatted)', 'uncanny-automator' );
			}

			add_action(
				'uap_wc_trigger_save_meta',
				array(
					$this,
					'uap_wc_trigger_save_meta_func',
				),
				40,
				4
			);

			add_action(
				'uap_wc_order_item_meta',
				array(
					$this,
					'uap_wc_order_item_meta_func',
				),
				40,
				4
			);

			add_action(
				'uap_wc_save_order_item_meta_by_term',
				array(
					$this,
					'uap_wc_save_order_item_meta_by_term_func',
				),
				40,
				1
			);

			add_action(
				'uap_wc_trigger_save_product_meta',
				array(
					$this,
					'uap_wc_trigger_save_product_meta_func',
				),
				40,
				4
			);

			add_filter(
				'automator_maybe_trigger_wc_wooprodcat_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_woovariproduct_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_woosubscriptions_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wooprodtag_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_woocoupons_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);
			add_filter(
				'automator_maybe_trigger_wc_orderpaymentfail_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wcprodreview_tokens',
				array(
					$this,
					'wc_wcprodreview_possible_tokens',
				),
				200,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wooproduct_tokens',
				array(
					$this,
					'wc_wcprodreview_possible_tokens',
				),
				200,
				2
			);

			//Adding WC tokens
			add_filter(
				'automator_maybe_trigger_wc_wcshipstationproductshipped_tokens',
				array(
					$this,
					'wc_order_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wcshipstationordertotalshipped_tokens',
				array(
					$this,
					'wc_order_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_anonwcorderrefunded_tokens',
				array(
					$this,
					'anon_wc_orderrefunded_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_anonwcorderpartrefunded_tokens',
				array(
					$this,
					'anon_wc_orderrefunded_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_woopaymentgateway_tokens',
				array(
					$this,
					'wc_wooprodcat_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_anonorderitemcreated_tokens',
				array(
					$this,
					'wc_wooorderitemadded_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_anonorderstatuschanged_tokens',
				array(
					$this,
					'wc_wooorderitemadded_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_prod_order_status_changed_in_a_term_tokens',
				array(
					$this,
					'wc_wooorderitemadded_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_addedtocart_tokens',
				array(
					$this,
					'wc_addedtocart_possible_tokens',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wooproductstock_tokens',
				array(
					$this,
					'wc_wcprodstock_possible_tokens',
				),
				200,
				2
			);

			add_filter(
				'automator_maybe_trigger_wc_wcprodstockstatus_tokens',
				array(
					$this,
					'wc_wcprodstock_possible_tokens',
				),
				200,
				2
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'wc_wcprodstock_tokens_pro',
				),
				26,
				6
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'wc_wcprodstock_status_tokens_pro',
				),
				26,
				6
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'wc_addedtocart_tokens_pro',
				),
				26,
				6
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'wc_ordertotal_tokens_pro',
				),
				2926,
				6
			);

		}

		add_filter(
			'automator_maybe_parse_token',
			array(
				$this,
				'parse_subscription_tokens_pro',
			),
			36,
			6
		);

		add_filter(
			'automator_maybe_trigger_wc_anonwcorderrefundedasscproduct_tokens',
			array(
				$this,
				'anon_wc_orderrefunded_possible_tokens',
			),
			20,
			2
		);
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
	}

	/**
	 * @param $args
	 * @param $trigger
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		$trigger_meta = $args['entry_args']['meta'];
		$trigger_code = $args['entry_args']['code'];

		if ( 'WCSUBSCRIPTIONSSWITCHED' === $trigger_code ) {
			/**
			 * @var \WC_Order        $order
			 * @var \WC_Subscription $subscription
			 */
			list( $order, $subscription, $add_line_item, $remove_line_item ) = $args['trigger_args'];
			$trigger_log_entry                                               = $args['trigger_entry'];
			if ( ! empty( $subscription ) ) {
				Automator()->db->token->save( 'subscription_id', $subscription->get_id(), $trigger_log_entry );
				Automator()->db->token->save( 'order_id', $order->get_id(), $trigger_log_entry );

				$product_id        = wc_get_order_item_meta( $add_line_item, '_product_id', true );
				$variation_id_from = wc_get_order_item_meta( $remove_line_item, '_variation_id', true );
				$variation_id_to   = wc_get_order_item_meta( $add_line_item, '_variation_id', true );
				Automator()->db->token->save( "{$trigger_meta}_FROM", $variation_id_from, $trigger_log_entry );
				Automator()->db->token->save( "{$trigger_meta}_TO", $variation_id_to, $trigger_log_entry );
				Automator()->db->token->save( $trigger_meta, $product_id, $trigger_log_entry );
			}
		}

		$trigger_meta_validations = apply_filters(
			'automator_wcs_validate_trigger_code_pieces',
			array( 'WCS_PAYMENT_FAILS', 'WC_SUBSCRIPTION_RENEWAL_COUNT' ),
			$args
		);

		/** @var mixed $trigger_meta_validations */
		if ( in_array( $args['entry_args']['code'], $trigger_meta_validations, true ) ) {
			$subscription      = $args['trigger_args'][0];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $subscription ) && $subscription instanceof \WC_Subscription ) {
				Automator()->db->token->save( 'subscription_id', $subscription->get_id(), $trigger_log_entry );
				Automator()->db->token->save( 'order_id', $subscription->get_parent_id(), $trigger_log_entry );
			}
		}

	}

	/**
	 * Method is used to add tokens in variation product stock status update triggers.
	 *
	 * @return array[]
	 */
	public function add_variable_product_tokens() {
		return array(
			'PRODUCT_TITLE'              => array(
				'name' => __( 'Product title', 'uncanny-automator-pro' ),
			),
			'PRODUCT_ID'                 => array(
				'name' => __( 'Product ID', 'uncanny-automator-pro' ),
			),
			'PRODUCT_URL'                => array(
				'name' => __( 'Product URL', 'uncanny-automator-pro' ),
			),
			'PRODUCT_FEATURED_IMAGE_ID'  => array(
				'name' => __( 'Product featured image ID', 'uncanny-automator-pro' ),
			),
			'PRODUCT_FEATURED_IMAGE_URL' => array(
				'name' => __( 'Product featured image URL', 'uncanny-automator-pro' ),
			),
			'PRODUCT_PRICE'              => array(
				'name' => __( 'Product price', 'uncanny-automator-pro' ),
			),
			'PRODUCT_STOCK_STATUS'       => array(
				'name' => __( 'Status', 'uncanny-automator-pro' ),
			),
			'PRODUCT_SKU'                => array(
				'name' => __( 'Product SKU', 'uncanny-automator-pro' ),
			),
			'PRODUCT_CATEGORIES'         => array(
				'name' => __( 'Product categories', 'uncanny-automator-pro' ),
			),
			'PRODUCT_TAGS'               => array(
				'name' => __( 'Product tags', 'uncanny-automator-pro' ),
			),
			'PRODUCT_VARIATION'          => array(
				'name' => __( 'Variation', 'uncanny-automator-pro' ),
			),
			'PRODUCT_VARIATION_ID'       => array(
				'name' => __( 'Variation ID', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function var_inventory_change_tokens_hydrate_tokens( $parsed, $args, $trigger ) {

		list( $product_var_id, $stock_status, $var_product ) = $args['trigger_args'];

		$parent_product_id = $var_product->get_parent_id();
		$categories        = wp_list_pluck( wp_get_post_terms( $parent_product_id, 'product_cat' ), 'name', 'slug' );
		$tags              = wp_list_pluck( wp_get_post_terms( $parent_product_id, 'product_tag' ), 'name', 'slug' );

		$featured_image_url = ( get_the_post_thumbnail_url( $product_var_id ) ) ? get_the_post_thumbnail_url( $product_var_id ) : get_the_post_thumbnail_url( $parent_product_id );
		$featured_image_id  = ( get_post_thumbnail_id( $product_var_id ) ) ? get_post_thumbnail_id( $product_var_id ) : get_post_thumbnail_id( $parent_product_id );

		$product_var_tokens = array(
			'PRODUCT_TITLE'              => get_the_title( $parent_product_id ),
			'PRODUCT_ID'                 => $parent_product_id,
			'PRODUCT_URL'                => $var_product->get_permalink(),
			'PRODUCT_FEATURED_IMAGE_ID'  => $featured_image_id,
			'PRODUCT_FEATURED_IMAGE_URL' => $featured_image_url,
			'PRODUCT_PRICE'              => $var_product->get_price(),
			'PRODUCT_STOCK_STATUS'       => $stock_status,
			'PRODUCT_SKU'                => $var_product->get_sku(),
			'PRODUCT_CATEGORIES'         => ( is_array( $categories ) ) ? implode( ', ', $categories ) : '',
			'PRODUCT_TAGS'               => ( is_array( $tags ) ) ? implode( ', ', $tags ) : '',
			'PRODUCT_VARIATION'          => $var_product->get_name(),
			'PRODUCT_VARIATION_ID'       => $product_var_id,
		);

		return $parsed + $product_var_tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_wcprodstock_possible_tokens( $tokens = array(), $args = array() ) {

		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta = isset( $args['triggers_meta'] ) ? $args['triggers_meta'] : '';
		if ( isset( $trigger_meta['code'] ) && ( 'WCPRODOUTOFSTOCK' === (string) $trigger_meta['code'] || 'WCPRODSTOCKSTATUS' === (string) $trigger_meta['code'] ) ) {
			$tokens   = array();
			$tokens[] = array(
				'tokenId'         => 'product_sku',
				'tokenName'       => __( 'Product SKU', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

			if ( 'WCPRODOUTOFSTOCK' === (string) $trigger_meta['code'] ) {
				$tokens[] = array(
					'tokenId'         => 'product_stock',
					'tokenName'       => __( 'Product stock', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_meta['code'],
				);
			}

			$tokens[] = array(
				'tokenId'         => 'product_price',
				'tokenName'       => __( 'Product price', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);
			$tokens[] = array(
				'tokenId'         => 'WOOPRODUCT_CATEGORIES',
				'tokenName'       => __( 'Product categories', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);
			$tokens[] = array(
				'tokenId'         => 'WOOPRODUCT_TAGS',
				'tokenName'       => __( 'Product tags', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

		}

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_wcprodreview_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta = isset( $args['triggers_meta'] ) ? $args['triggers_meta'] : '';
		if ( isset( $trigger_meta['code'] ) && ( 'WCPRODREVIEW' === (string) $trigger_meta['code'] || 'WCPRODREVIEWAPPRVD' === (string) $trigger_meta['code'] || 'WCPRODREVIEWRATING' === (string) $trigger_meta['code'] ) ) {
			$tokens   = array();
			$tokens[] = array(
				'tokenId'         => 'product_review',
				'tokenName'       => __( 'Product review', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

			$tokens[] = array(
				'tokenId'         => 'product_sku',
				'tokenName'       => __( 'Product SKU', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

			$tokens[] = array(
				'tokenId'         => 'product_tags',
				'tokenName'       => __( 'Product tags', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

			$tokens[] = array(
				'tokenId'         => 'product_categories',
				'tokenName'       => __( 'Product categories', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta['code'],
			);

			if ( 'WCPRODREVIEWRATING' !== (string) $trigger_meta['code'] ) {
				$tokens[] = array(
					'tokenId'         => 'product_rating',
					'tokenName'       => __( 'Product rating', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_meta['code'],
				);
			}
		}

		$tokens = Automator()->utilities->remove_duplicate_token_ids( $tokens );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_wooprodcat_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$tokens = $this->wc_possible_tokens( $tokens, $args, 'product' );
		$tokens = Automator()->utilities->remove_duplicate_token_ids( $tokens );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function anon_wc_orderrefunded_possible_tokens( $tokens = array(), $args = array() ) {

		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$fields[] = array(
			'tokenId'         => 'ORDER_REFUND_ID',
			'tokenName'       => __( 'Order refund ID', 'uncanny-automator-pro' ),
			'tokenType'       => 'text',
			'tokenIdentifier' => 'ORDERREFUNDED',
		);

		if ( isset( $args['meta'] ) && ( 'ANONWCORDERPARTREFUNDED' === $args['meta'] || 'ANONWCORDERREFUNDED' === $args['meta'] || 'ANONWCORDERREFUNDEDASSCPRODUCT' === $args['meta'] ) ) {
			$fields[] = array(
				'tokenId'         => 'ORDER_REFUND_AMOUNT',
				'tokenName'       => __( 'Order refund amount', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'ORDERREFUNDED',
			);

			$fields[] = array(
				'tokenId'         => 'ORDER_REFUND_REASON',
				'tokenName'       => __( 'Order refund reason', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'ORDERREFUNDED',
			);

			if ( 'ANONWCORDERREFUNDEDASSCPRODUCT' === $args['meta'] ) {
				$fields[] = array(
					'tokenId'         => 'ORDERREFUNDED_PRODUCT_QTY',
					'tokenName'       => __( 'Product refund quantity', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'ORDERREFUNDED',
				);

				$fields[] = array(
					'tokenId'         => 'ORDERREFUNDED_PRODUCT_AMOUNT',
					'tokenName'       => __( 'Product refund amount', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'ORDERREFUNDED',
				);
			}
		}

		$tokens = array_merge( $tokens, $fields );
		$tokens = Automator()->utilities->remove_duplicate_token_ids( $tokens );

		return $this->wc_possible_tokens( $tokens, $args, 'product' );
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_wooorderitemadded_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta     = $args['meta'];
		$trigger_specific = array(
			array(
				'tokenId'         => 'item_total',
				'tokenName'       => __( 'Order item(s) total', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'item_subtotal',
				'tokenName'       => __( 'Order item(s) subtotal', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'item_tax',
				'tokenName'       => __( 'Order item(s) tax', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'item_qty',
				'tokenName'       => __( 'Order item(s) quantity', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'product_price',
				'tokenName'       => __( 'Product price', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'product_sale_price',
				'tokenName'       => __( 'Product sale price', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'product_sku',
				'tokenName'       => __( 'Product SKU', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WOOPRODUCT_CATEGORIES',
				'tokenName'       => __( 'Product categories', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WOOPRODUCT_TAGS',
				'tokenName'       => __( 'Product tags', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'user_total_spend',
				'tokenName'       => __( "User's total spend", 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'user_total_spend_raw',
				'tokenName'       => __( "User's total spend (unformatted)", 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		return array_merge( $trigger_specific, $tokens );
	}

	/**
	 * @param array  $tokens
	 * @param array  $args
	 * @param string $type
	 *
	 * @return array
	 */
	public function wc_possible_tokens( $tokens = array(), $args = array(), $type = 'order' ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$fields          = array();
		$trigger_meta    = $args['meta'];
		$possible_tokens = apply_filters( 'automator_woocommerce_possible_tokens', $this->possible_order_fields_pro );

		if ( 'WOOPAYMENTGATEWAY' === $trigger_meta ) {
			unset( $possible_tokens['payment_method'] );
		}

		if ( 'WOOVARIPRODUCT' === $trigger_meta ) {
			$pro_tokens = array(
				array(
					'tokenId'         => 'product_qty',
					'tokenName'       => __( 'Product quantity', 'uncanny-automator-pro' ),
					'tokenType'       => 'int',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
				array(
					'tokenId'         => 'product_price',
					'tokenName'       => __( 'Product price', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
				array(
					'tokenId'         => 'product_sku',
					'tokenName'       => __( 'Product SKU', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
				array(
					'tokenId'         => 'WCPURCHPRODINCAT',
					'tokenName'       => __( 'Product categories', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
				array(
					'tokenId'         => 'WCPURCHPRODINTAG',
					'tokenName'       => __( 'Product tags', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
				array(
					'tokenId'         => 'WOOVARIATION_ID',
					'tokenName'       => __( 'Variation ID', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'WOOVARIPRODUCT',
				),
			);

			$fields = array_merge( $fields, $pro_tokens );
		}

		foreach ( $possible_tokens as $token_id => $input_title ) {
			if ( 'billing_email' === (string) $token_id || 'shipping_email' === (string) $token_id ) {
				$input_type = 'email';
			} else {
				$input_type = 'text';
			}
			$fields[] = array(
				'tokenId'         => $token_id,
				'tokenName'       => $input_title,
				'tokenType'       => $input_type,
				'tokenIdentifier' => $trigger_meta,
			);
		}
		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return string|null
	 */
	public function wc_ordertotal_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$to_match = array(
			'WOOQNTY',
			'WCQNTYPURCHPROD',
			'WCPRODREVIEW',
			'WCPRODREVIEWRATING',
			'WCPRODREVIEWAPPRVD',
			'WOOPRODCAT',
			'WOOPRODTAG',
			'WCPURCHPRODUCTINCAT',
			'ANONWCPURCHPRODUCTINCAT',
			'WCPURCHPRODINCAT',
			'WCPURCHPRODUCTINTAG',
			'WCPURCHPRODINTAG',
			'WOOVARIPRODUCT',
			'WCPURCHVARIPROD',
			'WOORDERTOTAL',
			'WOOPRODUCT',
			'WCORDERSTATUS',
			'WCORDERCOMPLETE',
			'WCSHIPSTATIONPRODUCTSHIPPED',
			'WCSHIPSTATIONORDERTOTALSHIPPED',
			'TRIGGERCOND',
			'WOOPAYMENTGATEWAY',
			'NUMBERCOND',
			'WOOORDERQTYTOTAL',
			'ANONORDERITEMCREATED',
			'WOOCOUPONS',
			'ANONWCPURCHPRODUCTWITHCOUPON',
			'ORDERPAYMENTFAIL',
			'ORDERFAIL',
			'ANONWCORDERREFUNDED',
			'ORDERREFUNDED',
			'ANONWCORDERPARTREFUNDED',
			'PARTIALLYORDERREFUNDED',
			'ANONORDERSTATUSCHANGED',
			'WCPRODOUTOFSTOCK',
			'WCPRODSTOCKSTATUS',
			'ANONWCORDERREFUNDEDASSCPRODUCT',
			'WCSUBSCRIPTIONSSWITCHED',
			'WOOSUBSCRIPTIONS',
			'PROD_ORDER_STATUS_CHANGED_IN_A_TERM',
		);
		if ( $pieces ) {
			if ( array_intersect( $to_match, $pieces ) ) {
				$value = $this->replace_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
			}
		}
		$to_match = array(
			'WCPRODREVIEW',
			'WCPRODREVIEWAPPRVD',
			'WCPRODREVIEWRATING',
		);
		if ( in_array( 'WOOPRODUCT', $pieces, false ) ) {
			$to_match[] = 'WOOPRODUCT';
		}
		if ( $pieces ) {
			if ( array_intersect( $to_match, $pieces ) ) {
				$value = $this->replace_review_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
			}
		}
		if (
			in_array( 'ANONORDERITEMCREATED', $pieces, true ) ||
			in_array( 'ANONORDERSTATUSCHANGED', $pieces, true )
		) {
			$to_match = array(
				'ANONORDERITEMCREATED',
				'ANONORDERSTATUSCHANGED',
				'WOOPRODUCT',
				'WCORDERSTATUS',
			);
		}

		if ( $pieces ) {
			if ( array_intersect( $to_match, $pieces ) ) {
				$value = $this->replace_item_created_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
			}
		}
		if ( in_array( 'PROD_ORDER_STATUS_CHANGED_IN_A_TERM', $pieces, true ) ) {
			$value = $this->replace_item_created_in_taxonomy_values( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return float|int|mixed|string|null
	 */
	public function parse_subscription_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$to_match = array(
			'WCSUBSCRIPTIONSTATUSCHANGED',
			'WCSUBSCRIPTIONSUBSCRIBE',
			'WCSUBSCRIPTIONVARIATION',
			'WCSPECIFICSUBVARIATION',
			'WCVARIATIONSUBSCRIPTIONEXPIRED',
			'WCVARIATIONSUBSCRIPTIONSTATUSCHANGED',
			'WOOSUBSCRIPTIONSTATUS',
			'WOOSUBSCRIPTIONSTATUS_ID',
			'WOOSUBSCRIPTIONSTATUS_END_DATE',
			'WOOSUBSCRIPTIONSTATUS_TRIAL_END_DATE',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_ID',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_STATUS',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_END_DATE',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_TRIAL_END_DATE',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_NEXT_PAYMENT_DATE',
			'WOOSUBSCRIPTIONS_SUBSCRIPTION_RENEWAL_COUNT',
			'WOOVARIPRODUCT_SUBSCRIPTION_RENEWAL_COUNT',
			'WOOSUBSCRIPTIONS_THUMB_URL',
			'WOOSUBSCRIPTIONS_THUMB_ID',
			'WOOSUBSCRIPTIONS_ID',
			'WOOSUBSCRIPTIONS_URL',
			'WOOSUBSCRIPTIONS',
			'WCSUBSCRIPTIONTRIALEXPIRES',
			'WCVARIATIONSUBSCRIPTIONRENEWED',
			'WCVARIATIONSUBSCRIPTIONTRIALEXPIRES',
			'WCSUBSCRIPTIONSSWITCHED',
			'WC_SUBSCRIPTION_RENEWAL_COUNT',
			'RENEWAL_COUNT',
		);

		if ( $pieces ) {
			if ( array_intersect( $to_match, $pieces ) ) {
				$value = $this->replace_wcs_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
				$value = apply_filters( 'automator_woocommerce_subscription_token_parser', $value, $pieces, $trigger_data, $replace_args );
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string|null
	 */
	public function replace_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$trigger_meta         = $pieces[1];
		$parse                = $pieces[2];
		$multi_line_separator = apply_filters( 'automator_woo_multi_item_separator', ' | ', $pieces );
		$recipe_log_id        = isset( $replace_args['recipe_log_id'] ) ? (int) $replace_args['recipe_log_id'] : Automator()->maybe_create_recipe_log_entry( $recipe_id, $user_id )['recipe_log_id'];
		if ( ! $trigger_data || ! $recipe_log_id ) {
			return $value;
		}
		if ( ! is_array( $trigger_data ) ) {
			return $value;
		}

		foreach ( $trigger_data as $trigger ) {
			if ( ! is_array( $trigger ) || empty( $trigger ) ) {
				continue;
			}
			if ( ! key_exists( $trigger_meta, $trigger['meta'] ) && ( ! isset( $trigger['meta']['code'] ) && $trigger_meta !== $trigger['meta']['code'] ) ) {
				continue;
			}
			$trigger_id     = $trigger['ID'];
			$trigger_log_id = $replace_args['trigger_log_id'];
			$order_id       = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'order_id', $trigger_id, $trigger_log_id, $user_id );
			if ( empty( $order_id ) ) {
				continue;
			}
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			switch ( $parse ) {
				case 'order_id':
					$value = $order_id;
					break;
				case 'WCORDERSTATUS':
					$value = $order->get_status();
					break;
				case 'WOOPRODCAT':
				case 'WCPURCHPRODINCAT':
				case 'WCPURCHPRODUCTINCAT':
				case 'ANONWCPURCHPRODUCTINCAT':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_categories_from_items( $order, $value_to_match );
					break;
				case 'WOOPRODTAG':
				case 'WCPURCHPRODINTAG':
				case 'WCPURCHPRODUCTINTAG':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_tags_from_items( $order, $value_to_match );
					break;
				case 'WOOPRODUCT':
				case 'WOOVARIABLEPRODUCTS':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$product_ids    = array_map( 'intval', explode( ',', $this->get_woo_product_ids_from_items( $order, $value_to_match ) ) );
					$value          = '';
					if ( ! empty( $product_ids ) ) {
						$product_names = array();
						foreach ( $product_ids as $woo_product_id ) {
							$parent_product = get_post( $woo_product_id );

							if ( $parent_product->post_parent ) {
								$parent_product = get_post( $parent_product->post_parent );
							}

							$product_names[] = $parent_product->post_title;
						}
						$value = join( ',', $product_names );
					}
					break;
				case 'WOOVARIPRODUCT':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					if ( intval( '-1' ) === $value_to_match ) {
						$maybe_value_to_match = Automator()->db->token->get( $parse, $replace_args );
						if ( ! empty( $maybe_value_to_match ) && is_numeric( $maybe_value_to_match ) ) {
							$value_to_match = $maybe_value_to_match;
						}
					}
					$value = $this->get_woo_product_names_from_items_a( $order, $value_to_match, 'variation' );
					break;
				case 'WOOVARIATION_ID':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					if ( intval( '-1' ) === $value_to_match ) {
						$maybe_value_to_match = Automator()->db->token->get( $parse, $replace_args );
						if ( ! empty( $maybe_value_to_match ) && is_numeric( $maybe_value_to_match ) ) {
							$value_to_match = $maybe_value_to_match;
						}
					}
					$value = $this->get_woo_variation_product_ids_from_items( $order, $value_to_match, 'variation' );

					break;
				case 'WOOQNTY':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $value_to_match;
					break;
				case 'WOOPRODTAG_ID':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_terms_ids_from_items( $order, $value_to_match, 'product_tag' );
					break;
				case 'WOOPRODCAT_ID':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_terms_ids_from_items( $order, $value_to_match, 'product_cat' );
					break;
				case 'WOOPRODTAG_URL':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_terms_links_from_items( $order, $value_to_match, 'product_tag' );
					break;
				case 'WOOPRODCAT_URL':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_terms_links_from_items( $order, $value_to_match, 'product_cat' );
					break;
				case 'WOOPRODUCT_ID':
				case 'WOOVARIABLEPRODUCTS_ID':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_ids_from_items( $order, $value_to_match );
					break;
				case 'WOOPRODUCT_URL':
				case 'WOOVARIABLEPRODUCTS_URL':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_urls_from_items( $order, $value_to_match );
					break;
				case 'WOOPRODUCT_THUMB_ID':
				case 'WOOVARIABLEPRODUCTS_THUMB_ID':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_image_ids_from_items( $order, $value_to_match );
					break;
				case 'WOOPRODUCT_THUMB_URL':
				case 'WOOVARIABLEPRODUCTS_THUMB_URL':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_image_urls_from_items( $order, $value_to_match );
					break;
				case 'product_price':
				case 'ORDERREFUNDED_PRODUCT_PRICE':
				case 'ORDERREFUNDED_PRODUCT_PRICE_UNFORMATTED':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_details( $order, $value_to_match, 'price' );
					break;
				case 'ORDERREFUNDED_PRODUCT_SALE_PRICE':
				case 'ORDERREFUNDED_PRODUCT_SALE_PRICE_UNFORMATTED':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_details( $order, $value_to_match, 'sale' );
					break;
				case 'product_sku':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_details( $order, $value_to_match, 'sku' );
					break;
				case 'product_qty':
				case 'ORDERREFUNDED_ORDER_QTY':
					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $this->get_woo_product_details( $order, $value_to_match, 'quantity' );
					break;
				case 'WOORDERTOTAL':
					$value = wc_price( $order->get_total() );
					break;
				case 'TRIGGERCOND':
					$trigger_condition_labels = Automator()->helpers->recipe->woocommerce->pro->get_trigger_condition_labels();

					$value_to_match = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '-1';
					$value          = $trigger_condition_labels[ $value_to_match ];
					break;
				case 'WOOORDERQTYTOTAL':
					$value = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '';
					break;
				case 'ORDER_REFUND_ID':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'ORDER_REFUND_ID', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'ORDER_REFUND_AMOUNT':
					$value = Automator()->db->token->get( 'ORDER_REFUND_AMOUNT', $replace_args );
					break;
				case 'ORDER_REFUND_REASON':
					$value = Automator()->db->token->get( 'ORDER_REFUND_REASON', $replace_args );
					break;
				case 'NUMBERCOND':
					$val = isset( $trigger['meta'][ $parse ] ) ? $trigger['meta'][ $parse ] : '';
					switch ( $val ) {
						case '<':
							$value = esc_attr__( 'less than', 'uncanny-automator' );
							break;
						case '>':
							$value = esc_attr__( 'greater than', 'uncanny-automator' );
							break;
						case '=':
							$value = esc_attr__( 'equal to', 'uncanny-automator' );
							break;
						case '!=':
							$value = esc_attr__( 'not equal to', 'uncanny-automator' );
							break;
						case '>=':
							$value = esc_attr__( 'greater or equal to', 'uncanny-automator' );
							break;
						case '<=':
							$value = esc_attr__( 'less or equal to', 'uncanny-automator' );
							break;
						default:
							$value = '';
							break;
					}
					break;
				case 'ORDERREFUNDED_PRODUCT_QTY':
					$value = Automator()->db->token->get( 'ORDERREFUNDED_PRODUCT_QTY', $replace_args );
					break;
				case 'ORDERREFUNDED_PRODUCT_AMOUNT':
					$value = Automator()->db->token->get( 'ORDERREFUNDED_PRODUCT_AMOUNT', $replace_args );
					break;
				case 'NUMTIMES':
					$value = absint( $replace_args['run_number'] );
					break;
				case 'billing_first_name':
					$value = $order->get_billing_first_name();
					break;
				case 'billing_last_name':
					$value = $order->get_billing_last_name();
					break;
				case 'billing_company':
					$value = $order->get_billing_company();
					break;
				case 'billing_country':
					$value = $order->get_billing_country();
					break;
				case 'billing_country_name':
					$value = $this->pro_get_country_name_from_code( $order->get_billing_country() );
					break;
				case 'billing_address_1':
					$value = $order->get_billing_address_1();
					break;
				case 'billing_address_2':
					$value = $order->get_billing_address_2();
					break;
				case 'billing_city':
					$value = $order->get_billing_city();
					break;
				case 'billing_state':
					$value = $order->get_billing_state();
					break;
				case 'billing_state_name':
					$value = $this->pro_get_state_name_from_codes( $order->get_billing_state(), $order->get_billing_country() );
					break;
				case 'billing_postcode':
					$value = $order->get_billing_postcode();
					break;
				case 'billing_phone':
					$value = $order->get_billing_phone();
					break;
				case 'billing_email':
					$value = $order->get_billing_email();
					break;
				case 'order_date':
					$value = $order->get_date_created()->format( get_option( 'date_format', 'F j, Y' ) );
					break;
				case 'shipping_first_name':
					$value = $order->get_shipping_first_name();
					break;
				case 'shipping_last_name':
					$value = $order->get_shipping_last_name();
					break;
				case 'shipping_company':
					$value = $order->get_shipping_company();
					break;
				case 'shipping_country':
					$value = $order->get_shipping_country();
					break;
				case 'shipping_country_name':
					$value = $this->pro_get_country_name_from_code( $order->get_shipping_country() );
					break;
				case 'shipping_address_1':
					$value = $order->get_shipping_address_1();
					break;
				case 'shipping_address_2':
					$value = $order->get_shipping_address_2();
					break;
				case 'shipping_city':
					$value = $order->get_shipping_city();
					break;
				case 'shipping_state':
					$value = $order->get_shipping_state();
					break;
				case 'shipping_state_name':
					$value = $this->pro_get_state_name_from_codes( $order->get_shipping_state(), $order->get_shipping_country() );
					break;
				case 'shipping_postcode':
					$value = $order->get_shipping_postcode();
					break;
				case 'shipping_phone':
					$value = get_post_meta( $order_id, 'shipping_phone', true );
					break;
				case 'order_comments':
					$comments = $order->get_customer_note();
					if ( is_array( $comments ) ) {
						$comments = join( $multi_line_separator, $comments );
					}
					$value = ! empty( $comments ) ? $comments : '';
					break;
				case 'order_status':
					$value = $order->get_status();
					break;
				case 'order_total':
					$value = strip_tags( wc_price( $order->get_total() ) );
					break;
				case 'order_total_raw':
					$value = $order->get_total();
					break;
				case 'order_subtotal':
					$value = strip_tags( wc_price( $order->get_subtotal() ) );
					break;
				case 'order_subtotal_raw':
					$value = $order->get_subtotal();
					break;
				case 'order_tax':
					$value = strip_tags( wc_price( $order->get_total_tax() ) );
					break;
				case 'order_fees':
					$value = wc_price( $order->get_total_fees() );
					break;
				case 'order_fees_raw':
					$value = $order->get_total_fees();
					break;
				case 'order_shipping':
					$value = wc_price( $order->get_shipping_total() );
					break;
				case 'order_shipping_raw':
					$value = $order->get_shipping_total();
					break;
				case 'order_tax_raw':
					$value = $order->get_total_tax();
					break;
				case 'order_discounts':
					$value = strip_tags( wc_price( $order->get_discount_total() * - 1 ) );
					break;
				case 'order_discounts_raw':
					$value = ( $order->get_discount_total() * - 1 );
					break;
				case 'user_total_spend_raw':
					$customer_id = $order->get_user_id();
					$value       = wc_get_customer_total_spent( $customer_id );
					break;
				case 'user_total_spend':
					$customer_id = $order->get_user_id();
					$value       = wc_price( wc_get_customer_total_spent( $customer_id ) );
					break;
				case 'order_coupons':
					$coupons = $order->get_coupon_codes();
					$value   = join( ', ', $coupons );
					break;
				case 'order_products':
					$items         = $order->get_items();
					$product_names = array();
					if ( $items ) {
						/** @var WC_Order_Item_Product $item */
						foreach ( $items as $item ) {
							$product_names[] = $item->get_name();
						}
					}
					$value = join( $multi_line_separator, $product_names );

					break;
				case 'order_products_qty':
					$items = $order->get_items();
					$prods = array();
					if ( $items ) {
						/** @var WC_Order_Item_Product $item */
						foreach ( $items as $item ) {
							$prods[] = $item->get_name() . ' x ' . $item->get_quantity();
						}
					}
					$value = join( $multi_line_separator, $prods );

					break;
				case 'order_qty':
					$qty = 0;
					/** @var WC_Order_Item_Product $item */
					$items = $order->get_items();
					foreach ( $items as $item ) {
						$qty = $qty + $item->get_quantity();
					}
					$value = $qty;
					break;
				case 'order_products_links':
					$items = $order->get_items();
					$prods = array();
					if ( $items ) {
						/** @var WC_Order_Item_Product $item */
						foreach ( $items as $item ) {
							$product = $item->get_product();
							$prods[] = '<a href="' . $product->get_permalink() . '">' . $item->get_name() . '</a>';
						}
					}
					$value = join( $multi_line_separator, $prods );
					break;

				case 'WOOPAYMENTGATEWAY':
				case 'payment_method':
					$value = $order->get_payment_method_title();
					break;

				case 'stripe_fee':
					$value = 0;
					if ( function_exists( 'stripe_wc' ) ) {
						$value = \WC_Stripe_Utils::display_fee( $order );
					}
					if ( ( function_exists( 'woocommerce_gateway_stripe' ) || class_exists( '\WC_Stripe_Helper' ) ) && 0 === $value ) {
						$value = \WC_Stripe_Helper::get_stripe_fee( $order );
					}

					break;

				case 'stripe_fee_raw':
					$value = 0;
					if ( function_exists( 'stripe_wc' ) ) {
						$value = \WC_Stripe_Utils::display_fee( $order );
					}
					if ( ( function_exists( 'woocommerce_gateway_stripe' ) || class_exists( '\WC_Stripe_Helper' ) ) && 0 === $value ) {
						$value = \WC_Stripe_Helper::get_stripe_fee( $order );
					}

					if ( ! empty( $value ) ) {
						$value = $this->clean_wc_price( $value );
					}
					break;

				case 'stripe_payout':
					$value = 0;
					if ( function_exists( 'stripe_wc' ) ) {
						$value = \WC_Stripe_Utils::display_net( $order );
					}
					if ( class_exists( '\WC_Stripe_Helper' ) && 0 === $value ) {
						$value = \WC_Stripe_Helper::get_stripe_net( $order );
					}
					break;

				case 'stripe_payout_raw':
					$value = 0;
					if ( function_exists( 'stripe_wc' ) ) {
						$value = \WC_Stripe_Utils::display_net( $order );
					}
					if ( class_exists( '\WC_Stripe_Helper' ) && 0 === $value ) {
						$value = \WC_Stripe_Helper::get_stripe_net( $order );
					}
					if ( ! empty( $value ) ) {
						$value = $this->clean_wc_price( $value );
					}
					break;

				case 'shipping_method':
					$value = $order->get_shipping_method();
					break;

				case 'CARRIER':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'WOOORDER_CARRIER', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'TRACKING_NUMBER':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'WOOORDER_TRACKING_NUMBER', $trigger_id, $trigger_log_id, $user_id );
					break;
				case 'SHIP_DATE':
					$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'WOOORDER_SHIP_DATE', $trigger_id, $trigger_log_id, $user_id );
					$value = $value ? date( 'Y-m-d H:i:s', $value ) : '';
					break;
				case 'order_summary':
					$value = $this->build_summary_style_html( $order );
					break;
				default:
					$this->handle_default_switch( $value, $parse, $pieces, $order );
					break;
			}
			$value = apply_filters( 'automator_woocommerce_token_parser', $value, $parse, $pieces, $order );
		}

		return $value;
	}

	/**
	 * @param $price
	 *
	 * @return float|mixed
	 */
	public function clean_wc_price( $price ) {
		// Regular expression to match the numeric/float value after the currency symbol
		$pattern = '/<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">.*?<\/span>([0-9,]+(?:\.[0-9]+)?)<\/bdi><\/span>/';

		// Extract the value
		if ( preg_match( $pattern, $price, $matches ) ) {
			// Convert the captured value to a float
			return floatval( str_replace( ',', '', $matches[1] ) );
		}

		return $price;
	}

	/**
	 * @param $value
	 * @param $parse
	 * @param $pieces
	 * @param $order
	 *
	 * @return mixed|void
	 */
	public function handle_default_switch( $value, $parse, $pieces, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $value;
		}
		$multi_line_separator = apply_filters( 'automator_woo_multi_item_separator', ' | ', $pieces );
		if ( preg_match( '/custom_order_meta/', $parse ) ) {
			$custom_meta = explode( '|', $parse );
			if ( ! empty( $custom_meta ) && count( $custom_meta ) > 1 && 'custom_order_meta' === $custom_meta[0] ) {
				$meta_key = $custom_meta[1];
				if ( $order->meta_exists( $meta_key ) ) {
					$value = $order->get_meta( $meta_key );
					if ( is_array( $value ) ) {
						$value = join( $multi_line_separator, $value );
					}
				}
				$value = apply_filters( 'automator_woocommerce_custom_order_meta_token_parser', $value, $meta_key, $pieces, $order );
			}
		}
		if ( preg_match( '/custom_item_meta/', $parse ) ) {
			$custom_meta = explode( '|', $parse );
			if ( ! empty( $custom_meta ) && count( $custom_meta ) > 1 && 'custom_item_meta' === $custom_meta[0] ) {
				$meta_key = $custom_meta[1];
				$items    = $order->get_items();
				if ( $items ) {
					/** @var WC_Order_Item_Product $item */
					foreach ( $items as $item ) {
						if ( $item->meta_exists( $meta_key ) ) {
							$value = $item->get_meta( $meta_key );
						}
						$value = apply_filters( 'automator_woocommerce_custom_item_meta_token_parser', $value, $meta_key, $pieces, $order, $item );
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function replace_wcs_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$trigger_meta  = $pieces[1];
		$parse         = $pieces[2];
		$recipe_log_id = isset( $replace_args['recipe_log_id'] ) ? (int) $replace_args['recipe_log_id'] : Automator()->maybe_create_recipe_log_entry( $recipe_id, $user_id )['recipe_log_id'];
		if ( ! $trigger_data || ! $recipe_log_id ) {
			return $value;
		}
		foreach ( $trigger_data as $trigger ) {
			if ( ! is_array( $trigger ) || empty( $trigger ) ) {
				continue;
			}
			if ( ! key_exists( $trigger_meta, $trigger['meta'] ) && ( ! isset( $trigger['meta']['code'] ) && $trigger_meta !== $trigger['meta']['code'] ) ) {
				continue;
			}
			$trigger_id      = $trigger['ID'];
			$trigger_log_id  = $replace_args['trigger_log_id'];
			$subscription_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'subscription_id', $trigger_id, $trigger_log_id, $user_id );
			if ( empty( $subscription_id ) ) {
				continue;
			}
			$subscription = wcs_get_subscription( $subscription_id );
			if ( ! $subscription instanceof WC_Order ) {
				continue;
			}
			switch ( $parse ) {
				case 'WCSUBSCRIPTIONSTATUSCHANGED':
					$value = $subscription_id;
					break;
				case 'WOOSUBSCRIPTIONS_PRODUCT':
					$items         = $subscription->get_items();
					$product_names = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$parent_product  = get_post_parent( $product->get_id() );
							$product_names[] = $parent_product->post_title;
						}
					}
					$value = implode( ', ', $product_names );
					break;
				case 'WOOSUBSCRIPTIONS':
					$items         = $subscription->get_items();
					$product_names = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							if ( get_post_type( $product->get_id() ) === 'product_variation' ) {
								$variation_product = get_post( $product->get_id() );
								$product_names[]   = ! empty( $variation_product->post_excerpt ) ? $variation_product->post_excerpt : $variation_product->post_title;
							} else {
								$product_names[] = $product->get_name();
							}
						}
					}
					$value = implode( ', ', $product_names );
					break;
				case 'WOOSUBSCRIPTIONS_ID':
				case 'WOOVARIPRODUCT_ID':
					$items         = $subscription->get_items();
					$product_names = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_names[] = $product->get_id();
						}
					}
					$value = implode( ', ', $product_names );
					break;
				case 'WOOSUBSCRIPTIONS_PRODUCT_ID':
				case 'WOOVARIPRODUCT_PRODUCT_ID':
					$items       = $subscription->get_items();
					$product_ids = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_ids [] = wp_get_post_parent_id( $product->get_id() );
						}
					}
					$value = implode( ', ', $product_ids );
					break;
				case 'WOOSUBSCRIPTIONS_URL':
				case 'WOOVARIPRODUCT_URL':
					$items         = $subscription->get_items();
					$product_names = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_names[] = get_permalink( $product->get_id() );
						}
					}
					$value = implode( ', ', $product_names );
					break;
				case 'WOOSUBSCRIPTIONS_PRODUCT_URL':
				case 'WOOVARIPRODUCT_PRODUCT_URL':
					$items        = $subscription->get_items();
					$product_urls = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_urls[] = get_permalink( wp_get_post_parent_id( $product->get_id() ) );
						}
					}
					$value = implode( ', ', $product_urls );
					break;
				case 'WOOSUBSCRIPTIONS_THUMB_ID':
				case 'WOOVARIPRODUCT_THUMB_ID':
					$items         = $subscription->get_items();
					$product_thumb = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_thumb[] = get_post_thumbnail_id( $product->get_id() );
						}
					}
					$value = implode( ', ', $product_thumb );
					if ( empty( $value ) || $value == 0 ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONS_PRODUCT_THUMB_ID':
				case 'WOOVARIPRODUCT_PRODUCT_THUMB_ID':
					$items         = $subscription->get_items();
					$product_thumb = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_thumb[] = get_post_thumbnail_id( wp_get_post_parent_id( $product->get_id() ) );
						}
					}
					$value = implode( ', ', $product_thumb );
					if ( empty( $value ) || $value == 0 ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONS_THUMB_URL':
				case 'WOOVARIPRODUCT_THUMB_URL':
					$items            = $subscription->get_items();
					$product_thumburl = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_thumburl[] = get_the_post_thumbnail_url( $product->get_id() );
						}
					}
					$value = implode( ', ', $product_thumburl );
					if ( empty( $value ) ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONS_PRODUCT_THUMB_URL':
				case 'WOOVARIPRODUCT_PRODUCT_THUMB_URL':
					$items            = $subscription->get_items();
					$product_thumburl = array();
					foreach ( $items as $item ) {
						/** @var WC_Order_Item_Product $product */
						$product = $item->get_product();
						if ( class_exists( '\WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
							$product_thumburl[] = get_the_post_thumbnail_url( wp_get_post_parent_id( $product->get_id() ) );
						}
					}
					$value = implode( ', ', $product_thumburl );
					if ( empty( $value ) ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONSTATUS':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_STATUS':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_STATUS':
					$value = $subscription->get_status();
					break;
				case 'WOOSUBSCRIPTIONSTATUS_ID':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_ID':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_ID':
					$value = $subscription->get_id();
					break;
				case 'WOOSUBSCRIPTIONSTATUS_END_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_END_DATE':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_END_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_END_DATE':
					$value = $subscription->get_date( 'end' );
					if ( empty( $value ) || $value == 0 ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONSTATUS_NEXT_PAYMENT_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_NEXT_PAYMENT_DATE':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_NEXT_PAYMENT_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_NEXT_PAYMENT_DATE':
					$value = $subscription->get_date( 'next_payment' );
					break;
				case 'WOOSUBSCRIPTIONSTATUS_TRIAL_END_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_TRIAL_END_DATE':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_TRIAL_END_DATE':
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_TRIAL_END_DATE':
					$value = $subscription->get_date( 'trial_end' );
					if ( empty( $value ) || $value == 0 ) {
						$value = 'N/A';
					}
					break;
				case 'WOOSUBSCRIPTIONS_SUBSCRIPTION_RENEWAL_COUNT':
				case 'WOOVARIPRODUCT_SUBSCRIPTION_RENEWAL_COUNT':
				case 'RENEWAL_COUNT':
					$value = $subscription->get_payment_count( 'completed', 'renewal' );
					break;
				default:
					$this->handle_default_switch( $value, $parse, $pieces, $subscription );
					break;

			}

			$value = apply_filters( 'automator_pro_woocommerce_subscriptions_token_parser', $value, $parse, $pieces, $subscription );
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string|null
	 */
	public function replace_review_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $trigger_data ) || empty( $replace_args ) ) {
			return $value;
		}

		$trigger_meta  = $pieces[1];
		$parse         = $pieces[2];
		$recipe_log_id = isset( $replace_args['recipe_log_id'] ) ? (int) $replace_args['recipe_log_id'] : Automator()->maybe_create_recipe_log_entry( $recipe_id, $user_id )['recipe_log_id'];
		if ( ! $trigger_data || ! $recipe_log_id ) {
			return $value;
		}

		foreach ( $trigger_data as $trigger ) {
			if ( empty( $trigger ) ) {
				continue;
			}
			if ( ! key_exists( $trigger_meta, $trigger['meta'] ) ) {
				if ( isset( $trigger['meta']['code'] ) && (string) strtolower( $trigger_meta ) !== (string) strtolower( $trigger['meta']['code'] ) ) {
					continue;
				}
			}
			$trigger_id     = $trigger['ID'];
			$trigger_log_id = $replace_args['trigger_log_id'];
			$comment_id     = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'comment_id', $trigger_id, $trigger_log_id, $user_id );
			$comment        = get_comment( $comment_id );

			if ( empty( $comment_id ) ) {
				continue;
			}

			$product = new \WC_Product( $comment->comment_post_ID );

			switch ( $parse ) {
				case 'product_id':
					$value = $comment->comment_post_ID;
					break;
				case 'WOOPRODUCT':
					$value = get_the_title( $comment->comment_post_ID );
					break;
				case 'WOOPRODUCT_ID':
					$value = $comment->comment_post_ID;
					break;
				case 'WOOPRODUCT_URL':
					$value = get_permalink( $comment->comment_post_ID );
					break;
				case 'product_review':
					$value = $comment->comment_content;
					break;
				case 'product_tags':
					// Get the terms.
					$terms = get_the_terms( $product->get_id(), 'product_tag' );
					// Separate by comma.
					if ( is_array( $terms ) && ! empty( $terms ) ) {
						$value = implode( ', ', array_column( $terms, 'name' ) );
					}
					break;
				case 'product_categories':
					// Get the terms.
					$terms = get_the_terms( $product->get_id(), 'product_cat' );
					// Separate by comma.
					if ( is_array( $terms ) && ! empty( $terms ) ) {
						$value = implode( ', ', array_column( $terms, 'name' ) );
					}
					break;
				case 'product_sku':
					$value = $product->get_sku();
					break;
				case 'product_rating':
					$value = get_comment_meta( $comment->comment_ID, 'rating', true );
					if ( empty( $value ) ) {
						$value = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'rating', $trigger_id, $trigger_log_id, $user_id );
					}
					break;
				case 'NUMTIMES':
					$value = absint( $replace_args['run_number'] );
					break;
				default:
					$this->handle_default_switch( $value, $parse, $pieces, array() );
					break;
			}
		}

		return $value;
	}

	/**
	 * @param $order_id
	 * @param $recipe_id
	 * @param $args
	 * @param $type
	 */
	public function uap_wc_trigger_save_meta_func( $order_id, $recipe_id, $args, $type ) {
		if ( ! empty( $order_id ) && is_array( $args ) && $recipe_id ) {
			foreach ( $args as $trigger_result ) {
				if ( true === $trigger_result['result'] ) {

					$recipe = Automator()->get_recipes_data( true, $recipe_id );
					if ( is_array( $recipe ) ) {
						$recipe = array_pop( $recipe );
					}
					$triggers = $recipe['triggers'];
					if ( $triggers ) {
						foreach ( $triggers as $trigger ) {
							$trigger_id = $trigger['ID'];
							if ( ! key_exists( 'WOOPRODCAT', $trigger['meta'] ) &&
								 ! key_exists( 'WOOPRODTAG', $trigger['meta'] ) &&
								 ! key_exists( 'WOOVARIPRODUCT', $trigger['meta'] ) &&
								 ! key_exists( 'WOOVARIABLEPRODUCTS', $trigger['meta'] ) &&
								 ! key_exists( 'WOOSUBSCRIPTIONS', $trigger['meta'] ) &&
								 ! key_exists( 'WOOPAYMENTGATEWAY', $trigger['meta'] ) ) {
								continue;
							} else {
								$user_id        = (int) $trigger_result['args']['user_id'];
								$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
								$run_number     = (int) $trigger_result['args']['run_number'];

								$args = array(
									'user_id'        => $user_id,
									'trigger_id'     => $trigger_id,
									'meta_key'       => 'order_id',
									'meta_value'     => $order_id,
									'run_number'     => $run_number,
									//get run number
									'trigger_log_id' => $trigger_log_id,
								);

								Automator()->insert_trigger_meta( $args );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $item_id
	 * @param $item
	 * @param $order_id
	 * @param $recipe_id
	 * @param $args
	 */
	public function uap_wc_order_item_meta_func( $item_id, $order_id, $recipe_id, $args ) {
		if ( empty( $order_id ) ) {
			return;
		}
		if ( ! is_array( $args ) ) {
			return;
		}
		if ( ! $recipe_id ) {
			return;
		}
		foreach ( $args as $trigger_result ) {
			if ( true !== $trigger_result['result'] ) {
				continue;
			}

			$recipe = Automator()->get_recipes_data( true, $recipe_id );
			if ( is_array( $recipe ) ) {
				$recipe = array_pop( $recipe );
			}
			$triggers = $recipe['triggers'];
			if ( $triggers ) {
				foreach ( $triggers as $trigger ) {
					$trigger_id     = $trigger['ID'];
					$user_id        = (int) $trigger_result['args']['user_id'];
					$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
					$run_number     = (int) $trigger_result['args']['run_number'];
					$args           = array(
						'user_id'        => $user_id,
						'trigger_id'     => $trigger_id,
						'run_number'     => $run_number, //get run number
						'trigger_log_id' => $trigger_log_id,
					);
					$meta_value     = array(
						// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
						'order_item' => $item_id,
						'order_id'   => $order_id,
					);
					Automator()->db->trigger->add_token_meta( 'order_item_details', maybe_serialize( $meta_value ), $args );
				}
			}
		}
	}

	/**
	 * Save trigger run meta for PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param $args
	 *
	 * @return void
	 */
	public function uap_wc_save_order_item_meta_by_term_func( $args ) {
		if ( ! isset( $args['additional_details'] ) || empty( $args['additional_details'] ) ) {
			return;
		}

		$additional_details = $args['additional_details'];
		$order_id           = absint( $additional_details['order_id'] );
		$item_id            = isset( $additional_details['item_id'] ) ? absint( $additional_details['item_id'] ) : 0;
		$trigger_id         = absint( $additional_details['recipe_details']['trigger_id'] );
		$log_args           = $args['return_args'];

		foreach ( $log_args as $trigger_result ) {
			if ( true !== $trigger_result['result'] ) {
				continue;
			}

			$user_id        = (int) $trigger_result['args']['user_id'];
			$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
			$run_number     = (int) $trigger_result['args']['run_number'];

			$save_args = array(
				'user_id'        => $user_id,
				'trigger_id'     => $trigger_id,
				'run_number'     => $run_number, //get run number
				'trigger_log_id' => $trigger_log_id,
			);

			$meta_value = array(
				'order_item'         => $item_id,
				'order_id'           => $order_id,
				'additional_details' => $additional_details,
			);

			// For PROD_ORDER_STATUS_CHANGED_IN_A_TERM parsing
			Automator()->db->trigger->add_token_meta( 'order_item_details_in_cat', maybe_serialize( $meta_value ), $save_args );

			// For generic parsing
			Automator()->db->trigger->add_token_meta( 'order_item_details', maybe_serialize( $meta_value ), $save_args );

			// For generic parsing
			Automator()->db->trigger->add_token_meta( 'order_id', $order_id, $save_args );
		}
	}

	/**
	 * @param $product_id
	 * @param $recipe_id
	 * @param $args
	 * @param $type
	 */
	public function uap_wc_trigger_save_product_meta_func( $product_id, $recipe_id, $args, $type ) {
		if ( ! empty( $product_id ) && is_array( $args ) && $recipe_id ) {
			foreach ( $args as $trigger_result ) {
				if ( true === $trigger_result['result'] ) {

					$recipe = Automator()->get_recipes_data( true, $recipe_id );
					if ( is_array( $recipe ) ) {
						$recipe = array_pop( $recipe );
					}
					$triggers = $recipe['triggers'];
					if ( $triggers ) {
						foreach ( $triggers as $trigger ) {
							$trigger_id = $trigger['ID'];
							if ( ! key_exists( 'WOOPRODCAT', $trigger['meta'] ) &&
								 ! key_exists( 'WOOPRODTAG', $trigger['meta'] ) &&
								 ! key_exists( 'WOOVARIPRODUCT', $trigger['meta'] ) &&
								 ! key_exists( 'WOOSUBSCRIPTIONS', $trigger['meta'] ) ) {
								if ( in_array( 'WCPRODREVIEW', $trigger['meta'], false ) || in_array( 'WCPRODREVIEWRATING', $trigger['meta'], false ) ) {
									$user_id        = (int) $trigger_result['args']['user_id'];
									$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
									$run_number     = (int) $trigger_result['args']['run_number'];

									$args = array(
										'user_id'        => $user_id,
										'trigger_id'     => $trigger_id,
										'meta_key'       => 'comment_id',
										'meta_value'     => $product_id,
										'run_number'     => $run_number,
										//get run number
										'trigger_log_id' => $trigger_log_id,
									);

									Automator()->insert_trigger_meta( $args );
									if ( automator_filter_has_var( 'rating', INPUT_POST ) ) {
										$rating = apply_filters( 'automator_woocommerce_product_rating', absint( $_POST['rating'] ), $_POST, $trigger_result );
										$args   = array(
											'user_id'    => $user_id,
											'trigger_id' => $trigger_id,
											'meta_key'   => 'rating',
											'meta_value' => $rating,
											'run_number' => $run_number,
											//get run number
											'trigger_log_id' => $trigger_log_id,
										);

										Automator()->insert_trigger_meta( $args );
									}
								}
								if ( in_array( 'WOOPRODUCTSTOCK', $trigger['meta'], false ) ) {
									$user_id        = (int) $trigger_result['args']['user_id'];
									$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
									$run_number     = (int) $trigger_result['args']['run_number'];

									$args = array(
										'user_id'        => $user_id,
										'trigger_id'     => $trigger_id,
										'run_number'     => $run_number,
										'trigger_log_id' => $trigger_log_id,
									);
									// post_id Token
									Automator()->db->token->save( 'product_stock', $product_id, $args );
								}
							} else {
								$user_id        = (int) $trigger_result['args']['user_id'];
								$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
								$run_number     = (int) $trigger_result['args']['run_number'];

								$args = array(
									'user_id'        => $user_id,
									'trigger_id'     => $trigger_id,
									'meta_key'       => 'product_id',
									'meta_value'     => $product_id,
									'run_number'     => $run_number,
									//get run number
									'trigger_log_id' => $trigger_log_id,
								);

								Automator()->insert_trigger_meta( $args );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 *
	 * @return string
	 */
	public function get_woo_product_categories_from_items( WC_Order $order, $value_to_match ) {
		if ( intval( '-1' ) === intval( $value_to_match ) ) {
			$return = array();
			if ( $order->get_items() ) {
				/** @var \WC_Order_Item_Product $item */
				foreach ( $order->get_items() as $item ) {
					$terms = wp_get_post_terms( $item->get_product_id(), 'product_cat' );
					if ( $terms ) {
						foreach ( $terms as $term ) {
							$return[] = $term->name;
						}
					}
				}
			}

			//array_unique( $return );

			return join( ', ', $return );
		} else {
			$term = get_term_by( 'ID', $value_to_match, 'product_cat' );
			if ( ! $term ) {
				return '';
			}

			return $term->name;
		}

	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 *
	 * @return string
	 */
	public function get_woo_product_tags_from_items( WC_Order $order, $value_to_match ) {
		if ( intval( '-1' ) === intval( $value_to_match ) ) {
			$return = array();
			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$terms = wp_get_post_terms( $item->get_product_id(), 'product_tag' );
					if ( $terms ) {
						foreach ( $terms as $term ) {
							$return[] = $term->name;
						}
					}
				}
			}

			//array_unique( $return );

			return join( ', ', $return );
		} else {
			$term = get_term_by( 'ID', $value_to_match, 'product_tag' );
			if ( ! $term ) {
				return '';
			}

			return $term->name;
		}
	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 *
	 * @param string   $term_type
	 *
	 * @return string
	 */
	public function get_woo_terms_ids_from_items( WC_Order $order, $value_to_match, $term_type = 'product_cat' ) {
		if ( intval( '-1' ) === intval( $value_to_match ) ) {
			$return = array();
			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$terms = wp_get_post_terms( $item->get_product_id(), $term_type );
					if ( $terms ) {
						foreach ( $terms as $term ) {
							$return[] = $term->term_id;
						}
					}
				}
			}

			//array_unique( $return );

			return join( ', ', $return );
		} else {
			$term = get_term_by( 'ID', $value_to_match, $term_type );
			if ( ! $term ) {
				return '';
			}

			return $term->term_id;
		}
	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 *
	 * @param string   $term_type
	 *
	 * @return string
	 */
	public function get_woo_terms_links_from_items( WC_Order $order, $value_to_match, $term_type = 'product_cat' ) {
		if ( intval( '-1' ) === intval( $value_to_match ) ) {
			$return = array();
			if ( $order->get_items() ) {
				foreach ( $order->get_items() as $item ) {
					$terms = wp_get_post_terms( $item->get_product_id(), $term_type );
					if ( $terms ) {
						foreach ( $terms as $term ) {
							$return[] = get_term_link( $term, $term_type );
						}
					}
				}
			}

			//array_unique( $return );

			return join( ', ', $return );
		} else {
			$term = get_term_by( 'ID', $value_to_match, $term_type );
			if ( ! $term ) {
				return '';
			}

			return get_term_link( $term, $term_type );
		}
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_order_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		//$args['meta'] = 'WCSHIPSTATIONSHIPPED';
		$fields   = array();
		$fields[] = array(
			'tokenId'         => 'TRACKING_NUMBER',
			'tokenName'       => esc_attr__( 'Shipping tracking number', 'uncanny-automator' ),
			'tokenType'       => 'text',
			'tokenIdentifier' => $args['meta'],
		);
		$fields[] = array(
			'tokenId'         => 'CARRIER',
			'tokenName'       => esc_attr__( 'Shipping carrier', 'uncanny-automator' ),
			'tokenType'       => 'text',
			'tokenIdentifier' => $args['meta'],
		);
		$fields[] = array(
			'tokenId'         => 'SHIP_DATE',
			'tokenName'       => esc_attr__( 'Ship date', 'uncanny-automator' ),
			'tokenType'       => 'text',
			'tokenIdentifier' => $args['meta'],
		);
		$tokens   = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wc_addedtocart_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta = $args['meta'];
		$fields       = array(
			array(
				'tokenId'         => 'PRODUCT_PRICE',
				'tokenName'       => __( 'Price', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'PRODUCT_QUANTITY',
				'tokenName'       => __( 'Product quantity', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'PRODUCT_VARIATION',
				'tokenName'       => __( 'Variation', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function wc_addedtocart_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$to_match = array(
			'PRODUCT_PRICE',
			'PRODUCT_QUANTITY',
			'PRODUCT_VARIATION',
		);
		if ( $pieces && isset( $pieces[2] ) ) {
			$meta_field = $pieces[2];
			if ( ! empty( $meta_field ) && in_array( $meta_field, $to_match, false ) ) {
				if ( $trigger_data ) {
					global $wpdb;
					foreach ( $trigger_data as $trigger ) {
						if ( empty( $trigger ) ) {
							continue;
						}
						$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE meta_key = %s AND automator_trigger_id = %d AND automator_trigger_log_id = %d ORDER BY ID DESC LIMIT 0,1", $meta_field, $trigger['ID'], $replace_args['trigger_log_id'] ) );
						if ( ! empty( $meta_value ) ) {
							$value = maybe_unserialize( $meta_value );
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function wc_wcprodstock_possible_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$to_match = array(
			'WOOPRODUCTSTOCK',
			'product_sku',
		);
		if ( $pieces && isset( $pieces[2] ) ) {
			$meta_field = $pieces[2];
			if ( ! empty( $meta_field ) && in_array( $meta_field, $to_match, false ) ) {
				if ( $trigger_data ) {
					global $wpdb;
					foreach ( $trigger_data as $trigger ) {
						if ( empty( $trigger ) ) {
							continue;
						}
						$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE meta_key = %s AND automator_trigger_id = %d AND automator_trigger_log_id = %d ORDER BY ID DESC LIMIT 0,1", $meta_field, $trigger['ID'], $replace_args['trigger_log_id'] ) );
						if ( ! empty( $meta_value ) ) {
							$value = maybe_unserialize( $meta_value );
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return false|int|mixed|string|\WP_Error
	 */
	public function replace_item_created_values_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$trigger_meta  = $pieces[1];
		$parse         = $pieces[2];
		$recipe_log_id = $replace_args['recipe_log_id'];
		if ( ! $trigger_data || ! $recipe_log_id ) {
			return $value;
		}
		foreach ( $trigger_data as $trigger ) {
			if ( empty( $trigger ) ) {
				continue;
			}
			if ( ! key_exists( $trigger_meta, $trigger['meta'] ) && ( ! isset( $trigger['meta']['code'] ) && $trigger_meta !== $trigger['meta']['code'] ) ) {
				continue;
			}
			$trigger_id         = $trigger['ID'];
			$trigger_log_id     = $replace_args['trigger_log_id'];
			$token_meta_args    = array(
				'trigger_id'     => $trigger_id,
				'trigger_log_id' => $trigger_log_id,
				'user_id'        => $user_id,
			);
			$order_item_details = maybe_unserialize( Automator()->db->trigger->get_token_meta( 'order_item_details', $token_meta_args ) );
			if ( empty( $order_item_details ) ) {
				continue;
			}
			$order_id = $order_item_details['order_id'];
			$order    = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$order_item_id = $order_item_details['order_item'];
			/** @var WC_Order_Item_Product $order_item */
			$order_item = Woocommerce_Pro_Helpers::get_order_item_by_id( $order_item_id, $order_id );

			switch ( $parse ) {
				case 'order_id':
					$value = $order_id;
					break;
				case 'WOOPRODUCT':
					$value = $order_item->get_product()->get_name();
					break;
				case 'WOOPRODUCT_ID':
					if ( 'ANONORDERITEMCREATED' === $trigger_meta || 'ANONORDERSTATUSCHANGED' === $trigger_meta ) {
						$value = $order_item->get_product()->get_id();
					}
					break;
				case 'WOOPRODUCT_URL':
					if ( 'ANONORDERITEMCREATED' === $trigger_meta || 'ANONORDERSTATUSCHANGED' === $trigger_meta ) {
						$value = get_permalink(
							$order_item->get_product()
									   ->get_id()
						);
					}
					break;
				case 'WOOPRODUCT_THUMB_ID':
					$value = get_post_thumbnail_id( $order_item->get_product()->get_id() );
					break;
				case 'WOOPRODUCT_THUMB_URL':
					$value = get_the_post_thumbnail_url( $order_item->get_product()->get_id() );
					break;
				case 'WOOPRODUCT_ORDER_QTY':
				case 'item_qty':
					$value = $order_item->get_quantity();
					break;
				case 'item_total':
					$value = $order_item->get_total();
					break;
				case 'item_subtotal':
					$value = $order_item->get_subtotal();
					break;
				case 'product_price':
					$value = $order_item->get_product()->get_price();
					break;
				case 'product_sale_price':
					$value = $order_item->get_product()->get_sale_price();
					break;
				case 'product_sku':
					$value = $order_item->get_product()->get_sku();
					break;
				case 'item_tax':
					$value = $order_item->get_subtotal_tax();
					break;
				case 'WOOPRODUCT_CATEGORIES':
					/** @var  \WC_Order_Item $order_item */
					$categories = $order_item->get_product()
											 ->get_category_ids();
					$value      = '-';
					if ( ! empty( $categories ) ) {
						$category_names = array();
						$value          = '';
						foreach ( $categories as $category ) {
							$category_names[] = get_term_by( 'id', $category, 'product_cat' )->name;
						}
						if ( ! empty( $category_names ) ) {
							$value = join( ',', $category_names );
						}
					}
					break;
				case 'WOOPRODUCT_TAGS':
					/** @var  \WC_Order_Item $order_item */
					$tags  = $order_item->get_product()->get_tag_ids();
					$value = '-';
					if ( ! empty( $tags ) ) {
						$tag_names = array();
						$value     = '';
						foreach ( $tags as $tag ) {
							$tag_names[] = get_term( $tag )->name;
						}
						if ( ! empty( $tag_names ) ) {
							$value = join( ',', $tag_names );
						}
					}
					break;
				default:
					$value = $this->handle_default_switch( $value, $parse, $pieces, $order );
					$value = apply_filters( 'automator_woocommerce_order_item_created_token_parser', $value, $parse, $pieces, $order_item, $order );
					break;
			}
			$value = apply_filters( 'automator_woocommerce_token_parser', $value, $parse, $pieces, $order );
		}

		return $value;
	}

	/**
	 * Specifically for PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|null
	 */
	public function replace_item_created_in_taxonomy_values( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( empty( $trigger_data ) ) {
			return $value;
		}

		$trigger_meta   = $pieces[1];
		$parse          = $pieces[2];
		$trigger_log_id = (int) $replace_args['trigger_log_id'];

		foreach ( $trigger_data as $trigger ) {
			if ( empty( $trigger ) ) {
				continue;
			}

			if ( ! key_exists( $trigger_meta, $trigger['meta'] ) && ( ! isset( $trigger['meta']['code'] ) && $trigger_meta !== $trigger['meta']['code'] ) ) {
				continue;
			}

			$trigger_id      = (int) $trigger['ID'];
			$token_meta_args = array(
				'trigger_id'     => $trigger_id,
				'trigger_log_id' => $trigger_log_id,
				'user_id'        => $user_id,
			);

			$token_details = maybe_unserialize( Automator()->db->trigger->get_token_meta( 'order_item_details_in_cat', $token_meta_args ) );

			if ( empty( $token_details ) ) {
				continue;
			}

			$order_id = absint( $token_details['order_id'] );
			$order    = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$additional_details = $token_details['additional_details'];
			$recipe_details     = $additional_details['recipe_details'];
			$term_id            = $recipe_details['term'];
			$item_id            = isset( $additional_details['item_id'] ) ? absint( $additional_details['item_id'] ) : 0;

			if ( isset( $additional_details['combine_rows'] ) && 'yes' === $additional_details['combine_rows'] ) {
				$item_id = 0;
			}

			$this->term_id         = $term_id;
			$this->item_id         = $item_id;
			$this->order_id        = $order_id;
			$product_details       = isset( $additional_details['product_details'] ) ? $additional_details['product_details'] : array();
			$this->product_details = $product_details;
			$this->item_details    = $this->get_item_details( $product_details, $term_id, $item_id );

			switch ( $parse ) {
				case 'order_id':
					$value = $order_id;
					break;
				case 'WOOPRODUCT':
					$value = $this->get_product_related_values( 'name' );
					break;
				case 'WOOPRODUCT_ID':
					$value = $this->get_product_related_values( 'id' );
					break;
				case 'WOOPRODUCT_URL':
					$value = $this->get_product_related_values( 'permalink' );
					break;
				case 'item_total':
					$value = $this->get_item_related_values( 'total' );
					break;
				case 'item_tax':
					$value = $this->get_item_related_values( 'tax' );
					break;
				case 'WOOPRODUCT_ORDER_QTY':
				case 'item_qty':
					$value = $this->get_item_related_values( 'qty' );
					break;
				case 'product_price':
					$value = $this->get_product_related_values( 'price' );
					break;
				case 'product_sale_price':
					$value = $this->get_product_related_values( 'sale_price' );
					break;
				case 'product_sku':
					$value = $this->get_product_related_values( 'sku' );
					break;
				case 'WOOPRODUCT_THUMB_ID':
					$value = $this->get_product_related_values( 'thumb_id' );
					break;
				case 'WOOPRODUCT_THUMB_URL':
					$value = $this->get_product_related_values( 'thumb_url' );
					break;
				case 'WOOPRODUCT_CATEGORIES':
					$value = $this->get_product_related_values( 'categories' );
					break;
				case 'WOOPRODUCT_TAGS':
					$value = $this->get_product_related_values( 'tags' );
					break;
				default:
					$value = apply_filters( 'automator_pro_woocommerce_item_created_in_term_token_parser', $value, $parse, $pieces, $order, $product_details, $this );
					break;
			}
			$value = apply_filters( 'automator_woocommerce_token_parser', $value, $parse, $pieces, $order );
		}

		return $value;
	}

	/**
	 * Parse data for PROD_ORDER_STATUS_CHANGED_IN_A_TERM tokens
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_product_related_values( $type = 'name' ) {
		$data            = $this->product_details;
		$product_details = $data['products'];
		$products        = $this->get_product_details_for_term_trigger( $product_details );

		switch ( $type ) {
			case 'permalink':
				$value = $this->extract_product_details( $products, 'id', 'permalink' );
				break;
			case 'thumb_id':
				$value = $this->extract_product_details( $products, 'id', 'thumb_id' );
				break;
			case 'thumb_url':
				$value = $this->extract_product_details( $products, 'id', 'thumb_url' );
				break;
			case 'categories':
				$value = $this->extract_product_details( $products, 'id', 'categories' );
				break;
			case 'tags':
				$value = $this->extract_product_details( $products, 'id', 'tags' );
				break;
			default:
				$value = $this->extract_product_details( $products, $type );
				break;
		}

		return $value;
	}

	/**
	 * Get items related data for PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param string $type
	 *
	 * @return float|int
	 */
	public function get_item_related_values( $type = 'name' ) {
		$item_id = $this->item_id;
		$data    = $this->item_details;
		if ( 0 !== $item_id ) {
			foreach ( $data as $product_id => $d ) {
				if ( $item_id !== $d['item_id'] ) {
					unset( $data[ $product_id ] );
				}
			}
		}
		$value = '';
		switch ( $type ) {
			case 'total':
				$subtotal = array_column( $data, 'subtotal' );
				$value    = array_sum( $subtotal );
				break;
			case 'tax':
				$tax   = array_column( $data, 'tax' );
				$value = array_sum( $tax );
				break;
			case 'qty':
				$qty   = array_column( $data, 'qty' );
				$value = array_sum( $qty );
				break;
		}

		return $value;
	}

	/**
	 * Extract details from the trigger run for PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param $products
	 * @param $key
	 * @param $type
	 *
	 * @return string
	 */
	public function extract_product_details( $products, $key, $type = '' ) {
		$value     = array();
		$func_name = "get_$key";
		/** @var \WC_Product $product */
		foreach ( $products as $product ) {
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$value[] = $product->$func_name();
		}

		if ( 'permalink' === $type ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = get_permalink( $v );
			}
		}

		if ( 'thumb_id' === $type ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = get_post_thumbnail_id( $v );
			}
		}

		if ( 'thumb_url' === $type ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = get_the_post_thumbnail_url( $v, 'full' );
			}
		}

		if ( 'categories' === $type ) {
			foreach ( $value as $k => $v ) {
				$categories = wc_get_product( $v )->get_category_ids();
				$vv         = '-';
				if ( ! empty( $categories ) ) {
					$category_names = array();
					foreach ( $categories as $category ) {
						$category_names[] = get_term_by( 'id', $category, 'product_cat' )->name;
					}
					if ( ! empty( $category_names ) ) {
						$value[ $k ] = join( ',', $category_names );
					} else {
						$value[ $k ] = $vv;
					}
				} else {
					$value[ $k ] = $vv;
				}
			}
		}

		if ( 'tags' === $type ) {
			foreach ( $value as $k => $v ) {
				$tags = wc_get_product( $v )->get_tag_ids();
				$vv   = '-';
				if ( ! empty( $tags ) ) {
					$tag_names = array();
					foreach ( $tags as $tag ) {
						$tag_names[] = get_term( $tag )->name;
					}
					if ( ! empty( $tag_names ) ) {
						$value[ $k ] = join( ',', $tag_names );
					} else {
						$value[ $k ] = $vv;
					}
				} else {
					$value[ $k ] = $vv;
				}
			}
		}

		return join( apply_filters( 'automator_pro_woo_item_in_term_multi_item_separator', ', ' ), $value );
	}

	/**
	 * Get item details for PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param     $data
	 * @param     $term_id
	 * @param int $item_id
	 *
	 * @return array
	 */
	public function get_item_details( $data, $term_id, $item_id = 0 ) {
		$item_details = array();

		if ( 0 !== $item_id ) {
			if ( ! isset( $data['products']['items'][ $item_id ] ) ) {
				return $item_details;
			}
			$item_detail = $data['products']['items'][ $item_id ];

			return array( $item_detail['product_id'] => $item_detail );
		}

		$product_details = $data['terms'];

		if ( ! isset( $product_details[ $term_id ] ) ) {
			return $item_details;
		}

		foreach ( $product_details[ $term_id ] as $product_id => $details ) {
			$item_details[ $details['product_id'] ] = $details;
		}

		return $item_details;
	}

	/**
	 * Get details when term is defined PROD_ORDER_STATUS_CHANGED_IN_A_TERM
	 *
	 * @param $product_details
	 *
	 * @return array
	 */
	public function get_product_details_for_term_trigger( $product_details ) {
		$products = array();
		if ( empty( $product_details ) ) {
			return $products;
		}

		$item_id = absint( $this->item_id );

		// If item_id is defined, return a specific ITEM
		if ( 0 !== $item_id ) {
			$order = wc_get_order( $this->order_id );
			foreach ( $order->get_items() as $items ) {
				if ( $item_id !== $items->get_id() ) {
					continue;
				}
				$products[] = $items->get_product();

				return $products;
			}
		}

		// Else, its possible that the data is combined!
		foreach ( $product_details as $product_detail ) {
			// Only return products that are in the same category
			if ( 0 !== $this->term_id && $this->term_id !== $product_detail['item']['term']->term_id ) {
				continue;
			}
			$products[] = $product_detail['product'];
		}

		return $products;
	}

	/**
	 * @param $order
	 *
	 * @return string
	 */
	public function build_summary_style_html( $order ) {
		$font_colour      = apply_filters( 'automator_woocommerce_order_summary_text_color', '#000', $order );
		$font_family      = apply_filters( 'automator_woocommerce_order_summary_font_family', "'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif", $order );
		$table_styles     = apply_filters( 'automator_woocommerce_order_summary_table_style', '', $order );
		$border_colour    = apply_filters( 'automator_woocommerce_order_summary_border_color', '#eee', $order );
		$tr_border_colour = apply_filters( 'automator_woocommerce_order_summary_tr_border_color', '#e5e5e5', $order );
		$tr_text_colour   = apply_filters( 'automator_woocommerce_order_summary_tr_text_color', '#636363', $order );
		$td_border_colour = apply_filters( 'automator_woocommerce_order_summary_td_border_color', '#e5e5e5', $order );
		$td_text_colour   = apply_filters( 'automator_woocommerce_order_summary_td_text_color', '#636363', $order );

		$html   = array();
		$html[] = sprintf(
			'<table class="td" cellspacing="0" cellpadding="6" border="1" style="color:%s; border: 1px solid %s; vertical-align: middle; width: 100%%; font-family: %s;%s">',
			$font_colour,
			$border_colour,
			$font_family,
			$table_styles
		);
		$items  = $order->get_items();
		$html[] = '<thead>';
		$html[] = '<tr class="row">';
		$th     = sprintf(
			'<th class="td" scope="col" style="color: %s; border: 1px solid %s; vertical-align: middle; padding: 12px; text-align: left;">',
			$tr_text_colour,
			$tr_border_colour
		);
		$html[] = $th . '<strong>' . apply_filters( 'automator_woocommerce_order_summary_product_title', esc_attr__( 'Product', 'uncanny-automator' ) ) . '</strong></th>';
		$html[] = $th . '<strong>' . apply_filters( 'automator_woocommerce_order_summary_quantity_title', esc_attr__( 'Quantity', 'uncanny-automator' ) ) . '</strong></th>';
		$html[] = $th . '<strong>' . apply_filters( 'automator_woocommerce_order_summary_price_title', esc_attr__( 'Price', 'uncanny-automator' ) ) . '</strong></th>';
		$html[] = '</thead>';
		if ( $items ) {
			/** @var WC_Order_Item_Product $item */
			$td = sprintf(
				'<td class="td" style="color: %s; border: 1px solid %s; padding: 12px; text-align: left; vertical-align: middle; font-family: %s">',
				$td_text_colour,
				$td_border_colour,
				$font_family
			);
			foreach ( $items as $item ) {
				$product = $item->get_product();
				if ( true === apply_filters( 'automator_woocommerce_order_summary_show_product_in_invoice', true, $product, $item, $order ) ) {
					$html[] = '<tr class="order_item">';
					$title  = $product->get_title();
					if ( $item->get_variation_id() ) {
						$variation      = new \WC_Product_Variation( $item->get_variation_id() );
						$variation_name = implode( ' / ', $variation->get_variation_attributes() );
						$title          = apply_filters( 'automator_woocommerce_order_summary_line_item_title', "$title - $variation_name", $product, $item, $order );
					}
					if ( true === apply_filters( 'automator_woocommerce_order_summary_link_to_line_item', true, $product, $item, $order ) ) {
						$title = sprintf( '<a style="color: %s; vertical-align: middle; padding: 12px 0; text-align: left;" href="%s">%s</a>', $td_text_colour, $product->get_permalink(), $title );
					}
					$html[] = sprintf( '%s %s</td>', $td, $title );
					$html[] = $td . $item->get_quantity() . '</td>';
					$html[] = $td . wc_price( $item->get_total() ) . '</td>';
					$html[] = '</tr>';
				}
			}
		}

		$td       = sprintf(
			'<td colspan="2" class="td" style="color: %s; border: 1px solid %s; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;">',
			$td_text_colour,
			$td_border_colour
		);
		$td_right = sprintf(
			'<td class="td" style="color: %s; border: 1px solid %s; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;">',
			$td_text_colour,
			$td_border_colour
		);
		// Subtotal
		if ( true === apply_filters( 'automator_woocommerce_order_summary_show_subtotal', true, $order ) ) {
			$html[] = '<tr>';
			$html[] = $td;
			$html[] = apply_filters( 'automator_woocommerce_order_summary_subtotal_title', esc_attr__( 'Subtotal:', 'uncanny-automator' ) );
			$html[] = '</td>';
			$html[] = $td_right;
			$html[] = $order->get_subtotal_to_display();
			$html[] = '</td>';
			$html[] = '</tr>';
		}
		// Tax
		if ( true === apply_filters( 'automator_woocommerce_order_summary_show_taxes', true, $order ) ) {
			if ( ! empty( $order->get_taxes() ) ) {
				$html[] = '<tr>';
				$html[] = $td;
				$html[] = apply_filters( 'automator_woocommerce_order_summary_tax_title', esc_attr__( 'Tax:', 'uncanny-automator' ) );
				$html[] = '</td>';
				$html[] = $td_right;
				$html[] = wc_price( $order->get_total_tax() );
				$html[] = '</td>';
				$html[] = '</tr>';
			}
		}
		// Payment method
		if ( true === apply_filters( 'automator_woocommerce_order_summary_show_payment_method', true, $order ) ) {
			$html[] = '<tr>';
			$html[] = $td;
			$html[] = apply_filters( 'automator_woocommerce_order_summary_payment_method_title', esc_attr__( 'Payment method:', 'uncanny-automator' ) );
			$html[] = '</td>';
			$html[] = $td_right;
			$html[] = $order->get_payment_method_title();
			$html[] = '</td>';
			$html[] = '</tr>';
		}
		// Total
		if ( true === apply_filters( 'automator_woocommerce_order_summary_show_total', true, $order ) ) {
			$html[] = '<tr>';
			$html[] = $td;
			$html[] = apply_filters( 'automator_woocommerce_order_summary_total_title', esc_attr__( 'Total:', 'uncanny-automator' ) );
			$html[] = '</td>';
			$html[] = $td_right;
			$html[] = $order->get_formatted_order_total();
			$html[] = '</td>';
			$html[] = '</tr>';
		}
		$html[] = '</table>';
		$html   = apply_filters( 'automator_order_summary_html_raw', $html, $order );

		return implode( PHP_EOL, $html );
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function wc_wcprodstock_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( isset( $trigger_data['meta']['code'] ) && 'WCPRODOUTOFSTOCK' === $trigger_data['meta']['code'] ) {
			return $value;
		}

		$to_match = array(
			'product_sku',
			'product_stock',
		);

		if ( $pieces && isset( $pieces[2] ) ) {
			$meta_field = $pieces[2];
			if ( ! empty( $meta_field ) && in_array( $meta_field, $to_match, false ) ) {
				if ( $trigger_data ) {

					foreach ( $trigger_data as $trigger ) {
						if ( empty( $trigger ) ) {
							continue;
						}

						$product_id = Automator()->db->token->get( 'product_id', $replace_args );
						$product    = wc_get_product( $product_id );
						if ( $product instanceof \WC_Product ) {
							switch ( $meta_field ) {
								case 'product_stock':
									$value = $product->get_stock_quantity();
									break;
								case 'product_sku':
									$value = $product->get_sku();
									break;
								default:
									$this->handle_default_switch( $value, $meta_field, $pieces, array() );
									break;
							}
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function wc_wcprodstock_status_tokens_pro( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( isset( $trigger_data['meta']['code'] ) && 'WCPRODSTOCKSTATUS' !== $trigger_data['meta']['code'] ) {
			return $value;
		}

		$to_match = array(
			'WOOPRODUCTSTOCKSTATUS_STOCKSTATUS',
			'WOOPRODUCT_CATEGORIES',
			'WOOPRODUCT_TAGS',
			'product_price',
		);

		if ( $pieces && isset( $pieces[2] ) ) {
			$meta_field = $pieces[2];

			if ( ! empty( $meta_field ) && in_array( $meta_field, $to_match, false ) ) {
				if ( $trigger_data ) {

					foreach ( $trigger_data as $trigger ) {
						if ( empty( $trigger ) ) {
							continue;
						}
						$product_id = Automator()->db->token->get( 'product_id', $replace_args );
						$product    = wc_get_product( $product_id );
						if ( ! $product instanceof \WC_Product ) {
							continue;
						}
						switch ( $meta_field ) {
							case 'WOOPRODUCTSTOCKSTATUS_STOCKSTATUS':
								$value = Automator()->db->token->get( 'WCPRODSTOCKSTATUS', $replace_args );
								break;
							case 'WOOPRODUCT_CATEGORIES':
								$categories = $product->get_category_ids();
								$value      = '-';
								if ( ! empty( $categories ) ) {
									$category_names = array();
									$value          = '';
									foreach ( $categories as $category ) {
										$category_names[] = get_term_by( 'id', $category, 'product_cat' )->name;
									}
									if ( ! empty( $category_names ) ) {
										$value = join( ',', $category_names );
									}
								}
								break;
							case 'WOOPRODUCT_TAGS':
								$tags  = $product->get_tag_ids();
								$value = '-';
								if ( ! empty( $tags ) ) {
									$tag_names = array();
									$value     = '';
									foreach ( $tags as $tag ) {
										$tag_names[] = get_term( $tag )->name;
									}
									if ( ! empty( $tag_names ) ) {
										$value = join( ',', $tag_names );
									}
								}
								break;
							case 'product_price':
								$value = $product->get_price();
								break;
							default:
								$this->handle_default_switch( $value, $meta_field, $pieces, array() );
								break;
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 * @param string   $type
	 *
	 * @return string
	 */
	public function get_woo_variation_product_ids_from_items( WC_Order $order, $value_to_match, $type = 'product' ) {
		$items       = $order->get_items();
		$product_ids = array();
		if ( $items ) {
			/** @var WC_Order_Item_Product $item */
			foreach ( $items as $item ) {
				$value_to_compare = $item->get_product_id();
				if ( 'variation' === $type ) {
					$value_to_compare = $item->get_variation_id();
				}
				if ( absint( $value_to_match ) === absint( $value_to_compare ) || absint( '-1' ) === absint( $value_to_match ) ) {
					$product_ids[] = $item->get_variation_id();
				}
			}
		}

		return join( ', ', $product_ids );
	}

	/**
	 * @param WC_Order $order
	 * @param          $value_to_match
	 * @param string   $type
	 *
	 * @return string
	 */
	public function get_woo_product_names_from_items_a( WC_Order $order, $value_to_match, $type = 'product' ) {
		$items          = $order->get_items();
		$product_titles = array();
		if ( $items ) {
			/** @var WC_Order_Item_Product $item */
			foreach ( $items as $item ) {
				$value_to_compare = $item->get_product_id();
				if ( 'variation' === $type ) {
					$value_to_compare = $item->get_variation_id();
				}
				if ( absint( $value_to_match ) === absint( $value_to_compare ) || absint( '-1' ) === absint( $value_to_match ) ) {
					$product_titles[] = $item->get_product()->get_name();
				}
			}
		}

		return join( ', ', $product_titles );
	}

	/**
	 * Easily get those order action tokens in the form of static method.
	 *
	 * @return array The order action tokens.
	 */
	public static function get_order_action_tokens() {
		return array(
			'ORDER_ID'                  => array(
				'name' => __( 'Order ID', 'uncanny-automator-pro' ),
			),
			'ORDER_URL_ADMIN'           => array(
				'name' => __( 'Order URL (admin)', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ORDER_URL_USER'            => array(
				'name' => __( 'Order URL (user)', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ORDER_PAYMENT_URL'         => array(
				'name' => __( 'Payment URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ORDER_DIRECT_CHECKOUT_URL' => array(
				'name' => __( 'Direct checkout URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
		);
	}

	/**
	 * Easily get those order action tokens in the form of static method.
	 *
	 * @return array The order action tokens.
	 */
	public static function get_product_added_to_order_action_tokens() {
		return array(
			'WC_PRODUCTS'            => array(
				'name' => __( 'Product title', 'uncanny-automator-pro' ),
			),
			'WC_PRODUCTS_ID'         => array(
				'name' => __( 'Product ID', 'uncanny-automator-pro' ),
			),
			'WC_PRODUCTS_URL'        => array(
				'name' => __( 'Product URL', 'uncanny-automator-pro' ),
			),
			'WC_PRODUCTS_THUMB_ID'   => array(
				'name' => __( 'Product featured image ID', 'uncanny-automator-pro' ),
			),
			'WC_PRODUCTS_THUMB_URL'  => array(
				'name' => __( 'Product featured image URL', 'uncanny-automator-pro' ),
			),
			'WC_PRODUCTS_ORDER_QTY'  => array(
				'name' => __( 'Product quantity', 'uncanny-automator-pro' ),
			),
			'UPDATED_ORDER_SUBTOTAL' => array(
				'name' => __( 'Updated order subtotal', 'uncanny-automator-pro' ),
			),
			'UPDATED_ORDER_TOTAL'    => array(
				'name' => __( 'Updated order total', 'uncanny-automator-pro' ),
			),
			'UPDATED_ORDER_PRODUCTS' => array(
				'name' => __( 'Updated order products', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * Easily hydrate the tokens coming from $order object.
	 *
	 * @param $order \WC_Order The order object.
	 *
	 * @return array The hydrated tokens from $object.
	 */
	public static function hydrate_order_tokens( $order = null ) {

		if ( $order instanceof \WC_Order ) {

			return array(
				'ORDER_ID'                  => $order->get_id(),
				'ORDER_URL_USER'            => $order->get_checkout_order_received_url(),
				'ORDER_URL_ADMIN'           => admin_url(
					strtr( 'post.php?post=:id&action=edit', array( ':id' => absint( $order->get_id() ) ) )
				),
				'ORDER_PAYMENT_URL'         => $order->get_checkout_payment_url(),
				'ORDER_DIRECT_CHECKOUT_URL' => $order->get_checkout_payment_url( true ),
			);

		}

		return array();

	}

	/**
	 * @return array
	 */
	public static function set_wc_order_tokens_for_actions() {
		$tokens       = array();
		$wc_tokens    = new Wc_Tokens();
		$order_tokens = $wc_tokens->possible_order_fields;
		$skip_tokens  = array( 'WOOPRODUCT_CATEGORIES', 'WOOPRODUCT_TAGS' );
		foreach ( $order_tokens as $token_id => $input_title ) {
			$input_type = 'text';
			if ( 'billing_email' === $token_id || 'shipping_email' === $token_id ) {
				$input_type = 'email';
			} elseif ( 'order_qty' === $token_id ) {
				$input_type = 'int';
			}
			if ( ! in_array( $token_id, $skip_tokens, true ) ) {
				$tokens[ $token_id ] = array(
					'name' => $input_title,
					'type' => $input_type,
				);
			}
		}

		return $tokens;
	}

	/**
	 * @param $order_id
	 * @param $order
	 *
	 * @return array
	 */
	public static function get_wc_order_tokens_parsed_for_actions( $order_id, $order ) {
		$order_token_values = array();

		if ( $order instanceof \WC_Order ) {
			$wc_tokens = new Wc_Tokens();
			$comments  = $order->get_customer_note();
			if ( is_array( $comments ) ) {
				$comments = join( ' | ', $comments );
			}

			$coupons = $order->get_coupon_codes();
			$coupons = join( ', ', $coupons );

			$items                  = $order->get_items();
			$ordered_products       = array();
			$ordered_products_links = array();
			$ordered_products_qty   = array();
			$qty                    = 0;
			if ( $items ) {
				/** @var \WC_Order_Item_Product $item */
				foreach ( $items as $item ) {
					$product                  = $item->get_product();
					$ordered_products[]       = $product->get_title();
					$ordered_products_qty[]   = $product->get_title() . ' x ' . $item->get_quantity();
					$qty                      += $item->get_quantity();
					$ordered_products_links[] = '<a href="' . $product->get_permalink() . '">' . $product->get_title() . '</a>';
				}
			}
			$ordered_products       = join( ' | ', $ordered_products );
			$ordered_products_qty   = join( ' | ', $ordered_products_qty );
			$ordered_products_links = join( ' | ', $ordered_products_links );

			$stripe_fee    = 0;
			$stripe_payout = 0;
			if ( function_exists( 'stripe_wc' ) ) {
				$stripe_fee    = \WC_Stripe_Utils::display_fee( $order );
				$stripe_payout = \WC_Stripe_Utils::display_net( $order );
			}
			if ( ( function_exists( 'woocommerce_gateway_stripe' ) || class_exists( '\WC_Stripe_Helper' ) ) && 0 === $stripe_fee ) {
				$stripe_fee = \WC_Stripe_Helper::get_stripe_fee( $order );
			}
			if ( class_exists( '\WC_Stripe_Helper' ) && 0 === $stripe_payout ) {
				$stripe_payout = \WC_Stripe_Helper::get_stripe_net( $order );
			}

			$order_token_values = array(
				'order_id'              => $order_id,
				'billing_first_name'    => $order->get_billing_first_name(),
				'billing_last_name'     => $order->get_billing_last_name(),
				'billing_company'       => $order->get_billing_company(),
				'billing_country'       => $order->get_billing_country(),
				'billing_country_name'  => $wc_tokens->get_country_name_from_code( $order->get_billing_country() ),
				'billing_address_1'     => $order->get_billing_address_1(),
				'billing_address_2'     => $order->get_billing_address_2(),
				'billing_city'          => $order->get_billing_city(),
				'billing_state'         => $order->get_billing_state(),
				'billing_state_name'    => $wc_tokens->get_state_name_from_codes( $order->get_billing_state(), $order->get_billing_country() ),
				'billing_postcode'      => $order->get_billing_postcode(),
				'billing_phone'         => $order->get_billing_phone(),
				'billing_email'         => $order->get_billing_email(),
				'order_date'            => $order->get_date_created()->format( get_option( 'date_format', 'F j, Y' ) ),
				'order_time'            => $order->get_date_created()->format( get_option( 'time_format', 'H:i:s' ) ),
				'order_date_time'       => $order->get_date_created()->format( sprintf( '%s %s', get_option( 'date_format', 'F j, Y' ), get_option( 'time_format', 'H:i:s' ) ) ),
				'shipping_first_name'   => $order->get_shipping_first_name(),
				'shipping_company'      => $order->get_shipping_company(),
				'shipping_country'      => $order->get_shipping_country(),
				'shipping_country_name' => $wc_tokens->get_country_name_from_code( $order->get_shipping_country() ),
				'shipping_address_1'    => $order->get_shipping_address_1(),
				'shipping_address_2'    => $order->get_shipping_address_2(),
				'shipping_last_name'    => $order->get_shipping_last_name(),
				'shipping_method'       => $order->get_shipping_method(),
				'product_sku'           => $wc_tokens->get_products_skus( $order ),
				'order_summary'         => $wc_tokens->build_summary_style_html( $order ),
				'shipping_city'         => $order->get_shipping_city(),
				'shipping_state'        => $order->get_shipping_state(),
				'shipping_state_name'   => $wc_tokens->get_state_name_from_codes( $order->get_shipping_state(), $order->get_shipping_country() ),
				'shipping_postcode'     => $order->get_shipping_postcode(),
				'shipping_phone'        => get_post_meta( $order_id, 'shipping_phone', true ),
				'order_comments'        => $comments,
				'order_status'          => $order->get_status(),
				'order_total'           => wp_strip_all_tags( wc_price( $order->get_total() ) ),
				'order_total_raw'       => $order->get_total(),
				'order_subtotal'        => wp_strip_all_tags( wc_price( $order->get_subtotal() ) ),
				'order_subtotal_raw'    => $order->get_subtotal(),
				'order_tax'             => wp_strip_all_tags( wc_price( $order->get_total_tax() ) ),
				'order_fees'            => wc_price( $order->get_total_fees() ),
				'order_shipping'        => wc_price( $order->get_shipping_total() ),
				'order_tax_raw'         => $order->get_total_tax(),
				'order_discounts'       => wp_strip_all_tags( wc_price( $order->get_discount_total() * - 1 ) ),
				'order_discounts_raw'   => ( $order->get_discount_total() * - 1 ),
				'order_coupons'         => $coupons,
				'order_products'        => $ordered_products,
				'order_products_qty'    => $ordered_products_qty,
				'order_qty'             => $qty,
				'order_products_links'  => $ordered_products_links,
				'payment_method'        => $order->get_payment_method_title(),
				'payment_url'           => $order->get_checkout_payment_url(),
				'payment_url_checkout'  => $order->get_checkout_payment_url( true ),
				'stripe_fee'            => $stripe_fee,
				'stripe_payout'         => $stripe_payout,
			);
		}

		return $order_token_values;
	}

	/**
	 * Easily hydrate the tokens coming from $order object.
	 *
	 * @param      $order      \WC_Order The order object.
	 * @param int  $product_id the product ID
	 *
	 * @return array The hydrated tokens from $object.
	 */
	public static function hydrate_product_added_to_order_tokens( $order = null, $product_id = null ) {

		if ( $order instanceof \WC_Order ) {
			$items              = $order->get_items();
			$product_names      = array();
			$product_quantities = array();
			if ( $items ) {
				/** @var WC_Order_Item_Product $item */
				foreach ( $items as $item ) {
					$product_names[]      = $item->get_name();
					$product_quantities[] = $item->get_quantity();
				}
			}
			$ordered_products            = join( ' | ', $product_names );
			$ordered_products_quantities = join( ' | ', $product_quantities );

			return array(
				'UPDATED_ORDER_PRODUCTS' => $ordered_products,
				'UPDATED_ORDER_SUBTOTAL' => $order->get_subtotal(),
				'UPDATED_ORDER_TOTAL'    => $order->get_total(),
				'WC_PRODUCTS'            => get_the_title( $product_id ),
				'WC_PRODUCTS_ID'         => $product_id,
				'WC_PRODUCTS_URL'        => get_permalink( $product_id ),
				'WC_PRODUCTS_THUMB_ID'   => get_post_thumbnail_id( $product_id ),
				'WC_PRODUCTS_THUMB_URL'  => get_the_post_thumbnail_url( $product_id ),
				'WC_PRODUCTS_ORDER_QTY'  => $ordered_products_quantities,
			);

		}

		return array();

	}

	/**
	 * Retrieve those subscription tokens.
	 *
	 * @return array The order action tokens.
	 */
	public static function get_order_subscription_tokens() {

		// Early bail if function does not exists. Dependency from 3rd-party plugin outside WC.
		if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			return array();
		}

		return array(
			'ORDER_SUBSCRIPTION_ID'  => array(
				'name' => __( 'Subscription ID', 'uncanny-automator-pro' ),
			),
			'ORDER_SUBSCRIPTION_URL' => array(
				'name' => __( 'Subscription URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
		);

	}

	/**
	 * Hydrate the subscription tokens coming from $order object.
	 *
	 * @param $order \WC_Order The order object.
	 *
	 * @return array The hydrated tokens from $object.
	 */
	public static function hydrate_order_subscription_tokens( $order = null ) {

		// Early bail if function does not exists. Dependency from 3rd-party plugin outside WC.
		if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			return array();
		}

		if ( $order instanceof \WC_Order ) {

			$subscription_id = array_keys( wcs_get_subscriptions_for_order( $order->get_id() ) );

			return array(
				'ORDER_SUBSCRIPTION_ID'  => end( $subscription_id ), // We only process one subscription at a time.
				'ORDER_SUBSCRIPTION_URL' => admin_url(
					strtr( 'post.php?post=:id&action=edit', array( ':id' => absint( end( $subscription_id ) ) ) )
				),
			);

		}

		return array();

	}

	/**
	 * @param $order
	 * @param $value_to_match
	 * @param $type
	 *
	 * @return string
	 */
	public function get_woo_product_details( $order, $value_to_match, $type ) {
		$items           = $order->get_items();
		$product_details = array();
		if ( $items ) {
			/** @var WC_Order_Item_Product $item */
			foreach ( $items as $item ) {
				if ( absint( $value_to_match ) === absint( $item->get_product_id() ) || absint( '-1' ) === absint( $value_to_match ) ) {
					if ( 'price' === $type ) {
						$product_details[ $type ][] = $item->get_product()->get_price();
					} elseif ( 'sku' === $type ) {
						$product_details[ $type ][] = $item->get_product()->get_sku();
					} elseif ( 'quantity' === $type ) {
						$product_details[ $type ][] = $item->get_quantity();
					} elseif ( 'sale' === $type ) {
						$product_details[ $type ][] = $item->get_product()->get_sale_price();
					}
				}
			}
		}

		return join( ', ', $product_details[ $type ] );
	}

	/**
	 * Helper function to return country name from provided code.
	 *
	 * @param string $country_code
	 *
	 * @return string $country_name if found, otherwise $country_code
	 */
	public function pro_get_country_name_from_code( $country_code ) {
		$countries = WC()->countries->get_countries();
		if ( ! empty( $countries ) ) {
			foreach ( $countries as $country_key => $country_name ) {
				if ( $country_key === $country_code ) {
					return $country_name;
				}
			}
		}

		return $country_code;
	}

	/**
	 * Helper function to return state name from provided codes.
	 *
	 * @param string $state_code
	 * @param string $country_code
	 *
	 * @return string $state_name if found, otherwise $state_code
	 */
	public function pro_get_state_name_from_codes( $state_code, $country_code ) {
		$states = WC()->countries->get_states( $country_code );
		if ( ! empty( $states ) ) {
			foreach ( $states as $state_key => $state_name ) {
				if ( $state_key === $state_code ) {
					return $state_name;
				}
			}
		}

		return $state_code;
	}

}
