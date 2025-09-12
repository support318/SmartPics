<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens;

use WC_Subscription;

/**
 * Common tokens for WooCommerce Subscription.
 *
 * All tokens are prefixed with the unique code.
 *
 * @since 6.6
 */
class Common_Tokens {

	/**
	 * The common tokens.
	 *
	 * @var array
	 */
	protected $common_tokens = array();

	/**
	 * The unique code.
	 *
	 * @var string
	 */
	protected $unique_code;

	/**
	 * Whether to treat the subscription as a variation.
	 *
	 * @var bool
	 */
	protected $as_variation = false;

	/**
	 * Constructor.
	 *
	 * @param string $unique_code The unique code.
	 *
	 * @param bool   $as_variation Whether to treat the subscription as a variation.
	 */
	public function __construct( $unique_code, $as_variation = false ) {

		$this->unique_code  = $unique_code;
		$this->as_variation = $as_variation;

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code,
			'tokenName' => $this->as_variation
				? esc_html_x( 'Variation title', 'Woocommerce Subscription', 'uncanny-automator-pro' )
				: esc_html_x( 'Product title', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_ID',
			'tokenName' => $this->as_variation
				? esc_html_x( 'Variation ID', 'Woocommerce Subscription', 'uncanny-automator-pro' )
				: esc_html_x( 'Product ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_URL',
			'tokenName' => $this->as_variation
				? esc_html_x( 'Variation URL', 'Woocommerce Subscription', 'uncanny-automator-pro' )
				: esc_html_x( 'Product URL', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'url',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_THUMB_URL',
			'tokenName' => $this->as_variation
				? esc_html_x( 'Variation featured image URL', 'Woocommerce Subscription', 'uncanny-automator-pro' )
				: esc_html_x( 'Product featured image URL', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'url',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_THUMB_ID',
			'tokenName' => $this->as_variation
				? esc_html_x( 'Variation featured image ID', 'Woocommerce Subscription', 'uncanny-automator-pro' )
				: esc_html_x( 'Product featured image ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_ID',
			'tokenName' => esc_html_x( 'Subscription ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_STATUS',
			'tokenName' => esc_html_x( 'Subscription status', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_TRIAL_END_DATE',
			'tokenName' => esc_html_x( 'Subscription trial end date', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_END_DATE',
			'tokenName' => esc_html_x( 'Subscription end date', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_NEXT_PAYMENT_DATE',
			'tokenName' => esc_html_x( 'Subscription next payment date', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->common_tokens[] = array(
			'tokenId'   => $this->unique_code . '_SUBSCRIPTION_RENEWAL_COUNT',
			'tokenName' => esc_html_x( 'Subscription renewal count', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);
	}

	/**
	 * Add a token
	 *
	 * @param array $token The token to add.
	 */
	public function add_token( $token ) {
		$this->common_tokens[] = $token;
	}

	/**
	 * Set the token as a variation
	 *
	 * @param bool $as_variation
	 */
	public function set_as_variation( $as_variation ) {
		$this->as_variation = $as_variation;
	}

	/**
	 * Get tokens
	 *
	 * @return array
	 */
	public function get_tokens() {
		return $this->common_tokens;
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
			case $this->unique_code:
				$token_value = $this->get_subscription_product_title( $subscription );
				break;
			case $this->unique_code . '_SUBSCRIPTION_ID':
				$token_value = $subscription->get_id();
				break;

			case $this->unique_code . '_SUBSCRIPTION_STATUS':
				$token_value = $subscription->get_status();
				break;

			case $this->unique_code . '_SUBSCRIPTION_TRIAL_END_DATE':
				// Using get_date() as per docs instead of get_trial_end()
				$token_value = $this->format_with_na( $subscription->get_date( 'trial_end' ) );
				break;

			case $this->unique_code . '_SUBSCRIPTION_END_DATE':
				// Using get_date() as per docs instead of get_end_date()
				$token_value = $this->format_with_na( $subscription->get_date( 'end' ) );
				break;

			case $this->unique_code . '_SUBSCRIPTION_NEXT_PAYMENT_DATE':
				// Using get_date() as per docs instead of get_next_payment_date()
				$token_value = $this->format_with_na( $subscription->get_date( 'next_payment' ) );
				break;

			case $this->unique_code . '_SUBSCRIPTION_RENEWAL_COUNT':
				// Using get_payment_count() as per docs
				$token_value = $subscription->get_payment_count( 'completed', array( 'renewal' ) );
				break;

			// Product related tokens
			case $this->unique_code . '_ID':
				$token_value = $this->get_subscription_product_id( $subscription );
				break;

			case $this->unique_code . '_URL':
				$product_id  = $this->get_subscription_product_id( $subscription );
				$token_value = get_permalink( $product_id );
				break;

			case $this->unique_code . '_THUMB_URL':
				$product_id  = $this->get_subscription_product_id( $subscription );
				$token_value = $this->format_with_na( get_the_post_thumbnail_url( $product_id, 'full' ) );
				break;

			case $this->unique_code . '_THUMB_ID':
				$product_id  = $this->get_subscription_product_id( $subscription );
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
		return apply_filters( 'automator_woocommerce_subscription_token_value', $token_value, $token_id, $subscription );
	}

	/**
	 * Get subscription product or variation title
	 *
	 * @param WC_Subscription $subscription
	 * @return string
	 */
	private function get_subscription_product_title( $subscription ) {

		$items = $subscription->get_items();
		$item  = reset( $items ); // Get first item.

		return $item ? $item->get_name() : '';
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

	/**
	 * Helper method to get subscription product ID or variation ID
	 *
	 * @param WC_Subscription $subscription
	 * @return int
	 */
	private function get_subscription_product_id( $subscription ) {

		$items = $subscription->get_items();
		$item  = reset( $items ); // Get the first item.

		if ( $this->as_variation && $item ) {
			return $item->get_variation_id();
		}

		return $item ? $item->get_product_id() : 0;
	}
}
