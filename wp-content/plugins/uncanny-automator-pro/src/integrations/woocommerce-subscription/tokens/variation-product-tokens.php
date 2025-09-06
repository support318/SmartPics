<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens;

use WC_Subscription;

/**
 * Variation product tokens.
 *
 * All tokens are prefixed with the unique code.
 *
 * @since 6.6
 */
class Variation_Product_Tokens {

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
	protected $unique_code;

	/**
	 * The prefix.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * The token identifier.
	 *
	 * @var string
	 */
	protected $token_identifier;

	/**
	 * Constructor
	 *
	 * @param string $unique_code
	 */
	public function __construct( $unique_code = '', $token_identifier = '' ) {

		$this->unique_code      = $unique_code;
		$this->token_identifier = $token_identifier;

		$this->tokens[] = array(
			'tokenId'   => $this->create_unique_code( 'PRODUCT' ),
			'tokenName' => esc_html_x( 'Product title', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->tokens[] = array(
			'tokenId'   => $this->create_unique_code( 'PRODUCT_ID' ),
			'tokenName' => esc_html_x( 'Product ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->tokens[] = array(
			'tokenId'   => $this->create_unique_code( 'PRODUCT_URL' ),
			'tokenName' => esc_html_x( 'Product URL', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'url',
		);

		$this->tokens[] = array(
			'tokenId'   => $this->create_unique_code( 'PRODUCT_THUMB_URL' ),
			'tokenName' => esc_html_x( 'Product featured image URL', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'url',
		);

		$this->tokens[] = array(
			'tokenId'   => $this->create_unique_code( 'PRODUCT_THUMB_ID' ),
			'tokenName' => esc_html_x( 'Product featured image ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		if ( ! empty( $this->token_identifier ) ) {
			foreach ( $this->tokens as $key => $token ) {
				$this->tokens[ $key ]['tokenIdentifier'] = $this->token_identifier;
			}
		}
	}

	/**
	 * Create a unique code
	 *
	 * @param string $token
	 *
	 * @return string
	 */
	protected function create_unique_code( $token ) {

		if ( empty( $this->unique_code ) ) {
			return $token;
		}

		return $this->unique_code . '_' . $token;
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
	 * Parse subscription tokens
	 *
	 * @param string          $token_id The token identifier
	 * @param WC_Subscription $subscription The subscription object
	 *
	 * @return mixed The token value
	 */
	public function parse_token( $token_id, $subscription ) {

		// Default value in case of errors
		$token_value = '';

		// Ensure we have a valid subscription object
		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			return $token_value;
		}

		switch ( $token_id ) {
			case $this->unique_code . $this->prefix . '_PRODUCT':
				$token_value = $this->get_product_title( $subscription );
				break;

			case $this->unique_code . $this->prefix . '_PRODUCT_ID':
				$token_value = $this->get_product_id( $subscription );
				break;

			case $this->unique_code . $this->prefix . '_PRODUCT_URL':
				$product_id  = $this->get_product_id( $subscription );
				$token_value = get_permalink( $product_id );
				break;

			case $this->unique_code . $this->prefix . '_PRODUCT_THUMB_URL':
				$product_id  = $this->get_product_id( $subscription );
				$token_value = $this->format_with_na( get_the_post_thumbnail_url( $product_id, 'full' ) );
				break;

			case $this->unique_code . $this->prefix . '_PRODUCT_THUMB_ID':
				$product_id  = $this->get_product_id( $subscription );
				$token_value = $this->format_with_na( get_post_thumbnail_id( $product_id ) );
				break;
		}

		/**
		 * Filter the token value before returning
		 *
		 * @param mixed $token_value The value of the token
		 * @param string $token_id The token identifier
		 * @param WC_Subscription $subscription The subscription object
		 */
		return apply_filters( 'automator_woocommerce_subscription_variation_product_token_value', $token_value, $token_id, $subscription );
	}

	/**
	 * Get parent product title for a variation
	 *
	 * @param WC_Subscription $subscription
	 * @return string
	 */
	private function get_product_title( $subscription ) {

		$product_id = $this->get_product_id( $subscription );

		if ( empty( $product_id ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		return $product ? $product->get_name() : '';
	}

	/**
	 * Get parent product ID for a variation
	 *
	 * @param WC_Subscription $subscription
	 * @return int
	 */
	private function get_product_id( $subscription ) {

		$items = $subscription->get_items();
		$item  = reset( $items ); // Get first item

		if ( ! $item ) {
			return 0;
		}

		// Always return the product_id (parent) even for variations
		return $item->get_product_id();
	}

	/**
	 * Format the value as N/A if it is empty.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function format_with_na( $value ) {

		if ( empty( $value ) ) {
			return esc_html_x( 'N/A', 'WooCommerce Subscription', 'uncanny-automator-pro' );
		}

		return $value;
	}
}
