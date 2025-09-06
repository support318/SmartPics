<?php

namespace Uncanny_Automator_Pro\Integrations\Bitly;

/**
 * Class BITLY_SHORTEN_A_LINK
 *
 * @package Uncanny_Automator_Pro
 */
class BITLY_SHORTEN_A_LINK extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'WP_BITLY' );
		$this->set_action_code( 'BITLY_SHORTEN_URL' );
		$this->set_action_meta( 'LONG_URL' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_is_deprecated( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Shorten {{a URL:%1$s}}', 'Bit.ly', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Shorten {{a URL}}', 'Bit.ly', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return array[]
	 */
	public function options() {

		return array(
			array(
				'option_code'     => $this->get_action_meta(),
				'label'           => esc_attr_x( 'Long URL', 'Bit.ly', 'uncanny-automator-pro' ),
				'input_type'      => 'url',
				'required'        => true,
				'placeholder'     => 'www.example.com/my-page',
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'LONG_URL'  => array(
				'name' => __( 'Long URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'SHORT_URL' => array(
				'name' => __( 'Short URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
		);
	}

	/**
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$long_url = sanitize_url( Automator()->parse->text( $action_data['meta'][ $this->get_action_meta() ], $recipe_id, $user_id, $args ) );

		if ( ! class_exists( '\Wp_Bitly_Api' ) ) {
			$this->add_log_error( 'Class \Wp_Bitly_Api is not found. Make sure Bit.ly plugin is installed and activated.' );

			return false;
		}

		$wp_bitly_api = new \Wp_Bitly_Api();
		$url          = $wp_bitly_api->wpbitly_api( 'shorten' );
		if ( empty( $url ) ) {
			$this->add_log_error( 'WP Bitly Error: No such API endpoint.' );

			return false;
		}

		$wp_bitly_options = new \Wp_Bitly_Options();
		$oauth_token      = $wp_bitly_options->get_option( 'oauth_token' );
		$domain           = $wp_bitly_options->get_option( 'default_domain' );
		$group            = $wp_bitly_options->get_option( 'default_group' );

		$options = array(
			'long_url'   => $long_url,
			'domain'     => $domain,
			'group_guid' => $group,
		);

		$response = $wp_bitly_api->wpbitly_post( $url, $oauth_token, $options );
		if ( false === $response ) {
			$this->add_log_error( sprintf( 'WP Bitly Error: Unable to shorten the given URL: "%s"', $long_url ) );

			return false;
		}

		// Populate the custom token values
		$this->hydrate_tokens(
			array(
				'LONG_URL'  => $long_url,
				'SHORT_URL' => $response['link'],
			)
		);

		return true;
	}

}
