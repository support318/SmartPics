<?php

namespace Uncanny_Automator_Pro;

if ( automator_free_older_than( '5.6' ) ) {
	class SureCart_Pro_Tokens_New_Framework {};
} else {
	/**
	 * Class SureCart_Pro_Tokens_New_Framework
	 *
	 * @package Uncanny_Automator
	 */
	class SureCart_Pro_Tokens_New_Framework extends \Uncanny_Automator\SureCart_Tokens_New_Framework {
		/**
		 *
		 * @return array[] The list of tokens where array key is the token identifier.
		 */
		public function subscription_tokens() {

			$tokens = array(
				array(
					'tokenId'   => 'SUBSCRIPTION_ID',
					'tokenName' => __( 'Subscription ID', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRICE_NAME',
					'tokenName' => __( 'Subscription name', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRICE_ID',
					'tokenName' => __( 'Subscription price ID', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRICE_AMOUNT',
					'tokenName' => __( 'Subscription amount', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRICE_CURRENCY',
					'tokenName' => __( 'Subscription price currency', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRICE_PERIOD',
					'tokenName' => __( 'Subscription period', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_TAX',
					'tokenName' => __( 'Subscription price tax included', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PAYMENT_METHOD',
					'tokenName' => __( 'Subscription payment method', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRODUCT_NAME',
					'tokenName' => __( 'Subscription product name', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
				array(
					'tokenId'   => 'SUBSCRIPTION_PRODUCT_IMAGE_URL',
					'tokenName' => __( 'Subscription product image URL', 'uncanny-automator' ),
					'tokenType' => 'text',
				),
			);

			return apply_filters( 'automator_surecart_subscription_tokens', $tokens );
		}

		/**
		 * hydrate_subscription_tokens
		 *
		 * @param  mixed $subscription
		 * @return array
		 */
		public function hydrate_subscription_tokens( $subscription_info ) {

			$billing_tokens = $this->hydrate_billing_tokens( $subscription_info->purchase->initial_order->checkout );

			return $billing_tokens + array(
				'SUBSCRIPTION_ID'                => $subscription_info->id,
				'SUBSCRIPTION_PRICE_NAME'        => $subscription_info->price->name,
				'SUBSCRIPTION_PRICE_ID'          => $subscription_info->price->id,
				'SUBSCRIPTION_PRICE_AMOUNT'      => $subscription_info->price->amount,
				'SUBSCRIPTION_PRICE_PERIOD'      => $subscription_info->price->recurring_interval,
				'SUBSCRIPTION_PRICE_CURRENCY'    => $subscription_info->price->currency,
				'SUBSCRIPTION_PAYMENT_METHOD'    => $subscription_info->payment_method->processor_type,
				'SUBSCRIPTION_TAX'               => $subscription_info->price->tax_enabled,
				'SUBSCRIPTION_PRODUCT_NAME'      => $subscription_info->purchase->product->name,
				'SUBSCRIPTION_PRODUCT_IMAGE_URL' => $subscription_info->purchase->product->image_url,
			);
		}
	}

}
