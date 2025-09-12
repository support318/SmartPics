<?php

namespace Uncanny_Automator_Pro;

use EasyAffiliate\Lib\Utils;

/**
 * Class Easy_Affiliate_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Easy_Affiliate_Pro_Helpers {

	public function get_countries() {
		$options   = array();
		$countries = Utils::get_countries();
		foreach ( $countries as $country_key => $country ) {
			$options[ $country_key ] = __( $country, 'uncanny-automator-pro' );
		}

		return $options;
	}

	public function get_transaction_source() {
		$options = array();
		$sources = array(
			'General',
			'MemberPress',
			'WooCommerce',
			'Easy Digital Downloads',
			'WPForms',
			'Formidable',
			'PayPal',
		);
		foreach ( $sources as $source ) {
			$options[ str_replace( ' ', '_', strtolower( $source ) ) ] = __( $source, 'uncanny-automator-pro' );
		}

		return $options;
	}
}
