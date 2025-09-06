<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WP_USER_EMAIL_ENDS_WITH
 *
 * @since 5.3
 *
 * @package Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter\WP_USER_EMAIL_ENDS_WITH
 */
final class WP_USER_EMAIL_ENDS_WITH extends Loop_Filter {

	public function setup() {

		$this->set_integration( 'WP' );

		$this->set_meta( 'WP_USER_EMAIL_ENDS_WITH' );

		$this->set_sentence( esc_html_x( "User's email address ends with {{a domain}}", 'WordPress', 'uncanny-automator-pro' ) );

		$this->set_sentence_readable(
			sprintf(
				esc_html_x( "User's email address ends with {{a domain:%1\$s}}", 'WordPress', 'uncanny-automator-pro' ),
				'DOMAIN'
			)
		);

		$this->set_fields( array( $this, 'load_options' ) );

		$this->set_entities( array( $this, 'retrieve_users' ) );

	}

	/**
	 * Loads the fields.
	 *
	 * @return mixed[]
	 */
	public function load_options() {

		return array(
			$this->get_meta() => array(
				array(
					'option_code' => 'DOMAIN',
					'type'        => 'text',
					'label'       => esc_html_x( 'Domain', 'WordPress', 'uncanny-automator' ),
					'required'    => true,
				),
			),
		);

	}


	/**
	 * @param mixed[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users( $fields ) {

		// Bail if domain is empty.
		if ( empty( $fields['DOMAIN'] ) ) {
			return array();
		}

		$email = $this->remove_at_symbol( $fields['DOMAIN'] );

		global $wpdb;

		$like = '%@' . $wpdb->esc_like( $email );

		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s",
				$like
			),
			ARRAY_A
		);

		return ! empty( $users ) ? array_column( $users, 'ID' ) : array();

	}

	/**
	 * Removes at (@) symbol.
	 *
	 * @param $string $domain
	 *
	 * @return string
	 */
	private function remove_at_symbol( $string ) {

		return str_replace( '@', '', $string );

	}

}
