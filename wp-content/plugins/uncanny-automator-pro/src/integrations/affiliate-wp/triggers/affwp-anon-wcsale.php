<?php

namespace Uncanny_Automator_Pro;

/**
 * Class AFFWP_ANON_WCSALE
 *
 * @package Uncanny_Automator_Pro
 */
class AFFWP_ANON_WCSALE extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$this->set_integration( 'AFFWP' );
		$this->set_trigger_code( 'ANON_WCSALES' );
		$this->set_trigger_meta( 'REFERS_WCPRODUCT' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// Trigger sentence - AffiliateWP
		$this->set_sentence( sprintf( esc_attr_x( '{{A WooCommerce product:%1$s}} is purchased using an affiliate referral', 'AffiliateWP', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A WooCommerce product}} is purchased using an affiliate referral', 'AffiliateWP', 'uncanny-automator-pro' ) );
		$this->add_action( 'affwp_insert_referral', 90, 1 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$all_products = Automator()->helpers->recipe->affiliate_wp->options->pro->get_wc_products( null, $this->get_trigger_meta(), array( 'any_option' => true ) );
		$options      = array();
		foreach ( $all_products['options'] as $k => $option ) {
			$options[] = array(
				'text'  => $option,
				'value' => $k,
			);
		}

		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => $all_products['label'],
				'required'    => true,
				'options'     => $options,
			),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		if ( ! isset( $hook_args[0] ) ) {
			return false;
		}

		$selected_product_id = intval( $trigger['meta'][ $this->get_trigger_meta() ] );
		$referral            = affwp_get_referral( $hook_args[0] );

		if ( 'woocommerce' !== (string) $referral->context ) {
			return false;
		}

		$order_id = $referral->reference;
		if ( empty( $order_id ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$items       = $order->get_items();
		$product_ids = array();
		/** @var \WC_Order_Item_Product $item */
		foreach ( $items as $item ) {
			$product_ids[] = (int) $item->get_product_id();
		}

		// Any product or specific product
		if ( intval( '-1' ) === $selected_product_id || in_array( $selected_product_id, $product_ids, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Define Tokens.
	 *
	 * @param array $tokens
	 * @param array $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens = array(
			array(
				'tokenId'   => 'AFFILIATEWPID',
				'tokenName' => __( 'Affiliate ID', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPURL',
				'tokenName' => __( 'Affiliate URL', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPSTATUS',
				'tokenName' => __( 'Affiliate status', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPREGISTERDATE',
				'tokenName' => __( 'Registration date', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPWEBSITE',
				'tokenName' => __( 'Website', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPREFRATETYPE',
				'tokenName' => __( 'Referral rate type', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPREFRATE',
				'tokenName' => __( 'Referral rate', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPCOUPON',
				'tokenName' => __( 'Dynamic coupon', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPACCEMAIL',
				'tokenName' => __( 'Account email', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPPAYMENTEMAIL',
				'tokenName' => __( 'Payment email', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPPROMOMETHODS',
				'tokenName' => __( 'Promotion methods', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AFFILIATEWPNOTES',
				'tokenName' => __( 'Affiliate notes', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALTYPE',
				'tokenName' => __( 'Referral type', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALAMOUNT',
				'tokenName' => __( 'Referral amount', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALDATE',
				'tokenName' => __( 'Referral date', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALDESCRIPTION',
				'tokenName' => __( 'Referral description', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALREFERENCE',
				'tokenName' => __( 'Referral reference', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALCONTEXT',
				'tokenName' => __( 'Referral context', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALCUSTOM',
				'tokenName' => __( 'Referral custom', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'REFERRALSTATUS',
				'tokenName' => __( 'Referral status', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $tokens, $trigger_tokens );
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$referral  = affwp_get_referral( $hook_args[0] );
		$affiliate = affwp_get_affiliate( $referral->affiliate_id );
		$order     = wc_get_order( $referral->reference );
		$user      = get_userdata( $affiliate->user_id );

		$dynamic_coupons = affwp_get_dynamic_affiliate_coupons( $affiliate->ID, false );
		$coupons         = '';
		if ( isset( $dynamic_coupons ) && is_array( $dynamic_coupons ) ) {
			foreach ( $dynamic_coupons as $coupon ) {
				$coupons .= $coupon->coupon_code . '<br/>';
			}
		}

		$parsed_values = array(
			'REFERS_WCPRODUCT'        => $referral->description,
			'REFERRALTYPE'            => $referral->type,
			'AFFILIATEWPID'           => $referral->affiliate_id,
			'AFFILIATEWPSTATUS'       => $affiliate->status,
			'AFFILIATEWPREGISTERDATE' => $affiliate->date_registered,
			'AFFILIATEWPPAYMENTEMAIL' => affwp_get_affiliate_payment_email( $referral->affiliate_id ),
			'AFFILIATEWPACCEMAIL'     => $user->user_email,
			'AFFILIATEWPWEBSITE'      => $user->user_url,
			'AFFILIATEWPURL'          => affwp_get_affiliate_referral_url( array( 'affiliate_id' => $referral->affiliate_id ) ),
			'AFFILIATEWPREFRATE'      => affwp_get_affiliate_rate( $referral->affiliate_id ),
			'AFFILIATEWPREFRATETYPE'  => affwp_get_affiliate_rate_type( $referral->affiliate_id ),
			'AFFILIATEWPPROMOMETHODS' => get_user_meta( $affiliate->user_id, 'affwp_promotion_method', true ),
			'AFFILIATEWPNOTES'        => affwp_get_affiliate_meta( $affiliate->affiliate_id, 'notes', true ),
			'REFERRALAMOUNT'          => number_format( affwp_calc_referral_amount( $order->get_total(), $affiliate->ID ), 2 ),
			'REFERRALDATE'            => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $referral->date ) ),
			'REFERRALDESCRIPTION'     => $referral->description,
			'REFERRALCONTEXT'         => $referral->context,
			'REFERRALREFERENCE'       => $referral->reference,
			'REFERRALCUSTOM'          => $referral->custom,
			'REFERRALSTATUS'          => affwp_get_referral_status( $referral->ID ),
			'AFFILIATEWPCOUPON'       => $coupons,
		);

		return $parsed_values;
	}
}
