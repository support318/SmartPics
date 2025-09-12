<?php
namespace Uncanny_Automator_Pro\Integrations\WPMU\Tokens\Loopable\Universal;

use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Universal_Loopable_Token;

/**
 * Network_Sites
 *
 * @package Uncanny_Automator\Integrations\WPMU\Tokens\Loopable
 */
class Network_Sites extends Universal_Loopable_Token {

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$child_tokens = array(
			'ID'     => array(
				'name'       => _x( 'Site ID', 'WP', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'DOMAIN' => array(
				'name' => _x( 'Site domain', 'WP', 'uncanny-automator' ),
			),
			'PATH'   => array(
				'name' => _x( 'Site path', 'WP', 'uncanny-automator' ),
			),
		);

		$this->set_id( 'WP_NETWORK_SITES' );
		$this->set_name( _x( 'Network sites', 'WP', 'uncanny-automator' ) );
		$this->set_log_identifier( '#{{ID}} {{DOMAIN}}' );
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

		$sites = get_sites();

		if ( ! empty( $sites ) ) {
			foreach ( $sites as $site ) {
				$network_sites_info = array(
					'ID'     => $site->blog_id,
					'DOMAIN' => $site->domain,
					'PATH'   => $site->path,
				);
				$loopable->create_item( $network_sites_info );
			}
		}

		return $loopable;

	}

}
