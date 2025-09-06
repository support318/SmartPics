<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens;

/**
 * Product Extra Tokens
 *
 * Handles token generation and parsing for WooCommerce product data.
 *
 * @since 1.0.0
 */
class Product_Extra_Tokens {

	/**
	 * The tokens.
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * The unique code.
	 *
	 * @var string
	 */
	protected $unique_code = 'WOOVARIPRODUCT';

	/**
	 * Constructor.
	 *
	 * @param string $unique_code The token identifier code.
	 */
	public function __construct( $unique_code = 'WOOVARIPRODUCT' ) {

		$this->unique_code = $unique_code;
		$this->setup_tokens();
	}

	/**
	 * Setup available tokens.
	 *
	 * @return void
	 */
	private function setup_tokens() {

		$token_definitions = $this->get_token_definitions();

		foreach ( $token_definitions as $token_id => $token_name ) {
			$this->tokens[] = array(
				'tokenId'         => $token_id,
				'tokenName'       => $token_name,
				'tokenType'       => 'text',
				'tokenIdentifier' => $this->unique_code,
			);
		}
	}

	/**
	 * Define token labels.
	 *
	 * @return array Array of token IDs and their display names.
	 */
	private function get_token_definitions() {

		return array(
			'product_qty'      => esc_html_x( 'Product quantity', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'product_price'    => esc_html_x( 'Product price', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'product_sku'      => esc_html_x( 'Product SKU', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'WCPURCHPRODINCAT' => esc_html_x( 'Product categories', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'WCPURCHPRODINTAG' => esc_html_x( 'Product tags', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'WOOVARIATION_ID'  => esc_html_x( 'Variation ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
		);
	}

	/**
	 * Get tokens
	 *
	 * @return array
	 */
	public function get_tokens() {

		return $this->tokens;
	}

	/**
	 * Parse token
	 *
	 * @param string                     $token_id The token ID.
	 * @param \WC_Order|\WC_Subscription $order The order or subscription.
	 *
	 * @return string
	 */
	public function parse_token( $token_id, $order ) {

		// Get product data with caching.
		$product_data = $this->get_product_data( $order );

		if ( empty( $product_data ) ) {
			return '';
		}

		return $this->get_token_value( $token_id, $product_data );
	}

	/**
	 * Get the value for a specific token from product data.
	 *
	 * @param string $token_id     The token identifier.
	 * @param array  $product_data The product data array.
	 *
	 * @return string The token value.
	 */
	private function get_token_value( $token_id, $product_data ) {

		$token_map = array(
			'product_qty'      => 'qty',
			'product_price'    => 'price',
			'product_sku'      => 'sku',
			'WCPURCHPRODINCAT' => 'categories',
			'WCPURCHPRODINTAG' => 'tags',
			'WOOVARIATION_ID'  => 'variation_id',
		);

		if ( isset( $token_map[ $token_id ] ) && isset( $product_data[ $token_map[ $token_id ] ] ) ) {
			return $product_data[ $token_map[ $token_id ] ];
		}

		return '';
	}

	/**
	 * Get product data from order with caching.
	 *
	 * @param \WC_Order|\WC_Subscription $order Order or subscription object.
	 *
	 * @return array|null Product data or null if not found.
	 */
	private function get_product_data( $order ) {

		static $cache = array();

		// Use object ID for cache key.
		$cache_key = $this->generate_cache_key( $order );

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		// Check if valid order.
		if ( ! $this->is_valid_order( $order ) ) {
			return null;
		}

		// Get items safely.
		try {
			$product = $this->get_product_from_order( $order );

			if ( ! $product ) {
				return null;
			}

			// Build and cache product data.
			$result              = $this->build_product_data( $order, $product );
			$cache[ $cache_key ] = $result;

			return $result;

		} catch ( \Exception $e ) {
			$this->log_error( $e );
			return null;
		}
	}

	/**
	 * Generate a cache key for an order object.
	 *
	 * @param object $order The order object.
	 *
	 * @return string The cache key.
	 */
	private function generate_cache_key( $order ) {

		return spl_object_hash( $order );
	}

	/**
	 * Check if the order object is valid.
	 *
	 * @param mixed $order The order to check.
	 *
	 * @return bool Whether the order is valid.
	 */
	private function is_valid_order( $order ) {

		return is_a( $order, 'WC_Order' ) || is_a( $order, 'WC_Subscription' );
	}

	/**
	 * Get the product from an order.
	 *
	 * @param \WC_Order|\WC_Subscription $order The order object.
	 *
	 * @return \WC_Product|null The product or null if not found.
	 */
	private function get_product_from_order( $order ) {

		$items = $order->get_items();

		if ( empty( $items ) ) {
			return null;
		}

		// Get first item.
		$item = reset( $items );

		// Get product from item.
		return $item->get_product();
	}

	/**
	 * Build complete product data array.
	 *
	 * @param \WC_Order|\WC_Subscription $order   The order object.
	 * @param \WC_Product                $product The product object.
	 *
	 * @return array The product data.
	 */
	private function build_product_data( $order, $product ) {

		// Determine product ID (account for variations).
		$product_id   = $product->get_id();
		$is_variation = $product->is_type( 'variation' );
		$parent_id    = $is_variation ? $product->get_parent_id() : $product_id;

		// Build result array with all needed data.
		return array(
			'qty'          => $order->get_item_count(),
			'price'        => $order->get_total(),
			'sku'          => $product->get_sku(),
			'variation_id' => $is_variation ? $product_id : '',
			'categories'   => $this->get_taxonomy_terms( $parent_id, 'product_cat' ),
			'tags'         => $this->get_taxonomy_terms( $parent_id, 'product_tag' ),
		);
	}

	/**
	 * Get formatted taxonomy terms.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $taxonomy   Taxonomy name.
	 *
	 * @return string Comma-separated term names.
	 */
	private function get_taxonomy_terms( $product_id, $taxonomy ) {

		$terms_list = array();
		$terms      = get_the_terms( $product_id, $taxonomy );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( isset( $term->name ) ) {
					$terms_list[] = $term->name;
				}
			}
		}

		return implode( ', ', $terms_list );
	}

	/**
	 * Log an error.
	 *
	 * @param \Exception $exception The exception to log.
	 *
	 * @return void
	 */
	private function log_error( $exception ) {

		automator_log( 'Error getting product data: ' . $exception->getMessage(), 'error', true, 'product-extra-tokens' );
	}
}
