<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpmu_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wpmu_Pro_Tokens {


	/**
	 * @return array[]
	 */
	public function add_to_blog_tokens() {
		return array(
			'USER_ID' => array(
				'name'         => __( 'User ID', 'uncanny-automator' ),
				'hydrate_with' => 'trigger_args|0',
			),
			'ROLES'   => array(
				'name'         => __( 'Roles', 'uncanny-automator' ),
				//'hydrate_with' => array( $this, 'roles' ),
				'hydrate_with' => 'trigger_args|1',
			),
			'BLOG_ID' => array(
				'name'         => __( 'Site ID', 'uncanny-automator-pro' ),
				'hydrate_with' => 'trigger_args|2',
			),
		);
	}

	/**
	 * @param ...$args
	 *
	 * @return string
	 */
	public function roles( ...$args ) {

		return isset( $args[1]['trigger_args'][1] )
			? join( ', ', array_keys( maybe_unserialize( $args[1]['trigger_args'][1] ) ) )
			: '';
	}
}
