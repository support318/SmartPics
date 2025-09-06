<?php


namespace Uncanny_Automator_Pro;

/**
 * Class Wpmu_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wpmu_Pro_Helpers {

	/**
	 * Wpmu_Pro_Helpers constructor.
	 */
	public function __construct( $load_actions = true ) {
		if ( $load_actions ) {
			// all add_actions()
		}
	}

	/**
	 * @param $label
	 * @param $option_code
	 *
	 * @return mixed|null
	 */
	public function get_site_ids( $label = null, $option_code = 'WPMUSITEID' ) {

		if ( ! $label ) {
			$label = esc_attr__( 'Subsite', 'uncanny-automator-pro' );
		}
		$options = array();
		$sites   = get_sites( array( 'number' => 9999 ) );
		if ( $sites ) {
			/** @var \WP_Site $site */
			foreach ( $sites as $site ) {
				$options[ $site->blog_id ] = sprintf( '%s - %s', $site->blogname, $site->domain );
			}
		}

		$option = array(
			'option_code'           => $option_code,
			'label'                 => $label,
			'input_type'            => 'select',
			'required'              => true,
			'options'               => $options,
			'relevant_tokens'       => array(),
			'supports_custom_value' => true,
		);

		return apply_filters( 'uap_option_all_wp_sites', $option );
	}
}
