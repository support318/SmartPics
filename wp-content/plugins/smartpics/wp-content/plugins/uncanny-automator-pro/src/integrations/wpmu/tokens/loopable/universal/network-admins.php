<?php
namespace Uncanny_Automator_Pro\Integrations\WPMU\Tokens\Loopable\Universal;

use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Universal_Loopable_Token;

/**
 * Network_Admins
 *
 * @package Uncanny_Automator\Integrations\WPMU\Tokens\Loopable
 */
class Network_Admins extends Universal_Loopable_Token {

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$child_tokens = array(
			'ID'       => array(
				'name'       => _x( 'Network admin ID', 'WP', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'EMAIL'    => array(
				'name'       => _x( 'Network admin email', 'WP', 'uncanny-automator' ),
				'token_type' => 'email',
			),
			'USERNAME' => array(
				'name' => _x( 'Network admin username', 'WP', 'uncanny-automator' ),
			),
		);

		$this->set_id( 'WP_NETWORK_ADMINS' );
		$this->set_name( _x( 'Network admins', 'WP', 'uncanny-automator' ) );
		$this->set_log_identifier( '# {{ID}} {{EMAIL}}' );
		$this->set_child_tokens( $child_tokens );
		$this->set_requires_user( false );

	}

	/**
	 * Hydrate the tokens.
	 *
	 * @param mixed $args
	 * @return Loopable_Token_Collection
	 */
	public function hydrate_token_loopable( $args ) {

		$loopable = new Loopable_Token_Collection();

		$super_admins = get_super_admins();

		if ( ! empty( $super_admins ) ) {
			foreach ( $super_admins as $admin_username ) {
				$user = get_user_by( 'login', $admin_username );
				if ( $user ) {
					$super_admins_info = array(
						'ID'       => $user->ID,
						'USERNAME' => $user->user_login,
						'EMAIL'    => $user->user_email,
					);

					$loopable->create_item( $super_admins_info );
				}
			}
		}

		return $loopable;

	}

}
