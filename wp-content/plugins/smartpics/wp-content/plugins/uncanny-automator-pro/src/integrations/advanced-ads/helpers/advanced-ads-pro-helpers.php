<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Advanced_Ads_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Advanced_Ads_Pro_Helpers {

	/**
	 * @param $option_code
	 * @param $is_any
	 * @param $label
	 * @param $tokens
	 *
	 * @return array|mixed|void
	 */
	public function ad_types( $option_code, $is_any = false, $label = null, $tokens = array() ) {
		$get_types = \Advanced_Ads::get_instance()->ad_types;
		$types     = array();
		foreach ( $get_types as $key => $type ) {
			if ( false === strpos( $key, 'upgrade' ) ) {
				$types[ $type->ID ] = __( $type->title, 'uncanny-automator-pro' );
			}
		}

		if ( true === $is_any ) {
			$types = array( '-1' => __( 'Any type', 'uncanny-automator' ) ) + $types;
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => ( empty( $label ) ) ? esc_attr__( 'Type', 'uncanny-automator' ) : $label,
			'input_type'      => 'select',
			'required'        => true,
			'options_show_id' => true,
			'relevant_tokens' => $tokens,
			'options'         => $types,
		);

		return apply_filters( 'uap_option_ad_types', $option );
	}

}
