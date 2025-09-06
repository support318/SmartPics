<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wholesale_Suite_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wholesale_Suite_Pro_Helpers {

	/**
	 * @param null $label
	 * @param string $option_code
	 * @param bool $is_any
	 * @param bool $is_all
	 * @param bool $custom_value
	 *
	 * @return array|mixed|void
	 */
	public function all_wc_products( $label = null, $option_code = 'WOOPRODUCTS', $is_any = false, $is_all = false, $custom_value = false ) {

		if ( ! $label ) {
			$label = esc_attr__( 'Product', 'uncanny-automator-pro' );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 99999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args );

		if ( true === $is_any ) {
			$options = array( '-1' => esc_attr__( 'Any product', 'uncanny-automator-pro' ) ) + $options;
		}

		if ( true === $is_all ) {
			$options = array( '-1' => esc_attr__( 'All products', 'uncanny-automator-pro' ) ) + $options;
		}

		$option = array(
			'option_code'           => $option_code,
			'label'                 => $label,
			'input_type'            => 'select',
			'required'              => true,
			'options'               => $options,
			//'relevant_tokens'       => array(),
			'supports_custom_value' => $custom_value,
		);

		return apply_filters( 'uap_option_all_wc_products', $option );
	}
}
